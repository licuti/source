<?php
/**
 * Student — Nhận xét tháng (STU-04, mockup student-ver2 §10 + §11).
 *
 * Action:
 *   - 'man' (GET) → lưới card lớp + dropdown tháng riêng từng lớp.
 *
 * Chỉ xem nhận xét đã published (published_at is not null) của lớp mình đang học.
 * Row-level tự nhiên qua db_lms_monthly_reviews.student_id (guide §3.5).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d    = lms_db();
$user = lms_current_user();
$alias = 'lms-nhan-xet-thang';
$studentId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    default:
        lmnx_list();
        break;
}

// ===================== Action: man =====================

function lmnx_list()
{
    global $title, $template, $items, $studentId, $d;

    $svc = new \Lms\MonthlyReviewService($d);
    $data = $svc->listForStudent($studentId);
    $items = $data['items'];

    $title    = 'Nhận xét tháng';
    $template = 'lms-nhan-xet-thang/hienthi';
}
