<?php
/**
 * Trình phân tích biến môi trường (.env) siêu nhẹ
 */

if (!function_exists('env')) {
    function env($key, $default = null) {
        if (array_key_exists($key, $_ENV)) {
            $val = $_ENV[$key];
        } elseif (array_key_exists($key, $_SERVER)) {
            $val = $_SERVER[$key];
        } else {
            $val = getenv($key);
            if ($val === false) return $default;
        }

        // Loại bỏ nháy kép nếu có
        $val = trim((string)$val);
        if (preg_match('/^"(.*)"$/', $val, $matches)) {
            $val = $matches[1];
        } elseif (preg_match("/^'(.*)'$/", $val, $matches)) {
            $val = $matches[1];
        }

        // Normalize boolean / null / empty strings
        switch (strtolower($val)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return null;
        }

        return $val;
    }
}

if (!function_exists('loadEnv')) {
    function loadEnv($path) {
        if (!file_exists($path)) {
            return;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '#') === 0) {
                continue; // Bỏ qua comment và dòng trống
            }
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
}
