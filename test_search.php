<?php
require 'app/Core/Model.php';
require 'app/Models/ProductModel.php';
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8', 'root', '');
\Model::$pdo = $pdo;
$prefix = 'db_';

$q = "san"; 
$query = \App\Models\ProductModel::query();
$query->table = 'db_products'; // Hack for testing

if (!empty($q)) {
    $query->where('title', 'like', "%{$q}%")
          ->orWhere('sku', 'like', "%{$q}%");
}
echo $query->toSql() . "\n";

$products = $query->orderBy('updated_at', 'desc')
                  ->limit(20)
                  ->get(['id', 'title', 'thumbnail', 'price', 'flash_sale']);

print_r($products);
