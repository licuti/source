<?php
/**
 * LMS Form Component: form/file_upload
 * Khu vực tải lên nhiều tệp (kéo thả hoặc chọn), kèm danh sách tệp đã nộp.
 * Hành vi JS nằm trong lms/public/js/lms.js (LMS.initFileUploads).
 *
 * Props:
 * @var string $name          Tên input (VD: 'test_files[]')
 * @var string $id            ID input (tự sinh nếu không truyền)
 * @var string $label         Nhãn phía trên khu vực tải lên
 * @var string $accept        Định dạng chấp nhận (VD: '.pdf,.jpg')
 * @var int    $max_files     Số tệp tối đa của lần nộp (mặc định: 5)
 * @var int    $max_size_mb   Dung lượng tối đa mỗi tệp, MB (mặc định: 20)
 * @var array  $existing      Danh sách tệp đã nộp trước đó
 * @var string $existing_label Nhãn danh sách tệp đã nộp (mặc định: 'Đã nộp')
 * @var string $replace_hint  Ghi chú thêm cạnh nhãn tệp đã nộp
 * @var string $empty_existing Nội dung khi chưa có tệp nào
 * @var string $hint          Dòng gợi ý dưới vùng tải lên
 * @var bool   $required      Bắt buộc chọn ít nhất 1 tệp mới
 * @var bool   $disabled      Ẩn vùng tải lên, chỉ hiển thị tệp đã nộp
 * @var bool   $show_zone     Hiển thị vùng tải lên (mặc định: true)
 * @var string $browse_text   Chữ trên liên kết chọn tệp (mặc định: 'chọn tệp')
 *
 * PHP 7.3 compatible.
 */

$name           = isset($name) ? (string) $name : 'files[]';
$label          = isset($label) ? (string) $label : '';
$accept         = isset($accept) && $accept !== ''
    ? (string) $accept
    : '.pdf,.jpg,.jpeg,.png,.gif,.webp,.docx,.xlsx,.pptx,.zip';
$maxFiles       = isset($max_files) ? max(1, (int) $max_files) : 5;
$maxSizeMb      = isset($max_size_mb) ? max(1, (int) $max_size_mb) : 20;
$existing       = isset($existing) && is_array($existing) ? $existing : array();
$existingLabel  = isset($existing_label) ? (string) $existing_label : 'Đã nộp';
$replaceHint    = isset($replace_hint) ? (string) $replace_hint : '';
$emptyExisting  = isset($empty_existing) ? (string) $empty_existing : '';
$hint           = isset($hint) ? (string) $hint : '';
$required       = !empty($required);
$disabled       = !empty($disabled);
$showZone       = !isset($show_zone) || !empty($show_zone);
$browseText     = isset($browse_text) && $browse_text !== '' ? (string) $browse_text : 'chọn tệp';
$id             = isset($id) && $id !== ''
    ? (string) $id
    : 'fld-' . str_replace(array('[', ']', '_'), array('-', '', '-'), $name);
$multiple       = $maxFiles > 1;

if ($hint === '') {
    $hint = 'Kéo thả hoặc chọn tối đa ' . $maxFiles . ' tệp · ≤ ' . $maxSizeMb . 'MB/tệp';
}
?>
<div class="lms-upload-field" data-lms-upload
     data-max-files="<?= (int) $maxFiles ?>"
     data-max-size="<?= (int) ($maxSizeMb * 1024 * 1024) ?>"
     data-upload-required="<?= $required ? '1' : '0' ?>">
    <?php if ($label !== ''): ?>
        <div class="lms-upload-field-label"><?= e($label) ?></div>
    <?php endif; ?>

    <?php if ($showZone && !$disabled): ?>
        <div class="lms-upload-zone" data-upload-zone>
            <input type="file"
                   name="<?= e($name) ?>"
                   id="<?= e($id) ?>"
                   class="d-none"
                   accept="<?= e($accept) ?>"
                   data-upload-input
                   <?= $multiple ? 'multiple' : '' ?>>

            <button type="button" class="lms-upload-trigger" data-upload-trigger>
                <i class="fa-solid fa-cloud-arrow-up lms-upload-trigger-icon"></i>
                <span class="lms-upload-trigger-title">
                    Kéo thả tệp vào đây hoặc <span class="lms-upload-trigger-link"><?= e($browseText) ?></span>
                </span>
                <span class="lms-upload-trigger-hint"><?= e($hint) ?></span>
            </button>

            <ul class="lms-upload-selected" data-upload-list hidden></ul>

            <button type="button" class="lms-upload-add" data-upload-add hidden>
                <i class="fa-solid fa-plus me-1"></i>Chọn thêm tệp
            </button>
        </div>
        <div class="lms-upload-error text-danger small mt-1" data-upload-error hidden></div>
    <?php endif; ?>

    <?php if (!empty($existing)): ?>
        <div class="lms-upload-existing">
            <div class="lms-upload-existing-label">
                <?= e($existingLabel) ?> (<?= count($existing) ?>)
                <?php if ($replaceHint !== ''): ?>
                    <span class="lms-upload-existing-note"><?= e($replaceHint) ?></span>
                <?php endif; ?>
            </div>
            <?php lms_component('file_grid', array('files' => $existing)); ?>
        </div>
    <?php elseif ($emptyExisting !== ''): ?>
        <div class="lms-file-grid-empty text-muted small"><?= e($emptyExisting) ?></div>
    <?php endif; ?>
</div>
