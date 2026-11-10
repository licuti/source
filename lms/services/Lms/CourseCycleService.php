<?php
/**
 * Course cycle snapshots and per-student topic unlocking.
 * PHP 7.3 compatible.
 */

namespace Lms;

class CourseCycleService extends LmsService
{
    public function resolveActiveCycleId($classId)
    {
        $row = $this->db->safeFetchOne(
            'select id from db_lms_course_cycles where class_id = ? and status = ?'
            . ' order by cycle_no desc limit 1',
            array((int) $classId, 'active')
        );
        return empty($row) ? 0 : (int) $row['id'];
    }

    public function getCycle($cycleId, $classId = 0)
    {
        $sql = 'select cc.*, c.class_code, c.name as class_name'
            . ' from db_lms_course_cycles cc'
            . ' join db_lms_classes c on c.id = cc.class_id and c.deleted_at is null'
            . ' where cc.id = ?';
        $params = array((int) $cycleId);
        if ((int) $classId > 0) {
            $sql .= ' and cc.class_id = ?';
            $params[] = (int) $classId;
        }
        $sql .= ' limit 1';
        $row = $this->db->safeFetchOne($sql, $params);
        return empty($row) ? null : $row;
    }

    public function listCyclesForClass($classId)
    {
        return $this->db->safeFetch(
            'select cc.*,'
            . ' (select count(*) from db_lms_sessions s where s.cycle_id = cc.id and s.status = ?) as finished_sessions'
            . ' from db_lms_course_cycles cc where cc.class_id = ?'
            . ' order by cc.cycle_no desc limit 2',
            array('finished', (int) $classId)
        );
    }

    /** Create the initial immutable snapshot after a class is created. */
    public function initializeClassCycle($classId, $manageTransaction = true)
    {
        $classId = (int) $classId;
        if ($classId <= 0) {
            return 0;
        }
        $existing = $this->db->safeFetchOne(
            'select id from db_lms_course_cycles where class_id = ? and cycle_no = 1 limit 1',
            array($classId)
        );
        if (!empty($existing)) {
            return (int) $existing['id'];
        }
        $class = $this->db->safeFetchOne(
            'select * from db_lms_classes where id = ? and deleted_at is null limit 1',
            array($classId)
        );
        if (empty($class)) {
            return 0;
        }

        if ($manageTransaction) {
            $this->db->beginTransaction();
        }
        try {
            $this->db->insert('db_lms_course_cycles', $this->cycleSnapshot($class, 1));
            $cycleId = (int) $this->db->insertId;
            $this->copyCycleRelations($cycleId, $classId);
            $this->db->safeExec('update db_lms_sessions set cycle_id = ? where class_id = ? and cycle_id is null', array($cycleId, $classId));
            $this->db->safeExec('update db_lms_tests set cycle_id = ? where class_id = ? and cycle_id is null', array($cycleId, $classId));
            $this->seedFirstTopicUnlocks($cycleId);
            if ($manageTransaction) {
                $this->db->commit();
            }
            return $cycleId;
        } catch (\Exception $e) {
            if ($manageTransaction) {
                $this->db->rollBack();
            }
            error_log('[LMS] initializeClassCycle failed: ' . $e->getMessage());
            return 0;
        }
    }

    public function enrollStudentInActiveCycle($classId, $studentId)
    {
        $cycleId = $this->resolveActiveCycleId($classId);
        if ($cycleId <= 0) {
            return false;
        }
        $exists = $this->db->safeFetchOne(
            'select id, status from db_lms_cycle_students where cycle_id = ? and student_id = ? limit 1',
            array($cycleId, (int) $studentId)
        );
        if (!empty($exists)) {
            $this->db->update('db_lms_cycle_students', array('status' => 'active', 'left_at' => null), 'id = ?', array((int) $exists['id']));
            $topicCount = (int) $this->db->count(
                'db_lms_cycle_student_topics',
                'cycle_id = ? and student_id = ?',
                array($cycleId, (int) $studentId)
            );
            if ($topicCount > 0) {
                return true;
            }
        }
        if (empty($exists)) {
            $this->db->insert('db_lms_cycle_students', array(
                'cycle_id' => $cycleId,
                'student_id' => (int) $studentId,
                'status' => 'active',
            ));
        }
        $first = $this->db->safeFetchOne(
            'select topic_id from db_lms_cycle_topics where cycle_id = ? order by order_index, id limit 1',
            array($cycleId)
        );
        if (!empty($first)) {
            $this->db->safeExec(
                'insert into db_lms_cycle_student_topics (cycle_id, student_id, topic_id, status, unlocked_at, unlock_reason)'
                . ' values (?, ?, ?, ?, ?, ?) on duplicate key update status = values(status), unlocked_at = values(unlocked_at), unlock_reason = values(unlock_reason)',
                array($cycleId, (int) $studentId, (int) $first['topic_id'], 'unlocked', $this->now(), 'initial enrollment')
            );
            $others = $this->db->safeFetch(
                'select topic_id from db_lms_cycle_topics where cycle_id = ? and topic_id <> ?',
                array($cycleId, (int) $first['topic_id'])
            );
            foreach ($others as $topic) {
                $this->db->safeExec(
                    'insert ignore into db_lms_cycle_student_topics (cycle_id, student_id, topic_id, status) values (?, ?, ?, ?)',
                    array($cycleId, (int) $studentId, (int) $topic['topic_id'], 'locked')
                );
            }
        }
        return true;
    }

