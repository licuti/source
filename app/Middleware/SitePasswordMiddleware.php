<?php

namespace App\Middleware;

use App\Core\View;

class SitePasswordMiddleware {
    /**
     * Xử lý kiểm tra mật khẩu truy cập toàn trang
     */
    public function handle($request, $next) {
        // Nếu đã có cookie đúng, cho đi tiếp
        if (isset($_COOKIE['code']) && $_COOKIE['code'] == '0000') {
            return $next($request);
        }

        // Xử lý khi user submit form
        if ($request->input('xac_nhan')) {
            $ma = $request->input('xac_nhan');
            if ($ma == '0000') {
                setcookie('code', '0000', time() + (86400 * 30 * 365), "/");
                // Refresh trang để nhận cookie
                header("Location: " . $_SERVER['REQUEST_URI']);
                exit();
            }
        }

        // Nếu chưa đăng nhập, hiển thị form và chặn xử lý tiếp
        $view = new View();
        $view->setLayout(null); // Không dùng layout chính
        echo $view->render('pages/auth/site_gate');
        exit();
    }
}
