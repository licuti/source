<?php
/**
 * LMS auth + RBAC chung cho 3 portal (admin / teacher / student).
 *
 * Copy pattern đã hardened của admin/ (đợt 2026-09-11, xem ANALYSIS-ADMIN-REVIEW.md):
 *  - bcrypt (cost 10) + needsRehash để nâng cấp tự động
 *  - CSRF token random_bytes + hash_equals
 *  - Rate-limit đăng nhập (sai liên tiếp → khoá tạm)
 *  - Remember-me random_bytes + sha256 + rotate
 *
 * KHÔNG dùng db_user / db_thanhvien của e-commerce (D3 + R6).
 * PHP 7.3 compatible.
 */

if (!function_exists('lms_session_ensure')) {
    function lms_session_ensure()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        // CLI hoặc session_start() thất bại → $_SESSION có thể chưa tồn tại
        if (!isset($_SESSION) || !is_array($_SESSION)) {
            $_SESSION = array();
        }
    }
}

// ===================== Mật khẩu (bcrypt) =====================

if (!function_exists('lms_hash_password')) {
    /**
     * Tạo bcrypt hash. Dùng PASSWORD_DEFAULT (PHP chọn thuật toán an toàn nhất).
     *
     * @param string $password mật khẩu thô
     * @return string hash
     */
    function lms_hash_password($password)
    {
        return password_hash((string) $password, PASSWORD_DEFAULT, array('cost' => 10));
    }
}

if (!function_exists('lms_verify_password')) {
    /**
     * Xác thực mật khẩu.
     *
     * @param string $password mật khẩu thô
     * @param string $hash     hash lưu trong db_lms_users.password
     * @return bool
     */
    function lms_verify_password($password, $hash)
    {
        return password_verify((string) $password, (string) $hash);
    }
}

if (!function_exists('lms_needs_rehash')) {
    /**
     * Hash cũ hoặc cost thấp hơn hiện tại → cần rehash.
     *
     * @param string $hash
     * @return bool
     */
    function lms_needs_rehash($hash)
    {
        return password_needs_rehash((string) $hash, PASSWORD_DEFAULT, array('cost' => 10));
    }
}

// ===================== CSRF =====================

if (!function_exists('lms_csrf_token')) {
    /**
     * Lấy (hoặc sinh mới) CSRF token của session.
     *
     * @param bool $rotate sinh token mới
     * @return string
     */
    function lms_csrf_token($rotate = false)
    {
        lms_session_ensure();
        if ($rotate || empty($_SESSION['lms_csrf'])) {
            try {
                $_SESSION['lms_csrf'] = bin2hex(random_bytes(32));
            } catch (Exception $e) {
                $_SESSION['lms_csrf'] = sha1(uniqid((string) mt_rand(), true) . microtime(true));
            }
        }
        return (string) $_SESSION['lms_csrf'];
    }
}

if (!function_exists('lms_csrf_verify')) {
    /**
     * Xác thực CSRF token từ POST.
     *
     * @param string|null $token null = lấy từ $_POST['csrf_token']
     * @return bool
     */
    function lms_csrf_verify($token = null)
    {
        lms_session_ensure();
        if ($token === null) {
            $token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';
        }
        $token = (string) $token;
        $session = isset($_SESSION['lms_csrf']) ? (string) $_SESSION['lms_csrf'] : '';
        if ($session === '' || $token === '') {
            return false;
        }
        return hash_equals($session, $token);
    }
}

// ===================== Flash message =====================

if (!function_exists('lms_flash')) {
    function lms_flash($type, $message)
    {
        lms_session_ensure();
        $_SESSION['lms_flash'] = array(
            'type'    => (string) $type,
            'message' => (string) $message,
        );
    }
}

// ===================== Remember-me =====================

if (!function_exists('lms_generate_remember_token')) {
    /**
     * Sinh remember-me token (32 byte ngẫu nhiên, lưu dạng sha256 hex).
     *
     * @return array array('token_plain' => string, 'token_hash' => string)
     */
    function lms_generate_remember_token()
    {
        try {
            $bytes = random_bytes(32);
        } catch (Exception $e) {
            $bytes = pack('H*', sha1(uniqid((string) mt_rand(), true) . microtime(true)));
        }
        $plain = bin2hex($bytes);
        return array(
            'token_plain' => $plain,
            'token_hash'  => hash('sha256', $plain),
        );
    }
}

if (!function_exists('lms_hash_remember_token')) {
    /**
     * Hash token từ cookie trước khi so khớp với DB.
     *
     * @param string $plain
     * @return string
     */
    function lms_hash_remember_token($plain)
    {
        return hash('sha256', (string) $plain);
    }
}

// ===================== Remember-me cookie + session =====================
// Cookie tên lms_remember (P3: tách hẳn key_ad của admin e-commerce).

if (!function_exists('lms_remember_cookie_name')) {
    /**
     * @return string
     */
    function lms_remember_cookie_name()
    {
        return 'lms_remember';
    }
}

