<?php 
    $footer = $d->getContent(443);
    $menu_link = $d->getContents(443);
?>

<div class="footer">
    <div class="block footer-top py-5 pb-4">
        <div class="container-fluid">
            <div class="row justify-content-between g-4 g-lg-5">
                <div class="col-12 col-lg-auto">
                    <img src="<?= getImageUrl($footer['hinh_anh']) ?>" class="logo-footer mb-4" alt="Logo">
                    <div class="social-contact social-contact-footer">
                        <span>Theo dõi chúng tôi tại: </span>
                        <?php if ($info['facebook']): ?>
                            <a href="<?= $info['facebook'] ?>" class="item">
                                <i class="fa-brands fa-facebook-f"></i>
                            </a>
                        <?php endif ?>
                        <?php if ($info['twitter']): ?>
                            <a href="<?= $info['twitter'] ?>" class="item">
                                <i class="fa-brands fa-twitter"></i>
                            </a>
                        <?php endif ?>
                        <?php if ($info['linkedin']): ?>
                            <a href="<?= $info['linkedin'] ?>" class="item">
                                <i class="fa-brands fa-linkedin-in"></i>
                            </a>
                        <?php endif ?>
                        <?php if ($info['whatsapp']): ?>
                            <a href="<?= $info['whatsapp'] ?>" class="item">
                                <i class="fa-brands fa-whatsapp"></i>
                            </a>
                        <?php endif ?>
                        <?php if ($info['zalo']): ?>
                            <a href="<?= $info['zalo'] ?>" class="item item-zalo">
                                <span>Zalo</span>
                            </a>
                        <?php endif ?>
                        <?php if ($info['tiktok']): ?>
                            <a href="<?= $info['tiktok'] ?>" class="item">
                                <i class="fa-brands fa-tiktok"></i>
                            </a>
                        <?php endif ?>
                        <?php if ($info['youtube']): ?>
                            <a href="<?= $info['youtube'] ?>" class="item">
                                <i class="fa-brands fa-youtube"></i>
                            </a>
                        <?php endif ?>
                    </div>
                </div>
                <div class="col-md-auto col-lg-3">
                    <h4 class="title-footer"><?= $menu_link[0]['ten'] ?></h4>
                    <div class="list-footer"><?= $menu_link[0]['noi_dung'] ?></div>
                    <a href="tel:<?= $info['hotline'] ?>" class="box-hotline-footer mt-2">
                        <div class="icon"><i class="fa-solid fa-phone"></i></div>
                        <div class="label"><?= $info['hotline'] ?></div>
                    </a>
                </div>
                <div class="col-md-auto col-lg-auto">
                    <h4 class="title-footer"><?= $menu_link[1]['ten'] ?></h4>
                    <div class="list-footer"><?= $menu_link[1]['noi_dung'] ?></div>
                </div>
                <div class="col-md-auto col-lg-auto">
                    <h4 class="title-footer"><?= $menu_link[2]['ten'] ?></h4>
                    <div class="list-footer"><?= $menu_link[2]['noi_dung'] ?></div>
                </div>
            </div>
        </div>
    </div>

    <?php /* ?>
    <div class="block footer-bottom">
        <div class="container-fluid">
            <?= $info['coppy_right'] ?> | <a href="https://phuongnamvina.vn" rel="nofollow" target="_blank"><?= $d->getTxt(131) ?></a>
        </div>
    </div>
    <?php */ ?>
    <?php if ($footer['background']): ?>
        <div class="bg-cover">
            <img src="<?= getImageUrl($footer['background']) ?>" alt="bg-footer" class="image-cover">
        </div>
    <?php endif ?>
</div>

<!-- ===== Quick View Modal ===== -->
<div class="modal fade" id="quickViewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content qv-content">
            <button type="button" class="btn-close qv-close" data-bs-dismiss="modal" aria-label="Close"></button>
            <div class="modal-body p-0" id="quickViewBody">
                <div class="qv-loading text-center py-5">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="mt-2 text-muted">Đang tải...</p>
                </div>
            </div>
        </div>
    </div>
</div>