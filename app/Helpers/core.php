<?php
/**
 * ============================================================
 *  CORE HELPERS
 *  Các hàm thiết yếu dành cho hệ thống.
 * ============================================================
 */

if (!function_exists('base_path')) {
    /**
     * Lấy đường dẫn tuyệt đối đến thư mục gốc của project
     */
    function base_path($path = '') {
        $base = dirname(dirname(__DIR__));
        return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, '/') : $base;
    }
}

if (!function_exists('render_attrs')) {
    /**
     * Chuyển đổi mảng các thuộc tính thành chuỗi HTML chuẩn
     * VD: ['id' => 'my-id', 'required' => true] -> 'id="my-id" required'
     */
    function render_attrs($attrs = []) {
        if (empty($attrs)) return '';
        $html = [];
        foreach ($attrs as $key => $value) {
            if (is_bool($value)) {
                if ($value) {
                    $html[] = htmlspecialchars($key);
                }
            } elseif ($value !== null && $value !== '') {
                $html[] = htmlspecialchars($key) . '="' . htmlspecialchars((string)$value) . '"';
            }
        }
        return implode(' ', $html);
    }
}

if (!function_exists('renderCategoryTree')) {
    /**
     * Hiển thị cây danh mục dùng thẻ <option> cho thẻ <select>
     * Hỗ trợ đệ quy n cấp và chặn chọn chính nó làm cha
     */
    function renderCategoryTree($categories, $selectedId = 0, $currentEditingId = 0, $prefix = '') {
        foreach ($categories as $cat) {
            if ($currentEditingId > 0 && $cat->id_code == $currentEditingId) continue;
            $selected = ($cat->id_code == $selectedId) ? 'selected' : '';
            $catName = $cat->title ?? ($cat->ten ?? ($cat->name ?? ''));
            echo '<option value="' . $cat->id_code . '" ' . $selected . '>' . $prefix . htmlspecialchars($catName) . '</option>';
            if (!empty($cat->children)) {
                renderCategoryTree($cat->children, $selectedId, $currentEditingId, $prefix . '--- ');
            }
        }
    }
}

if (!function_exists('app_path')) {
    function app_path($path = '') {
        return base_path('app' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/') : ''));
    }
}

if (!function_exists('config_path')) {
    function config_path($path = '') {
        return base_path('config' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/') : ''));
    }
}

if (!function_exists('public_path')) {
    function public_path($path = '') {
        // Tuỳ cấu trúc, có thể là thư mục gốc hoặc public/
        return base_path($path);
    }
}

if (!function_exists('calculateVAT')) {
    function calculateVAT($amount, $rate, $type = 1) {
        $amount = (float)$amount;
        $rate = (float)$rate;
        $type = (int)$type;
        
        $vat_amount = 0;
        $total = $amount;

        if ($rate > 0 && $type > 0) {
            if ($type == 1) {
                // Exclusive (Giá chưa bao gồm VAT -> cộng thêm)
                $vat_amount = $amount * ($rate / 100);
                $total = $amount + $vat_amount;
            } else if ($type == 2) {
                // Inclusive (Giá đã bao gồm VAT -> trích xuất)
                $total = $amount;
                $vat_amount = $amount - ($amount / (1 + $rate / 100));
            }
        }

        return [
            'amount' => $vat_amount,
            'total' => $total
        ];
    }
}

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
                    'database'           => include $basePath . '/config/database.php',
                    'lang'               => include $basePath . '/config/languages.php',
                    'route_translations' => include $basePath . '/config/route_translations.php',
                    'modules'            => include $basePath . '/config/modules.php',
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
        
        // Tự động gán layout tương ứng (Frontend hoặc Admin)
        if ($renderLevel === 0) {
            if (strpos($template, 'admin.') === 0) {
                // View của admin (trừ trang login) thì dùng layout admin
                if ($template !== 'admin.auth.login') {
                    $view->setLayout('admin.layouts.main');
                }
            } else {
                // Các view còn lại dùng layout frontend
                $view->setLayout('layouts/main');
            }
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

if (!function_exists('hasPermission')) {
    /**
     * Kiểm tra quyền hiện tại của User (dành cho View/UI)
     * @param string $routeName VD: 'admin.user' hoặc 'admin.user.index'
     * @param string $action 'can_view', 'can_add', 'can_edit', 'can_delete'
     * @return bool
     */
    function hasPermission($routeName, $action = 'can_view') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        $isAdmin = $_SESSION['is_admin'] ?? 0;
        if ($isAdmin == 1) return true; // Super Admin có mọi quyền
        
        $roleId = $_SESSION['role_id'] ?? 0;
        if ($roleId == 0) return false;

        $parts = explode('.', $routeName);
        if (count($parts) >= 2) {
            $prefix = $parts[0] . '.' . $parts[1]; // admin.user
            
            // Tìm module id theo cache hoặc query DB
            static $modulesCache = [];
            if (!isset($modulesCache[$prefix])) {
                $module = \ModuleAdminModel::where('route_name', 'LIKE', $prefix . '.%')->first();
                $modulesCache[$prefix] = $module ? $module->id : 0;
            }
            $moduleId = $modulesCache[$prefix];
            
            if ($moduleId > 0) {
                // Kiểm tra trong session cache
                $permsCache = $_SESSION['role_permissions'] ?? [];
                if (isset($permsCache[$moduleId])) {
                    return !empty($permsCache[$moduleId][$action]);
                } else {
                    // Fallback
                    $perm = \App\Models\RolePermissionModel::where('role_id', $roleId)
                        ->where('module_id', $moduleId)->first();
                    if ($perm) {
                        return $perm->{$action} == 1;
                    }
                }
            }
        }
        
        return false;
    }
}

if (!function_exists('arr_get')) {
    /**
     * Lấy giá trị từ mảng đa chiều bằng dot notation (VD: "title.vi")
     */
    function arr_get($array, $key, $default = null) {
        if (!is_array($array)) return $default;
        if (array_key_exists($key, $array)) return $array[$key];
        
        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }
        return $array;
    }
}

if (!function_exists('old')) {
    /**
     * Trích xuất dữ liệu cũ từ Session Flash Data (nếu validate thất bại)
     * Hỗ trợ dot notation, ví dụ: old('title.vi')
     */
    function old($key = null, $default = '') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $oldInput = $_SESSION['_old_input'] ?? [];
        
        if ($key === null) return $oldInput;
        return arr_get($oldInput, $key, $default);
    }
}

