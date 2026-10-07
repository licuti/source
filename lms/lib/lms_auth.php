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
        return is_array($row) && (int) $row['allowed'] === 1;
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
