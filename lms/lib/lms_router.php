<?php
/**
 * Router LMS (T4) — nhân bản file_router_admin.php của e-commerce nhưng:
 *  - Lookup db_lms_modules thêm điều kiện portal = _LMS_PORTAL.
 *  - Dùng lms_check_permission() thay checkPermission().
 *  - Include lms/app/<portal>/sources/<alias>.php.
 *
 * Parse 2 dạng URL (guide §3.3.2):
 *  - Mới (D10): /lms/<portal>/<alias>[/<action>] qua lms_route
 *  - Cũ:        lms/<portal>.php?p=<alias>&a=<action>
 *
 * Phân quyền 2 cấp: router chỉ check page-level; "self only" do service lọc (§3.5).
 * PHP 7.3 compatible.
 */

if (!defined('_LMS_PORTAL')) {
    http_response_code(403);
    exit;
}

require_once __DIR__ . '/lms_layout.php';
require_once __DIR__ . '/lms_admin_forms.php';

lms_session_ensure();

$d = lms_db();

// ===================== 1) Parse route =====================
// Logic nằm ở lms_parse_route() (lms_url.php) để test được.

$route = lms_parse_route();
$p = $route['alias'];
$a = $route['action'];

// ===================== 2) Auth gate =====================

$user = lms_current_user();

if (!is_array($user) || empty($user['id'])) {
    lms_flash('error', 'Vui lòng đăng nhập để tiếp tục.');
    header('Location: ' . lms_url('', 'login'));
    exit;
}

// D9: role phải khớp portal — không khớp thì đẩy về portal đúng của họ
if (empty($user['role']) || $user['role'] !== _LMS_PORTAL) {
    $rightPortal = !empty($user['role']) ? (string) $user['role'] : 'admin';
    lms_flash('error', 'Tài khoản không thuộc khu vực này. Đăng nhập tại portal '
        . lms_portal_label($rightPortal) . '.');
    header('Location: ' . lms_url_to($rightPortal, '', 'login'));
    exit;
}

// BR-AUTH-01: chưa đổi mật khẩu lần đầu → ép qua trang đổi mật khẩu
if (!empty($user['must_change_password'])) {
    lms_flash('warning', 'Tài khoản của bạn cần đổi mật khẩu trước khi sử dụng hệ thống.');
    header('Location: ' . lms_url('', 'doi-mat-khau'));
    exit;
}

// ===================== 3) Whitelist alias =====================

if (!preg_match('/^[a-z0-9-]+$/', $p)) {
    lms_flash('error', 'Đường dẫn không hợp lệ.');
    header('Location: ' . lms_url(''));
    exit;
}

// ===================== 4) Lookup module =====================

$module = lms_find_module($d, $p, _LMS_PORTAL);
if (empty($module) || !is_array($module)) {
    lms_not_found('Module không tồn tại trong portal này.');
}

// ===================== 5) Phân quyền page-level =====================

if (!lms_check_permission($d, $user, $p, 'view', _LMS_PORTAL)) {
    lms_flash('error', 'Bạn không có quyền truy cập mục này.');
    header('Location: ' . lms_url(''));
    exit;
}

// ===================== 6) Source file =====================

// CHÚ Ý: portal dir nằm dưới lms/app/ (KHÔNG phải lms/<portal>/) — nếu đặt
// lms/admin/ thì URL /lms/admin trùng tên thư mục thật → mod_dir's DirectorySlash
// sinh 301 dư (xem note trong .htaccess).
$sourceFile = _LMS_DIR . '/app/' . _LMS_PORTAL . '/sources/' . $p . '.php';
if (!is_file($sourceFile)) {
    lms_not_found('Nội dung module này chưa được xây dựng.');
}

// Guard chống truy cập trực tiếp source (guide §2 bước 2)
define('_source', true);

// Biến dùng chung cho source + template
$id_module = (int) $module['id'];

// Template mặc định source có thể ghi đè
$template = $p . '/hienthi';

require $sourceFile;

// ===================== 7) Template =====================

// Validate path: không chứa '..', chỉ [a-zA-Z0-9/_-]
if (!isset($template) || !preg_match('#^[a-zA-Z0-9][a-zA-Z0-9/_-]*$#', (string) $template)) {
    lms_not_found('Template không hợp lệ.');
}

$tplFile = _LMS_DIR . '/app/' . _LMS_PORTAL . '/templates/' . $template . '_tpl.php';
if (!is_file($tplFile)) {
    lms_not_found('Giao diện module này chưa được xây dựng.');
}

// ===================== 8) Layout render =====================
// Template include ở scope global (giống admin/index.php) để thấy biến từ source.
lms_layout_open(array(
    'portal' => _LMS_PORTAL,
    'title'  => isset($title) && $title !== '' ? (string) $title : (string) $module['name'],
    'active' => $p,
    'hide_title' => !empty($hidePageTitle),
    'alert' => isset($layoutAlert) && is_array($layoutAlert) ? $layoutAlert : null,
));

require $tplFile;

lms_layout_close();
