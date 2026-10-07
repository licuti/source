<?php
/**
 * Bootstrap shell admin (AdminLTE 4 + Bootstrap 5).
 */
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Production: che notice; bật E_ALL khi debug local nếu cần
error_reporting(1);

if (empty($_SESSION['user_hash']) || empty($_SESSION['id_user'])) {
    header('Location: login.php');
    exit;
}
require_once __DIR__ . '/lib/bootstrap.php';
require_once __DIR__ . '/templates/components/_helpers.php';
require_once __DIR__ . '/lib/filemanager.php';
require_once __DIR__ . '/lib/page_assets.php';

global $d;
$d = new func_index($config['database']);
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Defaults trước router
$source   = '';
$template = 'index';
$id_module = 0;

require __DIR__ . '/lib/file_router_admin.php';

// Chỉ cho phép template path an toàn (vd. index, category/hienthi)
$template = isset($template) ? (string) $template : 'index';
$template = str_replace('\\', '/', $template);
if ($template === '' || strpos($template, '..') !== false || !preg_match('#^[a-zA-Z0-9/_\-]+$#', $template)) {
    $template = 'index';
}
$templateFile = __DIR__ . '/templates/' . $template . '_tpl.php';
if (!is_file($templateFile)) {
    $templateFile = __DIR__ . '/templates/index_tpl.php';
    $template = 'index';
}

// CSRF token sẵn cho form / meta
$csrfToken = function_exists('admin_csrf_token') ? admin_csrf_token(false) : '';

// Asset theo module: CKEditor chỉ nạp ở form soạn thảo, Sortable chỉ ở trang kéo-thả.
// Fallback nạp đủ để không vỡ trang nếu helper thiếu.
$pageAssets = function_exists('admin_page_assets')
    ? admin_page_assets($template)
    : array(
        'head_js' => array(
            'public/plugin/jquery/dist/jquery.min.js',
            'public/js/jquery.fancybox.min.js',
            'js/jquery.twbsPagination.js',
            'ckeditor/ckeditor.js',
        ),
        'body_js' => array('public/plugin/sortablejs/Sortable.min.js'),
    );
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Administrator</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if ($csrfToken !== ''): ?>
        <meta name="csrf-token" content="<?= e($csrfToken) ?>">
    <?php endif; ?>
    <link href="img/logo.png" rel="shortcut icon" type="image/x-icon">

    <!-- Core CSS -->
    <link rel="stylesheet" href="public/plugin/bootstrap5/css/bootstrap.min.css">
    <link rel="stylesheet" href="public/plugin/bootstrap-icons/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="public/plugin/fontawesome6/css/all.min.css">
    <link rel="stylesheet" href="public/plugin/fontawesome6/css/v4-shims.min.css">
    <link rel="stylesheet" href="public/plugin/overlayscrollbars/css/overlayscrollbars.min.css">
    <link rel="stylesheet" href="public/plugin/adminlte4/css/adminlte.min.css">
    <link rel="stylesheet" href="public/plugin/toastify/toastify.css">
    <link rel="stylesheet" href="public/css/jquery.fancybox.min.css">
    <link rel="stylesheet" href="public/plugin/datatables-bs5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="public/plugin/select2/css/select2.min.css">
    <link rel="stylesheet" href="public/plugin/select2-bs5/css/select2-bootstrap-5-theme.min.css">

    <link rel="stylesheet" href="css/admin-ui.css">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:ital,opsz,wght@0,17..18,400..700;1,17..18,400..700&display=swap" rel="stylesheet">

    <!-- jQuery + editor theo module (page_assets.php) -->
    <?php foreach ($pageAssets['head_js'] as $__src): ?>
    <script src="<?= e($__src) ?>"></script>
    <?php endforeach; ?>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
    <div class="app-wrapper">
        <?php require __DIR__ . '/templates/header_tpl.php'; ?>
        <?php require __DIR__ . '/templates/sidebar_tpl.php'; ?>
        <main class="app-main">
            <?php require $templateFile; ?>
        </main>
        <?php require __DIR__ . '/templates/footer_tpl.php'; ?>
        <?php $d->disconnect(); ?>
    </div>

    <!-- Core JS -->
    <script src="public/plugin/bootstrap5/js/bootstrap.bundle.min.js"></script>
    <script src="public/plugin/overlayscrollbars/js/overlayscrollbars.browser.es6.min.js"></script>
    <script src="public/plugin/adminlte4/js/adminlte.min.js"></script>
    <script src="public/plugin/toastify/toastify.js"></script>
    <script src="public/plugin/sweetalert2/sweetalert2.all.min.js"></script>
    <?php foreach ($pageAssets['body_js'] as $__src): ?>
    <script src="<?= e($__src) ?>"></script>
    <?php endforeach; ?>
    <script src="public/plugin/datatables-bs5/js/dataTables.min.js"></script>
    <script src="public/plugin/datatables-bs5/js/dataTables.bootstrap5.min.js"></script>
    <script src="public/plugin/select2/js/select2.min.js"></script>
    <script src="public/plugin/select2/js/select2.multi-checkboxes.js"></script>

    <script src="js/ui-adapter.js"></script>
    <script src="js/csrf.js"></script>
    <script src="js/notify-bridge.js"></script>
    <script src="js/admin.js"></script>
    <script src="js/admin-ui.js"></script>
    <script>window.ADMIN_FM_CONFIG = <?= function_exists('fm_client_config_json') ? fm_client_config_json() : '{}' ?>;</script>
    <script src="js/ckfinder-adapter.js"></script>
    <script src="js/admin-shell.js"></script>

    <?php if (!empty($_SESSION['admin_flash'])):
        $__admin_flash = $_SESSION['admin_flash'];
        unset($_SESSION['admin_flash']);
    ?>
    <script>
    (function () {
        var flash = <?= json_encode($__admin_flash, JSON_UNESCAPED_UNICODE) ?>;
        if (!flash || !flash.message) return;
        var type = (flash.type === 'error' || flash.type === 'danger') ? 'error' : (flash.type || 'success');
        function showFlash() {
            if (window.AdminUI && AdminUI.notify) {
                AdminUI.notify.show(String(flash.message), type);
                return true;
            }
            if (window.jQuery && jQuery.notify) {
                jQuery.notify(String(flash.message), type === 'error' ? 'error' : 'success');
                return true;
            }
            return false;
        }
        if (!showFlash()) {
            document.addEventListener('DOMContentLoaded', function () { showFlash(); });
        }
    })();
    </script>
    <?php endif; ?>

    <style>
        .fancybox-slide--iframe .fancybox-content {
            width: 800px;
            height: 600px;
            max-width: 80%;
            max-height: 80%;
            margin: 0;
        }
    </style>
</body>
</html>