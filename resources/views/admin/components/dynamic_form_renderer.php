<?php
/**
 * Tham số truyền vào:
 * - $schema: (array) Mảng cấu trúc field (name, label, type)
 * - $payload: (array) Giá trị hiện tại của các field
 * - $input_prefix: (string) Tiền tố của thuộc tính name (Vd: "data_payload[vi]")
 */

$schema = $schema ?? [];
$payload = $payload ?? [];
$input_prefix = $input_prefix ?? 'data_payload';
?>

<?php foreach($schema as $field): 
    $fieldName = htmlspecialchars($field['name']);
    $fieldLabel = htmlspecialchars($field['label']);
    $fieldType = $field['type'] ?? 'text';
    $fieldValue = $payload[$fieldName] ?? '';
    $inputName = "{$input_prefix}[{$fieldName}]";
?>
    <?php if($fieldType === 'text' || $fieldType === 'number' || $fieldType === 'link'): ?>
        <?= view('admin.components.input', [
            'type' => $fieldType === 'number' ? 'number' : 'text',
            'name' => $inputName,
            'value' => $fieldValue,
            'label' => $fieldLabel
        ]) ?>
    <?php elseif($fieldType === 'textarea'): ?>
        <div class="mb-3">
            <label class="form-label fw-bold"><?= $fieldLabel ?></label>
            <textarea name="<?= $inputName ?>" class="form-control" rows="3"><?= htmlspecialchars((string)$fieldValue) ?></textarea>
        </div>
    <?php elseif($fieldType === 'richtext'): ?>
        <?= view('admin.components.ckeditor', [
            'name' => $inputName,
            'value' => $fieldValue,
            'label' => $fieldLabel
        ]) ?>
    <?php elseif($fieldType === 'image'): ?>
        <?= view('admin.components.image_upload', [
            'name' => $inputName,
            'value' => $fieldValue,
            'label' => $fieldLabel
        ]) ?>
    <?php endif; ?>
<?php endforeach; ?>