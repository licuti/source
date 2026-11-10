<?php
/** Admin reports & curriculum management for homework, curriculum and lesson statistics. */
namespace Lms;

class AdminReportService extends LmsService
{
    public function adminUpdateSession($sessionId, array $input, $files = null)
    {
        $sessionId = (int) $sessionId;
        $before = $this->db->safeFetchOne(
            'select * from db_lms_sessions where id = ? limit 1', array($sessionId)
        );
        if (empty($before)) {
            $this->addError('session_id', 'Buổi học không tồn tại.');
            return false;
        }
        $attached = UserService::decodeJsonArray($before['hw_attached_files']);
        if ($files !== null) {
            $res = lms_save_files($files, 'lesson/admin/' . date('Ym', strtotime((string) $before['session_date'])));
            if (empty($res['ok'])) {
                $this->addError('hw_files', $res['error']);
                return false;
            }
            $attached = array_merge($attached, $res['paths']);
            if (count($attached) > 4) {
                $this->addError('hw_files', 'Tổng file đính kèm không quá 4.');
                return false;
            }
        }
        $topicIds = isset($input['topic_ids']) && is_array($input['topic_ids'])
            ? array_values(array_unique(array_filter(array_map('intval', $input['topic_ids']))))
            : array();
        if ($topicIds !== array()) {
            $cycleId = isset($before['cycle_id']) ? (int) $before['cycle_id'] : 0;
            if ($cycleId > 0) {
                $valid = (int) $this->db->count(
                    'db_lms_cycle_topics',
                    'cycle_id = ? and topic_id in (' . implode(',', array_fill(0, count($topicIds), '?')) . ')',
                    array_merge(array($cycleId), $topicIds)
                );
            } else {
                $classId = (int) $before['class_id'];
                $classTopicCount = (int) $this->db->count(
                    'db_lms_class_topics',
                    'class_id = ?',
                    array($classId)
                );
                if ($classTopicCount > 0) {
                    $valid = (int) $this->db->count(
                        'db_lms_class_topics',
                        'class_id = ? and topic_id in (' . implode(',', array_fill(0, count($topicIds), '?')) . ')',
                        array_merge(array($classId), $topicIds)
                    );
                } else {
                    // Lớp cũ chưa được gán chuyên đề: giữ fallback theo môn học.
                    $valid = (int) $this->db->count(
                        'db_lms_topics',
                        'subject_id = (select subject_id from db_lms_classes where id = ?) and id in (' . implode(',', array_fill(0, count($topicIds), '?')) . ')',
                        array_merge(array($classId), $topicIds)
                    );
                }
            }
            if ($valid !== count($topicIds)) {
                $this->addError('topic_ids', 'Chuyên đề phải thuộc khóa học của buổi học.');
                return false;
            }
        }
        $primaryTopic = $topicIds !== array() ? $topicIds[0] : null;
        $data = array(
            'lesson_summary' => isset($input['lesson_summary']) ? trim((string) $input['lesson_summary']) : null,
            'hw_content' => isset($input['hw_content']) ? trim((string) $input['hw_content']) : null,
            'topic_id' => $primaryTopic,
            'topic_ids' => $topicIds !== array() ? json_encode($topicIds, JSON_UNESCAPED_UNICODE) : null,
            'hw_attached_files' => $attached !== array() ? json_encode($attached, JSON_UNESCAPED_UNICODE) : null,
        );
        if (isset($input['hw_deadline']) && trim((string) $input['hw_deadline']) !== '') {
            $deadline = str_replace('T', ' ', trim((string) $input['hw_deadline']));
            if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $deadline)) {
                $this->addError('hw_deadline', 'Hạn nộp không hợp lệ.');
                return false;
            }
            $data['hw_deadline'] = $deadline . ':00';
        } else {
            $data['hw_deadline'] = null;
        }
        if (!$this->db->update('db_lms_sessions', $data, 'id = ?', array($sessionId))) {
            $this->addError('system', 'Không thể cập nhật nội dung buổi học.');
            return false;
        }
        $after = $this->db->safeFetchOne('select * from db_lms_sessions where id = ? limit 1', array($sessionId));
        $this->audit('admin_update_session', 'sessions', $sessionId, $before, $after, 'admin');
        return true;
    }

    public function sessionDetail($sessionId)
    {
        $session = $this->db->safeFetchOne(
            'select s.*, c.class_code, c.name as class_name, c.teacher_id, c.hw_check_hours,'
            . ' t.teacher_code, t.full_name as teacher_name, top.name as topic_name'
            . ' from db_lms_sessions s join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
            . ' join db_lms_teachers t on t.user_id = s.teacher_id'
            . ' left join db_lms_topics top on top.id = s.topic_id where s.id = ? limit 1',
            array((int) $sessionId)
        );
        if (empty($session)) {
            return array();
        }
        (new SessionService($this->db))->ensureSessionStudents((int) $sessionId);
        $students = $this->db->safeFetch(
            'select ss.*, s.student_code, s.full_name, s.grade'
            . ' from db_lms_session_students ss join db_lms_students s on s.user_id = ss.student_id'
            . ' where ss.session_id = ? order by s.full_name, ss.id',
            array((int) $sessionId)
        );
        foreach ($students as $i => $student) {
            $students[$i]['hw_submission_files'] = UserService::decodeJsonArray($student['hw_submission_files']);
            $students[$i]['teacher_feedback_files'] = UserService::decodeJsonArray($student['teacher_feedback_files']);
        }
        $session['hw_attached_files'] = UserService::decodeJsonArray($session['hw_attached_files']);
        $topics = array();
        if (!empty($session['cycle_id'])) {
            $topics = $this->db->safeFetch(
                'select ct.topic_id as id, t.name from db_lms_cycle_topics ct'
                . ' join db_lms_topics t on t.id = ct.topic_id where ct.cycle_id = ?'
                . ' order by ct.order_index, ct.id',
                array((int) $session['cycle_id'])
            );
        } else {
            $topics = $this->db->safeFetch(
                'select t.id, t.name from db_lms_class_topics ct'
                . ' join db_lms_topics t on t.id = ct.topic_id'
                . ' where ct.class_id = ? order by ct.order_index, t.id',
                array((int) $session['class_id'])
            );
            if (empty($topics)) {
                // Lớp cũ chưa được gán chuyên đề: giữ fallback theo môn học.
                $topics = $this->db->safeFetch(
                    'select t.id, t.name from db_lms_topics t'
                    . ' join db_lms_classes c on c.subject_id = t.subject_id where c.id = ? order by t.order_index, t.id',
                    array((int) $session['class_id'])
                );
            }
        }
        $session['topic_ids'] = UserService::decodeJsonArray($session['topic_ids']);
        $auditLogs = $this->db->safeFetch(
            'select al.*, u.username'
            . ' from db_lms_audit_logs al'
            . ' left join db_lms_users u on u.id = al.actor_user_id'
            . ' where al.target_type = ? and al.target_id = ?'
            . ' order by al.id desc limit 20',
            array('sessions', (int) $sessionId)
        );
        return array('session' => $session, 'students' => $students, 'topics' => $topics, 'audit_logs' => $auditLogs);
    }

    public function adminGradeHomework($sessionStudentId, array $input, $files = null, $reason = '')
    {
        $row = $this->db->safeFetchOne(
            'select ss.*, ses.id as session_id, ses.session_date, c.teacher_id'
            . ' from db_lms_session_students ss join db_lms_sessions ses on ses.id = ss.session_id'
            . ' join db_lms_classes c on c.id = ses.class_id where ss.id = ? limit 1',
            array((int) $sessionStudentId)
        );
        if (empty($row)) {
            $this->addError('session_student_id', 'Bài nộp không tồn tại.');
            return false;
        }
        $grade = isset($row['grade']) && $row['grade'] !== null ? (float) $row['grade'] : null;
        if (isset($input['grade']) && (string) $input['grade'] !== '') {
            if (!is_numeric($input['grade']) || (float) $input['grade'] < 0 || (float) $input['grade'] > 20) {
                $this->addError('grade', 'Điểm phải từ 0 đến 20.');
                return false;
            }
            $grade = (float) $input['grade'];
        }
        $feedback = isset($input['teacher_feedback_text']) ? trim((string) $input['teacher_feedback_text']) : '';
        $attached = UserService::decodeJsonArray($row['teacher_feedback_files']);
        if ($files !== null) {
            $res = lms_save_files($files, 'feedback/admin/' . date('Ym', strtotime((string) $row['session_date'])));
            if (empty($res['ok'])) {
                $this->addError('teacher_feedback_files', $res['error']);
                return false;
            }
            $attached = array_merge($attached, $res['paths']);
            if (count($attached) > 4) {
                $this->addError('teacher_feedback_files', 'Tổng số file đính kèm không quá 4.');
                return false;
            }
        }
        $before = $this->db->safeFetchOne('select * from db_lms_session_students where id = ? limit 1', array((int) $sessionStudentId));
        if (empty($before)) {
            $this->addError('session_student_id', 'Bài nộp không tồn tại.');
            return false;
        }
        if (!empty($before['hw_graded_at']) && trim((string) $reason) === '') {
            $this->addError('reason', 'Cần nhập lý do khi Admin ghi đè kết quả đã chấm.');
            return false;
        }
        $ok = $this->db->update('db_lms_session_students', array(
            'grade' => $grade,
            'teacher_feedback_text' => $feedback !== '' ? $feedback : null,
            'teacher_feedback_files' => $attached !== array() ? json_encode($attached, JSON_UNESCAPED_UNICODE) : null,
            'hw_graded_at' => $this->now(),
            'status' => 'approved',
        ), 'id = ?', array((int) $sessionStudentId));
        if (!$ok) {
            $this->addError('system', 'Không thể lưu chấm bài.');
            return false;
        }
        $after = $this->db->safeFetchOne('select * from db_lms_session_students where id = ? limit 1', array((int) $sessionStudentId));
        $this->audit('admin_grade_homework', 'session_students', $sessionStudentId, $before, array_merge($after, array('reason' => (string) $reason)), 'admin');
        return true;
    }

    public function homeworkList(array $filters = array(), $page = 1, $perPage = 20)
    {
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset = ($page - 1) * $perPage;
        // Buổi đã kết thúc mới phải báo cáo BTVN/nhận xét (đồng bộ TickService::closePastSessions).
        $where = array(
            '(s.status = ? or (s.status = ? and (s.session_date < CURDATE()'
            . ' or (s.session_date = CURDATE() and s.end_time is not null and s.end_time < CURTIME()))))'
        );
        $params = array('finished', 'upcoming');
        $from = isset($filters['date_from']) ? trim((string) $filters['date_from']) : '';
        $to = isset($filters['date_to']) ? trim((string) $filters['date_to']) : '';
        if ($this->validDate($from)) { $where[] = 's.session_date >= ?'; $params[] = $from; }
        if ($this->validDate($to)) { $where[] = 's.session_date <= ?'; $params[] = $to; }
        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $where[] = '(c.class_code like ? or t.teacher_code like ? or t.full_name like ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like);
        }
        $whereSql = implode(' and ', $where);
        $count = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_sessions s'
            . ' join db_lms_classes c on c.id=s.class_id and c.deleted_at is null'
            . ' join db_lms_teachers t on t.user_id=s.teacher_id'
            . ' where ' . $whereSql,
            $params
        );
        $total = isset($count['c']) ? (int) $count['c'] : 0;
        $items = $this->db->safeFetch(
            'select s.id as session_id, s.session_date, s.start_time, s.end_time, s.status as session_status,'
            . ' s.topic_id, s.topic_ids, s.hw_content, s.lesson_summary, s.updated_at, s.summary_submitted_at,'
            . ' c.id as class_id, c.class_code, c.name as class_name,'
            . ' t.user_id as teacher_id, t.teacher_code, t.full_name as teacher_name,'
            . ' (select count(*) from db_lms_session_students ss where ss.session_id=s.id and ss.hw_submission_status in ("submitted_on_time","submitted_late")) as submissions_count'
            . ' from db_lms_sessions s'
            . ' join db_lms_classes c on c.id=s.class_id and c.deleted_at is null'
            . ' join db_lms_teachers t on t.user_id=s.teacher_id'
            . ' where ' . $whereSql
            . ' order by s.session_date desc, s.start_time desc limit ' . $offset . ', ' . $perPage,
            $params
        );

        // Buổi học lưu nhiều chuyên đề trong topic_ids (JSON); topic_id là chuyên đề đầu tiên.
        $topicIds = array();
        foreach ($items as $row) {
            $ids = UserService::decodeJsonArray(isset($row['topic_ids']) ? $row['topic_ids'] : array());
            if (empty($ids) && !empty($row['topic_id'])) {
                $ids = array((int) $row['topic_id']);
            }
            foreach ($ids as $tid) {
                if ((int) $tid > 0) {
                    $topicIds[(int) $tid] = true;
                }
            }
        }
        $topicNames = array();
        if ($topicIds !== array()) {
            $ids = array_keys($topicIds);
            $rows = $this->db->safeFetch(
                'select id, name from db_lms_topics where id in (' . implode(',', array_fill(0, count($ids), '?')) . ')',
                $ids
            );
            foreach ($rows as $r) {
                $topicNames[(int) $r['id']] = (string) $r['name'];
            }
        }
        foreach ($items as &$row) {
            $ids = UserService::decodeJsonArray(isset($row['topic_ids']) ? $row['topic_ids'] : array());
            if (empty($ids) && !empty($row['topic_id'])) {
                $ids = array((int) $row['topic_id']);
            }
            $names = array();
            foreach ($ids as $tid) {
                if (isset($topicNames[(int) $tid])) {
                    $names[] = $topicNames[(int) $tid];
                }
            }
            $row['topic_name'] = implode(', ', $names);
        }
        unset($row);

        return array('items' => $items, 'total' => $total);
    }

    public function topicList(array $filters = array(), $page = 1, $perPage = 25)
    {
        $page = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset = ($page - 1) * $perPage;
        $where = array('1=1');
        $params = array();
        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $where[] = '(top.name like ? or top.content like ? or top.report_template like ? or sub.name like ? or p.name like ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like, $like, $like, $like);
        }
        $subjectId = isset($filters['subject_id']) ? (int) $filters['subject_id'] : 0;
        if ($subjectId > 0) { $where[] = 'top.subject_id = ?'; $params[] = $subjectId; }
        $testFilter = isset($filters['has_test']) ? (string) $filters['has_test'] : '';
        if ($testFilter === '1' || $testFilter === '0') { $where[] = 'top.has_test = ?'; $params[] = (int) $testFilter; }
        $whereSql = implode(' and ', $where);
        $count = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_topics top join db_lms_subjects sub on sub.id=top.subject_id'
            . ' join db_lms_programs p on p.id=sub.program_id where ' . $whereSql,
            $params
        );
        $items = $this->db->safeFetch(
            'select top.id, top.name, top.has_test, top.has_minitest, top.max_sessions, top.content, top.report_template, top.order_index,'
            . ' sub.id as subject_id, sub.name as subject_name, p.name as program_name,'
            . ' (select count(*) from db_lms_tests x where x.topic_id=top.id) as tests_count'
            . ' from db_lms_topics top join db_lms_subjects sub on sub.id=top.subject_id'
            . ' join db_lms_programs p on p.id=sub.program_id where ' . $whereSql
            . ' order by p.name, sub.name, top.order_index, top.name limit ' . $offset . ', ' . $perPage,
            $params
        );
        return array('items' => $items, 'total' => isset($count['c']) ? (int) $count['c'] : 0);
    }

    /**
     * Danh sách danh mục chuyên đề (db_lms_subjects).
     *
     * @return array
     */
    public function listSubjects()
    {
        return $this->db->safeFetch(
            'select s.id, s.name, s.program_id, p.name as program_name'
            . ' from db_lms_subjects s join db_lms_programs p on p.id = s.program_id'
            . ' order by s.name, s.id'
        );
    }

    /**
     * Lấy thông tin 1 chuyên đề theo ID.
     *
     * @param int $id
     * @return array
     */
    public function getTopic($id)
    {
        $row = $this->db->safeFetchOne(
            'select top.*, sub.name as subject_name, p.name as program_name'
            . ' from db_lms_topics top'
            . ' join db_lms_subjects sub on sub.id = top.subject_id'
            . ' join db_lms_programs p on p.id = sub.program_id'
            . ' where top.id = ? limit 1',
            array((int) $id)
        );
        return is_array($row) ? $row : array();
    }

    /**
     * Tạo mới hoặc cập nhật chuyên đề (Form Thêm / Sửa chuyên đề - §18).
     * Hỗ trợ tạo nhanh danh mục mới khi chọn "+ Thêm loại chuyên đề mới" (__new__).
     *
     * @param array $input
     * @param int   $id      0 nếu tạo mới, >0 nếu cập nhật
     * @return int|false     ID chuyên đề khi thành công, false khi lỗi
     */
    public function saveTopic(array $input, $id = 0, array $files = array())
    {
        $this->clearErrors();
        $id = (int) $id;
        $before = null;
        if ($id > 0) {
            $before = $this->db->safeFetchOne('select * from db_lms_topics where id = ? limit 1', array($id));
            if (empty($before)) {
                $this->addError('id', 'Chuyên đề không tồn tại.');
                return false;
            }
        }

        $name = isset($input['name']) ? trim((string) $input['name']) : '';
        if ($name === '') {
            $this->addError('name', 'Vui lòng nhập tên chuyên đề.');
        } elseif (mb_strlen($name, 'UTF-8') > 150) {
            $this->addError('name', 'Tên chuyên đề không được vượt quá 150 ký tự.');
        }

        // Xử lý Danh mục (subject_id) hoặc tạo mới danh mục (+ Thêm loại chuyên đề mới)
        $rawSubject = isset($input['subject_id']) ? trim((string) $input['subject_id']) : '';
        $subjectId = 0;
        if ($rawSubject === '__new__') {
            $newSubjectName = isset($input['new_subject_name']) ? trim((string) $input['new_subject_name']) : '';
            if ($newSubjectName === '') {
                $this->addError('new_subject_name', 'Vui lòng nhập tên danh mục (loại chuyên đề) mới.');
            } elseif (mb_strlen($newSubjectName, 'UTF-8') > 100) {
                $this->addError('new_subject_name', 'Tên danh mục không được vượt quá 100 ký tự.');
            } elseif (!$this->hasErrors()) {
                $existingSub = $this->db->safeFetchOne(
                    'select id from db_lms_subjects where lower(name) = lower(?) limit 1',
                    array($newSubjectName)
                );
                if (!empty($existingSub['id'])) {
                    $subjectId = (int) $existingSub['id'];
                } else {
                    $prog = $this->db->safeFetchOne('select id from db_lms_programs order by id asc limit 1');
                    if (!empty($prog['id'])) {
                        $programId = (int) $prog['id'];
                    } else {
                        $this->db->insert('db_lms_programs', array('name' => 'Chương trình chuẩn'));
                        $programId = (int) $this->db->insertId;
                    }
                    if ($this->db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => $newSubjectName))) {
                        $subjectId = (int) $this->db->insertId;
                    } else {
                        $this->addError('new_subject_name', 'Không thể tạo danh mục chuyên đề mới.');
                    }
                }
            }
        } else {
            $subjectId = (int) $rawSubject;
            if ($subjectId <= 0) {
                $this->addError('subject_id', 'Vui lòng chọn danh mục chuyên đề.');
            } else {
                $subRow = $this->db->safeFetchOne('select id from db_lms_subjects where id = ? limit 1', array($subjectId));
                if (empty($subRow)) {
                    $this->addError('subject_id', 'Danh mục đã chọn không tồn tại.');
                }
            }
        }

        // Loại chuyên đề: '1' (Có test: áp dụng test & minitest) | '0' (Không test: nhập báo cáo chung)
        $rawHasTest = isset($input['has_test']) ? trim((string) $input['has_test']) : '';
        if ($rawHasTest !== '0' && $rawHasTest !== '1') {
            $this->addError('has_test', 'Vui lòng chọn loại chuyên đề (Có test hoặc Không test).');
        }
        $hasTest = ($rawHasTest === '1') ? 1 : 0;
        $hasMinitest = isset($input['has_minitest']) ? ((int) $input['has_minitest'] ? 1 : 0) : $hasTest;

        // Số buổi học tối đa *
        $rawMax = isset($input['max_sessions']) ? trim((string) $input['max_sessions']) : '';
        $maxSessions = null;
        if ($rawMax === '') {
            $this->addError('max_sessions', 'Vui lòng nhập số buổi học tối đa.');
        } elseif (!ctype_digit($rawMax) || (int) $rawMax < 1 || (int) $rawMax > 500) {
            $this->addError('max_sessions', 'Số buổi học tối đa phải là số nguyên từ 1 đến 500.');
        } else {
            $maxSessions = (int) $rawMax;
        }

        $content = isset($input['content']) ? trim((string) $input['content']) : '';
        $reportTemplate = isset($input['report_template']) ? trim((string) $input['report_template']) : '';
        if ($hasTest === 1) {
            // Khi Loại chuyên đề = Có test, ẩn/bỏ trống Template báo cáo theo mockup chuyên đề mới (2).png
            $reportTemplate = '';
        }

        $testFileUrl = $before !== null && !empty($before['test_file_url']) ? (string) $before['test_file_url'] : null;
        $minitestFileUrl = $before !== null && !empty($before['minitest_file_url']) ? (string) $before['minitest_file_url'] : null;

        if ($hasTest === 1) {
            $allowedExts = array('pdf', 'doc', 'docx', 'xls', 'xlsx');
            if (isset($files['test_file']) && is_array($files['test_file']) && !empty($files['test_file']['name'])) {
                $saved = lms_save_files($files['test_file'], 'test/' . date('Ym'), array(
                    'maxFiles' => 1,
                    'maxSize' => 50 * 1024 * 1024,
                    'mimeWhitelist' => $allowedExts,
                ));
                if (!empty($saved['ok']) && !empty($saved['paths'])) {
                    $testFileUrl = $saved['paths'][0];
                } elseif (!empty($saved['error']) && strpos($saved['error'], 'không có file') === false) {
                    $this->addError('test_file', $saved['error']);
                }
            }
            if (!empty($input['delete_test_file'])) {
                $testFileUrl = null;
            }

            if (isset($files['minitest_file']) && is_array($files['minitest_file']) && !empty($files['minitest_file']['name'])) {
                $saved = lms_save_files($files['minitest_file'], 'test/' . date('Ym'), array(
                    'maxFiles' => 1,
                    'maxSize' => 50 * 1024 * 1024,
                    'mimeWhitelist' => $allowedExts,
                ));
                if (!empty($saved['ok']) && !empty($saved['paths'])) {
                    $minitestFileUrl = $saved['paths'][0];
                } elseif (!empty($saved['error']) && strpos($saved['error'], 'không có file') === false) {
                    $this->addError('minitest_file', $saved['error']);
                }
            }
            if (!empty($input['delete_minitest_file'])) {
                $minitestFileUrl = null;
            }
        }

        if ($this->hasErrors()) {
            return false;
        }

        $data = array(
            'subject_id'        => $subjectId,
            'name'              => $name,
            'has_test'          => $hasTest,
            'has_minitest'      => $hasMinitest,
            'max_sessions'      => $maxSessions,
            'content'           => $content !== '' ? $content : null,
            'report_template'   => $reportTemplate !== '' ? $reportTemplate : null,
            'test_file_url'     => $testFileUrl,
            'minitest_file_url' => $minitestFileUrl,
        );

        if ($id > 0) {
            if (!$this->db->update('db_lms_topics', $data, 'id = ?', array($id))) {
                $this->addError('system', 'Không thể cập nhật chuyên đề.');
                return false;
            }
            $after = $this->db->safeFetchOne('select * from db_lms_topics where id = ? limit 1', array($id));
            $this->audit('admin_update_topic', 'topics', $id, $before, $after, 'admin');
            return $id;
        }

        $maxOrderRow = $this->db->safeFetchOne(
            'select coalesce(max(order_index), 0) as max_ord from db_lms_topics where subject_id = ?',
            array($subjectId)
        );
        $data['order_index'] = isset($maxOrderRow['max_ord']) ? ((int) $maxOrderRow['max_ord'] + 1) : 1;

        if (!$this->db->insert('db_lms_topics', $data)) {
            $this->addError('system', 'Không thể thêm chuyên đề mới.');
            return false;
        }
        $newId = (int) $this->db->insertId;
        $after = $this->db->safeFetchOne('select * from db_lms_topics where id = ? limit 1', array($newId));
        $this->audit('admin_create_topic', 'topics', $newId, null, $after, 'admin');
        return $newId;
    }

    /**
     * Nhân bản (Duplicate) chuyên đề theo mockup Quản lý lộ trình.png.
     *
     * @param int $id
     * @return int|false ID chuyên đề mới hoặc false
     */
    public function duplicateTopic($id)
    {
        $this->clearErrors();
        $id = (int) $id;
        $src = $this->db->safeFetchOne('select * from db_lms_topics where id = ? limit 1', array($id));
        if (empty($src)) {
            $this->addError('id', 'Chuyên đề cần nhân bản không tồn tại.');
            return false;
        }

        $baseName = trim((string) $src['name']);
        $newName = mb_substr($baseName . ' (Bản sao)', 0, 150, 'UTF-8');

        $maxOrderRow = $this->db->safeFetchOne(
            'select coalesce(max(order_index), 0) as max_ord from db_lms_topics where subject_id = ?',
            array((int) $src['subject_id'])
        );
        $nextOrder = isset($maxOrderRow['max_ord']) ? ((int) $maxOrderRow['max_ord'] + 1) : 1;

        $data = array(
            'subject_id'        => (int) $src['subject_id'],
            'name'              => $newName,
            'has_test'          => (int) $src['has_test'],
            'has_minitest'      => (int) $src['has_minitest'],
            'max_sessions'      => $src['max_sessions'] !== null ? (int) $src['max_sessions'] : null,
            'content'           => isset($src['content']) ? $src['content'] : null,
            'report_template'   => isset($src['report_template']) ? $src['report_template'] : null,
            'test_file_url'     => isset($src['test_file_url']) ? $src['test_file_url'] : null,
            'minitest_file_url' => isset($src['minitest_file_url']) ? $src['minitest_file_url'] : null,
            'order_index'       => $nextOrder,
        );

        if (!$this->db->insert('db_lms_topics', $data)) {
            $this->addError('system', 'Không thể nhân bản chuyên đề.');
            return false;
        }
        $newId = (int) $this->db->insertId;
        $after = $this->db->safeFetchOne('select * from db_lms_topics where id = ? limit 1', array($newId));
        $this->audit('admin_duplicate_topic', 'topics', $newId, array('source_topic_id' => $id), $after, 'admin');
        return $newId;
    }

    /**
     * Xoá chuyên đề. Kiểm tra ràng buộc khoá ngoại (bài test / chu kỳ khoá học) trước khi xoá.
     *
     * @param int $id
     * @return bool
     */
    public function deleteTopic($id)
    {
        $this->clearErrors();
        $id = (int) $id;
        $before = $this->db->safeFetchOne('select * from db_lms_topics where id = ? limit 1', array($id));
        if (empty($before)) {
            $this->addError('id', 'Chuyên đề không tồn tại.');
            return false;
        }

        $testsCount = (int) $this->db->count('db_lms_tests', 'topic_id = ?', array($id));
        if ($testsCount > 0) {
            $this->addError('id', 'Không thể xoá chuyên đề đã có bài kiểm tra (' . $testsCount . ' bài test).');
            return false;
        }

        $cycleCount = (int) $this->db->count('db_lms_cycle_topics', 'topic_id = ?', array($id));
        $studentTopicCount = (int) $this->db->count('db_lms_cycle_student_topics', 'topic_id = ?', array($id));
        if ($cycleCount > 0 || $studentTopicCount > 0) {
            $this->addError('id', 'Không thể xoá chuyên đề đang được gắn với lộ trình chu kỳ lớp học.');
            return false;
        }

        if (!$this->db->safeExec('delete from db_lms_topics where id = ?', array($id))) {
            $this->addError('system', 'Không thể xoá chuyên đề.');
            return false;
        }

        $this->audit('admin_delete_topic', 'topics', $id, $before, null, 'admin');
        return true;
    }

    public function lessonStats($studentCode, $dateFrom = null, $dateTo = null, $page = 1, $perPage = 25)
    {
        $studentCode = trim((string) $studentCode);
        if ($studentCode === '') { return array('student' => array(), 'items' => array(), 'total' => 0, 'finished_count' => 0); }
        $student = $this->db->safeFetchOne(
            'select s.user_id, s.student_code, s.full_name, s.avatar, s.parent_phone, s.status,'
            . ' u.username, coalesce(nullif(u.phone, ""), s.parent_phone) as phone, u.email'
            . ' from db_lms_students s join db_lms_users u on u.id=s.user_id'
            . ' where s.student_code = ? or u.username = ? limit 1',
            array($studentCode, $studentCode)
        );
        if (empty($student)) { return array('student' => array(), 'items' => array(), 'total' => 0, 'finished_count' => 0); }
        if ($this->validDate($dateFrom) && $this->validDate($dateTo) && (string) $dateFrom > (string) $dateTo) {
            $tmpDate = $dateFrom;
            $dateFrom = $dateTo;
            $dateTo = $tmpDate;
        }
        $page = max(1, (int) $page);
        $perPage = max(1, min(10000, (int) $perPage));
        $offset = ($page - 1) * $perPage;
        $where = array('cs.student_id = ?', 's.status <> "cancelled"');
        $params = array((int) $student['user_id']);
        if ($this->validDate($dateFrom)) { $where[] = 's.session_date >= ?'; $params[] = $dateFrom; }
        if ($this->validDate($dateTo)) { $where[] = 's.session_date <= ?'; $params[] = $dateTo; }
        $whereSql = implode(' and ', $where);
        $count = $this->db->safeFetchOne(
            'select count(*) as c, sum(case when s.status="finished" then 1 else 0 end) as finished'
            . ' from db_lms_session_students cs join db_lms_sessions s on s.id=cs.session_id'
            . ' where ' . $whereSql,
            $params
        );
        $items = $this->db->safeFetch(
            'select s.id as session_id, s.session_date, s.start_time, s.end_time, s.status as session_status,'
            . ' s.lesson_summary, c.class_code, c.name as class_name, t.teacher_code, t.full_name as teacher_name'
            . ' from db_lms_session_students cs join db_lms_sessions s on s.id=cs.session_id'
            . ' join db_lms_classes c on c.id=s.class_id'
            . ' join db_lms_teachers t on t.user_id=s.teacher_id'
            . ' where ' . $whereSql . ' order by s.session_date desc, s.start_time desc'
            . ' limit ' . $offset . ', ' . $perPage,
            $params
        );
        return array(
            'student' => $student,
            'items' => $items,
            'total' => isset($count['c']) ? (int) $count['c'] : 0,
            'finished_count' => isset($count['finished']) ? (int) $count['finished'] : 0,
        );
    }

    private function validDate($date)
    {
        $date = (string) $date;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) { return false; }
        $parsed = \DateTime::createFromFormat('!Y-m-d', $date);
        return $parsed && $parsed->format('Y-m-d') === $date;
    }
}
