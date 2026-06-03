<?php
require_once 'app/autoload.php';
$app = App\Core\App::getInstance();
$app->boot();

$variants = \ProductVariantModel::where('id_sanpham', 778)->get();
\ProductVariantModel::loadNestedAttributes($variants);
$attrs = buildVariantAttributes($variants);

echo json_encode($variants[0] ?? []);
