<?php
require 'app/autoload.php';
$_SERVER['REQUEST_URI'] = '/en';
$_SERVER['REQUEST_METHOD'] = 'GET';
$app = \App\Core\App::getInstance();
$request = new \App\Core\Request();
$response = $app->router->dispatch();
echo substr($response->getContent(), 0, 500);
