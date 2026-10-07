<?php
/**
 * Component: form/checkbox
 * Checkbox / switch trong form (không gọi AJAX, chỉ submit cùng form).
 *
 * Props:
 * @var string $label
 * @var string $name
 * @var string $id
 * @var bool   $checked
 * @var bool   $switch  true = kiểu công tắc, false = checkbox thường
 * @var bool   $inline  true = hiển thị cùng dòng với các checkbox khác
 * @var mixed  $value   (tuỳ chọn) value gửi lên, mặc định browser gửi 'on'
 * @var string $hint
 */
$label   = $label   ?? '';
$name    = $name    ?? '';
$id      = $id      ?? ($name !== '' ? 'chk_' . preg_replace('/[^a-z0-9_]/i', '_', $name) : '');
$checked = !empty($checked);
$switch  = $switch  ?? true;
$inline  = !empty($inline);
$value   = $value   ?? null;
$hint    = $hint    ?? '';
?>
<div class="form-check <?= $switch ? 'form-switch' : '' ?> <?= $inline ? 'form-check-inline' : '' ?> mb-2">
    <input class="form-check-input"
           type="checkbox"
           <?= $switch ? 'role="switch"' : '' ?>
           name="<?= e($name) ?>"
           id="<?= e($id) ?>"
           <?= $value !== null ? 'value="' . e($value) . '"' : '' ?>
           <?= $checked ? 'checked' : '' ?>>
    <label class="form-check-label" for="<?= e($id) ?>"><?= e($label) ?></label>
    <?php if ($hint !== ''): ?>
        <div class="form-text mt-0"><?= e($hint) ?></div>
    <?php endif; ?>
</div>