<?php
    /**
     * Nạp biến môi trường từ `.env` ở thư mục gốc dự án (nếu có).
     *
     * Không dùng parse_ini_file vì password có thể chứa ký tự đặc biệt;
     * tự tách dòng đơn giản, bỏ qua comment `#`.
     *
     * Giới hạn: không hỗ trợ cú pháp `export KEY=val`, giá trị nhiều dòng,
     * hay inline comment sau giá trị (`K=v # c` sẽ giữ nguyên `# c`).
     *
     * @return array<string,string>
     */
    function admin_load_env($path)
    {
        $out = array();
        if (!is_file($path) || !is_readable($path)) {
            return $out;
        }
        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!is_array($lines)) {
            return $out;
        }
        foreach ($lines as $line) {
            $line = trim($line);
            if (substr($line, 0, 3) === "\xEF\xBB\xBF") {
                $line = substr($line, 3);
            }
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $pos = strpos($line, '=');
            if ($pos === false) {
                continue;
            }
            $key = trim(substr($line, 0, $pos));
            $val = trim(substr($line, $pos + 1));
            // Bỏ dấu nháy bao ngoài nếu có
            if (strlen($val) >= 2) {
                $first = $val[0];
                $last  = substr($val, -1);
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $val = substr($val, 1, -1);
                }
            }
            if ($key !== '') {
                $out[$key] = $val;
            }
        }
        return $out;
    }

    // .env nằm ở thư mục gốc dự án (c:\laragon\www\source\.env)
    $__env = admin_load_env(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . '.env');

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
        "pancake" => array(
            "api_key" => "", // Quản lý trong Admin -> Thông tin liên hệ
            "shop_id" => "", 
            "api_url" => "https://crm.pancake.vn/api", 
            "sync_to_crm" => true
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

    // Nguồn: .env có thể ghi độ toàn bộ base URL (ưu tiên cao nhất, khuyên dùng production)
    $__appUrl = isset($__env['APP_URL']) ? rtrim((string) $__env['APP_URL'], '/') : '';

    if ($__appUrl !== '') {
        $__baseUrl = $__appUrl;
    } else {
        if (php_sapi_name() === 'cli') {
            $__host = 'localhost';
            $__scheme = 'http';
        } else {
            $__host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST']
                    : (isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : 'localhost');
            $__scheme = (
                (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
                || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
                || (strtolower((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https')
            ) ? 'https' : 'http';
        }
        $__baseUrl = $__scheme . '://' . $__host;
    }

    define('URLPATH',  $__baseUrl . '/');
    define('urladmin', $__baseUrl . '/admin/');
?>