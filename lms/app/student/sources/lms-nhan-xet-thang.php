<?php
/**
 * Student — Nhận xét tháng (STU-04, mockup §10 + §11).
 *
 * Action:
 *   - 'man' (GET) → list class cards (collapse) + dropdown tháng.
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
    global $title, $template, $items, $months, $month, $studentId, $d;

    // Tháng xem: ?month=YYYY-MM (validate), mặc định tháng có dữ liệu mới nhất
    $month = isset($_GET['month']) ? trim((string) $_GET['month']) : '';
    if ($month !== '' && !preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = '';
    }

    $svc = new \Lms\MonthlyReviewService($d);
    $data = $svc->listForStudent($studentId, $month !== '' ? $month : null);
    $items = $data['items'];
    $months = $data['months'];

    // Chưa chọn tháng → mặc định tháng mới nhất có nhận xét
    if ($month === '' && !empty($months)) {
        reset($months);
        $month = (string) key($months); // months sắp xếp giảm dần (rsort)
        $data = $svc->listForStudent($studentId, $month);
        $items = $data['items'];
    }

    $title    = 'Nhận xét tháng';
    $template = 'lms-nhan-xet-thang/hienthi';
}
