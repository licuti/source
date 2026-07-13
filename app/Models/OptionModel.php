<?php
namespace App\Models;

class OptionModel extends \App\Core\Database\Model {
    public $table = '#_options';
    
    public bool $timestamps = true;
    
    private static $optionsCache = null;

    /**
     * Tải toàn bộ cấu hình (autoload = 1) vào RAM
     */
    public static function loadAutoloadOptions() {
        if (self::$optionsCache === null) {
            $options = (new static)->where('autoload', 1)->get();
            self::$optionsCache = [];
            if ($options) {
                foreach ($options as $opt) {
                    self::$optionsCache[$opt->option_key] = $opt->option_value;
                }
            }
        }
    }

    /**
     * Lấy giá trị cấu hình theo Key
     */
    public static function getValue($key, $default = null) {
        self::loadAutoloadOptions();

        // Nếu có trong cache
        if (isset(self::$optionsCache[$key])) {
            return self::$optionsCache[$key];
        }

        // Nếu không có trong cache, query DB
        $record = (new static)->where('option_key', $key)->first();
        if ($record) {
            // Lưu vào cache để lần sau gọi nhanh hơn
            self::$optionsCache[$key] = $record->option_value;
            return $record->option_value;
        }

        return $default;
    }

    /**
     * Lưu/Cập nhật giá trị cấu hình
     */
    public static function setValue($key, $value, $autoload = 1) {
        $record = (new static)->where('option_key', $key)->first();
        if ($record) {
            // Update
            $record->option_value = $value;
            $record->autoload = $autoload;
            $record->save();
        } else {
            // Insert
            (new static)->insert([
                'option_key' => $key,
                'option_value' => $value,
                'autoload' => $autoload,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        // Cập nhật lại cache
        self::$optionsCache[$key] = $value;
        return true;
    }
}
