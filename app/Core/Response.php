<?php

namespace App\Core;

use App\Core\Contracts\ResponseInterface;

class Response implements ResponseInterface {
    protected $content;
    protected $statusCode;
    protected $headers = [];

    public function __construct($content = '', $statusCode = 200) {
        $this->content = $content;
        $this->statusCode = $statusCode;
    }

    public function setStatusCode(int $code): self {
        $this->statusCode = $code;
        return $this;
    }

    public function withHeader(string $name, string $value): self {
        $this->headers[$name] = $value;
        return $this;
    }

    public function header($name, $value) {
        return $this->withHeader($name, $value);
    }

    public function with($key, $value) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION[$key] = $value;
        return $this;
    }

    public function send(string $content = '') {
        if ($content !== '') {
            $this->content = $content;
        }

        http_response_code($this->statusCode);
        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }
        
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        
        echo $this->content;
    }

    public function json($data, int $status = 200) {
        $this->content = json_encode($data, JSON_UNESCAPED_UNICODE);
        $this->statusCode = $status;
        $this->withHeader('Content-Type', 'application/json; charset=utf-8');
        return $this;
    }

    public static function jsonResponse($data, $statusCode = 200) {
        $response = new self();
        return $response->json($data, $statusCode);
    }

    public function redirect(string $url, int $status = 302) {
        $this->statusCode = $status;
        $this->withHeader('Location', $url);
        return $this;
    }

    public function download(string $filePath, ?string $name = null) {
        if (!file_exists($filePath)) {
            $this->setStatusCode(404)->send('File not found');
            exit;
        }
        $name = $name ?? basename($filePath);
        $this->withHeader('Content-Description', 'File Transfer')
             ->withHeader('Content-Type', 'application/octet-stream')
             ->withHeader('Content-Disposition', 'attachment; filename="' . $name . '"')
             ->withHeader('Expires', '0')
             ->withHeader('Cache-Control', 'must-revalidate')
             ->withHeader('Pragma', 'public')
             ->withHeader('Content-Length', (string)filesize($filePath));
        
        $this->send();
        readfile($filePath);
        exit;
    }

    public function stream(callable $callback) {
        $this->withHeader('X-Accel-Buffering', 'no');
        $this->send();
        $callback();
        exit;
    }

    public static function __callStatic($name, $arguments) {
        if ($name === 'json') {
            return self::jsonResponse(...$arguments);
        }
        $instance = new self();
        return $instance->$name(...$arguments);
    }
}
