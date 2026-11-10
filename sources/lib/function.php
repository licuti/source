<?php

function createYoutubeEmbed($videoId, $width = 500, $height = 250)
{
    if (empty($videoId)) {
        return '';
    }

    $id = htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8');
    $frameWidth = (int)$width;
    $frameHeight = (int)$height;

    $embedUrl = 'https://www.youtube.com/embed/' . $id;

    $markup = '
        <iframe
            width="' . $frameWidth . '"
            height="' . $frameHeight . '"
            src="' . $embedUrl . '"
            title="YouTube video player"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
            allowfullscreen
        ></iframe>
    ';

    return $markup;
}

function createFanpageEmbed($fanpageUrl, $includeSdk = false, $width = null, $height = 150)
{
    if (empty($fanpageUrl)) {
        return '';
    }

    $url = htmlspecialchars($fanpageUrl, ENT_QUOTES, 'UTF-8');
    $frameWidth = $width ? (int)$width : '';
    $frameHeight = (int)$height;

    $markup = '
        <div 
            class="fb-page"
            data-href="' . $url . '"
            data-width="' . $frameWidth . '"
            data-height="' . $frameHeight . '"
            data-tabs=""
            data-small-header="false"
            data-adapt-container-width="true"
            data-hide-cover="false"
            data-show-facepile="true"
        >
            <blockquote cite="' . $url . '" class="fb-xfbml-parse-ignore">
                <a href="' . $url . '">Facebook</a>
            </blockquote>
        </div>
    ';

    if ($includeSdk) {
        $markup .= '
            <div id="fb-root"></div>
            <script async defer crossorigin="anonymous"
                src="https://connect.facebook.net/vi_VN/sdk.js#xfbml=1&version=v11.0">
            </script>
        ';
    }

    return $markup;
}

function Img($img) {
    $img = trim((string)$img);
    if ($img !== '') {
        // Xoá các ký tự ẩn (zero-width spaces) có thể gây lỗi khi check URL
        $img = preg_replace('/^[\pZ\pC]+/u', '', $img);
        if (preg_match('#^(https?:)?//#i', $img) || strpos($img, 'http://') !== false || strpos($img, 'https://') !== false || strpos($img, 'data:') === 0) {
            return $img;
        }
        return URLPATH . 'media/images/' . $img; 
    }
    return URLPATH . 'media/no-image.png';
}


function getImageUrl($filename)
{
    $filename = trim((string)$filename);
    if ($filename !== '') {
        if (preg_match('#^(https?:)?//#i', $filename) || strpos($filename, 'data:') === 0) {
            return $filename;
        }
        if (strpos($filename, 'media/images/') === 0) {
            return URLPATH . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
        }
        if (strpos($filename, '/') === 0) {
            return URLPATH . htmlspecialchars(ltrim($filename, '/'), ENT_QUOTES, 'UTF-8');
        }
        return URLPATH . 'media/images/' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
    }

    return URLPATH . 'media/no-image.png';
}

function getVideoUrl($filename)
{
    $filename = trim((string)$filename);
    if ($filename !== '') {
        if (preg_match('#^(https?:)?//#i', $filename) || strpos($filename, 'data:') === 0) {
            return $filename;
        }
        if (strpos($filename, 'media/videos/') === 0) {
            return URLPATH . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
        }
        if (strpos($filename, '/') === 0) {
            return URLPATH . htmlspecialchars(ltrim($filename, '/'), ENT_QUOTES, 'UTF-8');
        }
        return URLPATH . 'media/videos/' . htmlspecialchars($filename, ENT_QUOTES, 'UTF-8');
    }
    return '';
}


