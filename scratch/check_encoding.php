<?php
define('DEBUG_ROUTE', true);
require __DIR__ . '/../app/autoload.php';
$dbConfig = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$dbConfig['servername']};dbname={$dbConfig['database']};charset=utf8";
$pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password']);

$stmt = $pdo->query("SELECT id, name FROM db_module_admin WHERE id IN (7,8,43,100)");
var_dump($stmt->fetchAll(PDO::FETCH_ASSOC));
