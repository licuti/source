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
        $defaultLang = config('app.locale', 'vi');

        if ($isAdmin) {
            // NGỮ CẢNH ADMIN:
            // Có thể lấy từ $_SESSION['admin_locale'] hoặc ép cứng về tiếng Việt
            $lang = $_SESSION['admin_locale'] ?? 'vi';
        } else {
            // NGỮ CẢNH FRONTEND (WEB):
            // Sử dụng một chìa khóa riêng biệt 'app_locale' để không bị Admin ghi đè
            $lang = $_SESSION['app_locale'] ?? $defaultLang;
            
            // 2. Nạp dữ liệu ngôn ngữ từ Database thông qua Model
            // Chuyển khối này lên trên để lấy danh sách ngôn ngữ hỗ trợ
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

            $supportedLangs = array_keys(config('lang', []));
            if (empty($supportedLangs)) $supportedLangs = ['vi', 'en'];

            // Nhận diện Subfolder URL: /en/product/t-shirt -> cắt 'en'
            $uriParts = explode('/', ltrim($request->uri, '/'));
            $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') 
                      || strpos($request->uri, '/ajax/') === 0 
                      || strpos($request->uri, '/api/') === 0;

            if (!empty($uriParts[0]) && in_array($uriParts[0], $supportedLangs)) {
                $lang = $uriParts[0];
                $_SESSION['app_locale'] = $lang;
                
                // Rewrite URI: bỏ /{lang}/ ra
                array_shift($uriParts);
                
                // Dịch slug đầu tiên về tiếng Việt để Router match đúng route
                // Ví dụ: /product/... → /san-pham/... (vì route đăng ký là /san-pham)
                if ($lang !== $defaultLang && !empty($uriParts[0])) {
                    $translations = config('route_translations', []);
                    foreach ($translations as $routeKey => $langs) {
                        if (isset($langs[$lang]) && $langs[$lang] === $uriParts[0]) {
                            $uriParts[0] = $langs[$defaultLang] ?? $uriParts[0];
                            break;
                        }
                    }
                }
                
                $request->uri = '/' . implode('/', $uriParts);
            } elseif (!$isAjax) {
                // Nếu URL không có tiền tố ngôn ngữ phụ và KHÔNG phải Ajax, ngầm định là ngôn ngữ mặc định (vi)
                // Cập nhật lại session để đồng bộ khi user chuyển về giao diện tiếng Việt
                $lang = $defaultLang;
                $_SESSION['app_locale'] = $lang;
            }
            
            // Hỗ trợ tham số ?lang=en (dự phòng)
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
