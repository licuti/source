<?php
require 'app/autoload.php';
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8', 'root', '');
try {
    $pdo->exec("ALTER TABLE db_thongtin ADD COLUMN url_lang_style VARCHAR(20) NOT NULL DEFAULT 'query'");
    echo "Column added successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
