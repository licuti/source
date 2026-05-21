<?php
/**
 * ============================================================
 *  CORE HELPERS
 *  Các hàm thiết yếu dành cho hệ thống.
 * ============================================================
 */

if (!function_exists('config')) {
    /**
     * Quản lý cấu hình hệ thống (Getter & Setter)
     * @param string|array|null $key 
     * @param mixed $default
     */
    function config($key = null, $default = null) {
        static $repository = null;

        // 1. Khởi tạo Repository nếu chưa có
        if ($repository === null) {
            $basePath = dirname(dirname(dirname(__FILE__)));
            $repository = array_merge(
                [
                    'database' => include $basePath . '/config/database.php',
                    'lang'     => include $basePath . '/config/languages.php',
                ],
                include $basePath . '/config/app.php'
            );
        }

        // 2. Chế độ SETTER: Nếu truyền vào một mảng
        if (is_array($key)) {
            $repository = array_replace_recursive($repository, $key);
            return true;
        }

        // 3. Chế độ GETTER (Toàn bộ)
        if ($key === null) {
            return $repository;
        }

        // 4. Chế độ GETTER (Dot notation)
        $parts = explode('.', $key);
        $data = $repository;
        foreach ($parts as $part) {
            if (!is_array($data) || !isset($data[$part])) {
                return $default;
            }
            $data = $data[$part];
        }
        return $data;
    }
}

/**
 * Nạp danh sách ngôn ngữ từ DB vào nội bộ hệ thống config
 */
function load_languages() {
    $dbLangs = LanguageModel::getActive();
    
    if (!empty($dbLangs)) {
        $langs = [];
        foreach ($dbLangs as $item) {
            $langs[] = [
                'code'  => $item->code,
                'name'  => $item->name,
                'label' => $item->label,
                'image' => $item->image,
                'price' => $item->price_unit
            ];
        }
        // Cập nhật vào config repository
        config(['lang' => $langs]);
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML để hiển thị an toàn
     */
    function e($value) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump and Die - In dữ liệu debug và dừng chương trình
     */
    function dd(...$args) {
        echo '<div style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; margin: 10px; font-family: monospace; font-size: 14px; 
                         border-left: 5px solid #ff79c6; box-shadow: 0 4px 6px rgba(0,0,0,0.3); white-space: pre-wrap; word-break: break-all;">';
        foreach ($args as $arg) {
            echo '<div style="margin-bottom: 20px;">';
            var_dump($arg);
            echo '</div>';
        }
        echo '</div>';
        die;
    }
}

if (!function_exists('site')) {
    /**
     * Truy xuất thông tin website từ SiteInfoService
     * @param string|null $key  Key cần lấy, null = toàn bộ object
     * @param mixed $default    Giá trị mặc định nếu không tìm thấy
     * @return mixed
     */
    function site($key = null, $default = '') {
        $service = \App\Services\SiteInfoService::getInstance();
        if ($key === null) {
            return $service->all();
        }
        return $service->get($key, $default);
    }
}

if (!function_exists('getProductRating')) {
    /**
     * Lấy đánh giá trung bình của sản phẩm
     * Wrapper cho BinhLuanModel::getSummary() — thay thế legacy function dùng $d global
     */
    function getProductRating($id_sanpham) {
        return \BinhLuanModel::getSummary((int)$id_sanpham);
    }
}

if (!function_exists('view')) {
    /**
     * Helper render view nhanh theo chuẩn Laravel
     * @param string $template Tên template
     * @param array $data Dữ liệu truyền vào view
     * @return string
     */
    function view($template, $data = []) {
        static $renderLevel = 0;
        
        $view = new \App\Core\View();
        
        // Nếu đang ở mức 0 (gọi từ Controller), thì bọc trong layout chính
        if ($renderLevel === 0) {
            $view->setLayout('layouts/main');
        }
        
        $renderLevel++;
        try {
            $result = $view->render($template, $data);
        } finally {
            $renderLevel--;
        }
        
        return $result;
    }
}
if (!function_exists('getExchangeRate')) {
    /**
     * Lấy tỷ giá hối đoái (Mặc định lấy từ config)
     */
    function getExchangeRate($base = 'VND', $target = 'USD') {
        $settings = config('currency', ['auto' => false, 'vnd_usd' => 25450]);
        if ($base == 'VND' && $target == 'USD') return 1 / $settings['vnd_usd'];
        if ($base == 'USD' && $target == 'VND') return $settings['vnd_usd'];
        return 1;
    }
}

if (!function_exists('renderPrice')) {
    /**
     * Hiển thị giá tiền định dạng chuẩn theo ngôn ngữ
     */
    function renderPrice($price) {
        $current_lang = $_SESSION['app_locale'] ?? 'vi';
        $langs = config('lang', []);
        $lang_conf = null;
        foreach ($langs as $l) {
            if ($l['code'] == $current_lang) {
                $lang_conf = $l;
                break;
            }
        }
        
        $price = (float)$price;
        $currency = $lang_conf['price'] ?? 'VND';

        if ($currency != 'VND') {
            $rate = getExchangeRate('VND', $currency);
            $converted_price = $price * $rate;
            if ($currency == 'USD') {
                return '$' . number_format($converted_price, 2, '.', ',');
            }
            return number_format($converted_price, 0, ',', '.') . ' ' . $currency;
        }
        
        return number_format($price, 0, ',', '.') . ' ₫';
    }
}

if (!function_exists('validate_content')) {
    /**
     * Làm sạch nội dung đầu vào
     */
    function validate_content($text) {
        if (empty($text)) return '';
        $text = trim($text);
        $text = strip_tags($text);
        $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        return $text;
    }
}

if (!function_exists('__')) {
    /**
     * Helper gọi bản dịch đa ngôn ngữ từ TextModel
     */
    function __($key) {
        return \TextModel::translate($key);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Lấy chuỗi CSRF token hiện tại từ session.
     * Tạo mới nếu chưa tồn tại.
     * @return string
     */
    function csrf_token() {
        if (empty($_SESSION['token'])) {
            $_SESSION['token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Tạo mã HTML input hidden cho CSRF token để nhúng vào form.
     * @return string
     */
    function csrf_field() {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}
