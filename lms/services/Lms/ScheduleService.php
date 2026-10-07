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
}
