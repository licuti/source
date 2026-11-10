<?php
/**
 * LMS Shared Component: badge
 * Hiển thị huy hiệu / tag trạng thái chuẩn LMS MasterStudy.
 *
 * Props:
 * @var string      $label    Nội dung nhãn hiển thị (VD: 'Khoá', 'Đang học', '1-1')
 * @var string      $text     Alias của $label
 * @var string      $variant  'danger' | 'success' | 'warning' | 'info' | 'secondary' | 'primary' | 'light' | 'dark' (mặc định: 'secondary')
 * @var string      $type     'bs' (Bootstrap badge bg-*) | 'lms' (LMS pastel chip .lms-badge-*) (mặc định: 'bs')
 * @var bool        $pill     Bo tròn viên thuốc (rounded-pill, mặc định: false)
 * @var string      $icon     FontAwesome icon (VD: 'fa-solid fa-lock')
 * @var string      $class    Class CSS bổ sung (VD: 'ms-1')
 * @var string      $style    Style inline tuỳ biến
 * @var array       $attrs    Các thuộc tính HTML tự do khác
 *
 * PHP 7.3 compatible.
 */

$label   = isset($label) ? (string) $label : (isset($text) ? (string) $text : '');
$variant = isset($variant) && $variant !== '' ? (string) $variant : 'secondary';
$type    = isset($type) && in_array($type, array('bs', 'lms', 'soft'), true) ? $type : 'bs';
$pill    = !empty($pill);
$icon    = isset($icon) ? (string) $icon : '';
$extra   = isset($class) ? (string) $class : '';
$style   = isset($style) ? (string) $style : '';
$attrs   = isset($attrs) && is_array($attrs) ? $attrs : array();

// Chuẩn hoá variant
$variantMap = array(
    'active'    => 'success',
    'locked'    => 'danger',
    'paused'    => 'secondary',
    'arranging' => 'warning',
    'finished'  => 'info',
    'cancelled' => 'danger',
);
if (isset($variantMap[$variant])) {
    $variant = $variantMap[$variant];
}

// Xây dựng class danh sách
$classes = array();
if ($type === 'lms' || $type === 'soft') {
    $classes[] = 'lms-badge';
    $classes[] = 'lms-badge-' . $variant;
} else {
    $classes[] = 'badge';
    $classes[] = 'bg-' . $variant;
    if ($pill) {
        $classes[] = 'rounded-pill';
    }
}

if ($extra !== '') {
    $classes[] = $extra;
}

$classAttr = implode(' ', array_unique($classes));

$attrPairs = array();
$attrPairs[] = 'class="' . htmlspecialchars($classAttr, ENT_QUOTES, 'UTF-8') . '"';

if ($style !== '') {
    $attrPairs[] = 'style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"';
}

foreach ($attrs as $k => $v) {
    if (is_bool($v)) {
        if ($v) $attrPairs[] = htmlspecialchars($k, ENT_QUOTES, 'UTF-8');
    } else {
        $attrPairs[] = htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '"';
    }
}

$iconHtml = '';
if ($icon !== '') {
    $iconHtml = '<i class="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . ' me-1"></i>';
}
?>
<span <?= implode(' ', $attrPairs) ?>><?= $iconHtml ?><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
