<?php
/**
 * ==========================================================================
 * admin/templates/components/_helpers.php
 *
 * Hạ tầng cho hệ thống component của trang admin.
 *
 * Ý tưởng: mỗi component là 1 file PHP nhỏ trong `admin/templates/components/`.
 * Ta gọi nó bằng `component('ten-component', [ ...props... ])`. Bên trong
 * component, các props được truy cập như biến thường (`$label`, `$name`...).
 *
 * Ví dụ:
 *      component('form/input', [
 *          'label' => 'Tên danh mục',
 *          'name'  => 'ten[]',
 *          'value' => $row['ten'],
 *      ]);
 *
 * Vì sao không dùng framework template? Dự án đang là PHP thuần, include
 * trực tiếp; giữ nguyên cách này để không phải đổi kiến trúc và không thêm
 * dependency.
 * ==========================================================================
 */

if (!defined('COMPONENTS_PATH')) {
    define('COMPONENTS_PATH', __DIR__);
}

if (!function_exists('e')) {
    /**
     * Escape dữ liệu trước khi in ra HTML (chống XSS).
     *
     * Dùng cho MỌI giá trị đến từ DB hoặc $_GET/$_POST.
     *
     * @param  mixed $value
     * @return string
     */
    function e($value)
    {
        if ($value === null || $value === false) {
            return '';
        }
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('component')) {
    /**
     * Render 1 component.
     *
     * @param  string $name  Đường dẫn tương đối trong components/, không cần .php
     *                       (vd: 'page_header', 'form/input')
     * @param  array  $props Dữ liệu truyền vào component
     * @return void
     */
    function component($name, array $props = [])
    {
        $file = COMPONENTS_PATH . '/' . ltrim($name, '/') . '.php';

        if (!is_file($file)) {
            // Hiện lỗi rõ ràng thay vì im lặng, giúp phát hiện typo khi dev.
            echo '<div class="alert alert-danger py-1 px-2 my-1">'
                . 'Component không tồn tại: <code>' . e($name) . '</code>'
                . '</div>';
            return;
        }

        // Tách scope: chỉ props được đưa vào biến, tránh rò rỉ biến ngoài.
        (static function ($__file, $__props) {
            extract($__props, EXTR_SKIP);
            include $__file;
        })($file, $props);
    }
}

if (!function_exists('component_render')) {
    /**
     * Giống component() nhưng trả về chuỗi thay vì in ra.
     * Hữu ích khi cần nhúng component vào 1 prop của component khác.
     *
     * @param  string $name
     * @param  array  $props
     * @return string
     */
    function component_render($name, array $props = [])
    {
        ob_start();
        component($name, $props);
        return ob_get_clean();
    }
}

if (!function_exists('attrs')) {
    /**
     * Build chuỗi HTML attribute từ mảng.
     *
     * - Giá trị `true`  -> in ra attribute dạng boolean (vd: `disabled`)
     * - Giá trị `false`/`null` -> bỏ qua
     *
     * @param  array $list ['class' => 'form-control', 'disabled' => true]
     * @return string
     */
    function attrs(array $list)
    {
        $out = [];

        foreach ($list as $key => $value) {
            if ($value === null || $value === false || $value === '') {
                continue;
            }
            if ($value === true) {
                $out[] = e($key);
                continue;
            }
            $out[] = e($key) . '="' . e($value) . '"';
        }

        return $out ? ' ' . implode(' ', $out) : '';
    }
}

if (!function_exists('admin_url')) {
    /**
     * Tạo URL nội bộ trang admin: admin_url(['p' => 'category', 'a' => 'edit']).
     *
     * @param  array $params
     * @return string
     */
    function admin_url(array $params = [])
    {
        $params = array_filter($params, static function ($v) {
            return $v !== null && $v !== '';
        });

        return 'index.php' . ($params ? '?' . http_build_query($params) : '');
    }
}

if (!function_exists('media_url')) {
    /**
     * URL file trong /media/{kind}/.
     * kind: image|images|file|files|video|videos|review|user|thumb|thumbs
     * Hỗ trợ path đã có prefix (media/..., uploads/...) hoặc URL tuyệt đối.
     */
    function media_url($file, $kind = 'image', $fallback = '')
    {
        $file = trim((string) $file);
        if ($file === '') {
            return $fallback;
        }
        // Absolute / protocol-relative URL
        $file = preg_replace('/^[\pZ\pC]+/u', '', $file);
        if (preg_match('#^(https?:)?//#i', $file) || strpos($file, 'http://') !== false || strpos($file, 'https://') !== false || strpos($file, 'data:') === 0) {
            return $file;
        }
        // Already a site-relative path with known roots
        $norm = str_replace(chr(92), '/', $file);
        if (preg_match('#^(?:\.\./)*(?:media|uploads|img_data)/#i', $norm)) {
            // normalize legacy img_data -> media
            $norm = preg_replace('#img_data/#i', 'media/', $norm);
            if (strpos($norm, '../') === 0 || strpos($norm, '/') === 0) {
                return $norm;
            }
            return '../' . ltrim($norm, '/');
        }

        $map = [
            'image'  => 'images',
            'images' => 'images',
            'file'   => 'files',
            'files'  => 'files',
            'video'  => 'videos',
            'videos' => 'videos',
            'review' => 'review',
            'user'   => 'user',
            'thumb'  => 'thumbs',
            'thumbs' => 'thumbs',
        ];
        $sub = $map[strtolower((string) $kind)] ?? 'images';

        // Auto-detect by extension when kind is auto
        if (strtolower((string) $kind) === 'auto') {
            $ext = strtolower(pathinfo($norm, PATHINFO_EXTENSION));
            $imgExt = ['jpg','jpeg','png','gif','webp','svg','bmp','ico'];
            $vidExt = ['mp4','webm','mov','avi','mkv','m4v'];
            if (in_array($ext, $vidExt, true)) {
                $sub = 'videos';
            } elseif (in_array($ext, $imgExt, true)) {
                $sub = 'images';
            } else {
                $sub = 'files';
            }
        }

        return '../media/' . $sub . '/' . ltrim($norm, '/');
    }
}

if (!function_exists('img_url')) {
    /**
     * URL ảnh trong thư mục media/images. Trả về ảnh mặc định nếu rỗng.
     *
     * @param  string|null $file
     * @param  string      $fallback
     * @return string
     */
    function img_url($file, $fallback = 'img/no-image.png')
    {
        $file = trim((string) $file);
        if ($file === '') {
            return $fallback;
        }
        return media_url($file, 'image', $fallback);
    }
}

if (!function_exists('admin_csrf_field')) {
    /** Hidden input CSRF — dùng trong mọi form POST admin. */
    function admin_csrf_field()
    {
        $t = function_exists('admin_csrf_token') ? admin_csrf_token(false) : '';
        return '<input type="hidden" name="csrf_token" value="' . e($t) . '">' . "\n";
    }
}

if (!function_exists('admin_csrf_input')) {
    /** Echo version of admin_csrf_field(). */
    function admin_csrf_input()
    {
        echo admin_csrf_field();
    }
}
