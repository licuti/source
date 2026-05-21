<?php
/**
 * ============================================================
 *  Modern Application Autoloader & Bootstrapper
 *  Centralizes class loading and helper initialization.
 * ============================================================
 */

// ── 1. Register Class Autoloader ─────────────────────────────
spl_autoload_register(function ($className) {
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR;

    // Support for App namespace
    if (strpos($className, 'App\\') === 0) {
        $relativeClass = substr($className, 4);
        $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }

    // Support for legacy Model classes (no namespace)
    if ($className === 'Model' || strpos($className, 'Model') !== false) {
        $paths = [
            $baseDir . 'Core' . DIRECTORY_SEPARATOR . $className . '.php',
            $baseDir . 'Models' . DIRECTORY_SEPARATOR . $className . '.php',
        ];

        foreach ($paths as $file) {
            if (file_exists($file)) {
                require_once $file;
                return;
            }
        }
    }
});

// ── 2. Load Helpers Automatically ────────────────────────────
$helpersPath = __DIR__ . DIRECTORY_SEPARATOR . 'Helpers' . DIRECTORY_SEPARATOR . '*.php';
foreach (glob($helpersPath) as $helperFile) {
    require_once $helperFile;
}

// ── 2.5 Load Environment Variables ────────────────────────────
if (function_exists('loadEnv')) {
    loadEnv(dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env');
}

// ── 3. Legacy Constants Hack ────────────────────────────────
if (!defined('_source')) {
    define('_source', 'resources/views/');
}
