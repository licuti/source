<?php
require 'app/autoload.php';
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8', 'root', '');
print_r($pdo->query("SELECT alias, id_code, lang FROM db_page WHERE alias IN ('san-pham', 'product', 'tin-tuc', 'news')")->fetchAll(PDO::FETCH_ASSOC));
