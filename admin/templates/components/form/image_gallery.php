<?php
/**
 * Component: form/image_gallery
 * Gallery ảnh đa năng, có thể tái sử dụng cho nhiều module.
 *
 * Props:
 * @var string $label             Tiêu đề gallery
 * @var string $name              Tên input hidden chứa ảnh mới chọn (mặc định 'album')
 * @var array  $images            Mảng các ảnh hiện có (object/array có hinh_anh hoặc chuỗi URL)
 * @var string $choose_text       Text nút chọn ảnh
 * @var string $empty_text        Text hiển thị khi chưa có ảnh
 * @var string $col_class         Class cột cho mỗi ảnh (mặc định 'col-4 col-md-3 mb-2 gallery-item')
 * @var bool   $include_existing  Có sinh hidden input cho ảnh cũ để submit theo form (mặc định false)
 */
$label            = $label            ?? 'Album hình ảnh';
$name             = $name             ?? 'album';
$images           = $images           ?? [];
$choose_text      = $choose_text      ?? 'Chọn hình ảnh';
$empty_text       = $empty_text       ?? 'Chưa có hình nào trong album.';
$col_class        = $col_class        ?? 'col-4 col-md-3 mb-2 gallery-item';
$include_existing = !empty($include_existing);
?>
<div class="mb-3 image-gallery" data-gallery="<?= e($name) ?>">
    <?php if ($label !== ''): ?>
        <label class="form-label fw-medium d-block"><?= e($label) ?></label>
    <?php endif; ?>

    <div class="row g-2 mb-3" id="list_<?= e($name) ?>" data-gallery-sortable>
        <?php if (!empty($images)): ?>
            <?php foreach ($images as $idx => $img):
                $imgSrc = is_array($img) ? ($img['hinh_anh'] ?? '') : (is_object($img) ? ($img->hinh_anh ?? '') : (string) $img);
                if (empty($imgSrc)) continue;
                $imgId  = is_array($img) ? ($img['id'] ?? $idx) : (is_object($img) ? ($img->id ?? $idx) : $idx);
                $itemId = 'gallery_item_' . e($name) . '_' . e($imgId);
            ?>
                <div class="<?= e($col_class) ?>" id="<?= $itemId ?>">
                    <div class="card h-100 shadow-sm" data-path="<?= e($imgSrc) ?>">
                        <img src="<?= e(img_url($imgSrc)) ?>"
                             alt=""
                             class="card-img-top bg-light"
                             style="height: 120px; object-fit: contain;">
                        <div class="card-body p-2 text-center">
                            <?php if ($include_existing): ?>
                                <input type="hidden" name="<?= e($name) ?>[]" value="<?= e($imgSrc) ?>">
                            <?php endif; ?>
                            <div class="d-flex gap-1">
                                <button type="button"
                                        class="btn btn-sm btn-outline-secondary js-copy-file-url flex-grow-1"
                                        title="Sao chép đường dẫn ảnh"
                                        data-copy-val="<?= e(img_url($imgSrc)) ?>">
                                    <i class="fa-solid fa-copy me-1"></i>Copy
                                </button>
                                <button type="button"
                                        class="btn btn-sm btn-danger btn-remove-gallery flex-grow-1"
                                        title="Xóa ảnh"
                                        data-action="remove-gallery-item"
                                        data-target="<?= $itemId ?>">
                                    <i class="fa-solid fa-trash me-1"></i>Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (empty($images)): ?>
        <p class="text-secondary small mb-2" id="empty_<?= e($name) ?>"><?= e($empty_text) ?></p>
    <?php endif; ?>

    <input type="hidden" id="<?= e($name) ?>" name="<?= e($name) ?>_picker" data-name="<?= e($name) ?>" class="form-control form-control-sm" data-preview="image" data-fm-multiple="1">

    <a <?= function_exists('fm_button_attrs') ? fm_button_attrs(array(
           'field_id' => $name,
           'type'     => 'image',
           'multiple' => true,
           'class'    => 'btn btn-sm btn-outline-primary iframe-btn js-fm-open',
       )) : ('href="ckfinder/ckfinder.html?type=Images" class="btn btn-sm btn-outline-primary iframe-btn js-fm-open" data-fm-driver="ckfinder" data-fm-field="' . e($name) . '" data-fm-type="image" data-fm-resource="Images" data-fm-multiple="1"') ?>>
        <i class="fa-solid fa-upload me-1"></i><?= e($choose_text) ?>
    </a>

    <div class="form-text mt-2">
        Ảnh tải lên lưu vào <code>media/images</code> (File Manager adapter).
    </div>
</div>
