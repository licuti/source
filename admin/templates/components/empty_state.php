<?php
/**
 * Component: empty_state
 * Hiển thị khi danh sách không có dữ liệu.
 *
 * Props:
 * @var int    $colspan  Số cột của bảng (để <td> trải hết chiều ngang)
 * @var string $message  Nội dung chính
 * @var string $hint     (tuỳ chọn) gợi ý thêm
 * @var string $icon     (tuỳ chọn) icon
 * @var bool   $as_row   true = render dạng <tr><td>, false = render <div>
 */
$colspan = (int) ($colspan ?? 1);
$message = $message ?? 'Chưa có dữ liệu';
$hint    = $hint    ?? '';
$icon    = $icon    ?? 'fa-solid fa-inbox';
$as_row  = $as_row  ?? true;

ob_start(); ?>
<div class="text-center text-secondary py-5">
    <i class="<?= e($icon) ?> d-block mb-2 fs-1 opacity-50"></i>
    <p class="mb-1 fw-medium"><?= e($message) ?></p>
    <?php if ($hint !== ''): ?>
        <small class="text-secondary"><?= e($hint) ?></small>
    <?php endif; ?>
</div>
<?php $inner = ob_get_clean(); ?>
<?php if ($as_row): ?>
    <tr class="no-hover">
        <td colspan="<?= $colspan ?>"><?= $inner ?></td>
    </tr>
<?php else: ?>
    <?= $inner ?>
<?php endif; ?>