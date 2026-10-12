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

    /** List students in a teacher's active classes, with their review for a month. */
    public function listForTeacher($teacherId, $month)
    {
        $teacherId = (int) $teacherId;
        $month = (string) $month;
        if ($teacherId <= 0 || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return array();
        }
        return $this->db->safeFetch(
            'select c.id as class_id, c.class_code, c.name as class_name,'
            . ' sub.name as subject_name, s.user_id as student_id, s.student_code, s.full_name as student_name,'
            . ' r.id as review_id, r.review_month, r.overall_rating, r.content, r.progress_percent, r.published_at'
            . ' from db_lms_classes c'
            . ' join db_lms_class_students cs on cs.class_id=c.id and cs.status="active"'
            . ' join db_lms_students s on s.user_id=cs.student_id'
            . ' left join db_lms_subjects sub on sub.id=c.subject_id'
            . ' left join db_lms_monthly_reviews r on r.class_id=c.id and r.student_id=s.user_id and r.review_month=?'
            . ' where c.teacher_id=? and c.status="active" and c.deleted_at is null'
            . ' order by c.name, s.full_name',
            array($month, $teacherId)
        );
    }

    /** Create/update one monthly review after verifying teacher owns the active enrolment. */
    public function saveForTeacher($teacherId, array $data, $publish = false)
    {
        $this->clearErrors();
        $teacherId = (int) $teacherId;
        $classId = isset($data['class_id']) ? (int) $data['class_id'] : 0;
        $studentId = isset($data['student_id']) ? (int) $data['student_id'] : 0;
        $month = isset($data['review_month']) ? trim((string) $data['review_month']) : '';
        $rating = isset($data['overall_rating']) ? (string) $data['overall_rating'] : '';
        $content = isset($data['content']) ? trim((string) $data['content']) : '';
        $percent = isset($data['progress_percent']) && (string) $data['progress_percent'] !== ''
            ? (float) $data['progress_percent'] : null;

        if ($teacherId <= 0 || $classId <= 0 || $studentId <= 0
            || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            $this->addError('system', 'Thông tin lớp, học viên hoặc tháng không hợp lệ.');
            return false;
        }
        if ($rating !== '' && !isset(self::OVERALL_RATINGS[$rating])) {
            $this->addError('overall_rating', 'Mức đánh giá không hợp lệ.');
            return false;
        }
        if ($publish && $rating === '') {
            $this->addError('overall_rating', 'Chọn mức đánh giá trước khi đăng nhận xét.');
            return false;
        }
        if ($content === '') {
            $this->addError('content', 'Nội dung nhận xét là bắt buộc.');
            return false;
        }
        if (mb_strlen($content, 'UTF-8') > 10000) {
            $this->addError('content', 'Nội dung nhận xét không được quá 10.000 ký tự.');
            return false;
        }
        if ($percent !== null && ($percent < 0 || $percent > 100)) {
            $this->addError('progress_percent', 'Tiến bộ phải nằm trong khoảng 0–100%.');
            return false;
        }
        if ($publish && $percent === null) {
            $this->addError('progress_percent', 'Nhập tỷ lệ tiến bộ trước khi đăng nhận xét.');
            return false;
        }
        $enrolment = $this->db->safeFetchOne(
            'select c.id from db_lms_classes c join db_lms_class_students cs on cs.class_id=c.id'
            . ' where c.id=? and c.teacher_id=? and c.status="active" and c.deleted_at is null'
            . ' and cs.student_id=? and cs.status="active" limit 1',
            array($classId, $teacherId, $studentId)
        );
        if (empty($enrolment)) {
            $this->addError('system', 'Học viên không thuộc lớp đang dạy của bạn.');
            return false;
        }
        $before = $this->db->safeFetchOne(
            'select * from db_lms_monthly_reviews where student_id=? and class_id=? and review_month=? limit 1',
            array($studentId, $classId, $month)
        );
        $publishedAt = $publish ? (!empty($before['published_at']) ? $before['published_at'] : $this->now()) : null;
        $values = array(
            'overall_rating' => $rating !== '' ? $rating : null,
            'content' => $content,
            'progress_percent' => $percent,
            'published_at' => $publishedAt,
        );
        if (!empty($before)) {
            $ok = $this->db->update('db_lms_monthly_reviews', $values,
                'id=? and teacher_id=?', array((int) $before['id'], $teacherId));
            $reviewId = (int) $before['id'];
        } else {
            $values += array('student_id'=>$studentId,'class_id'=>$classId,'teacher_id'=>$teacherId,'review_month'=>$month);
            $ok = $this->db->insert('db_lms_monthly_reviews', $values);
            $reviewId = (int) $this->db->insertId;
        }
        if (!$ok) {
            $this->addError('system', 'Không thể lưu nhận xét tháng.');
            return false;
        }
        $this->audit($publish ? 'publish_monthly_review' : 'save_monthly_review', 'monthly_reviews', $reviewId, $before, $values, 'teacher');
        return true;
    }

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
