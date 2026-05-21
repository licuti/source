<?php
require_once './library/vendor/autoload.php';
use \Firebase\JWT\JWT;
$jwt_key = "205049";

$params = getQueryParams();
$url_back = !empty($params['url']) ? URLPATH . $params['url'] . '.html' : URLPATH;

if (isset($_POST['login']) && $_SESSION['token'] == $_POST['_token']) {
    $email = validate_content($_POST['email']);
    $md5_email = md5($email);
    $matkhau = md5($_POST['password']);

    // Check credentials
    $thanhvien_raw = $d->simple_fetch("SELECT * FROM #_thanhvien WHERE md5_email = '$md5_email' AND mat_khau = '$matkhau'");
    
    if ($thanhvien_raw) {
        $thanhvien = (object)$thanhvien_raw;
        
        if ($thanhvien->trang_thai == 1) {
            $_SESSION['id_login'] = $thanhvien->id;
            $_SESSION['thanhvien_login'] = $thanhvien->ho_ten;
            $_SESSION['type'] = $thanhvien->loai;

            if (isset($_POST['save'])) {
                $token = $thanhvien->token;
                if (empty($token)) {
                    $token = randomString(16);
                    $d->reset();
                    $d->setTable('#_thanhvien');
                    $d->setWhere('id', $thanhvien->id);
                    $d->update(['token' => $token]);
                }

                $payload = [
                    'id_login'   => $thanhvien->id,
                    'user_login' => $thanhvien->ho_ten,
                    'type'       => $thanhvien->loai,
                    'token'      => $token,
                    'iat'        => time(),
                    'exp'        => time() + (60 * 60 * 24 * 365)
                ];
                $jwt = JWT::encode($payload, $jwt_key, 'HS256');
                setrawcookie("keyId", urlencode($jwt), time() + (60 * 60 * 24 * 365), "/", NULL, FALSE, TRUE);
            }

            $thongbao_tt      = 'Đăng nhập thành công';
            $thongbao_icon    = 'success';
            $thongbao_content = 'Chào mừng ' . $thanhvien->ho_ten;
            $thongbao_url     = $url_back;
        } else {
            $thongbao_tt      = 'Lỗi đăng nhập';
            $thongbao_icon    = 'error';
            $thongbao_content = 'Tài khoản của bạn chưa được kích hoạt.';
            $thongbao_url     = _URLLANG;
        }
    } else {
        $thongbao_tt      = 'Lỗi đăng nhập';
        $thongbao_icon    = 'error';
        $thongbao_content = 'Email hoặc mật khẩu không chính xác.';
        $thongbao_url     = _url_page;
    }
}
?>

<main class="py-5 bg-light min-vh-100 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-9 col-lg-10 shadow-lg bg-white rounded overflow-hidden p-0 d-flex flex-wrap flex-md-nowrap">
                <div class="col-md-6 d-none d-md-block p-0">
                    <img src="<?= getImageUrl($row->hinh_anh) ?>" alt="Login" class="w-100 h-100 object-fit-cover">
                </div>
                <div class="col-md-6 p-4 p-lg-5">
                    <div class="login-header mb-4">
                        <h1 class="h3 fw-bold mb-2"><?= $row->ten ?></h1>
                        <p class="text-muted small">
                            <?= $d->getTxt(61) ?> 
                            <a href="<?= URLPATH . $d->getCate(22, 'alias') ?>.html" class="text-primary text-decoration-none fw-bold">
                                <?= $d->getCate(22, 'ten') ?>
                            </a>
                        </p>
                    </div>

                    <form method="POST" action="" id="form-dangnhap">
                        <input type="hidden" value="<?= $_SESSION['token'] ?>" name="_token" />
                        <div class="mb-3">
                            <label class="form-label small fw-bold"><?= $d->getTxt(31) ?></label>
                            <input type="email" required name="email" class="form-control" placeholder="example@email.com">
                        </div>
                        <div class="mb-4">
                            <label class="form-label small fw-bold"><?= $d->getTxt(66) ?></label>
                            <input type="password" required name="password" class="form-control" placeholder="••••••••">
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="save" id="saveLogin" value="1">
                                <label class="form-check-label small pointer" for="saveLogin"><?= $d->getTxt(87) ?></label>
                            </div>
                            <a href="<?= URLPATH . $d->getCate(23, 'alias') ?>.html" class="small text-secondary text-decoration-none">
                                <?= $d->getCate(23, 'ten') ?>?
                            </a>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 shadow-sm" name="login">Đăng nhập ngay</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</main>