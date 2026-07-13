<?php

namespace App\Middleware;

use App\Exceptions\TokenMismatchException;

class CsrfMiddleware implements Middleware {

    /**
     * Danh sách các URI được miễn trừ kiểm tra CSRF (nếu có Webhooks, API)
     */
    protected $except = [
        'api/*',
        // 'webhook/payment',
    ];

    public function handle($request, $next) {
        if ($this->isReading($request) || $this->inExceptArray($request) || $this->tokensMatch($request)) {
            return $next($request);
        }

        throw new TokenMismatchException('Phiên làm việc đã hết hạn. Vui lòng tải lại trang.');
    }

    /**
     * Xác định xem Request HTTP có phải là loại chỉ đọc không.
     */
    protected function isReading($request): bool {
        return in_array(strtoupper($request->method()), ['HEAD', 'GET', 'OPTIONS']);
    }

    /**
     * Kiểm tra xem Request URI có nằm trong danh sách ngoại trừ không.
     */
    protected function inExceptArray($request): bool {
        $requestUri = trim($request->uri, '/');

        foreach ($this->except as $except) {
            if ($except !== '/') {
                $except = trim($except, '/');
            }

            // Xử lý wildcard (*)
            $pattern = str_replace('\*', '.*', preg_quote($except, '#'));
            if (preg_match('#^' . $pattern . '\z#u', $requestUri)) {
                return true;
            }
        }

        return false;
    }

    /**
     * So khớp token.
     */
    protected function tokensMatch($request): bool {
        $token = $this->getTokenFromRequest($request);

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $sessionToken = $_SESSION['token'] ?? null;

        return is_string($sessionToken) &&
               is_string($token) &&
               hash_equals($sessionToken, $token);
    }

    /**
     * Lấy token từ Request params hoặc Headers.
     */
    protected function getTokenFromRequest($request) {
        $token = $request->input('_token');

        if (!$token) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        }

        return $token;
    }
}
