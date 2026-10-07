<?php
    $block_partner = $d->getContent(668);
    $content_partner = $d->getContents(668);
?>

<?php if ($block_partner): ?>
    <div class="block">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-lg-2">
                    <h2 class="main-title"><?= $block_partner['ten'] ?></h2>
                </div>
                <div class="col-lg-4">
                    <img src="<?= getImageUrl($block_partner['hinh_anh']) ?>" alt="<?= $block_partner['ten'] ?>" class="image-cover">
                </div>
                <div class="col-lg-6">
                    <div class="box-title-main"><?= $block_partner['noi_dung'] ?></div>
                </div>
            </div>
        </div>
        <div class="bg-cover" style="background-color: #F2F9F0;"></div>
    </div>
    <div class="block px-0 py-2">
        <div class="wrapper-slide">
            <div class="swiper partner-slide">
                <div class="swiper-wrapper">
                    <?php foreach ($content_partner as $key => $value): ?>
                        <div class="swiper-slide">
                            <div class="box-img-partner">
                                <img src="<?= getImageUrl($value['hinh_anh']) ?>" alt="<?= $value['ten'] ?>" class="image-cover">
                            </div>
                        </div>
                    <?php endforeach ?>
                </div>
            </div>
        </div>
        <div class="bg-cover" style="background-color: #F2F9F0;"></div>
    </div>
<?php endif ?>