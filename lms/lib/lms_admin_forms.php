<?php
/**
 * Helper chung cho các module CRUD portal Admin LMS (ADM-01, ADM-02, ...).
 *
 * Tách code lặp lại giữa các source module:
 *  - lms_form_guard(): check quyền + flash + redirect khi thiếu quyền.
 *  - lms_field_error(): render thông báo lỗi 1 trường form.
 *  - lms_form_old(): lấy giá trị sticky cho form (ưu tiên input vừa submit).
 *
 * Phân trang: gọi thẳng component `lms_component('pagination', ...)`
 * (component đã tự thêm class mt-4, không cần wrapper trung gian).
 *
 * KHÔNG chứa logic nghiệp vụ — phần đó nằm trong service layer.
 * PHP 7.3 compatible.
 */

if (!function_exists('lms_form_guard')) {
    /**
     * Kiểm tra quyền thao tác; thiếu quyền → flash + redirect về trang danh sách.
     *
     * @param LmsDb  $d
     * @param array  $user
     * @param string $alias
     * @param string $permAction 'view' | 'edit' | 'delete'
     * @param string $portal
     * @return void (exit khi không đủ quyền)
     */
    function lms_form_guard(LmsDb $d, array $user, $alias, $permAction, $portal = 'admin')
    {
        if (lms_check_permission($d, $user, $alias, $permAction, $portal)) {
            return;
        }
        lms_flash('error', 'Bạn không có quyền ' . ($permAction === 'view' ? 'xem' : ($permAction === 'delete' ? 'xoá' : 'chỉnh sửa')) . ' mục này.');
        header('Location: ' . lms_url($alias));
        exit;
    }
}

if (!function_exists('lms_field_error')) {
    /**
     * Thông báo lỗi 1 trường (dùng dưới input form).
     *
     * @param array  $errors  field => message
     * @param string $field
     * @return string HTML (rỗng nếu không lỗi)
     */
    function lms_field_error(array $errors, $field)
    {
        if (!isset($errors[$field])) {
            return '';
        }
        return '<div class="invalid-feedback d-block"><i class="fa-solid fa-circle-exclamation me-1"></i>'
            . e((string) $errors[$field]) . '</div>';
    }
}

if (!function_exists('lms_form_old')) {
    /**
     * Giá trị sticky cho form: ưu tiên input vừa submit (POST), rồi record DB.
     *
     * @param array  $post    $_POST (hoặc mảng submit)
     * @param array  $record  row từ DB
     * @param string $key
     * @param string $default
     * @return string
     */
    function lms_form_old(array $post, array $record, $key, $default = '')
    {
        if (isset($post[$key])) {
            return (string) $post[$key];
        }
        if (isset($record[$key])) {
            return (string) $record[$key];
        }
        return (string) $default;
    }
}

if (!function_exists('lms_checked')) {
    /**
     * Trả về 'checked' khi giá trị truthy (dùng cho checkbox).
     *
     * @param mixed $value
     * @return string
     */
    function lms_checked($value)
    {
        return !empty($value) ? ' checked' : '';
    }
}

if (!function_exists('lms_selected')) {
    /**
     * Trả về 'selected' khi $current khớp $value (so sánh chuỗi lỏng).
     *
     * @param mixed $current
     * @param mixed $value
     * @return string
     */
    function lms_selected($current, $value)
    {
        return (string) $current === (string) $value ? ' selected' : '';
    }
}

if (!function_exists('lms_form_old_array')) {
    /**
     * Giá trị sticky dạng mảng cho multi-select: ưu tiên input vừa submit, rồi record.
     * Trả về mảng chuỗi (an toàn khi value là mảng hoặc scalar).
     *
     * @param array  $post
     * @param array  $record
     * @param string $key
     * @return array
     */
    function lms_form_old_array(array $post, array $record, $key)
    {
        $value = null;
        if (isset($post[$key])) {
            $value = $post[$key];
        } elseif (isset($record[$key])) {
            $value = $record[$key];
        }
        if ($value === null) {
            return array();
        }
        if (is_array($value)) {
            return array_map('strval', $value);
        }
        return array((string) $value);
    }
}

if (!function_exists('lms_in_selected')) {
    /**
     * 'selected' khi $value nằm trong mảng (dùng cho multi-select).
     *
     * @param mixed $value
     * @param array $haystack
     * @return string
     */
    function lms_in_selected($value, array $haystack)
    {
        return in_array((string) $value, array_map('strval', $haystack), true) ? ' selected' : '';
    }
}

// ===================== LMS Form Component Infrastructure =====================

if (!defined('LMS_COMPONENTS_PATH')) {
    define('LMS_COMPONENTS_PATH', _LMS_DIR . '/app/admin/templates/components');
}

if (!function_exists('lms_component')) {
    /**
     * Render 1 component trong LMS.
     * Tự động truyền context $row và $errors từ view nếu không truyền tường minh.
     *
     * @param string $name  Đường dẫn tương đối trong components/, không cần .php (VD 'form/input')
     * @param array  $props Dữ liệu truyền vào component
     * @return void
     */
    function lms_component($name, array $props = array())
    {
        $file = LMS_COMPONENTS_PATH . '/' . ltrim($name, '/') . '.php';
        if (!is_file($file)) {
            echo '<div class="alert alert-danger py-1 px-2 my-1">'
                . 'LMS Component không tồn tại: <code>' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</code>'
                . '</div>';
            return;
        }

        // Tự động kế thừa $row và $errors từ global scope nếu template cha có
        global $row, $errors;
        if (!isset($props['row']) && isset($row)) {
            $props['row'] = $row;
        }
        if (!isset($props['errors']) && isset($errors)) {
            $props['errors'] = $errors;
        }

        (static function ($__file, $__props) {
            extract($__props, EXTR_SKIP);
            include $__file;
        })($file, $props);
    }
}

if (!function_exists('lms_component_render')) {
    /**
     * Render 1 component và trả về chuỗi HTML.
     *
     * @param string $name
     * @param array  $props
     * @return string
     */
    function lms_component_render($name, array $props = array())
    {
        ob_start();
        lms_component($name, $props);
        return ob_get_clean();
    }
}



// ===================== Helper định dạng ngày giờ chuẩn =====================

if (!function_exists('lms_format_date')) {
    /**
     * Format ngày 'Y-m-d' → 'd/m/Y' an toàn cho view.
     *
     * @param mixed  $date
     * @param string $default Mặc định khi rỗng hoặc không hợp lệ ('—')
     * @return string
     */
    function lms_format_date($date, $default = '—')
    {
        $date = (string) $date;
        if ($date === '' || $date === '0000-00-00') {
            return $default;
        }
        $ts = strtotime($date);
        return $ts === false ? htmlspecialchars($date, ENT_QUOTES, 'UTF-8') : date('d/m/Y', $ts);
    }
}

if (!function_exists('lms_format_datetime')) {
    /**
     * Format datetime 'Y-m-d H:i:s' → 'd/m/Y H:i' an toàn cho view.
     *
     * @param mixed  $dt
     * @param string $format Mặc định: 'd/m/Y H:i'
     * @param string $default Mặc định khi rỗng hoặc không hợp lệ ('—')
     * @return string
     */
    function lms_format_datetime($dt, $format = 'd/m/Y H:i', $default = '—')
    {
        $dt = (string) $dt;
        if ($dt === '' || $dt === '0000-00-00 00:00:00') {
            return $default;
        }
        $ts = strtotime($dt);
        return $ts === false ? htmlspecialchars($dt, ENT_QUOTES, 'UTF-8') : date($format, $ts);
    }
}
