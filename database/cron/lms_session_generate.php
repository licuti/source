<?php
/**
 * Cron LMS — sinh buổi học (sessions) cho các tuần tiếp theo: ADM-03 §4.1.
 *
 * Mỗi ngày quét mọi lớp active, sinh sessions từ lịch cố định (db_lms_schedules)
 * trước DEFAULT_LOOKAHEAD_WEEKS (4) tuần, đồng bộ session_students cho học viên
 * active. Idempotent: UNIQUE(class_id, session_date, start_time) + check tồn tại
 * → chạy lại không tạo trùng.
 *
 * Chạy qua CLI mỗi ngày (Windows Task Scheduler / crontab):
 *   php database/cron/lms_session_generate.php
 *
 * Options:
 *   --dry-run        Chỉ thống kê, không ghi DB (xem trước).
 *   --db=<name>      Override tên DB (VD test: --db=phuongnamv_db_lms_test).
 *   --weeks=<n>      Số tuần nhìn trước (mặc định 4).
 *   --from=<Y-m-d>   Ngày bắt đầu khoảng sinh (mặc định hôm nay).
 *
 * Bảo mật: exit(1) nếu SAPI != cli; thư mục database/ cũng bị .htaccess chặn
 * truy cập web.
 *
 * Timezone: server cần date.timezone = Asia/Ho_Chi_Minh trong php.ini thì
 * "hôm nay" đúng theo giờ VN.
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

$dryRun  = false;
$dbName  = null;
$weeks   = \Lms\SessionService::DEFAULT_LOOKAHEAD_WEEKS;
$fromDate = null;

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--dry-run' || $arg === '-n') {
        $dryRun = true;
    } elseif (substr($arg, 0, 5) === '--db=') {
        $dbName = trim(substr($arg, 5));
    } elseif (substr($arg, 0, 8) === '--weeks=') {
        $weeks = (int) trim(substr($arg, 8));
    } elseif (substr($arg, 0, 7) === '--from=') {
        $fromDate = trim(substr($arg, 7));
    }
}

if ($weeks < 1) {
    $weeks = \Lms\SessionService::DEFAULT_LOOKAHEAD_WEEKS;
}
if ($fromDate !== null) {
    $parsedFrom = \DateTime::createFromFormat('!Y-m-d', $fromDate);
    if (!$parsedFrom || $parsedFrom->format('Y-m-d') !== $fromDate) {
        fwrite(STDERR, "Invalid --from date; expected YYYY-MM-DD.\n");
        exit(2);
    }
}

// Override DB PHẢI chạy trước khi lms_db() kết nối lần đầu (nó cache static).
if ($dbName !== null && $dbName !== '') {
    putenv('LMS_DB_NAME=' . $dbName);
}

$d = lms_db();

$runId = $dryRun ? null : lms_cron_start($d, 'session_generate');

try {
    $svc = new \Lms\SessionService($d);

    $stats = array(
        'weeks'               => $weeks,
        'from'                => $fromDate !== null ? $fromDate : date('Y-m-d'),
        'sessions_created'    => $svc->generateUpcoming($weeks, $fromDate, $dryRun),
        'session_students_created' => $dryRun
            ? $svc->estimateUpcomingSessionStudents($fromDate, $weeks)
            : $svc->ensureUpcomingSessionStudents($fromDate, $weeks, false),
    );
    $errors = false;
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

$rowsAffected = (int) $stats['sessions_created'] + (int) $stats['session_students_created'];

if ($runId !== null) {
    lms_cron_finish($d, $runId, $errors ? 'failed' : 'success', $rowsAffected);
}

$config = lms_db_config();

$stats['time']    = date('Y-m-d H:i:s');
$stats['db']      = isset($config['name']) ? $config['name'] : '';
$stats['dry_run'] = $dryRun;
$stats['result']  = $errors ? 'errors' : 'ok';

echo json_encode($stats, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), PHP_EOL;

exit($errors ? 1 : 0);
