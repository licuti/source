<?php
require 'app/autoload.php';
$app = App\Core\App::getInstance();
$app->boot();
$pdo = Model::getConnection();
$stmt = $pdo->query("DESCRIBE db_sanpham_bienthe_thuoctinh");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
