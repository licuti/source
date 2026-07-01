<?php
/**
 * ============================================================
 *  TIẾN TRÌNH TỰ ĐỘNG HÓA HỆ THỐNG (CRON)
 *  Thực thi tự động: Đổ CSDL, Dọn dẹp Log cũ
 *  Chạy: curl -s https://domain.com/cron.php?token=SECRET_KEY
 * ============================================================
 */
require_once __DIR__ . '/app/autoload.php';

// Bảo mật: Đặt chuỗi ngẫu nhiên khó đoán để làm khóa bảo vệ
$cronToken = 's4fe_cron_backup_2026_xyz'; 

if (!isset($_GET['token']) || $_GET['token'] !== $cronToken) {
    http_response_code(403);
    die("Access Denied.");
}

set_time_limit(600); // 10 phút

// Đọc cấu hình từ Admin
$cronSettingsFile = __DIR__ . '/storage/cron_settings.json';
$cronSettings = file_exists($cronSettingsFile) ? json_decode(file_get_contents($cronSettingsFile), true) : [];
$isEnabled = isset($cronSettings['enabled']) ? (int)$cronSettings['enabled'] : 0;
$intervalDays = isset($cronSettings['interval_days']) ? (int)$cronSettings['interval_days'] : 7;
$adminEmail = $cronSettings['email'] ?? '';

if (!$isEnabled) {
    die("Cronjob is disabled in settings.");
}

$backupDir = __DIR__ . '/storage/backups/';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// -----------------------------------------------------
// 1. TẠO BẢN SAO LƯU CSDL MỚI
// -----------------------------------------------------
try {
    $pdo = \Model::getConnection();
    
    $tables = [];
    $stmt = $pdo->query('SHOW TABLES');
    while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }

    $sql = "-- Database Backup (Auto Cron)\n";
    $sql .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW CREATE TABLE `$table`");
        $row = $stmt->fetch(\PDO::FETCH_NUM);
        $sql .= "DROP TABLE IF EXISTS `$table`;\n";
        $sql .= $row[1] . ";\n\n";

        $stmt = $pdo->query("SELECT * FROM `$table`");
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        if (count($rows) > 0) {
            $sql .= "INSERT INTO `$table` VALUES \n";
            $inserts = [];
            foreach ($rows as $r) {
                $values = [];
                foreach ($r as $val) {
                    if ($val === null) {
                        $values[] = "NULL";
                    } else {
                        $val = str_replace(["\\", "'", "\n", "\r"], ["\\\\", "\\'", "\\n", "\\r"], $val);
                        $values[] = "'$val'";
                    }
                }
                $inserts[] = "(" . implode(', ', $values) . ")";
            }
            $sql .= implode(",\n", $inserts) . ";\n\n";
        }
    }
    $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

    $filename = 'backup_' . date('Ymd') . '.sql';
    file_put_contents($backupDir . $filename, $sql);
    
    $status = 'SUCCESS';
    $message = "Đã sao lưu tự động thành công: $filename";
} catch (\Exception $e) {
    $status = 'ERROR';
    $message = "Lỗi khi sao lưu tự động: " . $e->getMessage();
}

// -----------------------------------------------------
// 2. XOAY VÒNG LOG (Xóa file cũ)
// -----------------------------------------------------
$deletedOldFiles = 0;
$files = glob($backupDir . '*.sql');
$now = time();
$limitDaysAgo = $now - ($intervalDays * 24 * 60 * 60);

foreach ($files as $file) {
    if (is_file($file)) {
        if (filemtime($file) < $limitDaysAgo) {
            unlink($file);
            $deletedOldFiles++;
        }
    }
}

if ($deletedOldFiles > 0) {
    $message .= " | Đã dọn dẹp $deletedOldFiles file backup cũ.";
}

// -----------------------------------------------------
// 3. TỰ ĐỘNG DỌN DẸP LOG CŨ
// -----------------------------------------------------
$logDir = __DIR__ . '/storage/logs/';
$logFiles = glob($logDir . 'app-*.log');
$deletedLogs = 0;

foreach ($logFiles as $lFile) {
    if (is_file($lFile)) {
        if (filemtime($lFile) < $limitDaysAgo) {
            unlink($lFile);
            $deletedLogs++;
        }
    }
}

if ($deletedLogs > 0) {
    $message .= " | Đã tự động dọn dẹp $deletedLogs file Log hệ thống cũ.";
}

// -----------------------------------------------------
// 4. GỬI EMAIL THÔNG BÁO (PLACEHOLDER)
// -----------------------------------------------------
function sendMailNotification($status, $message, $email) {
    if (empty($email)) return;
    
    // TODO: Chèn code gửi email (PHPMailer/SMTP) vào đây
    // $mailer->send($email, "Cron Status: $status", $message);
}

if (!empty($adminEmail)) {
    sendMailNotification($status, $message, $adminEmail);
}

// -----------------------------------------------------
// 5. KẾT THÚC VÀ TRẢ KẾT QUẢ
// -----------------------------------------------------
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'status' => $status,
    'message' => $message,
    'time' => date('Y-m-d H:i:s')
]);
exit;
