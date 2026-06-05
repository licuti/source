<?php
define('DEBUG_ROUTE', true);
require __DIR__ . '/../app/autoload.php';
$dbConfig = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$dbConfig['servername']};dbname={$dbConfig['database']};charset=utf8";
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);

$pdo->exec("UPDATE db_module_admin SET name = 'Quản lý Sản phẩm' WHERE id = 100");
$pdo->exec("UPDATE db_module_admin SET name = 'Bán hàng & Khách hàng' WHERE id = 43");
$pdo->exec("UPDATE db_module_admin SET name = 'Quản lý Bài viết' WHERE id = 7");
$pdo->exec("UPDATE db_module_admin SET name = 'Cấu hình hệ thống' WHERE id = 8");

echo "Done.";