if (!function_exists('lms_remember_cookie_options')) {
    /**
     * Thuộc tính cookie remember-me: path=/, HttpOnly, SameSite=Lax.
     *
     * @param int $expires timestamp
     * @return array
     */
    function lms_remember_cookie_options($expires)
    {
        $secure = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
        return array(
            'expires'  => (int) $expires,
            'path'     => '/',
            'secure'   => $secure,
            'httponly' => true,
            'samesite' => 'Lax',
        );
    }
}

if (!function_exists('lms_set_remember_cookie')) {
    /**
     * Sinh + lưu remember token (sha256) cho user, set cookie HttpOnly.
     *
     * @param int    $userId
     * @param LmsDb  $d
     * @param int    $days thời hạn
     * @return bool
     */
    function lms_set_remember_cookie($userId, LmsDb $d, $days = 30)
    {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return false;
        }

        $tok = lms_generate_remember_token();
        $expiresTs = time() + 86400 * (int) $days;

        $ok = $d->update(
            'db_lms_users',
            array(
                'remember_token'            => $tok['token_hash'],
                'remember_token_expires_at' => date('Y-m-d H:i:s', $expiresTs),
            ),
            'id = ?',
            array($userId)
        );
        if (!$ok) {
            return false;
        }

        if (!headers_sent()) {
            setcookie(
                lms_remember_cookie_name(),
                $tok['token_plain'],
                lms_remember_cookie_options($expiresTs)
            );
        }
        $_COOKIE[lms_remember_cookie_name()] = $tok['token_plain'];
        return true;
    }
}

if (!function_exists('lms_try_remember_me')) {
    /**
     * Đăng nhập lại bằng cookie remember-me (raw → sha256 → lookup DB).
     * Thành công: set $_SESSION['lms_user'] + ROTATE token (ghi token mới,
     * set cookie lại). Token chỉ dùng được 1 lần.
     *
     * @param LmsDb $d
     * @return bool
     */
    function lms_try_remember_me(LmsDb $d)
    {
        $name = lms_remember_cookie_name();
        if (empty($_COOKIE[$name])) {
            return false;
        }

        $hash = lms_hash_remember_token((string) $_COOKIE[$name]);

        $user = $d->safeFetchOne(
            'select id, username, role, status from db_lms_users'
            . ' where remember_token = ? and remember_token_expires_at > now() limit 1',
            array($hash)
        );
        if (empty($user) || !is_array($user)) {
            lms_clear_remember_cookie($d);
            return false;
        }
        if (isset($user['status']) && $user['status'] !== 'active') {
            lms_clear_remember_cookie($d);
            return false;
        }

        lms_session_ensure();
        $_SESSION['lms_user'] = lms_enrich_user($d, $user);

        // Rotate token (ghi token mới, set cookie lại)
        lms_set_remember_cookie((int) $user['id'], $d);
        return true;
    }
}

if (!function_exists('lms_clear_remember_cookie')) {
    /**
     * Xoá cookie remember-me (set quá khứ) + clear token trong DB nếu có.
     *
     * @param LmsDb|null $d
     * @return void
     */
    function lms_clear_remember_cookie(LmsDb $d = null)
    {
        $name = lms_remember_cookie_name();

        if ($d !== null && !empty($_COOKIE[$name])) {
            $hash = lms_hash_remember_token((string) $_COOKIE[$name]);
            $d->update(
                'db_lms_users',
                array(
                    'remember_token'            => null,
                    'remember_token_expires_at' => null,
                ),
                'remember_token = ?',
                array($hash)
            );
        }

        if (!headers_sent()) {
            setcookie($name, '', lms_remember_cookie_options(time() - 86400));
        }
        unset($_COOKIE[$name]);
    }
}

if (!function_exists('lms_logout')) {
    /**
     * Đăng xuất LMS: xoá session key LMS + remember token DB + cookie.
     * KHÔNG session_destroy() để tránh xoá luôn session e-commerce (D2/P3).
     *
     * @param LmsDb|null $d
     * @return void
     */
    function lms_logout(LmsDb $d = null)
    {
        lms_session_ensure();
        lms_clear_remember_cookie($d);

        unset($_SESSION['lms_user'], $_SESSION['lms_csrf'], $_SESSION['lms_flash']);

        // Xoá cả các key rate-limit đăng nhập của LMS
        foreach (array_keys($_SESSION) as $key) {
            if (strpos((string) $key, 'lms_login:') === 0) {
                unset($_SESSION[$key]);
            }
        }
    }
}

if (!function_exists('lms_login_attempts_remaining')) {
    /**
     * Số lần thử còn lại trước khi bị khoá (rate-limit 5 lần/15 phút).
     *
     * @param string $username
     * @return int
     */
    function lms_login_attempts_remaining($username)
    {
        lms_session_ensure();
        $key = lms_login_attempt_key($username);
        $n = isset($_SESSION[$key]) ? (int) $_SESSION[$key] : 0;
        $remaining = 5 - $n;
        return $remaining < 0 ? 0 : $remaining;
    }
}

