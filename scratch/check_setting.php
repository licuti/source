<?php
require 'app/autoload.php';
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8', 'root', '');
print_r($pdo->query("SELECT * FROM db_setting LIMIT 1")->fetchAll(PDO::FETCH_ASSOC));
