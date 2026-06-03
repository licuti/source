<?php
require 'app/autoload.php';
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8', 'root', '');
$stmt = $pdo->query("SHOW COLUMNS FROM db_page");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
