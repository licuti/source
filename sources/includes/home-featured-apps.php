<?php
    $block_app = $d->getContent(576);
    $content_app = $d->getContents(576);
?>

<?php if ($block_app): ?>
    <div class="block">
        <div class="container-fluid">
            <h2 class="heading-title text-center mb-3 mb-md-4 pb-3"><?= $block_app['ten'] ?></h2>
            <div class="row">
                <?php foreach ($content_app as $key => $value): ?>
                    <div class=""></div>
                <?php endforeach ?>
            </div>
            <div class="wrapper-slide">
                <div class="swiper app-slide">
                    <div class="swiper-wrapper">
                        <?php foreach ($content_app as $key => $value): ?>
                            <div class="swiper-slide" style="height: auto;">
                                <div class="box-app">
                                    <div class="box-thumbnail">
                                        <div class="inner-thumbnail ratio ratio-4x3">
                                            <img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">
                                        </div>
                                    </div>
                                    <div class="box-content pt-2">
                                        <h3 class="title fs-6 fw-semibold"><?= $value['ten'] ?></h3>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif ?>