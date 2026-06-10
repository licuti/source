<?php
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
                                    
                                    <?= view('admin.components.input', [
                                        'name' => "title[$c]",
                                        'value' => $item['title'][$c] ?? '',
                                        'label' => 'Tiêu đề bài viết',
                                        'attrs' => [
                                            'placeholder' => 'Nhập tên...',
                                            'required' => true,
                                            'data-slug-source' => $c
                                        ]
                                    ]) ?>
                                    
                                    <?php $isAutoSlug = empty($item['alias'][$c]) ? 'auto-slug' : ''; ?>
                                    <?= view('admin.components.input', [
                                        'name' => "alias[$c]",
                                        'value' => $item['alias'][$c] ?? '',
                                        'label' => 'Đường dẫn thân thiện (Alias / Slug)',
                                        'attrs' => [
                                            'placeholder' => 'tu-dong-tao-neu-de-trong',
                                            'class' => "text-muted $isAutoSlug",
                                            'data-slug-target' => $c
                                        ]
                                    ]) ?>

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

                                    <!-- Nhúng Component SEO -->
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
                                        'checked' => !isset($item) || !empty($item['status']),
                                        'label' => 'Cho phép hiển thị'
                                    ]) ?>
                            
                            <?= view('admin.components.switch', [
                                'name' => 'is_featured',
                                'checked' => !empty($item['is_featured']),
                                'label' => 'Nổi bật',
                                'attrs' => [
                                    'class' => 'text-danger' // Just an example, text-danger won't apply to label directly in the new switch component without tweaking, but let's keep the label simple.
                                ]
                            ]) ?>

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