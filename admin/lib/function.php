<?php

include "resize-class.php";
include 'simple_html_dom.php';

define("LANG",  get_json('lang','0', 'code'));

function resize_img($file, $option, $w ,$h,$folder='thumb' ){
    $resizeObj = new resize('../uploads/images/'.$file);
    //Resize image (options: exact, portrait, landscape, auto, crop)
    $resizeObj -> resizeImage($w, $h, $option);
    $resizeObj -> saveImage('../uploads/images/'.$folder.'/'.$file, 100);
    return $file;
}

function get_size_img($file ){
    $size = getimagesize($file);
    list($width, $height) = $size;
    return $width.'x'.$height;
}

function get_json($object, $key='', $attributes=''){
    global $config;
    $arr_config   = $config;
    if($key == ''){
        return $arr_config[$object];
    }elseif($key!='' and $attributes==''){
        return $arr_config[$object][$key];
    }else{
        return $arr_config[$object][$key][$attributes];
    }
}


function numberformat($number){
    $number2 = str_replace(',','.',  number_format($number));
    return $number2;
}

function token() {
    $token = sha1(time().rand(0,99999));
    $_SESSION['token'] = $token;
    return $token;
}




function replaceHTMLCharacter($str){
    $str  = preg_replace('/&/',		'&amp;',	$str);
    $str  = preg_replace('/</',		'&lt;',		$str);
    $str  = preg_replace('/>/',		'&gt;',		$str);
    $str  = preg_replace('/\"/',	'&quot;',	$str);
    $str  = preg_replace('/\'/',	'&apos;',	$str);
    return $str;
}

/**
 * ============================================================
 *  SHARED CORE FUNCTIONS (Frontend & Admin)
 * ============================================================
 */


function sanitizeHtml($html)
{
    // Cho phép các thẻ cơ bản
    $allowedTags = '<p><br><b><strong><i><em><ul><ol><li><a><img>';

    $html = strip_tags($html, $allowedTags);

    // Loại bỏ event handler + style + javascript:
    $html = preg_replace('/on\w+\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html);
    $html = preg_replace('/style\s*=\s*(".*?"|\'.*?\'|[^\s>]+)/i', '', $html);

    // Chặn javascript: trong href/src
    $html = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:.*?\2/i', '$1="#"', $html);

    return $html;
}

function Img($img) {
    if($img!=''){
        $link_img = URLPATH.'img_data/images/'.$img; 
    }else{
        $link_img = URLPATH.'img_data/no-image.png';
    }
    return $link_img;
}

function getImageUrl($filename)
{
    $basePath = URLPATH . 'img_data/';

    if (!empty(trim($filename))) {
        return $basePath . 'images/' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
    }

    return $basePath . 'no-image.png';
}

function createAlias($category, $options = array())
{
    global $lang, $config;

    $nofollow = !empty($options['nofollow']);
    $target   = !empty($options['target']);

    $href = '';
    $baseUrl = URLPATH;

    $is_object = is_object($category);
    
    if ($is_object ? !empty($category->url) : !empty($category['url'])) {
        $href = $is_object ? $category->url : $category['url'];
    } else {
        $alias = $is_object ? ($category->alias ?? '') : ($category['alias'] ?? '');
        $slug  = $is_object ? ($category->slug ?? '') : ($category['slug'] ?? '');

        if (!empty($config['lang']) && count($config['lang']) > 1) {
            $baseUrl .= $lang . '/';
        }

        if (!empty($slug)) {
            $href = $baseUrl . $slug . '/' . $alias . '.html';
        } else {
            $href = $baseUrl . $alias . '.html';
        }
    }

    $attr = 'href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" ';

    if ($nofollow) {
        $attr .= 'rel="nofollow" ';
    }

    if ($target) {
        $attr .= 'target="_blank" ';
    }

    return $attr;
}

function str_to_alias($str) {
    return bodautv($str);
}

function getExchangeRate($base = 'VND', $target = 'USD') {
    global $config;
    
    $settings = $config['currency'] ?? ['auto' => false, 'vnd_usd' => 25000];

    // 1. Trường hợp dùng Tỷ giá cố định (Manual)
    if (!$settings['auto']) {
        if ($base == 'VND' && $target == 'USD') return 1 / $settings['vnd_usd'];
        if ($base == 'USD' && $target == 'VND') return $settings['vnd_usd'];
        return 1;
    }

    // 2. Trường hợp dùng API (Auto)
    $cacheFile = dirname(__FILE__) . '/../../uploads/currency_cache.json';
    $cacheTime = 86400; // 24 giờ

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        $data = json_decode(file_get_contents($cacheFile), true);
    } else {
        $apiUrl = "https://api.exchangerate-api.com/v4/latest/USD"; 
        $response = @file_get_contents($apiUrl);
        if ($response) {
            $data = json_decode($response, true);
            file_put_contents($cacheFile, $response);
        } else if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
        } else {
            return ($target == 'USD') ? (1 / $settings['vnd_usd']) : 1;
        }
    }

    $rates = $data['rates'] ?? [];
    $vnd_per_usd = $rates['VND'] ?? $settings['vnd_usd'];
    $target_per_usd = $rates[$target] ?? 1;

    return $target_per_usd / $vnd_per_usd;
}

