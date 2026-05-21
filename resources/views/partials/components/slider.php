<?php
    $slider = \ContentModel::where('id_loai', 68)->where('hien_thi', 1)->orderBy('so_thu_tu', 'ASC')->get();
?>

<div class="swiper main-slide">
    <div class="swiper-wrapper">
        <?php foreach ($slider as $value): ?>
            <div class="swiper-slide">
                <?= $value->link ? '<a href="'.$value->link.'" >' : '' ?>
                    <?php if ($value->video): ?>
                        <video autoplay loop muted playsinline preload="auto" poster="<?= getImageUrl($value->hinh_anh) ?>">
                            <source src="<?= url('img_data/images/' . $value->video) ?>" type="video/mp4">
                                Trình duyệt của bạn không hỗ trợ thẻ video.
                        </video>
                    <?php else: ?>
                        <img src="<?= getImageUrl($value->hinh_anh) ?>" alt="<?= $value->ten ? $value->ten : "Slide Image" ?>" class="image-cover">
                    <?php endif ?>

                    <?php if ($value->noi_dung): ?>
                        <div class="banner-slide">
                            <?= $value->noi_dung ?>
                        </div>
                    <?php endif ?>
                    
                <?= $value->link ? '</a>' : '' ?>
            </div>
        <?php endforeach ?>
    </div>
    <div class="swiper-button-next main-slide-next"></div>
    <div class="swiper-button-prev main-slide-prev"></div>
</div>