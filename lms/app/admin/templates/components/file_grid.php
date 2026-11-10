<?php
/**
 * LMS Shared Component: file_grid
 * Hiển thị danh sách tệp đã lưu dưới dạng chip tải xuống.
 *
 * Props:
 * @var array  $files  Danh sách đường dẫn tương đối (VD: test/202610/abc.pdf)
 * @var string $empty  Nội dung khi không có tệp
 * @var string $class  Class CSS bổ sung cho lưới
 *
 * PHP 7.3 compatible.
 */

$files = isset($files) && is_array($files) ? $files : array();
$empty = isset($empty) ? (string) $empty : '';
$extra = isset($class) ? (string) $class : '';

if (empty($files)) {
    if ($empty !== '') {
        echo '<div class="lms-file-grid-empty text-muted small">' . e($empty) . '</div>';
    }
    return;
}

$iconFor = function ($path) {
    $ext = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));
    if ($ext === 'pdf') {
        return 'fa-solid fa-file-pdf text-danger';
    }
    if (in_array($ext, array('doc', 'docx'), true)) {
        return 'fa-solid fa-file-word text-primary';
    }
    if (in_array($ext, array('xls', 'xlsx'), true)) {
        return 'fa-solid fa-file-excel text-success';
    }
    if (in_array($ext, array('ppt', 'pptx'), true)) {
        return 'fa-solid fa-file-powerpoint text-warning';
    }
    if (in_array($ext, array('jpg', 'jpeg', 'png', 'gif', 'webp'), true)) {
        return 'fa-solid fa-file-image text-info';
    }
    if ($ext === 'zip') {
        return 'fa-solid fa-file-zipper text-secondary';
    }
    return 'fa-solid fa-file text-secondary';
};
?>
<div class="lms-file-grid<?= $extra !== '' ? ' ' . e($extra) : '' ?>">
    <?php foreach ($files as $file): ?>
        <?php $path = (string) $file; $name = basename($path); ?>
        <a href="<?= e(lms_upload_url($path)) ?>" target="_blank" rel="noopener"
           class="lms-file-chip" title="<?= e($name) ?>">
            <i class="<?= e($iconFor($path)) ?> lms-file-chip-icon"></i>
            <span class="lms-file-chip-name"><?= e($name) ?></span>
            <i class="fa-solid fa-download lms-file-chip-action"></i>
        </a>
    <?php endforeach; ?>
</div>
