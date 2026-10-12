<?php
/**
 * Teacher LMS — Vi phạm nội quy / tick của tôi (Phase 2).
 *
 * SRS §TCH-05 (mặt vi phạm): GV xem tick của chính mình + tổng hợp + cảnh báo
 * khi sắp đạt ngưỡng (BR-TICK-03).
 *
 * Action:
 *  - 'man' (GET) → danh sách violations của CHÍNH MÌNH (filter month/reason) +
 *    panel tổng hợp (raw / miễn / thực tế / ngưỡng).
 *
 * Row-level: teacher_id = user id hiện tại (source phải truyền, chặn xem của
 * người khác — listViolations không tự biết user).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d         = lms_db();
$user      = lms_current_user();
$alias     = 'lms-vi-pham';
$teacherId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    default:
        lvpv_list();
        break;
}

// ===================== Helpers =====================

function lvpv_default_month()
{
    $ts = strtotime('first day of previous month');
    return $ts !== false ? date('Y-m', $ts) : date('Y-m');
}

// ===================== Danh sách tick của mình =====================

function lvpv_list()
{
    global $title, $template, $teacherId, $month, $filterReason,
           $items, $total, $tickSummary, $warnMessage,
           $d, $user, $alias;

    if ($teacherId <= 0) {
        lms_flash('error', 'Vui lòng đăng nhập lại.');
        header('Location: ' . lms_url('', 'login'));
        exit;
    }

    $month = isset($_GET['month']) ? (string) $_GET['month'] : '';
    if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
        $month = lvpv_default_month();
    }
    $filterReason = isset($_GET['reason']) ? (string) $_GET['reason'] : '';

    $tickSvc = new \Lms\TickService($d);

    $res = $tickSvc->listViolations(array(
        'teacher_id' => $teacherId,
        'month'      => $month,
        'reason'     => $filterReason,
    ));

    $items = $res['items'];
    $total = (int) $res['total'];

    $tickSummary = $tickSvc->teacherTickSummary($teacherId);

    // Cảnh báo khi tick thực tế sắp/vượt ngưỡng (BR-TICK-03).
    $warnMessage = '';
    if (is_array($tickSummary['threshold'])) {
        $warnAt  = (int) $tickSummary['threshold']['ticks_to_warn'];
        $demoteAt = (int) $tickSummary['threshold']['ticks_to_demote_fee'];
        $real    = (int) $tickSummary['real'];

        if ($real >= $demoteAt) {
            $warnMessage = 'Bạn đã vượt ngưỡng ' . $demoteAt . ' tick — % phí dịch vụ sẽ bị hạ'
                . ' vào tháng chốt lương tiếp theo. Hãy chấm bài đúng hạn.';
        } elseif ($real >= $warnAt) {
            $warnMessage = 'Bạn đang ở mức ' . $real . '/' . $demoteAt . ' tick. Còn '
                . ($demoteAt - $real) . ' tick nữa là % phí dịch vụ sẽ bị hạ.';
        }
    }

    $title    = 'Vi phạm nội quy';
    $template = 'lms-vi-pham/hienthi';
}
