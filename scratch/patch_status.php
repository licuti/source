<?php
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4', 'root', '');
$pdo->exec("ALTER TABLE db_categories CHANGE is_active status TINYINT(1) DEFAULT 1");
echo "Column renamed successfully.";
