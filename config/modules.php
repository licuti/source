<?php
/**
 * ============================================================
 * MODULE CONFIGURATION
 * Định nghĩa ID của các module nội dung (tham chiếu từ bảng db_module)
 * Tránh việc dùng magic numbers trong code.
 * ============================================================
 */
return [
    // --- LEGACY MAPPING ---
    // Các Controller cũ (Post, Product...) đang dùng cấu trúc này để lấy ID. 
    // Sẽ được dọn dẹp sau khi tất cả Controller được nâng cấp.
    'home'     => 1,  
    'page'     => 2,  
    'post'     => 3,  
    'product'  => 4,  
    'cart'     => 5,  
    'checkout' => 6,  
    'wishlist' => 7,  
    'login'    => 8,  
    'register' => 9,  
    'forgot'   => 10, 
    'account'  => 11, 
    'upload'   => 12, 
    'video'    => 13, 
    'contact'  => 14, 
    'album'    => 15, 

    // --- NEW CONFIG STRUCTURE ---
    // Dữ liệu tĩnh thay thế hoàn toàn cho bảng db_module.
    // Dùng cho CategoryController và các hệ thống cấu hình mới.
    'settings' => [
        1  => ['id' => 1,  'sort_order' => 1,  'status' => 1, 'title' => 'Trang chủ', 'slug' => 'index', 'config' => []],
        2  => ['id' => 2,  'sort_order' => 1,  'status' => 1, 'title' => 'Trang cố định', 'slug' => 'page', 'config' => []],
        3  => ['id' => 3,  'sort_order' => 3,  'status' => 1, 'title' => 'Bài viết', 'slug' => 'tin-tuc', 'config' => ['num_post' => 12, 'heading_cate' => 'h3', 'heading_ct' => 'h1']],
        4  => ['id' => 4,  'sort_order' => 4,  'status' => 1, 'title' => 'Sản phẩm', 'slug' => 'san-pham', 'config' => ['num_post' => 18, 'heading_cate' => 'h3', 'heading_ct' => 'h1', 'heading_home' => 'h2']],
        5  => ['id' => 5,  'sort_order' => 5,  'status' => 1, 'title' => 'Trang giỏ hàng', 'slug' => 'gio-hang', 'config' => []],
        6  => ['id' => 6,  'sort_order' => 6,  'status' => 1, 'title' => 'Trang thanh toán', 'slug' => 'thanh-toan', 'config' => []],
        7  => ['id' => 7,  'sort_order' => 7,  'status' => 1, 'title' => 'Wishlist', 'slug' => 'wishlist', 'config' => []],
        8  => ['id' => 8,  'sort_order' => 8,  'status' => 0, 'title' => 'Login', 'slug' => 'dang-nhap', 'config' => []],
        9  => ['id' => 9,  'sort_order' => 9,  'status' => 0, 'title' => 'Register', 'slug' => 'dang-ky', 'config' => []],
        10 => ['id' => 10, 'sort_order' => 10, 'status' => 0, 'title' => 'Forgot password', 'slug' => 'quen-mat-khau', 'config' => []],
        11 => ['id' => 11, 'sort_order' => 11, 'status' => 0, 'title' => 'My Account', 'slug' => 'thanh-vien', 'config' => []],
        12 => ['id' => 12, 'sort_order' => 12, 'status' => 0, 'title' => 'Upload file', 'slug' => 'files', 'config' => []],
        13 => ['id' => 13, 'sort_order' => 13, 'status' => 0, 'title' => 'Video', 'slug' => 'video', 'config' => ['num_post' => 9, 'num_col' => 4]],
        14 => ['id' => 14, 'sort_order' => 14, 'status' => 1, 'title' => 'Liên hệ', 'slug' => 'lien-he', 'config' => []],
        15 => ['id' => 15, 'sort_order' => 15, 'status' => 1, 'title' => 'Album ảnh', 'slug' => 'album', 'config' => ['num_post' => 15, 'num_col' => 3]],
    ]
];
