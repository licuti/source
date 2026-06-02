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
            
            // Nếu có tham số lang trên URL (ví dụ: ?lang=en), ưu tiên nạp và lưu lại
            if ($request->get('lang')) {
                $lang = $request->get('lang');
                $_SESSION['app_locale'] = $lang;
            }
        }
        
        // 2. Nạp dữ liệu ngôn ngữ từ Database thông qua Model
        // (Chỉ nạp nếu cần thiết để tối ưu Performance)
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
