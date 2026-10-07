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
        return date('Y-m-d', $ts) . ' ' . $time . ':00';
    }

    /**
     * Lấy (hoặc tạo) session_students cho các học viên active của lớp.
     *
     * @param int $sessionId
     * @return int số row được đảm bảo tồn tại
     */
    public function ensureSessionStudents($sessionId)
    {
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
}
