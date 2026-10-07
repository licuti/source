<?php
/**
 * Helper gửi email qua SMTP sử dụng PHPMailer (Composer).
 */

require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!function_exists('sendmail')) {
    /**
     * Gửi email qua SMTP
     *
     * @param string $tieude Tiêu đề thư
     * @param string $noidung Nội dung thư (HTML)
     * @param string $nguoigui Email người gửi
     * @param string $nguoinhan Email người nhận
     * @param string $tennguoigui Tên hiển thị người gửi
     * @return bool
     */
    function sendmail($tieude, $noidung, $nguoigui, $nguoinhan, $tennguoigui = '') {
        $mail = new PHPMailer(true);

        try {
            // Cấu hình Server SMTP
            $mail->isSMTP();
            $mail->CharSet    = 'UTF-8';
            $mail->SMTPAuth   = true;

            // Lấy thông tin cấu hình từ CSDL (Ưu tiên)
            global $d;
            $db_smtpHost = $db_smtpUser = $db_smtpPass = $db_smtpPort = $db_smtpSecure = '';
            if (isset($d) && is_object($d)) {
                $thongtin = $d->simple_fetch("select smtp_host, smtp_port, smtp_user, smtp_pass, smtp_secure from #_thongtin limit 1");
                if (is_array($thongtin)) {
                    $db_smtpHost = $thongtin['smtp_host'] ?? '';
                    $db_smtpPort = $thongtin['smtp_port'] ?? '';
                    $db_smtpUser = $thongtin['smtp_user'] ?? '';
                    $db_smtpPass = $thongtin['smtp_pass'] ?? '';
                    $db_smtpSecure = $thongtin['smtp_secure'] ?? '';
                }
            }

            // Đọc cấu hình từ DB trước, nếu không có thì đọc từ hằng số hoặc biến môi trường (fallback)
            $smtpHost = !empty($db_smtpHost) ? $db_smtpHost : (defined('SMTP_HOST') ? SMTP_HOST : (getenv('SMTP_HOST') ?: 'smtp.gmail.com'));
            $smtpUser = !empty($db_smtpUser) ? $db_smtpUser : (defined('SMTP_USER') ? SMTP_USER : (getenv('SMTP_USER') ?: ''));
            $smtpPass = !empty($db_smtpPass) ? $db_smtpPass : (defined('SMTP_PASS') ? SMTP_PASS : (getenv('SMTP_PASS') ?: ''));
            $smtpPort = !empty($db_smtpPort) ? (int)$db_smtpPort : (defined('SMTP_PORT') ? (int)SMTP_PORT : (int)(getenv('SMTP_PORT') ?: 465));
            $smtpSecure = !empty($db_smtpSecure) ? $db_smtpSecure : (defined('SMTP_SECURE') ? SMTP_SECURE : (getenv('SMTP_SECURE') ?: 'ssl'));

            $mail->Host       = $smtpHost;
            $mail->Username   = $smtpUser;
            $mail->Password   = $smtpPass;
            $mail->SMTPSecure = ($smtpSecure === 'tls') ? PHPMailer::ENCRYPTION_STARTTLS : PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port       = $smtpPort;

            // Kiểm tra cấu hình trước khi gửi
            if (empty($mail->Username) || empty($mail->Password)) {
                error_log('[SMTP SendMail] Lưu ý: Chưa cấu hình Username và Password SMTP.');
                return false;
            }

            // Người gửi & Người nhận
            $fromEmail = !empty($nguoigui) ? $nguoigui : $mail->Username;
            $fromName  = !empty($tennguoigui) ? $tennguoigui : $fromEmail;
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($nguoinhan);

            // Nội dung email
            $mail->isHTML(true);
            $mail->Subject = $tieude;
            $mail->Body    = $noidung;

            return $mail->send();
        } catch (Exception $e) {
            error_log('[SMTP SendMail] Lỗi gửi email: ' . $mail->ErrorInfo);
            return false;
        }
    }
}
