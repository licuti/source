<?php
/**
 * Admin LMS — Thống kê phí dịch vụ (ADM-07, Phase 2).
 *
 * SRS §ADM-07: thống kê phí DV theo tháng + xuất Excel (CSV) + duyệt giao dịch.
 *
 * Action:
 *  - 'man'          (GET)  → danh sách GV × tháng (tổng thu/phạt/thưởng/thuế/thực nhận + status).
 *  - 'chi-tiet'     (GET)  → 1 GV × tháng: bảng transactions + form thêm điều chỉnh + nút duyệt.
 *  - 'them-dieu-chinh' (POST) → thêm penalty/bonus/adjustment (CSRF + addAdjustment + PRG).
 *  - 'duyet'        (POST) → advances status draft→pending→paid (CSRF + setMonthStatus + PRG).
 *  - 'xuat'         (GET)  → CSV tháng (toàn bộ GV), không dùng template.
 *
 * Router đã check auth gate + quyền view; action POST thêm guard edit (§3.4).
 * KHÔNG echo HTML (PRG). PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d       = lms_db();
$user    = lms_current_user();
$alias   = 'lms-phi-dv';
$paySvc  = new \Lms\PayrollService($d);

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'chi-tiet':
        lms_form_guard($d, $user, $alias, 'view', 'admin');
        lpd_detail($paySvc);
        break;

    case 'them-dieu-chinh':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lpd_add_adjustment($paySvc);
        break;

    case 'duyet':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lpd_approve($paySvc);
        break;

    case 'xuat':
        lms_form_guard($d, $user, $alias, 'view', 'admin');
        lpd_export_csv($paySvc);
        break;

    default:
        lpd_list($paySvc);
        break;
}

// ===================== Helpers =====================

/**
 * Tháng mặc định = tháng trước (ngày 1 hàng tháng chốt tháng vừa qua).
 *
 * @return string 'Y-m'
 */
function lpd_default_month()
{
    $ts = strtotime('first day of previous month');
    return $ts !== false ? date('Y-m', $ts) : date('Y-m');
}

/**
 * Tháng hợp lệ từ query, mặc định tháng trước.
 *
 * @return string
 */
function lpd_month_from_query()
{
    $month = isset($_GET['month']) ? (string) $_GET['month'] : '';
    return preg_match('/^\d{4}-\d{2}$/', $month) ? $month : lpd_default_month();
}

/**
 * Nhãn hiển thị cho status.
 *
 * @param string $status
 * @return string
 */
function lpd_status_label($status)
{
    switch ((string) $status) {
        case 'draft':
            return 'Nháp';
        case 'pending':
            return 'Chờ duyệt';
        case 'paid':
            return 'Đã thanh toán';
        default:
            return '—';
    }
}

/**
 * Badge CSS cho status.
 *
 * @param string $status
 * @return string
 */
function lpd_status_badge($status)
{
    switch ((string) $status) {
        case 'draft':
            return 'lms-badge-secondary';
        case 'pending':
            return 'lms-badge-warning';
        case 'paid':
            return 'lms-badge-success';
        default:
            return 'lms-badge-secondary';
    }
}

/**
 * Nhãn tiếng Việt cho type transaction.
 *
 * @param string $type
 * @return string
 */
function lpd_type_label($type)
{
    $labels = array(
        'salary_session' => 'Lương buổi',
        'penalty'        => 'Phạt',
        'bonus'          => 'Thưởng',
        'tax'            => 'Thuế TNCN',
        'deposit_deduct' => 'Trừ cọc',
        'adjustment'     => 'Điều chỉnh',
    );
    return isset($labels[$type]) ? $labels[$type] : $type;
}

// ===================== Danh sách GV × tháng =====================

function lpd_list(\Lms\PayrollService $paySvc)
{
    global $title, $template, $items, $total, $totalPages, $page,
           $filterMonth, $canEdit, $d, $user, $alias;

    $filterMonth = lpd_month_from_query();
    $page        = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage     = 20;

    $res = $paySvc->monthSummaryAll($filterMonth, $page, $perPage);

    $items      = $res['items'];
    $total      = (int) $res['total'];
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

    $canEdit = lms_check_permission($d, $user, $alias, 'edit', 'admin');

    $title    = 'Thống kê phí dịch vụ';
    $template = 'lms-phi-dv/hienthi';
}

// ===================== Chi tiết 1 GV × tháng =====================

