<?php
/**
 * Router admin: ?p=<alias-module>&a=<action>
 *
 * - Whitelist alias: chi [a-z0-9-]+
 * - Include source chi khi file ton tai trong sources/
 * - Logout sach session + key_ad
 * - Self-page: thongtin-user khong can quyen module
 */

$source     = '';
$template   = 'index';
$id_module  = 0;
$row_module = array();

$rawP = isset($_REQUEST['p']) ? (string) $_REQUEST['p'] : '';
$p    = strtolower(trim($rawP));

if ($p !== '' && !preg_match('/^[a-z0-9\-]+$/', $p)) {
    if (function_exists('admin_flash')) {
        admin_flash('error', 'Đường dẫn không hợp lệ.');
    }
    $d->redirect('index.php');
}

if ($p === 'out') {
    // Thu hồi remember-me token trong DB trước khi xoá session
    $uid = isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;
    if ($uid > 0 && is_object($d) && method_exists($d, 'safeExec')) {
        try {
            $d->safeExec('update #_user set remember_token = NULL where id = ?', array($uid));
        } catch (Exception $e) {
            if (function_exists('error_log')) {
                error_log('logout remember_token: ' . $e->getMessage());
            }
        }
    }

    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            isset($params['path']) ? $params['path'] : '/',
            isset($params['domain']) ? $params['domain'] : '',
            !empty($params['secure']),
            !empty($params['httponly'])
        );
    }
    @session_destroy();

    setrawcookie('key_ad', '0', time() - 3600, '/', null, null, true);
    if (isset($_COOKIE['key_ad'])) {
        unset($_COOKIE['key_ad']);
    }

    $d->redirect('login.php');
}

if ($p === '') {
    $source   = '';
    $template = 'index';
    return;
}

if (method_exists($d, 'safeFetchOne')) {
    $row_module = $d->safeFetchOne(
        'select * from #_module_admin where alias = ? limit 1',
        array($p)
    );
} else {
    $row_module = $d->simple_fetch(
        "select * from #_module_admin where alias = '" . addslashes($p) . "' limit 1"
    );
}
if (!is_array($row_module)) {
    $row_module = array();
}
$id_module = !empty($row_module['id']) ? (int) $row_module['id'] : 0;

$selfPages = array('thongtin-user');
$isSelf    = in_array($p, $selfPages, true);

if (!$isSelf) {
    $uid = isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;
    if ($id_module <= 0 || $d->checkPermission($uid, $id_module) == 0) {
        if (function_exists('admin_flash')) {
            admin_flash('error', 'Bạn không có quyền truy cập mục này.');
        }
        $d->redirect('index.php');
    }
}

$sourceFile = __DIR__ . '/../sources/' . $p . '.php';
if (!is_file($sourceFile)) {
    if (function_exists('admin_flash')) {
        admin_flash('error', 'Module không tồn tại.');
    }
    $d->redirect('index.php');
}

$source = $p;
require $sourceFile;