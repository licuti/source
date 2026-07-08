<?php
namespace App\Models;

class LanguageModel extends \App\Core\Model {
    public $table = '#_lang';

    protected static $current = null;

    /**
     * Lấy Object ngôn ngữ hiện tại đang sử dụng (dựa trên hằng số _lang)
     */
    public static function current() {
        if (self::$current === null && defined('_lang')) {
            self::$current = static::query()->where('code', _lang)->first();
        }
        return self::$current;
    }

    /**
     * Lấy tất cả ngôn ngữ đang kích hoạt, sắp xếp theo thứ tự
     */
    public static function getActive() {
        return static::query()
                   ->where('is_active', 1)
                   ->orderBy('sort_order', 'ASC')
                   ->get();
    }

    /**
     * Lấy ngôn ngữ được thiết lập làm mặc định
     */
    public static function getDefault() {
        return static::query()->where('is_default', 1)->first();
    }

    /**
     * Trả về mảng Map để tra cứu nhanh theo mã code
     * Kết quả: [ 'vi' => Object, 'en' => Object ]
     */
    public static function getCodeMap() {
        $langs = static::query()->orderBy('sort_order', 'ASC')->get();
        $map = [];
        foreach ($langs as $l) {
            $map[$l->code] = $l;
        }
        return $map;
    }

    /**
     * Thiết lập một ngôn ngữ làm mặc định và hủy mặc định các ngôn ngữ khác
     */
    public static function setAsDefault($id) {
        // 1. Bỏ mặc định tất cả
        static::query()->whereRaw("1=1")->update(['is_default' => 0]);
        
        // 2. Thiết lập mặc định cho ID chỉ định
        $lang = static::find($id);
        if ($lang) {
            $lang->update(['is_default' => 1]);
            return true;
        }
        return false;
    }
}
