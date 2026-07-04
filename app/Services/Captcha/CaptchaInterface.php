<?php
namespace App\Services\Captcha;

interface CaptchaInterface {
    /**
     * Render HTML script tag cho frontend
     */
    public function render(): string;

    /**
     * Xác minh token hợp lệ với API của nhà cung cấp
     */
    public function verify(string $token, string $ip = null): bool;
}
