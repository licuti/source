<?php
/**
 * Cron LMS — quét vi phạm (tick) giáo viên: BR-TICK-01/02/03.
 *
 * Chạy qua CLI mỗi 15 phút (Windows Task Scheduler / crontab):
 *   php database/cron/lms_tick_cron.php [--dry-run] [--db=<dbname>]
 *
 * Options:
 *   --dry-run      Chỉ thống kê, không ghi DB (xem trước).
 *   --db=<name>    Override tên DB (VD test: --db=phuongnamv_db_lms_test).
 *
 * Bảo mật: exit(1) nếu SAPI != cli; thư mục database/ cũng bị .htaccess chặn
 * truy cập web. Lý do chạy CLI: tách khỏi request web (time limit), dùng
 * credentials DB của server (không qua session user).
 *
 * Timezone: server cần date.timezone = Asia/Ho_Chi_Minh trong php.ini thì
 * BR-TICK mới đúng giờ VN (NOW()/CURDATE() theo giờ MySQL server).
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

// ===================== Parse tham số + chạy pipeline =====================
//
// Ghi db_lms_cron_runs (R1/R8: cảnh báo khi cron chết >30 phút).
// Dry-run KHÔNG ghi — tránh làm bộ đếm monitoring reset khi chạy tay.

$dryRun = false;
$dbName = null;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run' || $arg === '-n') {
        $dryRun = true;
    } elseif (substr($arg, 0, 5) === '--db=') {
        $dbName = trim(substr($arg, 5));
    }
}

// Override DB PHẢI chạy trước khi lms_db() kết nối lần đầu (nó cache static).
if ($dbName !== null && $dbName !== '') {
    putenv('LMS_DB_NAME=' . $dbName);
}

$d = lms_db();

$runId = $dryRun ? null : lms_cron_start($d, 'tick_scan');

try {
    $svc   = new \Lms\TickService($d);
    $stats = $svc->runAll($dryRun);
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

$hasErrors = false;
$rowsAffected = 0;
foreach ($stats as $step) {
    if (!is_array($step)) {
        continue;
    }
    if (isset($step['errors']) && (int) $step['errors'] > 0) {
        $hasErrors = true;
    }
    if (isset($step['closed'])) {
        $rowsAffected += (int) $step['closed'];
    }
    if (isset($step['ticked'])) {
        $rowsAffected += (int) $step['ticked'];
    }
}

if ($runId !== null) {
    lms_cron_finish($d, $runId, $hasErrors ? 'failed' : 'success', $rowsAffected);
}

$config = lms_db_config();

echo json_encode(
    array(
        'time'    => date('Y-m-d H:i:s'),
        'db'      => isset($config['name']) ? $config['name'] : '',
        'dry_run' => $dryRun,
        'result'  => $hasErrors ? 'errors' : 'ok',
        'stats'   => $stats,
    ),
    JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT
), PHP_EOL;

exit($hasErrors ? 1 : 0);
