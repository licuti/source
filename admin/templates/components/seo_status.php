<?php
/**
 * Component: seo_status
 * Hiển thị trạng thái SEO của 1 dòng: Index/Noindex và Dofollow/Nofollow.
 *
 * Props:
 * @var bool $noindex
 * @var bool $nofollow
 */
$noindex  = !empty($noindex);
$nofollow = !empty($nofollow);

/*
 * SỬA LỖI: trước đây dùng class 'text-bg-danger-subtle' / 'text-bg-success-subtle'
 * -> Bootstrap KHÔNG có các class này (chỉ có 'text-bg-*' hoặc 'bg-*-subtle',
 * không có biến thể ghép 'text-bg-*-subtle'). Vì vậy badge không hề có màu nền.
 *
 * Dùng đúng bộ 3 class của Bootstrap 5.3:
 *      bg-{color}-subtle       -> nền nhạt
 *      text-{color}-emphasis   -> chữ đậm màu, đủ tương phản trên nền nhạt
 *      border-{color}-subtle   -> viền cùng tông
 *
 * Gom thành mảng để 2 badge dùng chung một khối markup, tránh lặp code và
 * tránh việc sửa màu 1 chỗ mà quên chỗ kia.
 */
$badges = [
    [
        'on'    => $noindex,
        'icon'  => $noindex ? 'fa-eye-slash' : 'fa-circle-check',
        'label' => $noindex ? 'Noindex'      : 'Index',
        'hint'  => $noindex
            ? 'Trang này KHÔNG cho công cụ tìm kiếm lập chỉ mục'
            : 'Trang này cho phép lập chỉ mục',
    ],
    [
        'on'    => $nofollow,
        'icon'  => $nofollow ? 'fa-link-slash' : 'fa-link',
        'label' => $nofollow ? 'Nofollow'      : 'Dofollow',
        'hint'  => $nofollow
            ? 'Công cụ tìm kiếm KHÔNG đi theo các liên kết trong trang'
            : 'Công cụ tìm kiếm đi theo các liên kết trong trang',
    ],
];
?>
<div class="d-flex flex-column gap-1 align-items-start">
    <?php foreach ($badges as $b): ?>
        <?php $tone = $b['on'] ? 'danger' : 'success'; ?>
        <span class="badge rounded-pill bg-<?= $tone ?>-subtle text-<?= $tone ?>-emphasis border border-<?= $tone ?>-subtle fw-semibold"
              title="<?= e($b['hint']) ?>">
            <i class="fa-solid <?= $b['icon'] ?> me-1"></i><?= $b['label'] ?>
        </span>
    <?php endforeach; ?>
</div>
