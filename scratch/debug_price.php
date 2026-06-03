<?php
require 'app/autoload.php';
Model::boot(config('database'));

echo "=== TEST: Debug variants step by step ===\n";
$products = ProductModel::where('id', 170)->with('variants')->get();
$p = $products[0];

// Kiểm tra variants là gì sau get()
$variants = $p->variants;
echo "Type of variants: " . gettype($variants) . "\n";
echo "Count: " . count($variants) . "\n";

$first = $variants[0];
echo "Type of first element: " . gettype($first) . "\n";

// Nếu là array thì in ra
if (is_array($first)) {
    echo "First element is ARRAY (handleRelation's array_map FAILED):\n";
    print_r(array_keys($first));
} else {
    echo "First element class: " . get_class($first) . "\n";
    echo "gia: " . $first->gia . "\n";
}

// Test gọi trực tiếp renderProductPrice
echo "\n=== TEST: renderProductPrice ===\n";
require_once 'app/Helpers/product.php';

// Simulate bằng cách gán thẳng
$mockProduct = new ProductModel($p->attributes);
$mockProduct->variants = $variants; // gán thẳng

$hasVariants = !empty($mockProduct->variants);
echo "hasVariants: " . var_export($hasVariants, true) . "\n";

// Debug: check if __get works
$vRead = $mockProduct->variants;
echo "Read back variants type: " . gettype($vRead) . "\n";
echo "Read back count: " . count($vRead) . "\n";
