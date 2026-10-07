<?php
/**
 * Component: thumb
 * Ảnh thumbnail vuông trong bảng danh sách. Nếu có $url thì bọc trong <a>.
 *
 * Props:
 * @var string $file  Tên file ảnh trong media/images (có thể rỗng)
 * @var string $url   (tuỳ chọn) link khi click ảnh
 * @var string $alt   (tuỳ chọn) alt text
 * @var int    $size  (tuỳ chọn) kích thước px, mặc định 46
 */
$file = $file ?? '';
$url  = $url  ?? '';
$alt  = $alt  ?? '';
$size = (int) ($size ?? 46);

$hasImage = trim((string) $file) !== '';
?>
<?php if ($hasImage): ?>
    <?php if ($url !== ''): ?><a href="<?= e($url) ?>" class="d-inline-block"><?php endif; ?>
        <img src="<?= e(img_url($file)) ?>"
             alt="<?= e($alt) ?>"
             loading="lazy"
             class="rounded border object-fit-cover"
             style="width:<?= $size ?>px;height:<?= $size ?>px">
    <?php if ($url !== ''): ?></a><?php endif; ?>
<?php else: ?>
    <span class="d-inline-flex align-items-center justify-content-center rounded border text-secondary-emphasis bg-body-secondary"
          style="width:<?= $size ?>px;height:<?= $size ?>px" title="Chưa có hình">
        <i class="fa-regular fa-image"></i>
    </span>
<?php endif; ?>