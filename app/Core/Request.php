<?php

namespace App\Core;

class Request {
    public $uri;
    public $method;
    public $params = [];       // Query string: $_GET
    public $inputs = [];       // POST body: $_POST
    public $routeParams = [];  // Route params từ URL: /san-pham/{slug}

    public function __construct() {
        $this->uri    = $this->parseUri();
        $this->method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $this->params = $_GET;
        $this->inputs = $_POST;
    }

    protected function parseUri() {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Remove subdirectory if project is not at root
        $scriptName = dirname($_SERVER['SCRIPT_NAME']);
        if ($scriptName !== '/' && strpos($path, $scriptName) === 0) {
            $path = substr($path, strlen($scriptName));
        }

        return '/' . trim($path, '/');
    }

    public function get($key, $default = null) {
        return $this->params[$key] ?? $default;
    }

    public function input($key, $default = null) {
        return $this->inputs[$key] ?? $this->params[$key] ?? $default;
    }

    /** Kiểm tra xem key có tồn tại trong request không */
    public function has($key) {
        return isset($this->inputs[$key]) || isset($this->params[$key]);
    }

    /** Lấy route param (slug từ URL pattern) */
    public function param($key, $default = null) {
        return $this->routeParams[$key] ?? $default;
    }

    /** Được gọi bởi Router sau khi match thành công */
    public function setParams(array $params): void {
        $this->routeParams = $params;
    }

    public function all() {
        return array_merge($this->params, $this->inputs);
    }

    /** Kiểm tra request có phải từ AJAX (fetch/XHR) không */
    public function isAjax(): bool {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
            || isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false;
    }

    /** Kiểm tra phương thức HTTP hiện tại */
    public function isMethod(string $method): bool {
        return strtoupper($this->method) === strtoupper($method);
    }

    /** Lấy file upload từ $_FILES */
    public function file(string $key) {
        return $_FILES[$key] ?? null;
    }

    /** Chỉ lấy các field cụ thể từ request (chống Mass Assignment) */
    public function only(array $keys): array {
        $result = [];
        $all = $this->all();
        foreach ($keys as $key) {
            if (array_key_exists($key, $all)) {
                $result[$key] = $all[$key];
            }
        }
        return $result;
    }

    /** Lấy IP của client */
    public function ip(): string {
        $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        foreach ($keys as $key) {
            if (array_key_exists($key, $_SERVER)) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
}
