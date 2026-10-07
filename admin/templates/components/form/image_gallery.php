<?php
/**
 * Component: form/image_gallery
 * Gallery ảnh đa năng, có thể tái sử dụng cho nhiều module.
 *
 * Props:
 * @var string $label        Tiêu đề gallery
 * @var string $name         Tên input hidden chứa ảnh mới chọn (mặc định 'album')
 * @var array  $images       Mảng các object ảnh hiện có, mỗi object có id và hinh_anh
 * @var string $choose_text  Text nút chọn ảnh
 * @var string $empty_text   Text hiển thị khi chưa có ảnh
 */
$label       = $label       ?? 'Album hình ảnh';
$name        = $name        ?? 'album';
$images      = $images      ?? [];
$choose_text = $choose_text ?? 'Chọn hình ảnh';
$empty_text  = $empty_text  ?? 'Chưa có hình nào trong album.';
?>
<div class="mb-3 image-gallery" data-gallery="<?= e($name) ?>">
    <?php if ($label !== ''): ?>
        <label class="form-label fw-medium d-block"><?= e($label) ?></label>
    <?php endif; ?>

    <div class="row g-2 mb-3" id="list_<?= e($name) ?>" data-gallery-sortable>
        <?php if (!empty($images)): ?>
            <?php foreach ($images as $img): ?>
                <div class="col-6" id="gallery_item_<?= e($name) ?>_<?= (int) $img['id'] ?>">
                    <div class="position-relative border rounded overflow-hidden bg-body-secondary"
                         style="aspect-ratio:1/1">
                        <img src="<?= e(img_url($img['hinh_anh'])) ?>"
                             alt=""
                             class="w-100 h-100 object-fit-contain">
                        <button type="button"
                                class="btn btn-sm btn-danger position-absolute top-0 end-0 m-1"
                                title="Xóa ảnh"
                                data-action="remove-gallery-item"
                                data-target="gallery_item_<?= e($name) ?>_<?= (int) $img['id'] ?>">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <?php if (empty($images)): ?>
        <p class="text-secondary small mb-2" id="empty_<?= e($name) ?>"><?= e($empty_text) ?></p>
    <?php endif; ?>

    <input type="hidden" id="<?= e($name) ?>" class="form-control form-control-sm" data-preview="image" data-fm-multiple="1">

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
