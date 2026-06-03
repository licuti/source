<?php
/**
 * ============================================================
 *  URL HELPERS
 *  Các hàm xử lý đường dẫn và tài nguyên.
 * ============================================================
 */

if (!function_exists('url')) {
    /**
     * Tạo URL tuyệt đối chuẩn xác
     */
    function url($path = '') {
        if (!is_string($path)) $path = '';
        
        // Nếu là URL tuyệt đối (http, https, //) thì trả về luôn
        if (preg_match('/^(http|https|\/\/)/i', $path)) {
            return $path;
        }

        $path = ltrim($path, '/');
        $baseUrl = defined('URLPATH') ? URLPATH : config('urls.base', '/');
        return rtrim($baseUrl, '/') . '/' . $path;
    }
}

if (!function_exists('asset')) {
    /**
     * Trỏ đến file tĩnh (CSS, JS, Images) trong thư mục assets
     */
    function asset($path) {
        $path = ltrim($path, '/');
        // Cho phép thay đổi thư mục asset qua config sau này
        $assetBase = config('urls.assets', 'assets/');
        return url($assetBase . $path);
    }
}

if (!function_exists('admin_url')) {
    /**
     * Tạo đường dẫn vào trang Admin
     */
    function admin_url($path = '') {
        $path = ltrim($path, '/');
        $adminBase = defined('urladmin') ? urladmin : config('urls.admin', '/admin/');
        return $adminBase . $path;
    }
}
if (!function_exists('getImageUrl')) {
    /**
     * Lấy đường dẫn ảnh chuẩn xác
     */
    function getImageUrl($filename) {
        if (empty(trim($filename))) {
            return url('img_data/no-image.png');
        }
        return url('img_data/images/' . $filename);
    }
}

if (!function_exists('Img')) {
    /**
     * Alias cho getImageUrl (Legacy support)
     */
    function Img($img) {
        return getImageUrl($img);
    }
}

if (!function_exists('route')) {
    /**
     * Sinh URL từ tên Route.
     *
     * - Tự động dịch slug prefix (san-pham → product) dựa vào config/route_translations.php
     * - Tự động thêm /{lang}/ nếu không phải ngôn ngữ mặc định (vi)
     *
     * Ví dụ:
     *   route('product.show', 'ao-thun')  → /san-pham/ao-thun  (locale=vi)
     *   route('product.show', 'ao-thun')  → /en/product/ao-thun (locale=en)
     */
    function route($name, $parameters = []) {
        $router  = \App\Core\App::getInstance()->router;
        $locale  = $_SESSION['app_locale'] ?? config('app.locale', 'vi');

        $path = $router->getNamedRoute($name);
        if (!$path) {
            return url('#route-not-found-' . $name);
        }

        // Dịch slug prefix nếu không phải ngôn ngữ mặc định (vi)
        if ($locale !== 'vi') {
            $translations = config('route_translations', []);
            foreach ($translations as $key => $langs) {
                $viSlug = $langs['vi'] ?? null;
                $toLang = $langs[$locale] ?? null;
                if ($viSlug && $toLang) {
                    // Thay thế /san-pham → /product trong path
                    $path = preg_replace('#^/' . preg_quote($viSlug, '#') . '(/?|/{.*)$#', '/' . $toLang . '$1', $path);
                }
            }
        }

        // Thay thế {param} trong path
        if (!is_array($parameters)) {
            $parameters = ['slug' => $parameters];
        }
        foreach ($parameters as $key => $val) {
            $path = preg_replace('/\{' . $key . '\}/', $val, $path);
        }
        $path = preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $path); // Xóa param dư thừa

        $path = ltrim($path, '/');

        // Thêm prefix /{lang}/ nếu không phải ngôn ngữ mặc định
        if ($locale !== 'vi') {
            $path = $locale . '/' . $path;
        }

        return url($path);
    }
}

if (!function_exists('url_lang')) {
    /**
     * Tạo URL chuyển đổi ngôn ngữ cho trang hiện tại
     */
    function url_lang($langCode) {
        // Lấy link đồng bộ ngôn ngữ từ Controller (nếu có)
        $links = \App\Core\App::getInstance()->getLanguageLinks();
        if (!empty($links) && isset($links[$langCode])) {
            return $links[$langCode];
        }

        // Fallback: Trả về trang chủ của ngôn ngữ tương ứng
        $path = '';
        if ($langCode !== 'vi') {
            $path = $langCode . '/';
        }
        return url($path);
    }
}
