<?php
/**
 * Component Select Dropdown
 * 
 * Các tham số truyền vào:
 * @param string $name Tên biến submit
 * @param mixed  $value Giá trị đang được chọn (có thể là string/int hoặc mảng nếu là multiple)
 * @param array  $options Mảng dữ liệu option dạng ['giá_trị' => 'Nhãn hiển thị']
 * @param string $label Nhãn hiển thị bên ngoài
 * @param string $placeholder Nhãn hiển thị cho option rỗng đầu tiên (nếu có)
 * @param string $help_text Văn bản chú thích nhỏ
 * @param array  $attrs Các thuộc tính HTML khác (id, class, required, multiple...)
 */

$name = $name ?? '';
$value = $value ?? '';
$options = $options ?? [];
$label = $label ?? '';
$placeholder = $placeholder ?? '';
$help_text = $help_text ?? '';
$attrs = $attrs ?? [];

$baseClass = 'form-select form-select-sm';
if (isset($attrs['class'])) {
    $attrs['class'] = $baseClass . ' ' . $attrs['class'];
} else {
    $attrs['class'] = $baseClass;
}

// Convert value to array to handle both single and multiple selects uniformly
$valueArray = is_array($value) ? $value : [$value];

$attrString = render_attrs($attrs);
?>

<div class="mb-3">
    <?php if ($label): ?>
        <label class="form-label fw-bold"><?= htmlspecialchars($label) ?></label>
    <?php endif; ?>
    
    <select name="<?= htmlspecialchars($name) ?>" <?= $attrString ?>>
        <?php if ($placeholder !== ''): ?>
            <option value=""><?= htmlspecialchars($placeholder) ?></option>
        <?php endif; ?>
        
        <?php foreach ($options as $optValue => $optLabel): ?>
            <?php $selected = in_array((string)$optValue, array_map('strval', $valueArray)) ? 'selected' : ''; ?>
            <option value="<?= htmlspecialchars((string)$optValue) ?>" <?= $selected ?>><?= htmlspecialchars($optLabel) ?></option>
        <?php endforeach; ?>
    </select>
    
    <?php if ($help_text): ?>
        <small class="text-muted fst-italic"><?= htmlspecialchars($help_text) ?></small>
    <?php endif; ?>
</div>
