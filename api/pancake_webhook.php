<?php
/**
 * Endpoint nhận Webhook từ Pancake POS
 * URL: https://yourdomain.com/api/pancake_webhook.php
 */

require_once __DIR__ . '/../admin/lib/bootstrap.php';
$d = new func_index($config['database']);

// Hỗ trợ kiểm tra endpoint qua GET
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'message' => 'Pancake webhook endpoint is active']);
    exit;
}

// Nhận dữ liệu POST dạng JSON từ Pancake
$jsonPayload = file_get_contents('php://input');

// Ghi log incoming webhook để theo dõi và debug
$logDir = dirname(__DIR__) . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0777, true);
}
$logFile = $logDir . '/pancake_webhook.log';
$logEntry = date('Y-m-d H:i:s') . " | IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'CLI') . " | PAYLOAD: " . $jsonPayload . "\n";
@file_put_contents($logFile, $logEntry, FILE_APPEND);

$payload = json_decode($jsonPayload, true);

if (!$payload) {
    http_response_code(400);
    $res = ['success' => false, 'message' => 'Invalid JSON payload'];
    @file_put_contents($logFile, date('Y-m-d H:i:s') . " | RESPONSE (400): " . json_encode($res) . "\n---\n", FILE_APPEND);
    header('Content-Type: application/json');
    echo json_encode($res);
    exit;
}

// Load Service xử lý Webhook
require_once dirname(__DIR__) . '/admin/sources/services/pancake/PancakeWebhookService.php';

$webhookService = new PancakeWebhookService($d);
$result = $webhookService->handleWebhook($payload);

@file_put_contents($logFile, date('Y-m-d H:i:s') . " | RESPONSE (" . ($result['success'] ? '200' : '400') . "): " . json_encode($result) . "\n---\n", FILE_APPEND);

header('Content-Type: application/json');
if ($result['success']) {
    http_response_code(200);
} else {
    http_response_code(400); 
}

echo json_encode($result);
exit;
?>
