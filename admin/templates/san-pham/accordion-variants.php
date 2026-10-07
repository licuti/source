<?php
// ==========================================
// 1. KHAI BÁO SCHEMA CHO BIẾN THỂ (Cấu hình)
// ==========================================
$variant_schema = [
    // bulk = 'prefix' (Sẽ tự động cộng thêm đuôi -1, -2, -3)
    ['name' => 'ma_sp',    'label' => 'Mã SKU',    'type' => 'text',   'bulk' => 'prefix'], 
    
    // bulk = true (Sẽ copy y hệt giá trị nhập vào cho mọi biến thể)
    ['name' => 'gia',      'label' => 'Giá',       'type' => 'number', 'min' => 0, 'step' => '0.01', 'bulk' => true],
    ['name' => 'khuyen_mai', 'label' => 'Khuyến mãi',       'type' => 'number', 'min' => 0, 'step' => '0.01', 'bulk' => true],
    ['name' => 'so_luong', 'label' => 'Số lượng',  'type' => 'number', 'min' => 0, 'bulk' => true],
    ['name' => 'weight',   'label' => 'Cân nặng (gram)', 'type' => 'number', 'min' => 0, 'bulk' => true],
    
    // bulk = false (Ẩn đi khỏi form Nhập chung)
    ['name' => 'hinh_anh', 'label' => 'Hình ảnh',  'type' => 'image',  'bulk' => false], 
    ['name' => 'mo_ta',    'label' => 'Mô tả ngắn','type' => 'editor', 'bulk' => false], 
];


// Lấy danh sách thuộc tính và giá trị global + custom
$id_sp = !empty($_GET['id']) ? intval($_GET['id']) : 0;
$attributes = $d->o_fet("SELECT * FROM #_thuoctinh WHERE (id_sanpham = 0 OR id_sanpham = $id_sp) AND lang='".LANG."' ORDER BY id ASC");
foreach ($attributes as &$attribute) {
    $id_code = (int) $attribute['id_code'];
    $values = $d->o_fet("SELECT * FROM #_thuoctinh_giatri WHERE id_thuoctinh = $id_code AND (id_sanpham = 0 OR id_sanpham = $id_sp) AND lang='".LANG."' ORDER BY id ASC");
    $attribute['gia_tri'] = $values;
}
unset($attribute);

// Lấy product_attributes từ sản phẩm nếu edit
$product_attributes = [];
if ($id_sp) {
    $product = $d->simple_fetch("SELECT product_attributes FROM #_sanpham WHERE id_code = $id_sp");
    $product_attributes = json_decode($product['product_attributes'] ?? '[]', true);
}

// Lấy danh sách biến thể nếu có
$variants = [];
if ($id_sp) {
    $variants = $d->o_fet("SELECT * FROM #_sanpham_bienthe WHERE id_sanpham = $id_sp ORDER BY id ASC");
    foreach ($variants as &$variant) {
        $variant_id = (int) $variant['id'];
        $attrs = $d->o_fet("SELECT id_thuoctinh_giatri FROM #_sanpham_bienthe_thuoctinh WHERE id_bienthe = $variant_id");
        $variant['thuoc_tinh'] = array_column($attrs, 'id_thuoctinh_giatri');
    }
    unset($variant);
}

// Biến $main được truyền từ them_tpl.php; phòng trường hợp file include độc lập
$main = $main ?? [];

// Hàm render card thuộc tính sản phẩm (UI card-based)
function renderAttributeCard($index, $item, $globalAttributes) {
    $selected_values = $item['values'] ?? [];
    $attr_code = $item['attr'] ?? '';
    $is_new = $item['is_new'] ?? 0;
    $new_attr_name = $item['new_attr_name'] ?? '';

    $attr = null;
    foreach ($globalAttributes as $a) {
        if ((string)$a['id_code'] === (string)$attr_code) {
            $attr = $a;
            break;
        }
    }

    $selectedValuesJson = htmlspecialchars(json_encode($selected_values, JSON_UNESCAPED_UNICODE));

    ob_start();
    ?>
    <div class="attribute-row card mb-3" data-index="<?= $index ?>" data-selected-values='<?= $selectedValuesJson ?>'>
        <div class="card-header d-flex align-items-center gap-2 bg-light">
            <span class="drag-handle" title="Kéo thả để sắp xếp">☰</span>
            <div class="flex-grow-1">
                <select name="product_attributes[<?= $index ?>][attr]" class="form-select form-select-sm attr-select select2" style="width:100%">
                    <option value="">Chọn thuộc tính</option>
                    <?php foreach ($globalAttributes as $global_attr):
                        $selected = ((string)$attr_code === (string)$global_attr['id_code']) ? 'selected' : '';
                        ?>
                        <option value="<?= $global_attr['id_code'] ?>" <?= $selected ?>><?= htmlspecialchars($global_attr['ten']) ?></option>
                    <?php endforeach; ?>
                    <?php if ($is_new): ?>
                        <option value="new" selected>Mới: <?= htmlspecialchars($new_attr_name) ?></option>
                    <?php endif; ?>
                </select>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm remove-attr" title="Xóa thuộc tính">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label small text-muted">Giá trị đã chọn</label>
                <select name="product_attributes[<?= $index ?>][values][]" multiple class="form-select form-select-sm values-select select2" data-select2-id="values-<?= $index ?>" style="width:100%">
                    <?php if ($attr): ?>
                        <?php foreach ($attr['gia_tri'] as $value):
                            $selected = in_array((string)$value['id_code'], array_map('strval', (array)$selected_values)) ? 'selected' : '';
                            ?>
                            <option value="<?= $value['id_code'] ?>" <?= $selected ?>><?= htmlspecialchars($value['ten']) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php if (isset($item['new_values']) && is_array($item['new_values'])): ?>
                        <?php foreach ($item['new_values'] as $new_value):
                            $temp_id = 'new_'.substr(preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(rawurlencode($new_value))), 0, 15);
                            $selected = in_array($temp_id, (array)$selected_values) ? 'selected' : '';
                            ?>
                            <option value="<?= $temp_id ?>" <?= $selected ?>><?= htmlspecialchars($new_value) ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="mb-2">
                <label class="form-label small text-muted">Thêm giá trị mới</label>
                <div class="input-group input-group-sm">
                    <input type="text" name="product_attributes[<?= $index ?>][new_values][]" class="form-control new-value-input" placeholder="Nhập giá trị mới">
                    <button type="button" class="btn btn-primary add-value-btn">
                        <i class="fa-solid fa-plus me-1"></i> Thêm
                    </button>
                </div>
            </div>
            <input type="hidden" name="product_attributes[<?= $index ?>][is_new]" value="<?= $is_new ?>" class="is-new-attr">
            <?php if ($is_new): ?>
                <input type="hidden" name="product_attributes[<?= $index ?>][new_attr_name]" value="<?= htmlspecialchars($new_attr_name) ?>" class="new-attr-name">
            <?php endif; ?>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Hàm render dropdown cho biến thể
