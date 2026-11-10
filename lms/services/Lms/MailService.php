<?php
/** PHPMailer-backed SMTP adapter for LMS transactional mail. */
namespace Lms;

class MailService
{
    private $sender;

    public function __construct($sender = null)
    {
        $this->sender = $sender;
    }

    public function send($to, $subject, $body)
    {
        if (is_callable($this->sender)) {
            return (bool) call_user_func($this->sender, $to, $subject, $body);
        }
        $env = function_exists('env_load') ? env_load(dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . '.env') : array();
        if (empty($env['MAIL_ENABLED']) || strtolower((string) $env['MAIL_ENABLED']) !== 'true') {
            return false;
        }
        $autoload = dirname(__DIR__, 3) . '/library/vendor/autoload.php';
        if (!is_file($autoload)) {
            error_log('[LMS mail] Composer autoload missing.');
            return false;
        }
        require_once $autoload;
        try {
            $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = isset($env['MAIL_HOST']) ? $env['MAIL_HOST'] : '';
            $mail->Port = isset($env['MAIL_PORT']) ? (int) $env['MAIL_PORT'] : 587;
            $mail->SMTPAuth = !empty($env['MAIL_USERNAME']);
            $mail->Username = isset($env['MAIL_USERNAME']) ? $env['MAIL_USERNAME'] : '';
            $mail->Password = isset($env['MAIL_PASSWORD']) ? $env['MAIL_PASSWORD'] : '';
            $encryption = isset($env['MAIL_ENCRYPTION']) ? strtolower($env['MAIL_ENCRYPTION']) : '';
            if ($encryption === 'tls' || $encryption === 'ssl') {
                $mail->SMTPSecure = $encryption;
            }
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($env['MAIL_FROM_ADDRESS'], isset($env['MAIL_FROM_NAME']) ? $env['MAIL_FROM_NAME'] : 'MasterStudy');
            $mail->addAddress($to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->isHTML(false);
            return $mail->send();
        } catch (\Exception $e) {
            error_log('[LMS mail] SMTP send failed: ' . $e->getMessage());
            return false;
        }
    }
}
