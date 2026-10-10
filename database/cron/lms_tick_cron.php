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

// ===================== Parse tham số =====================

$dryRun = false;
$dbName = null;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run' || $arg === '-n') {
        $dryRun = true;
    } elseif (substr($arg, 0, 5) === '--db=') {
        $dbName = trim(substr($arg, 5));
    }
}

// Override DB PHẢI chạy trước khi lms_db() kết nối lần đầu.
if ($dbName !== null && $dbName !== '') {
    putenv('LMS_DB_NAME=' . $dbName);
}

// ===================== Chạy pipeline =====================

$svc   = new \Lms\TickService(lms_db());
$stats = $svc->runAll($dryRun);

// ===================== Báo cáo =====================

$hasErrors = false;
foreach ($stats as $step) {
    if (is_array($step) && isset($step['errors']) && (int) $step['errors'] > 0) {
        $hasErrors = true;
    }
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