function createAlias($category, $options = array())
{
    global $lang, $config;

    $nofollow = !empty($options['nofollow']);
    $target   = !empty($options['target']);

    $href = '';
    $baseUrl = URLPATH;

    if (!empty($category['url'])) {
        $href = $category['url'];
    } else {
        $alias = isset($category['alias']) ? $category['alias'] : '';
        $slug  = isset($category['slug']) ? $category['slug'] : '';

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

function str_to_alias ($str){
    $unicode = array(
    'a'=>'á|à|ả|ã|ạ|ă|ắ|ặ|ằ|ẳ|ẵ|â|ấ|ầ|ẩ|ẫ|ậ',
    'd'=>'đ',
    'e'=>'é|è|ẻ|ẽ|ẹ|ê|ế|ề|ể|ễ|ệ',
    'i'=>'í|ì|ỉ|ĩ|ị',
    'o'=>'ó|ò|ỏ|õ|ọ|ô|ố|ồ|ổ|ỗ|ộ|ơ|ớ|ờ|ở|ỡ|ợ',
    'u'=>'ú|ù|ủ|ũ|ụ|ư|ứ|ừ|ử|ữ|ự',
    'y'=>'ý|ỳ|ỷ|ỹ|ỵ',
    'A'=>'Á|À|Ả|Ã|Ạ|Ă|Ắ|Ặ|Ằ|Ẳ|Ẵ|Â|Ấ|Ầ|Ẩ|Ẫ|Ậ',
    'D'=>'Đ',
    'E'=>'É|È|Ẻ|Ẽ|Ẹ|Ê|Ế|Ề|Ể|Ễ|Ệ',
    'I'=>'Í|Ì|Ỉ|Ĩ|Ị',
    'O'=>'Ó|Ò|Ỏ|Õ|Ọ|Ô|Ố|Ồ|Ổ|Ỗ|Ộ|Ơ|Ớ|Ờ|Ở|Ỡ|Ợ',
    'U'=>'Ú|Ù|Ủ|Ũ|Ụ|Ư|Ứ|Ừ|Ử|Ữ|Ự',
    'Y'=>'Ý|Ỳ|Ỷ|Ỹ|Ỵ',
    );
    foreach($unicode as $nonUnicode=>$uni){
            $str = preg_replace("/($uni)/i", $nonUnicode, $str);
    }
    $str = str_replace(' ','-',$str);
    $str = trim($str,'nbsp;');
    $str = str_replace('nbsp;','-',$str);
    $search = ['%','@','?','&','!',',','.','$','(',')','#',':'];
    $replace   = '';
    $str = str_replace($search, $replace, $str);
    return strtolower($str);
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
    $cacheFile = dirname(__DIR__, 2) . '/logs/currency_cache.json';
    $cacheTime = 86400; // 24 giờ

    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        $data = json_decode(file_get_contents($cacheFile), true);
    } else {
        // Lưu ý: Nhiều bản FREE chỉ cho lấy gốc là USD (Base USD). 
        // Ta sẽ lấy tỷ giá từ USD để tính ngược lại cho VND.
        $apiUrl = "https://api.exchangerate-api.com/v4/latest/USD"; 
        $response = @file_get_contents($apiUrl);
        if ($response) {
            $data = json_decode($response, true);
            file_put_contents($cacheFile, $response);
        } else if (file_exists($cacheFile)) {
            $data = json_decode(file_get_contents($cacheFile), true);
        } else {
            // Fallback nếu API lỗi hoàn toàn
            return ($target == 'USD') ? (1 / $settings['vnd_usd']) : 1;
        }
    }

    $rates = $data['rates'] ?? [];
    
    // Logic tính toán: 
    // Nếu API trả về Base USD: VND_rate = rates['VND'], TARGET_rate = rates[target]
    // Tỷ giá quy đổi VND -> Target = TARGET_rate / VND_rate
    $vnd_per_usd = $rates['VND'] ?? $settings['vnd_usd'];
    $target_per_usd = $rates[$target] ?? 1;

    return $target_per_usd / $vnd_per_usd;
}

function renderPrice($price) {
    global $d, $config;
    
    // Ưu tiên _lang (frontend), sau đó đến session, cuối cùng là LANG (mặc định)
    $current_lang = defined('_lang') ? _lang : ($_SESSION['lang'] ?? (defined('LANG') ? LANG : 'vi'));
    
    // Tìm cấu hình ngôn ngữ tương ứng
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
        // Automatically fetch exchange rate from VND to target currency
        $rate = getExchangeRate('VND', $currency);
        $converted_price = $price * $rate;
        
        if ($currency == 'USD') {
            return '$' . number_format($converted_price, 2, '.', ',');
        } else {
            return number_format($converted_price, 0, ',', '.') . ' ' . $currency;
        }
    } else {
        // Default VND: 1.234.567 ₫
        if ($price > 0) {
            return number_format($price, 0, ',', '.') . ' ₫';
        }
        return '0 ₫';
    }
}

