<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Request;

class EmailController extends BaseAdminController
{
    public function index()
    {
        // Lấy cấu hình từ DB, fallback sang env nếu DB chưa có
        $settings = [
            'MAIL_MAILER' => get_option('MAIL_MAILER', env('MAIL_MAILER', 'smtp')),
            'MAIL_HOST' => get_option('MAIL_HOST', env('MAIL_HOST', 'smtp.gmail.com')),
            'MAIL_PORT' => get_option('MAIL_PORT', env('MAIL_PORT', '465')),
            'MAIL_USERNAME' => get_option('MAIL_USERNAME', env('MAIL_USERNAME', '')),
            'MAIL_PASSWORD' => get_option('MAIL_PASSWORD', env('MAIL_PASSWORD', '')),
            'MAIL_ENCRYPTION' => get_option('MAIL_ENCRYPTION', env('MAIL_ENCRYPTION', 'ssl')),
            'MAIL_FROM_ADDRESS' => get_option('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', '')),
            'MAIL_FROM_NAME' => get_option('MAIL_FROM_NAME', env('MAIL_FROM_NAME', ''))
        ];
        
        return $this->render('admin.email.index', ['settings' => $settings]);
    }

    public function save(Request $request)
    {
        $data = [
            'MAIL_MAILER' => $request->input('MAIL_MAILER'),
            'MAIL_HOST' => $request->input('MAIL_HOST'),
            'MAIL_PORT' => $request->input('MAIL_PORT'),
            'MAIL_USERNAME' => $request->input('MAIL_USERNAME'),
            'MAIL_PASSWORD' => $request->input('MAIL_PASSWORD'),
            'MAIL_ENCRYPTION' => $request->input('MAIL_ENCRYPTION'),
            'MAIL_FROM_ADDRESS' => $request->input('MAIL_FROM_ADDRESS'),
            'MAIL_FROM_NAME' => $request->input('MAIL_FROM_NAME')
        ];

        // Lưu vào bảng db_options thay vì ghi đè file .env
        foreach ($data as $key => $value) {
            set_option($key, $value);
        }

        return $this->redirect(route('admin.email.index'))->with('success', 'Đã cập nhật cấu hình Email/SMTP thành công!');
    }

    public function testEmail(Request $request)
    {
        $to = $request->input('test_email');
        if (empty($to)) {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập email nhận test!']);
        }

        // Đọc cấu hình từ Form để gửi test (không lấy từ env)
        $host = $request->input('MAIL_HOST');
        $port = $request->input('MAIL_PORT');
        $username = $request->input('MAIL_USERNAME');
        $password = $request->input('MAIL_PASSWORD');
        $encryption = $request->input('MAIL_ENCRYPTION');
        $fromAddress = $request->input('MAIL_FROM_ADDRESS');
        $fromName = $request->input('MAIL_FROM_NAME');

        // Yêu cầu thư viện PHPMailer thông qua Composer (đã load ở index.php)
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

        try {
            $mail->IsSMTP();
            $mail->SMTPDebug  = 0;
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = $encryption === 'ssl' ? 'ssl' : 'tls';
            $mail->Host       = $host;
            $mail->Port       = $port;
            $mail->Username   = $username;
            $mail->Password   = $password;
            
            $mail->CharSet = 'UTF-8';
            $mail->SetFrom($fromAddress, $fromName);
            $mail->AddAddress($to);
            $mail->Subject = "Email Test Connection - " . date('Y-m-d H:i:s');
            $mail->MsgHTML("<p>Xin chào!</p><p>Đây là email test gửi từ hệ thống Admin để kiểm tra cấu hình SMTP.</p><p>Cấu hình của bạn đã chính xác và hoạt động tốt!</p>");

            if ($mail->Send()) {
                return $this->json(['success' => true, 'message' => 'Email đã được gửi thành công đến ' . $to]);
            } else {
                return $this->json(['success' => false, 'message' => 'Lỗi không xác định khi gửi email.']);
            }
        } catch (\Exception $e) {
            return $this->json(['success' => false, 'message' => 'Gửi thất bại: ' . $e->getMessage()]);
        }
    }
}
