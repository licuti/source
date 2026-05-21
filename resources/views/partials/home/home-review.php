<?php
    $block_review = $d->getContent(528);
    $content_review = $d->getContents(528);
?>

<?php if ($content_review): ?>
    <div class="block bg-x">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="swiper swiper-button-circle swiper-pagination-white hover-show-button review-slide">
                        <div class="swiper-wrapper">
                            <?php foreach ($content_review as $key => $value): ?>
                                <div class="swiper-slide">
                                    <div class="card-review">
                                        <div class="thumbnail">
                                            <div class="ratio ratio-1x1">
                                                <getImageUrl src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">
                                            </div>
                                        </div>
                                        <div class="content">
                                            <div class="box-quote"><?= $value['noi_dung'] ?></div>
                                            <h5 class="title"><?= $value['ten'] ?></h5>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach ?>
                        </div>
                        <div class="swiper-pagination"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>