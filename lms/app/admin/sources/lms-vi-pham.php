<?php
/**
 * Admin LMS — Quản lý vi phạm (tick) (ADM-06, Phase 2).
 *
 * SRS §ADM-06: cấu hình rule ngưỡng + xem log tick phạt + thêm/xoá tick thủ công.
 *
 * Action:
 *   - 'man'        (GET)  → danh sách tick + filter (GV / lý do / tháng / nguồn) + phân trang.
 *   - 'them'       (GET/POST) → form thêm tick thủ công (POST: CSRF + addManualTick + PRG).
 *   - 'xoa'        (POST) → xoá tick thủ công (CSRF + deleteTick + PRG).
 *   - 'nguong'     (GET)  → danh sách + form thêm/sửa violation_thresholds.
 *   - 'luu-nguong' (POST) → lưu threshold (CSRF + saveThreshold + PRG).
 *   - 'xoa-nguong' (POST) → xoá threshold (CSRF + deleteThreshold + PRG).
 *
 * Router đã check auth gate + quyền view; action POST thêm guard edit/delete
 * (Huong-Dan-Phat-Trien-Page.md §3.4). KHÔNG echo HTML (PRG).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d       = lms_db();
$user    = lms_current_user();
$alias   = 'lms-vi-pham';
$tickSvc = new \Lms\TickService($d);

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'them':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lvp_add_form($tickSvc);
        break;

    case 'xoa':
        lms_form_guard($d, $user, $alias, 'delete', 'admin');
        lvp_delete_tick($tickSvc);
        break;

    case 'nguong':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lvp_thresholds($tickSvc);
        break;

    case 'luu-nguong':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lvp_save_threshold($tickSvc);
        break;

    case 'xoa-nguong':
        lms_form_guard($d, $user, $alias, 'delete', 'admin');
        lvp_delete_threshold($tickSvc);
        break;

    default:
        lvp_list($tickSvc);
        break;
}

// ===================== Danh sách tick =====================

function lvp_list(\Lms\TickService $tickSvc)
{
    global $title, $template, $items, $total, $totalPages, $page,
           $filterTeacherId, $filterReason, $filterMonth, $filterAuto,
           $teachers, $canEdit, $canDelete, $d, $user, $alias;

    $filterTeacherId = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
    $filterReason    = isset($_GET['reason']) ? (string) $_GET['reason'] : '';
    $filterMonth     = isset($_GET['month']) ? (string) $_GET['month'] : '';
    $filterAuto      = isset($_GET['auto']) ? (string) $_GET['auto'] : '';
    $page            = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage         = 20;

    $res = $tickSvc->listViolations(array(
        'teacher_id' => $filterTeacherId,
        'reason'     => $filterReason,
        'month'      => $filterMonth,
        'auto'       => $filterAuto,
    ), $page, $perPage);

    $items      = $res['items'];
    $total      = (int) $res['total'];
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

    $teachers = $d->safeFetch(
        'select t.user_id, t.teacher_code, t.full_name from db_lms_teachers t order by t.teacher_code'
    );

    $canEdit   = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'admin');

    $title    = 'Quản lý vi phạm (tick)';
    $template = 'lms-vi-pham/hienthi';
}

// ===================== Thêm tick thủ công =====================

function lvp_add_form(\Lms\TickService $tickSvc)
{
    global $title, $template, $errors, $teachers, $tickSummary, $d, $user, $alias;

    $errors  = array();
    $teachers = $d->safeFetch(
        'select t.user_id, t.teacher_code, t.full_name, t.status from db_lms_teachers t'
        . ' order by t.teacher_code'
    );

    // Tóm tắt tick của GV đang chọn (hiển thị để admin cân nhắc)
    $tickSummary = null;
    $selectedTeacherId = 0;
    $isPost = isset($_SERVER['REQUEST_METHOD']) && (string) $_SERVER['REQUEST_METHOD'] === 'POST';
    if ($isPost) {
        $selectedTeacherId = isset($_POST['teacher_id']) ? (int) $_POST['teacher_id'] : 0;
    } elseif (isset($_GET['teacher_id'])) {
        $selectedTeacherId = (int) $_GET['teacher_id'];
    }
    if ($selectedTeacherId > 0) {
        $tickSummary = $tickSvc->teacherTickSummary($selectedTeacherId);
    }

    if ($isPost) {
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
            header('Location: ' . lms_url($alias, 'them'));
            exit;
        }

        $reason     = isset($_POST['reason']) ? (string) $_POST['reason'] : '';
        $tickAmount = isset($_POST['tick_amount']) ? (int) $_POST['tick_amount'] : 0;
        $note       = isset($_POST['note']) ? trim((string) $_POST['note']) : '';
        $adminId    = isset($user['id']) ? (int) $user['id'] : 0;

        if ($tickSvc->addManualTick($selectedTeacherId, $reason, $tickAmount, $note, $adminId)) {
            lms_flash('success', 'Đã thêm tick thủ công.');
            header('Location: ' . lms_url($alias));
            exit;
        }

        $errors = $tickSvc->errors();
        // Làm mới tóm tắt sau khi thêm thất bại
        if ($selectedTeacherId > 0) {
            $tickSummary = $tickSvc->teacherTickSummary($selectedTeacherId);
        }
    }

    $title    = 'Thêm tick thủ công';
    $template = 'lms-vi-pham/them';
}

// ===================== Xoá tick (POST → PRG) =====================

function lvp_delete_tick(\Lms\TickService $tickSvc)
{
    global $alias;

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID tick.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if ($tickSvc->deleteTick($id)) {
        lms_flash('success', 'Đã xoá tick thủ công.');
    } else {
        $errors = $tickSvc->errors();
        lms_flash('error', isset($errors['id'])
            ? $errors['id']
            : (isset($errors['system']) ? $errors['system'] : 'Không thể xoá tick.'));
    }

    header('Location: ' . lms_url($alias));
    exit;
}

// ===================== Cấu hình ngưỡng (violation_thresholds) =====================

function lvp_thresholds(\Lms\TickService $tickSvc)
{
    global $title, $template, $thresholds, $editRow, $errors, $d, $user, $alias;

    if (!isset($errors) || !is_array($errors)) {
        $errors = array();
    }
    $thresholds  = $tickSvc->listThresholds();

    // Ưu tiên id từ GET (click sửa); khi validate lỗi (POST) thì giữ id từ POST.
    $editId = 0;
    if (isset($_GET['id'])) {
        $editId = (int) $_GET['id'];
    } elseif (isset($_POST['id'])) {
        $editId = (int) $_POST['id'];
    }

    $editRow = $editId > 0 ? $tickSvc->getThreshold($editId) : array();
    if ($editId > 0 && empty($editRow)) {
        lms_flash('error', 'Rule không tồn tại.');
        header('Location: ' . lms_url($alias, 'nguong'));
        exit;
    }

    $title    = 'Cấu hình ngưỡng vi phạm';
    $template = 'lms-vi-pham/nguong';
}

function lvp_save_threshold(\Lms\TickService $tickSvc)
{
    global $alias;

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, 'nguong'));
        exit;
    }

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

    $in = array(
        'min_classes'              => isset($_POST['min_classes']) ? $_POST['min_classes'] : 0,
        'max_classes'              => isset($_POST['max_classes']) ? $_POST['max_classes'] : '',
        'ticks_to_warn'            => isset($_POST['ticks_to_warn']) ? $_POST['ticks_to_warn'] : 0,
        'ticks_to_demote_fee'      => isset($_POST['ticks_to_demote_fee']) ? $_POST['ticks_to_demote_fee'] : 0,
        'fee_percentage_after_demote' => isset($_POST['fee_percentage_after_demote'])
            ? $_POST['fee_percentage_after_demote'] : 0,
        'ticks_exempted'           => isset($_POST['ticks_exempted']) ? $_POST['ticks_exempted'] : 0,
        'effective_from'           => isset($_POST['effective_from']) ? (string) $_POST['effective_from'] : '',
        'effective_to'             => isset($_POST['effective_to']) ? (string) $_POST['effective_to'] : '',
    );

    $thresholdId = $id > 0 ? $id : null;
    if ($tickSvc->saveThreshold($in, $thresholdId)) {
        lms_flash('success', $id > 0 ? 'Đã cập nhật rule.' : 'Đã tạo rule mới.');
        header('Location: ' . lms_url($alias, 'nguong'));
        exit;
    }

    // Lỗi validate: render lại form + giữ giá trị vừa nhập (như lms-lop).
    global $errors;
    $errors = $tickSvc->errors();
    lvp_thresholds($tickSvc);
}

function lvp_delete_threshold(\Lms\TickService $tickSvc)
{
    global $alias;

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, 'nguong'));
        exit;
    }

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID rule.');
        header('Location: ' . lms_url($alias, 'nguong'));
        exit;
    }

    if ($tickSvc->deleteThreshold($id)) {
        lms_flash('success', 'Đã xoá rule.');
    } else {
        $errors = $tickSvc->errors();
        lms_flash('error', isset($errors['id'])
            ? $errors['id']
            : (isset($errors['system']) ? $errors['system'] : 'Không thể xoá rule.'));
    }

    header('Location: ' . lms_url($alias, 'nguong'));
    exit;
}
