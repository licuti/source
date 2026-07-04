<?php
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4', 'root', '');
$res = [];
foreach(['db_posts', 'db_products', 'db_categories'] as $t) {
    $stmt = $pdo->query("SHOW COLUMNS FROM `$t`");
    $res[$t] = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
print_r($res);
