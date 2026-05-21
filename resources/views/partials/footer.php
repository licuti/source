<?php 
    if(isset($_POST['contact-footer']) && ($_SESSION['token'] ?? '') == ($_POST['_token'] ?? '') ){
        $email = validate_content($_POST['email'] ?? '');

        if (!empty($email)) {
            $contact_footer = [
                'email'   => $email,
                'tieu_de' => "ĐĂNG KÝ NHẬN TIN",
            ];

            if (!isset($_SESSION['customer_send_mail_footer'][$_POST['_token']])) {
                $_SESSION['customer_send_mail_footer'][$_POST['_token']] = 0;
            }
            if ($_SESSION['customer_send_mail_footer'][$_POST['_token']] < 3){
                $_SESSION['customer_send_mail_footer'][$_POST['_token']]++;
                if(\ContactModel::create($contact_footer)) {
                    $thongbao_tt      = "Thành công";
                    $thongbao_icon    = 'success';
                    $thongbao_content = "Đăng ký nhận tin thành công!";
                    $thongbao_url     = url();
                }
            }
        } else {
            $thongbao_tt      = 'Error';
            $thongbao_icon    = 'error';
            $thongbao_content = 'Vui lòng nhập đầy đủ thông tin!';
            $thongbao_url     = url();
        }
    }

    $footer = \ContentModel::query()->setTable('#_category_noidung')->where('id_code', 443)->where('hien_thi', 1)->first();
    $menu_link = \ContentModel::where('id_loai', 443)->where('hien_thi', 1)->orderBy('so_thu_tu', 'ASC')->get();
?>

<div class="footer text-light bg-dark pt-5">
    <div class="block py-5">
        <div class="container-fluid">
            <div class="row g-4 g-lg-5">
                <div class="col-md-6 col-lg-3">
                    <?php if ($footer && !empty($footer->hinh_anh)): ?>
                        <img src="<?= getImageUrl($footer->hinh_anh) ?>" class="logo-footer mb-4" alt="Logo" style="max-width: 150px;">
                    <?php endif; ?>
                    <h4 class="title-footer mb-3"><?= $menu_link[0]->ten ?? 'Bản tin' ?></h4>
                    <form class="form-booking-footer" method="post" action="">
                        <input type="hidden" value="<?= $_SESSION['token'] ?? '' ?>" name="_token" />
                        <div class="input-group">
                            <input type="email" name="email" class="form-control" placeholder="Email của bạn" required>
                            <button type="submit" name="contact-footer" class="btn btn-primary">Gửi</button>
                        </div>
                    </form>
                </div>
                <?php for ($i = 1; $i <= 3; $i++): ?>
                    <?php if (isset($menu_link[$i])): ?>
                    <div class="col-md-6 col-lg-<?= $i == 1 ? '4' : ($i == 2 ? '2' : '3') ?>">
                        <h4 class="title-footer mb-3"><?= $menu_link[$i]->ten ?></h4>
                        <div class="list-footer small opacity-75">
                            <?= htmlspecialchars_decode($menu_link[$i]->noi_dung) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endfor; ?>
            </div>
        </div>
    </div>
    <div class="block footer-bottom py-3 border-top border-secondary">
        <div class="container-fluid">
            <div class="row flex-lg-row-reverse justify-content-between align-items-center g-3">
                <div class="col-lg-auto">
                    <div class="social-contact social-contact-footer d-flex gap-3 justify-content-center">
                        <?php 
                        $socials = ['facebook', 'twitter', 'linkedin', 'whatsapp', 'zalo', 'tiktok', 'youtube'];
                        foreach ($socials as $social):
                            $social_link = site($social);
                            if (!empty($social_link)):
                        ?>
                            <a href="<?= $social_link ?>" class="text-light opacity-75 hover-opacity-100 fs-5">
                                <i class="fa-brands fa-<?= $social == 'zalo' ? 'zalo' : ($social == 'youtube' ? 'youtube' : $social) ?>"></i>
                            </a>
                        <?php 
                            endif;
                        endforeach; 
                        ?>
                    </div>
                </div>
                <div class="col-lg-auto coppyright small opacity-50">
                    <?= site('coppy_right') ?> | <a href="https://phuongnamvina.vn" class="text-light text-decoration-none" rel="nofollow">Thiết kế web: Phương Nam Vina</a>
                </div>
            </div>
        </div>
    </div>
    <?php if ($footer && !empty($footer->background)): ?>
        <div class="bg-cover" style="opacity: 0.1;">
            <img src="<?= getImageUrl($footer->background) ?>" alt="bg-footer" class="image-cover position-absolute top-0 start-0 w-100 h-100 object-fit-cover">
        </div>
    <?php endif ?>
</div>

<!-- Quick View Modal -->
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