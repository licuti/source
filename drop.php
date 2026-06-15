<?php $pdo = new PDO("mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4", "root", ""); $pdo->exec("DROP TABLE IF EXISTS db_contents; DROP TABLE IF EXISTS db_content;"); echo "Dropped.";
