<?php
class TextModel extends Model {
    public $table = '#_text';
    public bool $timestamps = false;
    protected static $cache = null;

    /**
     * Tải toàn bộ bản dịch vào bộ nhớ (RAM)
     */
    public static function loadAll() {
        if (self::$cache === null) {
            self::$cache = [];
            // Lấy tất cả bản ghi (1 query duy nhất)
            $items = self::all();
            foreach ($items as $item) {
                // key_name có thể null cho các bản ghi cũ chưa được cập nhật
                $key = $item->key_name ?: "ID_" . $item->id;
                $translations = json_decode($item->text, true);
                self::$cache[$key] = is_array($translations) ? $translations : [];
            }
        }
    }

    protected static bool $isNewKeyAdded = false;

    /**
     * Lấy bản dịch theo Key
     */
    public static function translate($key) {
        self::loadAll();
        
        $lang = defined('LANG') ? LANG : 'vi';
        
        // 1. Tìm theo key_name
        if (isset(self::$cache[$key][$lang])) {
            return self::$cache[$key][$lang];
        }

        // 2. Tìm theo ID (Legacy support)
        $legacyKey = "ID_" . $key;
        if (isset(self::$cache[$legacyKey][$lang])) {
            return self::$cache[$legacyKey][$lang];
        }

        // 3. Nếu không tìm thấy và là key_name (không phải ID số), tự động thêm vào DB
        if (!is_numeric($key) && !isset(self::$cache[$key]) && !self::$isNewKeyAdded) {
            try {
                self::insert([
                    'key_name' => $key,
                    'text'     => json_encode(['vi' => $key, 'en' => $key], JSON_UNESCAPED_UNICODE)
                ]);
                // Ghi nhớ để không insert trùng trong 1 request
                self::$cache[$key] = ['vi' => $key, 'en' => $key];
            } catch (Exception $e) {
                // Bỏ qua nếu lỗi (ví dụ trùng key do race condition)
            }
        }

        return "[{$key}]";
    }

    /**
     * Cập nhật một chuỗi dịch
     */
    public static function updateTranslationAjax($id, $lang, $val) {
        $record = self::find($id);
        if (!$record) return false;

        $translations = json_decode($record->text, true) ?: [];
        $translations[$lang] = $val;
        
        return self::where('id', $id)->update([
            'text' => json_encode($translations, JSON_UNESCAPED_UNICODE)
        ]);
    }
}
?>
