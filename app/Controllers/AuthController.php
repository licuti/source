<?php

namespace App\Controllers;


use App\Core\Response;

/**
 * AuthController
 * Xử lý đăng nhập, đăng ký, đăng xuất, quên mật khẩu.
 */
class AuthController extends Controller {
    /**
     * Hiển thị trang đăng nhập
     */
    public function login($request) {
        // Nếu đã đăng nhập → chuyển về trang thành viên
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . url('thanh-vien.html'));
            exit;
        }

        return view('pages/auth/login', [
            'title' => 'Đăng nhập'
        ]);
    }

    /**
     * Xử lý form đăng nhập (POST)
     */
    public function loginPost($request) {
        $email    = trim($request->input('email', ''));
        $password = $request->input('password', '');

        if (empty($email) || empty($password)) {
            return view('pages/auth/login', [
                'title' => 'Đăng nhập',
                'error' => 'Vui lòng nhập đầy đủ email và mật khẩu.',
                'old'   => ['email' => $email],
            ]);
        }

        $user = \UserModel::where('email', $email)->first();

        if (!$user || !password_verify($password, $user->password ?? '')) {
            return view('pages/auth/login', [
                'title' => 'Đăng nhập',
                'error' => 'Email hoặc mật khẩu không chính xác.',
                'old'   => ['email' => $email],
            ]);
        }

        // Đăng nhập thành công
        $_SESSION['user_id']    = $user->id;
        $_SESSION['user_name']  = $user->ten;
        $_SESSION['user_email'] = $user->email;

        header('Location: ' . url('thanh-vien.html'));
        exit;
    }

    /**
     * Hiển thị trang đăng ký
     */
    public function register($request) {
        if (!empty($_SESSION['user_id'])) {
            header('Location: ' . url('thanh-vien.html'));
            exit;
        }

        return view('pages/auth/register', [
            'title' => 'Đăng ký tài khoản'
        ]);
    }

    /**
     * Đăng xuất
     */
    public function logout($request) {
        // Xóa toàn bộ session data liên quan đến user
        unset(
            $_SESSION['user_id'],
            $_SESSION['user_name'],
            $_SESSION['user_email']
        );

        header('Location: ' . url(''));
        exit;
    }

    /**
     * Hiển thị trang quên mật khẩu
     */
    public function forgotPassword($request) {
        return view('pages/auth/forgot-password', [
            'title' => 'Quên mật khẩu'
        ]);
    }
}

