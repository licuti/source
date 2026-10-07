<?php

/*
 * ĐÃ BỎ 2 thư viện nặng (tổng ~85 KB, nạp ở MỌI request dù hiếm dùng):
 *   - simple_html_dom.php : thay bằng DOMDocument có sẵn của PHP
 *   - resize-class.php    : không còn nơi nào gọi resize_img()
 * Bản gốc được lưu ở admin/_backup_ui/lib/removed-libs.zip
 *
 * Các hàm đã xoá (mã chết, không còn lời gọi):
 *   - get_size_img(), Uploadfile(), multiple_Uploadfile()
 *   - getUploadRules(), sanitizeFileName()
 *   - check_phone(), admin_module_any()
 *
 * Các hàm đã đổi tên:
 *   - numberformat()  → format_number()
 *   - catchuoi()      → truncate_text()
 *   - bodautv()       → slugify()  (đồng thời tối ưu logic)
 */

define("LANG", get_json('lang', '0', 'code'));

function get_json($object, $key = '', $attributes = '')
{
    global $config;
    $arr_config = $config;
    if ($key == '') {
        return $arr_config[$object];
    } elseif ($key != '' && $attributes == '') {
        return $arr_config[$object][$key];
    } else {
        return $arr_config[$object][$key][$attributes];
    }
}

/** Định dạng số kiểu Việt Nam: dấu chấm ngăn cách hàng nghìn (1.234.567). */
function format_number($number)
{
    return str_replace(',', '.', number_format($number));
}

/**
 * CSRF / flash / rate-limit / path ảnh — dùng chung cho module admin.
 * token() giữ tương thích: trả về token session (không xoay mỗi lần gọi).
 */
function admin_session_ensure()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
}

function admin_csrf_token($rotate = false)
{
    admin_session_ensure();
    if ($rotate || empty($_SESSION['admin_csrf'])) {
        if (function_exists('random_bytes')) {
            try {
                $_SESSION['admin_csrf'] = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                $_SESSION['admin_csrf'] = sha1(uniqid((string) mt_rand(), true) . microtime(true));
            }
        } else {
            $_SESSION['admin_csrf'] = sha1(uniqid((string) mt_rand(), true) . microtime(true));
        }
    }
    // Đồng bộ key cũ nếu code nào còn đọc $_SESSION['token']
    $_SESSION['token'] = $_SESSION['admin_csrf'];
    return (string) $_SESSION['admin_csrf'];
}

function token()
{
    return admin_csrf_token(false);
}

/**
 * @param string|null $token  null = lấy từ $_POST['csrf_token']
 */
function admin_csrf_verify($token = null)
{
    admin_session_ensure();
    if ($token === null) {
        $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
    }
    $token   = (string) $token;
    $session = '';
    if (!empty($_SESSION['admin_csrf'])) {
        $session = (string) $_SESSION['admin_csrf'];
    } elseif (!empty($_SESSION['token'])) {
        $session = (string) $_SESSION['token'];
    }
    if ($session === '' || $token === '') {
        return false;
    }
    return hash_equals($session, $token);
}

function admin_flash($type, $message)
{
    admin_session_ensure();
    $_SESSION['admin_flash'] = array(
        'type'    => (string) $type,
        'message' => (string) $message,
    );
}

/**
 * Flash một lần trên trang login (sau khi session đã destroy, vd. đổi mật khẩu).
 * Dùng cookie HttpOnly ngắn hạn.
 */
