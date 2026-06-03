<?php
$file = __DIR__ . '/../assets/script/shop.js';
$content = file_get_contents($file);

// Thay các URL ajax_cart.php
$content = str_replace(
    ["(typeof URLPATH !== 'undefined' ? URLPATH : '') + 'sources/ajax/ajax_cart.php'",
     "(typeof URLPATH !== 'undefined' ? URLPATH : '') + \"sources/ajax/ajax_cart.php\""],
    "AJAX_ROUTES.cart + 'legacy'",
    $content
);

// Thay các URL ajax.php
$content = str_replace(
    ["(typeof URLPATH !== 'undefined' ? URLPATH : '') + \"sources/ajax/ajax.php\"",
     "(typeof URLPATH !== 'undefined' ? URLPATH : '') + 'sources/ajax/ajax.php'",
     "'sources/ajax/ajax.php'"],
    "AJAX_ROUTES.product + 'legacy'",
    $content
);

file_put_contents($file, $content);
echo "Done. shop.js updated.\n";

// Verify
$remaining = substr_count(file_get_contents($file), 'sources/ajax/ajax');
echo "Remaining 'sources/ajax' references in shop.js: $remaining\n";
