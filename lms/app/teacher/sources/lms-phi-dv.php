<?php
/**
 * Teacher LMS — Phí dịch vụ / lương (TCH-05, Phase 2).
 *
 * SRS §TCH-05: GV xem lương + thuế của mình theo tháng; banner cảnh báo khi
 * tick vi phạm đạt ngưỡng hạ phí (BR-TICK-03).
 *
 * Action:
 *  - 'man' (GET) → bảng transactions của CHÍNH MÌNH (filter month/type) +
 *    tổng hợp thực nhận (BR-PAY-04) + banner cảnh báo tick.
 *
 * Row-level: teacher_id = user id hiện tại (service không tự lọc, source phải
 * truyền để chặn xem của người khác).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d         = lms_db();
$user      = lms_current_user();
$alias     = 'lms-phi-dv';
$teacherId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    default:
        lpdv_list();
        break;
}

// ===================== Helpers =====================

function lpdv_default_month()
{
    $ts = strtotime('first day of previous month');
    return $ts !== false ? date('Y-m', $ts) : date('Y-m');
}

function lpdv_type_label($type)
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

// ===================== Danh sách giao dịch của mình =====================

function lpdv_list()
{
    global $title, $template, $teacherId, $month, $filterType,
           $transactions, $summary, $tickSummary, $demoteWarning,
           $d, $user, $alias;

    if ($teacherId <= 0) {
        lms_flash('error', 'Vui lòng đăng nhập lại.');
        header('Location: ' . lms_url('', 'login'));
        exit;
    }

    $month     = isset($_GET['month']) ? (string) $_GET['month'] : '';
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = lpdv_default_month();
    }
    $filterType = isset($_GET['type']) ? (string) $_GET['type'] : '';

    $paySvc = new \Lms\PayrollService($d);

    $transactions = $paySvc->listTransactions($teacherId, $month, $filterType);
    $summary      = $paySvc->monthSummary($teacherId, $month);

    // Banner cảnh báo tick (SRS TCH-05 + BR-TICK-03).
    $tickSvc      = new \Lms\TickService($d);
    $tickSummary  = $tickSvc->teacherTickSummary($teacherId);
    $demoteWarning = '';
    if (is_array($tickSummary['threshold'])) {
        $demoteAt = (int) $tickSummary['threshold']['ticks_to_demote_fee'];
        if ($tickSummary['real'] >= $demoteAt) {
            $demoteWarning = 'Bạn đang ở mức ' . $tickSummary['real'] . ' tick vi phạm —'
                . ' đã vượt ngưỡng ' . $demoteAt . ', % phí dịch vụ sẽ bị hạ tháng sau.'
                . ' Hãy chấm bài đúng hạn để giảm tick.';
        }
    }

    $title    = 'Phí dịch vụ';
    $template = 'lms-phi-dv/hienthi';
}
