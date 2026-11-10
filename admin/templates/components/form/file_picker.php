<?php
/**
 * Component: form/file_picker
 * Chọn file qua Responsive FileManager (ảnh / video / tài liệu).
 *
 * Props: label, name, value, table, row_id, type (1|2|3), preview (image|video|file|none),
 *        height, choose_text, hint, class
 */
$label       = $label       ?? 'Tệp tin';
$name        = $name        ?? '';
$value       = $value       ?? '';
$table       = $table       ?? '';
$row_id      = $row_id      ?? '';
$type        = (int) ($type ?? 2);
$height      = (int) ($height ?? 150);
$hint        = $hint        ?? '';
$class       = $class       ?? '';
$choose_text = $choose_text ?? '';

if (!isset($preview) || $preview === '' || $preview === null) {
    if ($type === 1) { $preview = 'image'; }
    elseif ($type === 3) { $preview = 'video'; }
    else { $preview = 'file'; }
}
if ($choose_text === '') {
    if ($preview === 'image') { $choose_text = 'Chọn hình ảnh'; }
    elseif ($preview === 'video') { $choose_text = 'Chọn video'; }
    else { $choose_text = 'Chọn tệp tin'; }
}

$hasFile   = trim((string) $value) !== '';
$canDelete = $hasFile && $row_id !== '' && $table !== '';
$fileUrl   = $hasFile ? media_url($value, $preview === 'video' ? 'video' : ($preview === 'file' ? 'file' : 'image')) : '';

$ext = $hasFile ? strtolower(pathinfo($value, PATHINFO_EXTENSION)) : '';
$extIcon = 'fa-file';
if (in_array($ext, ['pdf'], true)) { $extIcon = 'fa-file-pdf'; }
elseif (in_array($ext, ['doc', 'docx'], true)) { $extIcon = 'fa-file-word'; }
elseif (in_array($ext, ['xls', 'xlsx', 'csv'], true)) { $extIcon = 'fa-file-excel'; }
elseif (in_array($ext, ['zip', 'rar', '7z'], true)) { $extIcon = 'fa-file-zipper'; }
elseif (in_array($ext, ['mp4', 'webm', 'mov'], true)) { $extIcon = 'fa-file-video'; }
elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) { $extIcon = 'fa-file-image'; }
?>

