<?php
$title = isset($firstItem) ? "Sửa Item" : "Thêm Item mới";

$sort_order = $_POST['sort_order'] ?? ($firstItem->sort_order ?? 0);
$is_active = isset($firstItem) ? $firstItem->is_active : 1;

$schema = $block->schema_config ?? [];
if (is_string($schema)) {
    $schema = json_decode($schema, true) ?: [];
}

// Get payload data
$payloads = [];
$langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);
if(isset($firstItem)) {
    $payloads = $firstItem->parsed_payload ?? [];
}
?>
<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Khối giao diện', 'url' => route('admin.block.index')],
        ['name' => $block->name, 'url' => route('admin.block_item.index', ['block_id' => $block->id_code])],
        ['name' => $title, 'url' => '']
    ],
    'actions' => [
        ['label' => 'Quay lại', 'icon' => 'fa-arrow-left', 'url' => route('admin.block_item.index', ['block_id' => $block->id_code]), 'class' => 'btn-default']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= isset($firstItem) ? route('admin.block_item.update', ['block_id' => $block->id_code, 'id' => $firstItem->id_code]) : route('admin.block_item.store', ['block_id' => $block->id_code]) ?>" method="POST">
            
            <div class="row">
                <div class="col-md-9">
                    <div class="card card-outline card-primary mb-4">
                        <?php if (count($langs) > 1): ?>
                        <div class="card-header p-0 pt-1 border-bottom-0 bg-white">
                            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                <?php $i = 0; foreach($langs as $lang): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $i === 0 ? 'active fw-bold' : '' ?>"
                                        data-bs-toggle="tab" data-bs-target="#pane-<?= $lang['code'] ?>"
                                        type="button" role="tab">
                                        <i class="fa-solid fa-language text-primary"></i> <?= htmlspecialchars($lang['name']) ?>
                                    </button>
                                </li>
                                <?php $i++; endforeach; ?>
                            </ul>
                        </div>
                        <?php else: ?>
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Thông tin chính</h5>
                        </div>
                        <?php endif; ?>
                        
                        <div class="card-body">
                            <?php if(empty($schema)): ?>
                                <div class="alert alert-warning">
                                    Khối này chưa được cấu hình Field nào. Vui lòng quay lại cấu hình Khối để thêm Field trước khi tạo Item.
                                </div>
                            <?php else: ?>
                                <div class="tab-content">
                                    <?php $i = 0; foreach($langs as $lang): ?>
                                    <?php $c = $lang['code']; ?>
                                    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>"
                                         id="pane-<?= $c ?>" role="tabpanel">
                                        
                                        <?php foreach($schema as $field): 
                                            $fieldName = htmlspecialchars($field['name']);
                                            $fieldLabel = htmlspecialchars($field['label']);
                                            $fieldType = $field['type'] ?? 'text';
                                            $fieldValue = $payloads[$c][$fieldName] ?? '';
                                            $inputName = "data_payload[$c][$fieldName]";
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
                                                    <textarea name="<?= $inputName ?>" class="form-control" rows="3"><?= htmlspecialchars($fieldValue) ?></textarea>
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
                                        
                                    </div>
                                    <?php $i++; endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header bg-white"><h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập</h5></div>
                        <div class="card-body bg-light">
                            <?= view('admin.components.input', [
                                'type' => 'number',
                                'name' => 'sort_order',
                                'value' => $sort_order,
                                'label' => 'Số thứ tự',
                                'help_text' => 'Số nhỏ hiển thị trước.'
                            ]) ?>

                            <?= view('admin.components.switch', [
                                'name' => 'is_active',
                                'checked' => $is_active,
                                'label' => 'Cho phép hiển thị'
                            ]) ?>
                        </div>
                        <?= view('admin.components.save_buttons', ['back_url' => route('admin.block_item.index', ['block_id' => $block->id_code])]) ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
