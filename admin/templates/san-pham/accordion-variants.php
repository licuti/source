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
    ['name' => 'weight',   'label' => 'Cân nặng (' . ($config['weight']['unit'] ?? 'g') . ')', 'type' => 'number', 'min' => 0, 'bulk' => true],
    
    // bulk = false (Ẩn đi khỏi form Nhập chung)
    ['name' => 'hinh_anh', 'label' => 'Hình ảnh',  'type' => 'image',  'bulk' => false], 
    ['name' => 'mo_ta',    'label' => 'Mô tả ngắn','type' => 'editor', 'bulk' => false], 
];


// Lấy danh sách thuộc tính và giá trị global + custom
$id_sp = !empty($_GET['id']) ? intval($_GET['id']) : 0;
$attributes = $d->o_fet("SELECT * FROM #_thuoctinh WHERE (id_sanpham = 0 OR id_sanpham = $id_sp) AND lang='".LANG."' ORDER BY id ASC");
foreach ($attributes as &$attribute) {
    $values = $d->o_fet("SELECT * FROM #_thuoctinh_giatri WHERE id_thuoctinh = {$attribute['id_code']} AND (id_sanpham = 0 OR id_sanpham = $id_sp) AND lang='".LANG."' ORDER BY id ASC");
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
        $attrs = $d->o_fet("SELECT id_thuoctinh_giatri FROM #_sanpham_bienthe_thuoctinh WHERE id_bienthe = {$variant['id']}");
        $variant['thuoc_tinh'] = array_column($attrs, 'id_thuoctinh_giatri');
    }
    unset($variant);
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

        $html .= '<select name="variants['.$variantId.'][attributes]['.$attr_code.']" class="form-control" style="display:inline-block; width:auto; margin-left:10px;" required>';
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

<form id="product-form" method="POST" enctype="multipart/form-data">

<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.2/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<h3>Quản lý Thuộc tính và Biến thể sản phẩm</h3>

<ul class="nav nav-tabs" role="tablist" style="margin-bottom: 15px;">
   <li role="presentation" class="active"><a href="#tab-thuoctinh" aria-controls="tab-thuoctinh" role="tab" data-toggle="tab">Thiết lập thuộc tính</a></li>
   <li role="presentation"><a href="#tab-nhapchung" aria-controls="tab-nhapchung" role="tab" data-toggle="tab">Quản lý biến thể</a></li>
</ul>

