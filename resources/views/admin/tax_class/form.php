<?php
$title = $title ?? 'Thêm Nhóm Thuế';
$isEdit = !empty($item['id']);
$langs = $langs ?? [];
?>
<?= view('admin.components.breadcrumb', [
    'title'  => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Thương mại điện tử', 'url' => '#'],
        ['name' => 'Quản lý Nhóm Thuế', 'url' => route('admin.tax_class.index')],
        ['name' => $title, 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $isEdit ? route('admin.tax_class.update', ['id' => $item['id']]) : route('admin.tax_class.store') ?>" method="POST">
            
            <div class="row">
                <!-- CỘT TRÁI: Nội dung chính -->
                <div class="col-md-9">
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header bg-white p-0 border-bottom-0">
                            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                <?php $i = 0; foreach ($langs as $langItem): $langCode = $langItem['code']; ?>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link <?= $i === 0 ? 'active' : '' ?>" 
                                                id="tab-<?= $langCode ?>" 
                                                data-bs-toggle="tab" 
                                                data-bs-target="#content-<?= $langCode ?>" 
                                                type="button" role="tab">
                                            <i class="fa-solid fa-language text-primary me-1"></i> <?= htmlspecialchars($langItem['name'] ?? $langCode) ?>
                                        </button>
                                    </li>
                                <?php $i++; endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="langTabsContent">
                                <?php $i = 0; foreach ($langs as $langItem): $langCode = $langItem['code']; ?>
                                    <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="content-<?= $langCode ?>" role="tabpanel">
                                        <div class="mb-3">
                                            <?= view('admin.components.input', [
                                                'name' => "name[{$langCode}]",
                                                'value' => $item['name'][$langCode] ?? '',
                                                'label' => 'Tên Nhóm Thuế <span class="text-danger">*</span>',
                                                'help_text' => 'Ví dụ: Hàng chịu VAT 10%, Hàng miễn thuế, Hàng y tế...',
                                                'attrs' => ['required' => ($i === 0), 'placeholder' => 'Nhập tên nhóm thuế']
                                            ]) ?>
                                        </div>
                                    </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CỘT PHẢI: Cấu hình & Hành động -->
                <div class="col-md-3">
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập</h5>
                        </div>
                        <div class="card-body bg-light">
                            <div class="form-check form-switch mb-3 d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" name="is_default" id="is_default" <?= (!empty($item['is_default'])) ? 'checked' : '' ?>>
                                <label class="form-check-label mt-1 ms-2 fw-bold" for="is_default">Mặc định</label>
                            </div>
                            <div class="form-text mb-4"><small>Nếu bật, nhóm thuế này sẽ được tự động chọn khi thêm sản phẩm mới. (Chỉ được phép 1 nhóm thuế mặc định).</small></div>

                            <div class="form-check form-switch mb-3 d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" <?= (!isset($item['is_active']) || !empty($item['is_active'])) ? 'checked' : '' ?>>
                                <label class="form-check-label mt-1 ms-2 fw-bold" for="is_active">Trạng thái hoạt động</label>
                            </div>
                        </div>
                        
                        <?= view('admin.components.save_buttons', [
                            'back_url' => route('admin.tax_class.index')
                        ]) ?>
                    </div>
                </div>
            </div>
            
        </form>
    </div>
</div>
