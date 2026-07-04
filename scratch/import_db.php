<?php
$pdo = new PDO('mysql:host=localhost;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("DROP DATABASE IF EXISTS phuongnamv_db_new");
$pdo->exec("CREATE DATABASE phuongnamv_db_new CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$pdo->exec("USE phuongnamv_db_new");

$sql = file_get_contents(__DIR__ . '/../database/phuongnamv_db.sql');
$pdo->exec($sql);

echo "Database imported successfully.\n";
