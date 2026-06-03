<?php
require_once 'app/autoload.php';
$app = App\Core\App::getInstance();
$app->boot();

// Gỉả lập môi trường request cho ProductController@show
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REQUEST_URI'] = '/san-pham/test-san-pham';
$GLOBALS['com'] = 'test-san-pham';

// Lấy 1 sản phẩm ngẫu nhiên có biến thể để test
$row = ProductModel::where('hien_thi', 1)->orderBy('id', 'DESC')->first();
$GLOBALS['row'] = $row;

$controller = new \App\Controllers\ProductController();
try {
    $request = new \App\Core\Request();
    $controller->show($request);
    echo "SUCCESS: ProductController@show ran without errors.\n";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "IN: " . $e->getFile() . " on line " . $e->getLine() . "\n";
}
