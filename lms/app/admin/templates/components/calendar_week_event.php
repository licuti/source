<?php
/**
 * LMS Shared Component: calendar_week_event
 * Thẻ một buổi học (hoặc khung lịch trống) trong ô của calendar_week.
 *
 * Thẻ định vị tuyệt đối trong cột lịch theo THỜI GIAN THỰC:
 *   - top    = phút lẻ của giờ bắt đầu (17:30 → 36px với hàng 72px)
 *   - height = thời lượng buổi (17:30–19:00 → 108px, tức kéo sang ô 18:00)
 *   - lane/lanes = chia chiều ngang khi hai thẻ chồng giờ nhau trong cùng cột ngày
 * (xem lms_cal_offset_px / lms_cal_span_px / lms_cal_lanes_for_rows trong lms/lib/lms_calendar.php)
 *
 * Props:
 * @var string $variant     upcoming|missing|finished|cancelled|free
 * @var string $title       Dòng 1 (VD 'HV01 - Nguyễn Văn A', 'Toán 1-1 - Duy Kiên')
 * @var string $start       'HH:MM'|'HH:MM:SS' — quyết định top
 * @var string $end         'HH:MM'|'HH:MM:SS' — quyết định chiều cao
 * @var bool   $show_time   Mặc định true: in dòng khung giờ
 * @var string $href        Không rỗng: tên buổi thành link phủ toàn thẻ (stretched link)
 * @var array  $attrs       Attr thêm cho thẻ (VD data-* cho modal)
 * @var string $tooltip     title="" của thẻ
 * @var string $flag        HTML góc trên phải (VD dấu ✗ thiếu BTVN)
 * @var string $footer      HTML dưới khung giờ (VD chip 'Xem chi tiết', nút 'Chỉnh lịch')
 * @var string $class       Class thêm
 * @var int    $lane        Cột con (0 = trái nhất)
 * @var int    $lanes       Tổng số cột con của cụm chồng giờ
 * @var int    $hour_height Chiều cao hàng giờ (px, mặc định LMS_CAL_HOUR_H)
 *
 * PHP 7.3 compatible.
 */

$variant    = isset($variant) ? strtolower(trim((string) $variant)) : 'upcoming';
$allowed    = array('upcoming', 'missing', 'finished', 'cancelled', 'free');
if (!in_array($variant, $allowed, true)) {
    $variant = 'finished';
}
$title      = isset($title) ? (string) $title : '';
$start      = isset($start) ? substr(trim((string) $start), 0, 5) : '';
$end        = isset($end) ? substr(trim((string) $end), 0, 5) : '';
$showTime   = !isset($show_time) || (bool) $show_time;
$href       = isset($href) ? (string) $href : '';
$attrs      = isset($attrs) && is_array($attrs) ? $attrs : array();
$tooltip    = isset($tooltip) ? (string) $tooltip : '';
$flag       = isset($flag) ? (string) $flag : '';
$footer     = isset($footer) ? (string) $footer : '';
$extraClass = isset($class) ? (string) $class : '';
$lane       = isset($lane) ? max(0, (int) $lane) : 0;
$lanes      = isset($lanes) ? max(1, (int) $lanes) : 1;
$hourHeight = isset($hour_height) ? (int) $hour_height : LMS_CAL_HOUR_H;

$classes = 'lms-cal-event lms-cal-event--' . $variant;
if ($extraClass !== '') {
    $classes .= ' ' . $extraClass;
}
$styles = '--lms-cal-top:' . lms_cal_offset_px($start, $hourHeight) . 'px;'
    . '--lms-cal-height:' . lms_cal_span_px($start, $end, $hourHeight) . 'px;'
    . '--lms-cal-lane:' . $lane . ';'
    . '--lms-cal-lanes:' . $lanes . ';';

$attrHtml = '';
foreach ($attrs as $key => $value) {
    if ($value === true) {
        $attrHtml .= ' ' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8');
    } elseif ($value !== false && $value !== null && $value !== '') {
        $attrHtml .= ' ' . htmlspecialchars((string) $key, ENT_QUOTES, 'UTF-8')
            . '="' . htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8') . '"';
    }
}

$titleClass = 'lms-cal-event__title' . ($href !== '' ? ' lms-cal-event__title--link' : '');
$titleHtml  = $href !== ''
    ? '<a class="' . e($titleClass) . '" href="' . e($href) . '">' . e($title) . '</a>'
    : '<span class="' . e($titleClass) . '">' . e($title) . '</span>';

$html  = '<div class="' . e($classes) . '"'
    . ' style="' . e($styles) . '"'
    . ($tooltip !== '' ? ' title="' . e($tooltip) . '"' : '')
    . $attrHtml . '>';
$html .= $titleHtml;
if ($showTime && ($start !== '' || $end !== '')) {
    $html .= '<span class="lms-cal-event__time">' . e(lms_cal_time_text($start, $end)) . '</span>';
}
if ($footer !== '') {
    $html .= '<span class="lms-cal-event__footer">' . $footer . '</span>';
}
if ($flag !== '') {
    $html .= '<span class="lms-cal-event__flag">' . $flag . '</span>';
}
$html .= '</div>';

echo $html;
