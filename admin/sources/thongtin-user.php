<?php
/**
 * Module: Thông tin user đang đăng nhập (self-service profile).
 * Không checkPermission_edit module — chỉ sửa đúng tài khoản trong session.
 */
if (!defined('_source')) {
    die('Error');
}

$a = isset($_REQUEST['a']) ? addslashes($_REQUEST['a']) : '';

switch ($a) {
    case 'man':
    case 'edit':
        showdulieu();
        $template = $_REQUEST['p'] . '/hienthi';
        break;
    case 'save':
        luudulieu();
        break;
    default:
        $template = 'index';
}

function thongtin_user_back()
{
    global $d;
    $d->redirect('index.php?p=thongtin-user&a=man');
}

function thongtin_user_id()
{
    return isset($_SESSION['id_user']) ? (int) $_SESSION['id_user'] : 0;
}

/** Mật khẩu: 8-20 ký tự, thường + HOA + số + !@#$%^&* */
function thongtin_user_password_ok($password)
{
    $password = (string) $password;
    $len = strlen($password);
    if ($len < 8 || $len > 20) {
        return false;
    }
    return (bool) preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[!@#$%^&*])(?=.*[A-Z]).{8,20}$/', $password);
}

function thongtin_user_logout_to_login()
{
    global $d;

    if (function_exists('admin_login_flash_set')) {
        admin_login_flash_set('pwd_ok');
    }

    $_SESSION = array();
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            isset($p['path']) ? $p['path'] : '/',
            isset($p['domain']) ? $p['domain'] : '',
            !empty($p['secure']),
            !empty($p['httponly'])
        );
    }
    @session_destroy();

    if (!empty($_COOKIE['key_ad'])) {
        setrawcookie('key_ad', '0', time() - 3600, '/', null, null, true);
    }

    $d->redirect('login.php');
}

function showdulieu()
{
    global $d, $items, $userRow;

    $uid = thongtin_user_id();
    $userRow = array();
    $items = array();

    if ($uid <= 0) {
        return;
    }

    $row = $d->safeFetchOne('select * from #_user where id = ?', array($uid));
    if (is_array($row) && !empty($row['id'])) {
        $userRow = $row;
        $items = array($row);
    }
}

