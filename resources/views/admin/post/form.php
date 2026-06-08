<?php
if (!function_exists('renderCategoryTree')) {
    function renderCategoryTree($categories, $selectedId = 0, $currentEditingId = 0, $prefix = '') {
        foreach ($categories as $cat) {
            if ($currentEditingId > 0 && $cat->id_code == $currentEditingId) continue;
            $selected = ($cat->id_code == $selectedId) ? 'selected' : '';
            echo '<option value="' . $cat->id_code . '" ' . $selected . '>' . $prefix . htmlspecialchars($cat->name ?? $cat->ten) . '</option>';
            if (!empty($cat->children)) {
                renderCategoryTree($cat->children, $selectedId, $currentEditingId, $prefix . '--- ');
            }
        }
    }
}
$isEdit = isset($item);
$action = $isEdit ? route('admin.post.update', ['id' => $item['id']]) : route('admin.post.store');
?>

<?= view('admin.components.breadcrumb', [
    'title' => $isEdit ? 'Cập nhật bài viết' : 'Thêm bài viết mới',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Bài viết', 'url' => route('admin.post.index')],
        ['name' => $isEdit ? 'Cập nhật' : 'Thêm mới', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $action ?>" method="POST">
            <div class="row">
                <!-- Cột Trái: Đa Ngôn Ngữ -->
                <div class="col-md-9">
                    <div class="card card-outline card-primary mb-4">
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
                                        <label class="form-label fw-bold">Tiêu đề bài viết <span class="text-danger">*</span></label>
                                        <input type="text" name="ten[<?= $c ?>]" class="form-control form-control-sm" placeholder="Nhập tên..." value="<?= htmlspecialchars($item['ten'][$c] ?? '') ?>" data-slug-source="<?= $c ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Đường dẫn thân thiện (Alias / Slug)</label>
                                        <?php $isAutoSlug = empty($item['alias'][$c]) ? 'auto-slug' : ''; ?>
                                        <input type="text" name="alias[<?= $c ?>]" class="form-control form-control-sm text-muted <?= $isAutoSlug ?>" placeholder="tu-dong-tao-neu-de-trong" value="<?= htmlspecialchars($item['alias'][$c] ?? '') ?>" data-slug-target="<?= $c ?>">
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
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập chung</h5>
                        </div>
                        <div class="card-body bg-light">
                            
                            <div class="mb-3">
                                <label class="form-label">Danh mục cha</label>
                                <select name="id_loai" class="form-select form-select-sm">
                                    <option value="0">--- Chọn danh mục ---</option>
                                    <?php renderCategoryTree($categories ?? [], $item['id_loai'] ?? 0); ?>
                                </select>
                            </div>



                            <!-- Nhúng Component Tải ảnh tái sử dụng -->
                            <?= view('admin.components.image_upload', [
                                'name' => 'hinh_anh',
                                'value' => $item['hinh_anh'] ?? '',
                                'label' => 'Hình đại diện (Thumbnail)'
                            ]) ?>

                            <div class="mb-3">
                                <label class="form-label">Ngày đăng</label>
                                <?php 
                                    $createdAt = $item['created_at'] ?? date('Y-m-d H:i:s');
                                    // Convert to datetime-local format: YYYY-MM-DDThh:mm
                                    $createdAtLocal = date('Y-m-d\TH:i', strtotime($createdAt));
                                ?>
                                <input type="datetime-local" name="created_at" class="form-control form-control-sm" value="<?= $createdAtLocal ?>">
                            </div>

                            <div class="form-check form-switch mb-3 d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" name="hien_thi" id="hien_thi" <?= (!isset($item) || !empty($item['hien_thi'])) ? 'checked' : '' ?>>
                                <label class="form-check-label mt-1 ms-2 fw-bold" for="hien_thi">Cho phép hiển thị</label>
                            </div>
                            
                            <div class="form-check form-switch mb-3 d-flex align-items-center">
                                <input class="form-check-input" type="checkbox" name="is_featured" id="is_featured" <?= (!empty($item['is_featured'])) ? 'checked' : '' ?>>
                                <label class="form-check-label mt-1 ms-2 fw-bold text-danger" for="is_featured">Nổi bật</label>
                            </div>

                        </div>
                        <div class="card-footer d-flex justify-content-end gap-1 flex-wrap">
                            <a href="<?= route('admin.post.index') ?>" class="btn btn-secondary btn-sm">
                                <i class="fa-solid fa-arrow-left"></i> Quay lại
                            </a>
                            <button type="submit" name="save_action" value="exit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-save"></i> Lưu
                            </button>
                            <button type="submit" name="save_action" value="continue" class="btn btn-success btn-sm">
                                <i class="fa-solid fa-pen-to-square"></i> Lưu và sửa
                            </button>

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
