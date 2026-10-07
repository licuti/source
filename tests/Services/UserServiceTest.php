<?php
/**
 * Tests cho UserService (auth 3 portal, D9).
 */

namespace Tests\Services;

use Lms\UserService;
use Tests\LmsTestCase;

class UserServiceTest extends LmsTestCase
{
    /** @var UserService */
    private $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new UserService($this->db);
    }

    private function makeTeacher($username = 'gv01', $password = 'matkhau123')
    {
        $id = $this->svc->createUser($username, $password, 'teacher');
        $this->db->insert('db_lms_teachers', array(
            'user_id'      => $id,
            'teacher_code' => 'TE001',
            'full_name'    => 'GV Test',
            'status'       => 'active',
        ));
        return $id;
    }

    public function testCreateUserHashesPasswordWithBcrypt()
    {
        $id = $this->svc->createUser('hv01', 'matkhau123', 'student');
        $this->assertGreaterThan(0, $id);

        $row = $this->db->safeFetchOne('select * from db_lms_users where id = ?', array($id));
        $this->assertNotEmpty($row);
        $this->assertSame('student', $row['role']);
        $this->assertStringStartsWith('$2y$', $row['password']);
        $this->assertTrue(lms_verify_password('matkhau123', $row['password']));
    }

    public function testCreateUserRejectsInvalidUsername()
    {
        $this->assertNull($this->svc->createUser('a b', 'matkhau123', 'student'));
        $this->assertTrue($this->svc->hasErrors());
    }

    public function testCreateUserRejectsShortPassword()
    {
        $this->assertNull($this->svc->createUser('hv02', '12345', 'student'));
        $this->assertArrayHasKey('password', $this->svc->errors());
    }

    public function testCreateUserRejectsDuplicateUsername()
    {
        $this->svc->createUser('hv03', 'matkhau123', 'student');
        $this->assertNull($this->svc->createUser('hv03', 'matkhau123', 'student'));
        $this->assertArrayHasKey('username', $this->svc->errors());
    }

    public function testAuthenticateCorrectPortal()
    {
        $this->makeTeacher();
        $res = $this->svc->authenticate('gv01', 'matkhau123', 'teacher');
        $this->assertTrue($res['ok']);
        $this->assertSame('teacher', $res['user']['role']);
        $this->assertArrayNotHasKey('password', $res['user']);
    }

    public function testD9WrongPortalIsRejectedWithCorrectPortalHint()
    {
        $this->makeTeacher();
        $res = $this->svc->authenticate('gv01', 'matkhau123', 'student');
        $this->assertFalse($res['ok']);
        $this->assertSame('Tài khoản không thuộc khu vực này.', $res['error']);
        $this->assertSame('teacher', $res['correct_portal']);
    }

    public function testAuthenticateRejectsWrongPassword()
    {
        $this->makeTeacher();
        $res = $this->svc->authenticate('gv01', 'saimatkhau', 'teacher');
        $this->assertFalse($res['ok']);
        $this->assertSame('Tên đăng nhập hoặc mật khẩu không đúng.', $res['error']);
    }

    public function testAuthenticateRejectsLockedUser()
    {
        $id = $this->makeTeacher();
        $this->db->update('db_lms_users', array('status' => 'locked'), 'id = ?', array($id));
        $res = $this->svc->authenticate('gv01', 'matkhau123', 'teacher');
        $this->assertFalse($res['ok']);
        $this->assertStringContainsString('khoá', $res['error']);
    }

    public function testAuthenticateUpdatesLastLogin()
    {
        $id = $this->makeTeacher();
        $this->svc->authenticate('gv01', 'matkhau123', 'teacher');
        $row = $this->db->safeFetchOne('select last_login_at from db_lms_users where id = ?', array($id));
        $this->assertNotEmpty($row['last_login_at']);
    }

    public function testSuperAdminFlagIsSet()
    {
        $id = $this->svc->createUser('adm01', 'matkhau123', 'admin');
        $this->db->insert('db_lms_admins', array(
            'user_id'    => $id,
            'full_name'  => 'Super Admin',
            'role_level' => 'super_admin',
        ));
        $res = $this->svc->authenticate('adm01', 'matkhau123', 'admin');
        $this->assertTrue($res['ok']);
        $this->assertSame(1, (int) $res['user']['is_super_admin']);
    }

    public function testChangePassword()
    {
        $id = $this->svc->createUser('hv04', 'matkhau123', 'student');
        $this->assertTrue($this->svc->changePassword($id, 'matkhaumoi456'));
        $res = $this->svc->authenticate('hv04', 'matkhaumoi456', 'student');
        $this->assertTrue($res['ok']);
    }

    public function testGeneratePasswordLengthAndUniqueness()
    {
        $a = $this->svc->generatePassword(12);
        $b = $this->svc->generatePassword(12);
        $this->assertSame(12, strlen($a));
        $this->assertNotSame($a, $b);
    }
}