function renderPrice($price) {
    global $config;
    
    $current_lang = defined('_lang') ? _lang : ($_SESSION['lang'] ?? (defined('LANG') ? LANG : 'vi'));
    
    $lang_conf = null;
    foreach ($config['lang'] as $l) {
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
        } else {
            return number_format($converted_price, 0, ',', '.') . ' ' . $currency;
        }
    } else {
        if ($price > 0) {
            return number_format($price, 0, ',', '.') . ' ₫';
        }
        return '0 ₫';
    }
}

function getPercentDiscount($promotion_price, $regular_price) {
    if ($promotion_price > 0 && $regular_price > 0 && $promotion_price < $regular_price) {
        $discount = 100 - (($promotion_price / $regular_price) * 100);
        return round($discount) . "%";
    }
    return null;
}

function getQueryParams($uri = null){
    $query = '';
    $params = [];

    // Nếu không truyền URI, mặc định lấy URI hiện tại
    if ($uri === null && isset($_SERVER['REQUEST_URI'])) {
        $uri = $_SERVER['REQUEST_URI'];
    }

    // Tách phần query string từ URI
    $parts = explode('?', $uri, 2);

    if (isset($parts[1])) {
        $query = $parts[1];
        parse_str($query, $params);
    }
    return $params;
}

function randomString($length = 10) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $str = '';
    $max = strlen($chars) - 1;

    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[random_int(0, $max)];
    }

    return $str;
}

function array_key_by_column($data, $key_column, $fallback_id = true) {
    if (empty($data)) return [];
    $result = [];
    foreach($data as $item) {
        if (is_object($item)) {
            $key = $item->$key_column ?? null;
            if (!$key && $fallback_id && isset($item->id)) {
                $key = $item->id;
            }
        } else {
            $key = $item[$key_column] ?? null;
            if (!$key && $fallback_id && isset($item['id'])) {
                $key = $item['id'];
            }
        }
        
        if ($key !== null) {
            $result[$key] = $item;
        }
    }
    return $result;
}


function getUploadRules(){
    return array(
        'images' => array(
            'types' => array('image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'),
            'exts' => array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'),
            'max_size' => 3 * 1024 * 1024 // 3MB
        ),
        'file' => array(
            'types' => array(
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'audio/mpeg',
                'video/mp4'
            ),
            'exts' => array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'mp3', 'mp4'),
            'max_size' => 10 * 1024 * 1024 // 10MB
        )
    );
}


function sanitizeFileName($name){
    if (function_exists('iconv')) {
        $name = @iconv('UTF-8', 'ASCII//TRANSLIT', $name);
    }
    return preg_replace('/[^a-zA-Z0-9_-]/', '', $name);
}


