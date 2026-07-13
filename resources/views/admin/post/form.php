<?php
$isEdit = isset($item['id']) && $item['id'] > 0;
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
            <input type="hidden" name="lang" value="<?= htmlspecialchars($langCode) ?>">
            <?php if (isset($item['id_code'])): ?>
                <input type="hidden" name="id_code" value="<?= $item['id_code'] ?>">
            <?php endif; ?>

            <div class="row">
                <!-- Cột Trái: Nội dung -->
                <div class="col-md-9">
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold">Nội dung bài viết (<?= htmlspecialchars($currentLangName) ?>)</h5>
                        </div>
                        <div class="card-body">
                            <?= view('admin.components.input', [
                                'name' => "title",
                                'value' => $item['title'] ?? '',
                                'label' => 'Tiêu đề bài viết',
                                'attrs' => [
                                    'placeholder' => 'Nhập tên...',
                                    'required' => true,
                                    'data-slug-source' => 'vi'
                                ]
                            ]) ?>
                            
                            <?php $isAutoSlug = empty($item['slug']) ? 'auto-slug' : ''; ?>
                            <?= view('admin.components.input', [
                                'name' => "slug",
                                'value' => $item['slug'] ?? '',
                                'label' => 'Đường dẫn thân thiện (Alias / Slug)',
                                'attrs' => [
                                    'placeholder' => 'tu-dong-tao-neu-de-trong',
                                    'class' => "text-muted $isAutoSlug",
                                    'data-slug-target' => 'vi'
                                ]
                            ]) ?>

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
                        'module_route' => 'admin.post',
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
                                <select name="category_id" class="form-select form-select-sm">
                                    <option value="0">--- Chọn danh mục ---</option>
                                    <?php renderCategoryTree($categories ?? [], $item['category_id'] ?? 0); ?>
                                </select>
                            </div>

                            <!-- Nhúng Component Tải ảnh tái sử dụng -->
                            <?= view('admin.components.image_upload', [
                                'name' => 'image',
                                'value' => $item['image'] ?? '',
                                'label' => 'Hình đại diện (Thumbnail)'
                            ]) ?>

                            <!-- Nhúng Component Ngày đăng -->
                            <?= view('admin.components.datetime', [
                                'name' => 'created_at',
                                'value' => $item['created_at'] ?? date('Y-m-d H:i:s'),
                                'label' => 'Ngày đăng'
                            ]) ?>

                            <?= view('admin.components.switch', [
                                'name' => 'status',
                                'checked' => !isset($item['id']) || !empty($item['status']),
                                'label' => 'Cho phép hiển thị'
                            ]) ?>
                            
                            <?= view('admin.components.switch', [
                                'name' => 'is_featured',
                                'checked' => !empty($item['is_featured']),
                                'label' => 'Nổi bật',
                                'attrs' => [
                                    'class' => 'text-danger'
                                ]
                            ]) ?>

                        </div>
                        <?= view('admin.components.save_buttons', ['back_url' => route('admin.post.index')]) ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>