<?php
/**
 * Component Input Tổng Quát
 * 
 * Các tham số truyền vào:
 * @param string $type Loại input (text, number, email, password, color...), mặc định 'text'
 * @param string $name Tên biến submit (bắt buộc)
 * @param string $value Giá trị mặc định (bắt buộc)
 * @param string $label Nhãn hiển thị bên ngoài thẻ input
 * @param string $help_text Văn bản chú thích nhỏ phía dưới
 * @param array  $attrs Các thuộc tính HTML khác (id, class, required, readonly, min, max...)
 */

$type = $type ?? 'text';
$name = $name ?? '';
$value = $value ?? '';
$label = $label ?? '';
$help_text = $help_text ?? '';
$attrs = $attrs ?? [];

// Tự động chuyển đổi name array sang dot notation (VD: title[vi] -> title.vi)
$dotName = str_replace(['[', ']'], ['.', ''], $name);
$dotName = rtrim($dotName, '.');

// 1. Tự động lấy giá trị cũ nếu validation thất bại
$oldValue = old($dotName);
if ($oldValue !== '' && $oldValue !== null && !is_array($oldValue)) {
    $value = $oldValue;
}

// 2. Tự động kiểm tra lỗi validation
$errorMsg = errors($dotName);

// Mặc định thẻ input luôn có class form-control (hoặc form-control-color nếu type=color)
$baseClass = $type === 'color' ? 'form-control form-control-color form-control-sm' : 'form-control form-control-sm';
if ($errorMsg) {
    $baseClass .= ' is-invalid';
}

if (isset($attrs['class'])) {
    $attrs['class'] = $baseClass . ' ' . $attrs['class'];
} else {
    $attrs['class'] = $baseClass;
}

$attrString = render_attrs($attrs);
?>

<div class="mb-3">
    <?php if ($label): ?>
        <label class="form-label fw-bold"><?= htmlspecialchars($label) ?></label>
    <?php endif; ?>
    
    <input type="<?= htmlspecialchars($type) ?>" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars((string)$value) ?>" <?= $attrString ?>>
    
    <?php if ($errorMsg): ?>
        <div class="invalid-feedback fw-bold">
            <i class="fa-solid fa-circle-exclamation"></i> <?= htmlspecialchars($errorMsg) ?>
        </div>
    <?php elseif ($help_text): ?>
        <small class="text-muted fst-italic"><?= htmlspecialchars($help_text) ?></small>
    <?php endif; ?>
</div>
