<?php
/**
 * ============================================================
 *  Frontend Entry Point (Laravel-Style)
 * ============================================================
 */
require_once 'app/autoload.php';

use App\Core\App;
use App\Core\ExceptionHandler;

// ── Bắt Fatal Error / Parse Error (PHP 7+) ───────────────────
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        $e = new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']);
        ExceptionHandler::handle($e);
    }
});

// Initialize and Run the Application
$app = App::getInstance();
$app->boot();
$app->run();