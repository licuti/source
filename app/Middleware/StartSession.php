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

        // 2. Rotate Validation Flash Data (old & errors)
        // Dữ liệu từ request trước (flash) sẽ được giữ lại cho request hiện tại,
        // sau đó xóa ngay để không tồn tại sang request tiếp theo.
        $_SESSION['_old_input'] = $_SESSION['_flash_old_input'] ?? [];
        $_SESSION['_errors']    = $_SESSION['_flash_errors'] ?? [];
        
        unset($_SESSION['_flash_old_input']);
        unset($_SESSION['_flash_errors']);

        // Chuyển sang middleware hoặc router tiếp theo
        return $next($request);
    }
}
