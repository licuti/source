<?php
namespace Tests\Services;

use Lms\MailService;
use Lms\PasswordResetService;
use Lms\UserService;
use Tests\LmsTestCase;

class PasswordResetServiceTest extends LmsTestCase
{
    private function createUser($username = 'reset01', $role = 'student', $email = 'reset@example.test')
    {
        $users = new UserService($this->db);
        $id = $users->createUser($username, 'old-password', $role, array('email' => $email));
        if ($role === 'student') {
            $this->db->insert('db_lms_students', array(
                'user_id' => $id, 'student_code' => 'RST001', 'full_name' => 'Reset Student', 'status' => 'studying',
            ));
        } elseif ($role === 'teacher') {
            $this->db->insert('db_lms_teachers', array(
                'user_id' => $id, 'teacher_code' => 'RSTT01', 'full_name' => 'Reset Teacher', 'status' => 'active',
            ));
        } else {
            $this->db->insert('db_lms_admins', array('user_id' => $id, 'full_name' => 'Reset Admin'));
        }
        return $id;
    }

    public function testRequestFindsUsernameAndEmailOnlyInPortalAndSendsOtp()
    {
        $id = $this->createUser();
        $sent = array();
        $mailer = new MailService(function ($to, $subject, $body) use (&$sent) {
            $sent = array($to, $subject, $body);
            return true;
        });
        $service = new PasswordResetService($this->db, $mailer);

        $this->assertSame(PasswordResetService::GENERIC_MESSAGE, $service->requestOtp('reset01', 'student', '127.0.0.1'));
        $this->assertSame('reset@example.test', $sent[0]);
        preg_match('/\b(\d{6})\b/', $sent[2], $match);
        $this->assertNotEmpty($match[1]);
        $otp = $this->db->safeFetchOne('select * from db_lms_password_reset_otps where user_id = ?', array($id));
        $this->assertNotSame($match[1], $otp['otp_hash']);
        $token = $service->verifyOtp('reset@example.test', 'student', $match[1]);
        $this->assertNotEmpty($token);
        $this->assertTrue($service->resetPassword($token, 'new-password', 'new-password', 'student'));
        $user = $this->db->safeFetchOne('select password, must_change_password from db_lms_users where id = ?', array($id));
        $this->assertTrue(password_verify('new-password', $user['password']));
        $this->assertSame(0, (int) $user['must_change_password']);
        $this->assertSame(1, $this->db->count('db_lms_audit_logs', 'action = ?', array('reset_password')));
        $this->assertNull($service->verifyOtp('reset01', 'student', $match[1]));
    }

    public function testGenericResponseAndNoOtpForUnknownWrongPortalOrMissingEmail()
    {
        $this->createUser('reset_teacher', 'teacher', 'teacher@example.test');
        $this->createUser('reset_noemail', 'student', null);
        $sends = 0;
        $service = new PasswordResetService($this->db, new MailService(function () use (&$sends) { $sends++; return true; }));
        foreach (array(array('nobody', 'student'), array('reset_teacher', 'student'), array('reset_noemail', 'student')) as $case) {
            $this->assertSame(PasswordResetService::GENERIC_MESSAGE, $service->requestOtp($case[0], $case[1], '127.0.0.2'));
        }
        $this->assertSame(0, $sends);
        $this->assertSame(0, $this->db->count('db_lms_password_reset_otps'));
    }

    public function testSmtpFailureDoesNotPersistOtpAndRateLimitIsFivePerWindow()
    {
        $id = $this->createUser();
        $service = new PasswordResetService($this->db, new MailService(function () { return false; }));
        for ($i = 0; $i < 5; $i++) {
            $this->assertSame(PasswordResetService::GENERIC_MESSAGE, $service->requestOtp('reset01', 'student', '127.0.0.3'));
        }
        $this->assertSame(0, $this->db->count('db_lms_password_reset_otps'));
        $this->assertSame(5, $this->db->count('db_lms_password_reset_requests'));
    }

    public function testWrongOtpExpiresAfterFiveAttemptsAndCannotResetAcrossPortals()
    {
        $id = $this->createUser();
        $expires = $this->db->safeFetchOne('select DATE_ADD(NOW(), INTERVAL 10 MINUTE) as expires_at');
        $this->db->insert('db_lms_password_reset_otps', array(
            'user_id' => $id,
            'portal' => 'student',
            'identifier_hash' => hash('sha256', 'reset01'),
            'otp_hash' => password_hash('123456', PASSWORD_DEFAULT),
            'expires_at' => $expires['expires_at'],
        ));
        $service = new PasswordResetService($this->db, new MailService(function () { return true; }));
        for ($i = 0; $i < 5; $i++) {
            $this->assertNull($service->verifyOtp('reset01', 'student', '000000'));
        }
        $this->assertNull($service->verifyOtp('reset01', 'student', '123456'));
        $this->assertNull($service->verifyOtp('reset01', 'teacher', '123456'));
    }

    public function testExpiredOtpCannotBeUsed()
    {
        $id = $this->createUser();
        $expired = $this->db->safeFetchOne('select DATE_SUB(NOW(), INTERVAL 1 MINUTE) as expires_at');
        $this->db->insert('db_lms_password_reset_otps', array(
            'user_id' => $id,
            'portal' => 'student',
            'identifier_hash' => hash('sha256', 'reset01'),
            'otp_hash' => password_hash('654321', PASSWORD_DEFAULT),
            'expires_at' => $expired['expires_at'],
        ));
        $service = new PasswordResetService($this->db, new MailService(function () { return true; }));
        $this->assertNull($service->verifyOtp('reset01', 'student', '654321'));
    }

    public function testNewOtpInvalidatesPreviouslyIssuedOtp()
    {
        $id = $this->createUser();
        $codes = array();
        $mailer = new MailService(function ($to, $subject, $body) use (&$codes) {
            preg_match('/\b(\d{6})\b/', $body, $match);
            $codes[] = $match[1];
            return true;
        });
        $service = new PasswordResetService($this->db, $mailer);
        $service->requestOtp('reset01', 'student', '127.0.0.9');
        $service->requestOtp('reset01', 'student', '127.0.0.9');

        $this->assertCount(2, $codes);
        $old = $this->db->safeFetchOne(
            'select used_at from db_lms_password_reset_otps where user_id = ? order by id asc limit 1',
            array($id)
        );
        $this->assertNotEmpty($old['used_at']);
        $this->assertNull($service->verifyOtp('reset01', 'student', $codes[0]));
        $this->assertNotEmpty($service->verifyOtp('reset01', 'student', $codes[1]));
    }
}