function getPercentDiscount($promotion_price, $regular_price) {
    if ($promotion_price > 0 && $regular_price > 0 && $promotion_price < $regular_price) {
        $discount = 100 - (($promotion_price / $regular_price) * 100);
        return round($discount) . "%"; // Làm tròn cho gọn (vd: 10% thay vì 10.00%)
    }
    return null;
}

/**
 * Render SVG star rating — pixel-perfect partial star support via clipPath.
 *
 * @param float      $avg    Average rating (0–5, supports decimals like 3.7)
 * @param int|null   $total  If provided, appends "(n)" review count
 * @param int        $size   Height of each star in px (default 16)
 * @param string     $color  Filled star color  (default amber)
 * @param string     $empty  Empty  star color  (default light gray)
 * @return string   HTML string — safe to echo directly
 *
 * Usage:
 *   echo renderStars(4.3);                  // 4.3 stars, no count
 *   echo renderStars($avg, $total);         // with review count
 *   echo renderStars($avg, $total, 20);     // larger stars
 */
function renderStars($avg, $total = null, $size = 16, $color = '#f59e0b', $empty = '#d1d5db') {
    $avg  = max(0.0, min(5.0, (float)$avg));
    $gap  = (int)round($size * 0.25);        // proportional gap
    $sw   = $size + $gap;                    // step width per star
    $tw   = $sw * 5 - $gap;                  // total SVG width
    $fw   = round(($avg / 5) * $tw, 4);      // filled width via clip
    $uid  = 'sr' . substr(md5(uniqid('', true)), 0, 8); // unique id per call

    // Star path designed for a 20×20 viewBox
    $p = 'M10 2l2.245 4.547 5.019.73-3.632 3.54.857 4.993L10 13.458l-4.489 2.352.857-4.993L2.736 7.277l5.019-.73L10 2z';

    $out = '<span class="star-rating" title="' . htmlspecialchars(number_format($avg, 1)) . '/5" '
         . 'style="display:inline-flex;align-items:center;line-height:1;gap:' . (int)($gap * 1.5) . 'px">';

    $out .= '<svg xmlns="http://www.w3.org/2000/svg" '
          . 'width="' . $tw . '" height="' . $size . '" '
          . 'viewBox="0 0 ' . $tw . ' ' . $size . '" '
          . 'style="display:block;flex-shrink:0">';

    // clip definition — reveals only $fw pixels of the filled layer
    $out .= '<defs><clipPath id="' . $uid . '">'
          . '<rect x="0" y="0" width="' . $fw . '" height="' . $size . '"/>'
          . '</clipPath></defs>';

    // base layer: empty (gray) stars
    for ($i = 0; $i < 5; $i++) {
        $tx = $i * $sw;
        $out .= '<path d="' . $p . '" '
              . 'transform="translate(' . $tx . ',0) scale(' . ($size / 20) . ')" '
              . 'fill="' . $empty . '"/>';
    }

    // filled layer: clipped to exact decimal width
    $out .= '<g clip-path="url(#' . $uid . ')">';
    for ($i = 0; $i < 5; $i++) {
        $tx = $i * $sw;
        $out .= '<path d="' . $p . '" '
              . 'transform="translate(' . $tx . ',0) scale(' . ($size / 20) . ')" '
              . 'fill="' . $color . '"/>';
    }
    $out .= '</g></svg>';

    if ($total !== null) {
        $out .= '<span style="font-size:0.82em;color:#6b7280">(' . (int)$total . ')</span>';
    }

    $out .= '</span>';
    return $out;
}

