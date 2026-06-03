<?php
require 'app/autoload.php';
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8', 'root', '');
print_r(array_keys($pdo->query("SELECT * FROM db_thongtin LIMIT 1")->fetch(PDO::FETCH_ASSOC)));
