<?php
/**
 * Helper logging cron vào db_lms_cron_runs (R1/R8: cảnh báo khi cron chết >30 phút).
 *
 * Schema (core.sql): job_name ∈ tick_scan | session_generate | payroll_close;
 * status ∈ running | success | failed; rows_affected; error_message.
 *
 * Cách dùng (xem database/cron/lms_tick_cron.php):
 *   $runId = lms_cron_start($d, 'tick_scan');
 *   try { ...; lms_cron_finish($d, $runId, 'success', $n); }
 *   catch (Exception $e) { lms_cron_finish($d, $runId, 'failed', 0, $e->getMessage()); }
 *
 * PHP 7.3 compatible.
 */

if (!function_exists('lms_cron_start')) {
    /**
     * Đánh dấu bắt đầu 1 cron run.
     *
     * @param LmsDb  $d
     * @param string $jobName tick_scan | session_generate | payroll_close
     * @return int|null id row db_lms_cron_runs (null nếu không ghi được)
     */
    function lms_cron_start(LmsDb $d, $jobName)
    {
        $ok = $d->insert(
            'db_lms_cron_runs',
            array(
                'job_name'   => $jobName,
                'started_at' => date('Y-m-d H:i:s'),
                'status'     => 'running',
            )
        );
        return $ok ? (int) $d->insertId : null;
    }
}

if (!function_exists('lms_cron_finish')) {
    /**
     * Đánh dấu kết thúc 1 cron run.
     *
     * @param LmsDb      $d
     * @param int|null   $runId      id từ lms_cron_start()
     * @param string     $status     success | failed
     * @param int        $rowsAffected
     * @param string|null $error
     * @return bool
     */
    function lms_cron_finish(LmsDb $d, $runId, $status, $rowsAffected = 0, $error = null)
    {
        if ($runId === null || (int) $runId <= 0) {
            return false;
        }
        $data = array(
            'finished_at'  => date('Y-m-d H:i:s'),
            'status'       => ($status === 'failed') ? 'failed' : 'success',
            'rows_affected' => (int) $rowsAffected,
        );
        if ($error !== null && $error !== '') {
            // Cắt bớt: cột TEXT nhưng tránh log quá dài.
            $data['error_message'] = mb_substr((string) $error, 0, 65000);
        }
        return $d->update('db_lms_cron_runs', $data, 'id = ?', array((int) $runId));
    }
}