//upload_file
function Uploadfile($file, $type, $folder, $name){
    if (!isset($_FILES[$file]) || $_FILES[$file]['error']) {
        return false;
    }

    $rules = getUploadRules();
    if (!isset($rules[$type])) {
        return false;
    }

    $info = pathinfo($_FILES[$file]['name']);
    $ext = strtolower($info['extension']);
    $tmp = $_FILES[$file]['tmp_name'];

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmp);
    finfo_close($finfo);

    if (
        $_FILES[$file]['size'] > $rules[$type]['max_size'] ||
        !in_array($mime, $rules[$type]['types']) ||
        !in_array($ext, $rules[$type]['exts'])
    ) {
        return false;
    }

    $safe_name = sanitizeFileName($name) . '.' . $ext;
    $destination = $folder . $safe_name;

    if (file_exists($destination)) {
        $safe_name = sanitizeFileName($name) . '-' . rand(1000, 9999) . '.' . $ext;
        $destination = $folder . $safe_name;
    }

    return move_uploaded_file($tmp, $destination) ? $safe_name : false;
}


function multiple_Uploadfile($file, $type, $folder, $name){
    $rules = getUploadRules();
    if (!isset($rules[$type])) {
        return '';
    }

    $total = count($_FILES[$file]['name']);
    $list = array();

    for ($i = 0; $i < $total; $i++) {
        if ($_FILES[$file]['error'][$i] !== UPLOAD_ERR_OK || empty($_FILES[$file]['name'][$i])) {
            continue;
        }

        $tmp = $_FILES[$file]['tmp_name'][$i];
        $original = $_FILES[$file]['name'][$i];
        $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $tmp);
        finfo_close($finfo);

        if (
            $_FILES[$file]['size'][$i] > $rules[$type]['max_size'] ||
            !in_array($mime, $rules[$type]['types']) ||
            !in_array($ext, $rules[$type]['exts'])
        ) {
            continue;
        }

        $safe_name = sanitizeFileName($name) . '-' . uniqid('', true) . '.' . $ext;
        $destination = $folder . $safe_name;

        if (move_uploaded_file($tmp, $destination)) {
            $list[] = $safe_name;
        }
    }

    return implode(',', $list);
}



function check_shell($text){
    $blacklist = [
        // PHP keywords
        'php', 'eval(', 'base64', 'shell_exec(', 'exec(', 'passthru(', 'system(', 'proc_open(', 'popen(',

        // Variable manipulation
        '$_GET', '$_POST', '$_REQUEST', '$_SERVER', '$_FILES', '$_ENV',

        // File and directory functions
        'fopen(', 'fwrite(', 'file_get_contents(', 'file_put_contents(', 'unlink(', 'readdir(', 'scandir(',

        // Form inputs and script tags
        '<form', '<input', '<textarea', '<select', '<button','onerror=', 'onclick=', 'onmouseover=',

        // Miscellaneous risky patterns
        'ini_set(', 'ini_get', 'preg_replace("/e"', 'assert(', 'chr(', 'str_rot13(', 'gzinflate(',
        'urldecode(', 'rawurldecode(', 'decodeURIComponent('
    ];

    foreach ($blacklist as $pattern) {
        if (stripos($text, $pattern) !== false) {
            return '';
        }
    }

    return $text;
}


function check_phone($text){
    $num1= substr($text,0,1);
    $error=0;
    if((int)$text==0){
            $error= $error+1;
    }
    if(substr($text,0,1)!=0){
            $error= $error+1;
    }
    if(strlen(strstr($text, '-')) > 0){
            $error = $error+1;
    }
    if(strlen($text)<9 or strlen($text)>10){
            $error = $error+1;
    }
    return $error;
}




function validate_content($text,$type=''){//$type='': bỏ thẻ tất cả thẻ html - $type='1': Loại bỏ hình ảnh, script
    if($text!=''){
        $html = str_get_html($text);
        if($type==''){
            if(count($html->find('a'))>0 or strlen(strstr($html, 'http')) > 0 ){
                $html = '';
            }else{
                $html = strip_tags($html);
            }
        }elseif($type=='0'){
            $html = strip_tags($html);
        }else{
            foreach($html->find('img') as $element){
                $element->outertext='';
            }
            foreach($html->find('script') as $element){
                $element->outertext='';
            }
            foreach($html->find('a') as $element){
                $element->outertext='';
            }
            $html ->load($html ->save());
        }
        return addslashes($html);
    }else{
        return $text;
    }
}


