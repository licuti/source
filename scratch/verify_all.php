<?php
require 'app/autoload.php';
$app = App\Core\App::getInstance();
$app->boot();

// Test 1: relation nested
$v = \ProductVariantModel::where('id', '>', 0)->first();
$arr = [$v];
\ProductVariantModel::loadNestedAttributes($arr);
$data = json_decode(json_encode($v), true);
echo "=== Variant ID: " . $data['id'] . " ===\n";
echo "So thuoctinh: " . count($data['thuoctinh'] ?? []) . "\n";
foreach (($data['thuoctinh'] ?? []) as $tt) {
    echo "  - id_thuoctinh={$tt['id_thuoctinh']}, id_thuoctinh_giatri={$tt['id_thuoctinh_giatri']}";
    echo ", attribute.ten=" . ($tt['attribute']['ten'] ?? 'MISSING');
    echo ", value.ten=" . ($tt['value']['ten'] ?? 'MISSING') . "\n";
}
