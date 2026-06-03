<?php
require 'app/autoload.php';
$app = App\Core\App::getInstance();
$app->boot();
print_r($GLOBALS['d']->o_fet("SHOW TABLES LIKE '%khuyenmai%'"));
print_r($GLOBALS['d']->o_fet("SHOW TABLES LIKE '%ma_giam_gia%'"));
