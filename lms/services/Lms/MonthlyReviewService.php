<?php
/**
 * MonthlyReviewService — portal Student: nhận xét tháng (STU-04).
 *
 * Row-level tự nhiên qua db_lms_monthly_reviews.student_id; HV chỉ thấy nhận xét
 * đã published (published_at is not null) của các lớp mình đang học.
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class MonthlyReviewService extends LmsService
{
    /** Đánh giá tổng thể (db_lms_monthly_reviews.overall_rating). */
    const OVERALL_RATINGS = array(
        'excellent'        => 'Xuất sắc',
        'good'             => 'Khá',
        'average'          => 'Trung bình',
        'need_improvement' => 'Cần cố gắng',
    );

    /**
     * Danh sách lớp HV đang học + nhận xét tháng đã published của từng lớp.
     *
     * @param int         $studentId
     * @param string|null $month 'YYYY-MM' (null → không lọc)
     * @return array array('items' => array(class rows kèm 'reviews'), 'months' => array)
     */
    public function listForStudent($studentId, $month = null)
    {
        $studentId = (int) $studentId;
        if ($studentId <= 0) {
            return array('items' => array(), 'months' => array());
        }

        // ---- Lớp HV đang học (card luôn hiện, dù chưa có nhận xét) ----
        $classes = $this->db->safeFetch(
            'select c.id, c.name, c.class_code, c.subject_id,'
            . ' sub.name as subject_name,'
            . ' t.full_name as teacher_name'
            . ' from db_lms_class_students cs'
            . ' join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
            . ' left join db_lms_subjects sub on sub.id = c.subject_id'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' where cs.student_id = ? and cs.status = ? and c.status = ?'
            . ' order by c.name',
            array($studentId, 'active', 'active')
        );

        $classIds = array();
        foreach ($classes as $c) {
            $classIds[] = (int) $c['id'];
        }

        // ---- Nhận xét đã published (1 query, group PHP) ----
        $reviews = array();
        $monthSet = array();
        if ($classIds !== array()) {
            $placeholders = implode(', ', array_fill(0, count($classIds), '?'));
            $sql = 'select r.id, r.student_id, r.class_id, r.teacher_id, r.review_month,'
                . ' r.overall_rating, r.content, r.progress_percent, r.published_at,'
                . ' t.full_name as teacher_name'
                . ' from db_lms_monthly_reviews r'
                . ' left join db_lms_teachers t on t.user_id = r.teacher_id'
                . ' where r.student_id = ? and r.published_at is not null'
                . ' and r.class_id in (' . $placeholders . ')';
            $params = array_merge(array($studentId), $classIds);
            if ($month !== null && preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
                $sql .= ' and r.review_month = ?';
                $params[] = (string) $month;
            }
            $sql .= ' order by r.review_month desc, r.id';
            $reviews = $this->db->safeFetch($sql, $params);
        }

        // ---- Gom reviews theo class + danh sách tháng có dữ liệu ----
        $byClass = array();
        foreach ($reviews as $r) {
            $cid = (int) $r['class_id'];
            if (!isset($byClass[$cid])) {
                $byClass[$cid] = array();
            }
            $byClass[$cid][] = $r;
            $m = (string) $r['review_month'];
            if (!in_array($m, $monthSet, true)) {
                $monthSet[] = $m;
            }
        }

        // ---- Lịch học text cho mỗi lớp ("Thứ 2 19:00-20:30 / Thứ 4 ...") ----
        $scheduleMap = $this->schedulesMap($classIds);

        $items = array();
        foreach ($classes as $c) {
            $cid = (int) $c['id'];
            $c['reviews'] = isset($byClass[$cid]) ? $byClass[$cid] : array();
            $c['schedules_text'] = isset($scheduleMap[$cid]) ? $scheduleMap[$cid] : '';
            $items[] = $c;
        }

        rsort($monthSet);
        $months = array();
        foreach ($monthSet as $m) {
            $months[$m] = $this->monthLabel($m);
        }

        return array('items' => $items, 'months' => $months);
    }

    /**
     * Nhãn tháng hiển thị: "2026-07" → "Tháng 7 / 2026".
     *
     * @param string $month 'YYYY-MM'
     * @return string
     */
    public function monthLabel($month)
    {
        $ts = strtotime((string) $month . '-01');
        if ($ts === false) {
            return (string) $month;
        }
        return 'Tháng ' . (int) date('n', $ts) . ' / ' . date('Y', $ts);
    }

    /**
     * Map class_id → text lịch học.
     *
     * @param array $classIds
     * @return array
     */
    private function schedulesMap(array $classIds)
    {
        $out = array();
        if ($classIds === array()) {
            return $out;
        }
        $placeholders = implode(', ', array_fill(0, count($classIds), '?'));
        $rows = $this->db->safeFetch(
            'select class_id, day_of_week, start_time, end_time from db_lms_schedules'
            . ' where class_id in (' . $placeholders . ')'
            . ' order by day_of_week, start_time',
            $classIds
        );
        $weekdays = \Lms\UserService::WEEKDAYS;
        foreach ($rows as $r) {
            $cid = (int) $r['class_id'];
            $dow = (int) $r['day_of_week'];
            $label = isset($weekdays[$dow]) ? $weekdays[$dow] : '';
            $text = $label . ' ' . substr((string) $r['start_time'], 0, 5)
                . '-' . substr((string) $r['end_time'], 0, 5);
            if (isset($out[$cid])) {
                $out[$cid] .= ' / ' . $text;
            } else {
                $out[$cid] = $text;
            }
        }
        return $out;
    }
}
