<?php
$title = isset($firstItem) ? "Sửa Khối giao diện" : "Thêm Khối giao diện mới";

$alias = $_POST['alias'] ?? ($firstItem->alias ?? '');
$sort_order = $_POST['sort_order'] ?? ($firstItem->sort_order ?? 0);
$is_active = isset($firstItem) ? $firstItem->is_active : 1;

$schemaStr = $firstItem->schema_config ?? '[]';
if (empty($schemaStr)) $schemaStr = '[]';
if (is_array($schemaStr) || is_object($schemaStr)) {
    $schemaStr = json_encode($schemaStr, JSON_UNESCAPED_UNICODE);
}
?>
<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Khối giao diện', 'url' => route('admin.block.index')],
        ['name' => $title, 'url' => '']
    ],
    'actions' => [
        ['label' => 'Quay lại', 'icon' => 'fa-arrow-left', 'url' => route('admin.block.index'), 'class' => 'btn-default']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= isset($firstItem) ? route('admin.block.update', ['id' => $firstItem->id_code]) : route('admin.block.store') ?>" method="POST" id="blockForm">
            <!-- Hidden schema config -->
            <input type="hidden" name="schema_config" id="schema_config_input" value="<?= htmlspecialchars($schemaStr) ?>">
            
            <div class="row">
                <div class="col-md-9">
                    <!-- LANGUAGE TABS -->
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
                            <div class="tab-content" id="langTabsContent">
                                <?php $i = 0; foreach($langs as $lang): 
                                    $c = $lang['code'];
                                    $l_name = $_POST['name'][$c] ?? ($firstItem->lang_data[$c]['name'] ?? '');
                                    $l_desc = $_POST['description'][$c] ?? ($firstItem->lang_data[$c]['description'] ?? '');
                                    $l_image = $_POST['image'][$c] ?? ($firstItem->lang_data[$c]['image'] ?? '');
                                ?>
                                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="pane-<?= $c ?>" role="tabpanel">
                                    <?= view('admin.components.input', [
                                        'name' => "name[$c]",
                                        'value' => $l_name,
                                        'label' => 'Tên khối (Name) - ' . $lang['name'],
                                        'help_text' => 'Chỉ hiển thị trong Admin để dễ quản lý',
                                        'attrs' => ['required' => ($i === 0), 'placeholder' => 'Vd: Slide Trang Chủ']
                                    ]) ?>
                                    
                                    <?= view('admin.components.ckeditor', [
                                        'name' => "description[$c]",
                                        'value' => $l_desc,
                                        'label' => 'Mô tả (Description) - ' . $lang['name'],
                                        'help_text' => 'Mô tả chi tiết về khối'
                                    ]) ?>
                                    
                                    <div class="mb-3">
                                        <?= view('admin.components.image_upload', [
                                            'name' => "image[$c]",
                                            'value' => $l_image,
                                            'label' => 'Ảnh đại diện khối - ' . $lang['name'],
                                            'help_text' => 'Hiển thị minh họa cho khối'
                                        ]) ?>
                                    </div>
                                </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- SCHEMA BUILDER COMPONENT -->
                    <?= view('admin.components.dynamic_schema_builder', [
                        'form_id' => 'blockForm',
                        'input_id' => 'schema_config_input'
                    ]) ?>
