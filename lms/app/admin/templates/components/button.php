<?php
/**
 * LMS Shared Component: button
 * Thành phần Button chuẩn dùng chung toàn hệ thống LMS.
 * Có thể render thẻ <button> (submit/button/reset) hoặc thẻ <a> (link button).
 *
 * Props:
 * @var string      $label       Nhãn chữ hiển thị trên nút (VD: 'Lưu', 'Thêm')
 * @var string      $type        'submit' | 'button' | 'reset' (mặc định: có href => thẻ <a>, không có => 'button')
 * @var string      $href        URL đích (nếu có sẽ tự động render thẻ <a>)
 * @var string      $variant     'primary' | 'brand' | 'secondary' | 'success' | 'danger' | 'warning' | 'info' | 'light' | 'outline-*' | 'link' (mặc định: 'primary')
 * @var string      $size        'sm' | 'md' | 'lg' (mặc định: 'md')
 * @var string      $icon        FontAwesome icon class (VD: 'fa-solid fa-floppy-disk', 'fa-solid fa-plus')
 * @var string      $icon_pos    'left' | 'right' (mặc định: 'left')
 * @var string      $class       Class CSS tuỳ biến bổ sung
 * @var string      $id          ID thuộc tính của element
 * @var string      $name        Tên POST khi submit form
 * @var string      $value       Giá trị POST
 * @var bool        $disabled    Vô hiệu hoá nút (disabled hoặc pointer-events: none)
 * @var string      $onclick     Javascript onclick handler
 * @var string      $confirm     Câu hỏi xác nhận popup trước khi bấm (tự sinh confirm JS)
 * @var string      $target      Target cho thẻ <a> (VD: '_blank')
 * @var array       $attrs       Mảng các attribute tự do khác (key => value)
 *
 * PHP 7.3 compatible.
 */

$label    = isset($label) ? (string) $label : '';
$href     = isset($href) && $href !== '' ? (string) $href : '';
$type     = isset($type) && $type !== '' ? (string) $type : ($href !== '' ? '' : 'button');
$variant  = isset($variant) && $variant !== '' ? (string) $variant : 'primary';
$size     = isset($size) && $size !== '' ? (string) $size : 'md';
$icon     = isset($icon) && $icon !== '' ? (string) $icon : '';
$iconPos  = isset($icon_pos) && $icon_pos === 'right' ? 'right' : 'left';
$extraCls = isset($class) ? (string) $class : '';
$id       = isset($id) && $id !== '' ? (string) $id : '';
$name     = isset($name) && $name !== '' ? (string) $name : '';
$value    = isset($value) ? (string) $value : '';
$disabled = !empty($disabled);
$onclick  = isset($onclick) ? (string) $onclick : '';
$confirm  = isset($confirm) ? (string) $confirm : '';
$target   = isset($target) ? (string) $target : '';
$attrs    = isset($attrs) && is_array($attrs) ? $attrs : array();

// Ánh xạ class biến thể màu sắc
$variantClasses = array(
    'primary'           => 'lms-btn-primary',
    'secondary'         => 'lms-btn-secondary',
    'success'           => 'lms-btn-success',
    'danger'            => 'lms-btn-danger',
    'warning'           => 'lms-btn-warning',
    'info'              => 'lms-btn-info',
    'light'             => 'lms-btn-light',
    'link'              => 'btn-link',
    'outline-primary'   => 'lms-btn-outline-primary',
    'outline-secondary' => 'lms-btn-outline-secondary',
    'outline-danger'    => 'lms-btn-outline-danger',
    'outline-success'   => 'lms-btn-outline-success',
);

$btnVariantClass = isset($variantClasses[$variant]) ? $variantClasses[$variant] : 'btn-' . $variant;

// Kích thước
$sizeClasses = array(
    'sm' => 'lms-btn-sm',
    'md' => 'lms-btn-md',
    'lg' => 'lms-btn-lg',
);
$btnSizeClass = isset($sizeClasses[$size]) ? $sizeClasses[$size] : '';

// Tập hợp danh sách class
$classes = array('lms-btn', $btnVariantClass);
if ($btnSizeClass !== '') {
    $classes[] = $btnSizeClass;
}
if ($extraCls !== '') {
    $classes[] = $extraCls;
}
$classAttr = implode(' ', array_unique($classes));

// Xử lý xác nhận popup confirm
if ($confirm !== '') {
    $confirmJs = "return confirm(" . json_encode($confirm, JSON_UNESCAPED_UNICODE) . ");";
    $onclick = $onclick !== '' ? $confirmJs . ' ' . $onclick : $confirmJs;
}

// Xây dựng mảng attribute
$attrPairs = array();
$attrPairs[] = 'class="' . htmlspecialchars($classAttr, ENT_QUOTES, 'UTF-8') . '"';

if ($id !== '') {
    $attrPairs[] = 'id="' . htmlspecialchars($id, ENT_QUOTES, 'UTF-8') . '"';
}
if ($onclick !== '') {
    $attrPairs[] = 'onclick="' . htmlspecialchars($onclick, ENT_QUOTES, 'UTF-8') . '"';
}

foreach ($attrs as $k => $v) {
    if (is_bool($v)) {
        if ($v) $attrPairs[] = htmlspecialchars($k, ENT_QUOTES, 'UTF-8');
    } else {
        $attrPairs[] = htmlspecialchars($k, ENT_QUOTES, 'UTF-8') . '="' . htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') . '"';
    }
}

// Icon HTML
$iconHtml = '';
if ($icon !== '') {
    $iconHtml = '<i class="' . htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') . '"></i>';
}

// Nội dung bên trong button
$contentParts = array();
if ($iconHtml !== '' && $iconPos === 'left') {
    $contentParts[] = $iconHtml;
}
if ($label !== '') {
    $contentParts[] = '<span class="lms-btn-text">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
}
if ($iconHtml !== '' && $iconPos === 'right') {
    $contentParts[] = $iconHtml;
}
$innerContent = implode(' ', $contentParts);

// Render thẻ <a> hoặc <button>
if ($href !== ''):
    if ($target !== '') {
        $attrPairs[] = 'target="' . htmlspecialchars($target, ENT_QUOTES, 'UTF-8') . '"';
    }
    if ($disabled) {
        $attrPairs[] = 'aria-disabled="true" style="pointer-events:none;opacity:0.65;"';
    }
    $attrPairs[] = 'href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"';
    $attrPairs[] = 'role="button"';
    ?>
    <a <?= implode(' ', $attrPairs) ?>><?= $innerContent ?></a>
<?php else:
    $attrPairs[] = 'type="' . htmlspecialchars($type, ENT_QUOTES, 'UTF-8') . '"';
    if ($name !== '') {
        $attrPairs[] = 'name="' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '"';
    }
    if ($value !== '') {
        $attrPairs[] = 'value="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
    }
    if ($disabled) {
        $attrPairs[] = 'disabled';
    }
    ?>
    <button <?= implode(' ', $attrPairs) ?>><?= $innerContent ?></button>
<?php endif; ?>
