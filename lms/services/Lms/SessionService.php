<?php
/**
 * SessionService — sinh + quản lý buổi học từ lịch cố định.
 *
 * - cron/session_generate.php gọi generateUpcoming() mỗi ngày để sinh trước
 *   4 tuần (plan §4.1).
 * - Sessions là bản snapshot của lịch tại ngày đó (nắm teacher/class/time).
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class SessionService extends LmsService
{
    const DEFAULT_LOOKAHEAD_WEEKS = 4;

    /**
     * Sinh sessions cho mọi lớp active trong $weeks tuần tới (idempotent:
     * UNIQUE(class_id, session_date, start_time) — xem migration core).
     *
     * @param int    $weeks
     * @param string $fromDate 'Y-m-d' (mặc định hôm nay)
     * @return int số session mới được sinh
     */
    public function generateUpcoming($weeks = self::DEFAULT_LOOKAHEAD_WEEKS, $fromDate = null)
    {
        $created = 0;
        $start = $fromDate === null ? date('Y-m-d') : (string) $fromDate;
        $endDate = date('Y-m-d', strtotime('+' . (int) $weeks . ' weeks', strtotime($start)));

        $classes = $this->db->safeFetch(
            'select id, teacher_id from db_lms_classes'
            . ' where status = ? and deleted_at is null',
            array('active')
        );

        foreach ($classes as $class) {
            $created += $this->generateForClass((int) $class['id'], (int) $class['teacher_id'], $start, $endDate);
        }

        return $created;
    }

    /**
     * Sinh sessions cho 1 lớp trong khoảng ngày (idempotent).
     *
     * @param int    $classId
     * @param int    $teacherId
     * @param string $startDate 'Y-m-d'
     * @param string $endDate   'Y-m-d'
     * @return int
     */
    public function generateForClass($classId, $teacherId, $startDate, $endDate)
    {
        $classId   = (int) $classId;
        $teacherId = (int) $teacherId;
        $startDate = (string) $startDate;
        $endDate   = (string) $endDate;

        if ($classId <= 0 || $teacherId <= 0 || strcmp($endDate, $startDate) < 0) {
            return 0;
        }

        $schedules = $this->db->safeFetch(
            'select day_of_week, start_time, end_time, effective_from, effective_to'
            . ' from db_lms_schedules where class_id = ? order by day_of_week, start_time',
            array($classId)
        );
        if (empty($schedules)) {
            return 0;
        }

        // Map day_of_week -> sessions trong khoảng
        $created = 0;
        $day = strtotime($startDate);
        $endTs = strtotime($endDate);
        while ($day <= $endTs) {
            $dateStr = date('Y-m-d', $day);
            $dow = (int) date('N', $day); // 1=Mon..7=Sun -> cần 2..8
            $dow = $dow + 1;
            if ($dow > 8) {
                $dow = 8; // không xảy ra (date('N') max 7 -> 8)
            }

            foreach ($schedules as $sch) {
                if ((int) $sch['day_of_week'] !== $dow) {
                    continue;
                }
                if (strcmp($dateStr, (string) $sch['effective_from']) < 0) {
                    continue;
                }
                if (!empty($sch['effective_to']) && strcmp($dateStr, (string) $sch['effective_to']) > 0) {
                    continue;
                }

                $exists = $this->db->safeFetchOne(
                    'select id from db_lms_sessions'
                    . ' where class_id = ? and session_date = ? and start_time = ? limit 1',
                    array($classId, $dateStr, (string) $sch['start_time'])
                );
                if (!empty($exists)) {
                    continue;
                }

                $ok = $this->db->insert('db_lms_sessions', array(
                    'class_id'     => $classId,
                    'teacher_id'   => $teacherId,
                    'session_date' => $dateStr,
                    'start_time'   => $sch['start_time'],
                    'end_time'     => $sch['end_time'],
                    'status'       => 'upcoming',
                ));
                if ($ok) {
                    $created++;
                }
            }

            $day = strtotime('+1 day', $day);
        }

        return $created;
    }

    /**
     * Tính hạn nộp BTVN: session_date + 1 ngày + classes.hw_deadline_time.
     *
     * @param string $sessionDate 'Y-m-d'
     * @param string|null $deadlineTime 'HH:MM' từ classes.hw_deadline_time
     * @return string|null DATETIME
     */
    public function computeHwDeadline($sessionDate, $deadlineTime = null)
    {
        $ts = strtotime((string) $sessionDate . ' +1 day');
        if ($ts === false) {
            return null;
        }
        $time = ($deadlineTime === null || $deadlineTime === '') ? '12:00' : (string) $deadlineTime;
        // TIME column trả 'HH:MM:SS' → chỉ giữ 'HH:MM'
        $time = substr($time, 0, 5);
        if ($time === '') {
            $time = '12:00';
        }
        return date('Y-m-d', $ts) . ' ' . $time . ':00';
    }

    /**
     * Lấy (hoặc tạo) session_students cho các học viên active của lớp.
     *
     * @param int $sessionId
     * @return int số row được đảm bảo tồn tại
     */
    public function ensureSessionStudents($sessionId)    {
        $sessionId = (int) $sessionId;
        if ($sessionId <= 0) {
            return 0;
        }

        $session = $this->db->safeFetchOne(
            'select class_id from db_lms_sessions where id = ? limit 1',
            array($sessionId)
        );
        if (empty($session)) {
            return 0;
        }

        $students = $this->db->safeFetch(
            'select student_id from db_lms_class_students'
            . ' where class_id = ? and status = ?',
            array((int) $session['class_id'], 'active')
        );

        $count = 0;
        foreach ($students as $row) {
            $exists = $this->db->safeFetchOne(
                'select id from db_lms_session_students where session_id = ? and student_id = ? limit 1',
                array($sessionId, (int) $row['student_id'])
            );
            if (!empty($exists)) {
                $count++;
                continue;
            }
            $ok = $this->db->insert('db_lms_session_students', array(
                'session_id' => $sessionId,
                'student_id' => (int) $row['student_id'],
                'status'     => 'not_submitted',
            ));
            if ($ok) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Check trùng lịch của GV tại một ngày cụ thể (BR-SCHED-01) — dùng cho
     * admin duyệt add_session / change_schedule (ADM-05).
     *
     * Khác ScheduleService::hasConflict() (lịch cố định theo day_of_week): đây
     * check các row db_lms_sessions cùng teacher + cùng session_date + overlap
     * thời gian, bỏ qua buổi đã cancelled.
     *
     * @param int    $teacherId
     * @param string $sessionDate     'Y-m-d'
     * @param string $startTime       'H:i'
     * @param string $endTime         'H:i'
     * @param int    $excludeSessionId id session cần bỏ qua khi tự sửa
     * @return bool
     */
    public function hasTeacherScheduleConflict($teacherId, $sessionDate, $startTime, $endTime, $excludeSessionId = null)
    {
        $teacherId = (int) $teacherId;
        if ($teacherId <= 0 || !preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $sessionDate)) {
            return false;
        }

        $sql = 'select id from db_lms_sessions'
            . ' where teacher_id = ? and session_date = ? and status <> ?'
            . ' and start_time < ? and end_time > ?';
        $params = array($teacherId, (string) $sessionDate, 'cancelled', (string) $endTime, (string) $startTime);

        $excludeSessionId = (int) $excludeSessionId;
        if ($excludeSessionId > 0) {
            $sql .= ' and id <> ?';
            $params[] = $excludeSessionId;
        }
        $sql .= ' limit 1';

        $row = $this->db->safeFetchOne($sql, $params);
        return !empty($row);
    }

    // ===================== TCH-01/02 — Quản lý lịch (portal Teacher) =====================

    /**
     * Danh sách buổi học của GV trong $weeks tuần từ thứ 2 $monday (1 query).
     * Trả map 'Y-m-d' => array(sessions) để calendar group mà không query per-cell.
     *
     * @param int    $teacherId
     * @param string $monday 'Y-m-d' (thứ 2)
     * @param int    $weeks
     * @return array map date => array(rows)
     */
    public function listWeek($teacherId, $monday, $weeks = 1)
    {
        $teacherId = (int) $teacherId;
        $weeks     = max(1, (int) $weeks);
        $startTs   = strtotime((string) $monday);
        if ($teacherId <= 0 || $startTs === false) {
            return array();
        }
        $start = date('Y-m-d', $startTs);
        $end   = date('Y-m-d', strtotime('+' . ($weeks * 7 - 1) . ' days', $startTs));

        $rows = $this->db->safeFetch(
            'select s.id, s.class_id, s.teacher_id, s.session_date, s.start_time, s.end_time, s.status,'
            . ' s.lesson_summary, s.hw_content, s.hw_deadline, s.hw_attached_files,'
            . ' s.topic_id, s.topic_ids, s.summary_submitted_at,'
            . ' c.class_code, c.class_type, c.hw_deadline_time, c.subject_id,'
            . ' c.name as class_name, c.hw_check_hours'
            . ' from db_lms_sessions s'
            . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
            . ' where s.teacher_id = ? and s.session_date between ? and ?'
            . ' order by s.session_date, s.start_time',
            array($teacherId, $start, $end)
        );

        $map = array();
        foreach ($rows as $row) {
            $date = (string) $row['session_date'];
            if (!isset($map[$date])) {
                $map[$date] = array();
            }
            $map[$date][] = $row;
        }
        return $map;
    }

    /**
     * Chi tiết 1 buổi cho GV (row-level: join classes, teacher_id phải khớp).
     * Bổ sung topics của subject để multi-select chuyên đề.
     *
     * @param int $sessionId
     * @param int $teacherId
     * @return array|null
     */
    public function getSessionForTeacher($sessionId, $teacherId)
    {
        $sessionId = (int) $sessionId;
        $teacherId = (int) $teacherId;
        if ($sessionId <= 0 || $teacherId <= 0) {
            return null;
        }

        $row = $this->db->safeFetchOne(
            'select s.id, s.class_id, s.session_date, s.start_time, s.end_time, s.status,'
            . ' s.lesson_summary, s.hw_content, s.hw_deadline, s.hw_attached_files,'
            . ' s.topic_id, s.topic_ids, s.summary_submitted_at,'
            . ' c.class_code, c.class_type, c.hw_deadline_time, c.subject_id,'
            . ' c.name as class_name, c.hw_check_hours'
            . ' from db_lms_sessions s'
            . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
            . ' where s.id = ? and s.teacher_id = ?'
            . ' limit 1',
            array($sessionId, $teacherId)
        );
        if (empty($row) || !is_array($row)) {
            return null;
        }

        $row['topics'] = $this->db->safeFetch(
            'select id, name from db_lms_topics where subject_id = ? order by order_index, name',
            array((int) $row['subject_id'])
        );
        $row['topic_ids'] = \Lms\UserService::decodeJsonArray(isset($row['topic_ids']) ? $row['topic_ids'] : array());

        return $row;
    }

    /**
     * GV ghi lesson summary + giao BTVN (row-level + BR deadline 12h trưa hôm sau).
     *
     * @param int         $sessionId
     * @param int         $teacherId
     * @param array       $in     topic_ids[], lesson_summary, hw_content, hw_deadline,
     *                              replace_files
     * @param array|null  $files  $_FILES['hw_files'] (null → không upload)
     * @return bool
     */
    public function updateLessonSummary($sessionId, $teacherId, array $in, $files = null)
    {
        $this->clearErrors();

        $sessionId = (int) $sessionId;
        $teacherId = (int) $teacherId;
        if ($sessionId <= 0 || $teacherId <= 0) {
            $this->addError('session_id', 'Buổi học không hợp lệ.');
            return false;
        }

        $session = $this->getSessionForTeacher($sessionId, $teacherId);
        if ($session === null) {
            $this->addError('session_id', 'Buổi học không tồn tại hoặc không thuộc lớp của bạn.');
            return false;
        }

        // BR deadline: chỉ sửa trước 12h trưa ngày kế tiếp buổi học
        $deadline = $this->computeHwDeadline(
            (string) $session['session_date'],
            isset($session['hw_deadline_time']) ? $session['hw_deadline_time'] : null
        );
        if ($deadline !== null && strcmp($this->now(), $deadline) > 0) {
            $this->addError('summary', 'Đã quá hạn sửa lesson summary (12:00 trưa ngày kế tiếp buổi học).');
            return false;
        }

        // topic_ids: phải thuộc subject của lớp
        $topicIds = array();
        $rawTopics = isset($in['topic_ids']) ? (array) $in['topic_ids'] : array();
        $validTopicIds = array();
        foreach ($session['topics'] as $t) {
            $validTopicIds[] = (int) $t['id'];
        }
        foreach ($rawTopics as $tid) {
            $tid = (int) $tid;
            if ($tid <= 0) {
                continue;
            }
            if (!in_array($tid, $validTopicIds, true)) {
                $this->addError('topic_ids', 'Chuyên đề được chọn không thuộc môn học của lớp.');
                return false;
            }
            $topicIds[] = $tid;
        }
        $topicIds = array_values(array_unique($topicIds));

        $lessonSummary = isset($in['lesson_summary']) ? trim((string) $in['lesson_summary']) : '';
        $hwContent     = isset($in['hw_content']) ? trim((string) $in['hw_content']) : '';

        // Hạn nộp BTVN: rỗng → mặc định computeHwDeadline; phải >= session_date
        $hwDeadline = null;
        if (isset($in['hw_deadline']) && trim((string) $in['hw_deadline']) !== '') {
            $hwDeadline = trim((string) $in['hw_deadline']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $hwDeadline)) {
                $this->addError('hw_deadline', 'Hạn nộp BTVN sai định dạng.');
                return false;
            }
            $check = str_replace('T', ' ', $hwDeadline) . ':00';
            if (strcmp($check, (string) $session['session_date'] . ' 00:00:00') < 0) {
                $this->addError('hw_deadline', 'Hạn nộp BTVN phải từ ngày diễn ra buổi học trở đi.');
                return false;
            }
            $hwDeadline = str_replace('T', ' ', $hwDeadline) . ':00';
        }
        if ($hwDeadline === null) {
            $hwDeadline = $deadline;
        }

        // File đính kèm: merge hoặc thay thế
        $replaceFiles = !empty($in['replace_files']);
        $attached = $replaceFiles
            ? array()
            : \Lms\UserService::decodeJsonArray(isset($session['hw_attached_files']) ? $session['hw_attached_files'] : array());
        if ($files !== null) {
            $month = date('Ym', strtotime((string) $session['session_date']));
            $res = lms_save_files($files, 'hw/' . $month);
            if (empty($res['ok'])) {
                $this->addError('hw_files', $res['error'] !== '' ? $res['error'] : 'Không lưu được file đính kèm.');
                return false;
            }
            $attached = array_merge($attached, $res['paths']);
            if (count($attached) > 4) {
                $this->addError('hw_files', 'Tổng số file đính kèm không quá 4.');
                return false;
            }
        }

        $before = $this->db->safeFetchOne(
            'select * from db_lms_sessions where id = ? limit 1',
            array($sessionId)
        );

        $data = array(
            'lesson_summary'     => $lessonSummary !== '' ? $lessonSummary : null,
            'hw_content'         => $hwContent !== '' ? $hwContent : null,
            'hw_deadline'        => $hwDeadline,
            'hw_attached_files'  => $attached !== array() ? json_encode($attached, JSON_UNESCAPED_UNICODE) : null,
            'topic_ids'          => $topicIds !== array() ? json_encode($topicIds, JSON_UNESCAPED_UNICODE) : null,
            'topic_id'           => $topicIds !== array() ? $topicIds[0] : null,
            'summary_submitted_at' => $this->now(),
        );

        // session_date <= hôm nay → đánh dấu finished (BR-TICK-01 cần finished
        // + summary_submitted_at để cron quết tick trễ)
        if (strcmp((string) $session['session_date'], date('Y-m-d')) <= 0) {
            $data['status'] = 'finished';
        }

        $ok = $this->db->update('db_lms_sessions', $data, 'id = ?', array($sessionId));
        if (!$ok) {
            $this->addError('system', 'Không thể lưu lesson summary. Vui lòng thử lại.');
            return false;
        }

        $after = $this->db->safeFetchOne(
            'select * from db_lms_sessions where id = ? limit 1',
            array($sessionId)
        );
        $this->audit('update_lesson_summary', 'sessions', $sessionId, $before, $after, 'teacher');

        return true;
    }

    /**
     * Danh sách học viên của buổi học để chấm BTVN (row-level qua session→class).
     *
     * @param int $sessionId
     * @param int $teacherId
     * @return array
     */
    public function listSessionStudentsForGrading($sessionId, $teacherId)
    {
        $sessionId = (int) $sessionId;
        $teacherId = (int) $teacherId;
        if ($sessionId <= 0 || $teacherId <= 0) {
            return array();
        }

        return $this->db->safeFetch(
            'select ss.id, ss.student_id, ss.hw_submission_files, ss.hw_submitted_at,'
            . ' ss.hw_submission_status, ss.teacher_feedback_text, ss.teacher_feedback_files,'
            . ' ss.hw_graded_at, ss.is_graded_late, ss.grade, ss.status,'
            . ' s.student_code, s.full_name, s.grade as student_grade,'
            . ' u.username, u.email, u.phone,'
            . ' ses.session_date, ses.start_time, ses.end_time,'
            . ' c.hw_check_hours, c.class_code, c.name as class_name'
            . ' from db_lms_session_students ss'
            . ' join db_lms_sessions ses on ses.id = ss.session_id'
            . ' join db_lms_classes c on c.id = ses.class_id and c.deleted_at is null'
            . ' join db_lms_students s on s.user_id = ss.student_id'
            . ' left join db_lms_users u on u.id = ss.student_id'
            . ' where ss.session_id = ? and ses.teacher_id = ?'
            . ' order by s.full_name, ss.id',
            array($sessionId, $teacherId)
        );
    }

    /**
     * GV chấm BTVN cho 1 học viên (row-level qua session→class).
     *
     * @param int         $sessionStudentId
     * @param int         $teacherId
     * @param array       $in grade, teacher_feedback_text
     * @param array|null  $files $_FILES['teacher_feedback_files']
     * @return bool
     */
    public function gradeHomework($sessionStudentId, $teacherId, array $in, $files = null)
    {
        $this->clearErrors();

        $sessionStudentId = (int) $sessionStudentId;
        $teacherId        = (int) $teacherId;
        if ($sessionStudentId <= 0 || $teacherId <= 0) {
            $this->addError('system', 'Dữ liệu không hợp lệ.');
            return false;
        }

        $row = $this->db->safeFetchOne(
            'select ss.id, ss.student_id, ss.hw_submitted_at, ss.teacher_feedback_files,'
            . ' ses.session_date, ses.id as session_id,'
            . ' c.hw_check_hours, c.teacher_id'
            . ' from db_lms_session_students ss'
            . ' join db_lms_sessions ses on ses.id = ss.session_id'
            . ' join db_lms_classes c on c.id = ses.class_id and c.deleted_at is null'
            . ' where ss.id = ? and c.teacher_id = ?'
            . ' limit 1',
            array($sessionStudentId, $teacherId)
        );
        if (empty($row) || !is_array($row)) {
            $this->addError('system', 'Bài tập về nhà không tồn tại hoặc không thuộc lớp của bạn.');
            return false;
        }

        // Điểm 0..20 hoặc rỗng
        $grade = null;
        if (isset($in['grade']) && (string) $in['grade'] !== '') {
            if (!is_numeric($in['grade']) || (float) $in['grade'] < 0 || (float) $in['grade'] > 20) {
                $this->addError('grade', 'Điểm phải từ 0 đến 20.');
                return false;
            }
            $grade = (float) $in['grade'];
        }

        $feedback = isset($in['teacher_feedback_text']) ? trim((string) $in['teacher_feedback_text']) : '';

        $attached = \Lms\UserService::decodeJsonArray(
            isset($row['teacher_feedback_files']) ? $row['teacher_feedback_files'] : array()
        );
        if ($files !== null) {
            $month = date('Ym', strtotime((string) $row['session_date']));
            $res = lms_save_files($files, 'feedback/' . $month);
            if (empty($res['ok'])) {
                $this->addError('teacher_feedback_files', $res['error'] !== '' ? $res['error'] : 'Không lưu được file đính kèm.');
                return false;
            }
            $attached = array_merge($attached, $res['paths']);
            if (count($attached) > 4) {
                $this->addError('teacher_feedback_files', 'Tổng số file đính kèm không quá 4.');
                return false;
            }
        }

        // BR-TICK-02: chậm nếu (hw_submitted_at + hw_check_hours giờ) < now()
        $isLate = 0;
        if (!empty($row['hw_submitted_at'])) {
            $checkHours = isset($row['hw_check_hours']) ? (int) $row['hw_check_hours'] : 24;
            $dueTs = strtotime((string) $row['hw_submitted_at'] . ' +' . max(0, $checkHours) . ' hours');
            if ($dueTs !== false && time() > $dueTs) {
                $isLate = 1;
            }
        }

        $before = $this->db->safeFetchOne(
            'select * from db_lms_session_students where id = ? limit 1',
            array($sessionStudentId)
        );

        $data = array(
            'grade'                  => $grade,
            'teacher_feedback_text'  => $feedback !== '' ? $feedback : null,
            'teacher_feedback_files' => $attached !== array() ? json_encode($attached, JSON_UNESCAPED_UNICODE) : null,
            'hw_graded_at'           => $this->now(),
            'is_graded_late'         => $isLate,
            'status'                 => 'graded',
        );

        $ok = $this->db->update('db_lms_session_students', $data, 'id = ?', array($sessionStudentId));
        if (!$ok) {
            $this->addError('system', 'Không thể chấm bài. Vui lòng thử lại.');
            return false;
        }

        $after = $this->db->safeFetchOne(
            'select * from db_lms_session_students where id = ? limit 1',
            array($sessionStudentId)
        );
        $this->audit('grade_homework', 'session_students', $sessionStudentId, $before, $after, 'teacher');

        return true;
    }

    // ===================== STU-01/02/03/04 — Portal Student =====================

    /**
     * Danh sách buổi học của HV trong $weeks tuần từ thứ 2 $monday (1 query).
     * Row-level qua db_lms_class_students (status active) — HV thấy mọi buổi của
     * lớp đang học, kể cả khi chưa có row session_students.
     * Trả map 'Y-m-d' => array(sessions) để calendar group mà không query per-cell.
     *
     * @param int    $studentId
     * @param string $monday 'Y-m-d' (thứ 2)
     * @param int    $weeks
     * @return array map date => array(rows)
     */
    public function listWeekForStudent($studentId, $monday, $weeks = 1)
    {
        $studentId = (int) $studentId;
        $weeks = max(1, (int) $weeks);
        $startTs = strtotime((string) $monday);
        if ($studentId <= 0 || $startTs === false) {
            return array();
        }
        $start = date('Y-m-d', $startTs);
        $end = date('Y-m-d', strtotime('+' . ($weeks * 7 - 1) . ' days', $startTs));

        $rows = $this->db->safeFetch(
            'select s.id, s.class_id, s.session_date, s.start_time, s.end_time, s.status,'
            . ' s.summary_submitted_at,'
            . ' c.class_code, c.class_type, c.subject_id, c.name as class_name,'
            . ' t.full_name as teacher_name'
            . ' from db_lms_sessions s'
            . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
            . ' join db_lms_class_students cs'
            . ' on cs.class_id = c.id and cs.student_id = ? and cs.status = ?'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' where s.session_date between ? and ?'
            . ' order by s.session_date, s.start_time',
            array($studentId, 'active', $start, $end)
        );

        $map = array();
        foreach ($rows as $row) {
            $date = (string) $row['session_date'];
            if (!isset($map[$date])) {
                $map[$date] = array();
            }
            $map[$date][] = $row;
        }
        return $map;
    }

    /**
     * Buổi học sắp tới của HV + trạng thái nộp BTVN (tổng hợp từ session_students).
     *
     * @param int $studentId
     * @param int $limit
     * @return array
     */
    public function listUpcomingForStudent($studentId, $limit = 10)
    {
        $studentId = (int) $studentId;
        $limit = max(1, min(50, (int) $limit));
        if ($studentId <= 0) {
            return array();
        }

        return $this->db->safeFetch(
            'select s.id, s.session_date, s.start_time, s.end_time, s.status,'
            . ' c.id as class_id, c.name as class_name, c.class_code,'
            . ' coalesce(ss.status, ?) as hw_status,'
            . ' ss.hw_submission_status, ss.hw_graded_at, ss.grade,'
            . ' s.hw_deadline, s.hw_content'
            . ' from db_lms_sessions s'
            . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
            . ' join db_lms_class_students cs'
            . ' on cs.class_id = c.id and cs.student_id = ? and cs.status = ?'
            . ' left join db_lms_session_students ss'
            . ' on ss.session_id = s.id and ss.student_id = ?'
            . ' where s.session_date >= ?'
            . ' order by s.session_date, s.start_time limit ' . $limit,
            array('not_submitted', $studentId, 'active', $studentId, date('Y-m-d'))
        );
    }
}
