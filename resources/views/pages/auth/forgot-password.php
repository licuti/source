<?php
$params = getQueryParams();

if (isset($_POST['quenpass']) && $_POST['_token'] == $_SESSION['token']) {
    token();
    $email = validate_content($_POST['email']);
    $email_md5 = md5($email);
    
    $exists = $d->num_rows("SELECT id FROM #_thanhvien WHERE md5_email = '$email_md5'");
    
    if ($exists > 0) {
        $row_tv_raw = $d->simple_fetch("SELECT id, email, ho_ten FROM #_thanhvien WHERE md5_email = '$email_md5'");
        if ($row_tv_raw) {
            $row_tv = (object)$row_tv_raw;
            $token_reset = $_POST['_token'];
            
            $d->reset();
            $d->setTable('#_thanhvien');
            $d->setWhere('id', $row_tv->id);
            if ($d->update(['token' => $token_reset, 'trang_thai' => 3])) {
                $reset_link = URLPATH . '?com=' . $com . '&verificationid=' . $token_reset . '&confirmid=' . $email_md5;
                $noidung = "
                <div style='width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 8px;'>
                    <h2 style='color: #333;'>Xin chào {$row_tv->ho_ten}!</h2>
                    <p>Bạn đã yêu cầu đặt lại mật khẩu tại <b>" . _web_page . "</b>.</p>
                    <p>Vui lòng click vào nút bên dưới để tạo mật khẩu mới:</p>
                    <div style='text-align: center; margin-top: 30px;'>
                        <a href='$reset_link' style='background: #f59e0b; color: #fff; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>ĐẶT LẠI MẬT KHẨU</a>
                    </div>
                    <p style='margin-top: 30px; font-size: 0.8em; color: #777;'>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email này.</p>
                </div>";
                
                if (send_email($email, _ten_cong_ty . " - Đặt lại mật khẩu", $noidung)) {
                    $thongbao_tt      = 'Yêu cầu thành công';
                    $thongbao_icon    = 'success';
                    $thongbao_content = 'Vui lòng kiểm tra email để tiếp tục.';
                    $thongbao_url     = URLPATH;
                } else {
                    $thongbao_tt      = 'Lỗi hệ thống';
                    $thongbao_icon    = 'error';
                    $thongbao_content = 'Không thể gửi email xác nhận. Thử lại sau.';
                    $thongbao_url      = URLPATH;
                }
            }
        }
    } else {
        $thongbao_tt      = 'Lỗi yêu cầu';
        $thongbao_icon    = 'error';
        $thongbao_content = 'Email này chưa được đăng ký trong hệ thống.';
        $thongbao_url      = URLPATH;
    }
}

// Password update logic
if (isset($_POST['doipasss']) && $_POST['_token'] == $_SESSION['token'] && !empty($_SESSION['confirmid'])) {
    token();
    $confirm_id = validate_content($_SESSION['confirmid']);
    $verify_id = validate_content($_SESSION['verificationid']);
    
    $thanhvien_raw = $d->simple_fetch("SELECT * FROM #_thanhvien WHERE md5_email = '$confirm_id' AND token = '$verify_id' AND trang_thai = 3");
    
    if ($thanhvien_raw) {
        $thanhvien = (object)$thanhvien_raw;
        if ($_POST['mat_khau'] == $_POST['mat_khau2']) {
            $update_data = [
                'mat_khau'   => md5($_POST['mat_khau']),
                'token'      => '',
                'trang_thai' => 1
            ];
            
            $d->reset();
            $d->setTable('#_thanhvien');
            $d->setWhere('id', $thanhvien->id);
            if ($d->update($update_data)) {
                unset($_SESSION['confirmid'], $_SESSION['verificationid']);
                $thongbao_tt      = 'Thành công';
                $thongbao_icon    = 'success';
                $thongbao_content = 'Mật khẩu đã được cập nhật. Bạn có thể đăng nhập ngay.';
                $thongbao_url     = URLPATH . $d->getCate(21, 'alias') . ".html";
            }
        }
    } else {
        $thongbao_tt      = 'Lỗi thực thi';
        $thongbao_icon    = 'error';
        $thongbao_content = 'Yêu cầu không hợp lệ hoặc đã hết hạn.';
        $thongbao_url     = URLPATH . $d->getCate(21, 'alias') . ".html";
    }
}

