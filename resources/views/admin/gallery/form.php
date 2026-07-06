<?php
$isEdit = isset($item) && isset($item['id']);
$action = route('admin.gallery.store');
$currentLangCode = $langCode ?? 'vi';

// Get current lang info
$currentLangName = 'Tiếng Việt';
foreach($langs as $l) {
    if ($l['code'] == $currentLangCode) {
        $currentLangName = $l['name'];
        break;
    }
}
?>
<?= view('admin.components.breadcrumb', [
    'title' => $isEdit ? "Cập nhật Album ($currentLangName)" : "Thêm Album mới ($currentLangName)",
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
            <input type="hidden" name="lang" value="<?= $currentLangCode ?>">
            <?php if (isset($item['id_code'])): ?>
                <input type="hidden" name="id_code" value="<?= $item['id_code'] ?>">
            <?php endif; ?>
            
            <div class="row">
                <!-- Cột Trái: Nội dung & Hình Ảnh -->
                <div class="col-md-9">
                    
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header bg-white">
                            <h3 class="card-title mb-0 fw-bold"><i class="fa-solid fa-pen-to-square text-primary"></i> Nội dung Album</h3>
                        </div>
                        <div class="card-body">
                            <?= view('admin.components.input', [
                                'name' => "title",
                                'value' => $item['title'] ?? '',
                                'label' => 'Tên Album <span class="text-danger">*</span>',
                                'attrs' => [
                                    'placeholder' => 'Nhập tên album...',
                                    'required' => true,
                                    'data-slug-source' => $currentLangCode
                                ]
                            ]) ?>
                            
                            <?php $isAutoSlug = empty($item['slug']) ? 'auto-slug' : ''; ?>
                            <?= view('admin.components.input', [
                                'name' => "alias",
                                'value' => $item['slug'] ?? '',
                                'label' => 'Đường dẫn thân thiện (Alias / Slug)',
                                'attrs' => [
                                    'placeholder' => 'tu-dong-tao-neu-de-trong',
                                    'class' => "text-muted $isAutoSlug",
                                    'data-slug-target' => $currentLangCode
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


                        </div>
                    </div>
                    
                    <!-- Phần Component Tải danh sách hình ảnh -->
                    <div class="card card-outline card-success mb-4">
                        <div class="card-header bg-white">
                            <h3 class="card-title mb-0 fw-bold"><i class="fa-solid fa-images text-success"></i> Danh sách hình ảnh Album (Dùng chung)</h3>
                        </div>
                        <div class="card-body">
                            <?= view('admin.components.gallery_upload', [
                                'name' => 'gallery[]',
                                'values' => $item['gallery'] ?? [],
                                'label' => '',
                                'path' => '/img_data/images/'
                            ]) ?>
                        </div>
                    </div>

                    <!-- Block SEO -->
                    <div class="card card-outline card-primary mb-4">
                        <div class="card-header bg-white">
                            <h3 class="card-title mb-0 fw-bold"><i class="fa-solid fa-magnifying-glass text-primary"></i> Tối ưu hóa công cụ tìm kiếm (SEO)</h3>
                        </div>
                        <div class="card-body">
                            <?= view('admin.components.seo', [
                                'c' => '',
                                'item' => $item ?? []
                            ]) ?>
                        </div>
                    </div>
                    
                </div>
                
                <!-- Cột Phải -->
                <div class="col-md-3">
                    
                    <?= view('admin.components.polylang', [
                        'module_route' => 'admin.gallery',
                        'langs' => $langs,
                        'currentLangCode' => $currentLangCode,
                        'currentLangName' => $currentLangName,
                        'item' => $item ?? [],
                        'translations' => $translations ?? []
                    ]) ?>

                    <div class="card card-outline card-primary shadow-sm mb-4">
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
                                'path' => '/img_data/images/'
                            ]) ?>
                            
                            <!-- Ngày đăng -->
                            <?= view('admin.components.input', [
                                'name' => 'created_at',
                                'label' => 'Ngày đăng',
                                'value' => isset($item['created_at']) ? date('Y-m-d\TH:i', strtotime($item['created_at'])) : date('Y-m-d\TH:i'),
                                'type' => 'datetime-local'
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
                                'checked' => !isset($item['status']) || $item['status'] == 1,
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
