<?php
/**
 * ClassService — quản lý lớp học (ADM-03).
 *
 * Tạo lớp: validate program/subject/teacher, sinh class_code, gán teacher,
 * tạo lịch cố định (delegating ScheduleService), sinh sessions 4 tuần.
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class ClassService extends LmsService
{
    /** Loại lớp (db_lms_classes.class_type). */
    const CLASS_TYPES = array(
        'one_on_one' => '1 kèm 1',
        'group'      => 'Nhóm',
    );

    /** Trạng thái lớp (db_lms_classes.status). */
    const CLASS_STATUSES = array(
        'active'    => 'Đang hoạt động',
        'paused'    => 'Tạm dừng',
        'finished'  => 'Đã kết thúc',
        'cancelled' => 'Đã huỷ',
    );

    /** Số tuần sinh sessions trước khi tạo lớp (mirror SessionService). */
    const LOOKAHEAD_WEEKS = 4;

    /** @var ScheduleService */
    private $schedules;

    /** @var SessionService */
    private $sessions;

    public function __construct(\LmsDb $db = null, ScheduleService $schedules = null, SessionService $sessions = null)
    {
        parent::__construct($db);
        $this->schedules = $schedules !== null ? $schedules : new ScheduleService($this->db);
        $this->sessions  = $sessions  !== null ? $sessions  : new SessionService($this->db);
    }

    /**
     * Tạo lớp học mới.
     *
     * @param array $data khóa: program_id, subject_id, teacher_id, class_type,
     *                     name, price_per_session, teacher_percentage, total_sessions,
     *                     hw_deadline_time, hw_check_hours, start_date,
     *                     schedules (mảng array('day'=>2..8,'start'=>'HH:MM','end'=>'HH:MM')),
     *                     student_ids (mảng int)
     * @return int|null class id
     */
    public function createClass(array $data)
    {
        $this->clearErrors();

        $required = array('program_id', 'subject_id', 'teacher_id', 'class_type');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->addError($field, 'Trường bắt buộc.');
                return null;
            }
        }

        $teacher = $this->db->safeFetchOne(
            'select user_id, status, max_classes from db_lms_teachers where user_id = ? limit 1',
            array((int) $data['teacher_id'])
        );
        if (empty($teacher)) {
            $this->addError('teacher_id', 'Giáo viên không tồn tại.');
            return null;
        }
        if (isset($teacher['status']) && $teacher['status'] !== 'active') {
            $this->addError('teacher_id', 'Giáo viên đang không hoạt động.');
            return null;
        }

        $program = $this->db->safeFetchOne('select id from db_lms_programs where id = ? limit 1', array((int) $data['program_id']));
        if (empty($program)) {
            $this->addError('program_id', 'Chương trình không tồn tại.');
            return null;
        }
        $subject = $this->db->safeFetchOne('select id from db_lms_subjects where id = ? limit 1', array((int) $data['subject_id']));
        if (empty($subject)) {
            $this->addError('subject_id', 'Môn học không tồn tại.');
            return null;
        }

        $classType = (string) $data['class_type'];
        if (!isset(self::CLASS_TYPES[$classType])) {
            $this->addError('class_type', 'Loại lớp không hợp lệ.');
            return null;
        }

        $fields = $this->validateClassFields($data, $classType);
        if ($fields === null) {
            return null;
        }

        // BR max_classes (SRS ADM-03): đếm lớp active hiện tại của GV
        $maxClasses = isset($teacher['max_classes']) ? (int) $teacher['max_classes'] : 0;
        if ($maxClasses > 0) {
            $activeCount = (int) $this->db->count(
                'db_lms_classes',
                'teacher_id = ? and status = ? and deleted_at is null',
                array((int) $data['teacher_id'], 'active')
            );
            if ($activeCount >= $maxClasses) {
                $this->addError('teacher_id', 'Giáo viên đã đạt số lớp tối đa (max_classes).');
                return null;
            }
        }

        $code = $this->generateClassCode($classType);

        $this->db->beginTransaction();
        try {
            $ok = $this->db->insert('db_lms_classes', array(
                'class_code'         => $code,
                'program_id'         => (int) $data['program_id'],
                'subject_id'         => (int) $data['subject_id'],
                'teacher_id'         => (int) $data['teacher_id'],
                'class_type'         => $classType,
                'name'               => $fields['name'],
                'price_per_session'  => $fields['price_per_session'],
                'teacher_percentage' => $fields['teacher_percentage'],
                'total_sessions'     => $fields['total_sessions'],
                'remaining_sessions' => $fields['total_sessions'],
                'hw_deadline_time'   => $fields['hw_deadline_time'],
                'hw_check_hours'     => $fields['hw_check_hours'],
                'status'             => 'active',
                'start_date'         => $fields['start_date'],
            ));
            if (!$ok) {
                throw new \Exception('insert db_lms_classes failed');
            }
            $classId = (int) $this->db->insertId;

            foreach ($fields['schedules'] as $sch) {
                $sid = $this->schedules->addSchedule(
                    $classId,
                    $sch['day'],
                    $sch['start'],
                    $sch['end'],
                    $fields['start_date']
                );
                if ($sid === null) {
                    $err = $this->schedules->errors();
                    $this->addError(
                        'schedules',
                        isset($err['schedule']) ? $err['schedule'] : 'Lịch học không hợp lệ.'
                    );
                    throw new \Exception('addSchedule rejected');
                }
            }

            foreach ($fields['student_ids'] as $studentId) {
                if (!$this->enrollStudentInternal($classId, $studentId)) {
                    throw new \Exception('enrollStudent failed');
                }
            }

            if ($fields['schedules'] !== array()) {
                $this->generateSessionsForClass($classId, (int) $data['teacher_id'], $fields['start_date']);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[LMS] createClass failed: ' . $e->getMessage());
            if (!$this->hasErrors()) {
                $this->addError('system', 'Không thể tạo lớp. Vui lòng thử lại.');
            }
            return null;
        }

        $this->audit('create_class', 'classes', $classId, null, $data);

        return $classId;
    }

    /**
     * Cập nhật lớp học (thay lịch nguyên khối, sync enrolment, regen sessions).
     *
     * @param int   $classId
     * @param array $data như createClass (class_code không đổi)
     * @return bool
     */
    public function updateClass($classId, array $data)
    {
        $this->clearErrors();

        $classId = (int) $classId;
        if ($classId <= 0) {
            $this->addError('system', 'ID không hợp lệ.');
            return false;
        }

        $before = $this->getClass($classId);
        if (empty($before)) {
            $this->addError('system', 'Lớp học không tồn tại.');
            return false;
        }

        $required = array('program_id', 'subject_id', 'teacher_id', 'class_type');
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $this->addError($field, 'Trường bắt buộc.');
                return false;
            }
        }

        $teacherId = (int) $data['teacher_id'];
        $teacher = $this->db->safeFetchOne(
            'select user_id, status, max_classes from db_lms_teachers where user_id = ? limit 1',
            array($teacherId)
        );
        if (empty($teacher)) {
            $this->addError('teacher_id', 'Giáo viên không tồn tại.');
            return false;
        }
        if (isset($teacher['status']) && $teacher['status'] !== 'active') {
            $this->addError('teacher_id', 'Giáo viên đang không hoạt động.');
            return false;
        }

        $program = $this->db->safeFetchOne('select id from db_lms_programs where id = ? limit 1', array((int) $data['program_id']));
        if (empty($program)) {
            $this->addError('program_id', 'Chương trình không tồn tại.');
            return false;
        }
        $subject = $this->db->safeFetchOne('select id from db_lms_subjects where id = ? limit 1', array((int) $data['subject_id']));
        if (empty($subject)) {
            $this->addError('subject_id', 'Môn học không tồn tại.');
            return false;
        }

        $classType = (string) $data['class_type'];
        if (!isset(self::CLASS_TYPES[$classType])) {
            $this->addError('class_type', 'Loại lớp không hợp lệ.');
            return false;
        }

        $fields = $this->validateClassFields($data, $classType);
        if ($fields === null) {
            return false;
        }

        // Backward compat: thiếu key schedules/student_ids → giữ nguyên hiện tại
        if (!isset($data['schedules'])) {
            $fields['schedules'] = array();
            foreach ($before['schedules'] as $sch) {
                $fields['schedules'][] = array(
                    'day'   => (int) $sch['day_of_week'],
                    'start' => substr((string) $sch['start_time'], 0, 5),
                    'end'   => substr((string) $sch['end_time'], 0, 5),
                );
            }
        }
        if (!isset($data['student_ids'])) {
            $fields['student_ids'] = $before['student_ids'];
        }

        // max_classes: chỉ check GV mới (không check lại GV cũ)
        if ($teacherId !== (int) $before['teacher_id']) {
            $maxClasses = isset($teacher['max_classes']) ? (int) $teacher['max_classes'] : 0;
            if ($maxClasses > 0) {
                $activeCount = (int) $this->db->count(
                    'db_lms_classes',
                    'teacher_id = ? and status = ? and deleted_at is null',
                    array($teacherId, 'active')
                );
                if ($activeCount >= $maxClasses) {
                    $this->addError('teacher_id', 'Giáo viên đã đạt số lớp tối đa (max_classes).');
                    return false;
                }
            }
        }

        // total_sessions thay đổi → điều chỉnh remaining_sessions cùng delta (không âm)
        $remaining = (int) $before['remaining_sessions'];
        if ($fields['total_sessions'] !== (int) $before['total_sessions']) {
            $delta = $fields['total_sessions'] - (int) $before['total_sessions'];
            $remaining = max(0, $remaining + $delta);
        }

        $today = date('Y-m-d');

        $this->db->beginTransaction();
        try {
            $ok = $this->db->update('db_lms_classes', array(
                'program_id'         => (int) $data['program_id'],
                'subject_id'         => (int) $data['subject_id'],
                'teacher_id'         => $teacherId,
                'class_type'         => $classType,
                'name'               => $fields['name'],
                'price_per_session'  => $fields['price_per_session'],
                'teacher_percentage' => $fields['teacher_percentage'],
                'total_sessions'     => $fields['total_sessions'],
                'remaining_sessions' => $remaining,
                'hw_deadline_time'   => $fields['hw_deadline_time'],
                'hw_check_hours'     => $fields['hw_check_hours'],
                'start_date'         => $fields['start_date'],
            ), 'id = ?', array($classId));
            if (!$ok) {
                throw new \Exception('update db_lms_classes failed');
            }

            // Thay lịch nguyên khối: xoá cũ, add lại (BR-SCHED-01 tự check)
            $this->db->safeExec('delete from db_lms_schedules where class_id = ?', array($classId));
            foreach ($fields['schedules'] as $sch) {
                $sid = $this->schedules->addSchedule(
                    $classId,
                    $sch['day'],
                    $sch['start'],
                    $sch['end'],
                    $fields['start_date']
                );
                if ($sid === null) {
                    $err = $this->schedules->errors();
                    $this->addError(
                        'schedules',
                        isset($err['schedule']) ? $err['schedule'] : 'Lịch học không hợp lệ.'
                    );
                    throw new \Exception('addSchedule rejected');
                }
            }

            // Sync enrolment: insert HS mới, delete HS bị bỏ
            foreach ($fields['student_ids'] as $studentId) {
                if (!$this->enrollStudentInternal($classId, $studentId)) {
                    throw new \Exception('enrollStudent failed');
                }
            }
            $this->db->safeExec(
                'delete from db_lms_class_students where class_id = ?'
                . ($fields['student_ids'] !== array()
                    ? ' and student_id not in (' . implode(', ', array_fill(0, count($fields['student_ids']), '?')) . ')'
                    : ''),
                $fields['student_ids'] !== array()
                    ? array_merge(array($classId), $fields['student_ids'])
                    : array($classId)
            );

            // Regen sessions: xoá session_students của HS không còn trong lớp +
            // xoá sessions upcoming từ hôm nay, sinh lại từ lịch mới
            $this->db->safeExec(
                'delete from db_lms_session_students where session_id in'
                . ' (select id from db_lms_sessions where class_id = ? and status = ? and session_date >= ?)'
                . ($fields['student_ids'] !== array()
                    ? ' and student_id not in (' . implode(', ', array_fill(0, count($fields['student_ids']), '?')) . ')'
                    : ''),
                $fields['student_ids'] !== array()
                    ? array_merge(array($classId, 'upcoming', $today), $fields['student_ids'])
                    : array($classId, 'upcoming', $today)
            );
            $this->db->safeExec(
                'delete from db_lms_sessions where class_id = ? and status = ? and session_date >= ?',
                array($classId, 'upcoming', $today)
            );

            if ($fields['schedules'] !== array()) {
                $this->generateSessionsForClass($classId, $teacherId, $fields['start_date']);
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[LMS] updateClass failed: ' . $e->getMessage());
            if (!$this->hasErrors()) {
                $this->addError('system', 'Không thể cập nhật lớp. Vui lòng thử lại.');
            }
            return false;
        }

        $after = $this->getClass($classId);
        $this->audit('update_class', 'classes', $classId, $before, $after);

        return true;
    }

    /**
     * Sinh class_code ngẫu nhiên duy nhất.
     *
     * @param string $classType
     * @return string
     */
    public function generateClassCode($classType)
    {
        $prefix = $classType === 'one_on_one' ? 'CLS11' : 'CLSGR';
        for ($i = 0; $i < 20; $i++) {
            try {
                $suffix = bin2hex(random_bytes(3));
            } catch (\Exception $e) {
                $suffix = substr(md5(uniqid((string) mt_rand(), true)), 0, 6);
            }
            $code = strtoupper($prefix . '-' . $suffix);
            $exists = $this->db->safeFetchOne(
                'select id from db_lms_classes where class_code = ? limit 1',
                array($code)
            );
            if (empty($exists)) {
                return $code;
            }
        }
        return strtoupper($prefix . '-' . time());
    }

    /**
     * Chi tiết 1 lớp (join program/subject/teacher) + schedules + student_ids.
     *
     * @param int $classId
     * @return array
     */
    public function getClass($classId)
    {
        $this->clearErrors();

        $classId = (int) $classId;
        if ($classId <= 0) {
            return array();
        }

        $row = $this->db->safeFetchOne(
            'select c.id, c.class_code, c.program_id, c.subject_id, c.teacher_id, c.class_type,'
            . ' c.name, c.price_per_session, c.teacher_percentage, c.total_sessions, c.remaining_sessions,'
            . ' c.hw_deadline_time, c.hw_check_hours, c.status, c.start_date, c.created_at,'
            . ' p.name as program_name, s.name as subject_name,'
            . ' t.teacher_code, t.full_name as teacher_name, t.service_fee_percentage, t.max_classes'
            . ' from db_lms_classes c'
            . ' left join db_lms_programs p on p.id = c.program_id'
            . ' left join db_lms_subjects s on s.id = c.subject_id'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' where c.id = ? and c.deleted_at is null limit 1',
            array($classId)
        );
        if (empty($row)) {
            return array();
        }

        $row['schedules'] = $this->schedules->listForClass($classId);

        $students = $this->db->safeFetch(
            'select student_id from db_lms_class_students where class_id = ? and status = ?',
            array($classId, 'active')
        );
        $row['student_ids'] = array();
        foreach ($students as $s) {
            $row['student_ids'][] = (int) $s['student_id'];
        }

        return $row;
    }

    /**
     * Danh sách lớp + filters + phân trang (tránh N+1: schedules 1 query gộp).
     *
     * @param array $filters q, teacher_id, program_ids[], status, class_type,
     *                        day_of_week, sort
     * @param int   $page
     * @param int   $perPage
     * @return array array('items' => array, 'total' => int)
     */
    public function listClasses(array $filters = array(), $page = 1, $perPage = 20)
    {
        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        $where = array('c.deleted_at is null');
        $params = array();

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $where[] = '(c.class_code like ? or c.name like ?)';
            $like = '%' . $q . '%';
            array_push($params, $like, $like);
        }

        $teacherId = isset($filters['teacher_id']) ? (int) $filters['teacher_id'] : 0;
        if ($teacherId > 0) {
            $where[] = 'c.teacher_id = ?';
            $params[] = $teacherId;
        }

        $programIds = array();
        if (isset($filters['program_ids']) && is_array($filters['program_ids'])) {
            foreach ($filters['program_ids'] as $pid) {
                $pid = (int) $pid;
                if ($pid > 0) {
                    $programIds[] = $pid;
                }
            }
        }
        if ($programIds !== array()) {
            $where[] = 'c.program_id in (' . implode(', ', array_fill(0, count($programIds), '?')) . ')';
            $params = array_merge($params, $programIds);
        }

        $status = isset($filters['status']) ? (string) $filters['status'] : '';
        if ($status !== '' && isset(self::CLASS_STATUSES[$status])) {
            $where[] = 'c.status = ?';
            $params[] = $status;
        }

        $classType = isset($filters['class_type']) ? (string) $filters['class_type'] : '';
        if ($classType !== '' && isset(self::CLASS_TYPES[$classType])) {
            $where[] = 'c.class_type = ?';
            $params[] = $classType;
        }

        $dayOfWeek = isset($filters['day_of_week']) ? (int) $filters['day_of_week'] : 0;
        if ($dayOfWeek >= 2 && $dayOfWeek <= 8) {
            $where[] = 'exists (select 1 from db_lms_schedules s where s.class_id = c.id and s.day_of_week = ?)';
            $params[] = $dayOfWeek;
        }

        $whereSql = implode(' and ', $where);

        $countRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_classes c where ' . $whereSql,
            $params
        );
        $total = isset($countRow['c']) ? (int) $countRow['c'] : 0;

        $items = $this->db->safeFetch(
            'select c.id, c.class_code, c.name, c.class_type, c.status, c.price_per_session,'
            . ' c.total_sessions, c.remaining_sessions, c.start_date, c.created_at,'
            . ' p.name as program_name, s.name as subject_name,'
            . ' t.teacher_code, t.full_name as teacher_name,'
            . ' (select count(*) from db_lms_class_students cs'
            . '   where cs.class_id = c.id and cs.status = "active") as student_count'
            . ' from db_lms_classes c'
            . ' left join db_lms_programs p on p.id = c.program_id'
            . ' left join db_lms_subjects s on s.id = c.subject_id'
            . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
            . ' where ' . $whereSql
            . ' order by ' . $this->classSort($filters)
            . ' limit ' . $offset . ', ' . $perPage,
            $params
        );

        // Gộp schedules 1 query cho tất cả lớp (tránh N+1)
        $classIds = array();
        foreach ($items as $it) {
            $classIds[] = (int) $it['id'];
        }
        $scheduleMap = array();
        if ($classIds !== array()) {
            $rows = $this->db->safeFetch(
                'select class_id, day_of_week, start_time, end_time from db_lms_schedules'
                . ' where class_id in (' . implode(', ', array_fill(0, count($classIds), '?')) . ')'
                . ' order by day_of_week, start_time',
                $classIds
            );
            foreach ($rows as $r) {
                $scheduleMap[(int) $r['class_id']][] = $r;
            }
        }
        foreach ($items as $i => $it) {
            $items[$i]['schedules'] = isset($scheduleMap[(int) $it['id']])
                ? $scheduleMap[(int) $it['id']]
                : array();
        }

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Cột sort cho danh sách lớp (whitelist, chống injection qua query string).
     *
     * @param array $filters
     * @return string
     */
    private function classSort(array $filters)
    {
        $map = array(
            'created_at' => 'c.created_at desc',
            'name'       => 'c.name asc',
            'code'       => 'c.class_code asc',
            'teacher'    => 't.full_name asc',
        );
        $sort = isset($filters['sort']) ? (string) $filters['sort'] : 'created_at';
        return isset($map[$sort]) ? $map[$sort] : $map['created_at'];
    }

    /**
     * Xoá lớp (soft-delete) + xoá schedules, class_students, sessions upcoming.
     * Sessions finished giữ lại làm lịch sử giao dịch.
     *
     * @param int $classId
     * @return bool
     */
    public function deleteClass($classId)
    {
        $this->clearErrors();

        $classId = (int) $classId;
        if ($classId <= 0) {
            $this->addError('system', 'ID không hợp lệ.');
            return false;
        }

        $before = $this->getClass($classId);
        if (empty($before)) {
            $this->addError('system', 'Lớp học không tồn tại.');
            return false;
        }

        $today = date('Y-m-d');

        $this->db->beginTransaction();
        try {
            $this->db->safeExec(
                'delete from db_lms_sessions where class_id = ? and status = ? and session_date >= ?',
                array($classId, 'upcoming', $today)
            );
            $this->db->safeExec('delete from db_lms_schedules where class_id = ?', array($classId));
            $this->db->safeExec('delete from db_lms_class_students where class_id = ?', array($classId));

            $ok = $this->db->update('db_lms_classes', array('deleted_at' => $this->now()), 'id = ?', array($classId));
            if (!$ok) {
                throw new \Exception('soft-delete failed');
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[LMS] deleteClass failed: ' . $e->getMessage());
            $this->addError('system', 'Không thể xoá lớp. Vui lòng thử lại.');
            return false;
        }

        $this->audit('delete_class', 'classes', $classId, $before, null);

        return true;
    }

    /**
     * Tạm dừng / tiếp tục lớp (SRS ADM-03 alternative flow).
     *
     * @param int    $classId
     * @param string $status 'active' | 'paused'
     * @return bool
     */
    public function setClassStatus($classId, $status)
    {
        $this->clearErrors();

        $classId = (int) $classId;
        $status  = (string) $status;

        if (!in_array($status, array('active', 'paused'), true)) {
            $this->addError('system', 'Trạng thái không hợp lệ.');
            return false;
        }
        if ($classId <= 0) {
            $this->addError('system', 'ID không hợp lệ.');
            return false;
        }

        $row = $this->db->safeFetchOne(
            'select id, status from db_lms_classes where id = ? and deleted_at is null limit 1',
            array($classId)
        );
        if (empty($row)) {
            $this->addError('system', 'Lớp học không tồn tại.');
            return false;
        }

        $ok = $this->db->update('db_lms_classes', array('status' => $status), 'id = ?', array($classId));
        if ($ok) {
            $this->audit(
                $status === 'paused' ? 'pause_class' : 'resume_class',
                'classes',
                $classId,
                $row,
                array('status' => $status)
            );
        }
        return $ok;
    }

    /**
     * Thêm học viên vào lớp.
     *
     * @param int $classId
     * @param int $studentId
     * @return bool
     */
    public function enrollStudent($classId, $studentId)
    {
        $this->clearErrors();
        return $this->enrollStudentInternal($classId, $studentId);
    }

    /**
     * Thêm học viên mà không xoá errors hiện tại (dùng trong transaction).
     *
     * @param int $classId
     * @param int $studentId
     * @return bool
     */
    private function enrollStudentInternal($classId, $studentId)
    {
        $exists = $this->db->safeFetchOne(
            'select id from db_lms_class_students where class_id = ? and student_id = ? limit 1',
            array((int) $classId, (int) $studentId)
        );
        if (!empty($exists)) {
            return true;
        }
        return $this->db->insert('db_lms_class_students', array(
            'class_id'   => (int) $classId,
            'student_id' => (int) $studentId,
            'status'     => 'active',
        ));
    }

    /**
     * Sinh sessions 4 tuần tới cho lớp + đảm bảo session_students.
     *
     * @param int    $classId
     * @param int    $teacherId
     * @param string $startDate 'Y-m-d'
     * @return void
     */
    private function generateSessionsForClass($classId, $teacherId, $startDate)
    {
        $today = date('Y-m-d');
        $from = strcmp($startDate, $today) < 0 ? $today : $startDate;
        $to = date('Y-m-d', strtotime('+' . self::LOOKAHEAD_WEEKS . ' weeks', strtotime($from)));

        $this->sessions->generateForClass($classId, $teacherId, $from, $to);

        $sessions = $this->db->safeFetch(
            'select id from db_lms_sessions where class_id = ? and status = ?',
            array((int) $classId, 'upcoming')
        );
        foreach ($sessions as $s) {
            $this->sessions->ensureSessionStudents((int) $s['id']);
        }
    }

    /**
     * Validate các trường chung cho create + update.
     * Trả null khi có lỗi (đã addError), ngược lại trả mảng data đã chuẩn hoá.
     *
     * @param array  $data
     * @param string $classType
     * @return array|null
     */
    private function validateClassFields(array $data, $classType)
    {
        // Thu thập toàn bộ lỗi trường (form hiện message tại từng field)
        $hasError = false;

        // Học phí 1 buổi
        $pricePerSession = 0.0;
        if (isset($data['price_per_session']) && (string) $data['price_per_session'] !== '') {
            if (!is_numeric($data['price_per_session']) || (float) $data['price_per_session'] < 0) {
                $this->addError('price_per_session', 'Học phí mỗi buổi phải là số lớn hơn hoặc bằng 0.');
                $hasError = true;
            } else {
                $pricePerSession = (float) $data['price_per_session'];
            }
        }

        // Phần trăm GV nhận: rỗng → null (kế thừa % của GV)
        $teacherPercentage = null;
        if (isset($data['teacher_percentage']) && (string) $data['teacher_percentage'] !== '') {
            if (!is_numeric($data['teacher_percentage'])
                || (float) $data['teacher_percentage'] < 0
                || (float) $data['teacher_percentage'] > 100) {
                $this->addError('teacher_percentage', 'Phần trăm giáo viên nhận phải từ 0 đến 100.');
                $hasError = true;
            } else {
                $teacherPercentage = (float) $data['teacher_percentage'];
            }
        }

        // Số buổi khoá đầu
        $totalSessions = 0;
        if (isset($data['total_sessions']) && (string) $data['total_sessions'] !== '') {
            if (!is_numeric($data['total_sessions']) || (int) $data['total_sessions'] < 0) {
                $this->addError('total_sessions', 'Số buổi khoá đầu phải là số nguyên không âm.');
                $hasError = true;
            } else {
                $totalSessions = (int) $data['total_sessions'];
            }
        }

        // Hạn giao BTVN
        $hwDeadline = '12:00';
        if (isset($data['hw_deadline_time']) && (string) $data['hw_deadline_time'] !== '') {
            $hwDeadline = trim((string) $data['hw_deadline_time']);
            if (!\Lms\UserService::isValidTime($hwDeadline)) {
                $this->addError('hw_deadline_time', 'Hạn giao BTVN sai định dạng (HH:MM).');
                $hasError = true;
                $hwDeadline = '12:00';
            }
        }

        // Hạn check BTVN
        $hwCheckHours = 24;
        if (isset($data['hw_check_hours']) && (string) $data['hw_check_hours'] !== '') {
            if (!is_numeric($data['hw_check_hours'])
                || (int) $data['hw_check_hours'] < 0
                || (int) $data['hw_check_hours'] > 720) {
                $this->addError('hw_check_hours', 'Hạn check BTVN phải từ 0 đến 720 giờ.');
                $hasError = true;
            } else {
                $hwCheckHours = (int) $data['hw_check_hours'];
            }
        }

        // Áp dụng từ ngày
        $startDate = date('Y-m-d');
        if (isset($data['start_date']) && (string) $data['start_date'] !== '') {
            $startDate = trim((string) $data['start_date']);
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate)) {
                $this->addError('start_date', 'Ngày áp dụng sai định dạng (YYYY-MM-DD).');
                $hasError = true;
                $startDate = date('Y-m-d');
            }
        }

        // Lịch cố định (cho phép mảng rỗng — giữ tương thích ngược)
        $schedules = array();
        if (isset($data['schedules'])) {
            $schedules = $this->parseClassSchedules((array) $data['schedules']);
            if ($this->hasErrors()) {
                $hasError = true;
            }
        }

        // Học viên: chỉ validate khi key có trong input (tương thích ngược)
        $studentIds = array();
        if (isset($data['student_ids'])) {
            $raw = is_array($data['student_ids']) ? $data['student_ids'] : array($data['student_ids']);
            foreach ($raw as $sid) {
                $sid = (int) $sid;
                if ($sid > 0) {
                    $studentIds[] = $sid;
                }
            }
            $studentIds = array_values(array_unique($studentIds));

            if ($studentIds !== array()) {
                $found = (int) $this->db->count(
                    'db_lms_students',
                    'user_id in (' . implode(', ', array_fill(0, count($studentIds), '?')) . ')',
                    $studentIds
                );
                if ($found !== count($studentIds)) {
                    $this->addError('student_ids', 'Một số học viên được chọn không tồn tại.');
                    $hasError = true;
                }
            }

            // Sĩ số theo loại lớp
            if ($classType === 'one_on_one') {
                if (count($studentIds) !== 1) {
                    $this->addError('student_ids', 'Lớp 1 kèm 1 phải có đúng 1 học viên.');
                    $hasError = true;
                }
            } else {
                if (count($studentIds) < 1) {
                    $this->addError('student_ids', 'Lớp nhóm phải có ít nhất 1 học viên.');
                    $hasError = true;
                }
            }
        }

        if ($hasError) {
            return null;
        }

        return array(
            'name'               => isset($data['name']) ? trim((string) $data['name']) : '',
            'price_per_session'  => $pricePerSession,
            'teacher_percentage' => $teacherPercentage,
            'total_sessions'     => $totalSessions,
            'hw_deadline_time'   => $hwDeadline,
            'hw_check_hours'     => $hwCheckHours,
            'start_date'         => $startDate,
            'schedules'          => $schedules,
            'student_ids'        => $studentIds,
        );
    }

    /**
     * Parse + validate lịch học cố định.
     * - Bỏ qua dòng rỗng (cả 3 trường trống).
     * - 2 dòng trùng (day, start, end) → giữ 1.
     * - 2 dòng cùng thứ giao nhau → lỗi (ScheduleService chỉ check chéo lớp).
     *
     * @param array $rows mảng array('day' => int, 'start' => 'HH:MM', 'end' => 'HH:MM')
     * @return array danh sách lịch hợp lệ (rỗng khi có lỗi)
     */
    private function parseClassSchedules(array $rows)
    {
        $out = array();
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $day   = isset($row['day']) ? (int) $row['day'] : 0;
            $start = isset($row['start']) ? trim((string) $row['start']) : '';
            $end   = isset($row['end']) ? trim((string) $row['end']) : '';

            if ($day === 0 && $start === '' && $end === '') {
                continue;
            }
            if (!isset(\Lms\UserService::WEEKDAYS[$day])) {
                $this->addError('schedules', 'Thứ trong lịch học không hợp lệ.');
                continue;
            }
            if ($start === '' || $end === '') {
                $this->addError('schedules', 'Mỗi lịch học phải có đủ giờ bắt đầu và kết thúc.');
                continue;
            }
            if (!\Lms\UserService::isValidTime($start) || !\Lms\UserService::isValidTime($end)) {
                $this->addError('schedules', 'Giờ học sai định dạng (HH:MM).');
                continue;
            }
            if (strcmp($start, $end) >= 0) {
                $this->addError('schedules', 'Giờ bắt đầu phải trước giờ kết thúc.');
                continue;
            }
            $out[] = array('day' => $day, 'start' => $start, 'end' => $end);
        }

        // Bỏ trùng lặp chính xác
        $clean = array();
        $seen = array();
        foreach ($out as $sch) {
            $key = $sch['day'] . '|' . $sch['start'] . '|' . $sch['end'];
            if (in_array($key, $seen, true)) {
                continue;
            }
            $seen[] = $key;
            $clean[] = $sch;
        }

        // Chống trùng khung giờ trong cùng lớp (cùng thứ, giao nhau)
        $n = count($clean);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $a = $clean[$i];
                $b = $clean[$j];
                if ((int) $a['day'] !== (int) $b['day']) {
                    continue;
                }
                if (strcmp($a['start'], $b['end']) < 0 && strcmp($b['start'], $a['end']) < 0) {
                    $this->addError('schedules', 'Hai khung giờ trùng nhau trong cùng một thứ.');
                    return array();
                }
            }
        }

        return $clean;
    }

    /**
     * @return ScheduleService
     */
    public function schedules()
    {
        return $this->schedules;
    }

    /**
     * @return SessionService
     */
    public function sessions()
    {
        return $this->sessions;
    }
}
