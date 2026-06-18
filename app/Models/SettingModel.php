<?php
namespace App\Models;

class SettingModel extends \Model {
    public $table = '#_settings';
    public bool $timestamps = false; // Let DB handle created_at / updated_at
    private static $settings_cache = null;

    protected array $casts = [
        'schema_config' => 'json',
        'data_payload' => 'json'
    ];

    /**
     * Lấy toàn bộ cấu hình website
     */
    public function getAll() {
        if (self::$settings_cache === null) {
            $lang = defined('LANG') ? LANG : 'vi';
            $record = static::withoutLang()->where('lang', $lang)->first();
            
            if ($record) {
                $data = $record->toArray();
                $payload = $record->data_payload;
                if (is_array($payload)) {
                    $data = array_merge($data, $payload);
                }
                self::$settings_cache = $data;
            } else {
                self::$settings_cache = [];
            }
        }
        return self::$settings_cache;
    }

    public function setSchemaConfigAttribute($value) {
        $this->attributes['schema_config'] = is_string($value) ? $value : json_encode(empty($value) ? new \stdClass() : $value, JSON_UNESCAPED_UNICODE);
    }

    public function setDataPayloadAttribute($value) {
        $this->attributes['data_payload'] = is_string($value) ? $value : json_encode(empty($value) ? new \stdClass() : $value, JSON_UNESCAPED_UNICODE);
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
        $logo = $this->getValue('logo_image');
        return (defined('URLPATH') ? URLPATH : '') . 'img_data/images/' . $logo;
    }

    /**
     * Lấy toàn bộ các field thuộc một nhóm (Tab) cụ thể
     * Tiện lợi cho việc render động danh sách (Vd: Mạng xã hội, Liên hệ)
     */
    public function getGroup($tabName) {
        $settings = $this->getAll();
        $schema = isset($settings['schema_config']) ? $settings['schema_config'] : [];
        if (is_string($schema)) {
            $schema = json_decode($schema, true) ?: [];
        }
        
        $result = [];
        foreach ($schema as $field) {
            $tab = !empty($field['tab']) ? trim($field['tab']) : 'Thông tin chung';
            if ($tab === $tabName) {
                $name = $field['name'] ?? '';
                if ($name) {
                    $result[$name] = [
                        'label' => $field['label'] ?? '',
                        'type'  => $field['type'] ?? 'text',
                        'value' => $settings[$name] ?? ''
                    ];
                }
            }
        }
        return $result;
    }
}
