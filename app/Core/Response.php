<?php

namespace App\Core;

class Response {
    protected $content;
    protected $statusCode;
    protected $headers = [];

    public function __construct($content = '', $statusCode = 200) {
        $this->content = $content;
        $this->statusCode = $statusCode;
    }

    public static function json($data, $statusCode = 200) {
        $response = new self(json_encode($data, JSON_UNESCAPED_UNICODE), $statusCode);
        $response->header('Content-Type', 'application/json; charset=utf-8');
        return $response;
    }

    public function header($name, $value) {
        $this->headers[$name] = $value;
        return $this;
    }

    public function send() {
        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        
        // Dọn dẹp output buffer (tránh các thẻ HTML hoặc khoảng trắng vô tình lọt vào JSON)
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        echo $this->content;
    }
}