/**
 * Fetch average rating + review count for a product.
 * Returns ['avg' => float, 'total' => int]
 *
 * Usage in product listing loops:
 *   $rating = getProductRating($value['id_code']);
 *   echo renderStars($rating['avg'], $rating['total']);
 */
function getProductRating($id_sanpham) {
    global $d;
    $row = $d->simple_fetch(
        "SELECT COUNT(id) as total, COALESCE(AVG(danh_gia), 0) as avg
         FROM #_binhluan
         WHERE id_sanpham = " . (int)$id_sanpham . "
           AND trang_thai = 1 AND parent = 0 AND danh_gia > 0"
    );
    return [
        'avg'   => $row ? round((float)$row['avg'], 1) : 0.0,
        'total' => $row ? (int)$row['total'] : 0,
    ];
}

function getTemplate(string $templateName, array $args = []): string
{
    $basePath = __DIR__ . '/soures/';

    $templateName = ltrim($templateName, '/\\');
    $fullPath     = $basePath . $templateName . '.php';

    $realBase = realpath($basePath);
    $realPath = realpath($fullPath);

    if ($realBase === false) {
        return '';
    }

    // Security check: Chống Path Traversal (LFI)
    if (
        $realPath === false ||
        strncmp($realPath, $realBase, strlen($realBase)) !== 0 ||
        !is_file($realPath)
    ) {
        return '';
    }

    extract($args, EXTR_SKIP);

    ob_start();
    try {
        include $realPath;
    } catch (\Throwable $e) {
        ob_end_clean();
        throw $e; 
    }
    
    // Trả về HTML nếu mọi thứ suôn sẻ
    return ob_get_clean();
}

function e($string)
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

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

function array_key_by_column($data, $key_column, $fallback_id = true) {
    $result = [];
    foreach($data as $item) {
        $key = $item[$key_column] ?? null;
        if (!$key && $fallback_id && isset($item['id'])) {
            $key = $item['id']; // fallback nếu key trống
        }
        if ($key !== null) {
            $result[$key] = $item;
        }
    }
    return $result;
}


function timeAgo($date) {
    $tz = new DateTimeZone('Asia/Ho_Chi_Minh');

    // Support both Unix timestamps and date strings
    if (is_numeric($date)) {
        $from = (new DateTime('@' . $date))->setTimezone($tz);
    } else {
        $from = new DateTime($date, $tz);
    }

    $now  = new DateTime('now', $tz);
    $diff = $from->diff($now);

    if ($diff->y > 0) return $diff->y . ' năm trước';
    if ($diff->m > 0) return $diff->m . ' tháng trước';
    if ($diff->d > 0) return $diff->d . ' ngày trước';
    if ($diff->h > 0) return $diff->h . ' giờ trước';
    if ($diff->i > 0) return $diff->i . ' phút trước';
    return 'Vừa xong';
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

/**
 * Phân trang số (1, 2, 3...)
 * @param int $total           Tổng số bản ghi
 * @param int $per_page        Số bản ghi mỗi trang
 * @param int $current_page    Trang hiện tại
 * @param string $url          URL cơ sở (không bao gồm &page=)
 */
function paging($total, $per_page, $current_page, $url) {
    if ($total <= $per_page) return '';
    
    $total_pages = ceil($total / $per_page);
    $current_page = max(1, min($total_pages, (int)$current_page));
    
    // Đảm bảo URL kết thúc đúng để nối tham số
    $separator = (strpos($url, '?') !== false) ? ( (substr($url, -1) == '?' || substr($url, -1) == '&') ? '' : '&' ) : '?';
    $baseUrl = $url . $separator;

    $out = '<nav aria-label="Page navigation" class="mt-4"><ul class="pagination justify-content-center">';
    
    // Nút Previous
    $prev_disabled = ($current_page <= 1) ? 'disabled' : '';
    $prev_url = ($current_page > 1) ? $baseUrl . 'page=' . ($current_page - 1) : '#';
    $out .= '<li class="page-item ' . $prev_disabled . '"><a class="page-link" href="' . $prev_url . '">&laquo;</a></li>';
    
    // Các trang số
    $range = 2; // Hiển thị 2 trang trước và sau trang hiện tại
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)) {
            $active = ($i == $current_page) ? 'active' : '';
            $out .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . 'page=' . $i . '">' . $i . '</a></li>';
        } elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1) {
            $out .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    
    // Nút Next
    $next_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
    $next_url = ($current_page < $total_pages) ? $baseUrl . 'page=' . ($current_page + 1) : '#';
    $out .= '<li class="page-item ' . $next_disabled . '"><a class="page-link" href="' . $next_url . '">&raquo;</a></li>';
    
    $out .= '</ul></nav>';
    return $out;
}

