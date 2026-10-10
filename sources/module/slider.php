<?php
    $slider = $d->getContents(68);
?>

<div class="swiper main-slide">
    <div class="swiper-wrapper">
        <?php foreach ($slider as $key => $value): ?>
            <div class="swiper-slide">
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
                            <div class="d-flex gap-3 mt-4">
                                <a href="" class="btn-custom btn-x btn-solid">Test đầu vào miễn phí <svg xmlns="http://www.w3.org/2000/svg" width="10" height="14" fill="none" viewBox="0 0 10 14">
                                    <path fill="#fff" d="M5.506 3.303 9.794 6.63c.275.213.275.527 0 .74L1.507 13.8C.99 14.201 0 13.958 0 13.43V7.707z"/>
                                    <path fill="#fff" d="M0 6.293V.57C0 .042.989-.2 1.506.2l3.101 2.406z" opacity=".5"/>
                                </svg>
                                </a>
                                <a href="" class="btn-custom btn-x btn-outline">Các khóa học tại Master Chuyên Anh
                                <svg xmlns="http://www.w3.org/2000/svg" width="10" height="14" fill="none" viewBox="0 0 10 14">
                                    <path fill="#000F3F" d="M5.506 3.303 9.794 6.63c.275.213.275.527 0 .74L1.507 13.8C.99 14.201 0 13.958 0 13.43V7.707z"/>
                                    <path fill="#000F3F" d="M0 6.293V.57C0 .042.989-.2 1.506.2l3.101 2.406z" opacity=".5"/>
                                </svg>
                                </a>
                            </div>

                            <h3 class="fw-bold text-x text-center mt-5 mb-4">REVIEW CỦA HỌC VIÊN</h3>

                            <div class="wrapper-slide">
                                <div class="swiper main-slide-review">
                                    <div class="swiper-wrapper">

                                            <div class="swiper-slide" style="height: 100%;">
                                                <div class="box-review-slide">
                                                    <div class="name">Nguyễn Văn A</div>
                                                    <div class="vote">
                                                        <i class="fa-solid fa-star"></i>
                                                        <i class="fa-solid fa-star"></i>
                                                        <i class="fa-solid fa-star"></i>
                                                        <i class="fa-solid fa-star"></i>
                                                        <i class="fa-solid fa-star"></i>
                                                    </div>
                                                    <div class="comment">Chất lượng dạy rất OK!</div>
                                                </div>
                                            </div>
                                            <div class="swiper-slide" style="height: 100%;">
                                                <div class="box-review-slide">
                                                    <div class="name">Nguyễn Văn A</div>
                                                    <div class="vote">
                                                        <i class="fa-solid fa-star"></i>
                                                        <i class="fa-solid fa-star"></i>
                                                        <i class="fa-solid fa-star"></i>
                                                        <i class="fa-solid fa-star"></i>
                                                        <i class="fa-solid fa-star"></i>
                                                    </div>
                                                    <div class="comment">Chất lượng dạy rất OK!</div>
                                                </div>
                                            </div>
                                            <div class="swiper-slide" style="height: 100%;">
                                                <div class="box-review-slide">
                                                    <div class="name">Nguyễn Văn A</div>
                                                    <div class="vote">
                                                        <i class="fa-solid fa-star"></i>
                                                        <i class="fa-solid fa-star"></i>
                                                        <i class="fa-solid fa-star"></i>
                                                        <i class="fa-solid fa-star"></i>
                                                        <i class="fa-solid fa-star"></i>
                                                    </div>
                                                    <div class="comment">Chất lượng dạy rất OK!</div>
                                                </div>
                                            </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif ?>
                    
                
            </div>
        <?php endforeach ?>
    </div>
    <!-- <div class="swiper-button-next main-slide-next"></div>
    <div class="swiper-button-prev main-slide-prev"></div> -->
</div>