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

        $ok = $this->db->update('db_lms_sessions', array('status' => 'finished'), $where, array('upcoming'));
        if ($ok) {
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
        $exempted = is_array($threshold) ? (int) $threshold['ticks_exempted'] : 0;

        $row = $this->db->safeFetchOne(
            'select coalesce(sum(tick_amount), 0) as s from db_lms_violations where teacher_id = ?',
            array($teacherId)
        );
        $raw = isset($row['s']) ? (int) $row['s'] : 0;

        return array(
            'raw'      => $raw,
            'exempted' => $exempted,
            'real'     => max(0, $raw - $exempted),
            'threshold' => is_array($threshold) ? $threshold : null,
        );
    }

    // ===================== Admin (ADM-06) =====================

    /**
     * Thêm tick thủ công (admin). session_id/class_id = NULL (MySQL 5.7 cho
     * phép nhiều hàng NULL trong UNIQUE → cùng ngày thêm nhiều lần được).
     *
     * @param int    $teacherId
     * @param string $reason     'manual_penalty' | 'manual_exempt'
     * @param int    $tickAmount +1 | -1 (phải khớp dấu của reason)
     * @param string $note
     * @param int    $actorId    user_id của admin (0 → NULL)
     * @return bool
     */
    public function addManualTick($teacherId, $reason, $tickAmount, $note, $actorId)
    {
        $this->clearErrors();

        $teacherId  = (int) $teacherId;
        $tickAmount = (int) $tickAmount;
        $reason     = (string) $reason;
        $note       = trim((string) $note);
        $actorId    = (int) $actorId;

        if ($teacherId <= 0) {
            $this->addError('teacher_id', 'Giáo viên không hợp lệ.');
            return false;
        }

        if (!isset(self::MANUAL_REASONS[$reason])) {
            $this->addError('reason', 'Lý do không hợp lệ.');
            return false;
        }

        $expected = $reason === self::REASON_MANUAL_PENALTY ? 1 : -1;
        if ($tickAmount !== $expected) {
            $this->addError('tick_amount', 'Số tick phải là ' . ($expected > 0 ? '+1' : '-1')
                . ' cho lý do đã chọn.');
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
            'class_id'       => null,
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
     * @param int $violationId
     * @return bool
     */
    public function deleteTick($violationId)
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

        // Tick tự sinh (cron) chỉ xoá khi admin chấp nhận rủi ro — chặn để giữ
        // lịch sử đối soát với lương. Admin muốn huỷ phải dùng manual_exempt.
        if ((int) $row['auto_generated'] === 1) {
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
     * Danh sách tick (admin): filter GV / lý do / tháng / nguồn + phân trang.
     *
     * @param array $filters array('teacher_id', 'reason', 'month' => 'Y-m', 'auto' => ''|'1'|'0')
     * @param int   $page
     * @param int   $perPage
     * @return array array('items' => array, 'total' => int)
     */
    public function listViolations(array $filters = array(), $page = 1, $perPage = 20)
    {
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

        $total = $this->db->safeFetchOne(
            'select count(*) as c from db_lms_violations v where ' . $where,
            $params
        );
        $total = isset($total['c']) ? (int) $total['c'] : 0;

        $page    = max(1, (int) $page);
        $perPage = max(1, min(100, (int) $perPage));
        $offset  = ($page - 1) * $perPage;

        $items = $this->db->safeFetch(
            'select v.*, t.teacher_code, t.full_name as teacher_name,'
            . ' c.class_code, c.name as class_name,'
            . ' s.session_date, s.start_time, s.end_time,'
            . ' creator.username as created_by_name'
            . ' from db_lms_violations v'
            . ' left join db_lms_teachers t on t.user_id = v.teacher_id'
            . ' left join db_lms_classes c on c.id = v.class_id'
            . ' left join db_lms_sessions s on s.id = v.session_id'
            . ' left join db_lms_users creator on creator.id = v.created_by'
            . ' where ' . $where
            . ' order by v.violation_date desc, v.id desc'
            . ' limit ' . $offset . ', ' . $perPage,
            $params
        );

        return array('items' => $items, 'total' => $total);
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