function renderAttributeSelects($variantId, $selectedAttributes, $selected = []) {
    $html = '';
    global $attributes;
    foreach ($selectedAttributes as $item) {
        $attr_code = $item['attr'];
        $value_ids = $item['values'];
        $attr = array_filter($attributes, function($a) use ($attr_code) { return (string)$a['id_code'] === (string)$attr_code; });
        $attr = reset($attr);
        if (!$attr && $attr_code !== 'new') continue;

        $html .= '<select name="variants['.$variantId.'][attributes]['.$attr_code.']" class="form-control form-control-sm me-2" required>';
        $html .= '<option value="">Chọn '.($attr ? htmlspecialchars($attr['ten']) : 'Thuộc tính mới').'</option>';
        if ($attr) {
            foreach ($attr['gia_tri'] as $value) {
                if (!in_array((string)$value['id_code'], array_map('strval', $value_ids))) continue;
                $isSelected = in_array((string)$value['id_code'], array_map('strval', $selected)) ? 'selected' : '';
                $html .= '<option value="'.$value['id_code'].'" '.$isSelected.'>'.htmlspecialchars($value['ten']).'</option>';
            }
        }
        // Thêm các giá trị mới tạm thời nếu có
        if (isset($item['new_values']) && is_array($item['new_values'])) {
            foreach ($item['new_values'] as $new_value) {
                // Sửa logic hash cho khớp với encodeURIComponent bên JS
                $tempId = 'new_'.substr(preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(rawurlencode($new_value))), 0, 15);
                if (in_array($tempId, array_map('strval', $value_ids))) {
                    $isSelected = in_array($tempId, array_map('strval', $selected)) ? 'selected' : '';
                    $html .= '<option value="'.$tempId.'" '.$isSelected.'>'.htmlspecialchars($new_value).'</option>';
                }
            }
        }
        $html .= '</select>';
    }
    return $html;
}
?>



