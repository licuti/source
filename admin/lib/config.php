<?php
    // Helper đọc .env nằm ở root dự án (trung lập, dùng chung với lms/).
    require_once dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'env.php';

    // .env nằm ở thư mục gốc dự án (c:\laragon\www\source\.env)
    $__env = env_load(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');

    // Giá trị dự phòng giữ nguyên như bản cũ để không phá vỡ môi trường local
    $__dbPrefix  = isset($__env['DB_PREFIX']) ? $__env['DB_PREFIX'] : 'db_';
    $__dbHost    = isset($__env['DB_HOST'])   ? $__env['DB_HOST']   : 'localhost';
    $__dbName    = isset($__env['DB_NAME'])   ? $__env['DB_NAME']   : 'phuongnamv_db';
    $__dbUser    = isset($__env['DB_USER'])   ? $__env['DB_USER']   : 'root';
    $__dbPass    = isset($__env['DB_PASS'])   ? $__env['DB_PASS']   : '';

    $config = array(
        'database' => array(
            'refix'      => $__dbPrefix,
            'servername' => $__dbHost,
            'database'   => $__dbName,
            'username'   => $__dbUser,
            'password'   => $__dbPass
        ),
        'lang' => array(
            '0'=>array(
                "code"  => "vi",
                "label"  => "VIE",
                "name"  => "Tiếng việt",
                "image" => "/templates/images/icon_vi.png",
                "price" => "VND"
            ),
            '1'=>array(
                "code"  => "ko",
                "label"  => "KO",
                "name"  => "Tiếng Hàn",
                "image" => "/templates/images/icon_en.png",
                "price" => "KRW"
            )
        ),
        "product"=> array(
            "thong_so"=>"1",
            "video"=>"1",
            "file"=> "0",
            "ma_sp"=>"1",
            "gia"=>"1",
            "khuyen_mai"=>"1",
            "don_vi_tinh"=>"0",
            "paging"=>"24"
        ),
        "posts"=> array(
            "video"=>"0",
            "file"=>"0",
            "paging"=>"12"
        ),
        "currency" => array(
            "auto" => false,       // true: dùng API, false: dùng tỷ giá cố định bên dưới
            "vnd_usd" => 25450,   // Tỷ giá 1 USD = 25.450 VNĐ (áp dụng khi auto = false)
        ),
        "shipping" => array(
            "weight_unit" => "g",    // Đơn vị cân nặng hiển thị
            "weight_factor" => 1000,  // Hệ số chia (vd: 1000 nếu nhập gram trong DB)
        ),
        "jwt" => array(
            // Secret 256-bit (64 hex) để ký HS256 cookie keyId. Đặt trong .env.
            "secret" => isset($__env['JWT_SECRET']) ? (string) $__env['JWT_SECRET'] : ""
        ),
        /*
         * Runtime migrations — mặc định TẮT.
         * Khi true, một số module sẽ tự ALTER TABLE trong request runtime
         * (ví dụ category_drop_unused_columns). Chỉ bật tạm để dọn schema.
         * Bật qua .env: RUNTIME_MIGRATIONS=true
         */
        'runtime_migrations' => (
            isset($__env['RUNTIME_MIGRATIONS'])
            && strtolower(trim((string) $__env['RUNTIME_MIGRATIONS'])) === 'true'
        )
    );

    // Base URL tuyệt đối, lấy từ .env (APP_URL). Không tự suy luận để tránh sai
    // khi deploy ở subfolder (vd /source/) — HTTP_HOST không chứa path con.
        if (!empty($_SERVER['HTTP_X_FORWARDED_HOST'])) {
        $__fwdProto = !empty($_SERVER['HTTP_X_FORWARDED_PROTO']) ? $_SERVER['HTTP_X_FORWARDED_PROTO'] : 'https';
        $__basePath = !empty($__env['APP_URL']) ? (parse_url($__env['APP_URL'], PHP_URL_PATH) ?: '') : '';
        $__baseUrl  = $__fwdProto . '://' . $_SERVER['HTTP_X_FORWARDED_HOST'] . rtrim($__basePath, '/');
    } else {
        $__baseUrl = rtrim($__env['APP_URL'], '/');
    }

    define('URLPATH',  $__baseUrl . '/');
    define('urladmin', $__baseUrl . '/admin/');
?>