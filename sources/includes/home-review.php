<?php
    $block_review = $d->getContent(738);
    $content_review = $d->getContents(738);
?>

<?php if ($block_review): ?>
    <div class="block">
        <div class="container-fluid">
            <div class="heading-content text-center mb-3">
                <?= $block_review['noi_dung'] ?>
            </div>
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