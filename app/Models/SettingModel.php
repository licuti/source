<?php
class SettingModel extends Model {
    public $table = '#_thongtin';
    private static $settings_cache = null;

    /**
     * Lấy toàn bộ cấu hình website
     */
    public function getAll() {
        if (self::$settings_cache === null) {
            $sql = "SELECT * FROM {$this->table} WHERE lang = '" . (defined('LANG') ? LANG : 'vi') . "' LIMIT 1";
            self::$settings_cache = $this->simple_fetch($sql);
        }
        return self::$settings_cache;
    }

    /**
     * Lấy giá trị cấu hình theo Key
     */
    public function getValue($key, $default = '') {
        $settings = $this->getAll();
        return $settings[$key] ?? $default;
    }

    /**
     * Helper: Lấy URL logo
     */
    public function getLogo() {
        $logo = $this->getValue('icon_share');
        return (defined('URLPATH') ? URLPATH : '') . 'img_data/images/' . $logo;
    }
}
