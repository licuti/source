<?php
require 'app/autoload.php';
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8', 'root', '');
$pdo->exec("UPDATE db_thongtin SET url_lang_style='path'");
echo 'Updated to path';
