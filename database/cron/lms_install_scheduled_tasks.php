<?php
/**
 * Lập lịch 3 cron LMS qua Windows Task Scheduler (máy dev / Windows production).
 *
 * Chạy 1 lần trong PowerShell (thường quyền, không cần admin):
 *   php database/cron/lms_install_scheduled_tasks.php
 *
 * Yêu cầu: Task Scheduler service chạy (mặc định trên Windows desktop).
 * Task chỉ chạy khi user đăng nhập (non-elevated). Đối với server Windows
 * không có user đăng nhập, chạy qua schtasks với /RU SYSTEM (cần admin).
 *
 * Xem/gỡ:
 *   Get-ScheduledTask -TaskName "LMS_*"
 *   Unregister-ScheduledTask -TaskName "LMS_session_generate" -Confirm:$false
 *
 * PHP 7.3 compatible.
 */

if (php_sapi_name() !== 'cli') {
    if (function_exists('http_response_code')) {
        http_response_code(403);
    }
    exit(1);
}

$php = PHP_BINARY;
$cronDir = __DIR__;

$jobs = array(
    // task name => array(mô tả, lệnh, trigger)
    'LMS_tick_scan' => array(
        'Quet vi pham (tick) moi 15 phut',
        escapeshellarg($php) . ' ' . escapeshellarg($cronDir . DIRECTORY_SEPARATOR . 'lms_tick_cron.php'),
        '/SC MINUTE /MO 15',
    ),
    'LMS_session_generate' => array(
        'Sinh buoi hoc (sessions) hang ngay luc 01:00',
        escapeshellarg($php) . ' ' . escapeshellarg($cronDir . DIRECTORY_SEPARATOR . 'lms_session_generate.php'),
        '/SC DAILY /ST 01:00',
    ),
    'LMS_payroll_close' => array(
        'Chot luong giao vien ngay 1 hang thang luc 00:15',
        escapeshellarg($php) . ' ' . escapeshellarg($cronDir . DIRECTORY_SEPARATOR . 'lms_payroll_cron.php'),
        '/SC MONTHLY /D 1 /ST 00:15',
    ),
);

echo "Dang lap lich " . count($jobs) . " cron LMS qua Task Scheduler...\n\n";

$okCount = 0;
foreach ($jobs as $name => $job) {
    list($desc, $cmd, $trigger) = $job;

    // Gỡ task cũ nếu có (idempotent)
    pclose(popen('schtasks /Delete /TN "' . $name . '" /F 2>nul', 'r'));

    $schtasks = 'schtasks /Create /TN "' . $name . '" /TR "' . $cmd . '" '
        . $trigger . ' /F';
    $output = array();
    $exit = 0;
    exec($schtasks . ' 2>&1', $output, $exit);

    $line = implode("\n", $output);
    $ok = ($exit === 0) && (strpos($line, 'SUCCESS') !== false || strpos($line, 'successfully') !== false);
    if ($ok) {
        $okCount++;
    }
    echo ($ok ? '[OK]  ' : '[FAIL]') . ' ' . $name . ' — ' . $desc . "\n";
    if (!$ok) {
        echo '      ' . str_replace("\n", "\n      ", trim($line)) . "\n";
    }
}

echo "\nXong: " . $okCount . '/' . count($jobs) . " task da lap lich.\n";
echo "Kiem tra: Get-ScheduledTask -TaskName \"LMS_*\"\n";
echo "Chay thu:  Start-ScheduledTask -TaskName LMS_session_generate\n";
exit($okCount === count($jobs) ? 0 : 1);
