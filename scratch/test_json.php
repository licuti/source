<?php
require 'app/autoload.php';
$app = App\Core\App::getInstance();
$app->boot();

$v = \ProductVariantModel::where('id', '>', 0)->first();
$arr = [$v];
\ProductVariantModel::loadNestedAttributes($arr);
echo json_encode($v);
