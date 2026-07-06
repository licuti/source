<?php
$isEdit = isset($item) && $item['id'];
$action = route('admin.gallery.store');
?>
<?= view('admin.components.breadcrumb', [
    'title' => $isEdit ? "Cập nhật Album" : "Thêm Album mới",
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Thư viện ảnh', 'url' => route('admin.gallery.index')],
        ['name' => $isEdit ? 'Cập nhật' : 'Thêm mới', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $action ?>" method="POST" id="albumForm">
            <?php if ($isEdit): ?>
                <input type="hidden" name="id" value="<?= $item['id'] ?>">
            <?php endif; ?>
            
            <div class="row">
                <!-- Cột Trái: Đa Ngôn Ngữ & Hình Ảnh -->
                <div class="col-md-9">
                    
                    <!-- Phần Ngôn Ngữ -->
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
                                        'label' => 'Tên Album <span class="text-danger">*</span>',
                                        'attrs' => [
                                            'placeholder' => 'Nhập tên album...',
                                            'required' => true,
                                            'data-slug-source' => $c
                                        ]
                                    ]) ?>
                                    
                                    <?php $isAutoSlug = empty($item['slug'][$c]) ? 'auto-slug' : ''; ?>
                                    <?= view('admin.components.input', [
                                        'name' => "alias[$c]",
                                        'value' => $item['slug'][$c] ?? '',
                                        'label' => 'Đường dẫn thân thiện (Alias / Slug)',
                                        'attrs' => [
                                            'placeholder' => 'tu-dong-tao-neu-de-trong',
                                            'class' => "text-muted $isAutoSlug",
                                            'data-slug-target' => $c
                                        ]
                                    ]) ?>
                                    
                                    <?= view('admin.components.ckeditor', [
                                        'name' => "description[$c]",
                                        'value' => $item['description'][$c] ?? '',
                                        'label' => "Mô tả ngắn (" . strtoupper($c) . ")"
                                    ]) ?>

                                    <?= view('admin.components.ckeditor', [
                                        'name' => "content[$c]",
                                        'value' => $item['content'][$c] ?? '',
                                        'label' => "Nội dung chi tiết (" . strtoupper($c) . ")"
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
                    
                    <!-- Phần Component Tải danh sách hình ảnh -->
                    <div class="card card-outline card-success mb-4">
                        <div class="card-header bg-white">
                            <h3 class="card-title mb-0 fw-bold"><i class="fa-solid fa-images text-success"></i> Danh sách hình ảnh Album</h3>
                        </div>
                        <div class="card-body">
                            <?= view('admin.components.gallery_upload', [
                                'name' => 'gallery[]',
                                'values' => $item['gallery'] ?? [],
                                'label' => '',
                                'path' => '/upload/album/'
                            ]) ?>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Cột Phải -->
                <div class="col-md-3">
                    <div class="card card-outline card-secondary mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Thiết lập chung</h5>
                        </div>
                        <div class="card-body bg-light">
                            <!-- Danh mục -->
                            <div class="mb-3">
                                <label class="form-label">Danh mục</label>
                                <select name="category_id" class="form-select form-select-sm">
                                    <option value="0">--- Chọn danh mục ---</option>
                                    <?php renderCategoryTree($categories ?? [], $item['category_id'] ?? 0); ?>
                                </select>
                            </div>
                            
                            <!-- Hình ảnh đại diện -->
                            <?= view('admin.components.image_upload', [
                                'name' => 'image',
                                'value' => $item['image'] ?? '',
                                'label' => 'Hình ảnh bìa',
                                'path' => '/upload/album/'
                            ]) ?>
                            
                            <!-- Số thứ tự -->
                            <?= view('admin.components.input', [
                                'name' => 'sort_order',
                                'label' => 'Số thứ tự',
                                'value' => $item['sort_order'] ?? 0,
                                'type' => 'number',
                                'attrs' => ['min' => 0]
                            ]) ?>
                            
                            <!-- Bật / Tắt -->
                            <?= view('admin.components.switch', [
                                'name' => 'status',
                                'checked' => !isset($item) || $item['status'] == 1,
                                'label' => 'Hiển thị'
                            ]) ?>
                            
                        </div>
                        <?= view('admin.components.save_buttons', ['back_url' => route('admin.gallery.index')]) ?>
                    </div>
                </div>
                
            </div>
        </form>
    </div>
</div>
