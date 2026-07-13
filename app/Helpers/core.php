<?php
/**
 * ============================================================
 *  CORE FRAMEWORK HELPERS
 *  Chỉ chứa các hàm nền tảng, KHÔNG CHỨA logic ứng dụng/DB.
 * ============================================================
 */

if (!function_exists('base_path')) {
    function base_path($path = '') {
        $base = dirname(dirname(__DIR__));
        return $path ? $base . DIRECTORY_SEPARATOR . ltrim($path, '/') : $base;
    }
}

if (!function_exists('app_path')) {
    function app_path($path = '') {
        return base_path('app' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/') : ''));
    }
}

if (!function_exists('config_path')) {
    function config_path($path = '') {
        return base_path('config' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/') : ''));
    }
}

if (!function_exists('public_path')) {
    function public_path($path = '') {
        return base_path($path);
    }
}

if (!function_exists('config')) {
    function config($key = null, $default = null) {
        $app = \App\Core\App::getInstance();
        if (isset($app->container) && $app->container->bound('config')) {
            $repository = $app->container->make('config');
            if (is_array($key)) {
                $repository->set($key);
                return true;
            }
            if ($key === null) {
                return $repository->all();
            }
            return $repository->get($key, $default);
        }
        
        // Fallback tạm thời nếu App chưa boot Container config
        static $legacyConfig = [];
        if (empty($legacyConfig)) {
            $basePath = dirname(dirname(dirname(__FILE__)));
            $legacyConfig = array_merge(
                [
                    'database'           => include $basePath . '/config/database.php',
                    'lang'               => include $basePath . '/config/languages.php',
                    'route_translations' => include $basePath . '/config/route_translations.php',
                    'modules'            => include $basePath . '/config/modules.php',
                ],
                include $basePath . '/config/app.php'
            );
        }
        if (is_array($key)) {
            $legacyConfig = array_replace_recursive($legacyConfig, $key);
            return true;
        }
        if ($key === null) {
            return $legacyConfig;
        }
        $parts = explode('.', $key);
        $data = $legacyConfig;
        foreach ($parts as $part) {
            if (!is_array($data) || !isset($data[$part])) {
                return $default;
            }
            $data = $data[$part];
        }
        return $data;
    }
}

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('dd')) {
    function dd(...$args) {
        echo '<div style="background: #2d2d2d; color: #f8f8f2; padding: 15px; border-radius: 5px; margin: 10px; font-family: monospace; font-size: 14px; border-left: 5px solid #ff79c6; box-shadow: 0 4px 6px rgba(0,0,0,0.3); white-space: pre-wrap; word-break: break-all;">';
        foreach ($args as $arg) {
            echo '<div style="margin-bottom: 20px;">';
            var_dump($arg);
            echo '</div>';
        }
        echo '</div>';
        die;
    }
}

if (!function_exists('view')) {
    function view($template, $data = []) {
        $view = new \App\Core\View();
        return $view->render($template, $data);
    }
}

if (!function_exists('arr_get')) {
    function arr_get($array, $key, $default = null) {
        if (!is_array($array)) return $default;
        if (array_key_exists($key, $array)) return $array[$key];
        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return $default;
            }
        }
        return $array;
    }
}

if (!function_exists('hasPermission')) {
    /**
     * Kiểm tra quyền truy cập của user hiện tại cho một module
     * @param string $module_route Prefix của route (vd: 'admin.category')
     * @param string $action Hành động ('view', 'add', 'edit', 'delete')
     * @return bool
     */
    function hasPermission($module_route, $action = 'view') {
        return \App\Core\Auth\Gate::check($module_route, $action);
    }
}

if (!function_exists('old')) {
    function old($key = null, $default = '') {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $oldInput = $_SESSION['_old_input'] ?? [];
        if ($key === null) return $oldInput;
        return arr_get($oldInput, $key, $default);
    }
}

if (!function_exists('errors')) {
    function errors($key = null) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $errors = $_SESSION['_errors'] ?? [];
        if ($key === null) return $errors;
        return $errors[$key] ?? null;
    }
}

if (!function_exists('__')) {
    function __($key) {
        try {
            $translator = \App\Core\App::getInstance()->container->make(\App\Core\Contracts\TranslatorInterface::class);
            return $translator->translate($key);
        } catch (\Exception $e) {
            // Fallback nếu chưa đăng ký Translator
            return $key;
        }
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['token'])) {
            $_SESSION['token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['token'];
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field() {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('session')) {
    function session($key = null, $default = null) {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($key === null) return $_SESSION;
        if (is_array($key)) {
            foreach ($key as $k => $v) $_SESSION[$k] = $v;
            return true;
        }
        $value = $_SESSION[$key] ?? $default;
        if (in_array($key, ['success', 'error']) && isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
        return $value;
    }
}

if (!function_exists('request')) {
    function request() {
        return \App\Core\App::getInstance()->request;
    }
}

if (!function_exists('response')) {
    function response($content = '', $statusCode = 200) {
        return new \App\Core\Response($content, $statusCode);
    }
}
