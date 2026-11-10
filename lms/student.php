<?php
/**
 * Entry point portal Học viên (T5).
 *
 * URL (D10): /lms/student, /lms/student/login, /lms/student/<alias>[/<action>]
 * URL cũ  : /lms/student.php?p=<alias>&a=<action> (vẫn hoạt động)
 *
 * KHÔNG include bất kỳ file nào trong admin/ e-commerce (P4).
 * PHP 7.3 compatible.
 */

define('_LMS_PORTAL', 'student');
require_once __DIR__ . '/lib/lms_bootstrap.php';

$act = isset($_GET['lms_route']) ? trim((string) $_GET['lms_route'], "/\r\n") : '';
$act = $act === '' ? (isset($_GET['a']) ? (string) $_GET['a'] : 'dashboard') : $act;

lms_session_ensure();

// Remember-me: cookie lms_remember (sha256, rotate) → $_SESSION['lms_user']
if (!isset($_SESSION['lms_user']) && !empty($_COOKIE['lms_remember'])) {
    lms_try_remember_me(lms_db());
}

switch ($act) {
    case 'login':
        require __DIR__ . '/lib/lms_login.php';
        break;

    case 'quen-mat-khau':
        require __DIR__ . '/lib/lms_password_reset.php';
        break;

    case 'logout':
        lms_logout(lms_db());
        header('Location: ' . lms_url('', 'login'));
        exit;

    case 'doi-mat-khau':
        require __DIR__ . '/lib/lms_change_password.php';
        break;

    case 'tim-kiem':
        require __DIR__ . '/lib/lms_search.php';
        break;

    default:
        require __DIR__ . '/lib/lms_router.php';
        break;
}
