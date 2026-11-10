<?php
/**
 * EnrolmentService — quản lý học viên trong lớp (ADM-03, Phase 1.3).
 *
 * Layer riêng cho admin UI "Học sinh - Lớp học":
 *  - listMatrix(): bảng ma trận HV × chương trình (cột động).
 *  - enrollStudent(): thêm HV vào lớp (flip transferred_out → active).
 *  - transferStudent(): chuyển lớp (row cũ transferred_out, row đích re-activate).
 *  - removeStudentFromClass(): xoá khỏi lớp (hard delete + xoá session chưa có dữ liệu).
 *
 * Không sửa ClassService (createClass/updateClass/deleteClass giữ nguyên, ADM-03).
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class EnrolmentService extends LmsService
{
    /** Trạng thái enrolment (db_lms_class_students.status). */
    const ENROLMENT_STATUSES = array(
        'active'           => 'Đang học',
        'transferred_out'  => 'Đã chuyển lớp',
        'completed'        => 'Đã hoàn thành',
    );

    /**
     * Ma trận học viên × chương trình học.
     *
     * Filters:
     *  - class_type  'group' (mặc định) | 'one_on_one' — tab hiện tại.
     *  - q_student   tìm theo username / full_name / student_code.
     *  - q_class     chỉ giữ HV đang học lớp khớp class_code / name.
     *  - program_id  tuỳ chọn.
     *
     * Trả array('items', 'programs', 'total', 'page', 'perPage'):
     *  - programs: cột động, distinct programs có ≥1 lớp active của tab.
     *  - items: mỗi dòng 1 HV, kèm 'classes' gộp theo program_id.
     *
     * @param array $filters
     * @param int   $page
     * @param int   $perPage
     * @return array
     */
    public function listMatrix(array $filters = array(), $page = 1, $perPage = 20)
    {
        $this->clearErrors();

        $classType = isset($filters['class_type']) ? (string) $filters['class_type'] : 'group';
        if (!isset(\Lms\ClassService::CLASS_TYPES[$classType])) {
            $classType = 'group';
        }

        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        // ---- Filter where (dùng cho cả count + chọn trang) ----
        $where = array(
            'c.deleted_at is null',
            'c.status = ?',
            'c.class_type = ?',
            'cs.status = ?',
        );
        $params = array('active', $classType, 'active');

        $qStudent = isset($filters['q_student']) ? trim((string) $filters['q_student']) : '';
        if ($qStudent !== '') {
            $where[] = '(u.username like ? or s.full_name like ? or s.student_code like ?)';
            $like = '%' . $qStudent . '%';
            array_push($params, $like, $like, $like);
        }

        $qClass = isset($filters['q_class']) ? trim((string) $filters['q_class']) : '';
        if ($qClass !== '') {
            $where[] = '(c.class_code like ? or c.name like ?)';
            $like = '%' . $qClass . '%';
            array_push($params, $like, $like);
        }

        $programId = isset($filters['program_id']) ? (int) $filters['program_id'] : 0;
        if ($programId > 0) {
            $where[] = 'c.program_id = ?';
            $params[] = $programId;
        }

        $whereSql = implode(' and ', $where);
        $joins = ' db_lms_class_students cs'
            . ' join db_lms_classes c on c.id = cs.class_id'
            . ' join db_lms_users u on u.id = cs.student_id'
            . ' join db_lms_students s on s.user_id = cs.student_id';

        // ---- Tổng số HV (distinct) ----
        $countRow = $this->db->safeFetchOne(
            'select count(distinct cs.student_id) as c from' . $joins . ' where ' . $whereSql,
            $params
        );
        $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;

        // ---- Chọn 1 trang HV (distinct student_id + cột sort) ----
        $sortCol = $this->matrixSort($filters);
        $idRows = $this->db->safeFetch(
            'select distinct cs.student_id, u.id, s.full_name, u.username, s.student_code from' . $joins
            . ' where ' . $whereSql
            . ' order by ' . $sortCol
            . ' limit ' . $offset . ', ' . $perPage,
            $params
        );

        $items = array();
        $studentIds = array();
        $order = array();
        foreach ($idRows as $r) {
            $sid = (int) $r['student_id'];
            $studentIds[] = $sid;
            $order[$sid] = array(
                'user_id'      => $sid,
                'username'     => (string) $r['username'],
                'full_name'    => (string) $r['full_name'],
                'student_code' => (string) $r['student_code'],
                'grade'        => null,
                'classes'      => array(),
            );
        }

        if ($studentIds !== array()) {
            // ---- Enrolments của trang (1 query, group bằng PHP — tránh N+1) ----
            $placeholders = implode(', ', array_fill(0, count($studentIds), '?'));
            $enrolments = $this->db->safeFetch(
                'select cs.student_id, s.full_name, s.grade, s.student_code, u.username,'
                . ' c.id as class_id, c.class_code, c.name as class_name, c.program_id,'
                . ' t.teacher_code, t.full_name as teacher_name'
                . ' from db_lms_class_students cs'
                . ' join db_lms_classes c on c.id = cs.class_id'
                . ' join db_lms_users u on u.id = cs.student_id'
                . ' join db_lms_students s on s.user_id = cs.student_id'
                . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
                . ' where cs.status = ? and c.deleted_at is null and c.status = ? and c.class_type = ?'
                . ' and cs.student_id in (' . $placeholders . ')'
                . ' order by s.full_name, c.class_code',
                array_merge(array('active', 'active', $classType), $studentIds)
            );

            // ---- Schedules 1 query cho mọi lớp của trang ----
            $classIds = array();
            foreach ($enrolments as $r) {
                $classIds[(int) $r['class_id']] = (int) $r['class_id'];
            }
            $scheduleMap = $this->schedulesMap(array_values($classIds));

            foreach ($enrolments as $r) {
                $sid = (int) $r['student_id'];
                if (!isset($order[$sid])) {
                    continue;
                }
                $cid = (int) $r['class_id'];
                $pid = (int) $r['program_id'];
                if (!isset($order[$sid]['classes'][$pid])) {
                    $order[$sid]['classes'][$pid] = array();
                }
                $order[$sid]['classes'][$pid][] = array(
                    'class_id'      => $cid,
                    'class_code'    => (string) $r['class_code'],
                    'class_name'    => (string) $r['class_name'],
                    'teacher_code'  => (string) $r['teacher_code'],
                    'teacher_name'  => (string) $r['teacher_name'],
                    'schedules_text' => isset($scheduleMap[$cid]) ? $scheduleMap[$cid] : '',
                );
                if ($order[$sid]['grade'] === null) {
                    $order[$sid]['grade'] = (string) $r['grade'];
                }
            }

            // Giữ đúng thứ tự trang (theo sort)
            foreach ($studentIds as $sid) {
                $items[] = $order[$sid];
            }
        }

        // ---- Cột động: programs có ≥1 lớp active của tab ----
        $programs = $this->db->safeFetch(
            'select distinct p.id, p.name'
            . ' from db_lms_programs p'
            . ' join db_lms_classes c on c.program_id = p.id'
            . ' where c.deleted_at is null and c.status = ? and c.class_type = ?'
            . ' order by p.name',
            array('active', $classType)
        );

        return array(
            'items'    => $items,
            'programs' => $programs,
            'total'    => $total,
            'page'     => $page,
            'perPage'  => $perPage,
        );
    }

    /**
     * Cột sort cho ma trận (whitelist, chống injection qua query string).
     *
     * @param array $filters
     * @return string
     */
    private function matrixSort(array $filters)
    {
        $map = array(
            'full_name'    => 's.full_name asc, u.id asc',
            'full_name_desc' => 's.full_name desc, u.id desc',
            'username'     => 'u.username asc, u.id asc',
            'student_code' => 's.student_code asc, u.id asc',
        );
        $sort = isset($filters['sort']) ? (string) $filters['sort'] : 'full_name';
        return isset($map[$sort]) ? $map[$sort] : $map['full_name'];
    }

    /**
     * Map class_id → schedules_text ("Thứ 3 19:00-20:30 / Thứ 5 19:00-20:30").
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

    /**
     * Thêm học viên vào lớp (kèm sync session_students).
     *
     * @param int $classId
     * @param int $studentId
     * @return bool
     */
    public function enrollStudent($classId, $studentId)
    {
        $this->clearErrors();

        $classId = (int) $classId;
        $studentId = (int) $studentId;
        if ($classId <= 0 || $studentId <= 0) {
            $this->addError('system', 'ID không hợp lệ.');
            return false;
        }

        // ---- Validate (thu thập hết lỗi) ----
        $class = $this->db->safeFetchOne(
            'select id, class_type, status, deleted_at from db_lms_classes where id = ? limit 1',
            array($classId)
        );
        if (empty($class) || (string) $class['status'] !== 'active' || $class['deleted_at'] !== null) {
            $this->addError('class_id', 'Lớp không hoạt động.');
        }

        $student = $this->db->safeFetchOne(
            'select user_id, status from db_lms_students where user_id = ? limit 1',
            array($studentId)
        );
        if (empty($student)) {
            $this->addError('student_id', 'Học viên không tồn tại.');
        } elseif ((string) $student['status'] === 'quit') {
            $this->addError('student_id', 'Học viên đã nghỉ học.');
        }

        $existing = $this->db->safeFetchOne(
            'select id, status from db_lms_class_students where class_id = ? and student_id = ? limit 1',
            array($classId, $studentId)
        );
        if (!empty($existing) && (string) $existing['status'] === 'active') {
            $this->addError('student_id', 'Học viên đang học lớp này.');
        }

        $this->validateCapacity($class, $classId, $studentId, 'class_id');

        if ($this->hasErrors()) {
            return false;
        }

        // ---- Transaction ----
        $this->db->beginTransaction();
        try {
            $enrolmentId = $this->upsertEnrolment($classId, $studentId);
            if ($enrolmentId === null) {
                throw new \Exception('upsertEnrolment failed');
            }
            $this->syncUpcomingSessionStudents($classId, $studentId);
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[LMS] enrollStudent failed: ' . $e->getMessage());
            if (!$this->hasErrors()) {
                $this->addError('system', 'Không thể thêm học viên vào lớp. Vui lòng thử lại.');
            }
            return false;
        }

        $this->audit(
            'enroll_student',
            'class_students',
            $enrolmentId,
            null,
            array('class_id' => $classId, 'student_id' => $studentId)
        );

        return true;
    }

    /**
     * Chuyển học viên sang lớp khác.
     *
     * @param int $studentId
     * @param int $fromClassId
     * @param int $toClassId
     * @return bool
     */
    public function transferStudent($studentId, $fromClassId, $toClassId)
    {
        $this->clearErrors();

        $studentId = (int) $studentId;
        $fromClassId = (int) $fromClassId;
        $toClassId = (int) $toClassId;

        if ($studentId <= 0 || $fromClassId <= 0 || $toClassId <= 0) {
            $this->addError('system', 'ID không hợp lệ.');
            return false;
        }
        if ($fromClassId === $toClassId) {
            $this->addError('to_class_id', 'Vui lòng chọn lớp khác lớp hiện tại.');
            return false;
        }

        // ---- Validate ----
        $fromEnrolment = $this->db->safeFetchOne(
            'select id from db_lms_class_students where class_id = ? and student_id = ? and status = ? limit 1',
            array($fromClassId, $studentId, 'active')
        );
        if (empty($fromEnrolment)) {
            $this->addError('from_class_id', 'Học viên không có trong lớp này.');
        }

        $toClass = $this->db->safeFetchOne(
            'select id, class_type, status, deleted_at from db_lms_classes where id = ? limit 1',
            array($toClassId)
        );
        if (empty($toClass) || (string) $toClass['status'] !== 'active' || $toClass['deleted_at'] !== null) {
            $this->addError('to_class_id', 'Lớp không hoạt động.');
        }

        $activeInTo = $this->db->safeFetchOne(
            'select id from db_lms_class_students where class_id = ? and student_id = ? and status = ? limit 1',
            array($toClassId, $studentId, 'active')
        );
        if (!empty($activeInTo)) {
            $this->addError('to_class_id', 'Học viên đang học lớp này.');
        }

        $this->validateCapacity($toClass, $toClassId, $studentId, 'to_class_id');

        if ($this->hasErrors()) {
            return false;
        }

        // ---- Transaction ----
        $today = date('Y-m-d');
        $this->db->beginTransaction();
        try {
            // Row cũ: transferred_out (giữ enrolled_at làm lịch sử)
            $this->db->update(
                'db_lms_class_students',
                array('status' => 'transferred_out'),
                'class_id = ? and student_id = ?',
                array($fromClassId, $studentId)
            );

            // Row đích: flip transferred_out → active hoặc insert mới
            $enrolmentId = $this->upsertEnrolment($toClassId, $studentId);
            if ($enrolmentId === null) {
                throw new \Exception('upsertEnrolment failed');
            }

            // Sync 2 chiều: thêm session mới của lớp đích, xoá session chưa có
            // dữ liệu của lớp cũ (chỉ upcoming từ hôm nay).
            $this->syncUpcomingSessionStudents($toClassId, $studentId);
            $this->deleteEmptySessionStudents($fromClassId, $studentId, $today);

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[LMS] transferStudent failed: ' . $e->getMessage());
            if (!$this->hasErrors()) {
                $this->addError('system', 'Không thể chuyển lớp. Vui lòng thử lại.');
            }
            return false;
        }

        $this->audit(
            'transfer_student',
            'class_students',
            $studentId,
            array('from_class_id' => $fromClassId),
            array('to_class_id' => $toClassId)
        );

        return true;
    }

    /**
     * Xoá học viên khỏi lớp (hard delete row + session_students chưa có dữ liệu).
     *
     * @param int $classId
     * @param int $studentId
     * @return bool
     */
    public function removeStudentFromClass($classId, $studentId)
    {
        $this->clearErrors();

        $classId = (int) $classId;
        $studentId = (int) $studentId;
        if ($classId <= 0 || $studentId <= 0) {
            $this->addError('system', 'ID không hợp lệ.');
            return false;
        }

        $enrolment = $this->db->safeFetchOne(
            'select id from db_lms_class_students where class_id = ? and student_id = ? and status = ? limit 1',
            array($classId, $studentId, 'active')
        );
        if (empty($enrolment)) {
            $this->addError('student_id', 'Học viên không có trong lớp này.');
            return false;
        }

        $today = date('Y-m-d');

        $this->db->beginTransaction();
        try {
            $this->deleteEmptySessionStudents($classId, $studentId, $today);
            $this->db->safeExec(
                'delete from db_lms_class_students where class_id = ? and student_id = ?',
                array($classId, $studentId)
            );
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[LMS] removeStudentFromClass failed: ' . $e->getMessage());
            if (!$this->hasErrors()) {
                $this->addError('system', 'Không thể xoá học viên khỏi lớp. Vui lòng thử lại.');
            }
            return false;
        }

        $this->audit(
            'unenroll_student',
            'class_students',
            (int) $enrolment['id'],
            array('class_id' => $classId, 'student_id' => $studentId),
            null
        );

        return true;
    }

    /**
     * Các lớp HV có thể enrol (active, đúng loại lớp, chưa active) cho form thêm/chuyển.
     *
     * @param int    $studentId
     * @param string $classType
     * @param int    $excludeClassId bỏ qua lớp này (lớp hiện tại khi chuyển)
     * @return array
     */
    public function studentClassOptions($studentId, $classType, $excludeClassId = 0)
    {
        $this->clearErrors();

        $studentId = (int) $studentId;
        $excludeClassId = (int) $excludeClassId;
        if (!isset(\Lms\ClassService::CLASS_TYPES[$classType])) {
            return array();
        }

        $sql = 'select c.id, c.class_code, c.name as class_name, p.name as program_name,'
            . ' t.teacher_code, t.full_name as teacher_name'
            . ' from db_lms_classes c'
            . ' left join db_lms_programs p on p.id = c.program_id'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' where c.deleted_at is null and c.status = ? and c.class_type = ?'
            . ' and not exists ('
            . '   select 1 from db_lms_class_students cs'
            . '   where cs.class_id = c.id and cs.student_id = ? and cs.status = ?'
            . ')';
        $params = array('active', $classType, $studentId, 'active');
        if ($excludeClassId > 0) {
            $sql .= ' and c.id <> ?';
            $params[] = $excludeClassId;
        }
        $sql .= ' order by c.class_code';

        $options = $this->db->safeFetch($sql, $params);

        $classIds = array();
        foreach ($options as $o) {
            $classIds[] = (int) $o['id'];
        }
        $scheduleMap = $this->schedulesMap($classIds);
        foreach ($options as $i => $o) {
            $cid = (int) $o['id'];
            $options[$i]['schedules_text'] = isset($scheduleMap[$cid]) ? $scheduleMap[$cid] : '';
        }

        return $options;
    }

    // ===================== Helper nội bộ =====================

    /**
     * Sĩ số lớp 1 kèm 1: đã có HV active khác → lỗi.
     *
     * @param array  $class row classes (có thể rỗng)
     * @param int    $classId
     * @param int    $studentId HV đang enrol (không tính chính nó)
     * @param string $field tên field để addError
     * @return void
     */
    private function validateCapacity($class, $classId, $studentId, $field)
    {
        if (empty($class) || (string) $class['class_type'] !== 'one_on_one') {
            return;
        }
        $count = (int) $this->db->count(
            'db_lms_class_students',
            'class_id = ? and status = ? and student_id <> ?',
            array((int) $classId, 'active', (int) $studentId)
        );
        if ($count >= 1) {
            $this->addError($field, 'Lớp 1 kèm 1 chỉ có 1 học viên.');
        }
    }

    /**
     * Insert/flip enrolment: row transferred_out → update lại active, ngược lại insert.
     * Trả enrolment id (để audit) hoặc null khi thất bại.
     *
     * @param int $classId
     * @param int $studentId
     * @return int|null
     */
    private function upsertEnrolment($classId, $studentId)
    {
        $existing = $this->db->safeFetchOne(
            'select id, status from db_lms_class_students where class_id = ? and student_id = ? limit 1',
            array((int) $classId, (int) $studentId)
        );
        if (!empty($existing)) {
            $ok = $this->db->update(
                'db_lms_class_students',
                array('status' => 'active'),
                'id = ?',
                array((int) $existing['id'])
            );
            return $ok ? (int) $existing['id'] : null;
        }
        $ok = $this->db->insert('db_lms_class_students', array(
            'class_id'   => (int) $classId,
            'student_id' => (int) $studentId,
            'status'     => 'active',
        ));
        return $ok ? (int) $this->db->insertId : null;
    }

    /**
     * Thêm session_students cho các session upcoming (từ hôm nay) của lớp,
     * HV chưa có row (tránh vi phạm uniq_session_student).
     *
     * @param int $classId
     * @param int $studentId
     * @return void
     */
    private function syncUpcomingSessionStudents($classId, $studentId)
    {
        $today = date('Y-m-d');
        $sessions = $this->db->safeFetch(
            'select id from db_lms_sessions'
            . ' where class_id = ? and status = ? and session_date >= ?',
            array((int) $classId, 'upcoming', $today)
        );
        foreach ($sessions as $s) {
            $sessionId = (int) $s['id'];
            $exists = $this->db->safeFetchOne(
                'select id from db_lms_session_students where session_id = ? and student_id = ? limit 1',
                array($sessionId, (int) $studentId)
            );
            if (!empty($exists)) {
                continue;
            }
            $this->db->insert('db_lms_session_students', array(
                'session_id' => $sessionId,
                'student_id' => (int) $studentId,
                'status'     => 'not_submitted',
            ));
        }
    }

    /**
     * Xoá session_students của các session upcoming (từ hôm nay) chưa có dữ liệu
     * học (chưa nộp bài / chưa chấm). Session đã có dữ liệu được giữ lại.
     *
     * @param int    $classId
     * @param int    $studentId
     * @param string $today 'Y-m-d'
     * @return void
     */
    private function deleteEmptySessionStudents($classId, $studentId, $today)
    {
        $sessions = $this->db->safeFetch(
            'select id from db_lms_sessions'
            . ' where class_id = ? and status = ? and session_date >= ?',
            array((int) $classId, 'upcoming', $today)
        );
        if ($sessions === array()) {
            return;
        }
        $sessionIds = array();
        foreach ($sessions as $s) {
            $sessionIds[] = (int) $s['id'];
        }
        $placeholders = implode(', ', array_fill(0, count($sessionIds), '?'));
        $this->db->safeExec(
            'delete from db_lms_session_students'
            . ' where student_id = ? and session_id in (' . $placeholders . ')'
            . ' and hw_submitted_at is null and grade is null'
            . ' and teacher_feedback_text is null and hw_submission_files is null',
            array_merge(array((int) $studentId), $sessionIds)
        );
    }
}