<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<!-- Card Cấu hình sản phẩm -->
<div class="card mb-3 config-product-card">
    <div class="card-header bg-light py-3">
        <h5 class="card-title mb-0"><i class="fa-solid fa-sliders me-2"></i>Cấu hình sản phẩm</h5>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-lg-3 col-md-4">
                <div class="nav flex-column nav-pills config-nav-pills" id="commonInfoTabs" role="tablist" aria-orientation="vertical">
                    <button class="nav-link active" id="giakho-tab" data-bs-toggle="pill" data-bs-target="#tab-giakho" type="button" role="tab" aria-controls="tab-giakho" aria-selected="true"><i class="fa-solid fa-tag me-2"></i>Giá & Kho</button>
                    <button class="nav-link" id="thuoctinh-tab" data-bs-toggle="pill" data-bs-target="#tab-thuoctinh" type="button" role="tab" aria-controls="tab-thuoctinh" aria-selected="false"><i class="fa-solid fa-layer-group me-2"></i>Thuộc tính</button>
                    <button class="nav-link" id="bienthe-tab" data-bs-toggle="pill" data-bs-target="#tab-bienthe" type="button" role="tab" aria-controls="tab-bienthe" aria-selected="false"><i class="fa-solid fa-cubes me-2"></i>Biến thể</button>
                    <button class="nav-link" id="album-video-tab" data-bs-toggle="pill" data-bs-target="#tab-album-video" type="button" role="tab" aria-controls="tab-album-video" aria-selected="false"><i class="fa-solid fa-images me-2"></i>Album ảnh và video</button>
                    <button class="nav-link" id="khac-tab" data-bs-toggle="pill" data-bs-target="#tab-khac" type="button" role="tab" aria-controls="tab-khac" aria-selected="false"><i class="fa-solid fa-ellipsis me-2"></i>Khác</button>
                </div>
            </div>
            <div class="col-lg-9 col-md-8">
                <div class="tab-content config-tab-content" id="commonInfoTabsContent">

   <!-- Tab 1: Giá & Kho -->
    <div class="tab-pane fade show active" id="tab-giakho" role="tabpanel" aria-labelledby="giakho-tab">
        <div class="row g-3">
            <div class="col-6 col-md-4 col-lg-3">
                <label class="form-label small">Mã sản phẩm</label>
                <input type="text" name="ma_sp" class="form-control form-control-sm" placeholder="SKU001" value="<?= htmlspecialchars($main["ma_sp"] ?? "") ?>">
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <label class="form-label small">Giá vốn</label>
                <input type="number" name="gia" class="form-control form-control-sm" placeholder="0" min="0" step="0.01" value="<?= htmlspecialchars($main["gia"] ?? "") ?>">
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <label class="form-label small">Giá bán (Khuyến mãi)</label>
                <input type="number" name="khuyen_mai" class="form-control form-control-sm" placeholder="0" min="0" step="0.01" value="<?= htmlspecialchars($main["khuyen_mai"] ?? "") ?>">
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <label class="form-label small">Số lượng (Tồn kho)</label>
                <input type="number" name="so_luong" class="form-control form-control-sm" placeholder="0" min="0" value="<?= htmlspecialchars($main["so_luong"] ?? "0") ?>">
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <label class="form-label small">Cân nặng (g)</label>
                <input type="number" name="weight" class="form-control form-control-sm" placeholder="0" min="0" value="<?= htmlspecialchars($main["weight"] ?? "") ?>">
            </div>
            <div class="col-6 col-md-4 col-lg-3">
                <label class="form-label small">Đã bán</label>
                <input type="number" name="da_ban" class="form-control form-control-sm" placeholder="0" min="0" value="<?= htmlspecialchars($main["da_ban"] ?? "") ?>">
            </div>
        </div>
    </div>
   <!-- Tab 2: Thuộc tính sản phẩm -->
    <style>
        #product-attributes .attribute-row .card-header {
            padding: 0.75rem 1rem;
        }
        #product-attributes .attribute-row {
            display: block !important;
            align-items: stretch !important;
        }
        #product-attributes .attribute-row .card-header {
            flex-wrap: wrap;
        }
        #product-attributes .attribute-row .card-header > div {
            min-width: 0;
            flex: 1 1 0%;
        }
        #product-attributes .attribute-row .card-header .btn {
            flex-shrink: 0;
        }
        /* Card cấu hình sản phẩm */
        .config-product-card {
            border: 1px solid #e9ecef;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .config-product-card .card-header {
            border-bottom: 1px solid #e9ecef;
        }
        .config-product-card .card-title {
            font-weight: 600;
            color: #495057;
        }
        .config-nav-pills {
            background: #f8f9fa;
            border-radius: 0.5rem;
            padding: 0.5rem;
        }
        .config-nav-pills .nav-link {
            text-align: left;
            border-radius: 0.375rem;
            margin-bottom: 0.25rem;
            color: #6c757d;
            font-weight: 500;
            padding: 0.65rem 0.9rem;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }
        .config-nav-pills .nav-link:hover {
            background: #e9ecef;
            color: #495057;
        }
        .config-nav-pills .nav-link.active {
            background: #0d6efd;
            color: #fff;
            border-color: #0d6efd;
            box-shadow: 0 2px 6px rgba(13, 110, 253, 0.2);
        }
        .config-tab-content {
            min-height: 320px;
        }
        #product-attributes .attribute-row .card-body {
            padding: 1rem;
        }
        #product-attributes .attribute-row .select2-container {
            width: 100% !important;
            display: block;
        }
        #global-attr-select {
            width: 100% !important;
            min-width: 200px;
            visibility: visible;
        }
        #product-attributes .sortable-placeholder {
            background: #f8f9fa;
            border: 2px dashed #dee2e6;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
            min-height: 120px;
        }
        #product-attributes .drag-handle {
            cursor: grab;
            color: #adb5bd;
            font-size: 1.1rem;
            user-select: none;
        }
        #product-attributes .drag-handle:active {
            cursor: grabbing;
        }
    </style>

   <div class="tab-pane fade" id="tab-thuoctinh" role="tabpanel" aria-labelledby="thuoctinh-tab">
        <!-- Card: Thêm thuộc tính -->
        <div class="card mb-3">
            <div class="card-body">
                <h6 class="card-title mb-3 w-100">Thêm thuộc tính cho sản phẩm</h6>
                <div class="row align-items-end">
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Chọn từ thuộc tính có sẵn</label>
                        <select id="global-attr-select" class="form-select form-select-sm">
                            <option value="">-- Chọn thuộc tính --</option>
                            <?php foreach ($attributes as $global_attr): ?>
                                <?php
                                $isUsed = false;
                                foreach ($product_attributes as $pa) {
                                    if ((string)$pa['attr'] === (string)$global_attr['id_code']) {
                                        $isUsed = true;
                                        break;
                                    }
                                }
                                ?>
                                <option value="<?= $global_attr['id_code'] ?>" <?= $isUsed ? 'disabled' : '' ?>><?= htmlspecialchars($global_attr['ten']) ?> <?= $isUsed ? '(đã chọn)' : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small text-muted">Hoặc nhập tên thuộc tính mới</label>
                        <input type="text" id="new-attr-name" class="form-control form-control-sm" placeholder="Ví dụ: Kích thước, Chất liệu...">
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="add-global-attr-btn" class="btn btn-primary btn-sm w-100">+ Thêm</button>
                    </div>
                </div>
            </div>
        </div>

        <h6 class="fw-medium mb-2">Danh sách thuộc tính của sản phẩm:</h6>
        <div id="product-attributes">
            <?php 
            $attr_index = 0;
            if (empty($product_attributes)) {
                echo renderAttributeCard($attr_index, [], $attributes);
                $attr_index = 1;
            } else {
                foreach ($product_attributes as $item) {
                    echo renderAttributeCard($attr_index, $item, $attributes);
                    $attr_index++;
                }
            }
            ?>
        </div>
        <button type="button" id="add-attribute-btn" class="btn btn-primary btn-sm mt-2">+ Thêm thuộc tính</button>
    </div>

      <!-- Tab 3: Biến thể -->

   <div class="tab-pane fade" id="tab-bienthe" role="tabpanel" aria-labelledby="bienthe-tab">
        <div class="bulk-section">
            <div class="row g-3">
                <?php foreach ($variant_schema as $field): ?>
                    <?php if (isset($field['bulk']) && $field['bulk'] !== false): ?>
                        <div class="col-6 col-md-3 col-lg-3">
                            <label class="form-label small"><?= $field['label'] ?> <?= $field['bulk'] === 'prefix' ? '<small>(Hậu tố tự động)</small>' : '' ?></label>
                            <?php if ($field['type'] == 'textarea'): ?>
                                <textarea id="bulk_<?= $field['name'] ?>" class="form-control form-control-sm" rows="2"></textarea>
                            <?php else: ?>
                                <input type="<?= $field['type'] == 'number' ? 'number' : 'text' ?>" id="bulk_<?= $field['name'] ?>" class="form-control form-control-sm" <?= isset($field['min']) ? 'min="'.$field['min'].'"' : '' ?> <?= isset($field['step']) ? 'step="'.$field['step'].'"' : '' ?>>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <button type="button" id="applyBulkBtn" class="btn btn-info btn-sm mt-3"><i class="fa-solid fa-bolt"></i> Áp dụng cho tất cả</button>
        </div>
    <div class="accordion mt-3" id="accordionVariants">
    <div id="no-variants-message" class="no-variants-alert">Chưa có thuộc tính hoặc giá trị nào được chọn. Vui lòng thêm thuộc tính trước khi tạo biến thể.</div>
    <?php foreach ($variants as $i => $variant): 
        $panelId = 'collapse'.$variant['id'];
        $headingId = 'heading'.$variant['id'];
    ?>
    <div class="accordion-item variant-item" data-id="<?= $variant['id'] ?>">
        <div class="accordion-header d-flex align-items-center gap-2 p-2" id="<?= $headingId ?>">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $panelId ?>" aria-expanded="false" aria-controls="<?= $panelId ?>">
                Biến thể #<?= $i + 1 ?>
            </button>
            <div class="variant-attrs flex-grow-1 ms-2">
                <?= renderAttributeSelects($variant['id'], $product_attributes, $variant['thuoc_tinh']) ?>
            </div>
            <button type="button" class="btn btn-danger btn-sm btn-remove-variant remove-variant" data-id="<?= $variant['id'] ?>" title="Xóa biến thể"><i class="fa-solid fa-trash"></i></button>
        </div>
        <div id="<?= $panelId ?>" class="accordion-collapse collapse" data-bs-parent="#accordionVariants">
            <div class="accordion-body">
                <input type="hidden" name="variants[<?= $variant['id'] ?>][id]" value="<?= $variant['id'] ?>">
                <div class="row g-3">
                <?php foreach ($variant_schema as $field):
                    $colClass = (in_array($field['type'], ['textarea', 'editor'])) ? 'col-12' : 'col-6 col-md-4 col-lg-3';
                    ?>
                    <div class="mb-3 <?= $colClass ?>">
                        <label class="form-label small"><?= $field['label'] ?></label>
                        
                        <?php if ($field['type'] == 'textarea'): ?>
                            <textarea name="variants[<?= $variant['id'] ?>][<?= $field['name'] ?>]" class="form-control form-control-sm" rows="3"><?= htmlspecialchars($variant[$field['name']] ?? '') ?></textarea>
                        
                        <?php elseif ($field['type'] == 'editor'): ?>
                            <?php $editor_id = "editor_" . $variant['id'] . "_" . $field['name']; ?>
                            <textarea name="variants[<?= $variant['id'] ?>][<?= $field['name'] ?>]" id="<?= $editor_id ?>" class="form-control form-control-sm ckeditor-init" rows="3" data-ckeditor="true"><?= htmlspecialchars($variant[$field['name']] ?? '') ?></textarea>

                        <?php elseif ($field['type'] == 'image'): ?>
                            <?php $field_id = $field['name'] . "_variant_" . $variant['id']; ?>
                            <div class="variant-img-box">
                                <?php if (!empty($variant[$field['name']])): ?>
                                    <img src="<?= htmlspecialchars(img_url($variant[$field['name']])) ?>" id="review_<?= $field_id ?>" class="variant-preview-img" alt="NO PHOTO" />
                                <?php else: ?>
                                    <img src="img/no-image.png" id="review_<?= $field_id ?>" class="variant-preview-img" alt="NO PHOTO" />
                                <?php endif; ?>
                                <input type="hidden" name="variants[<?= $variant['id'] ?>][<?= $field['name'] ?>]" id="<?= $field_id ?>" value="<?= htmlspecialchars($variant[$field['name']] ?? '') ?>" class="form-control form-control-sm">
                                <a <?= function_exists('fm_button_attrs') ? fm_button_attrs([
                                    'field_id' => $field_id,
                                    'type'     => 'image',
                                    'multiple' => false,
                                    'class'    => 'btn btn-sm btn-info iframe-btn js-fm-open mt-2 d-inline-block'
                                ]) : ('href="ckfinder/ckfinder.html?type=Images" class="btn btn-sm btn-info iframe-btn js-fm-open mt-2 d-inline-block" data-fm-driver="ckfinder" data-fm-field="' . $field_id . '" data-fm-type="image" data-fm-resource="Images" data-fm-multiple="0"') ?>>
                                    <i class="fa-solid fa-upload"></i> Chọn <?= $field['label'] ?>
                                </a>
                                <button type="button" class="btn btn-sm btn-outline-danger mt-2 d-inline-block js-clear-variant-img" data-target="<?= $field_id ?>" title="Xóa hình">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>

                        <?php else: ?>
                            <input type="<?= $field['type'] ?>" 
                                   name="variants[<?= $variant['id'] ?>][<?= $field['name'] ?>]" 
                                   class="form-control form-control-sm" 
                                   <?= isset($field['min']) ? 'min="'.$field['min'].'"' : '' ?>
                                   <?= isset($field['step']) ? 'step="'.$field['step'].'"' : '' ?>
                                   value="<?= htmlspecialchars($variant[$field['name']] ?? '') ?>">
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                </div><!-- /row -->
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mt-3 variant-actions">
    <div class="d-flex flex-wrap gap-2">
        <button type="button" id="addVariantBtn" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Thêm biến thể</button>
        <button type="button" id="generateVariantsBtn" class="btn btn-success btn-sm btn-sync-variants" title="Giữ nguyên biến thể đã có, chỉ thêm/bớt theo thuộc tính"><i class="fa-solid fa-rotate me-1"></i> Đồng bộ biến thể</button>
    </div>
    <button type="button" id="deleteAllVariantsBtn" class="btn btn-outline-danger btn-sm"><i class="fa-solid fa-trash me-1"></i> Xóa tất cả biến thể</button>
