<?php
/**
 * Helper URL cho LMS (D10 — clean URL, không viết tay link trong template).
 *
 * Quy ước (Huong-Dan-Phat-Trien-Page.md §3.3.1 + §3.3.3):
 *   /lms/<portal>                       dashboard
 *   /lms/<portal>/login | /logout       action thuần (không thuộc module)
 *   /lms/<portal>/<alias>[/<action>]    module
 *
 * Base suy ra từ SCRIPT_NAME của entry point (chính xác cả khi deploy subfolder,
 * không phụ thuộc admin/ e-commerce — D2/P4).
 * PHP 7.3 compatible.
 */

if (!function_exists('lms_base_url')) {
    /**
     * Base path của LMS (VD: '/lms' hoặc '/source/lms').
     *
     * @return string
     */
    function lms_base_url()
    {
        static $base = null;
        if ($base === null) {
            $script = isset($_SERVER['SCRIPT_NAME']) ? (string) $_SERVER['SCRIPT_NAME'] : '';
            $dir = dirname($script); // <base>/lms khi chạy qua entry point lms/*.php
            if (basename($dir) !== 'lms') {
                $dir = '/lms'; // fallback an toàn (CLI / trường hợp lạ)
            }
            $base = rtrim($dir, '/');
        }
        return $base;
    }
}

if (!function_exists('lms_asset_url')) {
    /**
     * URL asset tĩnh trong lms/public/.
     *
     * @param string $relative 'css/lms.css'
     * @return string
     */
    function lms_asset_url($relative)
    {
        $relative = ltrim((string) $relative, '/');
        return lms_base_url() . '/public/' . $relative;
    }
}

if (!function_exists('lms_upload_url')) {
    /**
     * URL file upload trong lms/public/uploads/.
     *
     * @param string $relative 'hw/202610/abc123.pdf'
     * @return string
     */
    function lms_upload_url($relative)
    {
        $relative = ltrim((string) $relative, '/');
        return lms_asset_url('uploads/' . $relative);
    }
}

if (!function_exists('lms_url_to')) {
    /**
     * Sinh link cho portal bất kỳ (dùng khi redirect chéo portal — D9).
     *
     * @param string $portal  'admin' | 'teacher' | 'student'
     * @param string $alias   '' = dashboard
     * @param string $action
     * @param array  $params
     * @return string
     */
    function lms_url_to($portal, $alias = '', $action = '', array $params = array())
    {
        $url = lms_base_url() . '/' . $portal;
        if ($alias !== '') {
            $url .= '/' . $alias;
        }
        if ($action !== '') {
            $url .= '/' . $action;
        }
        return $params ? $url . '?' . http_build_query($params) : $url;
    }
}

if (!function_exists('lms_url')) {
    /**
     * Sinh link nội bộ portal hiện tại. BẮT BUỘC dùng thay vì viết tay URL.
     *
     * @param string $alias   '' = dashboard
     * @param string $action  action thuần ('login','logout') hoặc action module
     * @param array  $params  query thêm (id, page, search...)
     * @return string
     */
    function lms_url($alias = '', $action = '', array $params = array())
    {
        $portal = defined('_LMS_PORTAL') ? _LMS_PORTAL : 'admin';
        return lms_url_to($portal, $alias, $action, $params);
    }
}

if (!function_exists('lms_portal_label')) {
    /**
     * Tên hiển thị tiếng Việt của portal.
     *
     * @param string $portal
     * @return string
     */
    function lms_portal_label($portal)
    {
        $map = array(
            'admin'   => 'Quản trị',
            'teacher' => 'Giáo viên',
            'student' => 'Học viên',
        );
        $portal = (string) $portal;
        return isset($map[$portal]) ? $map[$portal] : $portal;
    }
}

if (!function_exists('lms_portal_icon')) {
    /**
     * Icon FontAwesome 6 đại diện cho portal (trang chọn vai trò).
     *
     * @param string $portal
     * @return string
     */
    function lms_portal_icon($portal)
    {
        $map = array(
            'admin'   => 'fa-solid fa-user-shield',
            'teacher' => 'fa-solid fa-chalkboard-user',
            'student' => 'fa-solid fa-user-graduate',
        );
        $portal = (string) $portal;
        return isset($map[$portal]) ? $map[$portal] : 'fa-solid fa-school';
    }
}

// ===================== Parse route (router T4) =====================

if (!function_exists('lms_parse_route')) {
    /**
     * Parse route LMS thành (alias, action).
     *
     * 2 dạng URL (guide §3.3.2):
     *  - Mới (D10): 'hoc-vien/them'            → alias=hoc-vien, action=them
     *  - Cũ       : '' + ?p=hoc-vien&a=them     → như nhau (backward compat)
     *
     * Reserved words 'login' | 'logout' | 'dashboard' là action thuần (không thuộc
     * module): alias='' để entry point / router xử lý riêng.
     *
     * @param string|null $route giá trị ?lms_route (null = lấy từ $_GET)
     * @param array|null  $get   query params (null = $_GET) — cho test
     * @return array array('alias' => string, 'action' => string)
     */
    function lms_parse_route($route = null, array $get = null)
    {
        if ($route === null) {
            $route = isset($_GET['lms_route']) ? (string) $_GET['lms_route'] : '';
        }
        if ($get === null) {
            $get = isset($_GET) && is_array($_GET) ? $_GET : array();
        }

        $route = trim((string) $route, "/\r\n");
        $parts = $route !== '' ? explode('/', $route) : array();

        $reserved = array('login', 'logout', 'dashboard');

        $p = null;
        $a = null;

        if (!empty($parts) && in_array($parts[0], $reserved, true)) {
            // /lms/admin/login → action thuần
            $p = '';
            $a = $parts[0];
        } elseif (!empty($parts)) {
            // /lms/admin/lms-hoc-vien[/them]
            $p = $parts[0];
            $a = isset($parts[1]) ? $parts[1] : 'man';
        }

        // Backward compat: ?p=&a= cũ
        if ($p === null) {
            $p = isset($get['p']) ? trim((string) $get['p']) : '';
        }
        if ($a === null) {
            $a = isset($get['a']) ? trim((string) $get['a']) : '';
        }

        // ?p=x (không có a) → action mặc định 'man', giống clean URL /lms/<portal>/x
        if ($p !== '' && $a === '') {
            $a = 'man';
        }

        // Dashboard mặc định
        if ($p === '' && ($a === '' || $a === 'dashboard')) {
            $p = 'lms-tong-quan';
            $a = 'hienthi';
        }

        return array('alias' => $p, 'action' => $a);
    }
}

if (!function_exists('lms_find_module')) {
    /**
     * Lookup module theo alias + portal (bước 4 của router).
     * Sai portal → không trả về (D9).
     *
     * @param LmsDb  $d
     * @param string $alias
     * @param string $portal
     * @return array|null row module hoặc null
     */
    function lms_find_module(LmsDb $d, $alias, $portal)
    {
        $row = $d->safeFetchOne(
            'select id, name, alias from db_lms_modules where alias = ? and portal = ? limit 1',
            array((string) $alias, (string) $portal)
        );
        return is_array($row) && !empty($row) ? $row : null;
    }
}
