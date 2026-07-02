<?php

if(isset($_POST['dang-ky']) && $_SESSION['token'] == $_POST['_token']){
    token();
    $email = validate_content($_POST['email']);
    $dien_thoai = validate_content($_POST['dien_thoai'] ?? '');
    
    $row_tv_count = $d->num_rows("SELECT id FROM #_thanhvien WHERE email = '$email' OR (dien_thoai = '$dien_thoai' AND dien_thoai != '')");
    
    if($row_tv_count == 0){
        $data = [
            'ho_ten'     => addslashes(replaceHTMLCharacter($_POST['ho_ten'])),
            'email'      => $email,
            'md5_email'  => md5($email),
            'mat_khau'   => md5($_POST['mat_khau']),
            'loai'       => (int)$_POST['loai'],
            'ngay_tao'   => date('Y-m-d H:i:s'),
            'token'      => $_SESSION['token'],
            'trang_thai' => 0
        ];

        $d->reset();
        $d->setTable('#_thanhvien');
        if($idthanhvien = $d->insert($data)) {
            $dem = strlen($idthanhvien);
            $chuoi = str_repeat('0', max(0, 5 - $dem));
            
            $prefix = ($data['loai'] == 1) ? 'CTV-' : '';
            $ma_thanhvien = $prefix . $chuoi . $idthanhvien;
            
            $update_data = ['ma_thanhvien' => $ma_thanhvien];
            if($data['loai'] == 1){
                $update_data['token_gioithieu'] = md5($ma_thanhvien . time());
            }

            $d->reset();
            $d->setTable('#_thanhvien');
            $d->setWhere('id', $idthanhvien);
            if($d->update($update_data)){
                $confirm_link = URLPATH . '?com=' . $com . '&verificationid=' . $data['token'] . '&confirmid=' . $data['md5_email'];
                $noidung = "
                <p>Xin chào {$data['ho_ten']},</p>
                <p>Cảm ơn quý khách đã đăng ký tài khoản tại " . _web_page . ".</p>
                <p>Vui lòng click vào link bên dưới để xác nhận tài khoản:</p>
                <p><a href='$confirm_link'>$confirm_link</a></p>
                <p>Trân trọng!</p>";
                
                if(send_email($email, "Xác nhận đăng ký tại " . _web_page, $noidung)) {
                    $thongbao_tt      = 'Đăng ký thành công';
                    $thongbao_icon    = 'success';
                    $thongbao_content = 'Vui lòng kiểm tra email để xác nhận kích hoạt tài khoản.';
                    $thongbao_url      = URLPATH;
                } else {
                    // Cleanup on mail failure
                    $d->reset();
                    $d->setTable('#_thanhvien');
                    $d->setWhere('id', $idthanhvien);
                    $d->delete();
                    
                    $thongbao_tt      = 'Lỗi hệ thống';
                    $thongbao_icon    = 'error';
                    $thongbao_content = 'Không thể gửi email xác nhận. Vui lòng thử lại sau.';
                    $thongbao_url      = '';
                }
            }
        }    
    } else {
        $thongbao_tt      = 'Lỗi đăng ký';
        $thongbao_icon    = 'error';
        $thongbao_content = 'Email hoặc số điện thoại đã tồn tại.';
        $thongbao_url      = _url_page;
    }
}

// Verification logic
if(isset($_GET['verificationid'])){
    $confirm_id = validate_content($_GET['confirmid']);
    $verify_id = validate_content($_GET['verificationid']);
    
    $thanhvien_raw = $d->simple_fetch("SELECT * FROM #_thanhvien WHERE md5_email = '$confirm_id' AND token = '$verify_id'");
    
    if($thanhvien_raw){
        $thanhvien = (object)$thanhvien_raw;
        $update_status = [
            'token' => '',
            'trang_thai' => ($thanhvien->loai == 0) ? 1 : 2 // 1: Active, 2: Pending for CTV
        ];
        
        $d->reset();
        $d->setTable('#_thanhvien');
        $d->setWhere('id', $thanhvien->id);
        if($d->update($update_status)){
            $thongbao_tt      = 'Hoàn tất xác nhận';
            $thongbao_icon    = 'success';
            $thongbao_content = ($thanhvien->loai == 0) ? 'Tài khoản đã được kích hoạt.' : 'Tài khoản CTV đang chờ xét duyệt.';
            $thongbao_url      = URLPATH . $d->getCate(22, 'alias') . ".html";
        }
    } else {
        $thongbao_tt      = 'Lỗi xác nhận';
        $thongbao_icon    = 'error';
        $thongbao_content = 'Liên kết xác nhận không hợp lệ hoặc đã hết hạn.';
        $thongbao_url      = URLPATH . "dang-ky.html";
    }
}
?>

<main class="py-5 bg-light min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-lg-10 shadow-lg bg-white rounded overflow-hidden p-0 d-flex flex-wrap flex-md-nowrap">
                <div class="col-md-6 p-4 p-lg-5">
                    <div class="registration-header mb-4">
                        <h1 class="h3 fw-bold mb-2"><?= $d->getTxt(63) ?></h1>
                        <p class="text-muted small">
                            <?= $d->getTxt(64) ?> 
                            <a href="<?= URLPATH . $d->getCate(21, 'alias') ?>.html" class="text-primary text-decoration-none fw-bold">
                                <?= $d->getCate(21, 'ten') ?>
                            </a>
                        </p>
                    </div>

                    <form method="post" action="" id="form-dangky">
                        <input type="hidden" value="<?= $_SESSION['token'] ?>" name="_token" />
                        <div class="mb-3">
                            <label class="form-label small fw-bold"><?= $d->getTxt(65) ?></label>
                            <input type="text" required name="ho_ten" class="form-control" placeholder="Họ và tên">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Email</label>
                            <input type="email" required name="email" class="form-control" placeholder="example@email.com">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold"><?= $d->getTxt(66) ?></label>
                                <input type="password" required id="matkhau" name="mat_khau" class="form-control" placeholder="••••••••">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold"><?= $d->getTxt(67) ?></label>
                                <input type="password" required name="mat_khau2" class="form-control" placeholder="••••••••">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small fw-bold d-block">Loại tài khoản</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="loai" id="loai_user" value="0" checked>
                                <label class="form-check-label small" for="loai_user"><?= $d->getTxt(68) ?></label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="loai" id="loai_ctv" value="1">
                                <label class="form-check-label small" for="loai_ctv"><?= $d->getTxt(69) ?></label>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="xacnhan" id="termsCheck" required>
                                <label class="form-check-label small pointer" for="termsCheck">
                                    <span><?= $d->getTxt(70) ?></span>
                                    <a href="<?= URLPATH . $d->getCate(25, 'alias') ?>.html" class="text-decoration-none ms-1 text-primary"><?= $d->getTxt(72) ?></a>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm mb-3" name="dang-ky"><?= $d->getTxt(73) ?></button>
                        <p class="text-muted" style="font-size: 0.75rem;"><strong>Ghi chú:</strong> <?= $d->getTxt(71) ?></p>
                    </form>
                </div>
                <div class="col-md-6 d-none d-md-block p-0">
                    <img src="<?= getImageUrl($row->hinh_anh) ?>" alt="Register" class="w-100 h-100 object-fit-cover">
                </div>
            </div>
        </div>
    </div>
</main>