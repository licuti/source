<?php
/**
 * Teacher LMS — Vi phạm chất lượng dịch vụ / tick của tôi (Phase 2).
 *
 * SRS §TCH-05 (mặt vi phạm): GV xem toàn bộ tick của chính mình + tổng hợp
 * (raw / miễn / thực tế / ngưỡng) + cảnh báo khi sắp đạt ngưỡng (BR-TICK-03).
 *
 * Action:
 *  - 'man' (GET) → danh sách violations của CHÍNH MÌNH (phân trang) +
 *    panel tổng hợp all-time.
 *
 * Row-level: teacher_id = user id hiện tại.
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

// ===================== Danh sách tick của mình =====================

function lvpv_list()
{
    global $title, $template, $teacherId, $items, $total, $page, $totalPages,
           $tickSummary, $warnMessage, $d, $alias;

    if ($teacherId <= 0) {
        lms_flash('error', 'Vui lòng đăng nhập lại.');
        header('Location: ' . lms_url('', 'login'));
        exit;
    }

    $tickSvc = new \Lms\TickService($d);

    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = 20;
    $res = $tickSvc->listViolations(array('teacher_id' => $teacherId), $page, $perPage);
    $total = (int) $res['total'];
    $totalPages = max(1, (int) ceil($total / $perPage));
    if ($page > $totalPages) {
        $page = $totalPages;
        $res = $tickSvc->listViolations(array('teacher_id' => $teacherId), $page, $perPage);
    }
    $items = $res['items'];

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

    $title    = 'Các vi phạm';
    $template = 'lms-vi-pham/hienthi';
}
