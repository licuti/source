<?php

namespace App\Core;

/**
 * Logger Class
 * Ghi log hệ thống vào /storage/logs
 */
class Logger {
    public static function log(string $message, string $level = 'info') {
        $logPath = dirname(dirname(__DIR__)) . '/storage/logs/app.log';
        $time = date('Y-m-d H:i:s');
        $formattedMessage = "[$time] [$level]: $message" . PHP_EOL;
        
        file_put_contents($logPath, $formattedMessage, FILE_APPEND);
    }

    public static function info(string $message) { self::log($message, 'INFO'); }
    public static function error(string $message) { self::log($message, 'ERROR'); }
}
