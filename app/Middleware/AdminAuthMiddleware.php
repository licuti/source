<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;

class AdminAuthMiddleware implements Middleware {
    /**
     * Kiểm tra xem User đã đăng nhập vào Admin chưa.
     */
    public function handle($request, $next) {
        $uri = trim($request->uri, '/');
        
        // Chỉ áp dụng bảo vệ cho các URL bắt đầu bằng "admin" và không phải trang đăng nhập
        if (strpos($uri, 'admin') === 0 && $uri !== 'admin/login') {
            
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            
            // Nếu chưa có session user_hash (chuẩn cũ đang dùng) thì đẩy về trang login
            if (!isset($_SESSION['user_hash'])) {
                $response = new Response('', 302);
                return $response->header('Location', '/admin/login');
            }
        }
        
        return $next($request);
    }
}
