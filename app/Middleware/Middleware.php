<?php

namespace App\Middleware;

/**
 * Interface Middleware
 * Cấu trúc chuẩn cho các bộ lọc Request.
 */
interface Middleware {
    public function handle($request, $next);
}
