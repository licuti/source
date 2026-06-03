<?php
$_SERVER['REQUEST_URI'] = '/san-pham';
$_SERVER['REQUEST_METHOD'] = 'GET';
require __DIR__ . '/../index.php';

var_dump($GLOBALS['row']->title);
var_dump($GLOBALS['row']->ten);
