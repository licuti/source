<?php
namespace App\Core;

use App\Core\Contracts\LoggerInterface;

class Logger implements LoggerInterface {
    protected $logPath;

    public function __construct() {
        $this->logPath = dirname(dirname(__DIR__)) . '/storage/logs/';
    }

    public function log(string $level, string $message, array $context = []) {
        $logFile = $this->logPath . 'app-' . date('Y-m-d') . '.log';
        if (!is_dir($this->logPath)) {
            mkdir($this->logPath, 0777, true);
        }
        $time = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        $formattedMessage = "[$time] [" . strtoupper($level) . "]: $message$contextStr" . PHP_EOL;
        
        file_put_contents($logFile, $formattedMessage, FILE_APPEND);
        $this->rotateLogs();
    }

    public function debug(string $message, array $context = []) { $this->log('DEBUG', $message, $context); }
    public function info(string $message, array $context = []) { $this->log('INFO', $message, $context); }
    public function notice(string $message, array $context = []) { $this->log('NOTICE', $message, $context); }
    public function warning(string $message, array $context = []) { $this->log('WARNING', $message, $context); }
    public function error(string $message, array $context = []) { $this->log('ERROR', $message, $context); }
    public function critical(string $message, array $context = []) { $this->log('CRITICAL', $message, $context); }
    public function alert(string $message, array $context = []) { $this->log('ALERT', $message, $context); }
    public function emergency(string $message, array $context = []) { $this->log('EMERGENCY', $message, $context); }

    protected function rotateLogs(int $maxDays = 30) {
        // Simple rotation: delete logs older than $maxDays
        if (rand(1, 100) !== 1) return; // Only run rotation 1% of the time to save performance
        $files = glob($this->logPath . 'app-*.log');
        $now = time();
        foreach ($files as $file) {
            if (is_file($file)) {
                if ($now - filemtime($file) >= 60 * 60 * 24 * $maxDays) {
                    unlink($file);
                }
            }
        }
    }

    // Static wrappers for backward compatibility
    public static function __callStatic($name, $arguments) {
        $instance = new static();
        if (method_exists($instance, $name)) {
            return $instance->$name(...$arguments);
        }
        if ($name === 'log') {
            return $instance->log($arguments[1] ?? 'INFO', $arguments[0], $arguments[2] ?? []);
        }
        throw new \BadMethodCallException("Method $name does not exist.");
    }
}
