<?php 
$list_video = VideoModel::query()
    ->where('id_loai', $row->id_code)
    ->where('hien_thi', 1)
    ->get();
?>
<!-- ==================== End Navbar ==================== -->
<div class="circle-bg">
    <div class="circle-color fixed">
        <div class="gradient-circle"></div>
        <div class="gradient-circle two"></div>
    </div>
</div>
<!-- ==================== Start header ==================== -->
<header class="works-header fixed-slider hfixd valign sub-bg">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-7 col-md-9 static">
                <div class="capt mt-50">
                    <div class="parlx text-center">
                        <h1 class="color-font"><?= $row->ten ?></h1>
                    </div>
                    <div class="bactxt custom-font valign">
                        <span>  </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="line bottom right"></div>
</header>
<!-- ==================== End header ==================== -->
<div class="main-content">
    <!-- ==================== Start works ==================== -->
    <section class="portfolio three-column section-padding pb-70">
        <div class="container">
            <div class="row">
                <!-- gallery -->
                <div class="gallery full-width">
                    <?php if (!empty($list_video)): ?>
                        <?php foreach ($list_video as $value): ?>
                        <div class="col-lg-6 col-md-6 items">
                            <?php if(!empty($value->video)): ?>
                                <div class="item-img wow fadeInUp" data-wow-delay=".2s">
                                    <a data-fancybox href="#video<?= $value->id ?>">
                                        <img src="<?= getImageUrl($value->hinh_anh) ?>" alt="<?= $value->ten ?>">
                                        <span class="fas fa-play play-overlay"></span>
                                    </a>
                                </div>
                                <div class="cont">
                                    <h6><?= $value->ten ?></h6>
                                </div>
                                <video width="90%" height="90%" controls id="video<?= $value->id ?>" style="display:none;">
                                    <source src="<?= getImageUrl($value->video) ?>" type="video/mp4">
                                </video>
                            <?php elseif(!empty($value->ma_video)): ?>
                                <div class="item-img wow fadeInUp" data-wow-delay=".2s">
                                    <a data-fancybox href="https://www.youtube.com/watch?v=<?= $value->ma_video ?>">
                                        <img src="<?= getImageUrl($value->hinh_anh) ?>" alt="<?= $value->ten ?>">
                                        <span class="fas fa-play play-overlay"></span>
                                    </a>
                                </div>
                                <div class="cont">
                                    <h6><?= $value->ten ?></h6>
                                </div>
                            <?php else: ?>
                                <div class="item-img wow fadeInUp" data-wow-delay=".2s">
                                    <a href="<?= getImageUrl($value->hinh_anh) ?>" data-fancybox="images">
                                        <img src="<?= getImageUrl($value->hinh_anh) ?>" alt="<?= $value->ten ?>">
                                    </a>
                                </div>
                                <div class="cont">
                                    <h6><?= $value->ten ?></h6>
                                </div>
                            <?php endif; ?>
                        </div> 
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="col-12 text-center py-5">
                            <p><?= $d->getTxt(141) ?: 'Nội dung đang được cập nhật' ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>

<style>
.play-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 100;
    color: #fff;
    width: 70px;
    height: 70px;
    line-height: 70px;
    background-color: rgba(6, 6, 6, 0.65);
    border-radius: 50%;
    text-align: center;
    font-size: 29px;
    padding-left: 9px;
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.5);
}
</style>