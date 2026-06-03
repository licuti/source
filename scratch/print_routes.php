<?php
require 'app/autoload.php';
$app = \App\Core\App::getInstance();
$router = $app->router;
$reflection = new ReflectionClass($router);
$property = $reflection->getProperty('routes');
$property->setAccessible(true);
$routes = $property->getValue($router);
print_r(array_keys($routes['GET']));
