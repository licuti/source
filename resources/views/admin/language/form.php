<?php
$title = isset($language) ? 'Chỉnh sửa Ngôn ngữ: ' . $language->name : 'Thêm Ngôn ngữ mới';
$action = isset($language) ? route('admin.language.update', ['id' => $language->id]) : route('admin.language.store');
?>

<?= view('admin.components.breadcrumb', [
    'title' => $title,
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Ngôn ngữ', 'url' => route('admin.language.index')],
        ['name' => isset($language) ? 'Chỉnh sửa' : 'Thêm mới', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 mx-auto">
                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Thông tin cấu hình</h3>
                    </div>
                    <form action="<?= $action ?>" method="POST" enctype="multipart/form-data">
                        <div class="card-body">
                            
                            <?php if (!isset($language)): ?>
                            <?php $presets = require config_path('language_presets.php'); ?>
                            <div class="mb-4 p-3 bg-light border rounded">
                                <label for="preset_language" class="form-label text-primary fw-bold"><i class="fa-solid fa-magic"></i> Tự động điền theo ngôn ngữ phổ biến</label>
                                <select class="form-select border-primary" id="preset_language">
                                    <option value="">-- Chọn ngôn ngữ để tự động điền --</option>
                                    <?php foreach ($presets as $k => $p): ?>
                                        <option value="<?= $k ?>"><?= $p['name'] ?> (<?= $p['code'] ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="code" class="form-label">Mã code (vi, en, jp...) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="code" name="code" value="<?= isset($language) ? htmlspecialchars($language->code) : '' ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="label" class="form-label">Nhãn (VIE, ENG...) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="label" name="label" value="<?= isset($language) ? htmlspecialchars($language->label) : '' ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">Tên hiển thị (Tiếng Việt, Tiếng Anh...) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= isset($language) ? htmlspecialchars($language->name) : '' ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="price_unit" class="form-label">Đơn vị tiền tệ (VND, USD...) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="price_unit" name="price_unit" value="<?= isset($language) ? htmlspecialchars($language->price_unit) : '' ?>" required>
                            </div>

                            <?= view('admin.components.image_upload', [
                                'name' => 'image',
                                'value' => isset($language) ? $language->image : '',
                                'label' => 'Đường dẫn Icon (Image URL)'
                            ]); ?>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="locale" class="form-label">Mã Bản địa hóa (VD: vi_VN, en_US)</label>
                                    <input type="text" class="form-control" id="locale" name="locale" value="<?= isset($language) ? htmlspecialchars($language->locale) : '' ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="currency_symbol" class="form-label">Ký hiệu tiền tệ (VD: ₫, $)</label>
                                    <input type="text" class="form-control" id="currency_symbol" name="currency_symbol" value="<?= isset($language) ? htmlspecialchars($language->currency_symbol) : '' ?>">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="sort_order" class="form-label">Số thứ tự</label>
                                    <input type="number" class="form-control" id="sort_order" name="sort_order" value="<?= isset($language) ? $language->sort_order : '0' ?>">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <label class="form-label d-block">Trạng thái cấu hình</label>
                                    
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= (isset($language) && $language->is_active == 1) || !isset($language) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_active">Hiển thị ngôn ngữ này</label>
                                    </div>
                                    
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" <?= (isset($language) && $language->is_default == 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_default">Đặt làm ngôn ngữ mặc định</label>
                                    </div>

                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="is_rtl" name="is_rtl" value="1" <?= (isset($language) && $language->is_rtl == 1) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="is_rtl">Đọc từ phải sang trái (RTL - Ả Rập/Do Thái)</label>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                        <div class="card-footer text-end">
                            <a href="<?= route('admin.language.index') ?>" class="btn btn-secondary me-2">
                                <i class="fa-solid fa-arrow-left"></i> Quay lại
                            </a>
                            <button type="submit" name="submit_action" value="save" class="btn btn-primary">
                                <i class="fa-solid fa-save"></i> Lưu
                            </button>
                            <button type="submit" name="submit_action" value="save_and_edit" class="btn btn-success ms-1">
                                <i class="fa-solid fa-pen-to-square"></i> Lưu và sửa
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if (!isset($language)): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const presets = <?= json_encode($presets ?? []) ?>;
    const presetSelect = document.getElementById('preset_language');
    
    if (presetSelect) {
        presetSelect.addEventListener('change', function() {
            const key = this.value;
            if (!key || !presets[key]) return;
            
            const data = presets[key];
            
            document.getElementById('code').value = data.code;
            document.getElementById('label').value = data.label;
            document.getElementById('name').value = data.name;
            document.getElementById('locale').value = data.locale;
            document.getElementById('price_unit').value = data.price_unit;
            document.getElementById('currency_symbol').value = data.currency_symbol;
            
            const rtlCheck = document.getElementById('is_rtl');
            if (rtlCheck) {
                rtlCheck.checked = data.is_rtl;
            }
        });
    }
});
</script>
<?php endif; ?>
