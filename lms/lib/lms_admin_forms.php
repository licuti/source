<?php
/**
 * Helper chung cho các module CRUD portal Admin LMS (ADM-01, ADM-02, ...).
 *
 * Tách code lặp lại giữa các source module:
 *  - lms_form_guard(): check quyền + flash + redirect khi thiếu quyền.
 *  - lms_pagination(): render thanh phân trang (query string giữ nguyên filter).
 *  - lms_field_error(): render thông báo lỗi 1 trường form.
 *  - lms_form_old(): lấy giá trị sticky cho form (ưu tiên input vừa submit).
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

if (!function_exists('lms_pagination')) {
    /**
     * Render thanh phân trang dạng « 1 2 3 ».
     *
     * @param string $baseUrl  URL gốc (không chứa query page)
     * @param int    $page     trang hiện tại
     * @param int    $totalPages
     * @param array  $params   query giữ nguyên khi chuyển trang (filter)
     * @return string HTML
     */
    function lms_pagination($baseUrl, $page, $totalPages, array $params = array())
    {
        $page = max(1, (int) $page);
        $totalPages = max(1, (int) $totalPages);
        if ($totalPages <= 1) {
            return '';
        }

        $url = function ($n) use ($baseUrl, $params) {
            $q = array_merge($params, array('page' => $n));
            return $baseUrl . '?' . http_build_query($q);
        };

        $out = '<nav class="lms-pagination" aria-label="Phân trang"><ul>';

        if ($page > 1) {
            $out .= '<li><a href="' . e($url($page - 1)) . '" rel="prev" aria-label="Trang trước">«</a></li>';
        } else {
            $out .= '<li class="disabled"><span aria-hidden="true">«</span></li>';
        }

        // Hiển thị tối đa 7 trang xung quanh trang hiện tại
        $start = max(1, $page - 3);
        $end = min($totalPages, $page + 3);
        if ($start > 1) {
            $out .= '<li><a href="' . e($url(1)) . '">1</a></li>';
            if ($start > 2) {
                $out .= '<li class="disabled"><span>…</span></li>';
            }
        }
        for ($i = $start; $i <= $end; $i++) {
            if ($i === $page) {
                $out .= '<li class="active" aria-current="page"><span>' . $i . '</span></li>';
            } else {
                $out .= '<li><a href="' . e($url($i)) . '">' . $i . '</a></li>';
            }
        }
        if ($end < $totalPages) {
            if ($end < $totalPages - 1) {
                $out .= '<li class="disabled"><span>…</span></li>';
            }
            $out .= '<li><a href="' . e($url($totalPages)) . '">' . $totalPages . '</a></li>';
        }

        if ($page < $totalPages) {
            $out .= '<li><a href="' . e($url($page + 1)) . '" rel="next" aria-label="Trang sau">»</a></li>';
        } else {
            $out .= '<li class="disabled"><span aria-hidden="true">»</span></li>';
        }

        $out .= '</ul></nav>';
        return $out;
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