function admin_login_flash_set($code)
{
    $code = preg_replace('/[^a-z0-9_\-]/i', '', (string) $code);
    if ($code === '') {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    // PHP 7.3+: array options; fallback tham số cũ
    if (PHP_VERSION_ID >= 70300) {
        setcookie('admin_login_flash', $code, array(
            'expires'  => time() + 300,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ));
    } else {
        setcookie('admin_login_flash', $code, time() + 300, '/; samesite=Lax', '', $secure, true);
    }
    $_COOKIE['admin_login_flash'] = $code;
}

function admin_login_flash_take()
{
    $code = isset($_COOKIE['admin_login_flash']) ? (string) $_COOKIE['admin_login_flash'] : '';
    if ($code === '' && isset($_GET['pwd']) && (string) $_GET['pwd'] === '1') {
        $code = 'pwd_ok';
    }
    if ($code === '') {
        return '';
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    if (PHP_VERSION_ID >= 70300) {
        setcookie('admin_login_flash', '', array(
            'expires'  => time() - 3600,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        ));
    } else {
        setcookie('admin_login_flash', '', time() - 3600, '/', '', $secure, true);
    }
    unset($_COOKIE['admin_login_flash']);

    $map = array(
        'pwd_ok' => 'Đã cập nhật hồ sơ và đổi mật khẩu thành công. Vui lòng đăng nhập lại.',
    );
    return isset($map[$code]) ? $map[$code] : '';
}

/** true = còn được phép thử; false = đã vượt ngưỡng trong cửa sổ thời gian. */
function admin_rate_allow($bucket, $maxAttempts = 5, $windowSeconds = 900)
{
    admin_session_ensure();
    $bucket        = preg_replace('/[^a-zA-Z0-9_\-:]/', '', (string) $bucket);
    $maxAttempts   = max(1, (int) $maxAttempts);
    $windowSeconds = max(60, (int) $windowSeconds);
    $now           = time();

    if (!isset($_SESSION['_rate']) || !is_array($_SESSION['_rate'])) {
        $_SESSION['_rate'] = array();
    }
    if (empty($_SESSION['_rate'][$bucket]) || !is_array($_SESSION['_rate'][$bucket])) {
        $_SESSION['_rate'][$bucket] = array('n' => 0, 't' => $now);
    }
    $row = &$_SESSION['_rate'][$bucket];
    if (($now - (int) $row['t']) > $windowSeconds) {
        $row['n'] = 0;
        $row['t'] = $now;
    }
    return ((int) $row['n']) < $maxAttempts;
}

function admin_rate_hit($bucket, $windowSeconds = 900)
{
    admin_session_ensure();
    $bucket        = preg_replace('/[^a-zA-Z0-9_\-:]/', '', (string) $bucket);
    $windowSeconds = max(60, (int) $windowSeconds);
    $now           = time();

    if (!isset($_SESSION['_rate']) || !is_array($_SESSION['_rate'])) {
        $_SESSION['_rate'] = array();
    }
    if (empty($_SESSION['_rate'][$bucket]) || !is_array($_SESSION['_rate'][$bucket])) {
        $_SESSION['_rate'][$bucket] = array('n' => 0, 't' => $now);
    }
    $row = &$_SESSION['_rate'][$bucket];
    if (($now - (int) $row['t']) > $windowSeconds) {
        $row['n'] = 0;
        $row['t'] = $now;
    }
    $row['n'] = ((int) $row['n']) + 1;
    return (int) $row['n'];
}

function admin_rate_clear($bucket)
{
    admin_session_ensure();
    $bucket = preg_replace('/[^a-zA-Z0-9_\-:]/', '', (string) $bucket);
    if (isset($_SESSION['_rate'][$bucket])) {
        unset($_SESSION['_rate'][$bucket]);
    }
}

/**
 * Chuẩn hoá path ảnh trong media/images — chặn traversal & URL lạ.
 * Trả về path tương đối (vd. user/a.png) hoặc '' nếu không hợp lệ.
 */
function admin_sanitize_image_path($path)
{
    $path = trim(str_replace(chr(92), '/', (string) $path));
    if ($path === '') {
        return '';
    }
    if (preg_match('#^(https?:)?//#i', $path) || stripos($path, 'data:') === 0) {
        return '';
    }
    $path = preg_replace('#^(?:\./|../)+#', '', $path);
    $path = preg_replace('#^(?:media/)?images/#i', '', $path);
    $path = ltrim($path, '/');
    if ($path === '' || strpos($path, '..') !== false) {
        return '';
    }
    // Chỉ giữ ký tự an toàn cho tên file/thư mục upload
    if (!preg_match('#^[A-Za-z0-9][A-Za-z0-9._/-]*$#', $path)) {
        return '';
    }
    if (substr($path, -1) === '/') {
        return '';
    }
    $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    $ok  = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'ico');
    if (!in_array($ext, $ok, true)) {
        return '';
    }
    return $path;
}

/**
 * Cắt chuỗi an toàn UTF-8 về tối đa $n ký tự, lùi về khoảng trắng gần nhất.
 * (Tên cũ: catchuoi.)
 */
function truncate_text($text, $n = 80)
{
    $text = strip_tags($text);

    // Dùng mb_* vì tiếng Việt là UTF-8 — strlen/substr đếm theo byte
    if (mb_strlen($text, 'UTF-8') <= $n) {
        return $text;
    }

    $text = mb_substr($text, 0, $n, 'UTF-8');

    // Cắt lùi về khoảng trắng gần nhất để không đứt giữa từ
    $lastSpace = mb_strrpos($text, ' ', 0, 'UTF-8');
    if ($lastSpace !== false && $lastSpace > 0) {
        $text = mb_substr($text, 0, $lastSpace, 'UTF-8');
    }

    return trim($text) . '...';
}

/**
 * Chuyển chuỗi tiếng Việt thành slug: chữ thường, ASCII, số và dấu gạch ngang.
 * Tối ưu so với bodautv() cũ:
 *   - viết thường (slug chuẩn hoá),
 *   - bỏ dấu bằng bảng quy đổi + cờ /iu nên phủ cả chữ HOA có dấu,
 *   - lọc bằng whitelist [a-z0-9] thay vì loại từng ký tự đặc biệt lẻ tẻ,
 *   - gộp nhiều dấu gạch liền kề, không để gạch ở hai đầu.
 * (Tên cũ: bodautv.)
 */
function slugify($str)
{
    $str = (string) $str;

    // Bỏ dấu tiếng Việt + Latin phổ biến (cờ /iu phủ cả chữ HOA)
    $map = array(
        'a' => 'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ|ä|å|ā',
        'd' => 'đ',
        'e' => 'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ|ë|ē',
        'i' => 'í|ì|ỉ|ĩ|ị|ï|ī',
        'o' => 'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ|ö|ō',
        'u' => 'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự|ü|ū',
        'y' => 'ý|ỳ|ỷ|ỹ|ỵ|ÿ',
        'c' => 'ç',
        'n' => 'ñ',
    );
    foreach ($map as $ascii => $accents) {
        $str = preg_replace("/($accents)/iu", $ascii, $str);
    }

    // Viết thường sau khi đã bỏ dấu
    $str = mb_strtolower($str, 'UTF-8');

    // Ký tự không hợp lệ → gạch ngang; gộp liền kề; bỏ hai đầu
    $str = preg_replace('/[^a-z0-9]+/', '-', $str);

    return trim($str, '-');
}

function replaceHTMLCharacter($str)
{
    $str = preg_replace('/&/',   '&amp;',  $str);
    $str = preg_replace('/</',   '&lt;',   $str);
    $str = preg_replace('/>/',   '&gt;',   $str);
    $str = preg_replace('/\"/',  '&quot;', $str);
    $str = preg_replace('/\'/',  '&apos;', $str);
    return $str;
}

/**
 * Chặn nội dung có dấu hiệu chèn mã (webshell).
 *
 * @param  string $text
 * @return string Rỗng nếu phát hiện nguy hiểm
 */
function check_shell($text)
{
    if ($text === '' || $text === null) {
        return $text;
    }

    $patterns = array(
        // Thẻ mở PHP
        '/<\?(php|=)?\s/i',
        '/<%/',

        // Hàm thực thi lệnh hệ thống / eval
        '/\b(eval|assert|shell_exec|exec|passthru|system|proc_open|popen|pcntl_exec)\s*\(/i',

        // Hàm ghi/đọc file
        '/\b(fopen|fwrite|fputs|file_put_contents|unlink|move_uploaded_file)\s*\(/i',

        // Che giấu payload
        '/\b(base64_decode|gzinflate|gzuncompress|str_rot13|convert_uudecode)\s*\(/i',

        // Truy cập biến siêu toàn cục trong nội dung
        '/\$_(GET|POST|REQUEST|SERVER|FILES|ENV|COOKIE|SESSION)\b/',

        // Thẻ và thuộc tính có thể chạy script
        '/<\s*(script|iframe|object|embed|form|input|textarea|button)\b/i',
        '/\son\w+\s*=/i',
        '/javascript\s*:/i',
    );

    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $text)) {
            return '';
        }
    }

    return $text;
}

