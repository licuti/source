<?php
/**
 * Component: form/image_picker
 * Props: label, name, id (optional, default = name sanitized), value, table, row_id, height, hint
 */
$label  = $label  ?? 'Hình ảnh';
$name   = $name   ?? '';
$id     = $id     ?? '';
if ($id === '') {
    $id = preg_replace('/[^A-Za-z0-9_-]/', '_', $name);
}
$value  = $value  ?? '';
$table  = $table  ?? '';
$row_id = $row_id ?? '';
$height = (int) ($height ?? 150);
$hint   = $hint   ?? '';

$hasImage  = trim((string) $value) !== '';
$canDelete = $hasImage && $row_id !== '' && $table !== '';
?>
<div class="mb-3 form-file-picker"
     data-file-picker
     data-preview="image">
    <label class="form-label fw-medium d-block"><?= e($label) ?></label>

    <div class="border rounded position-relative overflow-hidden bg-body-secondary"
         style="height:<?= $height ?>px">

        <img src="<?= e($hasImage ? img_url($value) : 'img/no-image.png') ?>"
             id="review_<?= e($id) ?>"
             alt="<?= e($label) ?>"
             class="w-100 h-100 object-fit-contain"
             data-fallback="img/no-image.png"
             onerror="this.onerror=null;this.src='img/no-image.png';">

        <div class="position-absolute top-0 end-0 m-1 d-flex gap-1">
            <button type="button"
                    class="btn btn-sm btn-light border shadow-sm js-copy-file-url <?= $hasImage ? '' : 'd-none' ?>"
                    title="Sao chép đường dẫn hình ảnh"
                    data-target="<?= e($id) ?>"
                    data-preview="image">
                <i class="fa-solid fa-copy"></i>
            </button>
            <button type="button"
                    class="btn btn-sm btn-danger js-file-picker-delete <?= $hasImage ? '' : 'd-none' ?>"
                    title="Xóa hình"
                    <?= $canDelete ? 'data-action="xoa-img"' : 'data-action="clear-image"' ?>
                    data-target="<?= e($id) ?>"
                    data-bang="<?= e($table) ?>"
                    data-cot="<?= e($id) ?>"
                    data-id-code="<?= e($row_id) ?>"
                    data-folder="">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    </div>

    <input type="hidden"
           name="<?= e($name) ?>"
           id="<?= e($id) ?>"
           value="<?= e($value) ?>"
           data-preview="image">

    <a <?= function_exists('fm_button_attrs') ? fm_button_attrs(array(
           'field_id' => $id,
           'type'     => 'image',
           'multiple' => false,
           'class'    => 'btn btn-sm btn-outline-primary w-100 mt-2 iframe-btn js-fm-open',
       )) : ('href="ckfinder/ckfinder.html?type=Images" class="btn btn-sm btn-outline-primary w-100 mt-2 iframe-btn js-fm-open" data-fm-driver="ckfinder" data-fm-field="' . e($id) . '" data-fm-type="image" data-fm-resource="Images" data-fm-multiple="0"') ?>>
        <i class="fa-solid fa-upload me-1"></i>Chọn hình ảnh
    </a>

    <?php if ($hint !== ''): ?>
        <div class="form-text"><?= e($hint) ?></div>
    <?php endif; ?>
</div>
