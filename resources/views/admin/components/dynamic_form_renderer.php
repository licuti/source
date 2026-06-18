<?php
/**
 * Tham số truyền vào:
 * - $schema: (array) Mảng cấu trúc field (name, label, type, tab)
 * - $payload: (array) Giá trị hiện tại của các field
 * - $input_prefix: (string) Tiền tố của thuộc tính name (Vd: "data_payload[vi]")
 */

$schema = $schema ?? [];
$payload = $payload ?? [];
$input_prefix = $input_prefix ?? 'data_payload';
$random_id = uniqid('df_');

// Group fields by tab
$groups = [];
foreach($schema as $field) {
    $tabName = !empty($field['tab']) ? trim($field['tab']) : 'Thông tin chung';
    if(!isset($groups[$tabName])) {
        $groups[$tabName] = [];
    }
    $groups[$tabName][] = $field;
}

$hasMultipleTabs = count($groups) > 1;
?>

<?php if($hasMultipleTabs): ?>
<ul class="nav nav-pills mb-3 p-2 rounded" role="tablist">
    <?php $i = 0; foreach($groups as $tabName => $fields): $tabId = $random_id . '_' . $i; ?>
    <li class="nav-item" role="presentation">
        <button class="nav-link <?= $i === 0 ? 'active fw-bold' : '' ?>" data-bs-toggle="pill" data-bs-target="#<?= $tabId ?>" type="button" role="tab">
            <?= htmlspecialchars($tabName) ?>
        </button>
    </li>
    <?php $i++; endforeach; ?>
</ul>
<div class="tab-content border p-3 rounded">
<?php else: ?>
<div>
<?php endif; ?>

    <?php $i = 0; foreach($groups as $tabName => $fields): $tabId = $random_id . '_' . $i; ?>
        <?php if($hasMultipleTabs): ?>
        <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="<?= $tabId ?>" role="tabpanel">
        <?php endif; ?>
        
        <div class="row">
            <?php foreach($fields as $field): 
                $fieldName = htmlspecialchars($field['name']);
                $fieldLabel = htmlspecialchars($field['label']);
                $fieldType = $field['type'] ?? 'text';
                $fieldValue = $payload[$fieldName] ?? '';
                $inputName = "{$input_prefix}[{$fieldName}]";
            ?>
                <div class="col-12 mb-3">
                    <?php if($fieldType === 'text' || $fieldType === 'number' || $fieldType === 'link'): ?>
                        <?= view('admin.components.input', [
                            'type' => $fieldType === 'number' ? 'number' : 'text',
                            'name' => $inputName,
                            'value' => $fieldValue,
                            'label' => $fieldLabel
                        ]) ?>
                    <?php elseif($fieldType === 'textarea'): ?>
                        <label class="form-label fw-bold"><?= $fieldLabel ?></label>
                        <textarea name="<?= $inputName ?>" class="form-control" rows="3"><?= htmlspecialchars((string)$fieldValue) ?></textarea>
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
                </div>
            <?php endforeach; ?>
        </div>

        <?php if($hasMultipleTabs): ?>
        </div>
        <?php endif; ?>
    <?php $i++; endforeach; ?>

</div>