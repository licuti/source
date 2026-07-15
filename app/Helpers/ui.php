<?php
use App\Models\CategoryModel;

if (!function_exists('render_form')) {
    /**
     * Render HTML của một Dynamic Form bằng mã Code
     */
    function render_form($code) {
        $form = \App\Models\FormModel::where('code', $code)->where('is_active', 1)->first();
        if (!$form) return '<!-- Form không tồn tại hoặc đã bị tắt -->';
        
        $fields = \App\Models\FormFieldModel::where('form_id', $form->id)->orderBy('sort_order', 'ASC')->get();
        if (count($fields) == 0) return '<!-- Form chưa có trường dữ liệu -->';
        
        $html = '<form action="' . url('submit-form/' . $form->id) . '" method="POST" class="dynamic-form" id="form-' . $form->code . '" enctype="multipart/form-data">';
        $html .= '<input type="hidden" name="form_code" value="' . htmlspecialchars($form->code) . '">';
        // Honeypot field (Anti-spam)
        $html .= '<div style="display:none !important;" aria-hidden="true">';
        $html .= '<input type="text" name="__hp_website" tabindex="-1" autocomplete="off">';
        $html .= '</div>';
        
        foreach ($fields as $field) {
            $req = $field->is_required ? 'required' : '';
            $reqMark = $field->is_required ? '<span class="text-danger">*</span>' : '';
            $name = htmlspecialchars($field->name);
            $label = htmlspecialchars($field->label);
            $placeholder = htmlspecialchars($field->placeholder);
            
            $html .= '<div class="form-group mb-3">';
            $html .= '<label class="form-label">' . $label . ' ' . $reqMark . '</label>';
            
            switch ($field->type) {
                case 'textarea':
                    $html .= '<textarea class="form-control" name="' . $name . '" placeholder="' . $placeholder . '" rows="4" ' . $req . '></textarea>';
                    break;
                case 'select':
                    $html .= '<select class="form-select" name="' . $name . '" ' . $req . '>';
                    $html .= '<option value="">-- Chọn --</option>';
                    $options = json_decode($field->options, true) ?? [];
                    foreach ($options as $opt) {
                        $html .= '<option value="' . htmlspecialchars($opt) . '">' . htmlspecialchars($opt) . '</option>';
                    }
                    $html .= '</select>';
                    break;
                case 'radio':
                    $options = json_decode($field->options, true) ?? [];
                    foreach ($options as $k => $opt) {
                        $html .= '<div class="form-check">';
                        $html .= '<input class="form-check-input" type="radio" name="' . $name . '" id="' . $name . '_' . $k . '" value="' . htmlspecialchars($opt) . '" ' . $req . '>';
                        $html .= '<label class="form-check-label" for="' . $name . '_' . $k . '">' . htmlspecialchars($opt) . '</label>';
                        $html .= '</div>';
                    }
                    break;
                case 'checkbox':
                    $options = json_decode($field->options, true) ?? [];
                    foreach ($options as $k => $opt) {
                        $html .= '<div class="form-check">';
                        $html .= '<input class="form-check-input" type="checkbox" name="' . $name . '[]" id="' . $name . '_' . $k . '" value="' . htmlspecialchars($opt) . '">';
                        $html .= '<label class="form-check-label" for="' . $name . '_' . $k . '">' . htmlspecialchars($opt) . '</label>';
                        $html .= '</div>';
                    }
                    break;
                case 'file':
                    $html .= '<input type="file" class="form-control" name="' . $name . '" ' . $req . '>';
                    break;
                default:
                    // text, email, tel
                    $html .= '<input type="' . htmlspecialchars($field->type) . '" class="form-control" name="' . $name . '" placeholder="' . $placeholder . '" ' . $req . '>';
                    break;
            }
            $html .= '</div>';
        }
        
        $html .= '<button type="submit" class="btn btn-primary btn-submit-form">Gửi thông tin</button>';
        
        $captcha = \App\Services\Captcha\CaptchaManager::getDriver();
        if ($captcha) {
            $html .= $captcha->render();
        }
        
        $html .= '</form>';
        
        return $html;
    }
}

/**
 * ============================================================
 *  UI HELPERS
 *  Các hàm render giao diện dùng chung: breadcrumbs, phân trang,
 *  đánh giá sao, embed media, v.v.
 * ============================================================
 */

