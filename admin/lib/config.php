<?php
    $rf = str_replace('www.', '', $_SERVER["SERVER_NAME"]);

    $config = array(
        'database' => array(
            'refix' => "db_",
            'servername' => 'localhost',
            'database' => 'phuongnamv_db_new',
            'username' => 'root',
            'password' => ''
            // 'password' => 'TfwEdtWP1'
            
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
                "code"  => "en",
                "label"  => "ENG",
                "name"  => "Tiếng anh",
                "image" => "/templates/images/icon_en.png",
                "price" => "USD"
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
            "paging"=>"1"
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
        "weight" => array(
            "unit" => "g",         // Đơn vị mặc định cho sản phẩm (g hoặc kg)
            "conversion" => 1000   // Hệ số quy đổi ra kg để tính ship (1000 nếu đơn vị là g, 1 nếu đơn vị là kg)
        )
    );

    define("URLPATH","http://".$_SERVER["SERVER_NAME"].":81/");
    define("urladmin","http://".$_SERVER["SERVER_NAME"].":81/admin/");
?>