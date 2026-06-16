<?php
$title = "Cấu hình Website";
$langs = config('lang', [['code' => 'vi', 'name' => 'Tiếng Việt']]);

$schemaStr = $firstItem->schema_config ?? '[]';
if (empty($schemaStr)) $schemaStr = '[]';
if (is_array($schemaStr) || is_object($schemaStr)) {
    $schemaStr = json_encode($schemaStr, JSON_UNESCAPED_UNICODE);
}
$schema = json_decode($schemaStr, true);
if (!is_array($schema)) $schema = [];

?>
<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => $title, 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= route('admin.setting.update') ?>" method="POST" id="settingForm">
            <!-- Hidden schema config -->
            <input type="hidden" name="schema_config" id="schema_config_input" value="<?= htmlspecialchars((string)$schemaStr) ?>">
            
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
                                    $record = $settings[$c] ?? null;
                                    $l_company = $_POST['company_name'][$c] ?? ($record->company_name ?? '');
                                    $l_logo = $_POST['logo_image'][$c] ?? ($record->logo_image ?? '');
                                    $l_favicon = $_POST['favicon_image'][$c] ?? ($record->favicon_image ?? '');
                                    $payload = $record->data_payload ?? [];
                                ?>
                                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="pane-<?= $c ?>" role="tabpanel">
                                    
                                    <h6 class="fw-bold text-primary mb-3"><i class="fa-regular fa-id-card"></i> Thông tin Định danh</h6>
                                    <?= view('admin.components.input', [
                                        'name' => "company_name[$c]",
                                        'value' => $l_company,
                                        'label' => 'Tên công ty / Thương hiệu - ' . $lang['name'],
                                        'attrs' => ['placeholder' => 'Vd: Công ty TNHH Demo']
                                    ]) ?>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <?= view('admin.components.image_upload', [
                                                'name' => "logo_image[$c]",
                                                'value' => $l_logo,
                                                'label' => 'Logo Website - ' . $lang['name']
                                            ]) ?>
                                        </div>
                                        <div class="col-md-6">
                                            <?= view('admin.components.image_upload', [
                                                'name' => "favicon_image[$c]",
                                                'value' => $l_favicon,
                                                'label' => 'Favicon (Icon trên tab trình duyệt) - ' . $lang['name']
                                            ]) ?>
                                        </div>
                                    </div>

                                    <?php if(!empty($schema)): ?>
                                        <hr class="my-4">
                                        <h6 class="fw-bold text-info mb-3"><i class="fa-solid fa-puzzle-piece"></i> Các trường mở rộng</h6>
                                        
                                        <?= view('admin.components.dynamic_form_renderer', [
                                            'schema' => $schema,
                                            'payload' => $payload,
                                            'input_prefix' => "data_payload[$c]"
                                        ]) ?>
                                    <?php endif; ?>

                                </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- SCHEMA BUILDER COMPONENT -->
                    <?= view('admin.components.dynamic_schema_builder', [
                        'form_id' => 'settingForm',
                        'input_id' => 'schema_config_input'
                    ]) ?>

                </div>

                <div class="col-md-3">
                    <div class="card card-outline card-secondary mb-4 sticky-top" style="top: 20px; z-index: 10;">
                        <div class="card-header bg-white"><h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập</h5></div>
                        <div class="card-body bg-light">
                            <p class="small text-muted mb-0">Bấm nút Lưu để áp dụng tất cả thay đổi cấu hình trên mọi ngôn ngữ.</p>
                        </div>
                        <?= view('admin.components.save_buttons', [
                            'buttons' => [
                                [
                                    'type' => 'submit',
                                    'name' => 'save_action',
                                    'value' => 'exit',
                                    'class' => 'btn btn-primary btn-sm',
                                    'icon' => 'fa-solid fa-save',
                                    'text' => 'Lưu cấu hình'
                                ]
                            ]
                        ]) ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
