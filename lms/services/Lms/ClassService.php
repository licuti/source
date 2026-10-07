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
     *                     hw_deadline_time, hw_check_hours, start_date
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
        if (!in_array($classType, array('one_on_one', 'group'), true)) {
            $this->addError('class_type', 'Loại lớp không hợp lệ.');
            return null;
        }

        $code = $this->generateClassCode($classType);
        $totalSessions = isset($data['total_sessions']) ? (int) $data['total_sessions'] : 0;

        $ok = $this->db->insert('db_lms_classes', array(
            'class_code'         => $code,
            'program_id'         => (int) $data['program_id'],
            'subject_id'         => (int) $data['subject_id'],
            'teacher_id'         => (int) $data['teacher_id'],
            'class_type'         => $classType,
            'name'               => isset($data['name']) ? (string) $data['name'] : '',
            'price_per_session'  => isset($data['price_per_session']) ? (float) $data['price_per_session'] : 0,
            'teacher_percentage' => isset($data['teacher_percentage']) ? (float) $data['teacher_percentage'] : null,
            'total_sessions'     => $totalSessions,
            'remaining_sessions' => $totalSessions,
            'hw_deadline_time'   => isset($data['hw_deadline_time']) ? (string) $data['hw_deadline_time'] : '12:00',
            'hw_check_hours'     => isset($data['hw_check_hours']) ? (int) $data['hw_check_hours'] : 24,
            'status'             => 'active',
            'start_date'         => isset($data['start_date']) ? (string) $data['start_date'] : date('Y-m-d'),
        ));
        if (!$ok) {
            $this->addError('system', 'Không thể tạo lớp.');
            return null;
        }

        $classId = (int) $this->db->insertId;
        $this->audit('create_class', 'classes', $classId, null, $data);

        return $classId;
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
            } catch (Exception $e) {
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
     * Thêm học viên vào lớp.
     *
     * @param int $classId
     * @param int $studentId
     * @return bool
     */
    public function enrollStudent($classId, $studentId)
    {
        $this->clearErrors();
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
