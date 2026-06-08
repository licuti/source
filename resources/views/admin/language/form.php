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
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <form action="<?= $action ?>" method="POST" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-9">
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Thông tin cấu hình</h3>
                        </div>
                        <div class="card-body">
                            
                            <?php if (!isset($language)): ?>
                            <?php $presets = require config_path('language_presets.php'); ?>
                            <div class="mb-4 p-3 bg-light border rounded">
                                <label for="preset_language" class="form-label text-primary fw-bold"><i class="fa-solid fa-magic"></i> Tự động điền theo ngôn ngữ phổ biến</label>
                                <select class="form-select form-select-sm border-primary" id="preset_language">
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
                                    <input type="text" class="form-control form-control-sm" id="code" name="code" value="<?= isset($language) ? htmlspecialchars($language->code) : '' ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="label" class="form-label">Nhãn (VIE, ENG...) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-sm" id="label" name="label" value="<?= isset($language) ? htmlspecialchars($language->label) : '' ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">Tên hiển thị (Tiếng Việt, Tiếng Anh...) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="name" name="name" value="<?= isset($language) ? htmlspecialchars($language->name) : '' ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="price_unit" class="form-label">Đơn vị tiền tệ (VND, USD...) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="price_unit" name="price_unit" value="<?= isset($language) ? htmlspecialchars($language->price_unit) : '' ?>" required>
                            </div>

                            <?= view('admin.components.image_upload', [
                                'name' => 'image',
                                'value' => isset($language) ? $language->image : '',
                                'label' => 'Đường dẫn Icon (Image URL)'
                            ]); ?>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="locale" class="form-label">Mã Bản địa hóa (VD: vi_VN, en_US)</label>
                                    <input type="text" class="form-control form-control-sm" id="locale" name="locale" value="<?= isset($language) ? htmlspecialchars($language->locale) : '' ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="currency_symbol" class="form-label">Ký hiệu tiền tệ (VD: ₫, $)</label>
                                    <input type="text" class="form-control form-control-sm" id="currency_symbol" name="currency_symbol" value="<?= isset($language) ? htmlspecialchars($language->currency_symbol) : '' ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập</h3>
                        </div>
                        <div class="card-body bg-light">
                            <div class="mb-3">
                                <label for="sort_order" class="form-label">Số thứ tự</label>
                                <input type="number" class="form-control form-control-sm" id="sort_order" name="sort_order" value="<?= isset($language) ? $language->sort_order : '0' ?>">
                            </div>

                            <div class="form-check form-switch mb-2 pt-2">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?= (isset($language) && $language->is_active == 1) || !isset($language) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold" for="is_active">Hiển thị ngôn ngữ</label>
                            </div>
                            
                            <div class="form-check form-switch mb-2">
                                <input class="form-check-input" type="checkbox" id="is_default" name="is_default" value="1" <?= (isset($language) && $language->is_default == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold" for="is_default">Mặc định</label>
                            </div>

                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="is_rtl" name="is_rtl" value="1" <?= (isset($language) && $language->is_rtl == 1) ? 'checked' : '' ?>>
                                <label class="form-check-label fw-bold" for="is_rtl">RTL (Từ phải sang trái)</label>
                            </div>
                        </div>
                        <div class="card-footer d-flex flex-column gap-2">
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fa-solid fa-save"></i> <?= isset($language) ? 'Lưu cập nhật' : 'Thêm mới' ?>
                            </button>
                            <a href="<?= route('admin.language.index') ?>" class="btn btn-secondary btn-sm w-100">
                                <i class="fa-solid fa-arrow-left"></i> Quay lại
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php if (!isset($language)): ?>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const presets = <?= json_encode($presets) ?>;
        const presetSelect = document.getElementById('preset_language');
        
        presetSelect.addEventListener('change', function() {
            const key = this.value;
            if (key && presets[key]) {
                const data = presets[key];
                document.getElementById('code').value = data.code || '';
                document.getElementById('label').value = data.label || '';
                document.getElementById('name').value = data.name || '';
                document.getElementById('price_unit').value = data.price_unit || '';
                document.getElementById('locale').value = data.locale || '';
                document.getElementById('currency_symbol').value = data.currency_symbol || '';
                document.getElementById('is_rtl').checked = data.is_rtl || false;
                
                // Cập nhật giá trị hiển thị cho Component Ảnh nếu bạn muốn thêm logic JS cho CkFinder input (nếu có id)
                const imgInput = document.getElementById('image');
                if (imgInput && data.image) {
                    imgInput.value = data.image;
                    // Cập nhật preview
                    const previewWrapper = imgInput.closest('.input-group').nextElementSibling;
                    if (previewWrapper && previewWrapper.tagName === 'DIV') {
                        previewWrapper.innerHTML = '<img src="/' + data.image + '" style="max-height: 100px; max-width: 100%; border-radius: 4px; box-shadow: 0 1px 3px rgba(0,0,0,0.2);">';
                        previewWrapper.classList.remove('d-none');
                    }
                }
            }
        });
    });
</script>
<?php endif; ?>
