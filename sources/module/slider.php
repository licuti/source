<?php
    $slider = $d->getContents(68);
?>

<div class="swiper main-slide">
    <div class="swiper-wrapper">
        <?php foreach ($slider as $key => $value): ?>
            <div class="swiper-slide">
                <?= $value['link'] ? '<a href="'.$value['link'].'" >' : '' ?>
                    <?php if ($value['video']): ?>
                        <video autoplay loop muted playsinline preload="auto" poster="<?= getImageUrl($value['hinh_anh']) ?>">
                            <source src="<?= URLPATH.'/media/images/'.$value['video'] ?>" type="video/mp4">
                                Trình duyệt của bạn không hỗ trợ thẻ video.
                        </video>
                    <?php else: ?>
                        <img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ? $value['ten'] : "Slide Image" ?>" class="image-cover">
                    <?php endif ?>

                    <?php if ($value['noi_dung']): ?>
                        <div class="banner-slide">
                            <?= $value['noi_dung'] ?>
                        </div>
                    <?php endif ?>
                    
                <?= $value['link'] ? '</a>' : '' ?>
            </div>
        <?php endforeach ?>
    </div>
    <!-- <div class="swiper-button-next main-slide-next"></div>
    <div class="swiper-button-prev main-slide-prev"></div> -->
</div>