<?php

namespace App\Middleware;

/**
 * StartSession Middleware
 * Khởi tạo Session cho các yêu cầu (Web Request).
 */
class StartSession implements Middleware {
    /**
     * @param \App\Core\Request $request
     * @param \Closure $next
     */
    public function handle($request, $next) {
        // 1. Chỉ khởi động session nếu chưa tồn tại
        if (session_status() === PHP_SESSION_NONE) {
            // Thiết lập cấu hình Cookie an toàn
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'domain' => '',
                'secure' => isset($_SERVER['HTTPS']), 
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            
            session_start();
        }

        // Chuyển sang middleware hoặc router tiếp theo
        return $next($request);
    }
}
