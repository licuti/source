<?php
require 'app/autoload.php';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['REQUEST_URI'] = '/ajax/cart/legacy';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_POST = [
    'action' => 'add-to-cart',
    'id_sp' => 778,
    'so_luong' => 1
];

$app = App\Core\App::getInstance();
$request = new App\Core\Request();
$router = $app->router;
require 'routes/web.php';
$response = $router->dispatch($request);
var_dump($response);
