<?php
/**
 * Admin LMS — Quản lý yêu cầu (ADM-05, Phase 3).
 *
 * Admin duyệt/từ chối các db_lms_requests do GV tạo từ portal giáo viên
 * (TCH-01/02). Xem SRS §ADM-05 + BR-SCHED-01/02.
 *
 * Action:
 *   - 'man'     (GET)  → danh sách + filter (status/type) + phân trang.
 *   - 'xem'     (GET)  → chi tiết request + payload + form duyệt/từ chối.
 *   - 'duyet'   (POST) → CSRF + approveRequest (transaction) + PRG.
 *   - 'tu-choi' (POST) → CSRF + rejectRequest (admin_note required) + PRG.
 *
 * Router đã check auth gate + quyền view; action POST thêm guard edit
 * (Huong-Dan-Phat-Trien-Page.md §3.4). KHÔNG echo HTML (PRG).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d            = lms_db();
$user         = lms_current_user();
$alias        = 'lms-yeu-cau';
$requestSvc   = new \Lms\RequestService($d);

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'xem':
        lmyc_detail($requestSvc);
        break;

    case 'duyet':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lmyc_approve($requestSvc);
        break;

    case 'tu-choi':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lmyc_reject($requestSvc);
        break;

    default:
        lmyc_list($requestSvc);
        break;
}

// ===================== Danh sách =====================

function lmyc_list(\Lms\RequestService $requestSvc)
{
    global $title, $template, $items, $total, $totalPages, $page,
           $filterStatus, $filterType, $filterTeacherId, $teachers,
           $canEdit, $pendingCount, $d, $user, $alias;

    $filterStatus    = isset($_GET['status']) ? (string) $_GET['status'] : '';
    $filterType      = isset($_GET['type']) ? (string) $_GET['type'] : '';
    $filterTeacherId = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
    $page            = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage         = 20;

    $res = $requestSvc->listRequestsForAdmin(array(
        'status'      => $filterStatus,
        'request_type' => $filterType,
        'teacher_id'  => $filterTeacherId,
    ), $page, $perPage);

    $items      = $res['items'];
    $total      = (int) $res['total'];
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

    $teachers = $d->safeFetch(
        'select t.user_id, t.teacher_code, t.full_name from db_lms_teachers t'
        . ' order by t.teacher_code'
    );

    $canEdit      = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $pendingCount = $requestSvc->countPending();

    $title    = 'Quản lý yêu cầu';
    $template = 'lms-yeu-cau/hienthi';
}

// ===================== Chi tiết =====================

function lmyc_detail(\Lms\RequestService $requestSvc)
{
    global $title, $template, $row, $canEdit, $pendingCount, $d, $user, $alias;

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID yêu cầu.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $row = $requestSvc->getRequestForAdmin($id);
    if (empty($row) || !is_array($row)) {
        lms_flash('error', 'Yêu cầu không tồn tại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $canEdit      = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $pendingCount = $requestSvc->countPending();

    $title    = 'Chi tiết yêu cầu';
    $template = 'lms-yeu-cau/xem';
}

// ===================== Duyệt (POST → PRG) =====================

function lmyc_approve(\Lms\RequestService $requestSvc)
{
    global $alias, $user;

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID yêu cầu.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, 'xem', array('id' => $id)));
        exit;
    }

    $adminId = isset($user['id']) ? (int) $user['id'] : 0;

    if ($requestSvc->approveRequest($id, $adminId, $_POST)) {
        lms_flash('success', 'Đã duyệt yêu cầu.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $errors = $requestSvc->errors();
    lms_flash('error', isset($errors['status'])
        ? $errors['status']
        : (isset($errors['system']) ? $errors['system'] : 'Không thể duyệt yêu cầu.'));
    header('Location: ' . lms_url($alias, 'xem', array('id' => $id)));
    exit;
}

// ===================== Từ chối (POST → PRG) =====================

function lmyc_reject(\Lms\RequestService $requestSvc)
{
    global $alias, $user;

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID yêu cầu.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, 'xem', array('id' => $id)));
        exit;
    }

    $adminId = isset($user['id']) ? (int) $user['id'] : 0;

    if ($requestSvc->rejectRequest($id, $adminId, $_POST)) {
        lms_flash('success', 'Đã từ chối yêu cầu.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $errors = $requestSvc->errors();
    lms_flash('error', isset($errors['admin_note'])
        ? $errors['admin_note']
        : (isset($errors['system']) ? $errors['system'] : 'Không thể từ chối yêu cầu.'));
    header('Location: ' . lms_url($alias, 'xem', array('id' => $id)));
    exit;
}
