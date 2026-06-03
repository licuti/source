<?php
/**
 * ============================================================
 *  Route Translations
 *  Mapping slug giữa các ngôn ngữ cho các route có prefix.
 *  - Key   : tên route (trùng với route name trong web.php)
 *  - value : array slug theo từng ngôn ngữ
 *
 *  Cấu trúc: 'route_key' => ['vi' => 'slug-vi', 'en' => 'slug-en']
 * ============================================================
 */
return [
    'product'  => ['vi' => 'san-pham',  'en' => 'product'],
    'news'     => ['vi' => 'tin-tuc',   'en' => 'news'],
    'category' => ['vi' => 'danh-muc',  'en' => 'category'],
    'contact'  => ['vi' => 'lien-he',   'en' => 'contact'],
    'login'    => ['vi' => 'dang-nhap', 'en' => 'login'],
    'register' => ['vi' => 'dang-ky',   'en' => 'register'],
    'logout'   => ['vi' => 'dang-xuat', 'en' => 'logout'],
];