    /** Create the next cycle only after the previous cycle has been closed. */
    public function createCycleFromClass($classId, array $overrides = array())
    {
        $this->clearErrors();
        $classId = (int) $classId;
        $class = $this->db->safeFetchOne(
            'select * from db_lms_classes where id = ? and deleted_at is null limit 1',
            array($classId)
        );
        if (empty($class)) {
            $this->addError('class_id', 'Lớp học không tồn tại.');
            return 0;
        }
        $currentId = $this->resolveActiveCycleId($classId);
        if ($currentId > 0) {
            $this->addError('cycle', 'Cần hoàn thành hoặc đóng khóa hiện tại trước khi tạo khóa tiếp theo.');
            return 0;
        }
        $last = $this->db->safeFetchOne(
            'select * from db_lms_course_cycles where class_id = ? order by cycle_no desc limit 1',
            array($classId)
        );
        if (empty($last)) {
            $this->initializeClassCycle($classId);
            $last = $this->db->safeFetchOne(
                'select * from db_lms_course_cycles where class_id = ? order by cycle_no desc limit 1',
                array($classId)
            );
        }
        if (empty($last) || !in_array((string) $last['status'], array('completed', 'cancelled'), true)) {
            $this->addError('cycle', 'Trạng thái khóa trước không cho phép tạo khóa mới.');
            return 0;
        }

        $data = array_merge($last, array_filter($overrides, function ($value) {
            return $value !== null && $value !== '' && $value !== array();
        }));
        $teacherId = isset($data['teacher_id']) ? (int) $data['teacher_id'] : 0;
        $teacher = $this->db->safeFetchOne(
            'select user_id, status from db_lms_teachers where user_id = ? limit 1', array($teacherId)
        );
        if (empty($teacher) || (string) $teacher['status'] !== 'active') {
            $this->addError('teacher_id', 'Giáo viên chu kỳ mới không tồn tại hoặc không hoạt động.');
            return 0;
        }
        $total = isset($data['total_sessions']) ? (int) $data['total_sessions'] : 0;
        $startDate = isset($data['start_date']) ? (string) $data['start_date'] : '';
        $start = \DateTime::createFromFormat('!Y-m-d', $startDate);
        if ($total <= 0 || !$start || $start->format('Y-m-d') !== $startDate) {
            $this->addError('cycle', 'Khóa mới cần số buổi lớn hơn 0 và ngày bắt đầu hợp lệ.');
            return 0;
        }
        $activeStudents = array_map('intval', array_column($this->db->safeFetch(
            'select student_id from db_lms_class_students where class_id = ? and status = ?',
            array($classId, 'active')
        ), 'student_id'));
        $studentIds = isset($overrides['student_ids']) && is_array($overrides['student_ids'])
            ? array_values(array_unique(array_map('intval', $overrides['student_ids'])))
            : $activeStudents;
        if ($studentIds === array() || array_diff($studentIds, $activeStudents) !== array()) {
            $this->addError('student_ids', 'Khóa mới phải có học viên đang thuộc lớp; không thể gán học viên ngoài lớp.');
            return 0;
        }
        if (isset($overrides['topic_ids']) && $overrides['topic_ids'] !== array()) {
            $topicIds = array_values(array_unique(array_map('intval', $overrides['topic_ids'])));
            $found = (int) $this->db->count(
                'db_lms_topics',
                'id in (' . implode(',', array_fill(0, count($topicIds), '?')) . ')',
                $topicIds
            );
            if ($topicIds === array() || $found !== count($topicIds)) {
                $this->addError('topic_ids', 'Một số chuyên đề của khóa mới không tồn tại.');
                return 0;
            }
        }

        $this->db->beginTransaction();
        try {
            $lockedClass = $this->db->safeFetchOne(
                'select id from db_lms_classes where id = ? and deleted_at is null for update', array($classId)
            );
            if (empty($lockedClass) || $this->resolveActiveCycleId($classId) > 0) {
                throw new \RuntimeException('Concurrent active cycle exists.');
            }
            $cycleNo = (int) $last['cycle_no'] + 1;
            $cycle = array(
                'class_id' => $classId,
                'cycle_no' => $cycleNo,
                'name' => isset($overrides['name']) ? trim((string) $overrides['name']) : 'Khóa ' . $cycleNo,
                'status' => 'active',
                'teacher_id' => $teacherId,
                'price_per_session' => isset($data['price_per_session']) ? $data['price_per_session'] : 0,
                'teacher_percentage' => isset($data['teacher_percentage']) ? $data['teacher_percentage'] : null,
                'hw_deadline_time' => isset($data['hw_deadline_time']) ? $data['hw_deadline_time'] : null,
                'hw_check_hours' => isset($data['hw_check_hours']) ? (int) $data['hw_check_hours'] : 24,
                'test_grace_hours' => isset($data['test_grace_hours']) ? (int) $data['test_grace_hours'] : 72,
                'start_date' => $startDate,
                'expected_end_date' => isset($overrides['expected_end_date']) ? $overrides['expected_end_date'] : null,
                'total_sessions' => $total,
                'remaining_sessions' => $total,
            );
            if (!$this->db->insert('db_lms_course_cycles', $cycle)) {
                throw new \RuntimeException('Could not create cycle.');
            }
            $cycleId = (int) $this->db->insertId;
            $this->copyCycleRelations($cycleId, $classId, $overrides);
            $this->seedFirstTopicUnlocks($cycleId);
            $from = strcmp($startDate, date('Y-m-d')) < 0 ? date('Y-m-d') : $startDate;
            $to = date('Y-m-d', strtotime('+4 weeks', strtotime($from)));
            $sessions = new SessionService($this->db);
            $sessions->generateForCycle($classId, $cycleId, $teacherId, $from, $to);
            $this->audit('create_course_cycle', 'course_cycles', $cycleId, $last, $cycle, 'admin');
            $this->db->commit();
            return $cycleId;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[LMS] createCycleFromClass failed: ' . $e->getMessage());
            $this->addError('cycle', 'Không thể tạo khóa học mới. Vui lòng kiểm tra dữ liệu và thử lại.');
            return 0;
        }
    }

