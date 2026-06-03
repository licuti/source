<?php
$dbConfig = require 'config/database.php';
$pdo = new PDO("mysql:host={$dbConfig['host']};dbname={$dbConfig['database']}", $dbConfig['username'], $dbConfig['password']);
$stmt = $pdo->query("SELECT id, alias, view, ten, title, id_code FROM db_page WHERE alias='san-pham'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