<div class="mb-3 form-file-picker <?= e($name) ?> <?= e($class) ?>"
     data-file-picker
     data-preview="<?= e($preview) ?>">
    <?php if ($label !== ''): ?>
        <label class="form-label fw-medium d-block"><?= e($label) ?></label>
    <?php endif; ?>

    <?php if ($preview === 'image'): ?>
        <div class="border rounded position-relative overflow-hidden bg-body-secondary"
             style="height:<?= $height ?>px">
            <img src="<?= e($hasFile ? $fileUrl : 'img/no-image.png') ?>"
                 id="review_<?= e($name) ?>"
                 alt="<?= e($label) ?>"
                 class="w-100 h-100 object-fit-contain"
                 data-fallback="img/no-image.png"
                 onerror="this.onerror=null;this.src='img/no-image.png';">
            <div class="position-absolute top-0 end-0 m-1 d-flex gap-1">
                <button type="button"
                        class="btn btn-sm btn-light border shadow-sm js-copy-file-url <?= $hasFile ? '' : 'd-none' ?>"
                        title="Sao chép đường dẫn hình ảnh"
                        data-target="<?= e($name) ?>"
                        data-preview="image">
                    <i class="fa-solid fa-copy"></i>
                </button>
                <button type="button"
                        class="btn btn-sm btn-danger js-file-picker-delete <?= $hasFile ? '' : 'd-none' ?>"
                        title="Xóa"
                        <?= $canDelete ? 'data-action="xoa-img"' : 'data-action="clear-image"' ?>
                        data-target="<?= e($name) ?>"
                        data-bang="<?= e($table) ?>"
                        data-cot="<?= e($name) ?>"
                        data-id-code="<?= e($row_id) ?>"
                        data-folder="">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

    <?php elseif ($preview === 'video'): ?>
        <div class="border rounded position-relative overflow-hidden bg-body-secondary mb-2 file-picker-video-box">
            <video id="review_<?= e($name) ?>"
                   controls
                   playsinline
                   class="w-100 d-block <?= $hasFile ? '' : 'd-none' ?>"
                   style="max-height:<?= max(180, $height) ?>px; background:#000"
                   <?= $hasFile ? 'src="' . e($fileUrl) . '"' : '' ?>></video>

            <div id="review_<?= e($name) ?>_empty"
                 class="d-flex align-items-center justify-content-center text-secondary <?= $hasFile ? 'd-none' : '' ?>"
                 style="min-height:<?= max(120, $height) ?>px">
                <span class="small"><i class="fa-solid fa-film me-1"></i>Chưa chọn video</span>
            </div>

            <div class="position-absolute top-0 end-0 m-1 d-flex gap-1 z-1">
                <button type="button"
                        class="btn btn-sm btn-light border shadow-sm js-copy-file-url <?= $hasFile ? '' : 'd-none' ?>"
                        title="Sao chép đường dẫn video"
                        data-target="<?= e($name) ?>"
                        data-preview="video">
                    <i class="fa-solid fa-copy"></i>
                </button>
                <button type="button"
                        class="btn btn-sm btn-danger js-file-picker-delete <?= $hasFile ? '' : 'd-none' ?>"
                        title="Xóa video"
                        <?= $canDelete ? 'data-action="xoa-img"' : 'data-action="clear-image"' ?>
                        data-target="<?= e($name) ?>"
                        data-bang="<?= e($table) ?>"
                        data-cot="<?= e($name) ?>"
                        data-id-code="<?= e($row_id) ?>"
                        data-folder="">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
        </div>

    <?php elseif ($preview === 'file'): ?>
        <div id="review_<?= e($name) ?>_wrap"
             class="border rounded p-2 d-flex align-items-center gap-2 bg-body-secondary mb-2 <?= $hasFile ? '' : 'd-none' ?>">
            <i class="fa-solid <?= e($extIcon) ?> fa-lg text-primary" id="review_<?= e($name) ?>_icon"></i>
            <div class="flex-grow-1 min-w-0">
                <a href="<?= e($fileUrl !== '' ? $fileUrl : '#') ?>" target="_blank" rel="noopener"
                   id="review_<?= e($name) ?>_link"
                   class="small text-break fw-medium">
                    <?= e($hasFile ? basename($value) : '') ?>
                </a>
            </div>
            <button type="button"
                    class="btn btn-sm btn-outline-secondary js-copy-file-url <?= $hasFile ? '' : 'd-none' ?>"
                    title="Sao chép đường dẫn tệp"
                    data-target="<?= e($name) ?>"
                    data-preview="file">
                <i class="fa-solid fa-copy"></i>
            </button>
            <button type="button"
                    class="btn btn-sm btn-outline-danger js-file-picker-delete <?= $hasFile ? '' : 'd-none' ?>"
                    title="Xóa"
                    <?= $canDelete ? 'data-action="xoa-img"' : 'data-action="clear-image"' ?>
                    data-target="<?= e($name) ?>"
                    data-bang="<?= e($table) ?>"
                    data-cot="<?= e($name) ?>"
                    data-id-code="<?= e($row_id) ?>"
                    data-folder="">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
        <div id="review_<?= e($name) ?>_empty"
             class="border rounded p-3 text-center text-secondary small mb-2 <?= $hasFile ? 'd-none' : '' ?>">
            <i class="fa-regular fa-file me-1"></i>Chưa chọn tệp
        </div>
    <?php endif; ?>

    <input type="hidden" name="<?= e($name) ?>" id="<?= e($name) ?>"
           value="<?= e($value) ?>" data-preview="<?= e($preview) ?>">

    <a <?= function_exists('fm_button_attrs') ? fm_button_attrs(array(
           'field_id' => $name,
           'type'     => isset($preview) ? $preview : 'file',
           'multiple' => false,
           'class'    => 'btn btn-sm btn-outline-primary w-100 mt-1 iframe-btn js-fm-open',
       )) : ('href="ckfinder/ckfinder.html?type=Files" class="btn btn-sm btn-outline-primary w-100 mt-1 iframe-btn js-fm-open" data-fm-driver="ckfinder" data-fm-field="' . e($name) . '" data-fm-type="' . (isset($preview) ? e($preview) : 'file') . '" data-fm-resource="Files" data-fm-multiple="0"') ?>>
        <i class="fa-solid fa-upload me-1"></i><?= e($choose_text) ?>
    </a>

    <?php if ($hint !== ''): ?>
        <div class="form-text"><?= e($hint) ?></div>
    <?php endif; ?>
</div>
