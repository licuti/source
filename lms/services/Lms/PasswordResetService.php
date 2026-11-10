<?php
/** Portal-scoped, enumeration-resistant email OTP password reset. */
namespace Lms;

class PasswordResetService extends LmsService
{
    const GENERIC_MESSAGE = 'Nếu thông tin phù hợp với tài khoản của portal này, hướng dẫn đặt lại mật khẩu sẽ được gửi đến email đã đăng ký. Nếu không nhận được email, vui lòng liên hệ trung tâm.';

    private $mailer;

    public function __construct(\LmsDb $db = null, MailService $mailer = null)
    {
        parent::__construct($db);
        $this->mailer = $mailer === null ? new MailService() : $mailer;
    }

    public function requestOtp($identifier, $portal, $ip)
    {
        $identifier = trim((string) $identifier);
        $portal = (string) $portal;
        $ip = (string) $ip;
        if ($identifier === '' || !in_array($portal, UserService::ROLES, true)) {
            return self::GENERIC_MESSAGE;
        }
        $key = $this->identifierHash($identifier);
        $ipHash = hash('sha256', $ip);
        $this->db->beginTransaction();
        try {
            $limit = $this->db->safeFetchOne(
                'select count(*) as c from db_lms_password_reset_requests'
                . ' where identifier_hash = ? and ip_hash = ? and created_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE) for update',
                array($key, $ipHash)
            );
            if ((int) $limit['c'] >= 5) {
                $this->db->rollBack();
                return self::GENERIC_MESSAGE;
            }
            $ok = $this->db->insert('db_lms_password_reset_requests', array(
                'identifier_hash' => $key, 'ip_hash' => $ipHash,
            ));
            if (!$ok) {
                throw new \RuntimeException('Rate-limit record failed.');
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[LMS password reset] Rate-limit check failed: ' . $e->getMessage());
            return self::GENERIC_MESSAGE;
        }

        $user = $this->db->safeFetchOne(
            'select id, username, email from db_lms_users where role = ? and status = ?'
            . ' and (username = ? or email = ?) limit 1',
            array($portal, 'active', $identifier, $identifier)
        );
        if (empty($user) || !filter_var((string) $user['email'], FILTER_VALIDATE_EMAIL)) {
            return self::GENERIC_MESSAGE;
        }
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $otpHash = password_hash($otp, PASSWORD_DEFAULT, array('cost' => 10));
        if (!$this->mailer->send($user['email'], 'Mã xác minh đặt lại mật khẩu',
            'Mã OTP của bạn là ' . $otp . '. Mã có hiệu lực trong 10 phút.')) {
            return self::GENERIC_MESSAGE;
        }

        $this->db->beginTransaction();
        try {
            $this->db->safeExec(
                'update db_lms_password_reset_otps set used_at = NOW()'
                . ' where user_id = ? and portal = ? and used_at is null',
                array((int) $user['id'], $portal)
            );
            $ok = $this->db->insert('db_lms_password_reset_otps', array(
                'user_id' => (int) $user['id'],
                'portal' => $portal,
                'identifier_hash' => $key,
                'otp_hash' => $otpHash,
                'expires_at' => $this->db->safeFetchOne('select DATE_ADD(NOW(), INTERVAL 10 MINUTE) as expires_at')['expires_at'],
            ));
            if (!$ok) {
                throw new \RuntimeException('OTP persistence failed.');
            }
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[LMS password reset] OTP persistence failed: ' . $e->getMessage());
        }
        return self::GENERIC_MESSAGE;
    }

    public function verifyOtp($identifier, $portal, $otp)
    {
        $user = $this->findUser($identifier, $portal);
        if (empty($user) || !preg_match('/^\d{6}$/', (string) $otp)) {
            return null;
        }
        $row = $this->db->safeFetchOne(
            'select * from db_lms_password_reset_otps where user_id = ? and portal = ?'
            . ' and used_at is null and verified_at is null and expires_at > NOW() order by id desc limit 1',
            array((int) $user['id'], (string) $portal)
        );
        if (empty($row) || (int) $row['attempt_count'] >= 5) {
            return null;
        }
        if (!password_verify((string) $otp, (string) $row['otp_hash'])) {
            $this->db->safeExec(
                'update db_lms_password_reset_otps set attempt_count = attempt_count + 1,'
                . ' used_at = case when attempt_count + 1 >= 5 then NOW() else used_at end'
                . ' where id = ? and used_at is null and attempt_count < 5',
                array((int) $row['id'])
            );
            return null;
        }
        $token = bin2hex(random_bytes(32));
        $this->db->beginTransaction();
        try {
            $current = $this->db->safeFetchOne(
                'select id, otp_hash, attempt_count from db_lms_password_reset_otps'
                . ' where id = ? and used_at is null and verified_at is null and expires_at > NOW() for update',
                array((int) $row['id'])
            );
            if (empty($current) || (int) $current['attempt_count'] >= 5
                || !password_verify((string) $otp, (string) $current['otp_hash'])) {
                $this->db->rollBack();
                return null;
            }
            $this->db->safeExec(
                'update db_lms_password_reset_otps set reset_token_hash = ?, verified_at = NOW()'
                . ' where id = ? and used_at is null and verified_at is null',
                array(hash('sha256', $token), (int) $row['id'])
            );
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            return null;
        }
        return $token;
    }

    public function resetPassword($token, $newPassword, $confirmPassword, $portal)
    {
        if (strlen((string) $newPassword) < 6 || (string) $newPassword !== (string) $confirmPassword) {
            return false;
        }
        $this->db->beginTransaction();
        try {
            $row = $this->db->safeFetchOne(
                'select * from db_lms_password_reset_otps where portal = ? and reset_token_hash = ?'
                . ' and verified_at is not null and used_at is null and expires_at > NOW() order by id desc limit 1 for update',
                array((string) $portal, hash('sha256', (string) $token))
            );
            if (empty($row)) {
                $this->db->rollBack();
                return false;
            }
            $currentPassword = $this->db->safeFetchOne(
                'select password from db_lms_users where id = ? and role = ? limit 1',
                array((int) $row['user_id'], (string) $portal)
            );
            if (empty($currentPassword) || password_verify((string) $newPassword, (string) $currentPassword['password'])) {
                $this->db->rollBack();
                return false;
            }
            $ok = $this->db->safeExec(
                'update db_lms_users set password = ?, password_changed_at = NOW(), must_change_password = 0'
                . ' where id = ? and role = ?',
                array(password_hash((string) $newPassword, PASSWORD_DEFAULT, array('cost' => 10)),
                    (int) $row['user_id'], (string) $portal)
            );
            if (!$ok || $this->db->affectedRows() !== 1) {
                throw new \RuntimeException('Password account update failed.');
            }
            $ok = $this->db->safeExec(
                'update db_lms_password_reset_otps set used_at = NOW(), reset_token_hash = null'
                . ' where id = ? and used_at is null',
                array((int) $row['id'])
            );
            if (!$ok || $this->db->affectedRows() !== 1) {
                throw new \RuntimeException('OTP consumption failed.');
            }
            $this->audit('reset_password', 'users', (int) $row['user_id'], null,
                array('portal' => (string) $portal), (string) $portal);
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollBack();
            error_log('[LMS password reset] Password update failed: ' . $e->getMessage());
            return false;
        }
    }

    private function findUser($identifier, $portal)
    {
        return $this->db->safeFetchOne(
            'select id from db_lms_users where role = ? and status = ? and (username = ? or email = ?) limit 1',
            array((string) $portal, 'active', trim((string) $identifier), trim((string) $identifier))
        );
    }

    private function identifierHash($identifier)
    {
        return hash('sha256', strtolower(trim((string) $identifier)));
    }
}
