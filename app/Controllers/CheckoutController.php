<?php

namespace App\Controllers;



/**
 * CheckoutController
 * Xử lý trang thanh toán.
 */
class CheckoutController extends Controller {
    /**
     * Hiển thị trang thanh toán
     */
    public function index($request) {
        // Nếu giỏ hàng trống → redirect về trang giỏ hàng
        if (empty($_SESSION['cart'])) {
            header('Location: ' . url('gio-hang.html'));
            exit;
        }

        return view('pages/cart/checkout', [
            'title' => 'Thanh toán',
            'cart'  => $_SESSION['cart'] ?? [],
        ]);
    }
}

