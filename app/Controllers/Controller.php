<?php

namespace App\Controllers;

use App\Core\Response;

/**
 * Base Controller
 * Chứa các logic dùng chung cho toàn bộ các Controller khác trong hệ thống.
 */
abstract class Controller {

    protected $layout = 'layouts.main';

    /**
     * Lấy đường dẫn tới file layout.
     * Cho phép hỗ trợ Multi-theme trong tương lai bằng cách Override hàm này.
     */
    protected function getLayout() {
        return $this->layout;
    }

    /**
     * Render view kết hợp layout
     */
    protected function render($view, $data = []) {
        $viewInstance = new \App\Core\View();
        $layout = $this->getLayout();
        if ($layout) {
            $viewInstance->setLayout($layout);
        }
        return $viewInstance->render($view, $data);
    }

    /**
     * Trả về JSON Response — format thô (dùng khi cần kiểm soát toàn bộ)
     */
    protected function json($data, $statusCode = 200) {
        return Response::json($data, $statusCode);
    }

    /**
     * Trả về JSON thành công chuẩn:
     * { "success": true, "message": "...", "data": {...} }
     */
    protected function jsonSuccess(string $message = '', $data = null) {
        $body = ['success' => true, 'message' => $message];
        if ($data !== null) {
            $body['data'] = $data;
        }
        return Response::json($body, 200);
    }

    /**
     * Trả về JSON lỗi chuẩn:
     * { "success": false, "message": "...", "errors": {...} }
     */
    protected function jsonError(string $message, $errors = null, int $statusCode = 200) {
        $body = ['success' => false, 'message' => $message];
        if ($errors !== null) {
            $body['errors'] = $errors;
        }
        return Response::json($body, $statusCode);
    }

    /**
     * Chuyển hướng trang
     */
    protected function redirect($url) {
        $response = new Response('', 302);
        return $response->header('Location', $url);
    }
}

