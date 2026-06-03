<?php
require 'app/autoload.php';
$app = App\Core\App::getInstance();
$app->boot();

// Grab a product that has variants
$p = \ProductModel::where('id_code', 778)->first(); // We used 778 earlier
$variants = \ProductVariantModel::where('id_sanpham', 778)->get();
echo json_encode($variants);
