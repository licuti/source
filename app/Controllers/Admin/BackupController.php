<?php

namespace App\Controllers\Admin;

use App\Core\Request;
use Model;

class BackupController extends BaseAdminController
{
    private $backupDir;
    private $logDir;

    public function __construct()
    {
        parent::__construct();
        $this->backupDir = dirname(dirname(dirname(__DIR__))) . '/storage/backups/';
        $this->logDir = dirname(dirname(dirname(__DIR__))) . '/storage/logs/';
        
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    public function index(Request $request)
    {
        $backups = [];
        if (is_dir($this->backupDir)) {
            // Láº¥y cáº£ .sql vÃ  .zip
            $files = glob($this->backupDir . '*.{sql,zip}', GLOB_BRACE);
            foreach ($files as $file) {
                $backups[] = [
                    'name' => basename($file),
                    'size' => filesize($file),
                    'time' => filemtime($file)
                ];
            }
            // Sort by newest first
            usort($backups, function($a, $b) {
                return $b['time'] - $a['time'];
            });
        }

        // Calculate log size
        $logSize = 0;
        if (is_dir($this->logDir)) {
            $files = glob($this->logDir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    $logSize += filesize($file);
                }
            }
        }

        return $this->render('admin.backup.index', compact('backups', 'logSize'));
    }