    public function closeCycle($cycleId, $status = 'completed', $actorId = null)
    {
        $cycleId = (int) $cycleId;
        if (!in_array($status, array('completed', 'cancelled'), true)) {
            $this->addError('status', 'Trạng thái đóng khóa không hợp lệ.');
            return false;
        }
        $this->db->beginTransaction();
        try {
            $row = $this->db->safeFetchOne(
                'select * from db_lms_course_cycles where id = ? for update', array($cycleId)
            );
            if (empty($row) || (string) $row['status'] !== 'active') {
                throw new \RuntimeException('Cycle is not active.');
            }
            $this->db->update('db_lms_course_cycles', array(
                'status' => $status,
                'closed_at' => $this->now(),
                'remaining_sessions' => max(0, (int) $row['remaining_sessions']),
            ), 'id = ?', array($cycleId));
            $this->db->safeExec(
                'update db_lms_sessions set status = ? where cycle_id = ? and status = ?',
                array('cancelled', $cycleId, 'upcoming')
            );
            $this->audit('close_course_cycle', 'course_cycles', $cycleId, $row, array('status' => $status), 'admin');
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->addError('cycle', 'Không thể đóng khóa học.');
            return false;
        }
    }

    /** Consume cycle session allowance once when sessions transition to finished. */
    public function recordFinishedSessions(array $sessionIds)
    {
        $sessionIds = array_values(array_unique(array_filter(array_map('intval', $sessionIds))));
        if ($sessionIds === array()) {
            return 0;
        }
        $placeholders = implode(',', array_fill(0, count($sessionIds), '?'));
        $this->db->safeExec(
            'update db_lms_sessions s'
            . ' join db_lms_classes c on c.id = s.class_id'
            . ' left join db_lms_course_cycles cc on cc.id = s.cycle_id'
            . ' left join db_lms_teachers t on t.user_id = s.teacher_id'
            . ' set s.price_per_session = coalesce(s.price_per_session, cc.price_per_session, c.price_per_session),'
            . '     s.teacher_percentage = coalesce(s.teacher_percentage, cc.teacher_percentage, c.teacher_percentage, t.service_fee_percentage)'
            . ' where s.id in (' . $placeholders . ') and (s.price_per_session is null or s.teacher_percentage is null)',
            $sessionIds
        );
        $sessions = $this->db->safeFetch(
            'select id, cycle_id from db_lms_sessions where id in (' . $placeholders . ')'
            . ' and cycle_id is not null and status = ?',
            array_merge($sessionIds, array('finished'))
        );
        $decremented = 0;
        foreach ($sessions as $session) {
            $ok = $this->db->safeExec(
                'insert ignore into db_lms_cycle_session_completions (session_id, cycle_id) values (?, ?)',
                array((int) $session['id'], (int) $session['cycle_id'])
            );
            if ($ok && $this->db->affectedRows() === 1) {
                $this->db->safeExec(
                    'update db_lms_course_cycles set remaining_sessions = GREATEST(0, remaining_sessions - 1)'
                    . ' where id = ?',
                    array((int) $session['cycle_id'])
                );
                $this->db->safeExec(
                    'update db_lms_course_cycles set status = ?, closed_at = COALESCE(closed_at, NOW())'
                    . ' where id = ? and remaining_sessions = 0 and status = ?',
                    array('completed', (int) $session['cycle_id'], 'active')
                );
                $decremented++;
            }
        }
        return $decremented;
    }

