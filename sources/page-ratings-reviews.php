<?php
    $block_header = $d->getContent(1446);
    $content_header = $d->getContents(1446);
?>

<?php if ($block_header): ?>
    <div class="block block-header-page block-header-ratings-reviews">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-4">
                    <div class="header-page">
                        <div class="fs-20 mb-3"><?= $block_header['noi_dung'] ?></div>
                        <div class="row g-3">
                            <?php foreach ($content_header as $key => $value): ?>
                                <div class="col-lg-6">
                                    <div class="header-page-counter">
                                        <div class="label-counter text-gradient"><?= $value['ten'] ?><?= $value['ten_phu'] ?></div>
                                        <?= $value['noi_dung'] ?>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="bg-cover" style="background-image: url('<?= getImageUrl($block_header['hinh_anh']) ?>'); background-size: contain; background-position: right bottom;"></div>
    </div>
<?php endif ?>


<?php
    $block_honor_roll = $d->getContent(1449);
    $content_honor_roll = $d->getContents(1449);
?>


<?php if ($block_honor_roll): ?>
    <div class="block px-0">
        <div class="container-fluid">
            <div class="heading-title text-center mb-3 mb-md-4"><span class="text-gradient"><?= $block_honor_roll['ten'] ?></span></div>
        </div>
        <div class="wrapper-slide">
            <div class="swiper honor-roll-slide">
                <div class="swiper-wrapper">
                    <?php foreach ($content_honor_roll as $key => $value): ?>
                        <div class="swiper-slide" style="height: auto;">
                            <div class="box-honor-roll">
                                <div class="box-thumbnail">
                                    <div class="thumbnail ratio ratio-1x1">
                                        <img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">
                                    </div>
                                </div>
                                <div class="box-content">
                                    <h3 class="title"><?= $value['ten'] ?></h3>
                                    <?= $value['ten_phu'] ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
            <div class="swiper-button-next honor-roll-next"></div>
            <div class="swiper-button-prev honor-roll-prev"></div>
        </div>
    </div>
<?php endif ?>



<?php
    $block_review = $d->getContent(738);
    $content_review = $d->getContents(738);
?>

<?php if ($block_review): ?>
    <div class="block">
        <div class="container-fluid">
            <div class="heading-content heading-gradient text-center mb-3"><?= $block_review['noi_dung'] ?></div>
            <div class="wrapper-slide">
                <div class="swiper review-slide">
                    <div class="swiper-wrapper">
                        <?php foreach ($content_review as $key => $value): ?>
                            <div class="swiper-slide" style="height: auto;">
                                <div class="box-review">
                                    <div class="box-top">
                                        <div class="thumbnail ratio ratio-1x1">
                                            <img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">
                                        </div>
                                        <div class="name"><?= $value['ten'] ?></div>
                                    </div>
                                    <div class="box-bottom"><?= $value['noi_dung'] ?></div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
                <div class="swiper-pagination swiper-pagination-simple pagination-review-slide mt-2"></div>
            </div>
        </div>
        <div class="bg-cover" style="background-color: #FAFAFA;"></div>
    </div>
<?php endif ?>