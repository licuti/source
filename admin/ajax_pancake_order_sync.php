<?php
session_start();
@define('_source', './sources/');

require_once __DIR__ . '/lib/bootstrap.php';
$d = new func_index($config['database']);

// Kiểm tra quyền (Chỉ admin đăng nhập mới được phép gọi)
if (!isset($_SESSION['id_user'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// CSRF cho mọi POST
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!function_exists('admin_csrf_verify') || !admin_csrf_verify()) {
        http_response_code(419);
        echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ']);
        exit;
    }
}

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;

require_once _source . 'services/pancake/PancakeApiClient.php';
require_once _source . 'services/pancake/PancakeOrderSync.php';

$apiClient = new PancakeApiClient($config['pancake']);
$syncService = new PancakeOrderSync($d, $apiClient);

$result = $syncService->pullAndSync($page, 50);

echo json_encode($result);
exit;
?>
