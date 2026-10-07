<?php
/**
 * UserService — auth + quản lý tài khoản LMS (SYS-03, ADM-01, ADM-02).
 *
 * Thực hiện:
 *  - BR-AUTH: bcrypt + rate-limit + remember-me (copy pattern admin đã hardened)
 *  - D9: đăng nhập sai portal → từ chối kèm portal đúng (không tự chuyển)
 *  - D3: 3 portal dùng chung db_lms_users
 *
 * PHP 7.3 compatible.
 */

namespace Lms;

class UserService extends LmsService
{
    const ROLE_ADMIN   = 'admin';
    const ROLE_TEACHER = 'teacher';
    const ROLE_STUDENT = 'student';

    const ROLES = array(self::ROLE_ADMIN, self::ROLE_TEACHER, self::ROLE_STUDENT);

    /**
     * Xác thực đăng nhập cho portal cụ thể.
     *
     * Luồng:
     *  1. Tìm user theo username (chưa phân biệt portal).
     *  2. Rate-limit: nếu đang bị khoá → từ chối.
     *  3. Sai mật khẩu → đếm attempt, từ chối.
     *  4. Đúng mật khẩu nhưng role != portal → từ chối + trả portal đúng (D9).
     *  5. OK: xoá attempt, cập nhật last_login, trả user.
     *
     * @param string $username
     * @param string $password
     * @param string $portal 'admin' | 'teacher' | 'student'
     * @return array array('ok' => bool, 'user' => array|null, 'error' => string, 'correct_portal' => string|null)
     */
    public function authenticate($username, $password, $portal)
    {
        $this->clearErrors();
        $username = trim((string) $username);
        $portal   = (string) $portal;

        if ($username === '' || $password === '') {
            return $this->fail('Tên đăng nhập hoặc mật khẩu không được để trống.');
        }

        // Rate-limit
        if (function_exists('lms_is_login_locked') && lms_is_login_locked($username)) {
            return $this->fail('Bạn đã đăng nhập sai nhiều lần. Vui lòng thử lại sau 15 phút.');
        }

        $user = $this->db->safeFetchOne(
            'select * from db_lms_users where username = ? limit 1',
            array($username)
        );

        if (!is_array($user) || empty($user)) {
            $this->registerFailedAttempt($username);
            return $this->fail('Tên đăng nhập hoặc mật khẩu không đúng.');
        }

        $hash = isset($user['password']) ? (string) $user['password'] : '';
        if (!function_exists('lms_verify_password') || !lms_verify_password($password, $hash)) {
            $this->registerFailedAttempt($username);
            return $this->fail('Tên đăng nhập hoặc mật khẩu không đúng.');
        }

        // D9: sai portal → từ chối, không tự chuyển
        if (!isset($user['role']) || $user['role'] !== $portal) {
            return array(
                'ok'             => false,
                'user'           => null,
                'error'          => 'Tài khoản không thuộc khu vực này.',
                'correct_portal' => isset($user['role']) ? (string) $user['role'] : null,
            );
        }

        // Tài khoản bị khoá
        if (isset($user['status']) && $user['status'] === 'locked') {
            return $this->fail('Tài khoản đã bị khoá. Vui lòng liên hệ quản trị viên.');
        }

        // Thành công
        if (function_exists('lms_login_attempt_clear')) {
            lms_login_attempt_clear($username);
        }

        $this->db->update(
            'db_lms_users',
            array('last_login_at' => $this->now()),
            'id = ?',
            array((int) $user['id'])
        );

        // Bổ sung is_super_admin từ db_lms_admins để RBAC bypass
        $user['is_super_admin'] = 0;
        if ($user['role'] === self::ROLE_ADMIN) {
            $admin = $this->db->safeFetchOne(
                'select role_level from db_lms_admins where user_id = ? limit 1',
                array((int) $user['id'])
            );
            if (is_array($admin) && isset($admin['role_level']) && $admin['role_level'] === 'super_admin') {
                $user['is_super_admin'] = 1;
            }
        }

        unset($user['password']);
        return array('ok' => true, 'user' => $user, 'error' => '', 'correct_portal' => null);
    }

    /**
     * @param string $username
     */
    private function registerFailedAttempt($username)
    {
        if (function_exists('lms_login_attempt_register')) {
            lms_login_attempt_register($username);
        }
    }

    /**
     * @param string $message
     * @return array
     */
    private function fail($message)
    {
        return array('ok' => false, 'user' => null, 'error' => $message, 'correct_portal' => null);
    }

    /**
     * Tạo tài khoản LMS (username unique, password bcrypt).
     *
     * @param string $username
     * @param string $password
     * @param string $role
     * @param array  $extra cột thêm (email, phone, status...)
     * @return int|null user id hoặc null nếu lỗi
     */
    public function createUser($username, $password, $role, array $extra = array())
    {
        $this->clearErrors();
        $username = trim((string) $username);
        $role     = (string) $role;

        if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            $this->addError('username', 'Tên đăng nhập chỉ gồm chữ, số, dấu chấm/gạch dưới/gạch ngang (3-50 ký tự).');
            return null;
        }
        if (strlen($password) < 6) {
            $this->addError('password', 'Mật khẩu tối thiểu 6 ký tự.');
            return null;
        }
        if (!in_array($role, self::ROLES, true)) {
            $this->addError('role', 'Vai trò không hợp lệ.');
            return null;
        }

        $exists = $this->db->safeFetchOne(
            'select id from db_lms_users where username = ? limit 1',
            array($username)
        );
        if (!empty($exists)) {
            $this->addError('username', 'Tên đăng nhập đã tồn tại.');
            return null;
        }

        $data = array_merge(array(
            'username' => $username,
            'password' => lms_hash_password($password),
            'role'     => $role,
            'status'   => 'active',
        ), $extra);

        $ok = $this->db->insert('db_lms_users', $data);
        if (!$ok) {
            $this->addError('system', 'Không thể tạo tài khoản.');
            return null;
        }
        return (int) $this->db->insertId;
    }

    /**
     * Đổi mật khẩu (+rehash tự động).
     *
     * @param int    $userId
     * @param string $newPassword
     * @return bool
     */
    public function changePassword($userId, $newPassword)
    {
        $this->clearErrors();
        if (strlen($newPassword) < 6) {
            $this->addError('password', 'Mật khẩu tối thiểu 6 ký tự.');
            return false;
        }
        return $this->db->update(
            'db_lms_users',
            array(
                'password'          => lms_hash_password($newPassword),
                'password_changed_at' => $this->now(),
            ),
            'id = ?',
            array((int) $userId)
        );
    }

    /**
     * Sinh mật khẩu ngẫu nhiên (dùng khi admin tạo tài khoản mới).
     *
     * @param int $length
     * @return string
     */
    public function generatePassword($length = 10)
    {
        $alphabet = 'abcdefghijkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        try {
            $bytes = random_bytes($length);
        } catch (Exception $e) {
            $bytes = '';
            for ($i = 0; $i < $length; $i++) {
                $bytes .= chr(mt_rand(0, 255));
            }
        }
        $out = '';
        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $out .= $alphabet[ord($bytes[$i]) % $max];
        }
        return $out;
    }
}
