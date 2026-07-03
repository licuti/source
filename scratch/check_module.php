<?php
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4', 'root', '');
$stmt = $pdo->query("SELECT * FROM db_module_admin WHERE id = 33");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
