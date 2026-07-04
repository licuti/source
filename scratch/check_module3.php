<?php
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4', 'root', '');
$stmt = $pdo->query("SELECT * FROM db_module_admin WHERE name LIKE '%liên hệ%' OR route_name LIKE '%form%'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
