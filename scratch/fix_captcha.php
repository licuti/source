<?php
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4', 'root', '');
$stmt = $pdo->query("SELECT schema_config FROM db_settings WHERE lang = 'vi'");
$schema = $stmt->fetchColumn();

$schemaArr = json_decode($schema, true) ?: [];

// Remove my incorrectly injected schema
$newSchemaArr = [];
foreach ($schemaArr as $item) {
    // If it's my deeply nested array, skip it
    if (isset($item['id']) && $item['id'] === 'captcha_settings') {
        continue;
    }
    // If it's a field I already added, skip it to recreate it
    if (isset($item['name']) && in_array($item['name'], ['captcha_provider', 'captcha_site_key', 'captcha_secret_key'])) {
        continue;
    }
    
    $newSchemaArr[] = $item;
}

// Add the fields in the correct flat format
$newSchemaArr[] = [
    'name' => 'captcha_provider',
    'label' => 'Nhà cung cấp Captcha',
    'type' => 'select',
    'tab' => 'Cấu hình Captcha',
    'options' => [
        'none' => 'Không sử dụng (Tắt)',
        'recaptcha' => 'Google reCAPTCHA v3',
        'turnstile' => 'Cloudflare Turnstile'
    ]
];
$newSchemaArr[] = [
    'name' => 'captcha_site_key',
    'label' => 'Site Key',
    'type' => 'text',
    'tab' => 'Cấu hình Captcha',
];
$newSchemaArr[] = [
    'name' => 'captcha_secret_key',
    'label' => 'Secret Key',
    'type' => 'text',
    'tab' => 'Cấu hình Captcha',
];

$newSchemaStr = json_encode($newSchemaArr, JSON_UNESCAPED_UNICODE);

$updateStmt = $pdo->prepare("UPDATE db_settings SET schema_config = ? WHERE lang = 'vi'");
$updateStmt->execute([$newSchemaStr]);

$updateStmt2 = $pdo->prepare("UPDATE db_settings SET schema_config = ? WHERE lang = 'en'");
$updateStmt2->execute([$newSchemaStr]);

echo "Schema fixed.";
