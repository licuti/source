<?php
/**
 * StudentCourseService — portal Student: khoá học + nộp BTVN/Test (STU-01/02/03).
 *
 * Row-level mọi nơi qua db_lms_class_students (status = 'active') — HV chỉ thấy
 * lớp đang học, session + topic của lớp đó (guide §3.5). Dựa trên enrolment
 * (không phải session_students) để HV enrol giữa khoá vẫn thấy đủ buổi đã học.
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class StudentCourseService extends LmsService
{
    /** Trạng thái nộp BTVN (db_lms_session_students.hw_submission_status). */
    const HW_SUBMISSION_STATUS = array(
        'not_submitted'     => 'Chưa nộp BTVN',
        'submitted_on_time' => 'Đã nộp đúng hạn',
        'submitted_late'    => 'Đã nộp trễ hạn',
    );

    /** Trạng thái nộp test (db_lms_test_submissions.submission_status). */
    const TEST_SUBMISSION_STATUS = array(
        'not_submitted'     => 'Chưa nộp bài',
        'submitted_on_time' => 'Đã nộp đúng hạn',
        'submitted_late'    => 'Đã nộp trễ hạn',
    );

    /** Trạng thái bài test (db_lms_tests.status). */
    const TEST_STATUS = array(
        'draft'     => 'Bản nháp',
        'published' => 'Đã đăng',
        'closed'    => 'Đã đóng',
    );

    /** Số file nộp tối đa (mockup STU-03: 4 ô upload). */
    const MAX_FILES = 4;

    /**
     * Danh sách lớp HV đang học (kèm % hoàn thành).
     *
     * @param int $studentId
     * @return array rows: id, name, class_code, class_type, subject_name,
     *               teacher_name, total_sessions, remaining_sessions, progress_percent
     */
    public function listMyClasses($studentId)
    {
        $studentId = (int) $studentId;
        if ($studentId <= 0) {
            return array();
        }

        $rows = $this->db->safeFetch(
            'select c.id, c.name, c.class_code, c.class_type, c.subject_id,'
            . ' c.total_sessions, c.remaining_sessions, c.start_date,'
            . ' sub.name as subject_name,'
            . ' t.full_name as teacher_name'
            . ' from db_lms_class_students cs'
            . ' join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
            . ' left join db_lms_subjects sub on sub.id = c.subject_id'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' where cs.student_id = ? and cs.status = ? and c.status = ?'
            . ' order by sub.name, c.name',
            array($studentId, 'active', 'active')
        );

        foreach ($rows as $i => $row) {
            $total = (int) $row['total_sessions'];
            $remaining = (int) $row['remaining_sessions'];
            $done = $total - $remaining;
            $rows[$i]['done_sessions'] = $done < 0 ? 0 : $done;
            $rows[$i]['progress_percent'] = $total > 0
                ? min(100, max(0, (int) round($done / $total * 100)))
                : 0;
        }
        return $rows;
    }

    /**
     * Chi tiết 1 lớp (row-level: HV phải đang active trong lớp).
     *
     * @param int $classId
     * @param int $studentId
     * @return array|null
     */
    public function getClassForStudent($classId, $studentId)
    {
        $classId = (int) $classId;
        $studentId = (int) $studentId;
        if ($classId <= 0 || $studentId <= 0) {
            return null;
        }

        $row = $this->db->safeFetchOne(
            'select c.id, c.name, c.class_code, c.class_type, c.subject_id,'
            . ' c.teacher_id, c.total_sessions, c.remaining_sessions,'
            . ' c.hw_deadline_time, c.hw_check_hours,'
            . ' sub.name as subject_name,'
            . ' t.full_name as teacher_name'
            . ' from db_lms_class_students cs'
            . ' join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
            . ' left join db_lms_subjects sub on sub.id = c.subject_id'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' where cs.class_id = ? and cs.student_id = ? and cs.status = ?'
            . ' limit 1',
            array($classId, $studentId, 'active')
        );
        if (empty($row) || !is_array($row)) {
            return null;
        }

        $total = (int) $row['total_sessions'];
        $done = $total - (int) $row['remaining_sessions'];
        $row['done_sessions'] = $done < 0 ? 0 : $done;
        $row['progress_percent'] = $total > 0
            ? min(100, max(0, (int) round($done / $total * 100)))
            : 0;

        $row['schedules'] = $this->db->safeFetch(
            'select day_of_week, start_time, end_time from db_lms_schedules'
            . ' where class_id = ? order by day_of_week, start_time',
            array($classId)
        );
        return $row;
    }

    /**
     * Lộ trình chuyên đề của 1 lớp (row-level) + điểm test/minitest + tiến độ.
     *
     * @param int $classId
     * @param int $studentId
     * @return array array('topics' => array, 'tests' => array) — rỗng khi không có quyền
     */
    public function listTopicsForClass($classId, $studentId)
    {
        $classId = (int) $classId;
        $studentId = (int) $studentId;
        if ($classId <= 0 || $studentId <= 0) {
            return array('topics' => array(), 'tests' => array());
        }
        if ($this->getClassForStudent($classId, $studentId) === null) {
            return array('topics' => array(), 'tests' => array());
        }

        $topics = $this->db->safeFetch(
            'select t.id, t.subject_id, t.name, t.has_test, t.has_minitest,'
            . ' t.max_sessions, t.order_index, t.report_template'
            . ' from db_lms_topics t'
            . ' where t.subject_id = (select subject_id from db_lms_classes where id = ?)'
            . ' order by t.order_index, t.name',
            array($classId)
        );

        if (empty($topics)) {
            return array('topics' => array(), 'tests' => array());
        }

        // ---- Số buổi đã dạy theo chuyên đề (1 query, group PHP) ----
        $today = date('Y-m-d');
        $sessions = $this->db->safeFetch(
            'select id, topic_id, topic_ids, session_date, status'
            . ' from db_lms_sessions'
            . ' where class_id = ? and session_date <= ? and status <> ?',
            array($classId, $today, 'cancelled')
        );
        $doneCount = array();
        foreach ($sessions as $s) {
            $topicIds = \Lms\UserService::decodeJsonArray(isset($s['topic_ids']) ? $s['topic_ids'] : array());
            if (!in_array((int) $s['topic_id'], $topicIds, true) && !empty($s['topic_id'])) {
                $topicIds[] = (int) $s['topic_id'];
            }
            foreach ($topicIds as $tid) {
                $tid = (int) $tid;
                if ($tid <= 0) {
                    continue;
                }
                if (!isset($doneCount[$tid])) {
                    $doneCount[$tid] = 0;
                }
                $doneCount[$tid]++;
            }
        }

        // ---- Bài test/minitest + điểm gần nhất của HV (1 query) ----
        $tests = $this->db->safeFetch(
            'select te.id, te.topic_id, te.test_type, te.title, te.test_file_url,'
            . ' te.deadline, te.status, te.max_score,'
            . ' ts.id as submission_id, ts.submitted_at, ts.submission_status,'
            . ' ts.score_x, ts.score_y_percent, ts.graded_at'
            . ' from db_lms_tests te'
            . ' left join db_lms_test_submissions ts'
            . ' on ts.test_id = te.id and ts.student_id = ?'
            . ' where te.class_id = ?'
            . ' order by te.test_type, te.deadline, te.id',
            array($studentId, $classId)
        );

        foreach ($topics as $i => $topic) {
            $tid = (int) $topic['id'];
            $done = isset($doneCount[$tid]) ? $doneCount[$tid] : 0;
            $max = $topic['max_sessions'] === null ? null : (int) $topic['max_sessions'];
            $topics[$i]['done_sessions'] = $done;
            $topics[$i]['max_sessions'] = $max;
            // Nút "Nộp test" active khi đã đủ số buổi (max_sessions NULL → luôn mở)
            $topics[$i]['test_ready'] = $max === null || $done >= $max;
        }

        return array('topics' => $topics, 'tests' => $tests);
    }

    /**
     * Test + bài nộp của HV cho 1 chuyên đề (dùng cho section Test/Minitest §6/§8).
     *
     * @param int $classId
     * @param int $topicId
     * @param int $studentId
     * @return array
     */
    public function listTestsForTopic($classId, $topicId, $studentId)
    {
        $classId = (int) $classId;
        $topicId = (int) $topicId;
        $studentId = (int) $studentId;
        if ($classId <= 0 || $topicId <= 0 || $studentId <= 0) {
            return array();
        }
        if ($this->getClassForStudent($classId, $studentId) === null) {
            return array();
        }

        $rows = $this->db->safeFetch(
            'select te.id, te.topic_id, te.test_type, te.title, te.test_file_url,'
            . ' te.deadline, te.status, te.max_score,'
            . ' ts.id as submission_id, ts.submission_files, ts.submitted_at,'
            . ' ts.submission_status, ts.score_x, ts.score_y_percent, ts.graded_at,'
            . ' ts.teacher_feedback_files'
            . ' from db_lms_tests te'
            . ' left join db_lms_test_submissions ts'
            . ' on ts.test_id = te.id and ts.student_id = ?'
            . ' where te.class_id = ? and te.topic_id = ?'
            . ' order by te.test_type, te.deadline, te.id',
            array($studentId, $classId, $topicId)
        );

        foreach ($rows as $i => $row) {
            $rows[$i]['submission_files'] = \Lms\UserService::decodeJsonArray(
                isset($row['submission_files']) ? $row['submission_files'] : array()
            );
            $rows[$i]['teacher_feedback_files'] = \Lms\UserService::decodeJsonArray(
                isset($row['teacher_feedback_files']) ? $row['teacher_feedback_files'] : array()
            );
        }
        return $rows;
    }

    /**
     * Danh sách buổi học của 1 chuyên đề (row-level qua enrolment).
     *
     * @param int $classId
     * @param int $topicId
     * @param int $studentId
     * @return array
     */
    public function listSessionsForTopic($classId, $topicId, $studentId)
    {
        $classId = (int) $classId;
        $topicId = (int) $topicId;
        $studentId = (int) $studentId;
        if ($classId <= 0 || $topicId <= 0 || $studentId <= 0) {
            return array();
        }
        if ($this->getClassForStudent($classId, $studentId) === null) {
            return array();
        }

        $rows = $this->db->safeFetch(
            'select s.id, s.class_id, s.session_date, s.start_time, s.end_time,'
            . ' s.lesson_summary, s.hw_content, s.hw_deadline, s.hw_attached_files,'
            . ' s.status, s.topic_ids, s.topic_id,'
            . ' ss.id as ss_id, ss.hw_submission_files, ss.hw_submitted_at,'
            . ' ss.hw_submission_status, ss.teacher_feedback_text,'
            . ' ss.teacher_feedback_files, ss.hw_graded_at, ss.grade, ss.status as ss_status'
            . ' from db_lms_sessions s'
            . ' left join db_lms_session_students ss'
            . ' on ss.session_id = s.id and ss.student_id = ?'
            . ' where s.class_id = ?'
            . ' and (json_contains(s.topic_ids, cast(? as json)) or s.topic_id = ?)'
            . ' order by s.session_date, s.start_time',
            array($studentId, $classId, (string) $topicId, $topicId)
        );

        foreach ($rows as $i => $row) {
            $rows[$i]['hw_attached_files'] = \Lms\UserService::decodeJsonArray(
                isset($row['hw_attached_files']) ? $row['hw_attached_files'] : array()
            );
            $rows[$i]['hw_submission_files'] = \Lms\UserService::decodeJsonArray(
                isset($row['hw_submission_files']) ? $row['hw_submission_files'] : array()
            );
            $rows[$i]['teacher_feedback_files'] = \Lms\UserService::decodeJsonArray(
                isset($row['teacher_feedback_files']) ? $row['teacher_feedback_files'] : array()
            );
            $rows[$i]['topic_ids'] = \Lms\UserService::decodeJsonArray(
                isset($row['topic_ids']) ? $row['topic_ids'] : array()
            );
        }
        return $rows;
    }

    /**
     * Chi tiết 1 buổi học cho HV (row-level qua enrolment lớp).
     *
     * @param int $sessionId
     * @param int $studentId
     * @return array|null
     */
    public function getSessionForStudent($sessionId, $studentId)
    {
        $sessionId = (int) $sessionId;
        $studentId = (int) $studentId;
        if ($sessionId <= 0 || $studentId <= 0) {
            return null;
        }

        $row = $this->db->safeFetchOne(
            'select s.id, s.class_id, s.session_date, s.start_time, s.end_time,'
            . ' s.lesson_summary, s.hw_content, s.hw_deadline, s.hw_attached_files,'
            . ' s.status, s.topic_ids, s.topic_id,'
            . ' c.name as class_name, c.class_code, c.subject_id,'
            . ' c.hw_deadline_time, c.hw_check_hours,'
            . ' t.full_name as teacher_name,'
            . ' ss.id as ss_id, ss.hw_submission_files, ss.hw_submitted_at,'
            . ' ss.hw_submission_status, ss.teacher_feedback_text,'
            . ' ss.teacher_feedback_files, ss.hw_graded_at, ss.grade, ss.status as ss_status'
            . ' from db_lms_sessions s'
            . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
            . ' join db_lms_class_students cs'
            . ' on cs.class_id = c.id and cs.student_id = ? and cs.status = ?'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' left join db_lms_session_students ss'
            . ' on ss.session_id = s.id and ss.student_id = ?'
            . ' where s.id = ?'
            . ' limit 1',
            array($studentId, 'active', $studentId, $sessionId)
        );
        if (empty($row) || !is_array($row)) {
            return null;
        }

        $row['hw_attached_files'] = \Lms\UserService::decodeJsonArray(
            isset($row['hw_attached_files']) ? $row['hw_attached_files'] : array()
        );
        $row['hw_submission_files'] = \Lms\UserService::decodeJsonArray(
            isset($row['hw_submission_files']) ? $row['hw_submission_files'] : array()
        );
        $row['teacher_feedback_files'] = \Lms\UserService::decodeJsonArray(
            isset($row['teacher_feedback_files']) ? $row['teacher_feedback_files'] : array()
        );
        $row['topic_ids'] = \Lms\UserService::decodeJsonArray(
            isset($row['topic_ids']) ? $row['topic_ids'] : array()
        );
        return $row;
    }

    /**
     * Chi tiết 1 bài test cho HV (row-level: test thuộc lớp HV đang học).
     *
     * @param int $testId
     * @param int $studentId
     * @return array|null
     */
    public function getTestForStudent($testId, $studentId)
    {
        $testId = (int) $testId;
        $studentId = (int) $studentId;
        if ($testId <= 0 || $studentId <= 0) {
            return null;
        }

        $row = $this->db->safeFetchOne(
            'select te.id, te.class_id, te.topic_id, te.test_type, te.title,'
            . ' te.test_file_url, te.deadline, te.status, te.max_score,'
            . ' c.name as class_name, c.subject_id,'
            . ' ts.id as submission_id, ts.submitted_at, ts.submission_status,'
            . ' ts.score_x, ts.score_y_percent, ts.graded_at'
            . ' from db_lms_tests te'
            . ' join db_lms_classes c on c.id = te.class_id and c.deleted_at is null'
            . ' join db_lms_class_students cs'
            . ' on cs.class_id = c.id and cs.student_id = ? and cs.status = ?'
            . ' left join db_lms_test_submissions ts'
            . ' on ts.test_id = te.id and ts.student_id = ?'
            . ' where te.id = ?'
            . ' limit 1',
            array($studentId, 'active', $studentId, $testId)
        );
        if (empty($row) || !is_array($row)) {
            return null;
        }
        return $row;
    }

    /**
     * Hạn nộp BTVN hiệu lực: hw_deadline nếu GV đã giao, ngược lại tính mặc định
     * (session_date + 1 ngày + classes.hw_deadline_time).
     *
     * @param array $session row từ getSessionForStudent()
     * @return string|null 'Y-m-d H:i:s'
     */
    public function hwDeadline(array $session)
    {
        if (!empty($session['hw_deadline'])) {
            return (string) $session['hw_deadline'];
        }
        $time = isset($session['hw_deadline_time']) ? $session['hw_deadline_time'] : null;
        $ts = strtotime((string) $session['session_date'] . ' +1 day');
        if ($ts === false) {
            return null;
        }
        $time = ($time === null || $time === '') ? '12:00' : substr((string) $time, 0, 5);
        if ($time === '') {
            $time = '12:00';
        }
        return date('Y-m-d', $ts) . ' ' . $time . ':00';
    }

    /**
     * Text đếm ngược hạn nộp (tĩnh tại lúc load — mockup §13).
     *
     * @param string|null $deadline 'Y-m-d H:i:s'
     * @return array array('text' => string, 'overdue' => bool)
     */
    public function deadlineCountdown($deadline)
    {
        if ($deadline === null || $deadline === '') {
            return array('text' => '', 'overdue' => false);
        }
        $ts = strtotime((string) $deadline);
        if ($ts === false) {
            return array('text' => '', 'overdue' => false);
        }
        $diff = $ts - time();
        if ($diff < 0) {
            $mins = (int) round(abs($diff) / 60);
            if ($mins < 60) {
                return array('text' => 'Hạn nộp: bạn đã nộp trễ ' . $mins . ' phút', 'overdue' => true);
            }
            return array(
                'text' => 'Hạn nộp: bạn đã nộp trễ ' . (int) round($mins / 60) . ' giờ',
                'overdue' => true,
            );
        }
        $mins = (int) round($diff / 60);
        if ($mins < 60) {
            return array('text' => 'Hạn nộp: bạn còn ' . $mins . ' phút để nộp', 'overdue' => false);
        }
        $hours = (int) round($mins / 60);
        if ($hours < 48) {
            return array('text' => 'Hạn nộp: bạn còn ' . $hours . ' giờ để nộp', 'overdue' => false);
        }
        return array(
            'text' => 'Hạn nộp: bạn còn ' . (int) round($hours / 24) . ' ngày để nộp',
            'overdue' => false,
        );
    }

    /**
     * Đếm số file hợp lệ trong cấu trúc $_FILES (bỏ qua ô trống).
     *
     * @param array|null $files
     * @return int
     */
    private function countUploadedFiles($files)
    {
        if (!is_array($files) || empty($files)) {
            return 0;
        }
        $count = 0;
        if (isset($files['name']) && is_array($files['name'])) {
            foreach (array_keys($files['name']) as $i) {
                $name = isset($files['name'][$i]) ? (string) $files['name'][$i] : '';
                $err = isset($files['error'][$i]) ? (int) $files['error'][$i] : UPLOAD_ERR_NO_FILE;
                if ($name !== '' && $err !== UPLOAD_ERR_NO_FILE) {
                    $count++;
                }
            }
            return $count;
        }
        $name = isset($files['name']) ? (string) $files['name'] : '';
        $err = isset($files['error']) ? (int) $files['error'] : UPLOAD_ERR_NO_FILE;
        return ($name !== '' && $err !== UPLOAD_ERR_NO_FILE) ? 1 : 0;
    }

    /**
     * HV nộp BTVN của 1 buổi (STU-03).
     *
     * Row-level qua getSessionForStudent; chỉ nộp được khi session_date <= hôm nay
     * (mockup §13: ô buổi chỉ mở khi tới ngày học). Nộp trễ vẫn được (label đỏ).
     *
     * @param int         $sessionId
     * @param int         $studentId
     * @param array|null  $files   $_FILES['hw_files']
     * @param bool        $replaceFiles true → thay file cũ, false → gộp (tối đa 4)
     * @param array       $opts    'cli' (bỏ qua is_uploaded_file khi smoke test)
     * @return bool
     */
    public function submitHomework($sessionId, $studentId, $files, $replaceFiles = false, array $opts = array())
    {
        $this->clearErrors();

        $sessionId = (int) $sessionId;
        $studentId = (int) $studentId;
        if ($sessionId <= 0 || $studentId <= 0) {
            $this->addError('system', 'Dữ liệu không hợp lệ.');
            return false;
        }

        $session = $this->getSessionForStudent($sessionId, $studentId);
        if ($session === null) {
            $this->addError('session_id', 'Buổi học không tồn tại hoặc không thuộc lớp của bạn.');
            return false;
        }

        // Chỉ nộp được khi tới ngày học (mockup §13 L248)
        if (strcmp((string) $session['session_date'], date('Y-m-d')) > 0) {
            $this->addError('session_id', 'Buổi học chưa diễn ra. Bạn chỉ nộp được bài vào ngày học trở đi.');
            return false;
        }

        if ($files === null || !is_array($files) || empty($files)) {
            $this->addError('hw_files', 'Vui lòng chọn ít nhất 1 file bài làm.');
            return false;
        }

        $existing = $replaceFiles
            ? array()
            : (isset($session['hw_submission_files']) ? $session['hw_submission_files'] : array());

        // Đếm file mới hợp lệ để check trước khi lưu (tránh file mồ côi)
        $newCount = $this->countUploadedFiles($files);
        if ($newCount === 0) {
            $this->addError('hw_files', 'Vui lòng chọn ít nhất 1 file bài làm.');
            return false;
        }
        if (count($existing) + $newCount > self::MAX_FILES) {
            $this->addError(
                'hw_files',
                'Tổng số file nộp không quá ' . self::MAX_FILES
                . ' (đã có ' . count($existing) . ' file).'
            );
            return false;
        }

        $month = date('Ym', strtotime((string) $session['session_date']));
        $saveOpts = array('maxFiles' => self::MAX_FILES);
        if (!empty($opts['cli'])) {
            $saveOpts['cli'] = true;
        }
        $res = lms_save_files($files, 'hw/' . $month, $saveOpts);
        if (empty($res['ok'])) {
            $this->addError('hw_files', $res['error'] !== '' ? $res['error'] : 'Không lưu được file bài làm.');
            return false;
        }
        if ($res['paths'] === array()) {
            $this->addError('hw_files', 'Vui lòng chọn ít nhất 1 file bài làm.');
            return false;
        }

        $all = array_merge($existing, $res['paths']);

        // Đảm bảo có row session_students
        $ssId = isset($session['ss_id']) ? (int) $session['ss_id'] : 0;
        if ($ssId <= 0) {
            $ok = $this->db->insert('db_lms_session_students', array(
                'session_id' => $sessionId,
                'student_id' => $studentId,
                'status'     => 'not_submitted',
            ));
            if (!$ok) {
                $this->addError('system', 'Không thể ghi nhận bài nộp. Vui lòng thử lại.');
                return false;
            }
            $ssId = (int) $this->db->insertId;
        }

        $deadline = $this->hwDeadline($session);
        $now = $this->now();
        $status = ($deadline !== null && strcmp($now, $deadline) <= 0)
            ? 'submitted_on_time'
            : 'submitted_late';

        $before = $this->db->safeFetchOne(
            'select * from db_lms_session_students where id = ? limit 1',
            array($ssId)
        );

        $ok = $this->db->update('db_lms_session_students', array(
            'hw_submission_files'  => json_encode($all, JSON_UNESCAPED_UNICODE),
            'hw_submitted_at'      => $now,
            'hw_submission_status' => $status,
            'status'               => 'submitted',
        ), 'id = ?', array($ssId));
        if (!$ok) {
            $this->addError('system', 'Không thể ghi nhận bài nộp. Vui lòng thử lại.');
            return false;
        }

        $after = $this->db->safeFetchOne(
            'select * from db_lms_session_students where id = ? limit 1',
            array($ssId)
        );
        $this->audit('submit_homework', 'session_students', $ssId, $before, $after, 'student');

        return true;
    }

    /**
     * HV nộp bài test/minitest (STU-03).
     *
     * Row-level: test thuộc lớp HV đang active; tests.status phải 'published'
     * (minitest draft → "chưa được admin duyệt", mockup §13 L245). Nộp trễ vẫn
     * được (label đỏ). Đã chấm thì không nộp lại được.
     *
     * @param int         $testId
     * @param int         $studentId
     * @param array|null  $files $_FILES['test_files']
     * @param array       $opts  'cli'
     * @return bool
     */
    public function submitTest($testId, $studentId, $files, array $opts = array())
    {
        $this->clearErrors();

        $testId = (int) $testId;
        $studentId = (int) $studentId;
        if ($testId <= 0 || $studentId <= 0) {
            $this->addError('system', 'Dữ liệu không hợp lệ.');
            return false;
        }

        $test = $this->getTestForStudent($testId, $studentId);
        if ($test === null) {
            $this->addError('test_id', 'Bài test không tồn tại hoặc không thuộc lớp của bạn.');
            return false;
        }

        $testType = (string) $test['test_type'];
        $status = (string) $test['status'];
        $typeLabel = $testType === 'minitest' ? 'minitest' : 'test';
        if ($status === 'draft') {
            $this->addError(
                'test_id',
                $testType === 'minitest'
                    ? 'Bài minitest chưa được admin duyệt nên bạn chưa nộp được.'
                    : 'Bài ' . $typeLabel . ' chưa được admin duyệt nên bạn chưa nộp được.'
            );
            return false;
        }
        if ($status === 'closed') {
            $this->addError('test_id', 'Bài ' . $typeLabel . ' đã đóng, không thể nộp.');
            return false;
        }

        // Đã chấm → không nộp lại
        if (!empty($test['graded_at'])) {
            $this->addError('test_id', 'Bài đã được chấm, không thể nộp lại.');
            return false;
        }

        $newCount = $this->countUploadedFiles($files);
        if ($newCount === 0) {
            $this->addError('test_files', 'Vui lòng chọn ít nhất 1 file bài làm.');
            return false;
        }
        if ($newCount > self::MAX_FILES) {
            $this->addError('test_files', 'Chỉ được nộp tối đa ' . self::MAX_FILES . ' file.');
            return false;
        }

        $month = date('Ym', strtotime((string) $test['deadline']));
        $saveOpts = array('maxFiles' => self::MAX_FILES);
        if (!empty($opts['cli'])) {
            $saveOpts['cli'] = true;
        }
        $res = lms_save_files($files, 'test/' . $month, $saveOpts);
        if (empty($res['ok'])) {
            $this->addError('test_files', $res['error'] !== '' ? $res['error'] : 'Không lưu được file bài làm.');
            return false;
        }
        if ($res['paths'] === array()) {
            $this->addError('test_files', 'Vui lòng chọn ít nhất 1 file bài làm.');
            return false;
        }

        $now = $this->now();
        $deadline = (string) $test['deadline'];
        $subStatus = strcmp($now, $deadline) <= 0 ? 'submitted_on_time' : 'submitted_late';

        $data = array(
            'submission_files'  => json_encode($res['paths'], JSON_UNESCAPED_UNICODE),
            'submitted_at'      => $now,
            'submission_status' => $subStatus,
        );

        $subId = isset($test['submission_id']) ? (int) $test['submission_id'] : 0;
        $before = null;
        if ($subId > 0) {
            $before = $this->db->safeFetchOne(
                'select * from db_lms_test_submissions where id = ? limit 1',
                array($subId)
            );
            $ok = $this->db->update('db_lms_test_submissions', $data, 'id = ?', array($subId));
        } else {
            $data['test_id'] = $testId;
            $data['student_id'] = $studentId;
            $ok = $this->db->insert('db_lms_test_submissions', $data);
            $subId = (int) $this->db->insertId;
        }
        if (!$ok) {
            $this->addError('system', 'Không thể ghi nhận bài nộp. Vui lòng thử lại.');
            return false;
        }

        $after = $this->db->safeFetchOne(
            'select * from db_lms_test_submissions where id = ? limit 1',
            array($subId)
        );
        $this->audit('submit_test', 'test_submissions', $subId, $before, $after, 'student');

        return true;
    }
}