</div>
</div><!-- /tab-bienthe -->

<!-- Tab 4: Album ảnh và video -->
<div class="tab-pane fade" id="tab-album-video" role="tabpanel" aria-labelledby="album-video-tab">
    <div class="row g-3">
        <div class="col-12">
            <?php component('card_open', ['title' => 'Album hình ảnh', 'icon' => 'fa-solid fa-images']); ?>
                <?php component('form/image_gallery', [
                    'name'        => 'album',
                    'images'      => $rowAlbum,
                    'label'       => '',
                    'choose_text' => 'Chọn hình ảnh',
                ]); ?>
            <?php component('card_close'); ?>
        </div>
        <?php if ($showVideo): ?>
        <div class="col-12">
            <?php component('card_open', ['title' => 'Video', 'icon' => 'fa-solid fa-video']); ?>
                <?php if (!empty($main["video"])): ?>
                    <div class="ratio ratio-16x9 mb-2">
                        <iframe id="if_video"
                                src="https://www.youtube.com/embed/<?= e($main["video"]) ?>"
                                title="YouTube preview"
                                frameborder="0"
                                allowfullscreen></iframe>
                    </div>
                <?php else: ?>
                    <div class="ratio ratio-16x9 mb-2 bg-body-secondary d-flex align-items-center justify-content-center">
                        <span class="text-secondary small">Chưa có video</span>
                    </div>
                <?php endif; ?>

                <?php component('form/input', [
                    'label'       => 'Mã YouTube',
                    'name'        => 'ma_video',
                    'id'          => 'ma_video',
                    'value'       => $main['video'] ?? '',
                    'placeholder' => 'Vd: dQw4w9WgXcQ',
                    'hint'        => 'Chỉ mã v=... của YouTube (phần cuối URL).',
                    'attr'        => [
                        "onchange" => "document.getElementById(\"if_video\").src=\"https://www.youtube.com/embed/\" + this.value;",
                    ],
                ]); ?>
            <?php component('card_close'); ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Tab 5: Khác -->
<div class="tab-pane fade" id="tab-khac" role="tabpanel" aria-labelledby="khac-tab">
    <div class="row g-3">
        <div class="col-md-6">
            <?php component('form/input', [
                'label'       => 'Link Shopee',
                'name'        => 'link_shopee',
                'id'          => 'link_shopee',
                'value'       => $main['link_shopee'] ?? '',
                'placeholder' => 'https://shopee.vn/...',
            ]); ?>
        </div>
        <div class="col-md-6">
            <?php component('form/input', [
                'label'       => 'Link TikTok',
                'name'        => 'link_tiktok',
                'id'          => 'link_tiktok',
                'value'       => $main['link_tiktok'] ?? '',
                'placeholder' => 'https://tiktok.com/...',
            ]); ?>
        </div>
    </div>
</div>


<style>
.sortable-placeholder {
    border: 2px dashed #ccc;
    height: 50px;
    margin: 5px 0;
    background-color: #f9f9f9;
    border-radius: 0.375rem;
}
.attribute-row {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
    padding: 0.75rem;
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 0.375rem;
}
.attribute-row:hover {
    background: #f5f5f5;
}
.attribute-row .drag-handle {
    font-size: 16px;
    color: #666;
    cursor: move;
    padding: 0.5rem;
    display: inline-flex;
    align-items: center;
}
.attribute-row .attr-select {
    width: 25% !important;
    min-width: 150px;
}
.attribute-row .values-select {
    width: 35% !important;
    min-width: 200px;
}
.attribute-row .new-value-input {
    width: 20%;
    min-width: 120px;
}
.no-variants-alert {
    display: none;
    text-align: center;
    color: #dc3545;
    margin: 10px 0;
}
.accordion-header {
    flex-wrap: wrap;
    gap: 0.5rem;
}
.accordion-header .accordion-button {
    width: auto;
    flex: 0 0 auto;
    max-width: 200px;
}
.accordion-header .variant-attrs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    min-width: 0;
}
.accordion-header .variant-attrs select {
    max-width: 180px;
    display: inline-block;
    vertical-align: middle;
}
.variant-img-box {
    display: block;
    margin-top: 5px;
}
.variant-preview-img {
    max-height: 100px;
    display: block;
    margin-bottom: 10px;
    border: 1px solid #ddd;
    padding: 2px;
    object-fit: contain;
}

/* ===== CSS Tab Biến thể ===== */
#tab-bienthe .bulk-section {
    background: #f8f9fa;
    border: 1px dashed #dee2e6;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1rem;
}
#tab-bienthe .bulk-section .form-label {
    font-weight: 500;
    color: #495057;
}
#tab-bienthe .btn-sync-variants {
    background: linear-gradient(135deg, #0d6efd, #0a58ca);
    border: none;
    box-shadow: 0 2px 6px rgba(13, 110, 253, 0.25);
}
#tab-bienthe .btn-sync-variants:hover {
    background: linear-gradient(135deg, #0b5ed7, #0948a3);
}

/* Accordion biến thể */
#accordionVariants .variant-item {
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    margin-bottom: 0.75rem;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0,0,0,0.04);
    transition: box-shadow 0.15s ease;
}
#accordionVariants .variant-item:hover {
    box-shadow: 0 3px 8px rgba(0,0,0,0.08);
}
#accordionVariants .variant-item .accordion-header {
    background: #fff;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid #e9ecef;
    flex-wrap: nowrap;
}
#accordionVariants .variant-item .accordion-button {
    flex: 0 0 auto;
    width: auto;
    max-width: 140px;
    padding: 0.5rem 0.75rem;
    font-weight: 600;
    color: #495057;
    background: #f8f9fa;
    border-radius: 0.375rem;
    border: 1px solid #e9ecef;
    box-shadow: none;
    font-size: 0.9rem;
}
#accordionVariants .variant-item .accordion-button:not(.collapsed) {
    background: #e9ecef;
    color: #212529;
}
#accordionVariants .variant-item .accordion-button:focus {
    box-shadow: none;
}
#accordionVariants .variant-item .accordion-button::after {
    margin-left: 0.5rem;
}
#accordionVariants .variant-attrs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    min-width: 0;
}
#accordionVariants .variant-attrs select {
    width: auto !important;
    min-width: 120px;
    max-width: 180px;
    padding: 0.25rem 0.5rem;
    font-size: 0.85rem;
    border-radius: 0.375rem;
    background: #f8f9fa;
    border: 1px solid #e9ecef;
}
#accordionVariants .variant-attrs select:focus {
    border-color: #86b7fe;
    background: #fff;
}
#accordionVariants .btn-remove-variant {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    flex-shrink: 0;
}
#accordionVariants .accordion-body {
    background: #fff;
    padding: 1.25rem;
}
#accordionVariants .accordion-body .row {
    --bs-gutter-x: 1rem;
}
#accordionVariants .accordion-body .form-label {
    font-size: 0.85rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}
#accordionVariants .accordion-body .form-control-sm,
#accordionVariants .accordion-body .form-select-sm {
    font-size: 0.88rem;
}
#accordionVariants .accordion-body .variant-img-box {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
#accordionVariants .accordion-body .variant-preview-img {
    max-height: 120px;
    border-radius: 0.375rem;
    border: 1px solid #e9ecef;
    padding: 0.25rem;
    background: #fff;
}
</style>

