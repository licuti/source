<?php
if (!function_exists('renderCategoryTree')) {
    function renderCategoryTree($categories, $selectedId = 0, $currentEditingId = 0, $prefix = '') {
        foreach ($categories as $cat) {
            if ($currentEditingId > 0 && $cat->id_code == $currentEditingId) continue;
            $selected = ($cat->id_code == $selectedId) ? 'selected' : '';
            echo '<option value="' . $cat->id_code . '" ' . $selected . '>' . $prefix . htmlspecialchars($cat->ten) . '</option>';
            if (!empty($cat->children)) {
                renderCategoryTree($cat->children, $selectedId, $currentEditingId, $prefix . '--- ');
            }
        }
    }
}
$isEdit = isset($item);
$action = $isEdit ? route('admin.category.update', ['id' => $item['id']]) : route('admin.category.store');
?>
<div class="app-content-header">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                <h3 class="mb-0 fw-bold"><?= $isEdit ? 'Cập nhật danh mục' : 'Thêm danh mục mới' ?></h3>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-end">
                    <li class="breadcrumb-item"><a href="<?= route('admin.category.index') ?>">Danh mục</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= $isEdit ? 'Cập nhật' : 'Thêm mới' ?></li>
                </ol>
            </div>
        </div>
    </div>
</div>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $action ?>" method="POST">
            <div class="row">
                <!-- Cột Trái: Đa Ngôn Ngữ -->
                <div class="col-md-9">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header p-0 pt-1 border-bottom-0 bg-white">
                            <ul class="nav nav-tabs" id="langTabs" role="tablist">
                                <?php $i = 0; foreach($langs as $index => $lang): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $i === 0 ? 'active fw-bold' : '' ?>" id="tab-<?= $lang['code'] ?>" data-bs-toggle="tab" data-bs-target="#content-<?= $lang['code'] ?>" type="button" role="tab" aria-controls="content-<?= $lang['code'] ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                                        <i class="fa-solid fa-language text-primary"></i> <?= htmlspecialchars($lang['name']) ?>
                                    </button>
                                </li>
                                <?php $i++; endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="langTabsContent">
                                <?php $i = 0; foreach($langs as $index => $lang): ?>
                                <?php $c = $lang['code']; ?>
                                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="content-<?= $c ?>" role="tabpanel" aria-labelledby="tab-<?= $c ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Tên danh mục <span class="text-danger">*</span></label>
                                        <input type="text" name="ten[<?= $c ?>]" class="form-control form-control-lg" placeholder="Nhập tên..." value="<?= htmlspecialchars($item['ten'][$c] ?? '') ?>" data-slug-source="<?= $c ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Đường dẫn thân thiện (Alias / Slug)</label>
                                        <?php $isAutoSlug = empty($item['alias'][$c]) ? 'auto-slug' : ''; ?>
                                        <input type="text" name="alias[<?= $c ?>]" class="form-control text-muted <?= $isAutoSlug ?>" placeholder="tu-dong-tao-neu-de-trong" value="<?= htmlspecialchars($item['alias'][$c] ?? '') ?>" data-slug-target="<?= $c ?>">
                                    </div>

                                    <!-- Thay thế textarea bằng Component CKEditor cho phần Mô tả -->
                                    <?= view('admin.components.ckeditor', [
                                        'name' => "mo_ta[$c]",
                                        'value' => $item['mo_ta'][$c] ?? '',
                                        'label' => "Mô tả ngắn (" . strtoupper($c) . ")"
                                    ]) ?>

                                    <!-- Nhúng Component CKEditor tái sử dụng -->
                                    <?= view('admin.components.ckeditor', [
                                        'name' => "noi_dung[$c]",
                                        'value' => $item['noi_dung'][$c] ?? '',
                                        'label' => "Nội dung chi tiết"
                                    ]) ?>

                                </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Cột Phải: Cấu Hình Chung -->
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 mb-4 sticky-top" style="top: 70px; z-index: 1;">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập chung</h5>
                        </div>
                        <div class="card-body bg-light">
                            
                            <div class="mb-3">
                                <label class="form-label">Danh mục cha</label>
                                <select name="id_loai" class="form-select">
                                    <option value="0">--- Trở thành Danh mục gốc ---</option>
                                    <?php renderCategoryTree($parentCategories ?? [], $item['id_loai'] ?? 0, $item['id'] ?? 0); ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phân loại hiển thị (Module)</label>
                                <select name="module" class="form-select">
                                    <?php foreach ($modules ?? [] as $mod): ?>
                                    <option value="<?= htmlspecialchars($mod->id) ?>" <?= ($item['module'] ?? '') == $mod->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mod->title) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Nhúng Component Tải ảnh tái sử dụng -->
                            <?= view('admin.components.image_upload', [
                                'name' => 'hinh_anh',
                                'value' => $item['hinh_anh'] ?? '',
                                'label' => 'Hình đại diện (Thumbnail)'
                            ]) ?>

                            <div class="mb-3">
                                <label class="form-label">Số thứ tự hiển thị</label>
                                <input type="number" name="so_thu_tu" class="form-control" value="<?= $item['so_thu_tu'] ?? 0 ?>">
                                <small class="text-muted">Số càng nhỏ ưu tiên hiển thị trước.</small>
                            </div>

                            <div class="form-check form-switch mb-3 pt-2">
                                <input class="form-check-input fs-5" type="checkbox" name="hien_thi" id="hien_thi" <?= (!isset($item) || !empty($item['hien_thi'])) ? 'checked' : '' ?>>
                                <label class="form-check-label mt-1 ms-2 fw-bold" for="hien_thi">Cho phép hiển thị</label>
                            </div>

                        </div>
                        <div class="card-footer bg-white text-end border-top-0 py-3">
                            <a href="<?= route('admin.category.index') ?>" class="btn btn-light border me-2"><i class="fa-solid fa-arrow-left"></i> Trở về</a>
                            <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-save"></i> <?= $isEdit ? 'Lưu cập nhật' : 'Thêm mới' ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Tự động chuyển tab nếu có input bị lỗi HTML5 validation (required) nằm trong tab đang ẩn
    document.addEventListener('invalid', function(e) {
        let target = e.target;
        let tabPane = target.closest('.tab-pane:not(.active)');
        
        if (tabPane) {
            let tabId = tabPane.getAttribute('id');
            let tabButton = document.querySelector(`[data-bs-target="#${tabId}"]`);
            
            if (tabButton && typeof bootstrap !== 'undefined') {
                let tab = new bootstrap.Tab(tabButton);
                tab.show();
                setTimeout(() => target.focus(), 200);
            }
        }
    }, true);
});
</script>
