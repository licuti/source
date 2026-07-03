<?php
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4', 'root', '');
$stmt = $pdo->query("SELECT id, parent, name, alias, route_name, is_active FROM db_module_admin WHERE id = 24");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
