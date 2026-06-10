<?php
/**
 * Component DateTime Local
 * 
 * Các tham số truyền vào:
 * @param string $name Tên biến submit (vd: 'created_at')
 * @param string $value Giá trị thời gian (định dạng Y-m-d H:i:s hoặc timestamp), mặc định là hiện tại
 * @param string $label Nhãn hiển thị, mặc định 'Thời gian'
 * @param string $class Thêm CSS class cho input (optional)
 */

$label = $label ?? 'Thời gian';
$name = $name ?? 'datetime';
$value = $value ?? date('Y-m-d H:i:s');
$help_text = $help_text ?? '';
$attrs = $attrs ?? [];

$baseClass = 'form-control form-control-sm';
if (isset($attrs['class'])) {
    $attrs['class'] = $baseClass . ' ' . $attrs['class'];
} else {
    $attrs['class'] = $baseClass;
}

// Convert to datetime-local format: YYYY-MM-DDThh:mm
$valueLocal = '';
if ($value) {
    $valueLocal = date('Y-m-d\TH:i', strtotime($value));
}

$attrString = render_attrs($attrs);
?>

<div class="mb-3">
    <?php if ($label): ?>
        <label class="form-label fw-bold"><?= htmlspecialchars($label) ?></label>
    <?php endif; ?>
    
    <input type="datetime-local" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($valueLocal) ?>" <?= $attrString ?>>
    
    <?php if ($help_text): ?>
        <small class="text-muted fst-italic"><?= htmlspecialchars($help_text) ?></small>
    <?php endif; ?>
</div>
