<?php
/**
 * TickService — phạt vi phạm (tick) của giáo viên (Phase 2).
 *
 * Implement SRS §3.1 + ADM-06:
 *  - closePastSessions():     đóng session quá hạn (tiền đề BR-TICK-01).
 *  - scanLateLessonSummary(): BR-TICK-01 — giao lesson summary trễ.
 *  - scanLateHwGrading():     BR-TICK-02 — chấm BTVN trễ (1 tick/session).
 *  - evaluateTickPenalty():   BR-TICK-03 — tick_thực_tế → warn/demote fee.
 *  - addManualTick()/listViolations()/saveThreshold(): admin ADM-06.
 *
 * Idempotency (R1): dựa vào cờ is_summary_late/is_graded_late + UNIQUE
 * uniq_tick(teacher_id, session_id, reason, violation_date). violation_date
 * là ngày hạn chót (deterministic) nên cron chạy lại không tạo tick trùng.
 *
 * Chỉ làm tick BTVN (late_lesson_summary + late_hw_grading). late_test_grading
 * bỏ qua vì TCH-04 (nộp/chấm test) chưa có → không có data.
 *
 * Timezone: code dùng date('Y-m-d H:i:s') theo PHP default. Cần
 * date.timezone = Asia/Ho_Chi_Minh trong php.ini thì BR-TICK mới đúng giờ VN.
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class TickService extends LmsService
{
    const REASON_LESSON_SUMMARY = 'late_lesson_summary';
    const REASON_HW_GRADING     = 'late_hw_grading';
    const REASON_TEST_GRADING   = 'late_test_grading';
    const REASON_MANUAL_PENALTY = 'manual_penalty';
    const REASON_MANUAL_EXEMPT  = 'manual_exempt';

    /** Lý do do cron sinh (auto_generated = 1). */
    const AUTO_REASONS = array(
        self::REASON_LESSON_SUMMARY => 'Giao bài (lesson summary) trễ',
        self::REASON_HW_GRADING     => 'Chấm BTVN trễ',
        self::REASON_TEST_GRADING   => 'Chấm test trễ',
    );

    /** Lý do admin tạo tay (auto_generated = 0). */
    const MANUAL_REASONS = array(
        self::REASON_MANUAL_PENALTY => 'Phạt thủ công (+1)',
        self::REASON_MANUAL_EXEMPT  => 'Miễn giảm thủ công (-1)',
    );

    const ALL_REASONS = self::AUTO_REASONS + self::MANUAL_REASONS;

    /** Hạn giao bài mặc định khi classes.hw_deadline_time IS NULL (12:00 trưa). */
    const DEFAULT_HW_DEADLINE_TIME = '12:00:00';

    // ===================== Cron: đóng session quá hạn =====================

    /**
     * Đóng các buổi đã qua nhưng vẫn còn 'upcoming' (tiền đề BR-TICK-01).
     *
     * Chỉ update status='finished' cho session 'upcoming' thuộc quá khứ
     * (qua ngày, hoặc cùng ngày nhưng đã hết end_time). Bỏ qua các trạng thái
     * khác (cancelled, absent_*, đã finished).
     *
     * @param bool $dryRun
     * @return array array('dry_run' => bool, 'closed' => int)
     */
    public function closePastSessions($dryRun = false)
    {
        $stats = array('dry_run' => (bool) $dryRun, 'closed' => 0);

        $where = 'status = ? and (session_date < CURDATE()'
            . ' or (session_date = CURDATE() and end_time is not null and end_time < CURTIME()))';

        $row = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_sessions where ' . $where,
            array('upcoming')
        );
        $stats['closed'] = isset($row['c']) ? (int) $row['c'] : 0;

        if ($dryRun || $stats['closed'] === 0) {
            return $stats;
        }

        $sessions = $this->db->safeFetch(
            'select id from db_lms_sessions where ' . $where,
            array('upcoming')
        );
        $ok = $this->db->update('db_lms_sessions', array('status' => 'finished'), $where, array('upcoming'));
        if ($ok) {
            (new CourseCycleService($this->db))->recordFinishedSessions(array_column($sessions, 'id'));
            $this->audit('close_past_sessions', 'sessions', null, null, $stats, 'system_cron');
        }
        return $stats;
    }

    // ===================== BR-TICK-01: giao bài trễ =====================

    /**
     * Quét session finished thiếu lesson summary sau hạn
     * (session_date + 1 ngày + classes.hw_deadline_time).
     *
     * @param bool $dryRun
     * @return array array('dry_run', 'candidates', 'ticked', 'errors')
     */
    public function scanLateLessonSummary($dryRun = false)
    {
        $stats = array(
            'dry_run'    => (bool) $dryRun,
            'candidates' => 0,
            'ticked'     => 0,
            'errors'     => 0,
        );

        $rows = $this->db->safeFetch(
            'select s.id, s.class_id, s.teacher_id, s.session_date,'
            . ' DATE(s.session_date + INTERVAL 1 DAY) as violation_date'
            . ' from db_lms_sessions s'
            . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
            . ' where s.status = ? and s.summary_submitted_at is null'
            . ' and s.is_summary_late = 0'
            . ' and ADDTIME(s.session_date + INTERVAL 1 DAY, IFNULL(c.hw_deadline_time, ?)) < NOW()',
            array('finished', self::DEFAULT_HW_DEADLINE_TIME)
        );

        $stats['candidates'] = count($rows);
        if ($dryRun || empty($rows)) {
            return $stats;
        }

        foreach ($rows as $row) {
            $sessionId = (int) $row['id'];
            $teacherId = (int) $row['teacher_id'];
            $violationDate = (string) $row['violation_date'];

            $this->db->beginTransaction();
            try {
                // Idempotency: đã có tick này (deterministic theo uniq_tick)
                $exists = $this->db->safeFetchOne(
                    'select id from db_lms_violations'
                    . ' where teacher_id = ? and session_id = ? and reason = ? and violation_date = ? limit 1',
                    array($teacherId, $sessionId, self::REASON_LESSON_SUMMARY, $violationDate)
                );
                if (!empty($exists)) {
                    // Tick đã có nhưng cờ chưa được set → set cờ để không quét lại.
                    $this->db->update('db_lms_sessions', array('is_summary_late' => 1), 'id = ?', array($sessionId));
                    $this->db->commit();
                    continue;
                }

                $ok = $this->db->update('db_lms_sessions', array('is_summary_late' => 1), 'id = ?', array($sessionId));
                if (!$ok) {
                    throw new \RuntimeException('Không cập nhật được is_summary_late.');
                }

                $ok = $this->db->insert('db_lms_violations', array(
                    'teacher_id'     => $teacherId,
                    'class_id'       => (int) $row['class_id'],
                    'session_id'     => $sessionId,
                    'tick_amount'    => 1,
                    'reason'         => self::REASON_LESSON_SUMMARY,
                    'note'           => null,
                    'auto_generated' => 1,
                    'violation_date' => $violationDate,
                    'created_by'     => null,
                ));
                if (!$ok) {
                    throw new \RuntimeException('Không insert được violation.');
                }

                $violationId = (int) $this->db->insertId;
                $this->audit(
                    'tick_late_lesson_summary',
                    'violations',
                    $violationId,
                    null,
                    array('session_id' => $sessionId, 'teacher_id' => $teacherId, 'violation_date' => $violationDate),
                    'system_cron'
                );

                $this->db->commit();
                $stats['ticked']++;
            } catch (\Exception $e) {
                $this->db->rollBack();
                $stats['errors']++;
            }
        }

        return $stats;
    }

    // ===================== BR-TICK-02: chấm BTVN trễ =====================

    /**
     * Quét BTVN đã nộp nhưng GV chưa chấm sau classes.hw_check_hours giờ.
     *
     * Quyết định (plan): 1 tick/session — nhiều học viên cùng 1 buổi chỉ tạo
     * 1 tick (uniq_tick có session_id).
     *
     * @param bool $dryRun
     * @return array array('dry_run', 'candidates', 'ticked', 'errors')
     */
    public function scanLateHwGrading($dryRun = false)
    {
        $stats = array(
            'dry_run'    => (bool) $dryRun,
            'candidates' => 0,
            'ticked'     => 0,
            'errors'     => 0,
        );

        // Group theo session: buổi nào có ít nhất 1 HV nộp trễ hạn chấm → 1 tick.
        $rows = $this->db->safeFetch(
            'select ses.id as session_id, ses.class_id, ses.teacher_id, ses.session_date,'
            . ' min(ss.hw_submitted_at) as first_submitted_at,'
            . ' c.hw_check_hours'
            . ' from db_lms_session_students ss'
            . ' join db_lms_sessions ses on ses.id = ss.session_id'
            . ' join db_lms_classes c on c.id = ses.class_id and c.deleted_at is null'
            . ' where ss.status = ? and ss.hw_graded_at is null and ss.is_graded_late = 0'
            . ' and DATE_ADD(ss.hw_submitted_at, INTERVAL c.hw_check_hours HOUR) < NOW()'
            . ' group by ses.id, ses.class_id, ses.teacher_id, ses.session_date, c.hw_check_hours',
            array('submitted')
        );

        $stats['candidates'] = count($rows);
        if ($dryRun || empty($rows)) {
            return $stats;
        }

        foreach ($rows as $row) {
            $sessionId = (int) $row['session_id'];
            $teacherId = (int) $row['teacher_id'];
            $classId   = (int) $row['class_id'];
            $checkHours = (int) $row['hw_check_hours'];

            // Ngày quá hạn chấm (deterministic theo session → idempotent).
            $dueTs = strtotime((string) $row['first_submitted_at'] . ' +' . max(0, $checkHours) . ' hours');
            if ($dueTs === false) {
                $stats['errors']++;
                continue;
            }
            $violationDate = date('Y-m-d', $dueTs);

            $this->db->beginTransaction();
            try {
                $exists = $this->db->safeFetchOne(
                    'select id from db_lms_violations'
                    . ' where teacher_id = ? and session_id = ? and reason = ? and violation_date = ? limit 1',
                    array($teacherId, $sessionId, self::REASON_HW_GRADING, $violationDate)
                );
                if (!empty($exists)) {
                    $this->db->update(
                        'db_lms_session_students',
                        array('is_graded_late' => 1),
                        'session_id = ? and status = ? and hw_graded_at is null',
                        array($sessionId, 'submitted')
                    );
                    $this->db->commit();
                    continue;
                }

                // Đánh dấu trễ cho mọi HV nộp nhưng chưa chấm của buổi này.
                $ok = $this->db->update(
                    'db_lms_session_students',
                    array('is_graded_late' => 1),
                    'session_id = ? and status = ? and hw_graded_at is null',
                    array($sessionId, 'submitted')
                );
                if (!$ok) {
                    throw new \RuntimeException('Không cập nhật được is_graded_late.');
                }

                $ok = $this->db->insert('db_lms_violations', array(
                    'teacher_id'     => $teacherId,
                    'class_id'       => $classId,
                    'session_id'     => $sessionId,
                    'tick_amount'    => 1,
                    'reason'         => self::REASON_HW_GRADING,
                    'note'           => null,
                    'auto_generated' => 1,
                    'violation_date' => $violationDate,
                    'created_by'     => null,
                ));
                if (!$ok) {
                    throw new \RuntimeException('Không insert được violation.');
                }

                $violationId = (int) $this->db->insertId;
                $this->audit(
                    'tick_late_hw_grading',
                    'violations',
                    $violationId,
                    null,
                    array('session_id' => $sessionId, 'teacher_id' => $teacherId, 'violation_date' => $violationDate),
                    'system_cron'
                );

                $this->db->commit();
                $stats['ticked']++;
            } catch (\Exception $e) {
                $this->db->rollBack();
                $stats['errors']++;
            }
        }

        return $stats;
    }

    // ===================== BR-TICK-03: đánh giá tick → warn/demote =====================

    /**
     * Với mỗi GV đang hoạt động: đếm tick thực tế, cảnh báo / ghi nhận hạ phí.
     *
     * tick_thực_tế = SUM(violations.tick_amount) - ticks_exempted (clamp >= 0).
     * - >= ticks_to_warn        → notification 'tick_warning' (idempotent: 1 lần/tháng).
     * - >= ticks_to_demote_fee  → tháng sau áp dụng fee_percentage_after_demote
     *   (chỉ audit + cảnh báo; PayrollService áp dụng thực tế ở phase sau).
     *
     * @param bool $dryRun
     * @return array array('dry_run', 'evaluated', 'warned', 'demote_reached')
     */
    public function evaluateTickPenalty($dryRun = false)
    {
        $stats = array(
            'dry_run'       => (bool) $dryRun,
            'evaluated'     => 0,
            'warned'        => 0,
            'demote_reached' => 0,
        );

        $teachers = $this->db->safeFetch(
            'select user_id, teacher_code, full_name from db_lms_teachers where status = ? order by user_id',
            array('active')
        );
        if (empty($teachers)) {
            return $stats;
        }

        $notifSvc = new NotificationService($this->db);

        foreach ($teachers as $t) {
            $teacherId = (int) $t['user_id'];
            $stats['evaluated']++;

            $threshold = $this->findThreshold($this->countActiveClasses($teacherId));
            if (empty($threshold) || !is_array($threshold)) {
                continue;
            }

            $realTicks = $this->teacherRealTicks($teacherId, (int) $threshold['ticks_exempted']);

            if ($realTicks < (int) $threshold['ticks_to_warn']) {
                continue;
            }

            $isDemote = $realTicks >= (int) $threshold['ticks_to_demote_fee'];

            if ($dryRun) {
                $stats['warned']++;
                if ($isDemote) {
                    $stats['demote_reached']++;
                }
                continue;
            }

            // Idempotent: chỉ gửi 1 thông báo tick_warning / GV / tháng.
            if ($this->hasTickWarningThisMonth($teacherId)) {
                continue;
            }

            $title   = 'Cảnh báo vi phạm: ' . $realTicks . ' tick thực tế';
            $message = 'Bạn hiện có ' . $realTicks . ' tick vi phạm thực tế'
                . ' (đã miễn ' . (int) $threshold['ticks_exempted'] . ' tick theo số lớp đang dạy).'
                . ' Ngưỡng cảnh báo: ' . (int) $threshold['ticks_to_warn'] . ' tick.';
            if ($isDemote) {
                $message .= ' Bạn đã đạt ngưỡng hạ phí dịch vụ ('
                    . (int) $threshold['ticks_to_demote_fee'] . ' tick): tháng kế tiếp % phí dịch vụ'
                    . ' sẽ áp dụng mức ' . $threshold['fee_percentage_after_demote'] . '%'
                    . ' thay vì mức mặc định.';
            }

            $notifSvc->notify($teacherId, 'tick_warning', $title, $message);
            $stats['warned']++;

            if ($isDemote) {
                $stats['demote_reached']++;
                $this->audit(
                    'tick_demote_fee_reached',
                    'teachers',
                    $teacherId,
                    null,
                    array(
                        'tick_thuc_te'            => $realTicks,
                        'ticks_to_demote_fee'     => (int) $threshold['ticks_to_demote_fee'],
                        'fee_percentage_after_demote' => $threshold['fee_percentage_after_demote'],
                    ),
                    'system_cron'
                );
            }
        }

        return $stats;
    }

    /**
     * Chạy toàn bộ pipeline cron (theo thứ tự BR).
     *
     * @param bool $dryRun
     * @return array stats của từng bước
     */
    public function runAll($dryRun = false)
    {
        return array(
            'close_past_sessions'   => $this->closePastSessions($dryRun),
            'late_lesson_summary'   => $this->scanLateLessonSummary($dryRun),
            'late_hw_grading'       => $this->scanLateHwGrading($dryRun),
            'tick_penalty'          => $this->evaluateTickPenalty($dryRun),
            'session_reminders'     => $this->sendUpcomingSessionReminders($dryRun),
        );
    }

    /**
     * Notify active enrollments 45-75 minutes before an upcoming session.
     * Each enrollment is claimed before its notification is inserted.
     *
     * @param bool $dryRun
     * @return array
     */
    public function sendUpcomingSessionReminders($dryRun = false)
    {
        $stats = array('dry_run' => (bool) $dryRun, 'candidates' => 0, 'sent' => 0, 'errors' => 0);
        $rows = $this->db->safeFetch(
            'select ss.id as enrollment_id, ss.student_id, ses.id as session_id,'
            . ' ses.session_date, ses.start_time, c.name as class_name'
            . ' from db_lms_session_students ss'
            . ' join db_lms_sessions ses on ses.id = ss.session_id and ses.status = ?'
            . ' join db_lms_classes c on c.id = ses.class_id and c.status = ? and c.deleted_at is null'
            . ' join db_lms_class_students cs on cs.class_id = c.id'
            . ' and cs.student_id = ss.student_id and cs.status = ?'
            . ' where ss.reminder_sent_at is null and ses.start_time is not null'
            . ' and TIMESTAMP(ses.session_date, ses.start_time) between'
            . ' DATE_ADD(NOW(), INTERVAL 45 MINUTE) and DATE_ADD(NOW(), INTERVAL 75 MINUTE)'
            . ' order by ses.session_date, ses.start_time, ss.id',
            array('upcoming', 'active', 'active')
        );
        $stats['candidates'] = count($rows);
        if ($dryRun || empty($rows)) {
            return $stats;
        }

        foreach ($rows as $row) {
            $this->db->beginTransaction();
            try {
                $ok = $this->db->safeExec(
                    'update db_lms_session_students set reminder_sent_at = NOW()'
                    . ' where id = ? and reminder_sent_at is null',
                    array((int) $row['enrollment_id'])
                );
                if (!$ok || $this->db->affectedRows() !== 1) {
                    $this->db->rollBack();
                    continue;
                }

                $start = date('H:i', strtotime((string) $row['start_time']));
                $date = date('d/m/Y', strtotime((string) $row['session_date']));
                $message = 'Buổi học lớp ' . (string) $row['class_name'] . ' sẽ bắt đầu lúc '
                    . $start . ' ngày ' . $date . '.';
                $link = function_exists('lms_url_to')
                    ? lms_url_to('student', 'lms-lich-hoc', 'buoi', array('id' => (int) $row['session_id']))
                    : 'lms/student/lms-lich-hoc/buoi?id=' . (int) $row['session_id'];
                $notifId = $this->createSessionReminderNotification(
                    (int) $row['student_id'], $message, $link
                );
                if ($notifId === null) {
                    throw new \RuntimeException('Notification insert failed.');
                }
                $this->db->commit();
                $stats['sent']++;
            } catch (\Exception $e) {
                $this->db->rollBack();
                error_log('[LMS session reminder] ' . $e->getMessage());
                $stats['errors']++;
            }
        }
        return $stats;
    }

    /** @param int $studentId @param string $message @param string $link @return int|null */
    protected function createSessionReminderNotification($studentId, $message, $link)
    {
        return (new NotificationService($this->db))->notify(
            (int) $studentId,
            'session_reminder',
            'Nhắc lịch học sắp tới',
            $message,
            $link
        );
    }

    // ===================== Helpers BR-TICK-03 =====================

    /**
     * Số lớp active GV đang dạy.
     *
     * @param int $teacherId
     * @return int
     */
    public function countActiveClasses($teacherId)
    {
        return $this->db->count(
            'db_lms_classes',
            'teacher_id = ? and status = ? and deleted_at is null',
            array((int) $teacherId, 'active')
        );
    }

    /**
     * Tìm threshold khớp với số lớp (effective tại thời điểm hiện tại).
     *
     * @param int $classCount
     * @return array
     */
    public function findThreshold($classCount)
    {
        $classCount = (int) $classCount;
        return $this->db->safeFetchOne(
            'select * from db_lms_violation_thresholds'
            . ' where effective_from <= CURDATE() and (effective_to is null or effective_to >= CURDATE())'
            . ' and min_classes <= ? and (max_classes is null or max_classes >= ?)'
            . ' order by min_classes asc limit 1',
            array($classCount, $classCount)
        );
    }

    /**
     * tick_thực_tế của GV: SUM(tick_amount) - ticks_exempted, clamp >= 0.
     *
     * Tick cộng/trừ thủ công có thể làm tổng âm → clamp 0 (không cảnh báo khi
     * GV đang được miễn dư).
     *
     * @param int $teacherId
     * @param int $exempted
     * @return int
     */
    public function teacherRealTicks($teacherId, $exempted = 0)
    {
        $row = $this->db->safeFetchOne(
            'select coalesce(sum(tick_amount), 0) as s from db_lms_violations where teacher_id = ?',
            array((int) $teacherId)
        );
        $raw = isset($row['s']) ? (int) $row['s'] : 0;
        return max(0, $raw - (int) $exempted);
    }

    /**
     * Đã có thông báo tick_warning trong tháng hiện tại chưa (idempotency).
     *
     * @param int $teacherId
     * @return bool
     */
    private function hasTickWarningThisMonth($teacherId)
    {
        $count = $this->db->count(
            'db_lms_notifications',
            'user_id = ? and type = ? and created_at >= ?',
            array((int) $teacherId, 'tick_warning', date('Y-m-01 00:00:00'))
        );
        return $count > 0;
    }

    /**
     * Tổng hợp tick của GV cho admin hiển thị.
     *
     * @param int $teacherId
     * @return array array('raw', 'exempted', 'real', 'threshold' => array|null)
     */
    public function teacherTickSummary($teacherId)
    {
        $teacherId = (int) $teacherId;
        $threshold = $this->findThreshold($this->countActiveClasses($teacherId));
        $exempted  = !empty($threshold) && is_array($threshold) ? (int) $threshold['ticks_exempted'] : 0;

        $row = $this->db->safeFetchOne(
            'select coalesce(sum(tick_amount), 0) as s from db_lms_violations where teacher_id = ?',
            array($teacherId)
        );
        $raw = isset($row['s']) ? (int) $row['s'] : 0;

        return array(
            'raw'      => $raw,
            'exempted' => $exempted,
            'real'     => max(0, $raw - $exempted),
            'threshold' => !empty($threshold) && is_array($threshold) ? $threshold : null,
        );
    }

    /**
     * tick_thực_tế của 1 tháng cụ thể (cho BR-PAY-02: chốt lương tháng M xem
     * tick tháng M-1).
     *
     * exempted theo class count HIỆN TẠI (giản lược — không lưu lịch sử số lớp
     * theo tháng; nếu GV nghỉ/bớt lớp giữa kỳ thì exempted có thể lệch).
     *
     * @param int    $teacherId
     * @param string $month 'Y-m' (không hợp lệ → coi như 0 tick)
     * @return array array('raw', 'exempted', 'real', 'threshold' => array|null)
     */
    public function monthTickSummary($teacherId, $month)
    {
        $teacherId = (int) $teacherId;
        $raw       = 0;

        if (preg_match('/^\d{4}-\d{2}$/', (string) $month)) {
            // Dùng last day thật (PHP) — '<month>-31' bị MySQL coerce thành
            // 0000-00-00 với tháng 2 (cùng fix đã dùng trong listViolations).
            $lastDay = date('Y-m-t', strtotime($month . '-01'));
            $row     = $this->db->safeFetchOne(
                'select coalesce(sum(tick_amount), 0) as s from db_lms_violations'
                . ' where teacher_id = ? and violation_date between ? and ?',
                array($teacherId, $month . '-01', $lastDay)
            );
            $raw = isset($row['s']) ? (int) $row['s'] : 0;
        }

        $threshold = $this->findThreshold($this->countActiveClasses($teacherId));
        $exempted  = !empty($threshold) && is_array($threshold) ? (int) $threshold['ticks_exempted'] : 0;

        return array(
            'raw'       => $raw,
            'exempted'  => $exempted,
            'real'      => max(0, $raw - $exempted),
            'threshold' => !empty($threshold) && is_array($threshold) ? $threshold : null,
        );
    }

    // ===================== Admin (ADM-06) =====================

    /**
     * Format nội dung vi phạm hiển thị trên bảng quản lý / portal GV.
     *
     * @param array $row Bản ghi từ listViolations()
     * @return string
     */
    public static function formatViolationContent(array $row)
    {
        $note = isset($row['note']) ? trim((string) $row['note']) : '';
        if ($note !== '') {
            return $note;
        }

        $reason = isset($row['reason']) ? (string) $row['reason'] : '';
        $dateRaw = !empty($row['session_date'])
            ? (string) $row['session_date']
            : (isset($row['violation_date']) ? (string) $row['violation_date'] : '');
        $dateStr = ($dateRaw !== '' && strtotime($dateRaw) !== false)
            ? date('d/m/Y', strtotime($dateRaw))
            : 'xx/xx/xxxx';

        switch ($reason) {
            case self::REASON_LESSON_SUMMARY:
                return 'Bạn đã bị phạt một tick vì quên ghi Lesson summary cho buổi học ngày ' . $dateStr;
            case self::REASON_HW_GRADING:
                return 'Bạn đã bị phạt một tick vì quên nhận xét BTVN của học sinh cho buổi học ngày ' . $dateStr;
            case self::REASON_TEST_GRADING:
                return 'Bạn đã bị phạt một tick vì chấm test muộn quá 72 giờ kể từ khi học sinh nộp bài (buổi ngày ' . $dateStr . ')';
            case self::REASON_MANUAL_PENALTY:
                return 'Điều chỉnh cộng tick vi phạm thủ công';
            case self::REASON_MANUAL_EXEMPT:
                return 'Điều chỉnh miễn giảm tick vi phạm';
            default:
                return isset(self::ALL_REASONS[$reason]) ? self::ALL_REASONS[$reason] : $reason;
        }
    }

    /**
     * Hiển thị khoảng số lớp theo chuẩn mockup ("Từ 6 trở lên", "5", "1-2").
     *
     * @param int      $min
     * @param int|null $max
     * @return string
     */
    public static function formatClassRange($min, $max)
    {
        $min = (int) $min;
        if ($max === null || (string) $max === '') {
            return 'Từ ' . $min . ' trở lên';
        }
        $max = (int) $max;
        if ($min === $max) {
            return (string) $min;
        }
        return $min . '-' . $max;
    }

    /**
     * Phân tích chuỗi khoảng số lớp ("Từ 6 trở lên", ">=6", "6+", "5", "1-2") thành min/max.
     *
     * @param string $rangeStr
     * @return array|null array('min' => int, 'max' => int|null) hoặc null nếu sai định dạng
     */
    public static function parseClassRange($rangeStr)
    {
        $s = trim((string) $rangeStr);
        if ($s === '') {
            return null;
        }
        if (preg_match('/^(?:từ\s*)?(\d+)\s*(?:lớp\s*)?(?:trở\s*lên|\+)$/iu', $s, $m)
            || preg_match('/^>=\s*(\d+)$/u', $s, $m)) {
            $min = (int) $m[1];
            return $min >= 1 ? array('min' => $min, 'max' => null) : null;
        }
        if (preg_match('/^(\d+)\s*(?:[-–~]|đến)\s*(\d+)$/iu', $s, $m)) {
            $min = (int) $m[1];
            $max = (int) $m[2];
            return ($min >= 1 && $max >= $min) ? array('min' => $min, 'max' => $max) : null;
        }
        if (preg_match('/^(\d+)$/', $s, $m)) {
            $val = (int) $m[1];
            return $val >= 1 ? array('min' => $val, 'max' => $val) : null;
        }
        return null;
    }

    /**
     * Thêm tick thủ công (admin). session_id = NULL (MySQL 5.7 cho
     * phép nhiều hàng NULL trong UNIQUE → cùng ngày thêm nhiều lần được).
     *
     * @param int      $teacherId
     * @param string   $reason     'manual_penalty' | 'manual_exempt' | '' (tự suy theo dấu tick)
     * @param int      $tickAmount != 0 (+1, -1, +2, -2...)
     * @param string   $note
     * @param int      $actorId    user_id của admin (0 → NULL)
     * @param int|null $classId    classes.id (tuỳ chọn)
     * @return bool
     */
    public function addManualTick($teacherId, $reason, $tickAmount, $note = '', $actorId = 0, $classId = null)
    {
        $this->clearErrors();

        $teacherId  = (int) $teacherId;
        $tickAmount = (int) $tickAmount;
        $reason     = trim((string) $reason);
        $note       = trim((string) $note);
        $actorId    = (int) $actorId;
        $classId    = ($classId !== null && (int) $classId > 0) ? (int) $classId : null;

        if ($teacherId <= 0) {
            $this->addError('teacher_id', 'Giáo viên không hợp lệ.');
            return false;
        }

        if ($tickAmount === 0 || $tickAmount < -100 || $tickAmount > 100) {
            $this->addError('tick_amount', 'Số tick điều chỉnh phải là số nguyên khác 0 (VD: +1, -1, +2, -2).');
            return false;
        }

        if ($reason === '' || $reason === 'auto') {
            $reason = $tickAmount > 0 ? self::REASON_MANUAL_PENALTY : self::REASON_MANUAL_EXEMPT;
        }

        if (!isset(self::MANUAL_REASONS[$reason])) {
            $this->addError('reason', 'Lý do không hợp lệ.');
            return false;
        }

        if ($reason === self::REASON_MANUAL_PENALTY && $tickAmount <= 0) {
            $this->addError('tick_amount', 'Số tick phải là số dương (+1, +2...) cho lý do phạt thủ công.');
            return false;
        }
        if ($reason === self::REASON_MANUAL_EXEMPT && $tickAmount >= 0) {
            $this->addError('tick_amount', 'Số tick phải là số âm (-1, -2...) cho lý do miễn giảm.');
            return false;
        }

        $teacher = $this->db->safeFetchOne(
            'select user_id, teacher_code, full_name from db_lms_teachers where user_id = ? limit 1',
            array($teacherId)
        );
        if (empty($teacher) || !is_array($teacher)) {
            $this->addError('teacher_id', 'Giáo viên không tồn tại.');
            return false;
        }

        if ($classId !== null) {
            $cls = $this->db->safeFetchOne(
                'select id from db_lms_classes where id = ? and deleted_at is null limit 1',
                array($classId)
            );
            if (empty($cls) || !is_array($cls)) {
                $this->addError('class_id', 'Lớp học không tồn tại.');
                return false;
            }
        }

        $createdBy = null;
        if ($actorId > 0) {
            $admin = $this->db->safeFetchOne(
                'select user_id from db_lms_admins where user_id = ? limit 1',
                array($actorId)
            );
            if (empty($admin) || !is_array($admin)) {
                $this->addError('actor', 'Tài khoản admin không hợp lệ.');
                return false;
            }
            $createdBy = $actorId;
        }

        $data = array(
            'teacher_id'     => $teacherId,
            'class_id'       => $classId,
            'session_id'     => null,
            'tick_amount'    => $tickAmount,
            'reason'         => $reason,
            'note'           => $note !== '' ? $note : null,
            'auto_generated' => 0,
            'violation_date' => date('Y-m-d'),
            'created_by'     => $createdBy,
        );

        $ok = $this->db->insert('db_lms_violations', $data);
        if (!$ok) {
            $this->addError('system', 'Không thể thêm tick. Vui lòng thử lại.');
            return false;
        }

        $violationId = (int) $this->db->insertId;
        $this->audit('add_manual_tick', 'violations', $violationId, null, $data, 'admin');

        return true;
    }

    /**
     * Xoá 1 tick (admin).
     *
     * @param int  $violationId
     * @param bool $allowAuto   Mặc định false (chặn tick tự sinh ở API mặc định);
     *                          truyền true khi Admin chủ động bấm Xoá trên bảng Quản lý vi phạm.
     * @return bool
     */
    public function deleteTick($violationId, $allowAuto = false)
    {
        $this->clearErrors();

        $violationId = (int) $violationId;
        if ($violationId <= 0) {
            $this->addError('id', 'Tick không hợp lệ.');
            return false;
        }

        $row = $this->db->safeFetchOne(
            'select * from db_lms_violations where id = ? limit 1',
            array($violationId)
        );
        if (empty($row) || !is_array($row)) {
            $this->addError('id', 'Tick không tồn tại.');
            return false;
        }

        // Tick tự sinh (cron) chỉ xoá khi admin chấp nhận rủi ro ($allowAuto = true).
        if (!$allowAuto && (int) $row['auto_generated'] === 1) {
            $this->addError('id', 'Không thể xoá tick do cron tạo. Dùng miễn giảm thủ công (-1) để bù trừ.');
            return false;
        }

        $ok = $this->db->safeExec('delete from db_lms_violations where id = ?', array($violationId));
        if (!$ok) {
            $this->addError('system', 'Không thể xoá tick. Vui lòng thử lại.');
            return false;
        }

        $this->audit('delete_manual_tick', 'violations', $violationId, $row, null, 'admin');

        return true;
    }

    /**
     * Danh sách tick (admin): filter từ khoá (Mã lớp, GV) / GV / lý do / tháng / nguồn + phân trang.
     *
     * @param array $filters array('q', 'teacher_id', 'reason', 'month' => 'Y-m', 'auto' => ''|'1'|'0')
     * @param int   $page
     * @param int   $perPage
     * @return array array('items' => array, 'total' => int)
     */
    public function listViolations(array $filters = array(), $page = 1, $perPage = 20)
    {
        $q         = isset($filters['q']) ? trim((string) $filters['q']) : '';
        $teacherId = isset($filters['teacher_id']) ? (int) $filters['teacher_id'] : 0;
        $reason    = isset($filters['reason']) ? (string) $filters['reason'] : '';
        $month     = isset($filters['month']) ? (string) $filters['month'] : '';
        $auto      = isset($filters['auto']) ? (string) $filters['auto'] : '';

        $where  = '1=1';
        $params = array();

        if ($teacherId > 0) {
            $where .= ' and v.teacher_id = ?';
            $params[] = $teacherId;
        }
        if ($reason !== '' && isset(self::ALL_REASONS[$reason])) {
            $where .= ' and v.reason = ?';
            $params[] = $reason;
        }
        if (preg_match('/^\d{4}-\d{2}$/', $month)) {
            // Dùng last day thật (PHP) thay vì '<month>-31' — tháng 2 sẽ sai
            // vì MySQL coerce '2026-02-31' thành 0000-00-00.
            $lastDay = date('Y-m-t', strtotime($month . '-01'));
            $where .= ' and v.violation_date between ? and ?';
            $params[] = $month . '-01';
            $params[] = $lastDay;
        }
        if ($auto === '1') {
            $where .= ' and v.auto_generated = 1';
        } elseif ($auto === '0') {
            $where .= ' and v.auto_generated = 0';
        }
        if ($q !== '') {
            $like = '%' . $q . '%';
            $where .= ' and (c.class_code like ? or c.name like ? or t.teacher_code like ? or t.full_name like ? or v.note like ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        $fromJoins = ' from db_lms_violations v'
            . ' left join db_lms_teachers t on t.user_id = v.teacher_id'
            . ' left join db_lms_sessions s on s.id = v.session_id'
            . ' left join db_lms_classes c on c.id = coalesce(v.class_id, s.class_id)'
            . ' left join db_lms_users creator on creator.id = v.created_by';

        $totalRow = $this->db->safeFetchOne(
            'select count(*) as c' . $fromJoins . ' where ' . $where,
            $params
        );
        $total = isset($totalRow['c']) ? (int) $totalRow['c'] : 0;

        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        $targetMonth = preg_match('/^\d{4}-\d{2}$/', $month) ? $month : date('Y-m');
        $monthStart  = $targetMonth . '-01';
        $monthEnd    = date('Y-m-t', strtotime($monthStart));

        $selectParams = array_merge(array($monthStart, $monthEnd), $params);

        $items = $this->db->safeFetch(
            'select v.*, t.teacher_code, t.full_name as teacher_name,'
            . ' c.class_code, c.name as class_name, c.class_type,'
            . ' (select group_concat(st.full_name order by st.full_name separator \', \')'
            . '   from db_lms_class_students cs'
            . '   join db_lms_students st on st.user_id = cs.student_id'
            . '  where cs.class_id = c.id and cs.status = \'active\') as student_names,'
            . ' s.session_date, s.start_time, s.end_time,'
            . ' creator.username as created_by_name,'
            . ' (select coalesce(sum(v2.tick_amount), 0)'
            . '  from db_lms_violations v2'
            . '  where v2.teacher_id = v.teacher_id'
            . '    and v2.violation_date between ? and ?) as month_ticks'
            . $fromJoins
            . ' where ' . $where
            . ' order by v.violation_date desc, v.id desc'
            . ' limit ' . $offset . ', ' . $perPage,
            $selectParams
        );

        return array('items' => $items, 'total' => $total);
    }

    /**
     * Lưu đồng loạt bảng quy chế miễn giảm + ngưỡng tick hạ phí dịch vụ (Mockup Miễn giảm.png).
     *
     * @param array $rows           Mảng các dòng array('class_range' => string, 'ticks_exempted' => int)
     * @param int   $demoteTicks    Ngưỡng tick hạ phí dịch vụ (VD: 8)
     * @param int   $actorId        ID admin thực hiện
     * @return bool
     */
    public function saveRegulationThresholds(array $rows, $demoteTicks, $actorId = 0)
    {
        $this->clearErrors();

        $demoteTicks = (int) $demoteTicks;
        if ($demoteTicks < 1 || $demoteTicks > 100) {
            $this->addError('ticks_to_demote_fee', 'Ngưỡng tick hạ phí dịch vụ phải từ 1 đến 100.');
            return false;
        }

        $parsed = array();
        foreach ($rows as $idx => $r) {
            $rangeStr  = isset($r['class_range']) ? trim((string) $r['class_range']) : '';
            $exemptRaw = isset($r['ticks_exempted']) ? trim((string) $r['ticks_exempted']) : '';
            if ($rangeStr === '' && $exemptRaw === '') {
                continue;
            }
            $range = self::parseClassRange($rangeStr);
            if ($range === null) {
                $this->addError('class_range_' . $idx, 'Khoảng số lớp "' . $rangeStr . '" không hợp lệ (VD hợp lệ: "Từ 6 trở lên", "5", "1-2").');
                return false;
            }
            if (!is_numeric($exemptRaw) || (int) $exemptRaw < 0 || (int) $exemptRaw > 100) {
                $this->addError('ticks_exempted_' . $idx, 'Số tick miễn giảm phải từ 0 đến 100.');
                return false;
            }
            $parsed[] = array(
                'min_classes'    => (int) $range['min'],
                'max_classes'    => $range['max'],
                'ticks_exempted' => (int) $exemptRaw,
            );
        }

        if (empty($parsed)) {
            $this->addError('rows', 'Vui lòng nhập ít nhất 1 dòng quy chế miễn giảm.');
            return false;
        }

        $count = count($parsed);
        for ($i = 0; $i < $count; $i++) {
            $aMin = $parsed[$i]['min_classes'];
            $aMax = $parsed[$i]['max_classes'] !== null ? $parsed[$i]['max_classes'] : 9999;
            for ($j = $i + 1; $j < $count; $j++) {
                $bMin = $parsed[$j]['min_classes'];
                $bMax = $parsed[$j]['max_classes'] !== null ? $parsed[$j]['max_classes'] : 9999;
                if ($aMin <= $bMax && $aMax >= $bMin) {
                    $this->addError(
                        'rows',
                        'Khoảng lớp "' . self::formatClassRange($parsed[$i]['min_classes'], $parsed[$i]['max_classes'])
                        . '" bị chồng lấn với "' . self::formatClassRange($parsed[$j]['min_classes'], $parsed[$j]['max_classes']) . '".'
                    );
                    return false;
                }
            }
        }

        $existing       = $this->listThresholds();
        $feeAfterDemote = 30.00;
        $effectiveFrom  = date('Y-m-01');
        if (!empty($existing) && isset($existing[0]['fee_percentage_after_demote'])) {
            $feeAfterDemote = (float) $existing[0]['fee_percentage_after_demote'];
        }
        if (!empty($existing) && !empty($existing[0]['effective_from'])) {
            $effectiveFrom = (string) $existing[0]['effective_from'];
        }
        $ticksToWarn = min(5, $demoteTicks);

        $this->db->beginTransaction();
        try {
            $okDel = $this->db->safeExec('delete from db_lms_violation_thresholds');
            if (!$okDel) {
                throw new \RuntimeException('Không thể làm mới bảng quy chế miễn giảm.');
            }

            foreach ($parsed as $item) {
                $okIns = $this->db->insert('db_lms_violation_thresholds', array(
                    'min_classes'                 => $item['min_classes'],
                    'max_classes'                 => $item['max_classes'],
                    'ticks_to_warn'               => $ticksToWarn,
                    'ticks_to_demote_fee'         => $demoteTicks,
                    'fee_percentage_after_demote' => $feeAfterDemote,
                    'ticks_exempted'              => $item['ticks_exempted'],
                    'effective_from'              => $effectiveFrom,
                    'effective_to'                => null,
                ));
                if (!$okIns) {
                    throw new \RuntimeException('Không thể lưu dòng quy chế miễn giảm.');
                }
            }

            $this->audit('update_regulation_thresholds', 'violation_thresholds', null, $existing, array(
                'ticks_to_demote_fee' => $demoteTicks,
                'rows'                => $parsed,
            ), 'admin');

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            $this->addError('system', $e->getMessage());
            return false;
        }
    }

    /**
     * Lấy cấu hình quy chế quét thời hạn cho lớp 1-1 và lớp đông người (Mockup Miễn giảm.png).
     * Lưu tại db_lms_class_rules (id = 2) dưới dạng JSON để không ảnh hưởng schema hiện hữu.
     *
     * @return array array('one_on_one' => array(...), 'group' => array(...))
     */
    public function getScanRules()
    {
        $defaults = array(
            'one_on_one' => array(
                'hw_deadline_enabled' => 1,
                'hw_deadline_time'    => '12:00',
                'hw_deadline_ticks'   => 1,
                'hw_check_enabled'    => 1,
                'hw_check_hours'      => 0,
                'hw_check_ticks'      => 1,
                'test_grace_enabled'  => 1,
                'test_grace_hours'    => 72,
                'test_grace_ticks'    => 1,
            ),
            'group' => array(
                'hw_deadline_enabled' => 1,
                'hw_deadline_time'    => '12:00',
                'hw_deadline_ticks'   => 1,
                'hw_check_enabled'    => 1,
                'hw_check_hours'      => 24,
                'hw_check_ticks'      => 1,
                'test_grace_enabled'  => 1,
                'test_grace_hours'    => 72,
                'test_grace_ticks'    => 1,
            ),
        );

        $row = $this->db->safeFetchOne(
            'select content from db_lms_class_rules where id = 2 limit 1'
        );
        if (empty($row) || empty($row['content'])) {
            return $defaults;
        }

        $decoded = json_decode((string) $row['content'], true);
        if (!is_array($decoded)) {
            return $defaults;
        }

        foreach (array('one_on_one', 'group') as $scope) {
            if (isset($decoded[$scope]) && is_array($decoded[$scope])) {
                $defaults[$scope] = array_merge($defaults[$scope], $decoded[$scope]);
            }
        }

        return $defaults;
    }

    /**
     * Lưu cấu hình quy chế quét thời hạn cho lớp 1-1 hoặc lớp đông người.
     *
     * @param string $scope   'one_on_one' | 'group'
     * @param array  $in      Dữ liệu form
     * @param int    $actorId ID admin
     * @return bool
     */
    public function saveScanRules($scope, array $in, $actorId = 0)
    {
        $this->clearErrors();

        $scope = trim((string) $scope);
        if (!in_array($scope, array('one_on_one', 'group'), true)) {
            $this->addError('scope', 'Loại lớp không hợp lệ.');
            return false;
        }

        $current = $this->getScanRules();
        $target  = $current[$scope];

        $hwDeadlineEnabled = !empty($in['hw_deadline_enabled']) ? 1 : 0;
        $hwDeadlineTime    = isset($in['hw_deadline_time']) ? trim((string) $in['hw_deadline_time']) : '12:00';
        if (preg_match('/^(\d{2}:\d{2}):\d{2}$/', $hwDeadlineTime, $m)) {
            $hwDeadlineTime = $m[1];
        }
        if (!preg_match('/^(?:[01]\d|2[0-3]):[0-5]\d$/', $hwDeadlineTime)) {
            $this->addError('hw_deadline_time', 'Giờ hạn giao BTVN không hợp lệ (HH:MM).');
            return false;
        }
        $hwDeadlineTicks = isset($in['hw_deadline_ticks']) ? (int) $in['hw_deadline_ticks'] : 1;
        if ($hwDeadlineTicks < 0 || $hwDeadlineTicks > 20) {
            $this->addError('hw_deadline_ticks', 'Số tick phạt hạn giao BTVN phải từ 0 đến 20.');
            return false;
        }

        $hwCheckEnabled = !empty($in['hw_check_enabled']) ? 1 : 0;
        $hwCheckHours   = isset($in['hw_check_hours']) ? (int) $in['hw_check_hours'] : ($scope === 'group' ? 24 : 0);
        if ($hwCheckHours < 0 || $hwCheckHours > 720) {
            $this->addError('hw_check_hours', 'Số giờ hạn check BTVN phải từ 0 đến 720.');
            return false;
        }
        $hwCheckTicks = isset($in['hw_check_ticks']) ? (int) $in['hw_check_ticks'] : 1;
        if ($hwCheckTicks < 0 || $hwCheckTicks > 20) {
            $this->addError('hw_check_ticks', 'Số tick phạt hạn check BTVN phải từ 0 đến 20.');
            return false;
        }

        $target['hw_deadline_enabled'] = $hwDeadlineEnabled;
        $target['hw_deadline_time']    = $hwDeadlineTime;
        $target['hw_deadline_ticks']   = $hwDeadlineTicks;
        $target['hw_check_enabled']    = $hwCheckEnabled;
        $target['hw_check_hours']      = $hwCheckHours;
        $target['hw_check_ticks']      = $hwCheckTicks;

        if ($scope === 'one_on_one' || isset($in['test_grace_hours'])) {
            $testGraceEnabled = !empty($in['test_grace_enabled']) ? 1 : 0;
            $testGraceHours   = isset($in['test_grace_hours']) ? (int) $in['test_grace_hours'] : 72;
            if ($testGraceHours < 0 || $testGraceHours > 720) {
                $this->addError('test_grace_hours', 'Số giờ hạn chấm test phải từ 0 đến 720.');
                return false;
            }
            $testGraceTicks = isset($in['test_grace_ticks']) ? (int) $in['test_grace_ticks'] : 1;
            if ($testGraceTicks < 0 || $testGraceTicks > 20) {
                $this->addError('test_grace_ticks', 'Số tick phạt hạn chấm test phải từ 0 đến 20.');
                return false;
            }
            $target['test_grace_enabled'] = $testGraceEnabled;
            $target['test_grace_hours']   = $testGraceHours;
            $target['test_grace_ticks']   = $testGraceTicks;
        }

        $before = $current;
        $current[$scope] = $target;

        $updatedBy = null;
        if ((int) $actorId > 0) {
            $adm = $this->db->safeFetchOne(
                'select user_id from db_lms_admins where user_id = ? limit 1',
                array((int) $actorId)
            );
            if (!empty($adm)) {
                $updatedBy = (int) $actorId;
            }
        }

        $json = json_encode($current, JSON_UNESCAPED_UNICODE);
        $ok = $this->db->safeExec(
            'insert into db_lms_class_rules (id, content, updated_by) values (2, ?, ?)'
            . ' on duplicate key update content = values(content), updated_by = values(updated_by)',
            array($json, $updatedBy)
        );
        if (!$ok) {
            $this->addError('system', 'Không thể lưu quy chế quét thời hạn.');
            return false;
        }

        $this->audit('update_scan_rules_' . $scope, 'class_rules', 2, $before, $current, 'admin');
        return true;
    }

    /**
     * Danh sách threshold (theo khoảng số lớp).
     *
     * @return array
     */
    public function listThresholds()
    {
        return $this->db->safeFetch(
            'select * from db_lms_violation_thresholds order by min_classes asc'
        );
    }

    /**
     * Lấy 1 threshold.
     *
     * @param int $thresholdId
     * @return array
     */
    public function getThreshold($thresholdId)
    {
        return $this->db->safeFetchOne(
            'select * from db_lms_violation_thresholds where id = ? limit 1',
            array((int) $thresholdId)
        );
    }

    /**
     * Xoá 1 threshold (admin).
     *
     * @param int $thresholdId
     * @return bool
     */
    public function deleteThreshold($thresholdId)
    {
        $this->clearErrors();

        $thresholdId = (int) $thresholdId;
        if ($thresholdId <= 0) {
            $this->addError('id', 'Rule không hợp lệ.');
            return false;
        }

        $row = $this->getThreshold($thresholdId);
        if (empty($row) || !is_array($row)) {
            $this->addError('id', 'Rule không tồn tại.');
            return false;
        }

        $ok = $this->db->safeExec(
            'delete from db_lms_violation_thresholds where id = ?',
            array($thresholdId)
        );
        if (!$ok) {
            $this->addError('system', 'Không thể xoá rule. Vui lòng thử lại.');
            return false;
        }

        $this->audit('delete_threshold', 'violation_thresholds', $thresholdId, $row, null, 'admin');

        return true;
    }

    /**
     * Thêm/sửa threshold (admin).
     *
     * @param array      $in  min_classes, max_classes, ticks_to_warn,
     *                         ticks_to_demote_fee, fee_percentage_after_demote,
     *                         ticks_exempted, effective_from, effective_to
     * @param int|null   $thresholdId null = thêm mới
     * @return bool
     */
    public function saveThreshold(array $in, $thresholdId = null)
    {
        $this->clearErrors();

        $thresholdId = $thresholdId === null ? 0 : (int) $thresholdId;

        $minClasses  = isset($in['min_classes']) ? (int) $in['min_classes'] : 0;
        $maxClasses  = isset($in['max_classes']) && (string) $in['max_classes'] !== ''
            ? (int) $in['max_classes'] : null;
        $ticksToWarn = isset($in['ticks_to_warn']) ? (int) $in['ticks_to_warn'] : -1;
        $ticksToDemote = isset($in['ticks_to_demote_fee']) ? (int) $in['ticks_to_demote_fee'] : -1;
        $feePercent  = isset($in['fee_percentage_after_demote']) ? (string) $in['fee_percentage_after_demote'] : '';
        $exempted    = isset($in['ticks_exempted']) ? (int) $in['ticks_exempted'] : -1;
        $from        = isset($in['effective_from']) ? (string) $in['effective_from'] : '';
        $to          = isset($in['effective_to']) && (string) $in['effective_to'] !== ''
            ? (string) $in['effective_to'] : null;

        if ($minClasses < 1) {
            $this->addError('min_classes', 'Số lớp tối thiểu phải >= 1.');
            return false;
        }
        if ($maxClasses !== null && $maxClasses < $minClasses) {
            $this->addError('max_classes', 'Số lớp tối đa phải >= số lớp tối thiểu.');
            return false;
        }
        if ($ticksToWarn < 0) {
            $this->addError('ticks_to_warn', 'Ngưỡng cảnh báo phải >= 0.');
            return false;
        }
        if ($ticksToDemote < 0) {
            $this->addError('ticks_to_demote_fee', 'Ngưỡng hạ phí phải >= 0.');
            return false;
        }
        if ($ticksToDemote < $ticksToWarn) {
            $this->addError('ticks_to_demote_fee', 'Ngưỡng hạ phí phải >= ngưỡng cảnh báo.');
            return false;
        }
        if (!is_numeric($feePercent) || (float) $feePercent < 0 || (float) $feePercent > 100) {
            $this->addError('fee_percentage_after_demote', '% phí sau hạ phải từ 0 đến 100.');
            return false;
        }
        if ($exempted < 0) {
            $this->addError('ticks_exempted', 'Số tick miễn phải >= 0.');
            return false;
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from) || strtotime($from) === false) {
            $this->addError('effective_from', 'Ngày hiệu lực không hợp lệ.');
            return false;
        }
        if ($to !== null && (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to) || strtotime($to) === false)) {
            $this->addError('effective_to', 'Ngày hết hiệu lực không hợp lệ.');
            return false;
        }
        if ($to !== null && strcmp($to, $from) < 0) {
            $this->addError('effective_to', 'Ngày hết hiệu lực phải từ ngày hiệu lực trở đi.');
            return false;
        }

        // Không cho 2 khoảng đè nhau (theo khoảng số lớp tại thời điểm hiệu lực).
        $overlap = $this->db->safeFetchOne(
            'select id from db_lms_violation_thresholds'
            . ' where id <> ? and effective_from <= ? and (effective_to is null or effective_to >= ?)'
            . ' and ? <= ifnull(max_classes, 9999) and ifnull(?, 9999) >= min_classes'
            . ' limit 1',
            array($thresholdId, $from, $from, $minClasses, $maxClasses)
        );
        if (!empty($overlap) && is_array($overlap)) {
            $this->addError('min_classes', 'Khoảng số lớp này đã trùng với một rule đang hiệu lực.');
            return false;
        }

        $data = array(
            'min_classes'              => $minClasses,
            'max_classes'              => $maxClasses,
            'ticks_to_warn'            => $ticksToWarn,
            'ticks_to_demote_fee'      => $ticksToDemote,
            'fee_percentage_after_demote' => (float) $feePercent,
            'ticks_exempted'           => $exempted,
            'effective_from'           => $from,
            'effective_to'             => $to,
        );

        if ($thresholdId > 0) {
            $before = $this->getThreshold($thresholdId);
            if (empty($before) || !is_array($before)) {
                $this->addError('id', 'Rule không tồn tại.');
                return false;
            }
            $ok = $this->db->update('db_lms_violation_thresholds', $data, 'id = ?', array($thresholdId));
            if (!$ok) {
                $this->addError('system', 'Không thể cập nhật rule. Vui lòng thử lại.');
                return false;
            }
            $this->audit('update_threshold', 'violation_thresholds', $thresholdId, $before, $data, 'admin');
        } else {
            $ok = $this->db->insert('db_lms_violation_thresholds', $data);
            if (!$ok) {
                $this->addError('system', 'Không thể tạo rule. Vui lòng thử lại.');
                return false;
            }
            $thresholdId = (int) $this->db->insertId;
            $this->audit('create_threshold', 'violation_thresholds', $thresholdId, null, $data, 'admin');
        }

        return true;
    }
}