function lpd_detail(\Lms\PayrollService $paySvc)
{
    global $title, $template, $teacherId, $teacher, $month, $summary,
           $transactions, $errors, $canEdit, $d, $user, $alias;

    $errors = array();

    $teacherId = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
    $month     = lpd_month_from_query();

    if ($teacherId <= 0) {
        lms_flash('error', 'Thiếu giáo viên.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $teacher = $d->safeFetchOne(
        'select user_id, teacher_code, full_name from db_lms_teachers where user_id = ? limit 1',
        array($teacherId)
    );
    if (empty($teacher) || !is_array($teacher)) {
        lms_flash('error', 'Giáo viên không tồn tại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $summary       = $paySvc->monthSummary($teacherId, $month);
    $transactions  = $paySvc->listTransactions($teacherId, $month);
    $canEdit       = lms_check_permission($d, $user, $alias, 'edit', 'admin');

    $title    = 'Chi tiết phí dịch vụ — ' . $teacher['teacher_code'];
    $template = 'lms-phi-dv/chitiet';
}

// ===================== Thêm điều chỉnh (POST → PRG) =====================

function lpd_add_adjustment(\Lms\PayrollService $paySvc)
{
    global $alias, $user;

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $teacherId = isset($_POST['teacher_id']) ? (int) $_POST['teacher_id'] : 0;
    $month     = isset($_POST['month']) ? (string) $_POST['month'] : '';
    $type      = isset($_POST['type']) ? (string) $_POST['type'] : '';
    $amount    = isset($_POST['amount']) ? (string) $_POST['amount'] : '';
    $note      = isset($_POST['note']) ? trim((string) $_POST['note']) : '';
    $actorId   = isset($user['id']) ? (int) $user['id'] : 0;

    if ($teacherId <= 0 || !preg_match('/^\d{4}-\d{2}$/', $month)) {
        lms_flash('error', 'Dữ liệu không hợp lệ.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    // amount nhập dạng "500000" hoặc "-500000" (điều chỉnh giữ dấu).
    $amountNorm = str_replace(array(',', ' '), '', $amount);
    if (!is_numeric($amountNorm)) {
        lms_flash('error', 'Số tiền không hợp lệ.');
        header('Location: ' . lms_url($alias, 'chi-tiet', array('teacher_id' => $teacherId, 'month' => $month)));
        exit;
    }

    $id = $paySvc->addAdjustment($teacherId, $month, $type, (float) $amountNorm, $note, $actorId);

    if ($id !== null) {
        lms_flash('success', 'Đã thêm điều chỉnh.');
        header('Location: ' . lms_url($alias, 'chi-tiet', array('teacher_id' => $teacherId, 'month' => $month)));
        exit;
    }

    $errors = $paySvc->errors();
    lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể thêm điều chỉnh.');
    header('Location: ' . lms_url($alias, 'chi-tiet', array('teacher_id' => $teacherId, 'month' => $month)));
    exit;
}

// ===================== Duyệt (POST → PRG) =====================

function lpd_approve(\Lms\PayrollService $paySvc)
{
    global $alias, $user;

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $teacherId = isset($_POST['teacher_id']) ? (int) $_POST['teacher_id'] : 0;
    $month     = isset($_POST['month']) ? (string) $_POST['month'] : '';
    $newStatus = isset($_POST['status']) ? (string) $_POST['status'] : '';

    if ($teacherId <= 0 || !preg_match('/^\d{4}-\d{2}$/', $month)) {
        lms_flash('error', 'Dữ liệu không hợp lệ.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if ($paySvc->setMonthStatus($teacherId, $month, $newStatus)) {
        lms_flash('success', 'Đã chuyển sang "' . lpd_status_label($newStatus) . '".');
    } else {
        $errors = $paySvc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể duyệt.');
    }

    header('Location: ' . lms_url($alias, 'chi-tiet', array('teacher_id' => $teacherId, 'month' => $month)));
    exit;
}

// ===================== Xuất CSV (không template) =====================

function lpd_export_csv(\Lms\PayrollService $paySvc)
{
    global $alias;

    $month = lpd_month_from_query();

    $res = $paySvc->monthSummaryAll($month, 1, 1000);

    $filename = 'lms-phi-dv-' . $month . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate');

    // UTF-8 BOM để Excel mở tiếng Việt không lỗi.
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    if ($out === false) {
        exit;
    }

    fputcsv($out, array(
        'Ma GV',
        'Ten giao vien',
        'Thang',
        'Tong thu',
        'Tong phat',
        'Tong thuong',
        'Thue TNCN',
        'Thuc nhan',
        'Trang thai',
    ));

    foreach ($res['items'] as $r) {
        fputcsv($out, array(
            $r['teacher_code'],
            $r['full_name'],
            $month,
            number_format((float) $r['tong_thu'], 2, '.', ''),
            number_format((float) $r['tong_phat'], 2, '.', ''),
            number_format((float) $r['tong_thuong'], 2, '.', ''),
            number_format((float) $r['thue'], 2, '.', ''),
            number_format((float) $r['thuc_nhan'], 2, '.', ''),
            lpd_status_label($r['status']),
        ));
    }

    fclose($out);
    exit;
}
