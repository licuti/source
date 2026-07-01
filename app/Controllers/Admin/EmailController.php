<?php

namespace App\Controllers\Admin;

use App\Controllers\Admin\BaseAdminController;
use App\Core\Request;

class EmailController extends BaseAdminController
{
    public function index(Request $request)
    {
        $settings = [
            'MAIL_MAILER' => env('MAIL_MAILER', 'smtp'),
            'MAIL_HOST' => env('MAIL_HOST', 'smtp.gmail.com'),
            'MAIL_PORT' => env('MAIL_PORT', '465'),
            'MAIL_USERNAME' => env('MAIL_USERNAME', ''),
            'MAIL_PASSWORD' => env('MAIL_PASSWORD', ''),
            'MAIL_ENCRYPTION' => env('MAIL_ENCRYPTION', 'ssl'),
            'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS', ''),
            'MAIL_FROM_NAME' => env('MAIL_FROM_NAME', '')
        ];
        
        return $this->render('admin.email.index', ['settings' => $settings]);
    }

    public function save(Request $request)
    {
        $data = [
            'MAIL_MAILER' => $request->post('MAIL_MAILER'),
            'MAIL_HOST' => $request->post('MAIL_HOST'),
            'MAIL_PORT' => $request->post('MAIL_PORT'),
            'MAIL_USERNAME' => $request->post('MAIL_USERNAME'),
            'MAIL_PASSWORD' => $request->post('MAIL_PASSWORD'),
            'MAIL_ENCRYPTION' => $request->post('MAIL_ENCRYPTION'),
            'MAIL_FROM_ADDRESS' => $request->post('MAIL_FROM_ADDRESS'),
            'MAIL_FROM_NAME' => $request->post('MAIL_FROM_NAME')
        ];

        $envPath = dirname(dirname(dirname(__DIR__))) . '/.env';
        
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            
            foreach ($data as $key => $value) {
                // Thoát các ký tự đặc biệt nếu cần thiết
                if (strpos($value, ' ') !== false) {
                    $value = '"' . str_replace('"', '\"', $value) . '"';
                }
                
                // Thay thế dòng cấu hình hiện có
                if (preg_match("/^$key=(.*)$/m", $envContent)) {
                    $envContent = preg_replace("/^$key=(.*)$/m", "$key=$value", $envContent);
                } else {
                    // Nếu chưa có, thêm vào cuối file
                    $envContent .= "\n$key=$value";
                }
            }
            
            file_put_contents($envPath, $envContent);
        }

        return $this->redirect(route('admin.email.index'))->with('success', 'Đã cập nhật cấu hình Email/SMTP thành công!');
    }

    public function testEmail(Request $request)
    {
        $to = $request->post('test_email');
        if (empty($to)) {
            return $this->json(['success' => false, 'message' => 'Vui lòng nhập email nhận test!']);
        }

        // Đọc cấu hình từ Form để gửi test (không lấy từ env)
        $host = $request->post('MAIL_HOST');
        $port = $request->post('MAIL_PORT');
        $username = $request->post('MAIL_USERNAME');
        $password = $request->post('MAIL_PASSWORD');
        $encryption = $request->post('MAIL_ENCRYPTION');
        $fromAddress = $request->post('MAIL_FROM_ADDRESS');
        $fromName = $request->post('MAIL_FROM_NAME');

        // Yêu cầu thư viện PHPMailer cũ
        $smtpDir = dirname(dirname(dirname(__DIR__))) . '/smtp';
        require_once $smtpDir . '/class.phpmailer.php';
        require_once $smtpDir . '/class.smtp.php';

        $mail = new \PHPMailer(true);

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