<script>
$(function() {
    // Kéo mảng Schema từ PHP sang JS
    var variantSchema = <?= json_encode($variant_schema, JSON_UNESCAPED_UNICODE) ?>;

    // Helper thông báo an toàn
    function fmNotify(message, type) {
        type = type || 'info';
        if (window.AdminUI && AdminUI.notify && AdminUI.notify.show) {
            AdminUI.notify.show(String(message), type);
        } else if (window.jQuery && $.notify) {
            $.notify(String(message), type === 'error' ? 'error' : 'success');
        } else {
            alert(message);
        }
    }
    
    var attrCount = <?= $attr_index ?>;
    var variantCount = <?= count($variants) ?>;
    var globalAttributes = <?php echo json_encode($attributes, JSON_UNESCAPED_UNICODE) ?: '[]'; ?>;

    // Hàm khởi tạo CKEditor an toàn (tránh double init)
    function initCKEditor(id) {
        if (typeof CKEDITOR === 'undefined') return;
        if (CKEDITOR.instances[id]) {
            CKEDITOR.instances[id].destroy(true);
        }
        CKEDITOR.replace(id, {
            toolbar: 'Basic',
            filebrowserBrowseUrl      : '/admin/ckfinder/ckfinder.html?type=Files',
            filebrowserImageBrowseUrl : '/admin/ckfinder/ckfinder.html?type=Images',
            filebrowserUploadUrl      : '/admin/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Files',
            filebrowserImageUploadUrl : '/admin/ckfinder/core/connector/php/connector.php?command=QuickUpload&type=Images',
            height: '150px'
        });
    }

    // Hàm cập nhật select thuộc tính
    function updateAttributeSelects() {
        var selectedAttrIds = [];
        $('.attr-select').each(function() {
            var val = $(this).find('option:selected').val() || $(this).val();
            if (val && val !== 'new') selectedAttrIds.push(val.toString());
        });

        $('.attr-select').each(function() {
            var $select = $(this);
            var currentVal = $select.find('option:selected').val() || $select.val() || '';
            if ($select.data('select2')) {
                $select.select2('destroy');
            }
            $select.empty().append('<option value="">Chọn thuộc tính</option>');

            globalAttributes.forEach(attr => {
                if (!selectedAttrIds.includes(attr.id_code.toString()) || attr.id_code.toString() === currentVal) {
                    var option = $('<option>').val(attr.id_code).text(attr.ten);
                    if (attr.id_code.toString() === currentVal) {
                        option.prop('selected', true);
                    }
                    $select.append(option);
                }
            });

            if (currentVal === 'new') {
                var newAttrName = $select.closest('.attribute-row').find('.new-attr-name').val();
                $select.append(`<option value="new" selected>Mới: ${newAttrName}</option>`);
            }

            $select.select2({
                placeholder: "Chọn thuộc tính",
                allowClear: true,
                width: '100%'
            });
        });

        // Cập nhật select toàn cục (chọn từ thuộc tính có sẵn)
        // Thuộc tính đã chọn sẽ bị disabled để tránh chọn trùng
        var $globalAttrSelect = $('#global-attr-select');
        $globalAttrSelect.find('option').each(function() {
            var $option = $(this);
            var attrIdStr = $option.val();
            if (!attrIdStr) return; // bỏ qua placeholder
            var isUsed = selectedAttrIds.includes(attrIdStr);
            $option.prop('disabled', isUsed);
            $option.text($option.text().replace(/ \(đã chọn\)$/, '') + (isUsed ? ' (đã chọn)' : ''));
        });
    }

    // Khởi tạo Select2
    $('.attr-select.select2').select2({
        placeholder: "Chọn thuộc tính",
        allowClear: true,
        width: '100%'
    });
    $('.values-select.select2').each(function() {
        var $this = $(this);
        var selectedValues = $this.closest('.attribute-row').data('selected-values') || [];
        $this.select2({
            placeholder: "Chọn giá trị",
            allowClear: true,
            width: '100%'
        }).val(selectedValues).trigger('change');
    });
    updateAttributeSelects();

    // Sortable cho thuộc tính
    $('#product-attributes').sortable({
        handle: '.drag-handle',
        axis: 'y',
        opacity: 0.8,
        placeholder: 'sortable-placeholder',
        start: function(event, ui) {
            ui.placeholder.height(ui.item.height());
            ui.item.find('.select2').each(function() {
                if ($(this).data('select2')) {
                    $(this).select2('close');
                }
            });
        },
        update: function(event, ui) {
            $('#product-attributes .attribute-row').each(function(i) {
                $(this).attr('data-index', i);
                $(this).find('.attr-select').attr('name', 'product_attributes[' + i + '][attr]');
                $(this).find('.values-select').attr('name', 'product_attributes[' + i + '][values][]');
                $(this).find('.is-new-attr').attr('name', 'product_attributes[' + i + '][is_new]');
                $(this).find('.new-attr-name').attr('name', 'product_attributes[' + i + '][new_attr_name]');
                $(this).find('.new-value-input').each(function() {
                    var $input = $(this);
                    var keyMatch = $input.attr('name').match(/\[new_values\]\[([^\]]+)\]$/);
                    var key = keyMatch ? keyMatch[1] : '0';
                    $input.attr('name', 'product_attributes[' + i + '][new_values][' + key + ']');
                });
            });
            updateAttributeSelects();
        }
    }).disableSelection();

    // Thêm thuộc tính toàn cục/custom
    $('#add-global-attr-btn').click(function() {
        var attrId = $('#global-attr-select').val();
        var newAttrName = $('#new-attr-name').val().trim();

        if (!attrId && !newAttrName) {
            fmNotify('Vui lòng chọn thuộc tính hoặc nhập tên thuộc tính mới.', 'error');
            return;
        }

        if (newAttrName) {
            var index = attrCount++;
            var $row = $(`
                <div class="attribute-row card mb-3" data-index="${index}" data-selected-values="[]">
                    <div class="card-header d-flex align-items-center gap-2 bg-light">
                        <span class="drag-handle" title="Kéo thả để sắp xếp">☰</span>
                        <div class="flex-grow-1">
                            <select name="product_attributes[${index}][attr]" class="form-select form-select-sm attr-select select2" style="width:100%">
                                <option value="">Chọn thuộc tính</option>
                                ${globalAttributes.map(attr => `<option value="${attr.id_code}">${attr.ten}</option>`).join('')}
                                <option value="new" selected>Mới: ${newAttrName}</option>
                            </select>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm remove-attr" title="Xóa thuộc tính">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label small text-muted">Giá trị đã chọn</label>
                            <select name="product_attributes[${index}][values][]" multiple class="form-select form-select-sm values-select select2" data-select2-id="values-${index}" style="width:100%"></select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small text-muted">Thêm giá trị mới</label>
                            <div class="input-group input-group-sm">
                                <input type="text" name="product_attributes[${index}][new_values][0]" class="form-control new-value-input" placeholder="Nhập giá trị mới">
                                <button type="button" class="btn btn-primary add-value-btn">
                                    <i class="fa-solid fa-plus me-1"></i> Thêm
                                </button>
                            </div>
                        </div>
                        <input type="hidden" name="product_attributes[${index}][is_new]" value="1" class="is-new-attr">
                        <input type="hidden" name="product_attributes[${index}][new_attr_name]" value="${newAttrName}" class="new-attr-name">
                    </div>
                </div>
            `);
            $('#product-attributes').append($row);
            $('#new-attr-name').val('');
            $row.find('.attr-select').select2({
                placeholder: "Chọn thuộc tính",
                allowClear: true,
                width: '100%'
            });
            $row.find('.values-select').select2({
                placeholder: "Chọn giá trị",
                allowClear: true,
                width: '100%'
            });
            updateAttributeSelects();
        } else {
            addAttributeRow(attrId);
            updateAttributeSelects();
        }
    });

    // Thêm attribute row
    function addAttributeRow(attrId) {
        var index = attrCount++;
        var options = globalAttributes.map(attr => {
            var selected = attr.id_code.toString() === attrId.toString() ? 'selected' : '';
            return `<option value="${attr.id_code}" ${selected}>${attr.ten}</option>`;
        }).join('');

        var $row = $(`
            <div class="attribute-row card mb-3" data-index="${index}" data-selected-values="[]">
                <div class="card-header d-flex align-items-center gap-2 bg-light">
                    <span class="drag-handle" title="Kéo thả để sắp xếp">☰</span>
                    <div class="flex-grow-1">
                        <select name="product_attributes[${index}][attr]" class="form-select form-select-sm attr-select select2" style="width:100%">
                            <option value="">Chọn thuộc tính</option>
                            ${options}
                        </select>
                    </div>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-attr" title="Xóa thuộc tính">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small text-muted">Giá trị đã chọn</label>
                        <select name="product_attributes[${index}][values][]" multiple class="form-select form-select-sm values-select select2" data-select2-id="values-${index}" style="width:100%"></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small text-muted">Thêm giá trị mới</label>
                        <div class="input-group input-group-sm">
                            <input type="text" name="product_attributes[${index}][new_values][0]" class="form-control new-value-input" placeholder="Nhập giá trị mới">
                            <button type="button" class="btn btn-primary add-value-btn">
                                <i class="fa-solid fa-plus me-1"></i> Thêm
                            </button>
                        </div>
                    </div>
                    <input type="hidden" name="product_attributes[${index}][is_new]" value="0" class="is-new-attr">
                </div>
            </div>
        `);
        $('#product-attributes').append($row);
        $row.find('.attr-select').select2({
            placeholder: "Chọn thuộc tính",
            allowClear: true,
            width: '100%'
        });
        $row.find('.values-select').select2({
            placeholder: "Chọn giá trị",
            allowClear: true,
            width: '100%'
        });

        if (attrId) {
            var $valuesSelect = $row.find('.values-select');
            var attr = globalAttributes.find(a => a.id_code == attrId);
            if (attr) {
                $.each(attr.gia_tri, function(_, val) {
                    $valuesSelect.append(`<option value="${val.id_code}">${val.ten}</option>`);
                });
                $valuesSelect.trigger('change');
            }
        }
    }

    // Thêm attribute row từ nút thêm thuộc tính
    $('#add-attribute-btn').click(function() {
        addAttributeRow('');
    });

    // Xóa attribute row
    $(document).on('click', '.remove-attr', function() {
        var $row = $(this).closest('.attribute-row');
        $row.find('.select2').each(function() {
            if ($(this).data('select2')) {
                $(this).select2('destroy');
            }
        });
        $row.remove();
        $('#product-attributes .attribute-row').each(function(i) {
            $(this).attr('data-index', i);
            $(this).find('.attr-select').attr('name', 'product_attributes[' + i + '][attr]');
            $(this).find('.values-select').attr('name', 'product_attributes[' + i + '][values][]');
            $(this).find('.is-new-attr').attr('name', 'product_attributes[' + i + '][is_new]');
            $(this).find('.new-attr-name').attr('name', 'product_attributes[' + i + '][new_attr_name]');
            $(this).find('.new-value-input').each(function() {
                var $input = $(this);
                var keyMatch = $input.attr('name').match(/\[new_values\]\[([^\]]+)\]$/);
                var key = keyMatch ? keyMatch[1] : '0';
                $input.attr('name', 'product_attributes[' + i + '][new_values][' + key + ']');
            });
        });
        updateAttributeSelects();

        // Tự động đồng bộ lại danh sách biến thể sau khi xóa thuộc tính
        if ($('#accordionVariants .variant-item').length > 0) {
            syncVariants();
        }
    });

    // Load values khi chọn attribute
    $(document).on('change', '.attr-select', function() {
        var $row = $(this).closest('.attribute-row');
        var attrId = $(this).val();
        var $valuesSelect = $row.find('.values-select');
        if ($valuesSelect.data('select2')) {
            $valuesSelect.select2('destroy');
        }
        $valuesSelect.empty();
        if (attrId && attrId !== 'new') {
            var attr = globalAttributes.find(a => a.id_code == attrId);
            if (attr) {
                var selectedValues = $row.data('selected-values') || [];
                $.each(attr.gia_tri, function(_, val) {
                    var isSelected = selectedValues.includes(val.id_code.toString()) ? 'selected' : '';
                    $valuesSelect.append(`<option value="${val.id_code}" ${isSelected}>${val.ten}</option>`);
                });
                $valuesSelect.val(selectedValues).trigger('change');
            }
        } else if (attrId === 'new') {
            var selectedValues = $row.data('selected-values') || [];
            selectedValues.forEach(function(val) {
                if (val.startsWith('new_')) {
                    var newValue = $row.find(`input[name*="[new_values]"][type="hidden"][value="${val.replace('new_', '')}"]`).val() || val.replace('new_', '');
                    $valuesSelect.append(`<option value="${val}" selected>${newValue}</option>`);
                }
            });
            $valuesSelect.trigger('change');
        }
        $valuesSelect.select2({
            placeholder: "Chọn giá trị",
            allowClear: true,
            width: '100%'
        });
        updateAttributeSelects();
    });

    // Thêm giá trị mới
    $(document).on('click', '.add-value-btn', function() {
        var $row = $(this).closest('.attribute-row');
        var attrId = $row.find('.attr-select').val();
        var newValue = $row.find('.new-value-input').first().val().trim();
        var $valuesSelect = $row.find('.values-select');
        var index = $row.data('index');

        if (!attrId) {
            fmNotify('Vui lòng chọn thuộc tính trước khi thêm giá trị.', 'error');
            return;
        }
        if (!newValue) {
            fmNotify('Vui lòng nhập giá trị mới.', 'error');
            return;
        }

        var tempId = 'new_' + md5(newValue);
        // Lưu tempId làm key để PHP ánh xạ sang ID thật khi lưu
        $row.append(`<input type="hidden" name="product_attributes[${index}][new_values][${tempId}]" value="${newValue}" class="new-value-input">`);
        $valuesSelect.append(`<option value="${tempId}" selected>${newValue}</option>`);
        $valuesSelect.trigger('change');
        $row.find('.new-value-input').first().val('');

        // Cập nhật select của các biến thể đã có để hiển thị giá trị mới
        if (attrId) {
            $('#accordionVariants .variant-item').find('select[name*="[attributes][' + attrId + ']"]').each(function() {
                var $select = $(this);
                if ($select.find('option[value="' + tempId + '"]').length === 0) {
                    $select.append(`<option value="${tempId}">${newValue}</option>`);
                }
            });
        }

        var selectedValues = $row.data('selected-values') || [];
        if (!selectedValues.includes(tempId)) {
            selectedValues.push(tempId);
            $row.data('selected-values', selectedValues);
        }
    });

    // Thêm biến thể thủ công
    $('#addVariantBtn').click(function() {
        var index = 'new' + variantCount++;
        var selectedAttrs = getSelectedAttributes();

        if (selectedAttrs.length === 0 || selectedAttrs.every(item => item.values.length === 0)) {
            fmNotify('Vui lòng chọn ít nhất một thuộc tính và giá trị.', 'error');
            $('#no-variants-message').show();
            return;
        }

        var $panel = createVariantPanel(index, selectedAttrs);
        $('#accordionVariants').append($panel);
        $('#no-variants-message').hide();
    });

    // Xóa biến thể
    $(document).on('click', '.remove-variant', function(e) {
        e.stopPropagation();
        if (!confirm('Bạn có chắc chắn muốn xóa biến thể này?')) {
            return;
        }
        var $panel = $(this).closest('.variant-item');
        $panel.remove();
        if ($('#accordionVariants .variant-item').length === 0) {
            $('#no-variants-message').show();
        }
    });

    // Xóa tất cả biến thể
    $('#deleteAllVariantsBtn').click(function() {
        if (confirm('Bạn có chắc chắn muốn xóa tất cả biến thể?')) {
            $('#accordionVariants').empty();
            variantCount = 0;
            $('#no-variants-message').show();
        }
    });

    // Áp dụng bulk (TỰ ĐỘNG QUÉT THEO SCHEMA)
    $('#applyBulkBtn').click(function() {
        var updates = {};
        var hasUpdate = false;

        // 1. Quét qua Schema để lấy các giá trị admin vừa nhập ở form Nhập chung
        variantSchema.forEach(function(field) {
            if (field.bulk !== false) {
                var val = $('#bulk_' + field.name).val();
                if (val !== undefined && val.trim() !== '') {
                    updates[field.name] = { value: val.trim(), type: field.bulk };
                    hasUpdate = true;
                }
            }
        });

        if (!hasUpdate) {
            fmNotify('Vui lòng nhập ít nhất một giá trị để áp dụng hàng loạt.', 'error');
            return;
        }

        if(!confirm('Hành động này sẽ ghi đè dữ liệu của tất cả các biến thể bên dưới. Bạn có chắc chắn?')) return;

        // 2. Chạy vòng lặp ghi đè vào từng thẻ input/textarea của biến thể
        $('.variant-item').each(function(index) {
            var variantId = $(this).data('id');
            var $panelBody = $(this).find('.accordion-body');
            
            for (var key in updates) {
                var fieldUpdate = updates[key];
                var targetName = `variants[${variantId}][${key}]`;
                var $targetInput = $panelBody.find(`[name="${targetName}"]`);
                
                if ($targetInput.length > 0) {
                    if (fieldUpdate.type === 'prefix') {
                        // Nếu là dạng prefix (như SKU), tự động cộng đuôi -1, -2...
                        $targetInput.val(fieldUpdate.value + '-' + (index + 1));
                    } else {
                        // Nếu là copy y hệt (như Giá, Số lượng)
                        $targetInput.val(fieldUpdate.value);
                    }
                }
            }
        });
        
        // (Tuỳ chọn) Tự động làm sạch form nhập chung sau khi bấm áp dụng
        $('[id^="bulk_"]').val('');
        fmNotify("Đã áp dụng thành công!", "success");
    });

    // Tạo/Sync biến thể tự động — giữ lại biến thể đã có, chỉ thêm/bớt theo tổ hợp thuộc tính
    $('#generateVariantsBtn').click(function() {
        var $btn = $(this);
        var originalHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-1"></i>Đang đồng bộ...');

        var selectedAttrs = getSelectedAttributes();

        if (selectedAttrs.length === 0 || selectedAttrs.every(item => item.values.length === 0)) {
            $('#no-variants-message').show();
            $btn.prop('disabled', false).html(originalHtml);
            return;
        }

        setTimeout(function() {
            syncVariants(selectedAttrs);
            $btn.prop('disabled', false).html(originalHtml);
        }, 10);
    });

    // Đồng bộ danh sách biến thể với thuộc tính hiện tại (giữ nguyên dữ liệu cũ)
    function syncVariants(selectedAttrs) {
        if (!selectedAttrs) {
            selectedAttrs = getSelectedAttributes();
        }
        if (selectedAttrs.length === 0 || selectedAttrs.every(item => item.values.length === 0)) {
            return;
        }

        // Tập hợp ID thuộc tính hiện tại
        var currentAttrIds = {};
        selectedAttrs.forEach(function(item) {
            currentAttrIds[item.attr] = true;
        });

        // Tạo tất cả tổ hợp cần có: mỗi phần tử là {attr_id, val_id}
        var valuesArray = selectedAttrs.map(function(item) {
            return item.values.map(function(id) {
                return { attr_id: item.attr, val_id: id };
            });
        });
        var combinations = cartesian(valuesArray);

        // Danh sách tổ hợp cần có
        var desiredKeys = new Set();
        combinations.forEach(function(comboItems) {
            var combo = {};
            comboItems.forEach(function(item) {
                combo[item.attr_id] = item.val_id;
            });
            desiredKeys.add(comboKey(combo));
        });

        // Map biến thể hiện có theo tổ hợp thuộc tính CÒN HIỆN TẠI (bỏ qua thuộc tính đã xóa)
        var existingMap = {};
        var keptKeys = new Set();

        $('#accordionVariants .variant-item').each(function() {
            var $item = $(this);
            var reducedCombo = {};
            var allAttrIds = [];

            $item.find('select[name*="[attributes]["]').each(function() {
                var $select = $(this);
                var name = $select.attr('name') || '';
                var match = name.match(/\[attributes\]\[([^\]]+)\]/);
                if (match) {
                    var attrId = match[1];
                    allAttrIds.push(attrId);
                    if (currentAttrIds[attrId]) {
                        reducedCombo[attrId] = $select.val();
                    }
                }
            });

            var key = comboKey(reducedCombo);

            // Nếu tổ hợp còn lại khớp với tổ hợp cần có và chưa có biến thể nào giữ key này
            if (desiredKeys.has(key) && !keptKeys.has(key)) {
                keptKeys.add(key);
                existingMap[key] = { el: $item, allAttrIds: allAttrIds };
            } else {
                // Trùng lặp hoặc không còn phù hợp
                $item.remove();
            }
        });

        // Xóa select của thuộc tính đã bị xóa ở các biến thể được giữ lại
        Object.keys(existingMap).forEach(function(key) {
            var item = existingMap[key];
            item.allAttrIds.forEach(function(attrId) {
                if (!currentAttrIds[attrId]) {
                    item.el.find('select[name*="[attributes][' + attrId + ']"]').remove();
                }
            });
        });

        // Tạo biến thể mới cho các tổ hợp chưa có
        var newCombos = [];
        combinations.forEach(function(comboItems) {
            var combo = {};
            comboItems.forEach(function(item) {
                combo[item.attr_id] = item.val_id;
            });
            var key = comboKey(combo);
            if (!keptKeys.has(key)) {
                newCombos.push(comboItems);
            }
        });

        // Cảnh báo nếu sắp tạo quá nhiều biến thể mới
        if (newCombos.length > 50) {
            if (!confirm('Bạn sắp tạo thêm ' + newCombos.length + ' biến thể. Tiếp tục?')) {
                return;
            }
        }

        newCombos.forEach(function(comboItems) {
            variantCount++;
            var variantId = 'new' + variantCount;
            var $panel = createVariantPanel(variantId, selectedAttrs, comboItems);
            $('#accordionVariants').append($panel);
        });

        // Cập nhật options cho các biến thể cũ để hiển thị đủ giá trị mới
        selectedAttrs.forEach(function(item) {
            var attrId = item.attr;
            var valueTexts = item.value_texts || {};
            $('#accordionVariants .variant-item').find('select[name*="[attributes][' + attrId + ']"]').each(function() {
                var $select = $(this);
                Object.keys(valueTexts).forEach(function(valId) {
                    if ($select.find('option[value="' + valId + '"]').length === 0) {
                        $select.append(`<option value="${valId}">${valueTexts[valId]}</option>`);
                    }
                });
            });
        });

        if ($('#accordionVariants .variant-item').length === 0) {
            $('#no-variants-message').show();
        } else {
            $('#no-variants-message').hide();
        }
    }

    // Tạo key duy nhất cho một tổ hợp thuộc tính
    function comboKey(combo) {
        return Object.keys(combo).sort().map(function(k) {
            return k + ':' + combo[k];
        }).join('|');
    }

    // Hàm lấy selected attributes (bao gồm cả text của giá trị tạm)
    function getSelectedAttributes() {
        var selectedAttrs = [];
        $('.attribute-row').each(function() {
            var $row = $(this);
            var attrId = $row.find('.attr-select').val();
            var $valuesSelect = $row.find('.values-select');
            var values = $valuesSelect.val() || [];
            var newValues = $row.find('input[name*="[new_values]"][type="hidden"]').map(function() { return $(this).val(); }).get();
            if (attrId && (values.length > 0 || newValues.length > 0)) {
                // Lưu lại text hiển thị của từng option (quan trọng cho giá trị mới tạo)
                var valueTexts = {};
                $valuesSelect.find('option').each(function() {
                    valueTexts[$(this).val()] = $(this).text();
                });
                var item = {attr: attrId, values: values, value_texts: valueTexts};
                if (attrId === 'new') {
                    item.new_values = newValues;
                }
                selectedAttrs.push(item);
            }
        });
        return selectedAttrs;
    }

    // Hàm tạo panel biến thể (RENDER BẰNG SCHEMA TỪ JS KÈM FILEMANAGER)
    // Hàm tạo panel biến thể (RENDER BẰNG SCHEMA TỪ JS)
    function createVariantPanel(variantId, selectedAttrs, combo = null) {
        var panelId = 'collapse' + variantId;
        var headingId = 'heading' + variantId;
        
        var inputsHtml = '';
        var editorIds = []; // Mảng lưu trữ ID các editor để kích hoạt sau khi vẽ HTML
        
        // 1. Quét qua Schema
        variantSchema.forEach(function(field) {
            var colClass = (field.type === 'textarea' || field.type === 'editor') ? 'col-12' : 'col-6 col-md-4 col-lg-3';
            inputsHtml += `<div class="mb-3 ${colClass}"><label class="form-label small">${field.label}</label>`;

            if (field.type === 'textarea') {
                inputsHtml += `<textarea name="variants[${variantId}][${field.name}]" class="form-control form-control-sm" rows="3"></textarea>`;

            } else if (field.type === 'editor') {
                var editorId = `editor_${variantId}_${field.name}`;
                inputsHtml += `<textarea name="variants[${variantId}][${field.name}]" id="${editorId}" class="form-control form-control-sm" rows="3"></textarea>`;
                editorIds.push(editorId); // Lưu ID lại

            } else if (field.type === 'image') {
                var fieldId = `${field.name}_variant_${variantId}`;
                inputsHtml += `
                    <span class="variant-img-box d-block">
                        <img src="img/no-image.png" id="review_${fieldId}" class="variant-preview-img mb-2" alt="NO PHOTO" style="max-width:120px;max-height:120px;" />
                        <input type="hidden" name="variants[${variantId}][${field.name}]" id="${fieldId}" value="" class="form-control form-control-sm" data-preview="image">
                        <a href="admin/ckfinder/ckfinder.html?type=Images" class="btn btn-sm btn-info iframe-btn js-fm-open mt-2 d-inline-block"
                           data-fm-field="${fieldId}"
                           data-fm-type="image"
                           data-fm-resource="Images"
                           data-fm-multiple="0">
                            <i class="fa-solid fa-upload" aria-hidden="true"></i> Chọn ${field.label}
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-danger mt-2 d-inline-block js-clear-variant-img" data-target="${fieldId}" title="Xóa hình">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </span>
                `;
            } else {
                var extraAttrs = '';
                if (field.min !== undefined) extraAttrs += ` min="${field.min}"`;
                if (field.step !== undefined) extraAttrs += ` step="${field.step}"`;
                inputsHtml += `<input type="${field.type}" name="variants[${variantId}][${field.name}]" class="form-control form-control-sm" ${extraAttrs}>`;
            }

            inputsHtml += `</div>`;
        });

        // 2. Tạo HTML cho Panel
        var $panel = $(`
            <div class="accordion-item variant-item" data-id="${variantId}">
                <div class="accordion-header d-flex align-items-center gap-2 p-2" id="${headingId}">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#${panelId}" aria-expanded="false" aria-controls="${panelId}">
                        Biến thể #${variantCount}
                    </button>
                    <div class="variant-attrs flex-grow-1 ms-2"></div>
                    <button type="button" class="btn btn-danger btn-sm btn-remove-variant remove-variant" data-id="${variantId}" title="Xóa biến thể"><i class="fa-solid fa-trash"></i></button>
                </div>
                <div id="${panelId}" class="accordion-collapse collapse" data-bs-parent="#accordionVariants">
                    <div class="accordion-body">
                        <div class="row g-3">
                            ${inputsHtml}
                        </div>
                    </div>
                </div>
            </div>
        `);

        // ... Đoạn code xử lý selectedAttrs (Option dropdown) giữ nguyên ...
        var $link = $panel.find('.variant-attrs');
        selectedAttrs.forEach((item, idx) => {
            var attrId = item.attr;
            var attrValues = item.values;
            var newValues = item.new_values || [];
            var valueTexts = item.value_texts || {};
            var attr = globalAttributes.find(a => a.id_code == attrId);
            var options = '';

            attrValues.forEach(valId => {
                var val = attr ? attr.gia_tri.find(v => v.id_code == valId) : null;
                if (val) {
                    var isSelected = combo && combo[idx].val_id == valId ? 'selected' : '';
                    options += `<option value="${val.id_code}" ${isSelected}>${val.ten}</option>`;
                } else if (valueTexts[valId]) {
                    // Giá trị mới thêm (tempId) cho thuộc tính cũ hoặc thuộc tính mới
                    var isSelected = combo && combo[idx].val_id == valId ? 'selected' : '';
                    options += `<option value="${valId}" ${isSelected}>${valueTexts[valId]}</option>`;
                }
            });

            var attrName = attr ? attr.ten : (item.new_attr_name || 'Thuộc tính mới');
            var $select = $(`
                <select name="variants[${variantId}][attributes][${attrId}]" class="form-control form-control-sm me-2" required>
                    <option value="">Chọn ${attrName}</option>
                    ${options}
                </select>
            `);
            $link.append($select);
        });

        // 3. Kích hoạt CKEditor khi panel mở lần đầu (tránh init trong collapsed panel)
        if (editorIds.length > 0) {
            var editorInited = false;
            $panel.one('shown.bs.collapse', function() {
                if (editorInited) return;
                editorInited = true;
                editorIds.forEach(function(id) {
                    initCKEditor(id);
                });
            });
        }

        return $panel;
    }

    // Hàm cartesian
    function cartesian(arrays) {
        return arrays.reduce((acc, curr) => {
            let result = [];
            acc.forEach(a => {
                curr.forEach(b => {
                    result.push(a.concat([b]));
                });
            });
            return result;
        }, [[]]);
    }

    // Hàm md5 (Đã fix lỗi Unicode bằng encodeURIComponent)
    function md5(str) {
        return btoa(encodeURIComponent(str)).substring(0, 15).replace(/[^a-zA-Z0-9]/g, '');
    }

    // Sortable kéo thả cho biến thể
    $('#accordionVariants').sortable({
        handle: '.accordion-button',
        axis: 'y',
        opacity: 0.8,
        placeholder: 'sortable-placeholder',
        start: function(event, ui) {
            ui.placeholder.height(ui.item.height());
        },
        update: function(event, ui) {}
    });

    // Hiển thị thông báo nếu không có biến thể
    if ($('#accordionVariants .variant-item').length === 0) {
        $('#no-variants-message').show();
    }
    
    // Xử lý nút xóa ảnh biến thể
    $(document).on('click', '.js-clear-variant-img', function() {
        let targetId = $(this).data('target');
        $('#' + targetId).val(''); // Xóa giá trị input hidden
        $('#review_' + targetId).attr('src', 'img/no-image.png'); // Reset hình ảnh hiển thị
    });

    // Khởi tạo CKEditor cho các textarea tĩnh (PHP render)
    $('.ckeditor-init').each(function() {
        initCKEditor($(this).attr('id'));
    });

    // Global attribute select dùng dropdown thuần (bỏ Select2 để tránh lỗi render)
});
</script>
                            </div><!-- /tab-content -->
                        </div><!-- /col-lg-9 -->
                    </div><!-- /row -->
                </div><!-- /card-body -->
            </div><!-- /card -->

