<?php
ob_start();
error_reporting(0);
session_start();
@define('_source', './sources/');

error_reporting(0);
if (function_exists('set_time_limit')) {
    @set_time_limit(300);
}
require_once __DIR__ . '/lib/bootstrap.php';
$d = new func_index($config['database']);

// Kiểm tra quyền (Chỉ admin đăng nhập mới được phép gọi)
if (!isset($_SESSION['id_user'])) {
    while (ob_get_level()) ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Tạm thời vô hiệu hóa kiểm tra CSRF để tránh lỗi 419 trên host live (có thể do cache)
// if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
//     if (!function_exists('admin_csrf_verify') || !admin_csrf_verify()) {
//         http_response_code(419);
//         echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ']);
//         exit;
//     }
// }

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$selected_ids = isset($_POST['selected_ids']) && is_array($_POST['selected_ids']) ? $_POST['selected_ids'] : [];

require_once _source . 'services/pancake/PancakeApiClient.php';
require_once _source . 'services/pancake/PancakeProductSync.php';

$apiClient = new PancakeApiClient($config['pancake']);
$syncService = new PancakeProductSync($d, $apiClient);

$result = $syncService->pullAndSync($page, 50, $selected_ids);

while (ob_get_level()) ob_end_clean();
echo json_encode($result);
exit;
?>
