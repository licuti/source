<?php
$isEdit = isset($item);
$action = $isEdit ? route('admin.category.update', ['id' => $item['id']]) : route('admin.category.store');
?>

<?= view('admin.components.breadcrumb', [
    'title' => $isEdit ? 'Cập nhật danh mục' : 'Thêm danh mục mới',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Danh mục', 'url' => route('admin.category.index')],
        ['name' => $isEdit ? 'Cập nhật' : 'Thêm mới', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $action ?>" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="lang" value="<?= htmlspecialchars($langCode) ?>">
            <?php if (!empty($item['id'])): ?>
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
            <?php endif; ?>

            <div class="row">
                <!-- Cột Trái: Nội dung -->
                <div class="col-md-9">
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Nội dung danh mục (<?= htmlspecialchars($currentLangName) ?>)</h5>
                        </div>
                        <div class="card-body">
                            
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tên danh mục <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control form-control-sm <?= errors('title') ? 'is-invalid' : '' ?>" placeholder="Nhập tên..." value="<?= htmlspecialchars(old('title', $item['title'] ?? '')) ?>" data-slug-source="<?= htmlspecialchars($langCode) ?>">
                                <?php if(errors('title')): ?>
                                    <div class="invalid-feedback"><?= errors('title') ?></div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Đường dẫn thân thiện (Alias / Slug)</label>
                                <?php $isAutoSlug = empty($item['slug']) ? 'auto-slug' : ''; ?>
                                <input type="text" name="slug" class="form-control form-control-sm text-muted <?= $isAutoSlug ?>" placeholder="tu-dong-tao-neu-de-trong" value="<?= htmlspecialchars($item['slug'] ?? '') ?>" data-slug-target="<?= htmlspecialchars($langCode) ?>">
                            </div>

                            <?= view('admin.components.ckeditor', [
                                'name' => "description",
                                'value' => $item['description'] ?? '',
                                'label' => "Mô tả ngắn"
                            ]) ?>

                            <?= view('admin.components.ckeditor', [
                                'name' => "content",
                                'value' => $item['content'] ?? '',
                                'label' => "Nội dung chi tiết"
                            ]) ?>

                            <!-- Nhúng Component SEO -->
                            <?= view('admin.components.seo', [
                                'c' => '',
                                'item' => $item ?? []
                            ]) ?>

                        </div>
                    </div>
                </div>

                <!-- Cột Phải: Cấu Hình Chung -->
                <div class="col-md-3">
                    
                    <?= view('admin.components.polylang', [
                        'module_route' => 'admin.category',
                        'langs' => $langs,
                        'currentLangCode' => $langCode,
                        'currentLangName' => $currentLangName,
                        'item' => $item ?? [],
                        'translations' => $translations ?? []
                    ]) ?>
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập chung</h5>
                        </div>
                        <div class="card-body bg-light">
                            
                            <div class="mb-3">
                                <label class="form-label">Danh mục cha</label>
                                <select name="parent_id" class="form-select form-select-sm <?= errors('parent_id') ? 'is-invalid' : '' ?>">
                                    <option value="0">--- Trở thành Danh mục gốc ---</option>
                                    <?php renderCategoryTree($parentCategories ?? [], old('parent_id', $item['parent_id'] ?? 0), $item['id'] ?? 0); ?>
                                </select>
                                <?php if(errors('parent_id')): ?>
                                    <div class="invalid-feedback"><?= errors('parent_id') ?></div>
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phân loại hiển thị (Module)</label>
                                <select name="module" class="form-select form-select-sm">
                                    <?php foreach ($modules ?? [] as $mod): ?>
                                    <option value="<?= htmlspecialchars($mod->id) ?>" <?= ($item['module'] ?? '') == $mod->id ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($mod->title) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Nhúng Component Tải ảnh tái sử dụng -->
                            <?= view('admin.components.image_upload', [
                                'name' => 'image',
                                'value' => $item['image'] ?? '',
                                'label' => 'Hình đại diện (Thumbnail)'
                            ]) ?>

                            <?= view('admin.components.image_upload', [
                                'name' => 'banner',
                                'value' => $item['banner'] ?? '',
                                'label' => 'Hình Banner (Tùy chọn)'
                            ]) ?>

                            <?= view('admin.components.input', [
                                'type' => 'number',
                                'name' => 'sort_order',
                                'value' => $item['sort_order'] ?? 0,
                                'label' => 'Số thứ tự hiển thị',
                                'help_text' => 'Số càng nhỏ ưu tiên hiển thị trước.'
                            ]) ?>

                            <div class="pt-2">
                                <?= view('admin.components.switch', [
                                    'name' => 'status',
                                    'checked' => !isset($item) || !empty($item['status']),
                                    'label' => 'Cho phép hiển thị',
                                    'attrs' => ['id' => 'status']
                                ]) ?>
                            </div>

                            <?= view('admin.components.switch', [
                                'name' => 'is_featured',
                                'checked' => !empty($item['is_featured']),
                                'label' => 'Danh mục Nổi bật',
                                'attrs' => ['id' => 'is_featured']
                            ]) ?>

                            <?= view('admin.components.datetime', [
                                'name' => 'created_at',
                                'value' => $item['created_at'] ?? '',
                                'label' => 'Ngày tạo'
                            ]) ?>

                        </div>
                        <?= view('admin.components.save_buttons', [
                            'back_url' => route('admin.category.index')
                        ]) ?>
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
