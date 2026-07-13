<?php
namespace App\Controllers\Admin;

use App\Core\Request;

class AuthController extends \App\Controllers\Controller {
    protected $layout = null;
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
        $login = \App\Models\UserModel::where('username', $username)
                           ->where('password', $pass_hash)
                           ->where('role_id', 1, '>=')
                           ->first();

        if ($login) {
            $_SESSION['id_user']    = $login->id;
            $_SESSION['user_admin'] = $login->username;
            $_SESSION['quyen']      = $login->role_id;
            $_SESSION['role_id']    = $login->role_id; // Thêm biến chuẩn
            $_SESSION['name']       = $login->fullname;
            $_SESSION['is_admin']   = $login->is_admin;
            
            // Cache quyền vào Session
            if ($login->is_admin != 1) {
                $perms = \App\Models\RolePermissionModel::where('role_id', $login->role_id)->get();
                $permissionsArray = [];
                foreach ($perms as $p) {
                    $permissionsArray[$p->module_id] = [
                        'can_view' => $p->can_view,
                        'can_add' => $p->can_add,
                        'can_edit' => $p->can_edit,
                        'can_delete' => $p->can_delete
                    ];
                }
                $_SESSION['role_permissions'] = $permissionsArray;
            }

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
        unset($_SESSION['quyen']);
        unset($_SESSION['name']);
        unset($_SESSION['is_admin']);
        
        if (isset($_COOKIE['key_ad'])) {
            setrawcookie('key_ad', '', time() - 3600, '/');
        }

        return $this->redirect(route('admin.login'));
    }
}
