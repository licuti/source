<?php
/**
 * Component Switch (Toggle)
 * 
 * Các tham số truyền vào:
 * @param string $name Tên biến submit
 * @param bool|int $checked Trạng thái bật/tắt (true/false hoặc 1/0)
 * @param string $label Nhãn hiển thị bên cạnh công tắc
 * @param string $help_text Văn bản chú thích nhỏ phía dưới
 * @param array  $attrs Các thuộc tính HTML khác
 */

$name = $name ?? '';
$checked = !empty($checked);
$label = $label ?? '';
$help_text = $help_text ?? '';
$attrs = $attrs ?? [];

$baseClass = 'form-check-input';
if (isset($attrs['class'])) {
    $attrs['class'] = $baseClass . ' ' . $attrs['class'];
} else {
    $attrs['class'] = $baseClass;
}

// Ensure it acts like a checkbox
$attrs['type'] = 'checkbox';
if (!isset($attrs['value'])) {
    $attrs['value'] = '1';
}
if ($checked) {
    $attrs['checked'] = true;
}

// Generate unique ID if not provided, for the label 'for' attribute
$id = $attrs['id'] ?? 'switch_' . uniqid();
$attrs['id'] = $id;

$attrString = render_attrs($attrs);
?>

<div class="mb-3">
    <div class="form-check form-switch d-flex align-items-center">
        <input name="<?= htmlspecialchars($name) ?>" <?= $attrString ?>>
        <?php if ($label): ?>
            <label class="form-check-label mt-1 ms-2 fw-bold" for="<?= htmlspecialchars($id) ?>"><?= $label ?></label>
        <?php endif; ?>
    </div>
    <?php if ($help_text): ?>
        <small class="text-muted fst-italic ms-5"><?= htmlspecialchars($help_text) ?></small>
    <?php endif; ?>
</div>
