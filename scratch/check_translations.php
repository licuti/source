<?php
require 'app/autoload.php';
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8', 'root', '');
echo "PAGE:\n";
print_r($pdo->query("SELECT id, ten, alias, id_code, lang FROM db_page WHERE hien_thi = 1 LIMIT 10")->fetchAll(PDO::FETCH_ASSOC));
echo "\nPRODUCT:\n";
print_r($pdo->query("SELECT id, ten, alias, id_code, lang FROM db_sanpham WHERE hien_thi = 1 LIMIT 5")->fetchAll(PDO::FETCH_ASSOC));
