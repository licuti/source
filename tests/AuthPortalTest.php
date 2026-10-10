<?php
/**
 * Tests cho auth + RBAC 3 portal LMS (T6 + D9).
 *
 * Phủ:
 *  - lms_check_permission(): đúng portal/role → cho; sai portal → luôn chối (D9);
 *    super_admin bypass (chỉ portal admin); ánh xạ action → cột quyền.
 *  - CSRF token (sinh / verify / sai / rỗng).
 *  - Remember-me token (sha256, plain ≠ hash).
 *  - Rate-limit đăng nhập (5 lần/15 phút, khoá theo username+IP, clear).
 *  - lms_enrich_user() (is_super_admin + xoá password).
 *  - lms_logout() (xoá key LMS, GIỮ key e-commerce — D2/P3).
 *  - bcrypt hash/verify/rehash.
 *  - Hội nhập authenticate() → lms_check_permission() đúng portal.
 */

namespace Tests;

use Lms\UserService;
use Tests\LmsTestCase;

class AuthPortalTest extends LmsTestCase
{
    /**
     * Rate-limit đăng nhập lưu state trong $_SESSION (chung cho cả process PHPUnit).
     * Phải dọn sạch trước/sau mỗi test để không khoá nhầm user của test khác
     * (VD UserServiceTest dùng cùng username 'gv01').
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearLoginAttemptState();
    }

    protected function tearDown(): void
    {
        $this->clearLoginAttemptState();
        parent::tearDown();
    }

    private function clearLoginAttemptState()
    {
        lms_session_ensure();
        foreach (array_keys($_SESSION) as $key) {
            if (strpos((string) $key, 'lms_login:') === 0) {
                unset($_SESSION[$key]);
            }
        }
    }

    private function seedModule($alias, $portal)
    {
        $this->db->insert('db_lms_modules', array(
            'parent'    => 0,
            'name'      => 'Module ' . $alias,
            'alias'     => $alias,
            'portal'    => $portal,
            'icon'      => 'fa-solid fa-circle',
            'so_thu_tu' => 1,
            'hien_thi'  => 1,
        ));
        return (int) $this->db->insertId;
    }

    private function seedPermission($moduleId, $role, $view, $edit = 0, $delete = 0)
    {
        $this->db->insert('db_lms_role_permissions', array(
            'id_module'  => $moduleId,
            'role'       => $role,
            'can_view'   => $view,
            'can_edit'   => $edit,
            'can_delete' => $delete,
        ));
    }

    private function user($role, $isSuperAdmin = 0)
    {
        return array(
            'id'             => 1,
            'username'       => 'tester',
            'role'           => $role,
            'is_super_admin' => $isSuperAdmin,
        );
    }

    // ===================== lms_check_permission — RBAC + D9 =====================

    public function testViewGrantedWhenPermissionRowExists()
    {
        $id = $this->seedModule('lms-hoc-vien', 'admin');
        $this->seedPermission($id, 'admin', 1);

        $this->assertTrue(lms_check_permission($this->db, $this->user('admin'), 'lms-hoc-vien', 'view', 'admin'));
    }

    public function testD9WrongPortalIsAlwaysDenied()
    {
        $id = $this->seedModule('lms-hoc-vien', 'admin');
        $this->seedPermission($id, 'admin', 1, 1, 1);

        // User admin đâm sang portal student → chối dù có đủ quyền
        $this->assertFalse(lms_check_permission($this->db, $this->user('admin'), 'lms-hoc-vien', 'view', 'student'));
        $this->assertFalse(lms_check_permission($this->db, $this->user('admin'), 'lms-hoc-vien', 'delete', 'student'));
    }

    public function testPermissionDeniedWithoutPermissionRow()
    {
        $this->seedModule('lms-hoc-vien', 'admin');

        $this->assertFalse(lms_check_permission($this->db, $this->user('admin'), 'lms-hoc-vien', 'view', 'admin'));
    }

    public function testPermissionDeniedForUnknownModule()
    {
        $this->assertFalse(lms_check_permission($this->db, $this->user('admin'), 'lms-khong-ton-tai', 'view', 'admin'));
    }

    public function testPermissionDeniedForUserWithoutRole()
    {
        $this->assertFalse(lms_check_permission($this->db, array('id' => 1), 'lms-hoc-vien', 'view', 'admin'));
    }

    public function testSuperAdminBypassesInAdminPortal()
    {
        // Không cần permission row
        $this->seedModule('lms-hoc-vien', 'admin');

        $this->assertTrue(lms_check_permission($this->db, $this->user('admin', 1), 'lms-hoc-vien', 'view', 'admin'));
        $this->assertTrue(lms_check_permission($this->db, $this->user('admin', 1), 'lms-hoc-vien', 'edit', 'admin'));
        $this->assertTrue(lms_check_permission($this->db, $this->user('admin', 1), 'lms-hoc-vien', 'delete', 'admin'));
    }

    public function testSuperAdminDoesNotBypassInOtherPortals()
    {
        $this->seedModule('lms-lich-day', 'teacher');

        // Super admin nhưng sang portal teacher → vẫn chối (role ≠ portal)
        $this->assertFalse(lms_check_permission($this->db, $this->user('admin', 1), 'lms-lich-day', 'view', 'teacher'));
    }

    public function testActionColumnMapping()
    {
        $id = $this->seedModule('lms-hoc-vien', 'teacher');
        $this->seedPermission($id, 'teacher', 1, 1, 0);
        $u = $this->user('teacher');

        $this->assertTrue(lms_check_permission($this->db, $u, 'lms-hoc-vien', 'view', 'teacher'));
        $this->assertTrue(lms_check_permission($this->db, $u, 'lms-hoc-vien', 'edit', 'teacher'));
        $this->assertFalse(lms_check_permission($this->db, $u, 'lms-hoc-vien', 'delete', 'teacher'));
    }

    public function testUnknownActionFallsBackToCanView()
    {
        $id = $this->seedModule('lms-hoc-vien', 'student');
        $this->seedPermission($id, 'student', 1, 0, 0);

        $this->assertTrue(lms_check_permission($this->db, $this->user('student'), 'lms-hoc-vien', 'action-la', 'student'));
    }

    public function testPortalDefaultsToLmsPortalConstant()
    {
        // _LMS_PORTAL = 'admin' (tests/bootstrap.php)
        $id = $this->seedModule('lms-hoc-vien', 'admin');
        $this->seedPermission($id, 'admin', 1);

        $this->assertTrue(lms_check_permission($this->db, $this->user('admin'), 'lms-hoc-vien'));
    }

    // ===================== Hội nhập authenticate → RBAC =====================

    public function testAuthenticatedTeacherHasTeacherPermissions()
    {
        $svc = new UserService($this->db);
        $svc->createUser('gv01', 'matkhau123', 'teacher');

        $id = $this->seedModule('lms-lich-day', 'teacher');
        $this->seedPermission($id, 'teacher', 1, 1);

        $res = $svc->authenticate('gv01', 'matkhau123', 'teacher');
        $this->assertTrue($res['ok']);

        $user = lms_enrich_user($this->db, $res['user']);

        $this->assertTrue(lms_check_permission($this->db, $user, 'lms-lich-day', 'view', 'teacher'));
        $this->assertTrue(lms_check_permission($this->db, $user, 'lms-lich-day', 'edit', 'teacher'));
        $this->assertFalse(lms_check_permission($this->db, $user, 'lms-lich-day', 'delete', 'teacher'));
        // Sang portal khác → chối
        $this->assertFalse(lms_check_permission($this->db, $user, 'lms-lich-day', 'view', 'admin'));
    }

    // ===================== CSRF =====================

    public function testCsrfTokenIs64HexAndVerifies()
    {
        $token = lms_csrf_token(true);

        $this->assertSame(64, strlen($token));
        $this->assertTrue(lms_csrf_verify($token));
    }

    public function testCsrfTokenIsStableAcrossCalls()
    {
        $a = lms_csrf_token(true);
        $b = lms_csrf_token();

        $this->assertSame($a, $b);
    }

    public function testCsrfVerifyRejectsWrongToken()
    {
        lms_csrf_token(true);

        $this->assertFalse(lms_csrf_verify(str_repeat('a', 64)));
    }

    public function testCsrfVerifyRejectsEmptyToken()
    {
        lms_csrf_token(true);

        $this->assertFalse(lms_csrf_verify(''));
        $this->assertFalse(lms_csrf_verify(null));
        $this->assertFalse(lms_csrf_verify());
    }

    // ===================== Remember-me =====================

    public function testGenerateRememberTokenShape()
    {
        $tok = lms_generate_remember_token();

        $this->assertSame(64, strlen($tok['token_plain']));
        $this->assertSame(hash('sha256', $tok['token_plain']), $tok['token_hash']);
        $this->assertNotSame($tok['token_plain'], $tok['token_hash']);
    }

    public function testHashRememberTokenIsSha256Hex()
    {
        $hash = lms_hash_remember_token('abc');

        $this->assertSame(64, strlen($hash));
        $this->assertSame(hash('sha256', 'abc'), $hash);
    }

    public function testRememberCookieName()
    {
        $this->assertSame('lms_remember', lms_remember_cookie_name());
    }

    public function testRememberCookieOptionsAreSecure()
    {
        $opts = lms_remember_cookie_options(time() + 100);

        $this->assertSame('/', $opts['path']);
        $this->assertTrue($opts['httponly']);
        $this->assertSame('Lax', $opts['samesite']);
    }

    // ===================== Rate-limit đăng nhập =====================

    public function testNotLockedInitially()
    {
        $this->assertFalse(lms_is_login_locked('gv01'));
    }

    public function testLocksOnlyOnFifthFailedAttempt()
    {
        for ($i = 0; $i < 4; $i++) {
            lms_login_attempt_register('gv01');
            $this->assertFalse(lms_is_login_locked('gv01'));
        }

        lms_login_attempt_register('gv01'); // lần thứ 5

        $this->assertTrue(lms_is_login_locked('gv01'));
    }

    public function testAttemptsRemainingCountsDown()
    {
        $this->assertSame(5, lms_login_attempts_remaining('gv01'));

        lms_login_attempt_register('gv01');

        $this->assertSame(4, lms_login_attempts_remaining('gv01'));
    }

    public function testClearAttemptsUnlocks()
    {
        for ($i = 0; $i < 5; $i++) {
            lms_login_attempt_register('gv01');
        }
        $this->assertTrue(lms_is_login_locked('gv01'));

        lms_login_attempt_clear('gv01');

        $this->assertFalse(lms_is_login_locked('gv01'));
        $this->assertSame(5, lms_login_attempts_remaining('gv01'));
    }

    public function testRateLimitIsScopedByUsername()
    {
        for ($i = 0; $i < 5; $i++) {
            lms_login_attempt_register('gv01');
        }
        $this->assertTrue(lms_is_login_locked('gv01'));

        // Username khác không bị khoá chung
        $this->assertFalse(lms_is_login_locked('gv02'));
    }

    // ===================== lms_enrich_user =====================

    public function testEnrichUserSetsSuperAdminFlagAndStripsPassword()
    {
        $svc = new UserService($this->db);
        $id = $svc->createUser('adm01', 'matkhau123', 'admin');
        $this->db->insert('db_lms_admins', array(
            'user_id'    => $id,
            'full_name'  => 'Super Admin',
            'role_level' => 'super_admin',
        ));

        $enriched = lms_enrich_user($this->db, array(
            'id'       => $id,
            'username' => 'adm01',
            'role'     => 'admin',
            'password' => 'hash-can-bi-xoa',
        ));

        $this->assertSame(1, (int) $enriched['is_super_admin']);
        $this->assertArrayNotHasKey('password', $enriched);
    }

    public function testEnrichUserZeroForNormalAdmin()
    {
        $svc = new UserService($this->db);
        $id = $svc->createUser('adm02', 'matkhau123', 'admin');
        $this->db->insert('db_lms_admins', array(
            'user_id'    => $id,
            'full_name'  => 'Nhân viên',
            'role_level' => 'staff',
        ));

        $enriched = lms_enrich_user($this->db, array('id' => $id, 'role' => 'admin'));

        $this->assertSame(0, (int) $enriched['is_super_admin']);
    }

    public function testEnrichUserNeverSetsSuperAdminForTeacherOrStudent()
    {
        $teacher = lms_enrich_user($this->db, array('id' => 9, 'role' => 'teacher'));
        $this->assertSame(0, (int) $teacher['is_super_admin']);

        $student = lms_enrich_user($this->db, array('id' => 10, 'role' => 'student'));
        $this->assertSame(0, (int) $student['is_super_admin']);
    }

    // ===================== lms_logout (D2/P3: không xoá session e-commerce) =====================

    public function testLogoutClearsAllLmsSessionKeys()
    {
        lms_session_ensure();
        $_SESSION['lms_user'] = array('id' => 1, 'role' => 'admin');
        $_SESSION['lms_csrf'] = 'abc';
        $_SESSION['lms_flash'] = array('type' => 'error', 'message' => 'x');
        $_SESSION['lms_login:u:cli'] = 5;
        unset($_COOKIE['lms_remember']);

        lms_logout();

        $this->assertArrayNotHasKey('lms_user', $_SESSION);
        $this->assertArrayNotHasKey('lms_csrf', $_SESSION);
        $this->assertArrayNotHasKey('lms_flash', $_SESSION);
        foreach (array_keys($_SESSION) as $key) {
            $this->assertStringStartsNotWith('lms_login:', (string) $key, 'còn key rate-limit LMS');
        }
    }

    public function testLogoutKeepsNonLmsSessionKeys()
    {
        lms_session_ensure();
        $_SESSION['cart'] = array(1, 2);
        $_SESSION['key_ad'] = 'xyz';
        unset($_COOKIE['lms_remember']);

        lms_logout();

        $this->assertArrayHasKey('cart', $_SESSION);
        $this->assertSame(array(1, 2), $_SESSION['cart']);
        $this->assertArrayHasKey('key_ad', $_SESSION);
    }

    // ===================== Mật khẩu (bcrypt) =====================

    public function testHashAndVerifyPassword()
    {
        $hash = lms_hash_password('matkhau123');

        $this->assertStringStartsWith('$2y$', $hash);
        $this->assertTrue(lms_verify_password('matkhau123', $hash));
        $this->assertFalse(lms_verify_password('sai-mat-khau', $hash));
    }

    public function testNeedsRehashForLowCostHash()
    {
        $hash = password_hash('x', PASSWORD_DEFAULT, array('cost' => 4));

        $this->assertTrue(lms_needs_rehash($hash));
    }

    public function testDoesNotNeedRehashForCurrentCost()
    {
        $hash = lms_hash_password('x');

        $this->assertFalse(lms_needs_rehash($hash));
    }
}
