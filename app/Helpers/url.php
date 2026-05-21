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
        $path = $router->getNamedRoute($name);
        
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
        // Lấy URI hiện tại (loại bỏ query string nếu cần hoặc giữ lại)
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        
        // Loại bỏ base path nếu có (ví dụ: /source/) để xử lý slug
        $baseUrl = config('urls.base', '/');
        $basePath = parse_url($baseUrl, PHP_URL_PATH) ?: '/';
        $relativeUri = ltrim(str_replace($basePath, '', $uri), '/');
        
        // Tách các phần của URI
        $parts = explode('/', $relativeUri);
        
        // Danh sách ngôn ngữ hỗ trợ (Lấy từ cột 'code')
        $langConfig = config('lang', []);
        $supportedLangs = array_column($langConfig, 'code');
        if (empty($supportedLangs)) {
            $supportedLangs = ['vi', 'en'];
        }
        
        // Nếu phần đầu tiên là ngôn ngữ, thay thế nó
        if (!empty($parts[0]) && in_array($parts[0], $supportedLangs)) {
            $parts[0] = $langCode;
        } else {
            // Nếu chưa có ngôn ngữ trên URL, chèn thêm vào đầu
            array_unshift($parts, $langCode);
        }
        
        return url(implode('/', $parts));
    }
}