function luudulieu()
{
    global $d;

    $uid = thongtin_user_id();
    if ($uid <= 0) {
        $d->redirect('login.php');
        return;
    }

    // CSRF
    if (!function_exists('admin_csrf_verify') || !admin_csrf_verify()) {
        admin_flash('error', 'Phiên làm việc không hợp lệ (CSRF). Vui lòng tải lại trang và thử lại.');
        thongtin_user_back();
        return;
    }

    $current = $d->safeFetchOne(
        'select id, pass_hash, token, tai_khoan, quyen_han, is_admin, hien_thi from #_user where id = ?',
        array($uid)
    );
    if (!is_array($current) || empty($current['id'])) {
        admin_flash('error', 'Không tìm thấy tài khoản.');
        thongtin_user_back();
        return;
    }

    $hoTen   = isset($_POST['ho_ten']) ? trim((string) $_POST['ho_ten']) : '';
    $email   = isset($_POST['email']) ? trim((string) $_POST['email']) : '';
    $noiDung = isset($_POST['noi_dung']) ? (string) $_POST['noi_dung'] : '';
    $hinhAnh = isset($_POST['hinh_anh']) ? (string) $_POST['hinh_anh'] : '';

    if ($hoTen === '') {
        admin_flash('error', 'Vui lòng nhập họ tên.');
        thongtin_user_back();
        return;
    }
    if (function_exists('mb_strlen')) {
        if (mb_strlen($hoTen, 'UTF-8') > 150) {
            admin_flash('error', 'Họ tên quá dài (tối đa 150 ký tự).');
            thongtin_user_back();
            return;
        }
    } elseif (strlen($hoTen) > 150) {
        admin_flash('error', 'Họ tên quá dài (tối đa 150 ký tự).');
        thongtin_user_back();
        return;
    }

    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        admin_flash('error', 'Email không hợp lệ.');
        thongtin_user_back();
        return;
    }

    // HTML giới thiệu: lọc script/iframe/on*
    $noiDung = $d->xss_filterContent($noiDung);

    // Path ảnh an toàn
    $hinhAnh = function_exists('admin_sanitize_image_path')
        ? admin_sanitize_image_path($hinhAnh)
        : '';

    // clear() cho text thuần; noi_dung đã qua xss_filterContent (giữ HTML an toàn)
    $data = array(
        'ho_ten'   => $d->clear($hoTen),
        'email'    => $d->clear($email),
        'noi_dung' => $noiDung,
        'hinh_anh' => $hinhAnh,
    );

    $wantPassword = (
        (isset($_POST['password']) && (string) $_POST['password'] !== '')
        || (isset($_POST['old-password']) && (string) $_POST['old-password'] !== '')
        || (isset($_POST['cfpassword']) && (string) $_POST['cfpassword'] !== '')
    );

    if ($wantPassword) {
        $rateKey = 'tt_user_pwd:' . $uid;

        if (!admin_rate_allow($rateKey, 5, 900)) {
            admin_flash('error', 'Bạn đã thử sai mật khẩu quá nhiều lần. Vui lòng thử lại sau 15 phút.');
            thongtin_user_back();
            return;
        }

        $oldPassword = isset($_POST['old-password']) ? (string) $_POST['old-password'] : '';
        $password    = isset($_POST['password']) ? (string) $_POST['password'] : '';
        $rePassword  = isset($_POST['cfpassword']) ? (string) $_POST['cfpassword'] : '';

        if ($oldPassword === '') {
            admin_flash('error', 'Vui lòng nhập mật khẩu cũ.');
            thongtin_user_back();
            return;
        }
        if ($password === '') {
            admin_flash('error', 'Vui lòng nhập mật khẩu mới.');
            thongtin_user_back();
            return;
        }
        if (!thongtin_user_password_ok($password)) {
            admin_flash('error', 'Mật khẩu mới chưa đúng định dạng (8-20 ký tự, có chữ thường, HOA, số và ký tự đặc biệt !@#$%^&*).');
            thongtin_user_back();
            return;
        }
        if (!hash_equals($password, $rePassword)) {
            admin_flash('error', 'Mật khẩu nhập lại chưa đúng.');
            thongtin_user_back();
            return;
        }

        $token = isset($current['token']) ? $current['token'] : '';
        if (!$d->verifyPassword($oldPassword, $current['pass_hash'], $token)) {
            admin_rate_hit($rateKey, 900);
            admin_flash('error', 'Mật khẩu cũ chưa đúng.');
            thongtin_user_back();
            return;
        }
        if (hash_equals($oldPassword, $password)) {
            admin_flash('error', 'Mật khẩu mới phải khác mật khẩu cũ.');
            thongtin_user_back();
            return;
        }

        $data['pass_hash'] = $d->hashPassword($password);

        $d->reset();
        $d->setTable('#_user');
        $d->setWhere('id', $uid);
        if ($d->update($data)) {
            admin_rate_clear($rateKey);
            // Xoay CSRF trước khi huỷ session (không bắt buộc)
            if (function_exists('admin_csrf_token')) {
                admin_csrf_token(true);
            }
            thongtin_user_logout_to_login();
            return;
        }

        admin_flash('error', 'Cập nhật thất bại. Vui lòng thử lại.');
        thongtin_user_back();
        return;
    }

    $d->reset();
    $d->setTable('#_user');
    $d->setWhere('id', $uid);
    if ($d->update($data)) {
        $_SESSION['name'] = $hoTen;
        admin_flash('success', 'Đã cập nhật thông tin tài khoản.');
        // Xoay token sau POST thành công
        if (function_exists('admin_csrf_token')) {
            admin_csrf_token(true);
        }
    } else {
        admin_flash('error', 'Cập nhật thất bại. Vui lòng thử lại.');
    }
    thongtin_user_back();
}