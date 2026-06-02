<?php
return [
    "debug" => env('APP_DEBUG', true),
    "protection" => env('SITE_PASSWORD_PROTECT', true),
    "name" => env('APP_NAME', 'My Website'),
    "locale" => "vi",
    "urls" => [
        "base" => rtrim(env('APP_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/') . "/",
        "admin" => rtrim(env('APP_URL', (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . ($_SERVER['HTTP_HOST'] ?? 'localhost')), '/') . "/admin/"
    ],
    "product" => [
        "thong_so" => "1",
        "video" => "1",
        "file" => "0",
        "ma_sp" => "1",
        "gia" => "1",
        "khuyen_mai" => "1",
        "don_vi_tinh" => "0",
        "paging" => "24"
    ],
    "posts" => [
        "video" => "0",
        "file" => "0",
        "paging" => "12"
    ],
    "currency" => [
        "auto" => false,
        "vnd_usd" => 25450,
    ],
    "weight" => [
        "unit" => "g",
        "conversion" => 1000
    ]
];