/**
 * Làm sạch dữ liệu người dùng nhập.
 *
 * @param string $text
 * @param string $type ''  : bỏ mọi thẻ HTML; nếu có link/URL -> trả về rỗng
 *                         (dùng cho form liên hệ, chống spam)
 *                    '0' : chỉ bỏ mọi thẻ HTML, vẫn cho phép URL
 *                    '1' : giữ HTML nhưng loại bỏ <img>, <script>, <a>
 * @return string
 */
function validate_content($text, $type = '')
{
    if ($text === '' || $text === null) {
        return $text;
    }

    if ($type === '') {
        // Có thẻ <a> hoặc có URL -> coi là spam, bỏ toàn bộ nội dung
        if (stripos($text, '<a') !== false
            || stripos($text, 'http') !== false
            || stripos($text, 'www.') !== false) {
            return '';
        }
        return addslashes(strip_tags($text));
    }

    if ($type === '0') {
        return addslashes(strip_tags($text));
    }

    // $type khác: giữ HTML nhưng bỏ <img>, <script>, <a>
    $doc  = new DOMDocument();
    $prev = libxml_use_internal_errors(true);
    $doc->loadHTML(
        '<?xml encoding="UTF-8"?><div id="__root">' . $text . '</div>',
        LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD
    );
    libxml_clear_errors();
    libxml_use_internal_errors($prev);

    foreach (array('img', 'script', 'a') as $tag) {
        $nodes = $doc->getElementsByTagName($tag);
        for ($i = $nodes->length - 1; $i >= 0; $i--) {
            $node = $nodes->item($i);
            if ($node && $node->parentNode) {
                $node->parentNode->removeChild($node);
            }
        }
    }

    $root = $doc->getElementById('__root');
    $html = '';
    if ($root) {
        foreach ($root->childNodes as $child) {
            $html .= $doc->saveHTML($child);
        }
    }

    return addslashes($html);
}

