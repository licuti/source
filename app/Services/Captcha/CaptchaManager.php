<?php
namespace App\Services\Captcha;

class CaptchaManager {
    
    /**
     * @return CaptchaInterface|null
     */
    public static function getDriver() {
        // Lấy config từ hệ thống settings chung
        // Hàm setting() giả định lấy từ database db_settings (data_payload json)
        // Nếu dự án của bạn có helper setting() hoặc lấy trực tiếp từ SettingModel
        
        $provider = self::getSetting('captcha_provider', 'none');
        $siteKey = self::getSetting('captcha_site_key', '');
        $secretKey = self::getSetting('captcha_secret_key', '');
        
        if ($provider === 'recaptcha' && !empty($siteKey) && !empty($secretKey)) {
            return new GoogleReCaptchaV3($siteKey, $secretKey);
        }
        
        if ($provider === 'turnstile' && !empty($siteKey) && !empty($secretKey)) {
            return new CloudflareTurnstile($siteKey, $secretKey);
        }
        
        return null;
    }
    
    /**
     * Hàm helper nội bộ để lấy cấu hình từ SettingModel
     */
    private static function getSetting($key, $default = '') {
        try {
            $record = \App\Models\SettingModel::withoutLang()->where('lang', 'vi')->first();
            if ($record && !empty($record->data_payload)) {
                $payload = json_decode($record->data_payload, true);
                return isset($payload[$key]) ? $payload[$key] : $default;
            }
        } catch (\Exception $e) {
            // Ignored
        }
        return $default;
    }
}
