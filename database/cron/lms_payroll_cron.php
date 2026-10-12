<?php
/**
 * Cron LMS — chốt lương giáo viên đầu tháng: BR-PAY-01/02/03.
 *
 * Chạy qua CLI ngày 1 hàng tháng (Windows Task Scheduler / crontab):
 *   0 0 1 * * php database/cron/lms_payroll_cron.php
 *
 * Options:
 *   --dry-run        Chỉ thống kê, không ghi DB (xem trước).
 *   --db=<name>      Override tên DB (VD test: --db=phuongnamv_db_lms_test).
 *   --month=<Y-m>    Tháng cần chốt (mặc định: tháng trước).
 *   --teacher=<id>   Chỉ chốt 1 GV (test/sửa个案).
 *
 * Bảo mật: exit(1) nếu SAPI != cli; thư mục database/ cũng bị .htaccess chặn.
 *
 * Lưu ý: chỉ chốt buổi đã 'finished' và không còn BTVN chưa chấm. Chạy sau khi
 * tick cron đã chốt xong tháng (ngày 1 hàng tháng, sau 00:15).
 *
 * PHP 7.3 compatible.
 */

if (php_sapi_name() !== 'cli') {
    if (function_exists('http_response_code')) {
        http_response_code(403);
    }
    exit(1);
}

require_once __DIR__ . '/../../lms/lib/lms_bootstrap.php';

// ===================== Parse tham số + chạy =====================

$dryRun    = false;
$dbName    = null;
$month     = null;
$teacherId = 0;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run' || $arg === '-n') {
        $dryRun = true;
    } elseif (substr($arg, 0, 5) === '--db=') {
        $dbName = trim(substr($arg, 5));
    } elseif (substr($arg, 0, 8) === '--month=') {
        $month = trim(substr($arg, 8));
    } elseif (substr($arg, 0, 10) === '--teacher=') {
        $teacherId = (int) trim(substr($arg, 10));
    }
}

// Override DB PHẢI chạy trước khi lms_db() kết nối lần đầu (nó cache static).
if ($dbName !== null && $dbName !== '') {
    putenv('LMS_DB_NAME=' . $dbName);
}

$d = lms_db();

// Mặc định: tháng trước (ngày 1 hàng tháng chốt tháng vừa qua).
if ($month === null || !preg_match('/^\d{4}-\d{2}$/', $month)) {
    $month = date('Y-m', strtotime('first day of previous month'));
}

$runId = $dryRun ? null : lms_cron_start($d, 'payroll_close');

try {
    $svc = new \Lms\PayrollService($d);

    if ($teacherId > 0) {
        $stats = array(
            'mode'  => 'single_teacher',
            'month' => $month,
            'stats' => $svc->closeMonth($teacherId, $month, $dryRun),
        );
        $errors = $svc->hasErrors();
    } else {
        $stats  = array('mode' => 'all_teachers') + $svc->closeMonthAll($month, $dryRun);
        $errors = $svc->hasErrors();
    }
} catch (Exception $e) {
    if ($runId !== null) {
        lms_cron_finish($d, $runId, 'failed', 0, $e->getMessage());
    }
    echo json_encode(
        array(
            'time'   => date('Y-m-d H:i:s'),
            'result' => 'exception',
            'error'  => $e->getMessage(),
        ),
        JSON_UNESCAPED_UNICODE
    ), PHP_EOL;
    exit(1);
}

// ===================== Báo cáo =====================

$rowsAffected = 0;
if (isset($stats['closed'])) {
    $rowsAffected += (int) $stats['closed'];
}
if (isset($stats['details']) && is_array($stats['details'])) {
    foreach ($stats['details'] as $detail) {
        if (isset($detail['closed'])) {
            $rowsAffected += (int) $detail['closed'];
        }
    }
}

if ($runId !== null) {
    lms_cron_finish($d, $runId, $errors ? 'failed' : 'success', $rowsAffected);
}

$config = lms_db_config();

$stats['time']    = date('Y-m-d H:i:s');
$stats['db']      = isset($config['name']) ? $config['name'] : '';
$stats['dry_run'] = $dryRun;
$stats['result']  = $errors ? 'errors' : 'ok';
if ($errors) {
    $stats['service_errors'] = $svc->errors();
}

echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;

exit($errors ? 1 : 0);
