<?php

namespace App\Core\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use App\Core\Logger;

class Mailer {
    
    /**
     * Gửi email
     * 
     * @param string|array $to
     * @param string $subject
     * @param string $body
     * @param array $attachments Mảng các đường dẫn file
     * @return bool
     */
    public static function send($to, $subject, $body, $attachments = []): bool {
        $mail = new PHPMailer(true);
        
        try {
            $mailerType = config('mail.mailer', 'smtp');
            if ($mailerType === 'smtp') {
                $mail->isSMTP();
            } elseif ($mailerType === 'sendmail') {
                $mail->isSendmail();
            } else {
                $mail->isMail();
            }
            
            $mail->SMTPDebug  = 0;
            $mail->SMTPAuth   = true;
            $mail->SMTPSecure = config('mail.encryption', 'ssl') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Host       = config('mail.host', 'smtp.gmail.com');
            $mail->Port       = config('mail.port', 465);
            $mail->Username   = config('mail.username', '');
            $mail->Password   = config('mail.password', '');
            
            $mail->CharSet = 'UTF-8';
            $mail->setFrom(config('mail.from.address', ''), config('mail.from.name', 'System'));
            
            if (is_array($to)) {
                foreach ($to as $address) {
                    $mail->addAddress($address);
                }
            } else {
                $mail->addAddress($to);
            }
            
            $mail->Subject = $subject;
            $mail->msgHTML($body);
            
            if (!empty($attachments)) {
                foreach ($attachments as $file) {
                    if (file_exists($file)) {
                        $mail->addAttachment($file);
                    }
                }
            }

            return $mail->send();
        } catch (Exception $e) {
            (new Logger())->error("Lỗi gửi email đến " . (is_array($to) ? implode(',', $to) : $to) . ": " . $mail->ErrorInfo);
            return false;
        } catch (\Throwable $e) {
            (new Logger())->error("Lỗi hệ thống khi gửi email: " . $e->getMessage());
            return false;
        }
    }
}
