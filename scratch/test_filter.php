<?php
$_COOKIE['code'] = '0000';
$_GET['price'] = '100000-500000'; // Giả lập lọc giá

require_once 'app/autoload.php';

use App\Core\App;

$app = App::getInstance();
$app->boot();

try {
    $response = $app->router->dispatch();
    ob_start();
    $response->send();
    $html = ob_get_clean();
    echo "RENDERED SUCCESSFULLY WITH FILTER!\n";
    echo "CONTENT LENGTH: " . strlen($html) . "\n";
    echo "CONTAINS SHOP-SIDEBAR: " . (strpos($html, 'class="shop-sidebar"') !== false ? "YES" : "NO") . "\n";
} catch (\Throwable $e) {
    echo "ERROR TRACE:\n";
    echo $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
