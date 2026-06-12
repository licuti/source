<?php
$isEdit = isset($item);
$action = $isEdit ? route('admin.product.update', ['id' => $item['id']]) : route('admin.product.store');
?>

<?= view('admin.components.breadcrumb', [
    'title' => $isEdit ? 'Cập nhật sản phẩm' : 'Thêm sản phẩm mới',
    'bitems' => [
        ['name' => 'Bảng điều khiển', 'url' => route('admin.dashboard')],
        ['name' => 'Sản phẩm', 'url' => route('admin.product.index')],
        ['name' => $isEdit ? 'Cập nhật' : 'Thêm mới', 'url' => '']
    ]
]) ?>

<div class="app-content">
    <div class="container-fluid">
        <form action="<?= $action ?>" method="POST">
            <div class="row">
                <!-- Cột Trái: Đa Ngôn Ngữ & Dữ liệu sản phẩm -->
                <div class="col-md-9">
                    
                    <!-- TAB ĐA NGÔN NGỮ -->
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
                                        'label' => 'Tên sản phẩm',
                                        'attrs' => [
                                            'placeholder' => 'Nhập tên sản phẩm...',
                                            'required' => true,
                                            'data-slug-source' => $c
                                        ]
                                    ]) ?>
                                    
                                    <?php $isAutoSlug = empty($item['slug'][$c]) ? 'auto-slug' : ''; ?>
                                    <?= view('admin.components.input', [
                                        'name' => "slug[$c]",
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
                                        'label' => "Bài viết chi tiết sản phẩm"
                                    ]) ?>

                                    <?= view('admin.components.input', [
                                        'name' => "unit[$c]",
                                        'value' => $item['unit'][$c] ?? '',
                                        'label' => 'Đơn vị tính (VD: Cái, Hộp, Kg)'
                                    ]) ?>

                                </div>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- DỮ LIỆU SẢN PHẨM CHUNG (KHÔNG ĐA NGÔN NGỮ) -->
                    <!-- DỮ LIỆU SẢN PHẨM (TABBED META-BOX) -->
                    <div class="card card-outline card-outline-tabs card-info mb-4">
                        <div class="card-header p-0 border-bottom-0">
                            <div class="d-flex justify-content-between align-items-center p-3 border-bottom">
                                <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-database text-info"></i> Dữ liệu sản phẩm</h5>
                                <div class="d-flex align-items-center">
                                    <label class="me-2 mb-0 fw-bold">Loại sản phẩm:</label>
                                    <select name="product_type" class="form-select form-select-sm w-auto fw-bold text-primary">
                                        <option value="simple" <?= ($item['product_type'] ?? '') == 'simple' ? 'selected' : '' ?>>Sản phẩm đơn giản</option>
                                        <option value="variable" <?= ($item['product_type'] ?? '') == 'variable' ? 'selected' : '' ?>>Sản phẩm có biến thể</option>
                                    </select>
                                </div>
                            </div>
                            <ul class="nav nav-tabs px-3 pt-2" id="v-pills-tab" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link active text-dark fw-bold border-bottom-0" id="v-pills-general-tab" data-bs-toggle="pill" data-bs-target="#v-pills-general" type="button" role="tab" aria-controls="v-pills-general" aria-selected="true">
                                        <i class="fa-solid fa-wrench fa-fw text-secondary"></i> Chung
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link text-dark fw-bold border-bottom-0" id="v-pills-inventory-tab" data-bs-toggle="pill" data-bs-target="#v-pills-inventory" type="button" role="tab" aria-controls="v-pills-inventory" aria-selected="false">
                                        <i class="fa-solid fa-box fa-fw text-secondary"></i> Kiểm kho & Giao hàng
                                    </button>
                                </li>
                                <li class="nav-item" id="nav-item-attributes" style="display: none;">
                                    <button class="nav-link text-dark fw-bold border-bottom-0" id="v-pills-attributes-tab" data-bs-toggle="pill" data-bs-target="#v-pills-attributes" type="button" role="tab" aria-controls="v-pills-attributes" aria-selected="false">
                                        <i class="fa-solid fa-tags fa-fw text-primary"></i> Thuộc tính
                                    </button>
                                </li>
                                <li class="nav-item" id="nav-item-variants" style="display: none;">
                                    <button class="nav-link text-dark fw-bold border-bottom-0" id="v-pills-variants-tab" data-bs-toggle="pill" data-bs-target="#v-pills-variants" type="button" role="tab" aria-controls="v-pills-variants" aria-selected="false">
                                        <i class="fa-solid fa-layer-group fa-fw text-success"></i> Các biến thể
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link text-dark fw-bold border-bottom-0" id="v-pills-gallery-tab" data-bs-toggle="pill" data-bs-target="#v-pills-gallery" type="button" role="tab" aria-controls="v-pills-gallery" aria-selected="false">
                                        <i class="fa-solid fa-images fa-fw text-warning"></i> Album ảnh
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body p-4">
                            <div class="tab-content" id="v-pills-tabContent">
                                        
                                        <!-- TAB CHUNG -->
                                        <div class="tab-pane fade show active" id="v-pills-general" role="tabpanel" aria-labelledby="v-pills-general-tab">
                                            <div class="row">
                                                <div class="col-md-4">
                                                    <?= view('admin.components.input', [
                                                        'name' => 'price',
                                                        'value' => $item['price'] ?? 0,
                                                        'label' => 'Giá bán thường (VNĐ)',
                                                        'type' => 'number'
                                                    ]) ?>
                                                </div>
                                                <div class="col-md-4">
                                                    <?= view('admin.components.input', [
                                                        'name' => 'promotional_price',
                                                        'value' => $item['promotional_price'] ?? 0,
                                                        'label' => 'Giá khuyến mãi (VNĐ)',
                                                        'type' => 'number'
                                                    ]) ?>
                                                </div>
                                                <div class="col-md-4">
                                                    <?= view('admin.components.input', [
                                                        'name' => 'cost_price',
                                                        'value' => $item['cost_price'] ?? 0,
                                                        'label' => 'Giá vốn (VNĐ)',
                                                        'type' => 'number'
                                                    ]) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- TAB KHO -->
                                        <div class="tab-pane fade" id="v-pills-inventory" role="tabpanel" aria-labelledby="v-pills-inventory-tab">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <?= view('admin.components.input', [
                                                        'name' => 'sku',
                                                        'value' => $item['sku'] ?? '',
                                                        'label' => 'Mã sản phẩm (SKU)'
                                                    ]) ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <?= view('admin.components.input', [
                                                        'name' => 'barcode',
                                                        'value' => $item['barcode'] ?? '',
                                                        'label' => 'Mã vạch (Barcode / UPC)'
                                                    ]) ?>
                                                </div>
                                            </div>
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <?= view('admin.components.input', [
                                                        'name' => 'stock_quantity',
                                                        'value' => $item['stock_quantity'] ?? 0,
                                                        'label' => 'Số lượng tồn kho',
                                                        'type' => 'number'
                                                    ]) ?>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <label class="form-label">Trạng thái kho</label>
                                                        <select name="stock_status" class="form-select">
                                                            <option value="in_stock" <?= ($item['stock_status'] ?? '') == 'in_stock' ? 'selected' : '' ?>>Còn hàng</option>
                                                            <option value="out_of_stock" <?= ($item['stock_status'] ?? '') == 'out_of_stock' ? 'selected' : '' ?>>Hết hàng</option>
                                                            <option value="on_backorder" <?= ($item['stock_status'] ?? '') == 'on_backorder' ? 'selected' : '' ?>>Cho phép đặt trước</option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <?= view('admin.components.input', [
                                                        'name' => 'weight',
                                                        'value' => $item['weight'] ?? 0,
                                                        'label' => 'Nặng (gram)',
                                                        'type' => 'number',
                                                        'attrs' => ['step' => '0.01']
                                                    ]) ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <?= view('admin.components.input', [
                                                        'name' => 'length',
                                                        'value' => $item['length'] ?? 0,
                                                        'label' => 'Dài (cm)',
                                                        'type' => 'number',
                                                        'attrs' => ['step' => '0.01']
                                                    ]) ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <?= view('admin.components.input', [
                                                        'name' => 'width',
                                                        'value' => $item['width'] ?? 0,
                                                        'label' => 'Rộng (cm)',
                                                        'type' => 'number',
                                                        'attrs' => ['step' => '0.01']
                                                    ]) ?>
                                                </div>
                                                <div class="col-md-3">
                                                    <?= view('admin.components.input', [
                                                        'name' => 'height',
                                                        'value' => $item['height'] ?? 0,
                                                        'label' => 'Cao (cm)',
                                                        'type' => 'number',
                                                        'attrs' => ['step' => '0.01']
                                                    ]) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- TAB THUỘC TÍNH -->
                                        <div class="tab-pane fade" id="v-pills-attributes" role="tabpanel" aria-labelledby="v-pills-attributes-tab">
                                            <div class="d-flex gap-2 mb-4 pb-3 border-bottom">
                                                <select id="attrSelector" class="form-select w-auto">
                                                    <option value="">-- Chọn thuộc tính để thêm --</option>
                                                    <?php foreach($attributes as $attr): ?>
                                                        <option value="<?= $attr->id_code ?>" data-name="<?= htmlspecialchars($attr->title) ?>"><?= htmlspecialchars($attr->title) ?></option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <button type="button" class="btn btn-primary" id="btnAddAttribute">
                                                    <i class="fa-solid fa-plus"></i> Thêm thuộc tính
                                                </button>
                                            </div>
                                            <!-- Nơi chứa các thuộc tính đã chọn -->
                                            <div id="productAttributesContainer">
                                                <!-- Render JS -->
                                            </div>
                                        </div>

                                        <!-- TAB BIẾN THỂ -->
                                        <div class="tab-pane fade" id="v-pills-variants" role="tabpanel" aria-labelledby="v-pills-variants-tab">
                                            <div class="d-flex justify-content-between align-items-center mb-4 pb-3 border-bottom">
                                                <div>
                                                    <p class="text-muted mb-0 fst-italic">Hãy chọn thuộc tính ở tab "Thuộc tính" trước khi tạo biến thể.</p>
                                                </div>
                                                <div>
                                                    <button type="button" class="btn btn-warning me-2 fw-bold" id="btnGenerateVariants">
                                                        <i class="fa-solid fa-bolt"></i> Tạo tổ hợp tự động
                                                    </button>
                                                    <button type="button" class="btn btn-outline-success fw-bold" id="btnAddVariant">
                                                        <i class="fa-solid fa-plus"></i> Thêm thủ công
                                                    </button>
                                                </div>
                                            </div>
                                            <!-- Accordion Container cho các biến thể -->
                                            <div class="accordion" id="variantsAccordion">
                                                <!-- JS sẽ render các accordion-item vào đây -->
                                            </div>
                                        </div>

                                        <!-- TAB ALBUM ẢNH -->
                                        <div class="tab-pane fade" id="v-pills-gallery" role="tabpanel" aria-labelledby="v-pills-gallery-tab">
                                            <div class="mb-3">
                                                <p class="text-muted fst-italic">Tải lên các hình ảnh khác của sản phẩm.</p>
                                                <?= view('admin.components.gallery_upload', [
                                                    'name' => 'gallery[]',
                                                    'values' => $item['gallery'] ?? []
                                                ]) ?>
                                            </div>
                                        </div>

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
                            <h5 class="card-title mb-0 fw-bold"><i class="fa-solid fa-gears text-secondary"></i> Phân loại</h5>
                        </div>
                        <div class="card-body bg-light">
                            
                            <div class="mb-3">
                                <label class="form-label">Danh mục cha</label>
                                <select name="category_id" class="form-select form-select-sm">
                                    <option value="0">--- Chọn danh mục ---</option>
                                    <?php renderCategoryTree($categories ?? [], $item['category_id'] ?? 0); ?>
                                </select>
                            </div>

                            <?= view('admin.components.input', [
                                'name' => 'brand_id',
                                'value' => $item['brand_id'] ?? 0,
                                'label' => 'ID Thương hiệu (tạm thời nhập số)'
                            ]) ?>

                            <hr>

                            <?= view('admin.components.image_upload', [
                                'name' => 'thumbnail',
                                'value' => $item['thumbnail'] ?? '',
                                'label' => 'Hình đại diện chính'
                            ]) ?>

                            <hr>

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
                                'label' => 'Sản phẩm Nổi bật'
                            ]) ?>

                            <?= view('admin.components.switch', [
                                'name' => 'is_new',
                                'checked' => !empty($item['is_new']),
                                'label' => 'Sản phẩm Mới'
                            ]) ?>

                            <?= view('admin.components.switch', [
                                'name' => 'is_hot',
                                'checked' => !empty($item['is_hot']),
                                'label' => 'Sản phẩm Hot'
                            ]) ?>

                            <?= view('admin.components.switch', [
                                'name' => 'is_sale',
                                'checked' => !empty($item['is_sale']),
                                'label' => 'Đang Khuyến Mãi'
                            ]) ?>

                        </div>
                        <div class="card-footer d-flex justify-content-end gap-1 flex-wrap">
                            <a href="<?= route('admin.product.index') ?>" class="btn btn-secondary btn-sm">
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

<?php require __DIR__ . '/form-script.php'; ?>
