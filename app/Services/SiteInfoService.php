<?php

namespace App\Services;

/**
 * SiteInfoService
 * Thay thế sources/lib/info.php — load thông tin website từ DB
 * một lần duy nhất và cung cấp qua helper site().
 */
class SiteInfoService extends Service {
    
    protected static $instance = null;
    protected $data = null;

    /**
     * Singleton
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load toàn bộ thông tin website từ DB (cache trong request)
     */
    protected function loadData() {
        if ($this->data !== null) return;

        $setting = \SettingModel::query()->first();
        
        if ($setting) {
            $baseUrl = config('urls.base', '/');
            
            $this->data = [
                // Thông tin cơ bản
                'company'     => $setting->company ?? '',
                'website'     => $setting->website ?? '',
                'email'       => $setting->email ?? '',
                'dien_thoai'  => $setting->dien_thoai ?? '',
                'hotline'     => $setting->hotline ?? '',
                'address'     => $setting->address ?? '',
                'thoi_gian'   => $setting->thoi_gian ?? '',
                'coppy_right' => $setting->coppy_right ?? '',

                // Logo & Favicon
                'logo'    => $baseUrl . 'img_data/images/' . ($setting->icon_share ?? ''),
                'favicon' => $baseUrl . 'img_data/images/' . ($setting->favicon ?? ''),

                // Map
                'map'      => $setting->map ?? '',
                'link_map' => $setting->link_map ?? '',

                // Mạng xã hội
                'zalo'      => $setting->zalo ?? '',
                'messenger' => $setting->messenger ?? '',
                'skype'     => $setting->skype ?? '',
                'facebook'  => $setting->facebook ?? '',
                'twitter'   => $setting->twitter ?? '',
                'linkedin'  => $setting->linkedin ?? '',
                'youtube'   => $setting->youtube ?? '',
                'pinterest' => $setting->pinterest ?? '',
                'instagram' => $setting->instagram ?? '',
                'telegram'  => $setting->telegram ?? '',
                'whatsapp'  => $setting->whatsapp ?? '',
                'tiktok'    => $setting->tiktok ?? '',
                'shoppe'    => $setting->shoppe ?? '',

                // Recaptcha
                'site_key'   => $setting->site_key ?? '',
                'secret_key' => $setting->secret_key ?? '',

                // Raw object (cho legacy nếu cần)
                '_raw' => $setting,
            ];
        } else {
            $this->data = [];
        }
    }

    /**
     * Lấy giá trị theo key
     */
    public function get($key, $default = '') {
        $this->loadData();
        return $this->data[$key] ?? $default;
    }

    /**
     * Lấy toàn bộ data dưới dạng object (tương thích legacy $info)
     */
    public function all() {
        $this->loadData();
        return (object) $this->data;
    }
}
