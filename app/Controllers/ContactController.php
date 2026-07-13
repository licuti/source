<?php

namespace App\Controllers;

use App\Core\Request;

use App\Core\Response;
use App\Models\ContactModel;

/**
 * ContactController
 * Xử lý trang liên hệ và gửi form liên hệ.
 */
class ContactController extends Controller {
    /**
     * Hiển thị trang liên hệ
     */
    public function index(Request $request) {
        // Đăng ký URL dịch
        $urls = [];
        foreach (config('lang', []) as $l) {
            $urls[$l['code']] = route('contact.index.' . $l['code']);
        }
        \App\Core\App::getInstance()->setLanguageLinks($urls);

        $row_detail = \App\Models\PageModel::where('type', 'lienhe')->first();
        return view('pages/contact', [
            'title' => 'Liên hệ'
        ]);
    }

    /**
     * Xử lý gửi form liên hệ (POST)
     */
    public function store(Request $request) {
        $name    = trim($request->input('name', ''));
        $email   = trim($request->input('email', ''));
        $phone   = trim($request->input('phone', ''));
        $subject = trim($request->input('subject', ''));
        $message = trim($request->input('message', ''));

        // Validate cơ bản
        $errors = [];
        if (empty($name))    $errors[] = 'Vui lòng nhập họ tên.';
        if (empty($email))   $errors[] = 'Vui lòng nhập email.';
        if (empty($message)) $errors[] = 'Vui lòng nhập nội dung.';

        if (!empty($errors)) {
            return view('pages/contact', [
                'title'  => 'Liên hệ',
                'errors' => $errors,
                'old'    => compact('name', 'email', 'phone', 'subject', 'message'),
            ]);
        }

        // Lưu vào database
        ContactModel::insert([
            'ten'       => $name,
            'email'     => $email,
            'dien_thoai'=> $phone,
            'tieu_de'   => $subject,
            'noi_dung'  => $message,
            'ngay'      => date('Y-m-d H:i:s'),
            'trang_thai'=> 0,
        ]);

        return view('pages/contact', [
            'title'   => 'Liên hệ',
            'success' => 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.',
        ]);
    }
}

