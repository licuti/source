<?php
/**
 * ============================================================
 *  API & AJAX Routes
 *  Định nghĩa các đường dẫn cho xử lý AJAX hoặc API JSON.
 * ============================================================
 */

use App\Controllers\CartController;

/**
 * Cart API
 */
$router->group('/api/cart', function($router) {
    $router->post('/add', [CartController::class, 'add']);
    $router->post('/update', [CartController::class, 'update']);
    $router->post('/remove', [CartController::class, 'remove']);
});