/**
 * Láy URL hiện tại và loại bỏ tham số page để dùng cho phân trang
 */
function getCurrentUrlWithoutPage() {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $url_parts = parse_url($uri);
    $path = $url_parts['path'] ?? '';
    $query = $url_parts['query'] ?? '';
    
    parse_str($query, $params);
    unset($params['page']); // Xóa tham số page để paging tự thêm
    
    $new_query = http_build_query($params);
    return $path . ($new_query ? '?' . $new_query : '');
} 


/**
 * Hàm phân trang PHP dùng chung cho toàn bộ dự án
 */
function getPaginationHTML($total, $limit, $current_page, $url = '') {
    $total_pages = ceil($total / $limit);
    if ($total_pages <= 1) {
        return '';
    }

    $html = '<div class="arrow-pagination"><ul>';

    // Parse URL hiện tại và giữ lại tất cả các tham số lọc ngoài các tham số hệ thống
    $params = $_GET;
    unset($params['page']); // Xóa trang cũ
    unset($params['ajax']); // Xóa cờ ajax
    unset($params['lang']); // Xóa tham số lang của htaccess
    unset($params['com']);  // Xóa tham số com của htaccess
    unset($params['slug']); // Xóa tham số slug của htaccess
    unset($params['p']);    // Xóa tham số p của htaccess

    $build_url = function($p) use ($params, $url) {
        $query_params = $params;
        if ($p > 1) {
            $query_params['page'] = $p;
        }
        $query_str = http_build_query($query_params);
        $base = $url ? $url : explode('?', $_SERVER['REQUEST_URI'])[0];
        return $base . ($query_str ? '?' . $query_str : '');
    };

    // Nút lùi trang (Prev)
    if ($current_page > 1) {
        $html .= '<li><a href="' . $build_url($current_page - 1) . '" class="ajax-page-link" data-page="' . ($current_page - 1) . '"><i class="fa-solid fa-angle-left"></i></a></li>';
    }

    // Thiết lập giới hạn trang hiển thị xung quanh trang hiện tại
    $start = max(1, $current_page - 2);
    $end = min($total_pages, $current_page + 2);

    if ($start > 1) {
        $html .= '<li><a href="' . $build_url(1) . '" class="ajax-page-link" data-page="1">1</a></li>';
        if ($start > 2) {
            $html .= '<li><span class="ellipsis">...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        if ($i == $current_page) {
            $html .= '<li class="active"><span class="current">' . $i . '</span></li>';
        } else {
            $html .= '<li><a href="' . $build_url($i) . '" class="ajax-page-link" data-page="' . $i . '">' . $i . '</a></li>';
        }
    }

    if ($end < $total_pages) {
        if ($end < $total_pages - 1) {
            $html .= '<li><span class="ellipsis">...</span></li>';
        }
        $html .= '<li><a href="' . $build_url($total_pages) . '" class="ajax-page-link" data-page="' . $total_pages . '">' . $total_pages . '</a></li>';
    }

    // Nút tiến trang (Next)
    if ($current_page < $total_pages) {
        $html .= '<li><a href="' . $build_url($current_page + 1) . '" class="ajax-page-link" data-page="' . ($current_page + 1) . '"><i class="fa-solid fa-angle-right"></i></a></li>';
    }

    $html .= '</ul></div>';
    return $html;
}

