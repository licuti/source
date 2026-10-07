<?php
    $block_banner = $d->getContent(456);
?>

<?php if ($d->getContent(456)): ?>
    <img src="<?= getImageUrl($block_banner['hinh_anh']) ?>" alt="<?= $block_banner['ten'] ?>" class="image-cover">
<?php endif ?>