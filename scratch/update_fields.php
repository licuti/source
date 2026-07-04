<?php
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // Add col_width
    $pdo->exec("ALTER TABLE `db_form_fields` ADD COLUMN `col_width` varchar(50) DEFAULT 'col-md-12' AFTER `is_required`");
    echo "Added col_width\n";
} catch (Exception $e) {
    echo "col_width might already exist: " . $e->getMessage() . "\n";
}

try {
    // Add advanced_settings
    $pdo->exec("ALTER TABLE `db_form_fields` ADD COLUMN `advanced_settings` text DEFAULT NULL AFTER `options`");
    echo "Added advanced_settings\n";
} catch (Exception $e) {
    echo "advanced_settings might already exist: " . $e->getMessage() . "\n";
}

echo "Done.";