if (!function_exists('lms_enrich_user')) {
    /**
     * Bổ sung is_super_admin (từ db_lms_admins) cho row user trước khi đưa
     * vào session (RBAC bypass — guide §3.2).
     *
     * @param LmsDb $d
     * @param array $user row db_lms_users (không chứa password)
     * @return array
     */
    function lms_enrich_user(LmsDb $d, array $user)
    {
        $user['is_super_admin'] = 0;
        if (isset($user['role']) && $user['role'] === 'admin') {
            $admin = $d->safeFetchOne(
                'select role_level from db_lms_admins where user_id = ? limit 1',
                array((int) $user['id'])
            );
            if (is_array($admin) && isset($admin['role_level']) && $admin['role_level'] === 'super_admin') {
                $user['is_super_admin'] = 1;
            }
        }
        unset($user['password']);
        return $user;
    }
}

// ===================== Rate-limit đăng nhập =====================

if (!function_exists('lms_login_attempt_key')) {
    /**
     * Khóa rate-limit theo username + IP (tránh brute-force).
     *
     * @param string $username
     * @return string
     */
    function lms_login_attempt_key($username)
    {
        $ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'cli';
        return 'lms_login:' . strtolower((string) $username) . ':' . $ip;
    }
}

if (!function_exists('lms_login_attempt_register')) {
    /**
     * Ghi nhận 1 lần đăng nhập thất bại (dùng session + DB cho đơn giản ở MVP).
     * Trả về số lần sai liên tiếp hiện tại.
     *
     * @param string $username
     * @return int
     */
    function lms_login_attempt_register($username)
    {
        lms_session_ensure();
        $key = lms_login_attempt_key($username);
        $n = isset($_SESSION[$key]) ? (int) $_SESSION[$key] : 0;
        $n++;
        $_SESSION[$key] = $n;
        $_SESSION[$key . ':first'] = time();
        return $n;
    }
}

if (!function_exists('lms_login_attempt_clear')) {
    function lms_login_attempt_clear($username)
    {
        lms_session_ensure();
        $key = lms_login_attempt_key($username);
        unset($_SESSION[$key], $_SESSION[$key . ':first']);
    }
}

if (!function_exists('lms_is_login_locked')) {
    /**
     * Sai 5 lần trong 15 phút → khoá 15 phút (theo SRS SYS-03).
     *
     * @param string $username
     * @return bool
     */
    function lms_is_login_locked($username)
    {
        lms_session_ensure();
        $key = lms_login_attempt_key($username);
        $n = isset($_SESSION[$key]) ? (int) $_SESSION[$key] : 0;
        if ($n < 5) {
            return false;
        }
        $first = isset($_SESSION[$key . ':first']) ? (int) $_SESSION[$key . ':first'] : 0;
        return (time() - $first) < 900; // 15 phút
    }
}

// ===================== RBAC =====================

if (!function_exists('lms_check_permission')) {
    /**
     * Kiểm tra quyền truy cập module của user LMS hiện tại.
     *
     * Phân 2 cấp:
     *  - Page-level (hàm này): user có được vào module không — dựa trên
     *    db_lms_role_permissions (theo role).
     *  - Row-level: service tự lọc (VD GV chỉ thấy vi phạm của mình) — xem
     *    Huong-Dan-Phat-Trien-Page.md §3.5.
     *
     * @param LmsDb  $d
     * @param array  $user     row db_lms_users đã xác thực (cần 'role')
     * @param string $alias    alias module trong db_lms_modules
     * @param string $action   'view' | 'edit' | 'delete'
     * @param string $portal   portal hiện tại ('admin' | 'teacher' | 'student')
     * @return bool
     */
    function lms_check_permission(LmsDb $d, array $user, $alias, $action = 'view', $portal = null)
    {
        if (empty($user['role'])) {
            return false;
        }
        if ($portal === null) {
            $portal = defined('_LMS_PORTAL') ? _LMS_PORTAL : 'admin';
        }

        // Sai portal → không bao giờ cho (D9)
        if ($user['role'] !== $portal) {
            return false;
        }

        // super_admin bypass (chỉ portal admin)
        if ($portal === 'admin'
            && !empty($user['is_super_admin']) && (int) $user['is_super_admin'] === 1) {
            return true;
        }

        $map = array('view' => 'can_view', 'edit' => 'can_edit', 'delete' => 'can_delete');
        $col = isset($map[$action]) ? $map[$action] : 'can_view';

        $row = $d->safeFetchOne(
            'select p.' . $col . ' as allowed'
            . ' from db_lms_modules m'
            . ' join db_lms_role_permissions p on p.id_module = m.id and p.role = ?'
            . ' where m.alias = ? and m.portal = ? limit 1',
            array($user['role'], $alias, $portal)
        );
        // safeFetchOne trả array() rỗng khi không có dòng → isset() để tránh notice
        return is_array($row) && isset($row['allowed']) && (int) $row['allowed'] === 1;
    }
}

// ===================== Escape output =====================

if (!function_exists('e')) {
    /**
     * Escape HTML output (anti-XSS). Trùng tên helper admin/ nhưng LMS load riêng
     * nên không xung đột (function_exists guard).
     *
     * @param mixed $value
     * @return string
     */
    function e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
