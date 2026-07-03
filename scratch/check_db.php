<?php
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4', 'root', '');
print_r($pdo->query('DESCRIBE db_lienhe')->fetchAll(PDO::FETCH_ASSOC));
