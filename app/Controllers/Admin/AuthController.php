<?php
namespace App\Controllers\Admin;

use App\Core\Request;

class AuthController extends BaseAdminController {
    public function login(Request $request) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Đã login thì chuyển vào dashboard
        if (isset($_SESSION['user_hash'])) {
            return $this->redirect(route('admin.dashboard'));
        }

        return $this->render('admin.auth.login');
    }

    public function loginPost(Request $request) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        // Mô phỏng lại hàm clean() cũ: loại bỏ khoảng trắng và thẻ HTML
        $username = strip_tags(trim((string)$request->input('input-username')));
        $password = strip_tags(trim((string)$request->input('input-password')));

        if (!$username || !$password) {
            return $this->render('admin.auth.login', ['err' => 'Vui lòng nhập đầy đủ thông tin']);
        }

        $user_hash = sha1($username);
        $pass_hash = sha1($password);

        // Truy vấn thông qua Model mới
        $login = \UserModel::where('user_hash', $user_hash)
                           ->where('pass_hash', $pass_hash)
                           ->where('quyen_han', 1, '>=')
                           ->first();

        if ($login) {
            $_SESSION['id_user']    = $login->id;
            $_SESSION['user_admin'] = $login->tai_khoan;
            $_SESSION['user_hash']  = $user_hash;
            $_SESSION['quyen']      = $login->quyen_han;
            $_SESSION['name']       = $login->ho_ten;
            $_SESSION['is_admin']   = $login->is_admin;

            if ($request->input('checkbox')) {
                $key_login = md5(time() . $login->id);
                setrawcookie('key_ad', $key_login, time() + (86400 * 30 * 365), '/', NULL, NULL, TRUE);
                
                // Cập nhật token bằng Model
                $login->token = $key_login;
                $login->save();
            }

            return $this->redirect(route('admin.dashboard'));
        } else {
            return $this->render('admin.auth.login', ['err' => 'Tài khoản hoặc mật khẩu chưa đúng.']);
        }
    }

    public function logout(Request $request) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        
        unset($_SESSION['id_user']);
        unset($_SESSION['user_admin']);
        unset($_SESSION['user_hash']);
        unset($_SESSION['quyen']);
        unset($_SESSION['name']);
        unset($_SESSION['is_admin']);
        
        if (isset($_COOKIE['key_ad'])) {
            setrawcookie('key_ad', '', time() - 3600, '/');
        }

        return $this->redirect(route('admin.login'));
    }
}
