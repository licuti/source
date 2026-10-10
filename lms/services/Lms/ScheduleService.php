<?php
/**
 * ScheduleService — lịch học cố định hàng tuần + BR-SCHED-01 (chống trùng lịch GV).
 *
 * BR-SCHED-01: một GV không thể dạy 2 lớp cùng khung giờ trong cùng ngày.
 * Check cả lịch đang hiệu lực (effective_from/to) của các lớp còn active.
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class ScheduleService extends LmsService
{
    /**
     * Thêm một lịch cố định cho lớp, kèm check trùng (BR-SCHED-01).
     *
     * @param int    $classId
     * @param int    $dayOfWeek  2..8 (2=Thứ 2 ... 8=Chủ nhật)
     * @param string $startTime  'HH:MM'
     * @param string $endTime    'HH:MM'
     * @param string $effectiveFrom 'Y-m-d'
     * @param string|null $effectiveTo
     * @return int|null schedule id hoặc null nếu trùng lịch/lỗi
     */
    public function addSchedule($classId, $dayOfWeek, $startTime, $endTime, $effectiveFrom, $effectiveTo = null)
    {
        $this->clearErrors();

        $classId     = (int) $classId;
        $dayOfWeek   = (int) $dayOfWeek;
        $startTime   = (string) $startTime;
        $endTime     = (string) $endTime;
        $effectiveFrom = (string) $effectiveFrom;

        if ($classId <= 0 || $dayOfWeek < 2 || $dayOfWeek > 8) {
            $this->addError('schedule', 'Dữ liệu lịch không hợp lệ.');
            return null;
        }
        if ($startTime === '' || $endTime === '' || strcmp($endTime, $startTime) <= 0) {
            $this->addError('schedule', 'Giờ kết thúc phải sau giờ bắt đầu.');
            return null;
        }

        $class = $this->db->safeFetchOne(
            'select id, teacher_id from db_lms_classes where id = ? and deleted_at is null limit 1',
            array($classId)
        );
        if (empty($class) || empty($class['teacher_id'])) {
            $this->addError('class', 'Lớp học không tồn tại.');
            return null;
        }
        $teacherId = (int) $class['teacher_id'];

        if ($this->hasConflict($teacherId, $dayOfWeek, $startTime, $endTime, $effectiveFrom, $effectiveTo)) {
            $this->addError('schedule', 'Giáo viên đã có lớp khác trong khung giờ này (BR-SCHED-01).');
            return null;
        }

        $ok = $this->db->insert('db_lms_schedules', array(
            'class_id'       => $classId,
            'day_of_week'    => $dayOfWeek,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'effective_from' => $effectiveFrom,
            'effective_to'   => $effectiveTo,
        ));
        return $ok ? (int) $this->db->insertId : null;
    }

    /**
     * BR-SCHED-01: check GV có bị trùng lịch không.
     * Trùng khi: cùng day_of_week và 2 khung giờ giao nhau và cả 2 lịch
     * cùng hiệu lực tại $effectiveFrom.
     *
     * @param int    $teacherId
     * @param int    $dayOfWeek
     * @param string $startTime
     * @param string $endTime
     * @param string $effectiveFrom
     * @param string|null $effectiveTo
     * @param int|null $excludeClassId bỏ qua lịch của lớp này (khi sửa)
     * @return bool
     */
    public function hasConflict($teacherId, $dayOfWeek, $startTime, $endTime, $effectiveFrom, $effectiveTo = null, $excludeClassId = null)
    {
        $teacherId   = (int) $teacherId;
        $dayOfWeek   = (int) $dayOfWeek;
        $startTime   = (string) $startTime;
        $endTime     = (string) $endTime;
        $effectiveFrom = (string) $effectiveFrom;

        $sql = 'select s.id from db_lms_schedules s'
            . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
            . ' where c.teacher_id = ? and s.day_of_week = ?'
            . ' and s.start_time < ? and s.end_time > ?'
            . ' and (s.effective_to is null or s.effective_to >= ?)'
            . ' and (s.effective_from <= ?)';

        $params = array($teacherId, $dayOfWeek, $endTime, $startTime, $effectiveFrom, $effectiveFrom);

        if ($excludeClassId !== null) {
            $sql .= ' and s.class_id != ?';
            $params[] = (int) $excludeClassId;
        }

        $row = $this->db->safeFetchOne($sql . ' limit 1', $params);
        return !empty($row);
    }

    /**
     * Liệt kê lịch hiện hành của một lớp.
     *
     * @param int $classId
     * @return array
     */
    public function listForClass($classId)
    {
        return $this->db->safeFetch(
            'select * from db_lms_schedules where class_id = ? order by day_of_week, start_time',
            array((int) $classId)
        );
    }

    // ===================== TCH-01/02 — Lịch trống GV đăng ký =====================

    /**
     * Validate khung giờ chung (dùng cho lịch lớp + lịch trống).
     *
     * @param int    $dayOfWeek 2..8
     * @param string $startTime 'HH:MM'
     * @param string $endTime   'HH:MM'
     * @param string $field     tên field để báo lỗi
     * @return bool
     */
    private function validateTimeSlot($dayOfWeek, $startTime, $endTime, $field)
    {
        $dayOfWeek = (int) $dayOfWeek;
        if (!isset(\Lms\UserService::WEEKDAYS[$dayOfWeek])) {
            $this->addError($field, 'Thứ trong tuần không hợp lệ.');
            return false;
        }
        if (!\Lms\UserService::isValidTime($startTime) || !\Lms\UserService::isValidTime($endTime)) {
            $this->addError($field, 'Giờ sai định dạng (HH:MM).');
            return false;
        }
        if (strcmp((string) $startTime, (string) $endTime) >= 0) {
            $this->addError($field, 'Giờ kết thúc phải sau giờ bắt đầu.');
            return false;
        }
        return true;
    }

    /**
     * GV đăng ký một khung giờ trống (nhận thêm lớp).
     *
     * @param int    $teacherId
     * @param int    $dayOfWeek 2..8
     * @param string $startTime 'HH:MM'
     * @param string $endTime   'HH:MM'
     * @return int|null slot id hoặc null khi lỗi
     */
    public function addFreeSlot($teacherId, $dayOfWeek, $startTime, $endTime)
    {
        $this->clearErrors();

        $teacherId  = (int) $teacherId;
        $dayOfWeek  = (int) $dayOfWeek;
        $startTime  = trim((string) $startTime);
        $endTime    = trim((string) $endTime);

        if ($teacherId <= 0) {
            $this->addError('teacher', 'Giáo viên không hợp lệ.');
            return null;
        }
        if (!$this->validateTimeSlot($dayOfWeek, $startTime, $endTime, 'slot')) {
            return null;
        }

        $exists = $this->db->safeFetchOne(
            'select user_id from db_lms_teachers where user_id = ? limit 1',
            array($teacherId)
        );
        if (empty($exists)) {
            $this->addError('teacher', 'Giáo viên không tồn tại.');
            return null;
        }

        // Chống trùng/kép slot cùng teacher + ngày (đang hiệu lực)
        $today = date('Y-m-d');
        $conflict = $this->db->safeFetchOne(
            'select id from db_lms_teacher_free_slots'
            . ' where teacher_id = ? and day_of_week = ?'
            . ' and start_time < ? and end_time > ?'
            . ' and (effective_to is null or effective_to >= ?)'
            . ' limit 1',
            array($teacherId, $dayOfWeek, $endTime, $startTime, $today)
        );
        if (!empty($conflict)) {
            $this->addError('slot', 'Bạn đã đăng ký khung giờ trùng trong ngày này rồi.');
            return null;
        }

        $ok = $this->db->insert('db_lms_teacher_free_slots', array(
            'teacher_id'     => $teacherId,
            'day_of_week'    => $dayOfWeek,
            'start_time'     => $startTime,
            'end_time'       => $endTime,
            'effective_from' => $today,
        ));
        if (!$ok) {
            $this->addError('system', 'Không thể đăng ký lịch trống. Vui lòng thử lại.');
            return null;
        }

        $id = (int) $this->db->insertId;
        $this->audit('add_free_slot', 'teacher_free_slots', $id, null, null, 'teacher');

        return $id;
    }

    /**
     * Danh sách lịch trống hiện còn hiệu lực của GV.
     *
     * @param int         $teacherId
     * @param string|null $fromDate chỉ lấy slot hiệu lực từ ngày này
     * @return array
     */
    public function listFreeSlots($teacherId, $fromDate = null)
    {
        $teacherId = (int) $teacherId;
        if ($teacherId <= 0) {
            return array();
        }
        $from = $fromDate === null ? date('Y-m-d') : (string) $fromDate;

        return $this->db->safeFetch(
            'select id, teacher_id, day_of_week, start_time, end_time, effective_from, effective_to'
            . ' from db_lms_teacher_free_slots'
            . ' where teacher_id = ?'
            . ' and effective_from <= ?'
            . ' and (effective_to is null or effective_to >= ?)'
            . ' order by day_of_week, start_time',
            array($teacherId, $from, $from)
        );
    }

    /**
     * Xoá 1 lịch trống (row-level: chỉ GV chủ mới xoá được).
     *
     * @param int $teacherId
     * @param int $slotId
     * @return bool
     */
    public function deleteFreeSlot($teacherId, $slotId)
    {
        $this->clearErrors();

        $teacherId = (int) $teacherId;
        $slotId    = (int) $slotId;
        if ($teacherId <= 0 || $slotId <= 0) {
            $this->addError('system', 'Dữ liệu không hợp lệ.');
            return false;
        }

        $row = $this->db->safeFetchOne(
            'select id, teacher_id, day_of_week, start_time, end_time'
            . ' from db_lms_teacher_free_slots where id = ? limit 1',
            array($slotId)
        );
        if (empty($row) || (int) $row['teacher_id'] !== $teacherId) {
            $this->addError('system', 'Lịch trống không tồn tại.');
            return false;
        }

        $ok = $this->db->safeExec(
            'delete from db_lms_teacher_free_slots where id = ? and teacher_id = ?',
            array($slotId, $teacherId)
        );
        if (!$ok) {
            $this->addError('system', 'Không thể xoá lịch trống. Vui lòng thử lại.');
            return false;
        }

        $this->audit('delete_free_slot', 'teacher_free_slots', $slotId, $row, null, 'teacher');

        return true;
    }

    /**
     * Lịch trống áp dụng trong tuần (cho ô xanh calendar).
     * Trả map day_of_week (2..8) => array(array('start','end'), ...).
     *
     * @param int    $teacherId
     * @param string $monday 'Y-m-d' (thứ 2)
     * @return array
     */
    public function freeSlotsForWeek($teacherId, $monday)
    {
        $teacherId = (int) $teacherId;
        $startTs   = strtotime((string) $monday);
        if ($teacherId <= 0 || $startTs === false) {
            return array();
        }

        $dates = array();
        for ($i = 0; $i < 7; $i++) {
            $ts = strtotime('+' . $i . ' days', $startTs);
            // date('N'): 1=Thứ2..7=CN → map sang 2..8
            $dates[] = array(
                'date' => date('Y-m-d', $ts),
                'dow'  => (int) date('N', $ts) + 1,
            );
        }

        $rows = $this->db->safeFetch(
            'select day_of_week, start_time, end_time, effective_from, effective_to'
            . ' from db_lms_teacher_free_slots where teacher_id = ?'
            . ' order by day_of_week, start_time',
            array($teacherId)
        );

        $map = array();
        foreach ($rows as $row) {
            $dow = (int) $row['day_of_week'];
            $from = (string) $row['effective_from'];
            $to = !empty($row['effective_to']) ? (string) $row['effective_to'] : null;
            foreach ($dates as $d) {
                if ((int) $d['dow'] !== $dow) {
                    continue;
                }
                if (strcmp($d['date'], $from) < 0) {
                    continue;
                }
                if ($to !== null && strcmp($d['date'], $to) > 0) {
                    continue;
                }
                if (!isset($map[$dow])) {
                    $map[$dow] = array();
                }
                $map[$dow][] = array(
                    'start' => substr((string) $row['start_time'], 0, 5),
                    'end'   => substr((string) $row['end_time'], 0, 5),
                );
            }
        }
        return $map;
    }
}
