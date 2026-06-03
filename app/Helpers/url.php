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
     * Khởi tạo URL từ tên Route đã đăng ký trong web.php
     * Ví dụ: route('product.show', ['slug' => 'ao-thun']) 
     *     hoặc route('product.show', 'ao-thun')
     */
    function route($name, $parameters = []) {
        $router = \App\Core\App::getInstance()->router;
        
        // Try localized route first
        $locale = $_SESSION['app_locale'] ?? config('app.locale', 'vi');
        $localizedName = $name . '.' . $locale;
        
        $path = $router->getNamedRoute($localizedName);
        if (!$path) {
            $path = $router->getNamedRoute($name);
        }
        
        if (!$path) {
            return url('#route-not-found-' . $name);
        }

        // Nếu tham số truyền vào không phải mảng, ngầm định thay cho {slug} hoặc param đầu tiên
        if (!is_array($parameters)) {
            $parameters = ['slug' => $parameters];
        }

        // Thay thế các placeholder {param}
        foreach ($parameters as $key => $val) {
            $path = preg_replace('/\{' . $key . '\}/', $val, $path);
        }

        // Xóa các param dư thừa không được truyền
        $path = preg_replace('/\{[a-zA-Z0-9_]+\}/', '', $path);

        return url(ltrim($path, '/'));
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

        // Fallback: Trả về trang chủ kèm theo tham số ngôn ngữ
        // Việc này giúp tránh lỗi 404 do các slug cũ không tồn tại trong ngôn ngữ mới
        return url('?lang=' . $langCode);
    }
}
