<?php
    /**
     * Bootstrap chung: nạp cấu hình + thư viện hàm/class.
     *
     * Mọi entry point thay 3 lệnh include/require riêng lẻ bằng 1 dòng:
     *   require_once __DIR__ . '<đường-dẫn>/admin/lib/bootstrap.php';
     *
     * Dùng require_once để config/function/class được load đúng 1 lần / request,
     * bất kể entry point bị include lặp trong cùng request — từ đó config.php
     * không còn cần guard function_exists / defined().
     */
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/function.php';
    require_once __DIR__ . '/class.php';
