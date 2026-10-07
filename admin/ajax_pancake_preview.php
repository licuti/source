<?php
ob_start();
@session_start();
@define('_source', './sources/');

// Bắt fatal error và in ra JSON để debug
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        while (ob_get_level()) ob_end_clean();
        header('Content-Type: application/json');
        echo json_encode([
            'success'  => false,
            'message'  => 'Fatal PHP Error: ' . $err['message'],
            'file'     => basename($err['file']),
            'line'     => $err['line'],
        ]);
    }
});

header('Content-Type: application/json');

require_once __DIR__ . '/lib/bootstrap.php';

// Kết nối DB (không bắt buộc phải thành công mới trả JSON)
try {
    $d = new func_index($config['database']);
} catch (Throwable $e) {
    // Tiếp tục - DB không cần thiết cho việc preview
}

if (!isset($_SESSION['id_user'])) {
    while (ob_get_level()) ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Unauthorized - vui lòng đăng nhập lại']);
    exit;
}

$pancakeClientFile = _source . 'services/pancake/PancakeApiClient.php';
if (!file_exists($pancakeClientFile)) {
    while (ob_get_level()) ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'File PancakeApiClient.php không tồn tại tại: ' . $pancakeClientFile]);
    exit;
}

require_once $pancakeClientFile;

// Lấy config từ DB (ưu tiên) hoặc config.php
$pancakeConfig = function_exists('getPancakeConfig') ? getPancakeConfig() : ($config['pancake'] ?? []);

if (empty($pancakeConfig['api_key'])) {
    while (ob_get_level()) ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Chưa cấu hình API Key Pancake. Vào Admin → Thông tin liên hệ để nhập API Key.']);
    exit;
}

$apiClient = new PancakeApiClient($pancakeConfig);

$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
if ($page < 1) $page = 1;

$limit = 30;
$response = $apiClient->getProducts($page, $limit);

if (!$response['success']) {
    while (ob_get_level()) ob_end_clean();
    echo json_encode(['success' => false, 'message' => $response['message'], 'raw' => $response]);
    exit;
}

$rawData = $response['data'];
$products = [];

if (is_array($rawData)) {
    if (isset($rawData['data']) && is_array($rawData['data'])) {
        $products = $rawData['data'];
    } elseif (isset($rawData['products']) && is_array($rawData['products'])) {
        $products = $rawData['products'];
    } elseif (isset($rawData['data']['products']) && is_array($rawData['data']['products'])) {
        $products = $rawData['data']['products'];
    } elseif (isset($rawData[0]) && is_array($rawData[0])) {
        $products = $rawData;
    }
}

while (ob_get_level()) ob_end_clean();
echo json_encode([
    'success'      => true,
    'total'        => count($products),
    'data'         => $products,
    'has_more'     => count($products) === $limit,
    'page'         => $page,
    'raw_response' => $response,
]);
exit;
