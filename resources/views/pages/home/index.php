<?php
/**
 * View: Trang chủ
 */
?>

<section class="hero">
    <h1>Chào mừng đến với <?= config('app.name') ?></h1>
    <p>Giải pháp CMS hiện đại dựa trên kiến trúc Laravel.</p>
</section>

<?php if ($featuredProducts): ?>
    <section class="block">
        <div class="container-fluid">
            <div class="row mb-3 mb-md-4">
                <div class="col-12">
                    <h2 class="main-title text-center"><span class="fw-normal">SẢN PHẨM</span> <span class="text-x">CỦA CHÚNG TÔI</span></h2>
                </div>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="swiper swiper-button-circle hover-show-button product-slide p-1">
                        <div class="swiper-wrapper">
                            <?php foreach ($featuredProducts as $key => $value): ?>
                                <div class="swiper-slide">
                                    <?= view('partials/components/card-product', ['value' => $value]); ?>
                                </div>
                            <?php endforeach ?>
                        </div>
                        <div class="swiper-button-next product-next"><i class="fa-solid fa-arrow-right-long"></i></div>
                        <div class="swiper-button-prev product-prev"><i class="fa-solid fa-arrow-left-long"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </section>
<?php endif ?>


<div class="block">
    <div class="container-fluid">
        <div class="row mb-3 mb-md-4">
            <div class="col-12">
                <h2 class="main-title text-x">TIN TỨC</h2>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="swiper swiper-button-circle hover-show-button post-slide">
                    <div class="swiper-wrapper">
                        <?php foreach ($latestNews as $key => $value): ?>
                            <div class="swiper-slide">
                                <?php echo view('partials/components/card-post', ['value' => $value]); ?>
                            </div>
                        <?php endforeach ?>
                    </div>
                    <div class="swiper-button-prev post-prev">
                        <i class="fa-solid fa-arrow-left-long"></i>
                    </div>
                    <div class="swiper-button-next post-next">
                        <i class="fa-solid fa-arrow-right-long"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