function get_shipping_fee($id_tinh, $id_huyen = '', $id_xa = '', $tong_don_hang = 0, $total_weight = 0, $has_box = false)
{
    global $d, $config;

    // 1. Lấy cấu hình từ Setting
    $setting = $d->simple_fetch("SELECT free_ship_threshold, default_ship_phi, ship_base_weight, ship_rounding, box_phi FROM #_thongtin WHERE lang = '" . LANG . "' LIMIT 1");

    $free_ship_threshold = (float) ($setting['free_ship_threshold'] ?? 0);
    $default_ship_phi    = (float) ($setting['default_ship_phi'] ?? 30000);
    $ship_base_weight    = (float) ($setting['ship_base_weight'] ?? 1.0);
    $ship_rounding       = (int) ($setting['ship_rounding'] ?? 0);
    $box_phi             = (float) ($setting['box_phi'] ?? 0);

    // 2. Kiểm tra mức miễn phí vận chuyển
    if ($free_ship_threshold > 0 && $tong_don_hang >= $free_ship_threshold) {
        return [
            'fee'         => 0,
            'description' => "Đơn hàng từ " . renderPrice($free_ship_threshold) . " được miễn phí giao hàng",
        ];
    }

    // 3. Xử lý cân nặng
    $weight_factor       = (float) ($config['shipping']['weight_factor'] ?? 1000);
    $current_weight_kg   = $total_weight / $weight_factor;
    $ship_base_weight_kg = $ship_base_weight / $weight_factor;

    if ($ship_rounding == 1) {
        $current_weight_kg = ceil($current_weight_kg);
    }

    $phi_ship         = 0;
    $phi_extra_kg     = 0;
    $free_ship_weight = 0;
    $free_ship_weight_kg = 0;
    $ghi_chu          = '';
    $found            = false;

    // 4. Tìm phí theo Phường/Xã
    if ($id_xa != '') {
        $row = $d->simple_fetch("SELECT phi_ship, phi_extra_kg, free_ship_weight, ghi_chu FROM #_ship WHERE id_xa = '$id_xa' AND hien_thi = 1 LIMIT 1");
        if (!empty($row)) {
            $phi_ship            = $row['phi_ship'];
            $phi_extra_kg        = $row['phi_extra_kg'];
            $free_ship_weight    = (float) ($row['free_ship_weight'] ?? 0);
            $free_ship_weight_kg = $free_ship_weight / $weight_factor;
            $ghi_chu             = $row['ghi_chu'];
            $found               = true;
        }
    }

    // 5. Tìm phí theo Quận/Huyện
    if (!$found && $id_huyen != '') {
        $row = $d->simple_fetch("SELECT phi_ship, phi_extra_kg, free_ship_weight, ghi_chu FROM #_ship WHERE id_tinh = '$id_tinh' AND id_huyen = '$id_huyen' AND (id_xa IS NULL OR id_xa = '') AND hien_thi = 1 LIMIT 1");
        if (!empty($row)) {
            $phi_ship            = $row['phi_ship'];
            $phi_extra_kg        = $row['phi_extra_kg'];
            $free_ship_weight    = (float) ($row['free_ship_weight'] ?? 0);
            $free_ship_weight_kg = $free_ship_weight / $weight_factor;
            $ghi_chu             = $row['ghi_chu'];
            $found               = true;
        }
    }

    // 6. Tìm phí theo Tỉnh/Thành phố
    if (!$found) {
        $row = $d->simple_fetch("SELECT phi_ship, phi_extra_kg, free_ship_weight, ghi_chu FROM #_ship WHERE id_tinh = '$id_tinh' AND (id_huyen IS NULL OR id_huyen = '') AND (id_xa IS NULL OR id_xa = '') AND hien_thi = 1 LIMIT 1");
        if (!empty($row)) {
            $phi_ship            = $row['phi_ship'];
            $phi_extra_kg        = $row['phi_extra_kg'];
            $free_ship_weight    = (float) ($row['free_ship_weight'] ?? 0);
            $free_ship_weight_kg = $free_ship_weight / $weight_factor;
            $ghi_chu             = $row['ghi_chu'];
            $found               = true;
        }
    }

    // 7. Phí mặc định
    if (!$found) {
        $phi_ship = $default_ship_phi;
    }

    // Miễn phí theo khối lượng khu vực
    if ($free_ship_weight_kg > 0 && $current_weight_kg >= $free_ship_weight_kg) {
        $display_weight = ($free_ship_weight >= 1000 && $weight_factor == 1000)
            ? ($free_ship_weight / 1000) . ' kg'
            : $free_ship_weight . ($config['shipping']['weight_unit'] ?? 'kg');
        return [
            'fee'         => 0,
            'description' => "Đơn hàng từ " . $display_weight . " được miễn phí giao hàng",
        ];
    }

    // 8. Phụ phí cân nặng
    $extra_weight    = max(0, $current_weight_kg - $ship_base_weight_kg);
    $total_phi_ship  = (float) $phi_ship + ($extra_weight * (float) $phi_extra_kg);

    // 9. Phí đóng thùng
    if ($has_box) {
        $total_phi_ship += $box_phi;
    }

    return [
        'fee'         => $total_phi_ship,
        'description' => $ghi_chu,
        'box_phi'     => $has_box ? $box_phi : 0,
    ];
}