// Link verification
if (isset($params['verificationid'])) {
    $v_id = validate_content($params['verificationid']);
    $c_id = validate_content($params['confirmid']);
    
    $check_raw = $d->simple_fetch("SELECT id FROM #_thanhvien WHERE md5_email = '$c_id' AND token = '$v_id' AND trang_thai = 3");
    if ($check_raw) {
        $_SESSION['verificationid'] = $v_id;
        $_SESSION['confirmid']      = $c_id;
        $d->location(URLPATH . $com . '.html?reset-password=1');
        exit;
    }
}

// Cleanup / Guard
if (isset($_SESSION['confirmid'])) {
    $confirm_check = $d->num_rows("SELECT id FROM #_thanhvien WHERE md5_email = '" . $_SESSION['confirmid'] . "' AND token = '" . $_SESSION['verificationid'] . "' AND trang_thai = 3");
    if ($confirm_check == 0) {
        unset($_SESSION['confirmid'], $_SESSION['verificationid']);
        $d->location(URLPATH . "404.html");
        exit;
    }
} else if (isset($params['reset-password'])) {
    $d->location(URLPATH . $com . ".html");
    exit;
}

$reset_mode = isset($_SESSION['confirmid']);
?>

<main class="py-5 bg-light min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-8 col-lg-10 shadow-lg bg-white rounded overflow-hidden p-0 d-flex flex-wrap flex-md-nowrap">
                
                <div class="col-md-6 d-none d-md-block p-0">
                    <img src="<?= getImageUrl($row->hinh_anh) ?>" alt="Password" class="w-100 h-100 object-fit-cover">
                </div>
                
                <div class="col-md-6 p-4 p-lg-5">
                    <div class="mb-4">
                        <h1 class="h3 fw-bold mb-2"><?= $reset_mode ? $d->getTxt(85) : $row->ten ?></h1>
                        <p class="text-muted small">Vui lòng điền thông tin để tiếp tục.</p>
                    </div>

                    <?php if ($reset_mode): ?>
                        <?php
                            $thanhvien_email = $d->simple_fetch("SELECT email FROM #_thanhvien WHERE md5_email = '" . $_SESSION['confirmid'] . "'")['email'] ?? '';
                        ?>
                        <form method="POST" action="" id="form-quenpass">
                            <input type="hidden" value="<?= $_SESSION['token'] ?>" name="_token" />
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Email</label>
                                <input type="text" readonly value="<?= $thanhvien_email ?>" class="form-control-plaintext border-bottom px-2">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold"><?= $d->getTxt(66) ?></label>
                                <input type="password" required id="matkhau" name="mat_khau" class="form-control" placeholder="Mật khẩu mới">
                            </div>
                            <div class="mb-4">
                                <label class="form-label small fw-bold"><?= $d->getTxt(67) ?></label>
                                <input type="password" required name="mat_khau2" class="form-control" placeholder="Xác nhận mật khẩu">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 shadow-sm" name="doipasss"><?= $d->getTxt(86) ?></button>
                        </form>
                    <?php else: ?>
                        <form method="POST" action="" id="form-dangnhap">
                            <input type="hidden" value="<?= $_SESSION['token'] ?>" name="_token" />
                            <div class="mb-4">
                                <label class="form-label small fw-bold">Email đăng ký</label>
                                <input type="email" required name="email" class="form-control" placeholder="example@email.com">
                            </div>
                            <button type="submit" class="btn btn-primary w-100 shadow-sm" name="quenpass"><?= $d->getTxt(84) ?></button>
                            <div class="text-center mt-3">
                                <a href="<?= URLPATH . $d->getCate(21, 'alias') ?>.html" class="text-decoration-none small text-secondary">
                                    <i class="fas fa-arrow-left me-1"></i> Quay lại đăng nhập
                                </a>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</main>