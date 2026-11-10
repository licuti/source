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
            'select c.id as class_id, c.class_code, c.name as class_name, c.class_type,'
            . ' sub.name as subject_name, s.user_id as student_id, s.student_code, s.full_name as student_name,'
            . ' s.grade, s.avatar,'
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

    /**
     * Gom danh sách học viên theo lớp cho giao diện card Teacher.
     *
     * Lớp 1-1 có 1 học viên; lớp nhóm có nhiều học viên (mỗi HV một field nhận xét).
     *
     * @param int    $teacherId
     * @param string $month 'YYYY-MM'
     * @return array
     */
    public function listClassesForTeacher($teacherId, $month)
    {
        $rows = $this->listForTeacher($teacherId, $month);
        if (empty($rows)) {
            return array();
        }

        $classIds = array();
        foreach ($rows as $row) {
            $classIds[] = (int) $row['class_id'];
        }
        $classIds = array_values(array_unique($classIds));
        $scheduleMap = $this->scheduleRowsMap($classIds);

        $classes = array();
        foreach ($rows as $row) {
            $cid = (int) $row['class_id'];
            if (!isset($classes[$cid])) {
                $classes[$cid] = array(
                    'class_id'      => $cid,
                    'class_code'    => (string) $row['class_code'],
                    'class_name'    => (string) $row['class_name'],
                    'class_type'    => (string) $row['class_type'],
                    'subject_name'  => (string) $row['subject_name'],
                    'schedule_rows' => isset($scheduleMap[$cid]) ? $scheduleMap[$cid] : array(),
                    'students'      => array(),
                );
            }

            $classes[$cid]['students'][] = array(
                'student_id'       => (int) $row['student_id'],
                'student_code'     => (string) $row['student_code'],
                'student_name'     => (string) $row['student_name'],
                'grade'            => (string) $row['grade'],
                'avatar'           => (string) $row['avatar'],
                'review_id'        => !empty($row['review_id']) ? (int) $row['review_id'] : 0,
                'review_month'     => (string) $row['review_month'],
                'overall_rating'   => (string) $row['overall_rating'],
                'content'          => (string) $row['content'],
                'progress_percent' => $row['progress_percent'],
                'published_at'     => $row['published_at'],
            );
        }

        return array_values($classes);
    }

    /**
     * Tháng cho dropdown Teacher: 12 tháng gần nhất + các tháng đã có nhận xét.
     *
     * @param int $teacherId
     * @param int $monthsBack
     * @return array map 'YYYY-MM' => 'Tháng N / YYYY' (mới nhất trước)
     */
    public function teacherMonthOptions($teacherId, $monthsBack = 12)
    {
        $options = array();
        $monthsBack = max(1, (int) $monthsBack);
        for ($i = 0; $i < $monthsBack; $i++) {
            $month = date('Y-m', strtotime(date('Y-m-01') . ' -' . $i . ' month'));
            $options[$month] = $this->monthLabel($month);
        }

        $rows = $this->db->safeFetch(
            'select distinct review_month from db_lms_monthly_reviews where teacher_id = ?',
            array((int) $teacherId)
        );
        foreach ($rows as $row) {
            $month = (string) $row['review_month'];
            if ($month !== '' && !isset($options[$month])) {
                $options[$month] = $this->monthLabel($month);
            }
        }

        krsort($options);
        return $options;
    }

    /** Create/update one monthly review after verifying teacher owns the active enrolment. */
    public function saveForTeacher($teacherId, array $data, $publish = false)
    {
        $this->clearErrors();
        $teacherId = (int) $teacherId;
        $classId = isset($data['class_id']) ? (int) $data['class_id'] : 0;
        $studentId = isset($data['student_id']) ? (int) $data['student_id'] : 0;
        $month = isset($data['review_month']) ? trim((string) $data['review_month']) : '';
        $hasRating = array_key_exists('overall_rating', $data);
        $rating = $hasRating ? (string) $data['overall_rating'] : '';
        $content = isset($data['content']) ? trim((string) $data['content']) : '';
        $hasPercent = array_key_exists('progress_percent', $data);
        $percent = $hasPercent && (string) $data['progress_percent'] !== ''
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
        // Form mới chỉ gửi nội dung → giữ nguyên đánh giá/tiến bộ đã có thay vì xoá trắng.
        if (!empty($before)) {
            if (!$hasRating) {
                $rating = (string) $before['overall_rating'];
            }
            if (!$hasPercent) {
                $percent = $before['progress_percent'] === null ? null : (float) $before['progress_percent'];
            }
        }
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
            . ' t.full_name as teacher_name, st.grade'
            . ' from db_lms_class_students cs'
            . ' join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
            . ' left join db_lms_students st on st.user_id = cs.student_id'
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

        // ---- Lịch học mỗi lớp: ưu tiên lịch cycle đang hiệu lực, fallback lịch lớp ----
        $scheduleMap = $this->scheduleRowsMap($classIds);

        $items = array();
        foreach ($classes as $c) {
            $cid = (int) $c['id'];
            $c['reviews'] = isset($byClass[$cid]) ? $byClass[$cid] : array();

            $rows = isset($scheduleMap[$cid]) ? $scheduleMap[$cid] : array();
            $texts = array();
            foreach ($rows as $row) {
                // Giữ định dạng cũ "19:00-20:30" cho schedules_text (tương thích test/consumer cũ).
                $texts[] = $row['day'] . ' ' . str_replace(' - ', '-', $row['time']);
            }
            $c['schedules_text'] = implode(' / ', $texts);
            $c['schedule_rows'] = $rows;

            // Tháng có nhận xét của riêng lớp (dropdown từng card).
            $classMonths = array();
            foreach ($c['reviews'] as $review) {
                $reviewMonth = (string) $review['review_month'];
                if (!isset($classMonths[$reviewMonth])) {
                    $classMonths[$reviewMonth] = $this->monthLabel($reviewMonth);
                }
            }
            $c['months'] = $classMonths;

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
     * Map class_id → danh sách lịch học (day + time).
     *
     * Ưu tiên lịch của cycle đang hiệu lực; lớp chưa có cycle/schedule thì
     * fallback về db_lms_schedules để không làm mất dữ liệu cũ.
     *
     * @param array $classIds
     * @return array
     */
    public function scheduleRowsMap(array $classIds)
    {
        $out = array();
        if ($classIds === array()) {
            return $out;
        }
        $placeholders = implode(', ', array_fill(0, count($classIds), '?'));
        $rows = $this->db->safeFetch(
            'select cc.class_id, sch.day_of_week, sch.start_time, sch.end_time'
            . ' from db_lms_course_cycles cc'
            . ' join db_lms_cycle_schedules sch on sch.cycle_id = cc.id'
            . ' where cc.class_id in (' . $placeholders . ') and cc.status = ?'
            . ' and sch.effective_from <= curdate()'
            . ' and (sch.effective_to is null or sch.effective_to >= curdate())'
            . ' order by cc.class_id, sch.day_of_week, sch.start_time',
            array_merge($classIds, array('active'))
        );

        $fallbackIds = array();
        $seen = array();
        foreach ($rows as $r) {
            $cid = (int) $r['class_id'];
            $seen[$cid] = true;
            $out[$cid][] = $this->formatScheduleRow($r);
        }
        foreach ($classIds as $cid) {
            if (!isset($seen[(int) $cid])) {
                $fallbackIds[] = (int) $cid;
            }
        }

        if ($fallbackIds !== array()) {
            $fbPlaceholders = implode(', ', array_fill(0, count($fallbackIds), '?'));
            $fallback = $this->db->safeFetch(
                'select class_id, day_of_week, start_time, end_time from db_lms_schedules'
                . ' where class_id in (' . $fbPlaceholders . ')'
                . ' order by class_id, day_of_week, start_time',
                $fallbackIds
            );
            foreach ($fallback as $r) {
                $cid = (int) $r['class_id'];
                $out[$cid][] = $this->formatScheduleRow($r);
            }
        }

        return $out;
    }

    /**
     * Chuẩn hoá một dòng lịch học cho template.
     *
     * @param array $row
     * @return array array('day' => 'Thứ 2', 'time' => '19:00 - 20:30')
     */
    private function formatScheduleRow(array $row)
    {
        $weekdays = \Lms\UserService::WEEKDAYS;
        $dow = (int) $row['day_of_week'];
        return array(
            'day'  => isset($weekdays[$dow]) ? $weekdays[$dow] : '',
            'time' => substr((string) $row['start_time'], 0, 5)
                . ' - ' . substr((string) $row['end_time'], 0, 5),
        );
    }
}