    public function createBackup(Request $request)
    {
        set_time_limit(300); // Allow 5 minutes
        $pdo = \App\Core\Database\Model::getConnection();
        
        $tables = [];
        $stmt = $pdo->query('SHOW TABLES');
        while ($row = $stmt->fetch(\PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql = "-- Database Backup\n";
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

        $filename = 'backup_db_' . date('Ymd_His') . '.sql';
        file_put_contents($this->backupDir . $filename, $sql);

        return $this->redirect(route('admin.backup.index'))->with('success', 'ÄÃ£ táº¡o báº£n sao lÆ°u CSDL thÃ nh cÃ´ng!');
    }

    public function createSourceBackup(Request $request)
    {
        set_time_limit(600); // Cho phÃ©p tá»‘i Ä‘a 10 phÃºt Ä‘á»ƒ nÃ©n
        
        $sourceDir = dirname(dirname(dirname(__DIR__))); // c:\laragon\www\source
        $filename = 'backup_source_' . date('Ymd_His') . '.zip';
        $zipPath = $this->backupDir . $filename;

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
            return $this->redirect(route('admin.backup.index'))->with('error', 'KhÃ´ng thá»ƒ táº¡o file Zip!');
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            // Bá» qua cÃ¡c thÆ° má»¥c khÃ´ng cáº§n thiáº¿t Ä‘á»ƒ file zip nháº¹ hÆ¡n
            $path = $item->getRealPath();
            $normalizedPath = str_replace('\\', '/', $path);
            
            if (
                strpos($normalizedPath, '/.git') !== false ||
                strpos($normalizedPath, '/.vscode') !== false ||
                strpos($normalizedPath, '/node_modules') !== false ||
                strpos($normalizedPath, '/storage') !== false ||
                strpos($normalizedPath, '/.gemini') !== false ||
                strpos($normalizedPath, '/.claude') !== false ||
                strpos($normalizedPath, '/.gitnexus') !== false ||
                pathinfo($path, PATHINFO_EXTENSION) === 'zip' ||
                !is_readable($path)
            ) {
                continue;
            }

            $relativePath = substr($path, strlen($sourceDir) + 1);
            
            if ($item->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($item->isFile()) {
                if (filesize($path) < 5 * 1024 * 1024) { // Nhá» hÆ¡n 5MB
                    $content = @file_get_contents($path);
                    if ($content !== false) {
                        $zip->addFromString($relativePath, $content);
                    }
                } else {
                    $zip->addFile($path, $relativePath);
                }
            }
        }

        $zip->close();

        return $this->redirect(route('admin.backup.index'))->with('success', 'ÄÃ£ nÃ©n mÃ£ nguá»“n thÃ nh cÃ´ng!');
    }

    public function downloadBackup(Request $request, $filename)
    {
        $filename = is_array($filename) ? ($filename['file'] ?? $filename[1] ?? '') : $filename;
        $file = $this->backupDir . basename($filename);
        if (file_exists($file)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="'.basename($file).'"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            exit;
        }
        return $this->redirect(route('admin.backup.index'))->with('error', 'KhÃ´ng tÃ¬m tháº¥y tá»‡p sao lÆ°u!');
    }

    public function restoreBackup(Request $request, $filename)
    {
        set_time_limit(600);
        $filename = is_array($filename) ? ($filename['file'] ?? $filename[1] ?? '') : $filename;
        $file = $this->backupDir . basename($filename);
        
        if (!file_exists($file) || pathinfo($file, PATHINFO_EXTENSION) !== 'sql') {
            return $this->redirect(route('admin.backup.index'))->with('error', 'Tá»‡p khÃ´ng tá»“n táº¡i hoáº·c khÃ´ng pháº£i lÃ  Ä‘á»‹nh dáº¡ng SQL!');
        }

        try {
            $pdo = \App\Core\Database\Model::getConnection();
            $sql = file_get_contents($file);
            
            // Táº¡m thá»i táº¯t check khÃ³a ngoáº¡i khi import
            $pdo->exec('SET FOREIGN_KEY_CHECKS=0;');
            
            // Chia nhá» query náº¿u file quÃ¡ lá»›n (phÃ¢n tÃ¡ch bá»Ÿi dáº¥u cháº¥m pháº©y vÃ  dÃ²ng má»›i)
            $queries = explode(";\n", $sql);
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query) && strpos($query, '--') !== 0) {
                    $pdo->exec($query);
                }
            }
            
            $pdo->exec('SET FOREIGN_KEY_CHECKS=1;');
            
            return $this->redirect(route('admin.backup.index'))->with('success', 'ÄÃ£ khÃ´i phá»¥c cÆ¡ sá»Ÿ dá»¯ liá»‡u thÃ nh cÃ´ng tá»« báº£n sao lÆ°u!');
        } catch (\Exception $e) {
            return $this->redirect(route('admin.backup.index'))->with('error', 'Lá»—i khÃ´i phá»¥c: ' . $e->getMessage());
        }
    }

    public function deleteBackup(Request $request, $filename)
    {
        $filename = is_array($filename) ? ($filename['file'] ?? $filename[1] ?? '') : $filename;
        $file = $this->backupDir . basename($filename);
        if (file_exists($file)) {
            unlink($file);
            return $this->redirect(route('admin.backup.index'))->with('success', 'ÄÃ£ xÃ³a báº£n sao lÆ°u thÃ nh cÃ´ng!');
        }
        return $this->redirect(route('admin.backup.index'))->with('error', 'KhÃ´ng tÃ¬m tháº¥y tá»‡p sao lÆ°u!');
    }

    public function clearCache(Request $request)
    {
        $type = $request->input('type', 'all');
        $deleted = 0;
        $message = '';

        if ($type === 'logs' || $type === 'all') {
            // Clear logs
            if (is_dir($this->logDir)) {
                $files = glob($this->logDir . '*');
                foreach ($files as $file) {
                    if (is_file($file) && basename($file) !== '.gitignore') {
                        unlink($file);
                        $deleted++;
                    }
                }
            }
            $message .= "ÄÃ£ xÃ³a $deleted tá»‡p log. ";
        }

        if ($type === 'opcache' || $type === 'all') {
            // Reset OPcache if available
            if (function_exists('opcache_reset')) {
                opcache_reset();
                $message .= "ÄÃ£ táº£i láº¡i bá»™ nhá»› OPcache.";
            } else {
                $message .= "MÃ¡y chá»§ khÃ´ng há»— trá»£ OPcache.";
            }
        }

        return $this->redirect(route('admin.backup.index'))->with('success', trim($message));
    }

    public function saveSettings(Request $request)
    {
        $settings = [
            'enabled' => (int)$request->input('enabled', 0),
            'interval_days' => (int)$request->input('interval_days', 7),
            'email' => $request->input('email', '')
        ];

        $file = dirname(dirname(dirname(__DIR__))) . '/storage/cron_settings.json';
        file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT));

        return $this->redirect(route('admin.backup.index'))->with('success', 'ÄÃ£ lÆ°u cáº¥u hÃ¬nh Cronjob tá»± Ä‘á»™ng!');
    }
}
