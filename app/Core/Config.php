<?php

namespace App\Core;

/**
 * Config Class
 * Quản lý việc truy xuất cấu hình từ thư mục /config
 */
class Config {
    protected static array $items = [];

    /**
     * Load toàn bộ cấu hình từ thư mục config
     */
    public static function load(string $path) {
        if (!is_dir($path)) return;

        $files = glob($path . '/*.php');
        foreach ($files as $file) {
            $key = basename($file, '.php');
            self::$items[$key] = require $file;
        }
    }

    /**
     * Lấy giá trị cấu hình (Dùng dot notation: app.name)
     */
    public static function get(string $key, $default = null) {
        $keys = explode('.', $key);
        $value = self::$items;

        foreach ($keys as $k) {
            if (!isset($value[$k])) return $default;
            $value = $value[$k];
        }

        return $value;
    }
}
