<?php
require 'app/autoload.php';
Model::boot(config('database'));
$pdo = Model::getConnection();

$sp = $pdo->query("SELECT id, id_code, ten FROM db_sanpham WHERE id = 170")->fetch(PDO::FETCH_ASSOC);
echo "--- PRODUCT 170 ---\n";
print_r($sp);

if ($sp) {
    echo "--- VARIANTS FOR id_code = {$sp['id_code']} ---\n";
    $stmt = $pdo->query("SELECT * FROM db_sanpham_bienthe WHERE id_sanpham = {$sp['id_code']}");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
