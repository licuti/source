<?php
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4', 'root', '');
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