    /**
     * Mở khóa chuyên đề kế tiếp sau khi giáo viên gửi test cho chuyên đề hiện tại.
     * Không yêu cầu đủ số buổi (khác teacherUnlockNextTopic).
     *
     * @param int $classId
     * @param int $cycleId
     * @param int $studentId
     * @param int $topicId
     * @param int $actorId
     * @return bool
     */
    public function unlockNextTopicAfterTest($classId, $cycleId, $studentId, $topicId, $actorId)
    {
        $classId = (int) $classId;
        $cycleId = (int) $cycleId;
        $studentId = (int) $studentId;
        $topicId = (int) $topicId;
        $actorId = (int) $actorId;
        if ($classId <= 0 || $cycleId <= 0 || $studentId <= 0 || $topicId <= 0 || $actorId <= 0) {
            return false;
        }
        $cycle = $this->getCycle($cycleId, $classId);
        if (empty($cycle) || (string) $cycle['status'] !== 'active') {
            return false;
        }
        $owns = $this->db->safeFetchOne(
            'select cc.id from db_lms_course_cycles cc'
            . ' join db_lms_classes c on c.id = cc.class_id and c.deleted_at is null'
            . ' where cc.id = ? and cc.class_id = ? and cc.teacher_id = ? and cc.status = ? limit 1',
            array($cycleId, $classId, $actorId, 'active')
        );
        if (empty($owns)) {
            return false;
        }
        $member = $this->db->safeFetchOne(
            'select id from db_lms_cycle_students where cycle_id = ? and student_id = ? and status = ? limit 1',
            array($cycleId, $studentId, 'active')
        );
        if (empty($member)) {
            return false;
        }

        $topics = $this->db->safeFetch(
            'select ct.topic_id, cst.status'
            . ' from db_lms_cycle_topics ct'
            . ' join db_lms_cycle_student_topics cst on cst.cycle_id = ct.cycle_id'
            . ' and cst.topic_id = ct.topic_id and cst.student_id = ?'
            . ' where ct.cycle_id = ? order by ct.order_index, ct.id',
            array($studentId, $cycleId)
        );
        $currentUnlocked = false;
        $nextLocked = null;
        foreach ($topics as $topic) {
            if ((int) $topic['topic_id'] === $topicId) {
                $currentUnlocked = (string) $topic['status'] === 'unlocked';
                continue;
            }
            if ($currentUnlocked && $nextLocked === null && (string) $topic['status'] === 'locked') {
                $nextLocked = (int) $topic['topic_id'];
                break;
            }
        }
        if (!$currentUnlocked || $nextLocked === null) {
            return false;
        }

        $this->db->beginTransaction();
        try {
            $this->db->update('db_lms_cycle_student_topics', array(
                'status' => 'unlocked',
                'unlocked_at' => $this->now(),
                'unlocked_by' => $actorId,
                'unlock_reason' => 'Test sent by teacher',
            ), 'cycle_id = ? and student_id = ? and topic_id = ?', array($cycleId, $studentId, $nextLocked));
            $this->audit('unlock_course_topic', 'cycle_student_topics', $nextLocked, null, array(
                'student_id' => $studentId,
                'cycle_id' => $cycleId,
                'status' => 'unlocked',
                'reason' => 'test_sent',
            ), 'teacher');
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function teacherUnlockNextTopic($classId, $cycleId, $studentId, $actorId)
    {
        $classId = (int) $classId;
        $cycleId = (int) $cycleId;
        $studentId = (int) $studentId;
        $actorId = (int) $actorId;
        $this->clearErrors();
        $cycle = $this->getCycle($cycleId, $classId);
        if (empty($cycle) || (string) $cycle['status'] !== 'active') {
            $this->addError('cycle', 'Khóa học không hoạt động hoặc không thuộc lớp này.');
            return false;
        }
        $ownsClass = $this->db->safeFetchOne(
            'select cc.id from db_lms_course_cycles cc'
            . ' join db_lms_classes c on c.id = cc.class_id and c.deleted_at is null'
            . ' where cc.id = ? and cc.class_id = ? and cc.teacher_id = ? and cc.status = ? limit 1',
            array($cycleId, $classId, $actorId, 'active')
        );
        if (empty($ownsClass)) {
            $this->addError('permission', 'Bạn không có quyền mở chuyên đề của lớp này.');
            return false;
        }
        $membership = $this->db->safeFetchOne(
            'select id from db_lms_cycle_students where cycle_id = ? and student_id = ? and status = ? limit 1',
            array($cycleId, $studentId, 'active')
        );
        if (empty($membership)) {
            $this->addError('permission', 'Học viên không thuộc khóa học đang hoạt động.');
            return false;
        }
        $this->db->beginTransaction();
        try {
            $topics = $this->db->safeFetch(
                'select ct.topic_id, ct.order_index, ct.max_sessions, st.status'
                . ' from db_lms_cycle_topics ct'
                . ' join db_lms_cycle_student_topics st on st.cycle_id = ct.cycle_id and st.topic_id = ct.topic_id and st.student_id = ?'
                . ' where ct.cycle_id = ? order by ct.order_index, ct.id for update',
                array($studentId, $cycleId)
            );
            $previous = null;
            $next = null;
            $lockedSeen = false;
            foreach ($topics as $topic) {
                if ((string) $topic['status'] === 'locked') {
                    $lockedSeen = true;
                    if ($next === null) {
                        $next = $topic;
                    }
                } elseif (!$lockedSeen) {
                    $previous = $topic;
                } else {
                    throw new \RuntimeException('Unlocked topic order is not sequential.');
                }
            }
            if (empty($previous) || empty($next)) {
                throw new \RuntimeException('No sequential next topic.');
            }
            $sessions = $this->db->safeFetch(
                'select s.topic_id, s.topic_ids from db_lms_sessions s'
                . ' where s.cycle_id = ? and s.session_date <= ? and s.status = ?',
                array($cycleId, date('Y-m-d'), 'finished')
            );
            $count = 0;
            foreach ($sessions as $session) {
                $ids = UserService::decodeJsonArray($session['topic_ids']);
                if (!empty($session['topic_id'])) {
                    $ids[] = (int) $session['topic_id'];
                }
                if (in_array((int) $previous['topic_id'], array_map('intval', $ids), true)) {
                    $count++;
                }
            }
            $required = $previous['max_sessions'] === null ? 1 : (int) $previous['max_sessions'];
            if ($count < $required) {
                throw new \RuntimeException('Topic session threshold not met.');
            }
            $before = $next;
            $this->db->update('db_lms_cycle_student_topics', array(
                'status' => 'unlocked',
                'unlocked_at' => $this->now(),
                'unlocked_by' => $actorId,
                'unlock_reason' => 'Teacher confirmed after session threshold',
            ), 'cycle_id = ? and student_id = ? and topic_id = ? and status = ?',
                array($cycleId, $studentId, (int) $next['topic_id'], 'locked'));
            $this->audit('unlock_course_topic', 'cycle_student_topics', (int) $next['topic_id'], $before,
                array('student_id' => $studentId, 'cycle_id' => $cycleId, 'status' => 'unlocked'), 'teacher');
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->addError('topic', 'Chưa đủ điều kiện mở chuyên đề tiếp theo hoặc chuyên đề đã được mở.');
            return false;
        }
    }

    /**
     * Dữ liệu trang chi tiết lớp nhóm (Cấp 2B): học viên, lộ trình cấp lớp, buổi học.
     *
     * @param int $classId
     * @param int $teacherId
     * @return array
     */
    public function teacherGroupDetail($classId, $teacherId)
    {
        $classId = (int) $classId;
        $teacherId = (int) $teacherId;
        if ($classId <= 0 || $teacherId <= 0) {
            return array();
        }
        $class = $this->db->safeFetchOne(
            'select c.id, c.class_code, c.name, c.class_type, c.subject_id, c.program_id,'
            . ' c.total_sessions, c.remaining_sessions, sub.name as subject_name, p.name as program_name'
            . ' from db_lms_classes c'
            . ' join db_lms_subjects sub on sub.id = c.subject_id'
            . ' join db_lms_programs p on p.id = c.program_id'
            . ' where c.id = ? and c.teacher_id = ? and c.class_type = ? and c.status = ? and c.deleted_at is null'
            . ' limit 1',
            array($classId, $teacherId, 'group', 'active')
        );
        if (empty($class)) {
            return array();
        }

        $cycleId = $this->resolveActiveCycleId($classId);
        $cycle = $cycleId > 0 ? $this->getCycle($cycleId, $classId) : array();
        $today = date('Y-m-d');

        $topics = array();
        $sessions = array();
        if ($cycleId > 0) {
            $topics = $this->db->safeFetch(
                'select ct.topic_id, ct.order_index, ct.max_sessions, ct.has_test, ct.has_minitest,'
                . ' t.name, t.subject_id, sub.name as subject_name,'
                . ' (select count(*) from db_lms_sessions s where s.cycle_id = ct.cycle_id'
                . '    and s.status = ? and s.session_date <= ?'
                . '    and (json_contains(s.topic_ids, cast(ct.topic_id as json)) or s.topic_id = ct.topic_id)) as done_sessions'
                . ' from db_lms_cycle_topics ct'
                . ' join db_lms_topics t on t.id = ct.topic_id'
                . ' left join db_lms_subjects sub on sub.id = t.subject_id'
                . ' where ct.cycle_id = ? order by ct.order_index, ct.id',
                array('finished', $today, $cycleId)
            );
            $sessions = $this->db->safeFetch(
                'select s.id, s.session_date, s.start_time, s.end_time, s.status,'
                . ' s.lesson_summary, s.hw_content, s.hw_deadline, s.hw_attached_files, s.summary_submitted_at,'
                . ' s.topic_id, s.topic_ids,'
                . ' (select count(*) from db_lms_session_students ss where ss.session_id = s.id) as student_count,'
                . ' (select count(*) from db_lms_session_students ss where ss.session_id = s.id and ss.hw_graded_at is not null) as graded_count'
                . ' from db_lms_sessions s where s.class_id = ? and s.cycle_id = ?'
                . ' order by s.session_date, s.start_time, s.id',
                array($classId, $cycleId)
            );
        }

        $students = $this->db->safeFetch(
            'select cs.student_id, st.student_code, st.full_name, st.grade, u.email'
            . ' from db_lms_class_students cs'
            . ' join db_lms_students st on st.user_id = cs.student_id'
            . ' left join db_lms_users u on u.id = cs.student_id'
            . ' where cs.class_id = ? and cs.status = ?'
            . ' order by st.full_name',
            array($classId, 'active')
        );

        return array(
            'class' => $class,
            'cycle' => $cycle,
            'topics' => $topics,
            'sessions' => $sessions,
            'students' => $students,
        );
    }

    /**
     * Giáo viên chấm điểm bài test/minitest (T8 / mockup Chi tiết Chấm Test.png).
     *
     * @param int        $testId
     * @param int        $studentId
     * @param int        $teacherId
     * @param array      $in
     * @param array|null $files
     * @return bool
     */
    public function gradeTestSubmission($testId, $studentId, $teacherId, array $in, $files = null)
    {
        $this->clearErrors();
        $testId    = (int) $testId;
        $studentId = (int) $studentId;
        $teacherId = (int) $teacherId;
        if ($testId <= 0 || $studentId <= 0 || $teacherId <= 0) {
            $this->addError('system', 'Dữ liệu không hợp lệ.');
            return false;
        }

        $test = $this->db->safeFetchOne(
            'select te.*, c.teacher_id as class_teacher_id, cc.teacher_id as cycle_teacher_id'
            . ' from db_lms_tests te'
            . ' join db_lms_classes c on c.id = te.class_id and c.deleted_at is null'
            . ' left join db_lms_course_cycles cc on cc.id = te.cycle_id'
            . ' where te.id = ? limit 1',
            array($testId)
        );
        if (empty($test)) {
            $this->addError('system', 'Bài test không tồn tại.');
            return false;
        }
        $ownerTeacherId = !empty($test['cycle_teacher_id']) ? (int) $test['cycle_teacher_id'] : (int) $test['class_teacher_id'];
        if ($ownerTeacherId !== $teacherId) {
            $this->addError('system', 'Bạn không có quyền chấm bài test này.');
            return false;
        }

        $sub = $this->db->safeFetchOne(
            'select * from db_lms_test_submissions where test_id = ? and student_id = ? limit 1',
            array($testId, $studentId)
        );

        $rawScoreX = isset($in['score_x']) ? trim((string) $in['score_x']) : '';
        $rawScoreY = isset($in['score_y_percent']) ? trim((string) $in['score_y_percent']) : '';
        $maxScore = !empty($test['max_score']) ? (float) $test['max_score'] : 20.0;

        $scoreX = null;
        $scoreY = null;

        if ($rawScoreX !== '') {
            if (strpos($rawScoreX, '/') !== false) {
                $parts = explode('/', $rawScoreX);
                $p0 = trim($parts[0]);
                if (!is_numeric($p0)) {
                    $this->addError('score_x', 'Điểm số không hợp lệ.');
                    return false;
                }
                $scoreX = (float) $p0;
                if (isset($parts[1]) && is_numeric(trim($parts[1])) && (float) trim($parts[1]) > 0) {
                    $maxScore = (float) trim($parts[1]);
                }
            } elseif (is_numeric($rawScoreX)) {
                $scoreX = (float) $rawScoreX;
            } else {
                $this->addError('score_x', 'Điểm số không hợp lệ.');
                return false;
            }
            if ($scoreX < 0 || $scoreX > $maxScore) {
                $this->addError('score_x', 'Điểm phải nằm trong khoảng 0 - ' . $maxScore . '.');
                return false;
            }
        }
        if ($rawScoreY !== '') {
            $cleanY = trim(str_replace('%', '', $rawScoreY));
            if (!is_numeric($cleanY)) {
                $this->addError('score_y_percent', 'Phần trăm không hợp lệ.');
                return false;
            }
            $scoreY = (float) $cleanY;
            if ($scoreY < 0 || $scoreY > 100) {
                $this->addError('score_y_percent', 'Phần trăm phải nằm trong khoảng 0 - 100.');
                return false;
            }
        } elseif ($scoreX !== null && $maxScore > 0) {
            $scoreY = round(($scoreX / $maxScore) * 100, 2);
        }
        if ($scoreX === null && $scoreY !== null && $maxScore > 0) {
            $scoreX = round(($scoreY / 100) * $maxScore, 2);
        }

        if ($scoreX === null || $scoreY === null) {
            $this->addError('score_x', 'Vui lòng nhập điểm số hoặc tỷ lệ phần trăm.');
            return false;
        }

        $feedbackFiles = array();
        if (!empty($sub['teacher_feedback_files'])) {
            $feedbackFiles = \Lms\UserService::decodeJsonArray($sub['teacher_feedback_files']);
        }
        if (!empty($files) && is_array($files)) {
            $res = lms_save_files($files, 'test/feedback/' . date('Ym'), array('maxFiles' => 5));
            if (!empty($res['paths'])) {
                $feedbackFiles = array_merge($feedbackFiles, $res['paths']);
            }
        }

        $now = date('Y-m-d H:i:s');
        if (!empty($sub)) {
            $ok = $this->db->update('db_lms_test_submissions', array(
                'score_x' => $scoreX,
                'score_y_percent' => $scoreY,
                'graded_at' => null,
                'teacher_feedback_files' => json_encode($feedbackFiles, JSON_UNESCAPED_UNICODE),
            ), 'id = ?', array((int) $sub['id']));
        } else {
            $ok = $this->db->insert('db_lms_test_submissions', array(
                'test_id' => $testId,
                'student_id' => $studentId,
                'submission_files' => json_encode(array()),
                'submission_status' => 'submitted_on_time',
                'submitted_at' => $now,
                'score_x' => $scoreX,
                'score_y_percent' => $scoreY,
                'graded_at' => null,
                'teacher_feedback_files' => json_encode($feedbackFiles, JSON_UNESCAPED_UNICODE),
            ));
        }

        return (bool) $ok;
    }

    public function teacherCourseDetail($classId, $studentId, $actorId, $cycleId = 0)
    {
        $classId = (int) $classId;
        $studentId = (int) $studentId;
        $actorId = (int) $actorId;
        $cycleId = (int) $cycleId > 0 ? (int) $cycleId : $this->resolveActiveCycleId($classId);
        $class = $this->db->safeFetchOne(
            'select c.id, c.name, c.class_code, c.subject_id, cc.teacher_id, cc.cycle_no, sub.name as subject_name,'
            . ' t.full_name as teacher_name from db_lms_classes c'
            . ' join db_lms_course_cycles cc on cc.class_id = c.id and cc.id = ?'
            . ' left join db_lms_subjects sub on sub.id = c.subject_id'
            . ' left join db_lms_teachers t on t.user_id = cc.teacher_id'
            . ' where c.id = ? and cc.teacher_id = ? and c.deleted_at is null limit 1',
            array($cycleId, $classId, $actorId)
        );
        if (empty($class) || $cycleId <= 0) {
            return array();
        }
        $student = $this->db->safeFetchOne(
            'select s.user_id, s.student_code, s.full_name, s.grade, s.status'
            . ' from db_lms_cycle_students cs join db_lms_students s on s.user_id = cs.student_id'
            . ' where cs.cycle_id = ? and cs.student_id = ? and cs.status = ? limit 1',
            array($cycleId, $studentId, 'active')
        );
        if (empty($student)) {
            return array();
        }
        $cycle = $this->getCycle($cycleId, $classId);
        $topics = $this->db->safeFetch(
            'select ct.topic_id, ct.order_index, ct.max_sessions, ct.has_test, ct.has_minitest,'
            . ' t.name, t.content, t.test_file_url, t.minitest_file_url, t.subject_id, sub.name as subject_name, cst.status as unlock_status, cst.unlocked_at,'
            . ' (select count(*) from db_lms_sessions s where s.cycle_id = ct.cycle_id'
            . '    and s.status = ? and s.session_date <= ?'
            . '    and (json_contains(s.topic_ids, cast(ct.topic_id as json)) or s.topic_id = ct.topic_id)) as done_sessions'
            . ' from db_lms_cycle_topics ct join db_lms_topics t on t.id = ct.topic_id'
            . ' left join db_lms_subjects sub on sub.id = t.subject_id'
            . ' join db_lms_cycle_student_topics cst on cst.cycle_id = ct.cycle_id'
            . ' and cst.topic_id = ct.topic_id and cst.student_id = ?'
            . ' where ct.cycle_id = ? order by ct.order_index, ct.id',
            array('finished', date('Y-m-d'), $studentId, $cycleId)
        );
        $tests = $this->db->safeFetch(
            'select te.id, te.topic_id, te.test_type, te.title, te.test_file_url, te.deadline, te.status, te.max_score,'
            . ' ts.id as submission_id, ts.submission_files, ts.submitted_at, ts.submission_status, ts.score_x, ts.score_y_percent, ts.graded_at, ts.teacher_feedback_files,'
            . ' t.test_file_url as topic_test_file_url, t.minitest_file_url as topic_minitest_file_url'
            . ' from db_lms_tests te'
            . ' join db_lms_topics t on t.id = te.topic_id'
            . ' left join db_lms_test_submissions ts'
            . ' on ts.test_id = te.id and ts.student_id = ? where te.cycle_id = ?'
            . ' order by te.topic_id, te.test_type, te.deadline, te.id',
            array($studentId, $cycleId)
        );
        foreach ($tests as $i => $tRow) {
            if (empty($tRow['test_file_url'])) {
                $tests[$i]['test_file_url'] = (string) $tRow['test_type'] === 'minitest'
                    ? (isset($tRow['topic_minitest_file_url']) ? $tRow['topic_minitest_file_url'] : '')
                    : (isset($tRow['topic_test_file_url']) ? $tRow['topic_test_file_url'] : '');
            }
            $tests[$i]['submission_files'] = \Lms\UserService::decodeJsonArray(
                isset($tRow['submission_files']) ? $tRow['submission_files'] : array()
            );
            $tests[$i]['teacher_feedback_files'] = \Lms\UserService::decodeJsonArray(
                isset($tRow['teacher_feedback_files']) ? $tRow['teacher_feedback_files'] : array()
            );
        }
        $sessions = $this->db->safeFetch(
            'select s.id, s.session_date, s.start_time, s.end_time, s.status,'
            . ' s.lesson_summary, s.hw_content, s.hw_deadline, s.hw_attached_files, s.summary_submitted_at,'
            . ' s.topic_ids, s.topic_id,'
            . ' (select count(*) from db_lms_session_students ss where ss.session_id = s.id) as student_count,'
            . ' (select count(*) from db_lms_session_students ss where ss.session_id = s.id and ss.hw_graded_at is not null) as graded_count'
            . ' from db_lms_sessions s'
            . ' where s.class_id = ? and s.cycle_id = ?'
            . ' order by s.session_date, s.start_time, s.id',
            array($classId, $cycleId)
        );
        $schedules = $this->db->safeFetch(
            'select day_of_week, time_format(start_time, \'%H:%i\') as start_time, time_format(end_time, \'%H:%i\') as end_time'
            . ' from db_lms_cycle_schedules where cycle_id = ? and (effective_to is null or effective_to >= ?) order by day_of_week, start_time, id',
            array($cycleId, date('Y-m-d'))
        );
        return array('class' => $class, 'student' => $student, 'cycle' => $cycle, 'topics' => $topics, 'tests' => $tests, 'sessions' => $sessions, 'schedules' => $schedules);
    }

    private function cycleSnapshot(array $class, $cycleNo)
    {
        return array(
            'class_id' => (int) $class['id'],
            'cycle_no' => (int) $cycleNo,
            'name' => 'Khóa ' . (int) $cycleNo,
            'status' => in_array($class['status'], array('finished', 'cancelled'), true) ? $class['status'] : 'active',
            'teacher_id' => (int) $class['teacher_id'],
            'price_per_session' => $class['price_per_session'],
            'teacher_percentage' => $class['teacher_percentage'],
            'hw_deadline_time' => $class['hw_deadline_time'],
            'hw_check_hours' => (int) $class['hw_check_hours'],
            'test_grace_hours' => (int) $class['test_grace_hours'],
            'start_date' => $class['start_date'],
            'expected_end_date' => $class['expected_end_date'],
            'total_sessions' => (int) $class['total_sessions'],
            'remaining_sessions' => (int) $class['remaining_sessions'],
        );
    }

    private function copyCycleRelations($cycleId, $classId, array $overrides = array())
    {
        $studentIds = !empty($overrides['student_ids']) && is_array($overrides['student_ids'])
            ? array_map('intval', $overrides['student_ids'])
            : array_map('intval', array_column($this->db->safeFetch(
                'select student_id from db_lms_class_students where class_id = ? and status = ?',
                array($classId, 'active')
            ), 'student_id'));
        foreach (array_unique($studentIds) as $studentId) {
            $this->db->insert('db_lms_cycle_students', array(
                'cycle_id' => (int) $cycleId, 'student_id' => (int) $studentId, 'status' => 'active',
            ));
        }
        if (!empty($overrides['schedules']) && is_array($overrides['schedules'])) {
            $schedules = array();
            foreach ($overrides['schedules'] as $schedule) {
                $day = isset($schedule['day']) ? (int) $schedule['day'] : (isset($schedule['day_of_week']) ? (int) $schedule['day_of_week'] : 0);
                $start = isset($schedule['start']) ? (string) $schedule['start'] : (isset($schedule['start_time']) ? (string) $schedule['start_time'] : '');
                $end = isset($schedule['end']) ? (string) $schedule['end'] : (isset($schedule['end_time']) ? (string) $schedule['end_time'] : '');
                if (!isset(UserService::WEEKDAYS[$day]) || !UserService::isValidTime($start) || !UserService::isValidTime($end) || strcmp($start, $end) >= 0) {
                    throw new \InvalidArgumentException('Invalid cycle schedule override.');
                }
                $schedules[] = array(
                    'day_of_week' => $day, 'start_time' => $start, 'end_time' => $end,
                    'effective_from' => isset($overrides['start_date']) ? $overrides['start_date'] : date('Y-m-d'),
                    'effective_to' => null,
                );
            }
        } else {
            $schedules = $this->db->safeFetch(
                'select day_of_week, start_time, end_time, effective_from, effective_to from db_lms_schedules where class_id = ?',
                array($classId)
            );
        }
        foreach ($schedules as $schedule) {
            $this->db->insert('db_lms_cycle_schedules', array(
                'cycle_id' => (int) $cycleId,
                'day_of_week' => (int) $schedule['day_of_week'],
                'start_time' => $schedule['start_time'],
                'end_time' => $schedule['end_time'],
                'effective_from' => isset($schedule['effective_from'])
                    ? $schedule['effective_from']
                    : (isset($overrides['start_date']) ? $overrides['start_date'] : date('Y-m-d')),
                'effective_to' => isset($schedule['effective_to']) ? $schedule['effective_to'] : null,
            ));
        }
        $topics = !empty($overrides['topic_ids']) && is_array($overrides['topic_ids'])
            ? $this->db->safeFetch(
                'select id as topic_id, order_index, max_sessions, has_test, has_minitest from db_lms_topics where id in ('
                . implode(',', array_fill(0, count($overrides['topic_ids']), '?')) . ')',
                array_map('intval', $overrides['topic_ids'])
            )
            : $this->db->safeFetch(
                'select t.id as topic_id, ct.order_index, t.max_sessions, t.has_test, t.has_minitest'
                . ' from db_lms_class_topics ct'
                . ' join db_lms_topics t on t.id = ct.topic_id'
                . ' where ct.class_id = ?'
                . ' order by ct.order_index, t.id',
                array($classId)
            );
        foreach ($topics as $index => $topic) {
            $this->db->insert('db_lms_cycle_topics', array(
                'cycle_id' => (int) $cycleId,
                'topic_id' => (int) $topic['topic_id'],
                'order_index' => !empty($overrides['topic_ids']) ? $index : (int) $topic['order_index'],
                'max_sessions' => $topic['max_sessions'],
                'has_test' => (int) $topic['has_test'],
                'has_minitest' => (int) $topic['has_minitest'],
            ));
        }
    }

    private function seedFirstTopicUnlocks($cycleId)
    {
        $first = $this->db->safeFetchOne(
            'select topic_id from db_lms_cycle_topics where cycle_id = ? order by order_index, id limit 1',
            array((int) $cycleId)
        );
        $students = $this->db->safeFetch(
            'select student_id from db_lms_cycle_students where cycle_id = ? and status = ?',
            array((int) $cycleId, 'active')
        );
        if (empty($first)) {
            return;
        }
        foreach ($students as $student) {
            $this->db->insert('db_lms_cycle_student_topics', array(
                'cycle_id' => (int) $cycleId,
                'student_id' => (int) $student['student_id'],
                'topic_id' => (int) $first['topic_id'],
                'status' => 'unlocked',
                'unlocked_at' => $this->now(),
                'unlock_reason' => 'initial enrollment',
            ));
            $topics = $this->db->safeFetch(
                'select topic_id from db_lms_cycle_topics where cycle_id = ? and topic_id <> ?',
                array((int) $cycleId, (int) $first['topic_id'])
            );
            foreach ($topics as $topic) {
                $this->db->insert('db_lms_cycle_student_topics', array(
                    'cycle_id' => (int) $cycleId,
                    'student_id' => (int) $student['student_id'],
                    'topic_id' => (int) $topic['topic_id'],
                    'status' => 'locked',
                ));
            }
        }
    }
}
