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
            
            // Nếu chưa có session id_user thì đẩy về trang login
            if (!isset($_SESSION['id_user'])) {
                $response = new Response('', 302);
                return $response->header('Location', '/admin/login');
            }

            // --- RBAC CHECK ---
            $routeName = $request->getRouteName() ?? '';
            
            // Exempt Dashboard and logout
            $publicRoutes = ['admin.dashboard', 'admin.logout'];
            if (!in_array($routeName, $publicRoutes) && !empty($routeName)) {
                // Extract module prefix (e.g., admin.category.edit -> admin.category)
                $parts = explode('.', $routeName);
                if (count($parts) >= 3) {
                    $prefix = $parts[0] . '.' . $parts[1]; // admin.category
                    $action = end($parts); // edit, index, store, destroy, updateStatusAjax
                    
                    if (!\App\Core\Auth\Gate::check($prefix, $action)) {
                        return $this->denyAccess();
                    }
                }
            }
        }
        
        return $next($request);
    }

    private function denyAccess() {
        // AJAX requests
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            $response = new Response(json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện thao tác này!']), 403);
            return $response->header('Content-Type', 'application/json');
        }
        
        // HTML requests
        $html = '<!DOCTYPE html>
        <html lang="vi">
        <head><meta charset="utf-8"><title>403 Forbidden</title><style>body{font-family:sans-serif;background:#f4f6f9;text-align:center;padding-top:100px;color:#333;}h1{font-size:48px;color:#dc3545;}p{font-size:18px;}a{color:#007bff;text-decoration:none;}</style></head>
        <body>
            <h1>403 Lỗi Phân Quyền</h1>
            <p>Bạn không có quyền truy cập vào khu vực này!</p>
            <p><a href="/admin/dashboard">Quay lại Bảng điều khiển</a></p>
        </body>
        </html>';
        
        return new Response($html, 403);
    }
}
