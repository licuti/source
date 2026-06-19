<?php
/**
 * Component Radio Buttons
 * 
 * Các tham số truyền vào:
 * @param string $name Tên biến submit chung cho nhóm radio
 * @param string $value Giá trị đang được chọn
 * @param array  $options Mảng lựa chọn dạng ['giá_trị' => 'Nhãn hiển thị']
 * @param string $label Nhãn chung cho nhóm
 * @param bool   $inline Trình bày nằm ngang (true) hoặc nằm dọc (false)
 * @param string $help_text Văn bản chú thích nhỏ
 * @param array  $attrs Các thuộc tính HTML bổ sung cho TẤT CẢ nút radio
 */

$name = $name ?? '';
$value = $value ?? '';
$options = $options ?? [];
$label = $label ?? '';
$inline = $inline ?? false;
$help_text = $help_text ?? '';
$attrs = $attrs ?? [];

$baseClass = 'form-check-input';
if (isset($attrs['class'])) {
    $attrs['class'] = $baseClass . ' ' . $attrs['class'];
} else {
    $attrs['class'] = $baseClass;
}
$attrs['type'] = 'radio';

?>

<div class="mb-3">
    <?php if ($label): ?>
        <label class="form-label fw-bold d-block"><?= $label ?></label>
    <?php endif; ?>
    
    <div>
        <?php foreach ($options as $optValue => $optLabel): ?>
            <?php 
                $isChecked = ((string)$optValue === (string)$value);
                $optAttrs = $attrs;
                if ($isChecked) $optAttrs['checked'] = true;
                
                $id = 'radio_' . $name . '_' . md5((string)$optValue);
                $optAttrs['id'] = $id;
                $attrString = render_attrs($optAttrs);
            ?>
            <div class="form-check <?= $inline ? 'form-check-inline' : '' ?>">
                <input name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars((string)$optValue) ?>" <?= $attrString ?>>
                <label class="form-check-label" for="<?= $id ?>">
                    <?= htmlspecialchars($optLabel) ?>
                </label>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($help_text): ?>
        <small class="text-muted fst-italic"><?= htmlspecialchars($help_text) ?></small>
    <?php endif; ?>
</div>
