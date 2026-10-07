<?php
/**
 * Bootstrap cho PHPUnit: nạp composer autoload + LMS lib.
 *
 * Test DB mặc định là phuongnamv_db_lms_test (bản copy), KHÔNG đụng DB thật.
 * Override qua env LMS_TEST_DB (chỉ nên dùng DB test).
 */

if (!defined('_LMS_PORTAL')) {
    define('_LMS_PORTAL', 'admin');
}

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/lms/lib/lms_bootstrap.php';
