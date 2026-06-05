<?php

namespace App\Controllers\Admin;

use App\Controllers\Controller;
use App\Core\Response;

class BaseAdminController extends Controller {
    public function __construct() {
        // Không gọi parent::__construct() vì class cha không có
    }

    /**
     * Render giao diện kèm dữ liệu chung cho Admin
     */
    protected function render($view, $data = []) {
        // Có thể load các dữ liệu dùng chung cho view ở đây
        if (!isset($data['admin_user'])) {
            $data['admin_user'] = $_SESSION['name'] ?? 'Administrator';
        }
        
        return view($view, $data);
    }

    /**
     * Trả về JSON Response
     */
    protected function json($data, $statusCode = 200) {
        return Response::json($data, $statusCode);
    }

    /**
     * Chuyển hướng trang
     */
    protected function redirect($url) {
        $response = new Response('', 302);
        return $response->header('Location', $url);
    }
}