function get_shipping_fee($id_tinh, $id_huyen = '', $id_xa = '', $tong_don_hang = 0, $total_weight = 0) {
    global $d;

    // 1. Lấy cấu hình từ Setting
    $setting = $d->simple_fetch("SELECT free_ship_threshold, default_ship_phi, ship_base_weight, ship_rounding FROM #_thongtin WHERE lang = '".LANG."' LIMIT 1");
    
    $free_ship_threshold = (float)($setting['free_ship_threshold'] ?? 0);
    $default_ship_phi    = (float)($setting['default_ship_phi'] ?? 30000);
    $ship_base_weight    = (float)($setting['ship_base_weight'] ?? 1.0);
    $ship_rounding       = (int)($setting['ship_rounding'] ?? 0);
    
    // 2. Kiểm tra mức miễn phí vận chuyển
    if ($free_ship_threshold > 0 && $tong_don_hang >= $free_ship_threshold) {
        return [
            'fee' => 0,
            'description' => "Đơn hàng từ " . renderPrice($free_ship_threshold) . " được miễn phí giao hàng"
        ];
    }

    // 3. Quy đổi cân nặng ra KG để so sánh với bảng giá
    $conversion_factor = (float)($config['weight']['conversion'] ?? 1000);
    $current_weight_kg = $total_weight / $conversion_factor;
    if ($ship_rounding == 1) {
        $current_weight_kg = ceil($current_weight_kg);
    }

    $phi_ship = 0;
    $phi_extra_kg = 0;
    $ghi_chu = "";
    $found = false;

    // 4. Tìm phí chi tiết theo Phường/Xã trước
    if ($id_xa != '') {
        $row = $d->simple_fetch("SELECT phi_ship, phi_extra_kg, ghi_chu, free_weight FROM #_ship WHERE id_xa = '$id_xa' AND hien_thi = 1 LIMIT 1");
        if (!empty($row)) {
            $phi_ship = $row['phi_ship'];
            $phi_extra_kg = $row['phi_extra_kg'];
            $ghi_chu = $row['ghi_chu'];
            $found = true;
        }
    }

    // 5. Nếu không thấy xã, tìm phí chi tiết theo Quận/Huyện
    if (!$found && $id_huyen != '') {
        $row = $d->simple_fetch("SELECT phi_ship, phi_extra_kg, ghi_chu, free_weight FROM #_ship WHERE id_tinh = '$id_tinh' AND id_huyen = '$id_huyen' AND (id_xa IS NULL OR id_xa = '') AND hien_thi = 1 LIMIT 1");
        if (!empty($row)) {
            $phi_ship = $row['phi_ship'];
            $phi_extra_kg = $row['phi_extra_kg'];
            $ghi_chu = $row['ghi_chu'];
            $found = true;
        }
    }

    // 6. Nếu không thấy huyện, tìm phí chung của Tỉnh/Thành phố
    if (!$found) {
        $row = $d->simple_fetch("SELECT phi_ship, phi_extra_kg, ghi_chu, free_weight FROM #_ship WHERE id_tinh = '$id_tinh' AND (id_huyen IS NULL OR id_huyen = '') AND (id_xa IS NULL OR id_xa = '') AND hien_thi = 1 LIMIT 1");
        if (!empty($row)) {
            $phi_ship = $row['phi_ship'];
            $phi_extra_kg = $row['phi_extra_kg'];
            $ghi_chu = $row['ghi_chu'];
            $free_weight = (float)$row['free_weight'];
            $found = true;
        }
    }

    // 7. Nếu vẫn không thấy, trả về phí mặc định
    if (!$found) {
        $phi_ship = $default_ship_phi;
        $phi_extra_kg = 0;
        $ghi_chu = "";
        $free_weight = 0;
    }

    // 8. Kiểm tra ngưỡng Freeship theo khối lượng của khu vực
    if ($free_weight > 0 && $current_weight_kg >= $free_weight) {
        return [
            'fee' => 0,
            'description' => '<span class="text-success"><i class="fa fa-truck"></i> Miễn phí vận chuyển (Ưu đãi hàng sỉ trên ' . $free_weight . 'kg)</span>'
        ];
    }

    // 9. Tính toán phụ phí cân nặng
    $extra_weight = max(0, $current_weight_kg - $ship_base_weight);
    $total_phi_ship = (float)$phi_ship + ($extra_weight * (float)$phi_extra_kg);

    return [
        'fee' => $total_phi_ship,
        'description' => $ghi_chu
    ];
}