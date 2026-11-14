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
        'arranging' => 'Đang sắp xếp',
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

        $subjectId = $this->resolveSubjectId($data);
        if ($subjectId === false) {
            return null;
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

        $expectedEndDate = null;
        if (!empty($fields['schedules']) && !empty($fields['start_date']) && (int) $fields['total_sessions'] > 0) {
            $span = CourseCycleService::calculateScheduleDates($fields['schedules'], $fields['start_date'], (int) $fields['total_sessions']);
            $expectedEndDate = !empty($span['end_date']) ? $span['end_date'] : null;
        }

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
                'test_grace_hours'   => $fields['test_grace_hours'],
                'status'             => isset($data['status']) && $data['status'] === 'arranging' ? 'arranging' : 'active',
                'start_date'         => $fields['start_date'],
                'expected_end_date'  => $expectedEndDate,
            ));
            if (!$ok) {
                throw new \Exception('insert db_lms_classes failed');
            }
            $classId = (int) $this->db->insertId;

            $this->syncClassTopics($classId, $fields['topic_ids']);

            // Snapshot cycle 1 after class relations exist. The migration also
            // backfills existing classes; this covers newly created classes.
            $cycleSvc = new CourseCycleService($this->db);

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

            if (!isset($data['status']) || $data['status'] !== 'arranging') {
                if ($cycleSvc->initializeClassCycle($classId, false) <= 0) {
                    throw new \Exception('initialize course cycle failed');
                }
            }

            if ($fields['schedules'] !== array()
                && (!isset($data['status']) || $data['status'] !== 'arranging')) {
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

        $subjectId = $this->resolveSubjectId($data);
        if ($subjectId === false) {
            return false;
        }

        $teacherId = (int) $data['teacher_id'];
        $teacher = $this->db->safeFetchOne(
            'select user_id, status, max_classes, service_fee_percentage from db_lms_teachers where user_id = ? limit 1',
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
        if (!isset($data['topic_ids'])) {
            $fields['topic_ids'] = isset($before['topic_ids']) ? $before['topic_ids'] : array();
        }
        if (!isset($data['test_grace_hours'])) {
            $fields['test_grace_hours'] = isset($before['test_grace_hours'])
                ? (int) $before['test_grace_hours']
                : 72;
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

        $oldPrice = isset($before['price_per_session']) && $before['price_per_session'] !== null
            ? (float) $before['price_per_session']
            : 0.0;
        $oldPct = $before['teacher_percentage'] !== null
            ? (float) $before['teacher_percentage']
            : (isset($before['service_fee_percentage']) && $before['service_fee_percentage'] !== null
                ? (float) $before['service_fee_percentage']
                : null);

        $newPrice = (float) $fields['price_per_session'];
        $newPct = $fields['teacher_percentage'] !== null
            ? (float) $fields['teacher_percentage']
            : (isset($teacher['service_fee_percentage']) && $teacher['service_fee_percentage'] !== null
                ? (float) $teacher['service_fee_percentage']
                : null);

        $hasExplicitEffectiveDate = false;
        $feeEffectiveFrom = $today;
        if (isset($data['effective_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string) $data['effective_date']))) {
            $feeEffectiveFrom = trim((string) $data['effective_date']);
            $hasExplicitEffectiveDate = true;
        } elseif (isset($data['start_date']) && trim((string) $data['start_date']) !== ''
            && trim((string) $data['start_date']) !== (string) $before['start_date']) {
            $feeEffectiveFrom = $fields['start_date'];
            $hasExplicitEffectiveDate = true;
        }
        $regenFrom = ($hasExplicitEffectiveDate && strcmp($feeEffectiveFrom, $today) > 0)
            ? $feeEffectiveFrom
            : $today;

        $this->db->beginTransaction();
        try {
            // Khoá snapshot mức phí cũ cho mọi buổi học hiện có của lớp trước khi cập nhật mức mới
            $this->db->safeExec(
                'update db_lms_sessions set price_per_session = coalesce(price_per_session, ?),'
                . ' teacher_percentage = coalesce(teacher_percentage, ?)'
                . ' where class_id = ? and (price_per_session is null or teacher_percentage is null)',
                array($oldPrice, $oldPct, $classId)
            );

            $classEndDate = null;
            if (!empty($fields['schedules']) && !empty($fields['start_date']) && (int) $fields['total_sessions'] > 0) {
                $cSpan = CourseCycleService::calculateScheduleDates($fields['schedules'], $fields['start_date'], (int) $fields['total_sessions']);
                $classEndDate = !empty($cSpan['end_date']) ? $cSpan['end_date'] : null;
            }

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
                'test_grace_hours'   => $fields['test_grace_hours'],
                'start_date'         => $fields['start_date'],
                'expected_end_date'  => $classEndDate,
            ), 'id = ?', array($classId));
            if (!$ok) {
                throw new \Exception('update db_lms_classes failed');
            }

            $this->syncClassTopics($classId, $fields['topic_ids']);

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
            $cycleSvc = new CourseCycleService($this->db);
            $activeCycleId = $cycleSvc->resolveActiveCycleId($classId);
            if ($activeCycleId > 0) {
                if ($fields['student_ids'] !== array()) {
                    $this->db->safeExec(
                        'update db_lms_cycle_students set status = ?, left_at = NOW()'
                        . ' where cycle_id = ? and student_id not in (' . implode(', ', array_fill(0, count($fields['student_ids']), '?')) . ') and status = ?',
                        array_merge(array('left', $activeCycleId), $fields['student_ids'], array('active'))
                    );
                    $this->db->safeExec(
                        'update db_lms_cycle_student_topics set status = ?'
                        . ' where cycle_id = ? and student_id not in (' . implode(', ', array_fill(0, count($fields['student_ids']), '?')) . ') and status = ?',
                        array_merge(array('locked', $activeCycleId), $fields['student_ids'], array('unlocked'))
                    );
                } else {
                    $this->db->safeExec(
                        'update db_lms_cycle_students set status = ?, left_at = NOW() where cycle_id = ? and status = ?',
                        array('left', $activeCycleId, 'active')
                    );
                    $this->db->safeExec(
                        'update db_lms_cycle_student_topics set status = ? where cycle_id = ? and status = ?',
                        array('locked', $activeCycleId, 'unlocked')
                    );
                }

                // Keep only the active cycle snapshot in sync with the class
                // editor. Completed cycles remain immutable history.
                $activeCycleRow = $this->db->safeFetchOne(
                    'select cycle_no, start_date, total_sessions, remaining_sessions from db_lms_course_cycles where id = ? limit 1',
                    array($activeCycleId)
                );
                $isFirstCycle = empty($activeCycleRow) || (int) $activeCycleRow['cycle_no'] <= 1;
                $cycStartDate = $isFirstCycle ? $fields['start_date'] : (string) $activeCycleRow['start_date'];
                $cycTotal     = $isFirstCycle ? $fields['total_sessions'] : (int) $activeCycleRow['total_sessions'];
                $cycRemaining = $isFirstCycle ? $remaining : (int) $activeCycleRow['remaining_sessions'];
                $cycEndDate   = null;
                if (!empty($fields['schedules']) && !empty($cycStartDate) && $cycTotal > 0) {
                    $cycSpan = CourseCycleService::calculateScheduleDates($fields['schedules'], $cycStartDate, $cycTotal);
                    $cycEndDate = !empty($cycSpan['end_date']) ? $cycSpan['end_date'] : null;
                }
                $this->db->update('db_lms_course_cycles', array(
                    'teacher_id' => $teacherId,
                    'price_per_session' => $fields['price_per_session'],
                    'teacher_percentage' => $fields['teacher_percentage'],
                    'total_sessions' => $cycTotal,
                    'remaining_sessions' => $cycRemaining,
                    'hw_deadline_time' => $fields['hw_deadline_time'],
                    'hw_check_hours' => $fields['hw_check_hours'],
                    'test_grace_hours' => $fields['test_grace_hours'],
                    'start_date' => $cycStartDate,
                    'expected_end_date' => $cycEndDate,
                ), 'id = ?', array($activeCycleId));

                $this->db->safeExec('delete from db_lms_cycle_schedules where cycle_id = ?', array($activeCycleId));
                foreach ($fields['schedules'] as $schedule) {
                    $this->db->insert('db_lms_cycle_schedules', array(
                        'cycle_id' => $activeCycleId,
                        'day_of_week' => (int) $schedule['day'],
                        'start_time' => $schedule['start'],
                        'end_time' => $schedule['end'],
                        'effective_from' => $fields['start_date'],
                    ));
                }

                $existingCycleTopics = array_map('intval', array_column($this->db->safeFetch(
                    'select topic_id from db_lms_cycle_topics where cycle_id = ?', array($activeCycleId)
                ), 'topic_id'));
                $newCycleTopics = array_values(array_unique(array_map('intval', $fields['topic_ids'])));
                $removedTopics = array_diff($existingCycleTopics, $newCycleTopics);
                if ($removedTopics !== array()) {
                    $this->db->safeExec(
                        'delete from db_lms_cycle_student_topics where cycle_id = ? and topic_id in ('
                        . implode(',', array_fill(0, count($removedTopics), '?')) . ')',
                        array_merge(array($activeCycleId), array_values($removedTopics))
                    );
                }
                $this->db->safeExec('delete from db_lms_cycle_topics where cycle_id = ?', array($activeCycleId));
                foreach ($newCycleTopics as $order => $topicId) {
                    $topic = $this->db->safeFetchOne(
                        'select max_sessions, has_test, has_minitest from db_lms_topics where id = ?',
                        array($topicId)
                    );
                    if (empty($topic)) {
                        throw new \RuntimeException('Invalid topic in active cycle update.');
                    }
                    $this->db->insert('db_lms_cycle_topics', array(
                        'cycle_id' => $activeCycleId,
                        'topic_id' => $topicId,
                        'order_index' => $order,
                        'max_sessions' => $topic['max_sessions'],
                        'has_test' => (int) $topic['has_test'],
                        'has_minitest' => (int) $topic['has_minitest'],
                    ));
                    foreach ($fields['student_ids'] as $studentId) {
                        $this->db->safeExec(
                            'insert ignore into db_lms_cycle_student_topics (cycle_id, student_id, topic_id, status) values (?, ?, ?, ?)',
                            array($activeCycleId, (int) $studentId, $topicId, 'locked')
                        );
                    }
                }
            }

            // Regen sessions: xoá session_students của HS không còn trong lớp +
            // xoá sessions upcoming từ mốc áp dụng, sinh lại từ lịch mới
            $this->db->safeExec(
                'delete from db_lms_session_students where session_id in'
                . ' (select id from db_lms_sessions where class_id = ? and status = ? and session_date >= ?)'
                . ($fields['student_ids'] !== array()
                    ? ' and student_id not in (' . implode(', ', array_fill(0, count($fields['student_ids']), '?')) . ')'
                    : ''),
                $fields['student_ids'] !== array()
                    ? array_merge(array($classId, 'upcoming', $regenFrom), $fields['student_ids'])
                    : array($classId, 'upcoming', $regenFrom)
            );
            $this->db->safeExec(
                'delete from db_lms_sessions where class_id = ? and status = ? and session_date >= ?'
                . ($activeCycleId > 0 ? ' and cycle_id = ?' : ''),
                $activeCycleId > 0
                    ? array($classId, 'upcoming', $regenFrom, $activeCycleId)
                    : array($classId, 'upcoming', $regenFrom)
            );

            if ($fields['schedules'] !== array()) {
                $this->generateSessionsForClass($classId, $teacherId, $fields['start_date']);
            }

            // Cập nhật mức học phí & % phí dịch vụ mới cho các buổi từ ngày áp dụng trở đi
            // (các buổi đã dạy trước ngày áp dụng hoặc đã chốt lương giữ nguyên snapshot cũ)
            if ($hasExplicitEffectiveDate) {
                $this->db->safeExec(
                    'update db_lms_sessions set price_per_session = ?, teacher_percentage = ?'
                    . ' where class_id = ? and session_date >= ?'
                    . ($activeCycleId > 0 ? ' and (cycle_id = ? or cycle_id is null)' : '')
                    . ' and not exists ('
                    . '   select 1 from db_lms_transactions tr'
                    . '   where tr.session_id = db_lms_sessions.id and tr.type = ?'
                    . ' )',
                    $activeCycleId > 0
                        ? array($newPrice, $newPct, $classId, $feeEffectiveFrom, $activeCycleId, 'salary_session')
                        : array($newPrice, $newPct, $classId, $feeEffectiveFrom, 'salary_session')
                );
            } else {
                $this->db->safeExec(
                    'update db_lms_sessions set price_per_session = ?, teacher_percentage = ?'
                    . ' where class_id = ? and status = ?'
                    . ($activeCycleId > 0 ? ' and (cycle_id = ? or cycle_id is null)' : '')
                    . ' and not exists ('
                    . '   select 1 from db_lms_transactions tr'
                    . '   where tr.session_id = db_lms_sessions.id and tr.type = ?'
                    . ' )',
                    $activeCycleId > 0
                        ? array($newPrice, $newPct, $classId, 'upcoming', $activeCycleId, 'salary_session')
                        : array($newPrice, $newPct, $classId, 'upcoming', 'salary_session')
                );
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
            . ' c.hw_deadline_time, c.hw_check_hours, c.test_grace_hours, c.status, c.start_date, c.created_at,'
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

        $topics = $this->db->safeFetch(
            'select ct.topic_id, ct.order_index, t.name from db_lms_class_topics ct'
            . ' join db_lms_topics t on t.id = ct.topic_id'
            . ' where ct.class_id = ? order by ct.order_index, t.order_index',
            array($classId)
        );
        $row['topic_ids'] = array();
        foreach ($topics as $t) {
            $row['topic_ids'][] = (int) $t['topic_id'];
        }

        // Fallback: nếu lớp chưa có cấu hình trong db_lms_class_topics nhưng đã có buổi học gắn topic
        if (empty($row['topic_ids'])) {
            $sessionTopics = $this->db->safeFetch(
                'select distinct topic_id from db_lms_sessions where class_id = ? and topic_id is not null order by topic_id asc',
                array($classId)
            );
            foreach ($sessionTopics as $st) {
                if (!empty($st['topic_id'])) {
                    $row['topic_ids'][] = (int) $st['topic_id'];
                }
            }
        }

        return $row;
    }

    /**
     * Đồng bộ danh sách chuyên đề của lớp (xoá cũ, insert mới theo order_index).
     *
     * @param int   $classId
     * @param array $topicIds
     * @return void
     */
    private function syncClassTopics($classId, array $topicIds)
    {
        $classId = (int) $classId;
        $this->db->safeExec('delete from db_lms_class_topics where class_id = ?', array($classId));

        $order = 0;
        foreach ($topicIds as $tid) {
            $tid = (int) $tid;
            if ($tid <= 0) {
                continue;
            }
            $this->db->insert('db_lms_class_topics', array(
                'class_id'    => $classId,
                'topic_id'    => $tid,
                'order_index' => $order,
            ));
            $order++;
        }
    }

    /**
     * Điều chỉnh số buổi khoá (total_sessions) — mockup "Điều chỉnh số buổi học".
     * remaining_sessions cũng thay đổi cùng delta (không âm). Tạo session mới
     * cho các buổi thêm nếu lớp đang active và có lịch cố định.
     *
     * @param int $classId
     * @param int $newTotal
     * @return bool
     */
    public function adjustSessions($classId, $newTotal)
    {
        $this->clearErrors();

        $classId = (int) $classId;
        $newTotal = (int) $newTotal;
        if ($classId <= 0) {
            $this->addError('system', 'ID không hợp lệ.');
            return false;
        }
        if ($newTotal < 0 || $newTotal > 10000) {
            $this->addError('total_sessions', 'Tổng số buổi phải từ 0 đến 10.000.');
            return false;
        }

        $before = $this->getClass($classId);
        if (empty($before)) {
            $this->addError('system', 'Lớp học không tồn tại.');
            return false;
        }
        if ($newTotal === (int) $before['total_sessions']) {
            $this->addError('total_sessions', 'Tổng số buổi không thay đổi.');
            return false;
        }

        $completedRow = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_sessions where class_id = ? and status = ?',
            array($classId, 'finished')
        );
        $completedCount = isset($completedRow['c']) ? (int) $completedRow['c'] : 0;
        if ($newTotal < $completedCount) {
            $this->addError('total_sessions', 'Tổng số buổi không thể thấp hơn số buổi đã hoàn thành (' . $completedCount . ').');
            return false;
        }

        $delta = $newTotal - (int) $before['total_sessions'];
        $remaining = max(0, (int) $before['remaining_sessions'] + $delta);

        $this->db->beginTransaction();
        try {
            $ok = $this->db->update(
                'db_lms_classes',
                array('total_sessions' => $newTotal, 'remaining_sessions' => $remaining),
                'id = ?',
                array($classId)
            );
            if (!$ok) {
                throw new \Exception('update total_sessions failed');
            }

            // Thêm buổi → sinh session mới cho các tuần tới
            if ($delta > 0 && (string) $before['status'] === 'active' && !empty($before['schedules'])) {
                $this->generateSessionsForClass($classId, (int) $before['teacher_id'], date('Y-m-d'));
            }

            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[LMS] adjustSessions failed: ' . $e->getMessage());
            $this->addError('system', 'Không thể điều chỉnh số buổi. Vui lòng thử lại.');
            return false;
        }

        $after = $this->getClass($classId);
        $this->audit('adjust_sessions', 'classes', $classId, $before, $after);

        return true;
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
            . ' c.total_sessions, c.remaining_sessions, c.start_date, c.expected_end_date, c.created_at,'
            . ' p.name as program_name, s.name as subject_name,'
            . ' t.teacher_code, t.full_name as teacher_name,'
            . ' (select count(*) from db_lms_class_students cs'
            . '   where cs.class_id = c.id and cs.status = "active") as student_count,'
            // Số buổi đã dạy xong
            . ' (select count(*) from db_lms_sessions ses'
            . '   where ses.class_id = c.id and ses.status = "finished") as sessions_finished,'
            // Chuyên đề đang học: topic của buổi finished gần nhất, nếu không có
            // thì lấy topic buổi sắp tới gần nhất
            . ' coalesce((select t2.name from db_lms_sessions ses'
            . '   join db_lms_topics t2 on t2.id = ses.topic_id'
            . '   where ses.class_id = c.id and ses.status = "finished" and ses.topic_id is not null'
            . '   order by ses.session_date desc, ses.start_time desc limit 1),'
            . ' (select t3.name from db_lms_sessions ses2'
            . '   join db_lms_topics t3 on t3.id = ses2.topic_id'
            . '   where ses2.class_id = c.id and ses2.status = "upcoming" and ses2.topic_id is not null'
            . '   order by ses2.session_date asc, ses2.start_time asc limit 1)) as current_topic'
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

        if (!in_array($status, array('arranging', 'active', 'paused'), true)) {
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

        // New arranging drafts must pass readiness checks. Legacy paused
        // classes may be resumed without changing historical behavior.
        if ($status === 'active' && (string) $row['status'] === 'arranging') {
            $ready = $this->db->safeFetchOne(
                'select c.id'
                . ' from db_lms_classes c'
                . ' where c.id = ? and c.teacher_id > 0 and c.start_date is not null'
                . ' and exists (select 1 from db_lms_class_students cs where cs.class_id = c.id and cs.status = ? and c.deleted_at is null)'
                . ' and exists (select 1 from db_lms_schedules s where s.class_id = c.id)',
                array($classId, 'active')
            );
            if (empty($ready)) {
                $this->addError('system', 'Lớp cần có giáo viên, học viên, lịch và ngày bắt đầu trước khi hoạt động.');
                return false;
            }
        }

        $ok = $this->db->update('db_lms_classes', array('status' => $status), 'id = ?', array($classId));
        if ($ok) {
            if ($status === 'active' && (string) $row['status'] === 'arranging') {
                if ((new CourseCycleService($this->db))->initializeClassCycle($classId) <= 0) {
                    $this->addError('system', 'Đã kích hoạt lớp nhưng không khởi tạo được chu kỳ đầu tiên.');
                    return false;
                }
                $class = $this->db->safeFetchOne(
                    'select teacher_id, start_date from db_lms_classes where id = ? limit 1', array($classId)
                );
                if (!empty($class)) {
                    $this->generateSessionsForClass($classId, (int) $class['teacher_id'], (string) $class['start_date']);
                }
            }
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

        // Lớp 1 kèm 1 chỉ được có đúng 1 học viên active — chặn ngay tại
        // service để mọi luồng (form/seed/script) không tạo được lớp 2 HV.
        $class = $this->db->safeFetchOne(
            'select class_type from db_lms_classes where id = ? limit 1',
            array((int) $classId)
        );
        if (empty($class)) {
            $this->addError('class_id', 'Lớp không tồn tại.');
            return false;
        }
        if ((string) $class['class_type'] === 'one_on_one') {
            $other = $this->db->safeFetchOne(
                'select id from db_lms_class_students'
                . ' where class_id = ? and status = ? and student_id <> ? limit 1',
                array((int) $classId, 'active', (int) $studentId)
            );
            if (!empty($other)) {
                $this->addError('class_id', 'Lớp 1 kèm 1 phải có đúng 1 học viên.');
                return false;
            }
        }

        $ok = $this->enrollStudentInternal($classId, $studentId);
        if ($ok) {
            (new CourseCycleService($this->db))->enrollStudentInActiveCycle($classId, $studentId);
        }
        return $ok;
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
        $classId = (int) $classId;
        $studentId = (int) $studentId;
        $exists = $this->db->safeFetchOne(
            'select id from db_lms_class_students where class_id = ? and student_id = ? limit 1',
            array((int) $classId, (int) $studentId)
        );
        if (!empty($exists)) {
            $this->db->update('db_lms_class_students', array('status' => 'active'), 'id = ?', array((int) $exists['id']));
            (new CourseCycleService($this->db))->enrollStudentInActiveCycle($classId, $studentId);
            return true;
        }
        $ok = $this->db->insert('db_lms_class_students', array(
            'class_id'   => (int) $classId,
            'student_id' => (int) $studentId,
            'status'     => 'active',
        ));
        if ($ok) {
            (new CourseCycleService($this->db))->enrollStudentInActiveCycle($classId, $studentId);
        }
        return $ok;
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
     * Giải quyết subject_id (nếu chọn __new__ thì kiểm tra/thêm mới vào db_lms_subjects).
     *
     * @param array $data
     * @return int|false ID danh mục chính hợp lệ, hoặc false nếu lỗi (đã ghi vào addError)
     */
    protected function resolveSubjectId(array &$data)
    {
        $rawSubject = isset($data['subject_id']) ? trim((string) $data['subject_id']) : '';
        if ($rawSubject === '__new__') {
            $newName = isset($data['new_subject_name']) ? trim((string) $data['new_subject_name']) : '';
            if ($newName === '') {
                $this->addError('new_subject_name', 'Vui lòng nhập tên loại chuyên đề mới.');
                return false;
            }
            if (mb_strlen($newName, 'UTF-8') > 100) {
                $this->addError('new_subject_name', 'Tên loại chuyên đề mới không được vượt quá 100 ký tự.');
                return false;
            }

            // Kiểm tra xem đã tồn tại chưa
            $exist = $this->db->safeFetchOne(
                'select id from db_lms_subjects where name = ? limit 1',
                array($newName)
            );
            if (!empty($exist['id'])) {
                $data['subject_id'] = (int) $exist['id'];
                return (int) $exist['id'];
            }

            // Tạo danh mục mới
            $programId = !empty($data['program_id']) ? (int) $data['program_id'] : 1;
            $ok = $this->db->insert('db_lms_subjects', array(
                'program_id' => $programId,
                'name'       => $newName,
                'created_at' => $this->now(),
            ));
            if (!$ok) {
                $this->addError('subject_id', 'Không thể tạo loại chuyên đề mới.');
                return false;
            }
            $newId = (int) $this->db->insertId;
            $data['subject_id'] = $newId;
            return $newId;
        }

        $sid = (int) $rawSubject;
        if ($sid <= 0) {
            $this->addError('subject_id', 'Vui lòng chọn danh mục chính.');
            return false;
        }
        $subject = $this->db->safeFetchOne('select id from db_lms_subjects where id = ? limit 1', array($sid));
        if (empty($subject)) {
            $this->addError('subject_id', 'Danh mục chính không tồn tại.');
            return false;
        }
        $data['subject_id'] = $sid;
        return $sid;
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

        // Hạn chấm test/minitest (giờ)
        $testGraceHours = 72;
        if (isset($data['test_grace_hours']) && (string) $data['test_grace_hours'] !== '') {
            if (!is_numeric($data['test_grace_hours'])
                || (int) $data['test_grace_hours'] < 0
                || (int) $data['test_grace_hours'] > 720) {
                $this->addError('test_grace_hours', 'Hạn chấm test phải từ 0 đến 720 giờ.');
                $hasError = true;
            } else {
                $testGraceHours = (int) $data['test_grace_hours'];
            }
        }

        // Chuyên đề được chọn (không ràng buộc Danh mục/Chương trình; chỉ cần tồn tại)
        $topicIds = array();
        if (isset($data['topic_ids'])) {
            $raw = is_array($data['topic_ids']) ? $data['topic_ids'] : array($data['topic_ids']);
            foreach ($raw as $tid) {
                $tid = (int) $tid;
                if ($tid > 0) {
                    $topicIds[] = $tid;
                }
            }
            $topicIds = array_values(array_unique($topicIds));
            if ($topicIds !== array()) {
                $found = (int) $this->db->count(
                    'db_lms_topics',
                    'id in (' . implode(', ', array_fill(0, count($topicIds), '?')) . ')',
                    $topicIds
                );
                if ($found !== count($topicIds)) {
                    $this->addError('topic_ids', 'Một số chuyên đề được chọn không tồn tại.');
                    $hasError = true;
                }
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

        if (isset($data['status']) && !in_array((string) $data['status'], array('active', 'arranging'), true)) {
            $this->addError('status', 'Trạng thái ban đầu của lớp không hợp lệ.');
            return null;
        }

        return array(
            'name'               => isset($data['name']) ? trim((string) $data['name']) : '',
            'price_per_session'  => $pricePerSession,
            'teacher_percentage' => $teacherPercentage,
            'total_sessions'     => $totalSessions,
            'hw_deadline_time'   => $hwDeadline,
            'hw_check_hours'     => $hwCheckHours,
            'test_grace_hours'   => $testGraceHours,
            'start_date'         => $startDate,
            'schedules'          => $schedules,
            'student_ids'        => $studentIds,
            'topic_ids'          => $topicIds,
            'status'             => isset($data['status']) ? (string) $data['status'] : 'active',
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
