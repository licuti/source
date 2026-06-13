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
                                        <label class="form-label fw-bold">Tên danh mục <span class="text-danger">*</span></label>
                                        <input type="text" name="title[<?= $c ?>]" class="form-control form-control-sm" placeholder="Nhập tên..." value="<?= htmlspecialchars($item['title'][$c] ?? '') ?>" data-slug-source="<?= $c ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Đường dẫn thân thiện (Alias / Slug)</label>
                                        <?php $isAutoSlug = empty($item['slug'][$c]) ? 'auto-slug' : ''; ?>
                                        <input type="text" name="slug[<?= $c ?>]" class="form-control form-control-sm text-muted <?= $isAutoSlug ?>" placeholder="tu-dong-tao-neu-de-trong" value="<?= htmlspecialchars($item['slug'][$c] ?? '') ?>" data-slug-target="<?= $c ?>">
                                    </div>

                                    <!-- Thay thế textarea bằng Component CKEditor cho phần Mô tả -->
                                    <?= view('admin.components.ckeditor', [
                                        'name' => "description[$c]",
                                        'value' => $item['description'][$c] ?? '',
                                        'label' => "Mô tả ngắn (" . strtoupper($c) . ")"
                                    ]) ?>

                                    <!-- Nhúng Component CKEditor tái sử dụng -->
                                    <?= view('admin.components.ckeditor', [
                                        'name' => "content[$c]",
                                        'value' => $item['content'][$c] ?? '',
                                        'label' => "Nội dung chi tiết"
                                    ]) ?>

                                </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <!-- TAB ĐA NGÔN NGỮ (SEO) -->
                    <div class="card card-outline card-success mb-4">
                        <div class="card-header p-0 pt-1 border-bottom-0 bg-white">
                            <ul class="nav nav-tabs" id="seoLangTabs" role="tablist">
                                <?php $i = 0; foreach($langs as $index => $lang): ?>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link <?= $i === 0 ? 'active fw-bold' : '' ?>" id="seo-tab-<?= $lang['code'] ?>" data-bs-toggle="tab" data-bs-target="#seo-content-<?= $lang['code'] ?>" type="button" role="tab" aria-controls="seo-content-<?= $lang['code'] ?>" aria-selected="<?= $i === 0 ? 'true' : 'false' ?>">
                                        <i class="fa-solid fa-magnifying-glass text-success"></i> SEO <?= htmlspecialchars($lang['name']) ?>
                                    </button>
                                </li>
                                <?php $i++; endforeach; ?>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="seoLangTabsContent">
                                <?php $i = 0; foreach($langs as $index => $lang): ?>
                                <?php $c = $lang['code']; ?>
                                <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="seo-content-<?= $c ?>" role="tabpanel" aria-labelledby="seo-tab-<?= $c ?>">
                                    <?= view('admin.components.seo', [
                                        'c' => $c,
                                        'item' => $item ?? []
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
                                <select name="parent_id" class="form-select form-select-sm">
                                    <option value="0">--- Trở thành Danh mục gốc ---</option>
                                    <?php renderCategoryTree($parentCategories ?? [], $item['parent_id'] ?? 0, $item['id'] ?? 0); ?>
                                </select>
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
                                    'name' => 'is_active',
                                    'checked' => !isset($item) || !empty($item['is_active']),
                                    'label' => 'Cho phép hiển thị',
                                    'attrs' => ['id' => 'is_active']
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
