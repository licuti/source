<?php
/**
 * Component: card_open
 * Mở thẻ .card của Bootstrap 5 (thay cho .box của AdminLTE 2).
 * Phải luôn có `card_close` tương ứng.
 *
 * Props:
 * @var string $title    (tuỳ chọn) tiêu đề card
 * @var string $icon     (tuỳ chọn) class icon đứng trước tiêu đề
 * @var string $tools    (tuỳ chọn) HTML đặt bên phải header
 * @var string $class    (tuỳ chọn) class thêm cho .card
 * @var string $body     (tuỳ chọn) 'none' = không mở .card-body (tự quản lý)
 */
$title = $title ?? '';
$icon  = $icon  ?? '';
$tools = $tools ?? '';
$class = $class ?? '';
$body  = $body  ?? '';
?>
<div class="card mb-4 <?= e($class) ?>">
    <?php if ($title !== '' || $tools !== ''): ?>
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <?php if ($title !== ''): ?>
                <h5 class="card-title mb-0">
                    <?php if ($icon !== ''): ?><i class="<?= e($icon) ?> me-1"></i><?php endif; ?>
                    <?= e($title) ?>
                </h5>
            <?php endif; ?>
            <?php if ($tools !== ''): ?>
                <div class="card-tools d-flex align-items-center gap-2 flex-grow-1 justify-content-end"><?= $tools ?></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php if ($body !== 'none'): ?>
    <div class="card-body">
    <?php endif; ?>