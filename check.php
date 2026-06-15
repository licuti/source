<?php $pdo = new PDO("mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4", "root", ""); $stmt = $pdo->query("SELECT * FROM db_contents"); print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
