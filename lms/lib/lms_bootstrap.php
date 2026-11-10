<?php
/**
 * Bootstrap LMS — nạp cho mọi request portal (lms/*.php) và CLI (cron/*).
 *
 * Cách dùng:
 *   require_once __DIR__ . '/lms/lib/lms_bootstrap.php';
 *
 * Entry point portal phải define('_LMS_PORTAL', 'admin'|'teacher'|'student')
 * TRƯỚC khi require file này.
 *
 * Không phụ thuộc code e-commerce (admin/) — D2 + plan §4.1.
 * PHP 7.3 compatible.
 */

if (defined('_LMS_BOOTSTRAPPED')) {
    return;
}
define('_LMS_BOOTSTRAPPED', true);

if (!defined('_LMS_ROOT')) {
    define('_LMS_ROOT', dirname(__DIR__, 2));
}
if (!defined('_LMS_DIR')) {
    define('_LMS_DIR', dirname(__DIR__));
}
if (!defined('_LMS_PORTAL')) {
    define('_LMS_PORTAL', 'admin'); // mặc định; entry point override
}

require_once __DIR__ . '/lms_db.php';
require_once __DIR__ . '/lms_auth.php';
require_once __DIR__ . '/lms_url.php';
require_once __DIR__ . '/lms_upload.php';
require_once __DIR__ . '/lms_cron.php';
require_once __DIR__ . '/lms_enums.php';
require_once __DIR__ . '/lms_calendar.php';

// ===================== Autoload namespace Lms\ =====================
// Lms\Foo\Bar -> lms/services/Lms/Foo/Bar.php
// Dùng cho service layer (plan Phase 0.4). Composer autoload (tests) cùng rule.

if (!function_exists('lms_autoload')) {
    function lms_autoload($class)
    {
        $prefix = 'Lms\\';
        $len = strlen($prefix);
        if (strncmp($class, $prefix, $len) !== 0) {
            return;
        }
        $relative = substr($class, $len);
        $file = __DIR__ . '/../services/Lms/' . str_replace('\\', '/', $relative) . '.php';
        if (is_file($file)) {
            require $file;
        }
    }
    spl_autoload_register('lms_autoload');
}

// ===================== Load .env =====================

// Helper env_load() nằm ở root dự án (file env.php) — vị trí trung lập,
// e-commerce và LMS cùng dùng mà không phụ thuộc lẫn nhau (D2).
if (!function_exists('env_load')) {
    require_once _LMS_ROOT . DIRECTORY_SEPARATOR . 'env.php';
}

if (!function_exists('lms_db_config')) {
    /**
     * Config DB LMS từ .env (dùng chung phuongnamv_db — D2).
     *
     * @return array array(host, name, user, pass)
     */
    function lms_db_config()
    {
        $env = env_load(_LMS_ROOT . DIRECTORY_SEPARATOR . '.env');
        // Cho phép override qua env (VD: smoke test CLI trỏ sang DB test)
        $name = getenv('LMS_DB_NAME');
        if ($name === false || $name === '') {
            $name = isset($env['DB_NAME']) ? $env['DB_NAME'] : 'phuongnamv_db';
        }
        return array(
            'host' => isset($env['DB_HOST']) ? $env['DB_HOST'] : 'localhost',
            'name' => $name,
            'user' => isset($env['DB_USER']) ? $env['DB_USER'] : 'root',
            'pass' => isset($env['DB_PASS']) ? $env['DB_PASS'] : '',
        );
    }
}

// ===================== Connection global =====================

if (!function_exists('lms_db')) {
    /**
     * Lấy LmsDb chia sẻ cho request hiện tại.
     *
     * @return LmsDb
     */
    function lms_db()
    {
        static $db = null;
        if ($db === null) {
            $db = new LmsDb(lms_db_config());
        }
        return $db;
    }
}

if (!function_exists('lms_current_user')) {
    /**
     * Lấy user LMS đã đăng nhập (từ session) hoặc null.
     * Entry point portal sẽ set $_SESSION['lms_user'] sau khi xác thực.
     *
     * @return array|null
     */
    function lms_current_user()
    {
        if (session_status() !== PHP_SESSION_ACTIVE && !headers_sent()) {
            @session_start();
        }
        if (!empty($_SESSION['lms_user']) && is_array($_SESSION['lms_user'])) {
            return $_SESSION['lms_user'];
        }
        return null;
    }
}
