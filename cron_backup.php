<?php
/**
 * ============================================================
 *  Cronjob Tự động Sao lưu CSDL & Xoay vòng Log
 *  Chạy: curl -s https://domain.com/cron_backup.php?token=SECRET_KEY
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

    $filename = 'backup_db_auto_' . date('Ymd_His') . '.sql';
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
// 3. GỬI EMAIL THÔNG BÁO (PLACEHOLDER)
// -----------------------------------------------------
function sendMailNotification($status, $message, $email) {
    if (empty($email)) return;
    
    // TODO: Khi setup xong cấu hình SMTP, viết code gửi mail ở đây
    // Ví dụ:
    // $mailer = new \App\Libraries\Mailer();
    // $mailer->send($email, "Cron Backup: $status", $message);
    
    // Tạm thời ghi log
    $logFile = __DIR__ . '/storage/logs/cron_backup.log';
    $logMsg = "[" . date('Y-m-d H:i:s') . "] [$status] Sent to $email: $message" . PHP_EOL;
    file_put_contents($logFile, $logMsg, FILE_APPEND);
}

sendMailNotification($status, $message, $adminEmail);

// Output cho trình duyệt hoặc terminal
echo "Cronjob Finished. Status: $status. Message: $message";
