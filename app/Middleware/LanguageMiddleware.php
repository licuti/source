<?php

namespace App\Middleware;

use LanguageModel;

/**
 * LanguageMiddleware
 * Đảm nhiệm việc xác định ngôn ngữ và nạp dữ liệu cấu hình ngôn ngữ từ DB.
 */
class LanguageMiddleware implements Middleware {
    /**
     * @param \App\Core\Request $request
     * @param \Closure $next
     */
    public function handle($request, $next) {
        // 1. Nhận diện Ngữ cảnh (Admin vs Frontend)
        $isAdmin = (strpos($request->uri, '/admin') === 0);
        // 2. Nạp dữ liệu ngôn ngữ từ Database thông qua Model
        if (config('lang') === null || empty(config('lang'))) {
            $dbLangs = \LanguageModel::where('is_active', 1)->get();
            if (!empty($dbLangs)) {
                $langs = [];
                foreach ($dbLangs as $item) {
                    $langs[$item->code] = [
                        'code'  => $item->code,
                        'name'  => $item->name,
                        'label' => $item->label,
                        'image' => $item->image,
                        'price' => $item->price_unit
                    ];
                }
                config(['lang' => $langs]);
            }
        }

        if ($isAdmin) {
            // NGỮ CẢNH ADMIN:
            $lang = $_SESSION['admin_locale'] ?? 'vi';
        } else {
            // NGỮ CẢNH FRONTEND (WEB):
            $defaultLang = config('app.locale', 'vi');
            $lang = $_SESSION['app_locale'] ?? $defaultLang;
            
            // Đọc cấu hình từ SettingModel
            $urlStyle = (new \App\Models\SettingModel())->getValue('url_lang_style', 'query');
            
            if ($urlStyle === 'path') {
                $segments = explode('/', trim($request->uri, '/'));
                if (!empty($segments[0])) {
                    $firstSeg = $segments[0];
                    $supportedLangs = config('lang') ?: [];
                    if (array_key_exists($firstSeg, $supportedLangs)) {
                        $lang = $firstSeg;
                        $_SESSION['app_locale'] = $lang;
                    } else {
                        // Nếu url không có prefix ngôn ngữ hợp lệ, mặc định là vi (hoặc defaultLang)
                        $lang = $defaultLang;
                        $_SESSION['app_locale'] = $lang;
                    }
                } else {
                    $lang = $defaultLang;
                    $_SESSION['app_locale'] = $lang;
                }
            }
            
            // Ưu tiên nạp từ tham số lang (áp dụng cho cả query style, hoặc fallback)
            if ($request->get('lang')) {
                $lang = $request->get('lang');
                $_SESSION['app_locale'] = $lang;
            }
        }

        // 3. Thiết lập ngôn ngữ thực tế cho Request hiện tại
        config(['app.locale' => $lang]);
        
        // 4. Đồng bộ hóa với Layer dữ liệu (Model)
        // Điều này giúp tất cả các câu Query tự động thêm "AND lang='$lang'"
        \Model::setGlobalConstraint("AND lang='$lang'");
        
        // Define legacy constant for old class.php functions
        if (!defined('_where_lang')) {
            define('_where_lang', " AND lang='$lang'");
        }

        // Tiếp tục chuyển request đến lớp tiếp theo
        return $next($request);
    }
}