<div class="tab-content">
   <!-- Tab 1: Thuộc tính sản phẩm -->
   <div role="tabpanel" class="tab-pane active" id="tab-thuoctinh">
        <div class="box box-primary">
            <div class="box-body">
                <div class="form-group" style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px dashed #ddd;">
                    <label>Quản lý thuộc tính (Chọn hoặc thêm thuộc tính mới)</label>
                    <div class="input-group">
                        <select id="global-attr-select" class="form-control select2" style="width: 70%;">
                            <option value="">Chọn thuộc tính có sẵn</option>
                            <?php foreach ($attributes as $global_attr): ?>
                                <option value="<?= $global_attr['id_code'] ?>"><?= htmlspecialchars($global_attr['ten']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" id="new-attr-name" class="form-control" style="width: 30%; margin-left: 10px;" placeholder="Tên thuộc tính mới">
                        <button type="button" id="add-global-attr-btn" class="btn btn-primary" style="margin-left: 10px;">+ Thêm</button>
                    </div>
                </div>
                
                <div style="margin-bottom: 10px;"><strong>Danh sách thuộc tính của sản phẩm:</strong></div>
        <div id="product-attributes">
            <?php 
            $attr_index = 0;
            if (empty($product_attributes)) {
                echo '<div class="attribute-row" data-index="0" data-selected-values="[]">
                    <span class="drag-handle" style="cursor: move; padding: 10px; display: inline-block; vertical-align: middle;">☰</span>
                    <select name="product_attributes[0][attr]" class="form-control attr-select select2" style="width: 30%; display: inline-block;">
                        <option value="">Chọn thuộc tính</option>';
                foreach ($attributes as $global_attr) {
                    echo '<option value="'.$global_attr['id_code'].'">'.htmlspecialchars($global_attr['ten']).'</option>';
                }
                echo '</select>
                    <select name="product_attributes[0][values][]" multiple class="form-control values-select select2" style="width: 40%; display: inline-block;" data-select2-id="values-0"></select>
                    <input type="text" name="product_attributes[0][new_values][]" class="form-control new-value-input" style="width: 20%; display: inline-block; margin-left: 10px;" placeholder="Giá trị mới">
                    <input type="hidden" name="product_attributes[0][is_new]" value="0" class="is-new-attr">
                    <button type="button" class="btn btn-primary add-value-btn" style="margin-left: 10px;">+ Thêm giá trị</button>
                    <button type="button" class="btn btn-danger remove-attr" style="margin-left: 10px;">Xóa</button>
                </div>';
                $attr_index = 1;
            } else {
                foreach ($product_attributes as $item) {
                    $attr_code = $item['attr'];
                    $selected_values = $item['values'];
                    $attr = array_filter($attributes, function($a) use ($attr_code) { return (string)$a['id_code'] === (string)$attr_code; });
                    $attr = reset($attr);
                    echo '<div class="attribute-row" data-index="'.$attr_index.'" data-selected-values=\''.htmlspecialchars(json_encode($selected_values, JSON_UNESCAPED_UNICODE)).'\'>
                        <span class="drag-handle" style="cursor: move; padding: 10px; display: inline-block; vertical-align: middle;">☰</span>
                        <select name="product_attributes['.$attr_index.'][attr]" class="form-control attr-select select2" style="width: 30%; display: inline-block;">
                            <option value="">Chọn thuộc tính</option>';
                    foreach ($attributes as $global_attr) {
                        $selected = ((string)$attr_code === (string)$global_attr['id_code']) ? 'selected' : '';
                        echo '<option value="'.$global_attr['id_code'].'" '.$selected.'>'.htmlspecialchars($global_attr['ten']).'</option>';
                    }
                    if ($item['is_new'] ?? false) {
                        echo '<option value="new" selected>Mới: '.htmlspecialchars($item['new_attr_name'] ?? '').'</option>';
                    }
                    echo '</select>
                        <select name="product_attributes['.$attr_index.'][values][]" multiple class="form-control values-select select2" style="width: 40%; display: inline-block;" data-select2-id="values-'.$attr_index.'">';
                    if ($attr) {
                        foreach ($attr['gia_tri'] as $value) {
                            $selected = in_array((string)$value['id_code'], array_map('strval', (array)$selected_values)) ? 'selected' : '';
                            echo '<option value="'.$value['id_code'].'" '.$selected.'>'.htmlspecialchars($value['ten']).'</option>';
                        }
                    }
                    if (isset($item['new_values']) && is_array($item['new_values'])) {
                        foreach ($item['new_values'] as $new_value) {
                            $temp_id = 'new_'.substr(preg_replace('/[^a-zA-Z0-9]/', '', base64_encode(rawurlencode($new_value))), 0, 15);
                            $selected = in_array($temp_id, (array)$selected_values) ? 'selected' : '';
                            echo '<option value="'.$temp_id.'" '.$selected.'>'.htmlspecialchars($new_value).'</option>';
                        }
                    }
                    echo '</select>
                        <input type="text" name="product_attributes['.$attr_index.'][new_values][]" class="form-control new-value-input" style="width: 20%; display: inline-block; margin-left: 10px;" placeholder="Giá trị mới">
                        <input type="hidden" name="product_attributes['.$attr_index.'][is_new]" value="'.($item['is_new'] ?? 0).'" class="is-new-attr">';
                    if ($item['is_new'] ?? false) {
                        echo '<input type="hidden" name="product_attributes['.$attr_index.'][new_attr_name]" value="'.htmlspecialchars($item['new_attr_name'] ?? '').'" class="new-attr-name">';
                    }
                    echo '<button type="button" class="btn btn-primary add-value-btn" style="margin-left: 10px;">+ Thêm giá trị</button>
                        <button type="button" class="btn btn-danger remove-attr" style="margin-left: 10px;">Xóa</button>
                    </div>';
                    $attr_index++;
                }
            }
            ?>
        </div>
        <button type="button" id="add-attribute-btn" class="btn btn-primary mt-2">+ Thêm thuộc tính</button>
            </div>
        </div>
   </div>

   <!-- Tab 2: Nhập chung cho tất cả biến thể -->
   <div role="tabpanel" class="tab-pane" id="tab-nhapchung">
        <div class="box box-primary">
            <div class="box-body">
        <div class="row">
            <?php foreach ($variant_schema as $field): ?>
                <?php if (isset($field['bulk']) && $field['bulk'] !== false): ?>
                    <div class="col-sm-3 form-group">
                        <label><?= $field['label'] ?> <?= $field['bulk'] === 'prefix' ? '<small>(Hậu tố tự động)</small>' : '' ?></label>
                        <?php if ($field['type'] == 'textarea'): ?>
                            <textarea id="bulk_<?= $field['name'] ?>" class="form-control" rows="2"></textarea>
                        <?php else: ?>
                            <input type="<?= $field['type'] == 'number' ? 'number' : 'text' ?>" id="bulk_<?= $field['name'] ?>" class="form-control" <?= isset($field['min']) ? 'min="'.$field['min'].'"' : '' ?> <?= isset($field['step']) ? 'step="'.$field['step'].'"' : '' ?>>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <button type="button" id="applyBulkBtn" class="btn btn-info mt-2"><i class="fa fa-bolt"></i> Áp dụng cho tất cả</button>
            </div>
        </div>

<div class="panel-group" id="accordionVariants" role="tablist">
    <div id="no-variants-message" style="display: none; text-align: center; color: red; margin: 10px 0;">Chưa có thuộc tính hoặc giá trị nào được chọn. Vui lòng thêm thuộc tính trước khi tạo biến thể.</div>
    <?php foreach ($variants as $i => $variant): 
        $panelId = 'collapse'.$variant['id'];
        $headingId = 'heading'.$variant['id'];
    ?>
    <div class="panel panel-default variant-item" data-id="<?= $variant['id'] ?>">
        <div class="panel-heading" role="tab" id="<?= $headingId ?>">
            <h5 class="panel-title">
                <a role="button" data-toggle="collapse" class="accordion-plus-toggle collapsed" data-parent="#accordionVariants" href="#<?= $panelId ?>">
                    Biến thể #<?= $i + 1 ?>
                </a>
                <?= renderAttributeSelects($variant['id'], $product_attributes, $variant['thuoc_tinh']) ?>
                <button type="button" class="btn btn-danger btn-xs pull-right remove-variant" data-id="<?= $variant['id'] ?>">&times;</button>
            </h5>
        </div>
        <div id="<?= $panelId ?>" class="panel-collapse collapse" role="tabpanel">
            <div class="panel-body">
                <input type="hidden" name="variants[<?= $variant['id'] ?>][id]" value="<?= $variant['id'] ?>">
                
                <?php foreach ($variant_schema as $field): ?>
                    <div class="form-group">
                        <label><?= $field['label'] ?></label>
                        
                        <?php if ($field['type'] == 'textarea'): ?>
                            <textarea name="variants[<?= $variant['id'] ?>][<?= $field['name'] ?>]" class="form-control" rows="3"><?= htmlspecialchars($variant[$field['name']] ?? '') ?></textarea>
                        
                        <?php elseif ($field['type'] == 'editor'): ?>
                            <?php $editor_id = "editor_" . $variant['id'] . "_" . $field['name']; ?>
                            <textarea name="variants[<?= $variant['id'] ?>][<?= $field['name'] ?>]" id="<?= $editor_id ?>" class="form-control" rows="3"><?= htmlspecialchars($variant[$field['name']] ?? '') ?></textarea>
                            <script>
                                // Kích hoạt CKEditor cho PHP render
                                if (typeof CKEDITOR !== 'undefined') {
                                    CKEDITOR.replace('<?= $editor_id ?>', {
                                        filebrowserBrowseUrl : 'filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
                                        filebrowserUploadUrl : 'filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
                                        filebrowserImageBrowseUrl : 'filemanager/dialog.php?type=1&editor=ckeditor&fldr=',
                                        height: '150px' // Chiều cao nhỏ gọn cho biến thể
                                    });
                                }
                            </script>

                        <?php elseif ($field['type'] == 'image'): ?>
                            <?php $field_id = $field['name'] . "_variant_" . $variant['id']; ?>
                            <span class="box-img2" style="display: block; margin-top: 5px;">
                                <?php if (!empty($variant[$field['name']])): ?>
                                    <img src="../img_data/images/<?= htmlspecialchars($variant[$field['name']]) ?>" id="review_<?= $field_id ?>" style="max-height: 100px; display: block; margin-bottom: 10px; border: 1px solid #ddd; padding: 2px; object-fit: contain;" alt="NO PHOTO" />
                                <?php else: ?>
                                    <img src="img/no-image.png" id="review_<?= $field_id ?>" style="max-height: 100px; display: block; margin-bottom: 10px; border: 1px solid #ddd; padding: 2px; object-fit: contain;" alt="NO PHOTO" />
                                <?php endif; ?>
                                <input type="hidden" name="variants[<?= $variant['id'] ?>][<?= $field['name'] ?>]" id="<?= $field_id ?>" value="<?= htmlspecialchars($variant[$field['name']] ?? '') ?>" class="form-control">
                                <a href="filemanager/dialog.php?type=1&field_id=<?= $field_id ?>&relative_url=1&multiple=0" class="btn btn-sm btn-info iframe-btn" style="margin-top: 5px; display: inline-block;"> 
                                    <i class="fa fa-upload" aria-hidden="true"></i> Chọn <?= $field['label'] ?>
                                </a>
                            </span>

                        <?php else: ?>
                            <input type="<?= $field['type'] ?>" 
                                   name="variants[<?= $variant['id'] ?>][<?= $field['name'] ?>]" 
                                   class="form-control" 
                                   <?= isset($field['min']) ? 'min="'.$field['min'].'"' : '' ?>
                                   <?= isset($field['step']) ? 'step="'.$field['step'].'"' : '' ?>
                                   value="<?= htmlspecialchars($variant[$field['name']] ?? '') ?>">
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<button type="button" id="addVariantBtn" class="btn btn-primary mt-2">+ Thêm biến thể</button>
<button type="button" id="generateVariantsBtn" class="btn btn-success mt-2">Tạo biến thể tự động</button>
<button type="button" id="deleteAllVariantsBtn" class="btn btn-danger mt-2">Xóa tất cả biến thể</button>

   </div>
</div>

</form>

<style>
.sortable-placeholder {
    border: 2px dashed #ccc;
    height: 50px;
    margin: 5px 0;
    background-color: #f9f9f9;
}
.attribute-row { 
    margin-bottom: 10px; 
    padding: 10px; 
    background: #fff; 
    border: 1px solid #ddd; 
    border-radius: 4px; 
}
.attribute-row:hover { 
    background: #f5f5f5; 
}
.drag-handle {
    font-size: 16px;
    color: #666;
    cursor: move;
}
.mt-2 { margin-top: 10px; }
.select2-container { width: auto !important; }
</style>

<script>
$(function() {
    // Kéo mảng Schema từ PHP sang JS
    var variantSchema = <?= json_encode($variant_schema, JSON_UNESCAPED_UNICODE) ?>;
    
    var attrCount = <?= $attr_index ?>;
    var variantCount = <?= count($variants) ?>;
    var globalAttributes = <?php echo json_encode($attributes, JSON_UNESCAPED_UNICODE) ?: '[]'; ?>;

    // Hàm cập nhật select thuộc tính
    function updateAttributeSelects() {
        var selectedAttrIds = [];
        $('.attr-select').each(function() {
            var val = $(this).val();
            if (val && val !== 'new') selectedAttrIds.push(val);
        });

        $('.attr-select').each(function() {
            var $select = $(this);
            var currentVal = $select.val();
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
                allowClear: true
            });
        });

        var $globalAttrSelect = $('#global-attr-select');
        if ($globalAttrSelect.data('select2')) {
            $globalAttrSelect.select2('destroy');
        }
        $globalAttrSelect.empty().append('<option value="">Chọn thuộc tính có sẵn</option>');
        globalAttributes.forEach(attr => {
            $globalAttrSelect.append(`<option value="${attr.id_code}">${attr.ten}</option>`);
        });
        $globalAttrSelect.select2({
            placeholder: "Chọn thuộc tính có sẵn",
            allowClear: true
        });
    }

    // Khởi tạo Select2
    $('.attr-select.select2').select2({
        placeholder: "Chọn thuộc tính",
        allowClear: true
    });
    $('.values-select.select2').each(function() {
        var $this = $(this);
        var selectedValues = $this.closest('.attribute-row').data('selected-values') || [];
        $this.select2({
            placeholder: "Chọn giá trị",
            allowClear: true
        }).val(selectedValues).trigger('change');
    });
    $('#global-attr-select').select2({
        placeholder: "Chọn thuộc tính có sẵn",
        allowClear: true
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
                $(this).find('.new-value-input').each(function(j) {
                    $(this).attr('name', 'product_attributes[' + i + '][new_values][' + j + ']');
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
            alert('Vui lòng chọn thuộc tính hoặc nhập tên thuộc tính mới.');
            return;
        }

        if (newAttrName) {
            var index = attrCount++;
            var $row = $(`
                <div class="attribute-row" data-index="${index}" data-selected-values="[]">
                    <span class="drag-handle" style="cursor: move; padding: 10px; display: inline-block; vertical-align: middle;">☰</span>
                    <select name="product_attributes[${index}][attr]" class="form-control attr-select select2" style="width: 30%; display: inline-block;">
                        <option value="">Chọn thuộc tính</option>
                        ${globalAttributes.map(attr => `<option value="${attr.id_code}">${attr.ten}</option>`).join('')}
                        <option value="new" selected>Mới: ${newAttrName}</option>
                    </select>
                    <input type="hidden" name="product_attributes[${index}][is_new]" value="1" class="is-new-attr">
                    <input type="hidden" name="product_attributes[${index}][new_attr_name]" value="${newAttrName}" class="new-attr-name">
                    <select name="product_attributes[${index}][values][]" multiple class="form-control values-select select2" style="width: 40%; display: inline-block;" data-select2-id="values-${index}"></select>
                    <input type="text" name="product_attributes[${index}][new_values][0]" class="form-control new-value-input" style="width: 20%; display: inline-block; margin-left: 10px;" placeholder="Giá trị mới">
                    <button type="button" class="btn btn-primary add-value-btn" style="margin-left: 10px;">+ Thêm giá trị</button>
                    <button type="button" class="btn btn-danger remove-attr" style="margin-left: 10px;">Xóa</button>
                </div>
            `);
            $('#product-attributes').append($row);
            $('#new-attr-name').val('');
            $row.find('.attr-select').select2({
                placeholder: "Chọn thuộc tính",
                allowClear: true
            });
            $row.find('.values-select').select2({
                placeholder: "Chọn giá trị",
                allowClear: true
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
            <div class="attribute-row" data-index="${index}" data-selected-values="[]">
                <span class="drag-handle" style="cursor: move; padding: 10px; display: inline-block; vertical-align: middle;">☰</span>
                <select name="product_attributes[${index}][attr]" class="form-control attr-select select2" style="width: 30%; display: inline-block;">
                    <option value="">Chọn thuộc tính</option>
                    ${options}
                </select>
                <input type="hidden" name="product_attributes[${index}][is_new]" value="0" class="is-new-attr">
                <select name="product_attributes[${index}][values][]" multiple class="form-control values-select select2" style="width: 40%; display: inline-block;" data-select2-id="values-${index}"></select>
                <input type="text" name="product_attributes[${index}][new_values][0]" class="form-control new-value-input" style="width: 20%; display: inline-block; margin-left: 10px;" placeholder="Giá trị mới">
                <button type="button" class="btn btn-primary add-value-btn" style="margin-left: 10px;">+ Thêm giá trị</button>
                <button type="button" class="btn btn-danger remove-attr" style="margin-left: 10px;">Xóa</button>
            </div>
        `);
        $('#product-attributes').append($row);
        $row.find('.attr-select').select2({
            placeholder: "Chọn thuộc tính",
            allowClear: true
        });
        $row.find('.values-select').select2({
            placeholder: "Chọn giá trị",
            allowClear: true
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
            $(this).find('.new-value-input').each(function(j) {
                $(this).attr('name', 'product_attributes[' + i + '][new_values][' + j + ']');
            });
        });
        updateAttributeSelects();
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
                    var newValue = $row.find(`input[name$="[new_values][]"][value="${val.replace('new_', '')}"]`).val() || val.replace('new_', '');
                    $valuesSelect.append(`<option value="${val}" selected>${newValue}</option>`);
                }
            });
            $valuesSelect.trigger('change');
        }
        $valuesSelect.select2({
            placeholder: "Chọn giá trị",
            allowClear: true
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
            alert('Vui lòng chọn thuộc tính trước khi thêm giá trị.');
            return;
        }
        if (!newValue) {
            alert('Vui lòng nhập giá trị mới.');
            return;
        }

        var tempId = 'new_' + md5(newValue);
        $row.append(`<input type="hidden" name="product_attributes[${index}][new_values][]" value="${newValue}" class="new-value-input">`);
        $valuesSelect.append(`<option value="${tempId}" selected>${newValue}</option>`);
        $valuesSelect.trigger('change');
        $row.find('.new-value-input').first().val('');

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
            alert('Vui lòng chọn ít nhất một thuộc tính và giá trị.');
            $('#no-variants-message').show();
            return;
        }

        var $panel = createVariantPanel(index, selectedAttrs);
        $('#accordionVariants').append($panel);
        $('#no-variants-message').hide();
    });

    // Xóa biến thể
    $(document).on('click', '.remove-variant', function() {
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
            alert('Vui lòng nhập ít nhất một giá trị để áp dụng hàng loạt.');
            return;
        }

        if(!confirm('Hành động này sẽ ghi đè dữ liệu của tất cả các biến thể bên dưới. Bạn có chắc chắn?')) return;

        // 2. Chạy vòng lặp ghi đè vào từng thẻ input/textarea của biến thể
        $('.variant-item').each(function(index) {
            var variantId = $(this).data('id');
            var $panelBody = $(this).find('.panel-body');
            
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
        $.notify("Đã áp dụng thành công!", "success"); // Tận dụng thư viện notify có sẵn trong admin của bạn
    });

    // Tạo biến thể tự động
    $('#generateVariantsBtn').click(function() {
        $('#accordionVariants').empty();
        variantCount = 0;

        var selectedAttrs = getSelectedAttributes();

        if (selectedAttrs.length === 0 || selectedAttrs.every(item => item.values.length === 0)) {
            $('#no-variants-message').show();
            return;
        }

        var valuesArray = selectedAttrs.map(item => item.values.map(id => ({val_id: id})));
        var combinations = cartesian(valuesArray);

        combinations.forEach((combo, i) => {
            var variantId = 'new' + i;
            variantCount++;
            var $panel = createVariantPanel(variantId, selectedAttrs, combo);
            $('#accordionVariants').append($panel);
        });
        $('#no-variants-message').hide();
    });

    // Hàm lấy selected attributes
    function getSelectedAttributes() {
        var selectedAttrs = [];
        $('.attribute-row').each(function() {
            var attrId = $(this).find('.attr-select').val();
            var values = $(this).find('.values-select').val() || [];
            var newValues = $(this).find('input[name$="[new_values][]"]').map(function() { return $(this).val(); }).get();
            if (attrId && (values.length > 0 || newValues.length > 0)) {
                var item = {attr: attrId, values: values};
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
            inputsHtml += `<div class="form-group"><label>${field.label}</label>`;
            
            if (field.type === 'textarea') {
                inputsHtml += `<textarea name="variants[${variantId}][${field.name}]" class="form-control" rows="3"></textarea>`;
            
            } else if (field.type === 'editor') {
                var editorId = `editor_${variantId}_${field.name}`;
                inputsHtml += `<textarea name="variants[${variantId}][${field.name}]" id="${editorId}" class="form-control" rows="3"></textarea>`;
                editorIds.push(editorId); // Lưu ID lại
                
            } else if (field.type === 'image') {
                var fieldId = `${field.name}_variant_${variantId}`;
                inputsHtml += `
                    <span class="box-img2" style="display: block; margin-top: 5px;">
                        <img src="img/no-image.png" id="review_${fieldId}" style="max-height: 100px; display: block; margin-bottom: 10px; border: 1px solid #ddd; padding: 2px; object-fit: contain;" alt="NO PHOTO" />
                        <input type="hidden" name="variants[${variantId}][${field.name}]" id="${fieldId}" value="" class="form-control">
                        <a href="filemanager/dialog.php?type=1&field_id=${fieldId}&relative_url=1&multiple=0" class="btn btn-sm btn-info iframe-btn" style="margin-top: 5px; display: inline-block;"> 
                            <i class="fa fa-upload" aria-hidden="true"></i> Chọn ${field.label}
                        </a>
                    </span>
                `;
            } else {
                var extraAttrs = '';
                if (field.min !== undefined) extraAttrs += ` min="${field.min}"`;
                if (field.step !== undefined) extraAttrs += ` step="${field.step}"`;
                inputsHtml += `<input type="${field.type}" name="variants[${variantId}][${field.name}]" class="form-control" ${extraAttrs}>`;
            }
            
            inputsHtml += `</div>`;
        });

        // 2. Tạo HTML cho Panel
        var $panel = $(`
            <div class="panel panel-default variant-item" data-id="${variantId}">
                <div class="panel-heading" role="tab" id="${headingId}">
                    <h5 class="panel-title">
                        <a role="button" data-toggle="collapse" class="accordion-plus-toggle collapsed" data-parent="#accordionVariants" href="#${panelId}">
                            Biến thể #${variantCount}
                        </a>
                        <button type="button" class="btn btn-danger btn-xs pull-right remove-variant" data-id="${variantId}">&times;</button>
                    </h5>
                </div>
                <div id="${panelId}" class="panel-collapse collapse" role="tabpanel">
                    <div class="panel-body">
                        ${inputsHtml}
                    </div>
                </div>
            </div>
        `);

        // ... Đoạn code xử lý selectedAttrs (Option dropdown) giữ nguyên ...
        var $link = $panel.find('.panel-title');
        selectedAttrs.forEach((item, idx) => {
            // (Giữ nguyên phần vòng lặp vẽ select tag options)
            var attrId = item.attr;
            var attrValues = item.values;
            var newValues = item.new_values || [];
            var attr = globalAttributes.find(a => a.id_code == attrId);
            var options = '';

            if (attr) {
                attrValues.forEach(valId => {
                    var val = attr.gia_tri.find(v => v.id_code == valId);
                    if (val) {
                        var isSelected = combo && combo[idx].val_id == valId ? 'selected' : '';
                        options += `<option value="${val.id_code}" ${isSelected}>${val.ten}</option>`;
                    }
                });
            }
            newValues.forEach(newValue => {
                var tempId = 'new_' + md5(newValue);
                var isSelected = combo && combo[idx].val_id == tempId ? 'selected' : '';
                options += `<option value="${tempId}" ${isSelected}>${newValue}</option>`;
            });

            var attrName = attr ? attr.ten : (item.new_attr_name || 'Thuộc tính mới');
            var $select = $(`
                <select name="variants[${variantId}][attributes][${attrId}]" class="form-control" style="display:inline-block; width:auto; margin-left:10px;" required>
                    <option value="">Chọn ${attrName}</option>
                    ${options}
                </select>
            `);
            $link.append($select);
        });

        // 3. Khởi tạo Fancybox cho nút upload hình (Filemanager)
        $panel.find('.iframe-btn').fancybox({
            'type'      : 'iframe',
            'autoScale' : false
        });

        // 4. Kích hoạt CKEditor sau khi DOM đã sẵn sàng (dùng setTimeout)
        if (editorIds.length > 0) {
            setTimeout(function() {
                editorIds.forEach(function(id) {
                    if (typeof CKEDITOR !== 'undefined') {
                        CKEDITOR.replace(id, {
                            filebrowserBrowseUrl : 'filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
                            filebrowserUploadUrl : 'filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
                            filebrowserImageBrowseUrl : 'filemanager/dialog.php?type=1&editor=ckeditor&fldr=',
                            height: '150px'
                        });
                    }
                });
            }, 300); // Chờ 300ms để html append xong vào body
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
        handle: '.accordion-plus-toggle',
        axis: 'y',
        opacity: 0.8,
        placeholder: 'sortable-placeholder',
        start: function(event, ui) {
            ui.placeholder.height(ui.item.height());
        },
        update: function(event, ui) {}
    }).disableSelection();

    // Hiển thị thông báo nếu không có biến thể
    if ($('#accordionVariants .variant-item').length === 0) {
        $('#no-variants-message').show();
    }
});
</script>