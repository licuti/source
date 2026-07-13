<?php

namespace App\Core\Contracts;

interface ResponseInterface {
    public function setStatusCode(int $code): self;
    public function withHeader(string $name, string $value): self;
    public function send(string $content = '');
    public function json($data, int $status = 200);
    public function redirect(string $url, int $status = 302);
    public function download(string $filePath, ?string $name = null);
    public function stream(callable $callback);
}