if (!function_exists('errors')) {
    /**
     * Trích xuất thông báo lỗi từ Session Flash Data (nếu validate thất bại)
     * Trả về mảng tất cả lỗi, hoặc chuỗi lỗi của một field cụ thể
     */
    function errors($key = null) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $errors = $_SESSION['_errors'] ?? [];
        
        if ($key === null) return $errors;
        return $errors[$key] ?? null;
    }
}

if (!function_exists('user')) {
    /**
     * Lấy thông tin người dùng đang đăng nhập từ Session
     * Trả về Object stdClass hoặc null
     */
    function user() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        if (!isset($_SESSION['id_user'])) {
            return null;
        }
        
        $user = new \stdClass();
        $user->id = $_SESSION['id_user'];
        $user->username = $_SESSION['user_admin'] ?? '';
        $user->fullname = $_SESSION['name'] ?? '';
        $user->role_id = $_SESSION['role_id'] ?? $_SESSION['quyen'] ?? 0;
        $user->is_admin = $_SESSION['is_admin'] ?? 0;
        
        return $user;
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

if (!function_exists('session')) {
    /**
     * Lấy hoặc gán giá trị session, tự động start session nếu chưa có.
     * Nếu lấy key flash (success/error), tự động xóa sau khi lấy.
     */
    function session($key = null, $default = null) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if ($key === null) {
            return $_SESSION;
        }
        
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $_SESSION[$k] = $v;
            }
            return true;
        }

        $value = $_SESSION[$key] ?? $default;
        
        // Cơ chế flash session cơ bản cho success/error
        if (in_array($key, ['success', 'error']) && isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
        
        return $value;
    }
}

if (!function_exists('hasPermission')) {
    /**
     * Kiểm tra quyền của User hiện tại với một module.
     * Dùng để ẩn/hiện các nút bấm trên giao diện Admin.
     * 
     * @param string $moduleRoutePrefix Tiền tố route (VD: admin.category)
     * @param string $action Hành động (view, add, edit, delete)
     * @return bool
     */
    function hasPermission($moduleRoutePrefix, $action = 'view') {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $is_admin = $_SESSION['is_admin'] ?? 0;
        if ($is_admin == 1) return true; // Super Admin bypasses all
        
        $role_id = $_SESSION['role_id'] ?? 0;
        if ($role_id == 0) return false;
        
        // Caching module IDs by prefix in memory to prevent N+1 queries during loop rendering
        static $moduleMapping = null;
        if ($moduleMapping === null) {
            $moduleMapping = [];
            try {
                $modules = \ModuleAdminModel::where('is_active', 1)->get();
                foreach ($modules as $mod) {
                    if (!empty($mod->route_name)) {
                        $parts = explode('.', $mod->route_name);
                        if (count($parts) >= 2) {
                            $prefix = $parts[0] . '.' . $parts[1];
                            $moduleMapping[$prefix] = $mod->id;
                        }
                    }
                }
            } catch (\Exception $e) {}
        }
        
        $moduleId = $moduleMapping[$moduleRoutePrefix] ?? null;
        if (!$moduleId) {
            // Fallback
            $module = \ModuleAdminModel::where('route_name', 'LIKE', $moduleRoutePrefix . '.%')->first();
            if ($module) {
                $moduleId = $module->id;
                $moduleMapping[$moduleRoutePrefix] = $moduleId;
            } else {
                return false;
            }
        }
        
        $permsCache = $_SESSION['role_permissions'] ?? [];
        if (!isset($permsCache[$moduleId])) {
            return false;
        }
        
        $perm = $permsCache[$moduleId];
        
        switch ($action) {
            case 'view':   return !empty($perm['can_view']);
            case 'add':    return !empty($perm['can_add']);
            case 'edit':   return !empty($perm['can_edit']);
            case 'delete': return !empty($perm['can_delete']);
            default:       return false;
        }
    }
}

if (!function_exists('setting')) {
    /**
     * Lấy giá trị từ Cấu hình Website
     * 
     * @param string|null $key Tên biến cấu hình cần lấy (nếu null, trả về toàn bộ mảng cấu hình)
     * @param mixed $default Giá trị mặc định nếu không tìm thấy key
     * @return mixed
     */
    function setting($key = null, $default = '') {
        $settingModel = new \App\Models\SettingModel();
        if ($key === null) {
            return $settingModel->getAll();
        }
        return $settingModel->getValue($key, $default);
    }
}