/**
 * Đồng bộ đường dẫn (url/alias) trên menu_items khi nguồn đổi alias.
 *
 * @param string $object_type  category | sanpham | tintuc
 * @param int    $object_id    id dòng nguồn
 * @param string $new_url      alias mới
 * @return bool
 */
if (!function_exists('sync_menu_item_url')) {
    function sync_menu_item_url($object_type, $object_id, $new_url)
    {
        global $d;

        $object_type = trim((string) $object_type);
        $object_id   = (int) $object_id;
        $new_url     = trim((string) $new_url);

        if ($object_type === '' || $object_id <= 0 || $new_url === '') {
            return false;
        }

        static $allowed = array('category', 'sanpham', 'tintuc');
        if (!in_array($object_type, $allowed, true)) {
            return false;
        }

        if (!isset($d) || !is_object($d)) {
            return false;
        }

        $d->reset();
        $d->setTable('#_menu_items');
        $d->setWhere('object_type', $object_type);
        $d->setWhere('object_id', $object_id);

        return (bool) $d->update(array(
            'url' => $d->clear($new_url),
        ));
    }
}

/**
 * Module admin có khả dụng với user hiện tại?
 *
 * @param string $alias  vd. quan-ly-don-hang, lien-he
 * @return bool
 */
function admin_module_available($alias)
{
    static $cache = array();

    $alias = strtolower(trim((string) $alias));
    if ($alias === '' || !preg_match('/^[a-z0-9\-]+$/', $alias)) {
        return false;
    }
    if (array_key_exists($alias, $cache)) {
        return $cache[$alias];
    }

    global $d;
    if (!is_object($d)) {
        $cache[$alias] = false;
        return false;
    }

    if (method_exists($d, 'safeFetchOne')) {
        $row = $d->safeFetchOne(
            'select id, hien_thi from #_module_admin where alias = ? limit 1',
            array($alias)
        );
    } else {
        $row = $d->simple_fetch(
            "select id, hien_thi from #_module_admin where alias = '" . addslashes($alias) . "' limit 1"
        );
    }

    if (!is_array($row) || empty($row['id']) || (int) $row['hien_thi'] !== 1) {
        $cache[$alias] = false;
        return false;
    }

    $uid = isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;
    if ($uid <= 0) {
        $cache[$alias] = false;
        return false;
    }

    $ok            = ($d->checkPermission($uid, (int) $row['id']) == 1);
    $cache[$alias] = $ok;
    return $ok;
}

if (!function_exists('getPancakeConfig')) {
    function getPancakeConfig()
    {
        global $d, $config;
        if (isset($d) && is_object($d) && method_exists($d, 'simple_fetch')) {
            $dbRow = $d->simple_fetch("SELECT pancake_api_key, pancake_shop_id, pancake_api_url, pancake_sync_to_crm FROM #_thongtin WHERE pancake_api_key IS NOT NULL AND pancake_api_key != '' LIMIT 1");
            if (is_array($dbRow) && !empty($dbRow['pancake_api_key'])) {
                return [
                    'api_key'     => $dbRow['pancake_api_key'],
                    'shop_id'     => $dbRow['pancake_shop_id'] ?? '',
                    'api_url'     => !empty($dbRow['pancake_api_url']) ? $dbRow['pancake_api_url'] : 'https://crm.pancake.vn/api',
                    'sync_to_crm' => (int) ($dbRow['pancake_sync_to_crm'] ?? 1) === 1,
                ];
            }
        }
        return $config['pancake'] ?? [
            'api_key'     => '',
            'shop_id'     => '',
            'api_url'     => 'https://crm.pancake.vn/api',
            'sync_to_crm' => true,
        ];
    }
}
