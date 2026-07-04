<?php
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4', 'root', '');
$stmt = $pdo->query("SELECT schema_config FROM db_settings WHERE lang = 'vi'");
$schema = $stmt->fetchColumn();

$schemaArr = json_decode($schema, true) ?: [];

// Add Captcha settings tab if not exists
$hasCaptcha = false;
foreach ($schemaArr as $tab) {
    if ($tab['id'] === 'captcha_settings') {
        $hasCaptcha = true;
        break;
    }
}

if (!$hasCaptcha) {
    $schemaArr[] = [
        'id' => 'captcha_settings',
        'title' => 'Cấu hình Captcha',
        'fields' => [
            [
                'name' => 'captcha_provider',
                'label' => 'Nhà cung cấp Captcha',
                'type' => 'select',
                'options' => [
                    'none' => 'Không sử dụng (Tắt)',
                    'recaptcha' => 'Google reCAPTCHA v3',
                    'turnstile' => 'Cloudflare Turnstile'
                ]
            ],
            [
                'name' => 'captcha_site_key',
                'label' => 'Site Key',
                'type' => 'text'
            ],
            [
                'name' => 'captcha_secret_key',
                'label' => 'Secret Key',
                'type' => 'text'
            ]
        ]
    ];
    
    $newSchema = json_encode($schemaArr, JSON_UNESCAPED_UNICODE);
    
    $updateStmt = $pdo->prepare("UPDATE db_settings SET schema_config = ? WHERE lang = 'vi'");
    $updateStmt->execute([$newSchema]);
    
    // Create english too just in case
    $updateStmt2 = $pdo->prepare("UPDATE db_settings SET schema_config = ? WHERE lang = 'en'");
    $updateStmt2->execute([$newSchema]);
    
    echo "Captcha schema injected successfully.";
} else {
    echo "Captcha schema already exists.";
}