if (!function_exists('renderBreadcrumbs')) {
    /**
     * Render Breadcrumbs sử dụng cấu trúc Bootstrap 5.
     * @param array $items Mảng các item ['ten' => '...', 'slug' => '...']
     */
    function renderBreadcrumbs($items) {
        if (empty($items)) return '';

        $out = '<nav aria-label="breadcrumb" class="mb-4">';
        $out .= '<ol class="breadcrumb mb-0">';
        $out .= '<li class="breadcrumb-item"><a href="index.html"><i class="fa fa-home"></i> Trang chủ</a></li>';

        $count = count($items);
        foreach ($items as $index => $item) {
            $isLast = ($index === $count - 1);
            $name = is_object($item) ? ($item->ten ?? '') : ($item['ten'] ?? '');

            if ($isLast) {
                $out .= '<li class="breadcrumb-item active" aria-current="page">' . e($name) . '</li>';
            } else {
                $out .= '<li class="breadcrumb-item"><a ' . createAlias($item) . '>' . e($name) . '</a></li>';
            }
        }

        $out .= '</ol></nav>';
        return $out;
    }
}

if (!function_exists('renderPageHeader')) {
    /**
     * Render tiêu đề trang và breadcrumb chuẩn.
     */
    function renderPageHeader($title, $breadcrumbs = []) {
        ob_start(); ?>
        <div class="page-header py-4 border-bottom mb-4 bg-light">
            <div class="container-fluid">
                <h1 class="h3 fw-bold mb-2"><?= e($title) ?></h1>
                <?= renderBreadcrumbs($breadcrumbs) ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

if (!function_exists('renderStars')) {
    /**
     * Render ngôi sao đánh giá SVG.
     */
    function renderStars($avg, $total = null, $size = 16, $color = '#f59e0b', $empty = '#d1d5db') {
        $avg  = max(0.0, min(5.0, (float)$avg));
        $gap  = (int)round($size * 0.25);
        $sw   = $size + $gap;
        $tw   = $sw * 5 - $gap;
        $fw   = round(($avg / 5) * $tw, 4);
        $uid  = 'sr' . substr(md5(uniqid('', true)), 0, 8);

        $p = 'M10 2l2.245 4.547 5.019.73-3.632 3.54.857 4.993L10 13.458l-4.489 2.352.857-4.993L2.736 7.277l5.019-.73L10 2z';

        $out = '<span class="star-rating" title="' . e(number_format($avg, 1)) . '/5" style="display:inline-flex;align-items:center;line-height:1;gap:' . (int)($gap * 1.5) . 'px">';
        $out .= '<svg xmlns="http://www.w3.org/2000/svg" width="' . $tw . '" height="' . $size . '" viewBox="0 0 ' . $tw . ' ' . $size . '" style="display:block;flex-shrink:0">';
        $out .= '<defs><clipPath id="' . $uid . '"><rect x="0" y="0" width="' . $fw . '" height="' . $size . '"/></clipPath></defs>';

        for ($i = 0; $i < 5; $i++) {
            $tx = $i * $sw;
            $out .= '<path d="' . $p . '" transform="translate(' . $tx . ',0) scale(' . ($size / 20) . ')" fill="' . $empty . '"/>';
        }

        $out .= '<g clip-path="url(#' . $uid . ')">';
        for ($i = 0; $i < 5; $i++) {
            $tx = $i * $sw;
            $out .= '<path d="' . $p . '" transform="translate(' . $tx . ',0) scale(' . ($size / 20) . ')" fill="' . $color . '"/>';
        }
        $out .= '</g></svg>';

        if ($total !== null) {
            $out .= '<span style="font-size:0.82em;color:#6b7280">(' . (int)$total . ')</span>';
        }

        $out .= '</span>';
        return $out;
    }
}

if (!function_exists('paging')) {
    /**
     * Phân trang Bootstrap 5.
     */
    function paging($total, $per_page, $current_page, $url) {
        if ($total <= $per_page) return '';

        $total_pages = ceil($total / $per_page);
        $current_page = max(1, min($total_pages, (int)$current_page));

        $separator = (strpos($url, '?') !== false) ? ( (substr($url, -1) == '?' || substr($url, -1) == '&') ? '' : '&' ) : '?';
        $baseUrl = $url . $separator;

        $out = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

        $prev_disabled = ($current_page <= 1) ? 'disabled' : '';
        $prev_url = ($current_page > 1) ? $baseUrl . 'page=' . ($current_page - 1) : '#';
        $out .= '<li class="page-item ' . $prev_disabled . '"><a class="page-link" href="' . $prev_url . '">&laquo;</a></li>';

        $range = 2;
        for ($i = 1; $i <= $total_pages; $i++) {
            if ($i == 1 || $i == $total_pages || ($i >= $current_page - $range && $i <= $current_page + $range)) {
                $active = ($i == $current_page) ? 'active' : '';
                $out .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . 'page=' . $i . '">' . $i . '</a></li>';
            } elseif ($i == $current_page - $range - 1 || $i == $current_page + $range + 1) {
                $out .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
            }
        }

        $next_disabled = ($current_page >= $total_pages) ? 'disabled' : '';
        $next_url = ($current_page < $total_pages) ? $baseUrl . 'page=' . ($current_page + 1) : '#';
        $out .= '<li class="page-item ' . $next_disabled . '"><a class="page-link" href="' . $next_url . '">&raquo;</a></li>';

        $out .= '</ul></nav>';
        return $out;
    }
}

if (!function_exists('createYoutubeEmbed')) {
    /**
     * Render mã nhúng Youtube.
     */
    function createYoutubeEmbed($videoId, $width = 500, $height = 250) {
        if (empty($videoId)) return '';

        $id = htmlspecialchars($videoId, ENT_QUOTES, 'UTF-8');
        $embedUrl = 'https://www.youtube.com/embed/' . $id;

        return '
            <div class="ratio ratio-16x9">
                <iframe src="' . $embedUrl . '" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>';
    }
}

if (!function_exists('createFanpageEmbed')) {
    /**
     * Render mã nhúng Fanpage Facebook.
     */
    function createFanpageEmbed($fanpageUrl, $includeSdk = false, $width = null, $height = 150) {
        if (empty($fanpageUrl)) return '';
        $url = htmlspecialchars($fanpageUrl, ENT_QUOTES, 'UTF-8');

        $markup = '
            <div class="fb-page" data-href="' . $url . '" data-height="' . (int)$height . '" data-tabs="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true">
                <blockquote cite="' . $url . '" class="fb-xfbml-parse-ignore"><a href="' . $url . '">Facebook</a></blockquote>
            </div>';

        if ($includeSdk) {
            $markup .= '
                <div id="fb-root"></div>
                <script async defer crossorigin="anonymous" src="https://connect.facebook.net/vi_VN/sdk.js#xfbml=1&version=v11.0"></script>';
        }
        return $markup;
    }
}

if (!function_exists('timeAgo')) {
    /**
     * Định dạng thời gian "cách đây".
     */
    function timeAgo($date) {
        if (!$date) return '';
        $tz = new DateTimeZone('Asia/Ho_Chi_Minh');

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
}

if (!function_exists('getPercent')) {
    /**
     * Tính phần trăm.
     */
    function getPercent(int $count, int $total): int {
        return $total > 0 ? (int)round($count * 100 / $total) : 0;
    }
}

if (!function_exists('reviewFilterUrl')) {
    /**
     * Tạo URL lọc review.
     */
    function reviewFilterUrl(string $base, int $star = 0, int $media = 0): string {
        $q = [];
        if ($star)  $q['bl_star']  = $star;
        if ($media) $q['bl_media'] = 1;
        return $base . ($q ? '?' . http_build_query($q) : '') . '#nhanxet';
    }
}

if (!function_exists('getCurrentUrlWithoutPage')) {
    /**
     * Lấy URL hiện tại và loại bỏ tham số page.
     */
    function getCurrentUrlWithoutPage() {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $url_parts = parse_url($uri);
        $path = $url_parts['path'] ?? '';
        $query = $url_parts['query'] ?? '';

        parse_str($query, $params);
        unset($params['page']);

        $new_query = http_build_query($params);
        return $path . ($new_query ? '?' . $new_query : '');
    }
}

if (!function_exists('getCategoryTreeIds')) {
    /**
     * Lấy danh sách ID của một danh mục và tất cả các con đệ quy.
     */
    function getCategoryTreeIds($parentId) {
        if (!$parentId) return [];

        $childIdsStr = CategoryModel::getChildrenIds($parentId);
        $ids = [$parentId];

        if ($childIdsStr) {
            $cleanChildIds = array_filter(explode(',', trim($childIdsStr, ',')));
            foreach ($cleanChildIds as $id) {
                $ids[] = (int)$id;
            }
        }

        return array_unique($ids);
    }
}

if (!function_exists('renderCategoryFilter')) {
    function renderCategoryFilter($categories, $selectedId = 0, $prefix = '') {
        foreach ($categories as $cat) {
            $selected = ($cat->id_code == $selectedId) ? 'selected' : '';
            $catName = $cat->title ?? ($cat->ten ?? ($cat->name ?? ''));
            echo '<option value="' . $cat->id_code . '" ' . $selected . '>' . $prefix . htmlspecialchars($catName) . '</option>';
            if (!empty($cat->children)) {
                renderCategoryFilter($cat->children, $selectedId, $prefix . '--- ');
            }
        }
    }
}

if (!function_exists('render_attrs')) {
    /**
     * Chuyển đổi mảng attributes thành chuỗi HTML attributes
     */
    function render_attrs(array $attrs): string {
        $html = [];
        foreach ($attrs as $key => $value) {
            if ($value === true) {
                $html[] = htmlspecialchars($key);
            } elseif ($value !== false && $value !== null) {
                $html[] = htmlspecialchars($key) . '="' . htmlspecialchars((string)$value) . '"';
            }
        }
        return implode(' ', $html);
    }
}
