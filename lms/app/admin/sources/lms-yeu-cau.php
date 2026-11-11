<?php
/**
 * Admin LMS — Quản lý yêu cầu (ADM-05).
 *
 * 5 tab theo mockup Tai-lieu-phat-trien/admin/Quản lý yêu cầu - *.png:
 *   them-buoi | test | duyet-kq | doi-lich | kiem-buoi
 *
 * Action:
 *   - 'man'           (GET)  → danh sách theo tab + lọc + phân trang.
 *   - 'xem'           (GET)  → chi tiết request add_session (duyệt/từ chối).
 *   - 'duyet'         (POST) → duyệt request (RequestService, transaction).
 *   - 'tu-choi'       (POST) → từ chối request (admin_note bắt buộc).
 *   - 'xoa-yeu-cau'   (POST) → xoá request đang chờ.
 *   - 'duyet-test'    (POST) → xác nhận gửi bài test draft.
 *   - 'xoa-test'      (POST) → xoá bài test draft.
 *   - 'ket-qua'       (GET)  → chi tiết KQ test (Test + Minitest của 1 HV).
 *   - 'cap-nhat-kq'   (POST) → lưu điểm/file chấm.
 *   - 'xac-nhan-kq'   (POST) → xác nhận kết quả test.
 *   - 'xoa-kq'        (POST) → xoá bài nộp.
 *
 * Router đã check auth gate + quyền view; action POST thêm guard edit/delete.
 * KHÔNG echo HTML sau POST (PRG).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d        = lms_db();
$user     = lms_current_user();
$alias    = 'lms-yeu-cau';
$requestSvc = new \Lms\RequestService($d);
$adminSvc   = new \Lms\AdminRequestService($d);

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'xem':
        lms_form_guard($d, $user, $alias, 'view', 'admin');
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

    case 'xoa-yeu-cau':
        lms_form_guard($d, $user, $alias, 'delete', 'admin');
        lmyc_delete_request($adminSvc);
        break;

    case 'duyet-test':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lmyc_publish_test($adminSvc);
        break;

    case 'xoa-test':
        lms_form_guard($d, $user, $alias, 'delete', 'admin');
        lmyc_delete_test($adminSvc);
        break;

    case 'ket-qua':
        lms_form_guard($d, $user, $alias, 'view', 'admin');
        lmyc_test_result($adminSvc);
        break;

    case 'cap-nhat-kq':
    case 'xac-nhan-kq':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lmyc_save_test_result($adminSvc, $action === 'xac-nhan-kq');
        break;

    case 'bat-tat-minitest':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lmyc_toggle_minitest($adminSvc);
        break;

    case 'xoa-kq':
        lms_form_guard($d, $user, $alias, 'delete', 'admin');
        lmyc_delete_submission($adminSvc);
        break;

    default:
        lmyc_list($adminSvc);
        break;
}

// ===================== Tab hợp lệ =====================

/**
 * Danh sách tab của trang.
 *
 * @return array
 */
function lmyc_tabs()
{
    return array(
        'them-buoi' => 'Request thêm buổi',
        'test'      => 'Request test',
        'duyet-kq'  => 'Duyệt KQ test',
        'doi-lich'  => 'Request đổi lịch',
        'kiem-buoi' => 'Kiểm tra số buổi',
    );
}

/**
 * Tab hiện tại (mặc định them-buoi).
 *
 * @return string
 */
function lmyc_tab()
{
    return lmyc_valid_tab(isset($_GET['tab']) ? $_GET['tab'] : 'them-buoi');
}

/**
 * Chuẩn hoá tên tab về tab hợp lệ.
 *
 * @param string $tab
 * @return string
 */
function lmyc_valid_tab($tab)
{
    $tab  = (string) $tab;
    $tabs = lmyc_tabs();
    return isset($tabs[$tab]) ? $tab : 'them-buoi';
}

/**
 * Redirect về danh sách kèm tab hiện tại.
 *
 * @param string $tab
 * @param array  $extra
 * @return void
 */
function lmyc_back($tab, array $extra = array())
{
    global $alias;
    header('Location: ' . lms_url($alias, '', array_merge(array('tab' => $tab), $extra)));
    exit;
}

/**
 * Redirect về trang chi tiết KQ test.
 *
 * @param array $ctx class_id, student_id, topic_id
 * @return void
 */
function lmyc_back_result(array $ctx)
{
    global $alias;
    header('Location: ' . lms_url($alias, 'ket-qua', array(
        'class_id'   => isset($ctx['class_id']) ? (int) $ctx['class_id'] : 0,
        'student_id' => isset($ctx['student_id']) ? (int) $ctx['student_id'] : 0,
        'topic_id'   => isset($ctx['topic_id']) ? (int) $ctx['topic_id'] : 0,
    )));
    exit;
}

// ===================== Danh sách =====================

function lmyc_list(\Lms\AdminRequestService $adminSvc)
{
    global $title, $template, $tab, $q, $phone, $items, $total, $totalPages, $page,
           $canEdit, $canDelete, $d, $user, $alias;

    $tab     = lmyc_tab();
    $q       = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $phone   = isset($_GET['phone']) ? trim((string) $_GET['phone']) : '';
    $page    = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = 20;

    $filters = array('q' => $q);

    switch ($tab) {
        case 'test':
            $res = $adminSvc->listTestRequests($filters, $page, $perPage);
            break;
        case 'duyet-kq':
            $res = $adminSvc->listTestResults($filters, $page, $perPage);
            break;
        case 'doi-lich':
            $res = $adminSvc->listScheduleRequests($filters, $page, $perPage);
            break;
        case 'kiem-buoi':
            $res = $adminSvc->listLowSessionClasses($filters, $page, $perPage);
            break;
        case 'them-buoi':
        default:
            $res = $adminSvc->listAddSessionRequests(array('q' => $q, 'phone' => $phone), $page, $perPage);
            break;
    }

    $items      = $res['items'];
    $total      = (int) $res['total'];
    $totalPages = (int) ceil($total / $perPage);
    if ($totalPages < 1) {
        $totalPages = 1;
    }

    $canEdit   = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'admin');

    $title    = 'Quản lý yêu cầu';
    $template = 'lms-yeu-cau/hienthi';
}

// ===================== Chi tiết request (duyệt add_session) =====================

function lmyc_detail(\Lms\RequestService $requestSvc)
{
    global $title, $template, $row, $canEdit, $d, $user, $alias;

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID yêu cầu.');
        lmyc_back('them-buoi');
    }

    $row = $requestSvc->getRequestForAdmin($id);
    if (empty($row) || !is_array($row)) {
        lms_flash('error', 'Yêu cầu không tồn tại.');
        lmyc_back('them-buoi');
    }

    $canEdit = lms_check_permission($d, $user, $alias, 'edit', 'admin');

    $title    = 'Chi tiết yêu cầu';
    $template = 'lms-yeu-cau/xem';
}

// ===================== Duyệt / từ chối request =====================

function lmyc_approve(\Lms\RequestService $requestSvc)
{
    global $alias, $user;

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID yêu cầu.');
        lmyc_back('them-buoi');
    }

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, 'xem', array('id' => $id)));
        exit;
    }

    $adminId    = isset($user['id']) ? (int) $user['id'] : 0;
    $returnTab  = lmyc_valid_tab(isset($_POST['return_tab']) ? $_POST['return_tab'] : 'them-buoi');

    if ($requestSvc->approveRequest($id, $adminId, $_POST)) {
        lms_flash('success', 'Đã duyệt yêu cầu.');
        lmyc_back($returnTab);
    }

    $errors = $requestSvc->errors();
    lms_flash('error', isset($errors['status'])
        ? $errors['status']
        : (isset($errors['system']) ? $errors['system'] : 'Không thể duyệt yêu cầu.'));
    header('Location: ' . lms_url($alias, 'xem', array('id' => $id)));
    exit;
}

function lmyc_reject(\Lms\RequestService $requestSvc)
{
    global $alias, $user;

    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID yêu cầu.');
        lmyc_back('them-buoi');
    }

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, 'xem', array('id' => $id)));
        exit;
    }

    $adminId   = isset($user['id']) ? (int) $user['id'] : 0;
    $returnTab = lmyc_valid_tab(isset($_POST['return_tab']) ? $_POST['return_tab'] : 'them-buoi');

    if ($requestSvc->rejectRequest($id, $adminId, $_POST)) {
        lms_flash('success', 'Đã từ chối yêu cầu.');
        lmyc_back($returnTab);
    }

    $errors = $requestSvc->errors();
    lms_flash('error', isset($errors['admin_note'])
        ? $errors['admin_note']
        : (isset($errors['system']) ? $errors['system'] : 'Không thể từ chối yêu cầu.'));
    header('Location: ' . lms_url($alias, 'xem', array('id' => $id)));
    exit;
}

// ===================== Xoá request =====================

function lmyc_delete_request(\Lms\AdminRequestService $adminSvc)
{
    global $user;

    $tab = isset($_POST['tab']) ? (string) $_POST['tab'] : 'doi-lich';
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        lmyc_back($tab);
    }

    $id      = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $adminId = isset($user['id']) ? (int) $user['id'] : 0;

    if ($adminSvc->deleteRequest($id, $adminId)) {
        lms_flash('success', 'Đã xoá yêu cầu.');
    } else {
        $errors = $adminSvc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể xoá yêu cầu.');
    }
    lmyc_back($tab);
}

// ===================== Test: xác nhận / xoá =====================

function lmyc_publish_test(\Lms\AdminRequestService $adminSvc)
{
    global $user;

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        lmyc_back('test');
    }

    $id      = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $adminId = isset($user['id']) ? (int) $user['id'] : 0;

    if ($adminSvc->publishTest($id, $adminId)) {
        lms_flash('success', 'Đã xác nhận gửi bài test.');
    } else {
        $errors = $adminSvc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể xác nhận bài test.');
    }
    lmyc_back('test');
}

function lmyc_delete_test(\Lms\AdminRequestService $adminSvc)
{
    global $user;

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        lmyc_back('test');
    }

    $id      = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $adminId = isset($user['id']) ? (int) $user['id'] : 0;

    if ($adminSvc->deleteTest($id, $adminId)) {
        lms_flash('success', 'Đã xoá bài test.');
    } else {
        $errors = $adminSvc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể xoá bài test.');
    }
    lmyc_back('test');
}

// ===================== Chi tiết + chấm KQ test =====================

function lmyc_test_result(\Lms\AdminRequestService $adminSvc)
{
    global $title, $template, $result, $ctx, $canEdit, $canDelete, $d, $user, $alias;

    $ctx = array(
        'class_id'   => isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0,
        'student_id' => isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0,
        'topic_id'   => isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : 0,
    );

    $result = $adminSvc->getTestResultDetail($ctx['class_id'], $ctx['student_id'], $ctx['topic_id']);
    if ($result === null) {
        lms_flash('error', 'Không tìm thấy kết quả test.');
        lmyc_back('duyet-kq');
    }

    $canEdit   = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'admin');

    $title    = 'Chi tiết kết quả test';
    $template = 'lms-yeu-cau/ketqua';
}

function lmyc_save_test_result(\Lms\AdminRequestService $adminSvc, $approve)
{
    global $user;

    $ctx = array(
        'class_id'   => isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0,
        'student_id' => isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0,
        'topic_id'   => isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0,
    );

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        lmyc_back_result($ctx);
    }

    $submissionId = isset($_POST['submission_id']) ? (int) $_POST['submission_id'] : 0;
    $files = isset($_FILES['graded_files']) ? $_FILES['graded_files'] : null;
    $adminId = isset($user['id']) ? (int) $user['id'] : 0;

    if ($adminSvc->updateSubmission($submissionId, $adminId, $_POST, $files, (bool) $approve)) {
        lms_flash('success', $approve ? 'Đã xác nhận kết quả test.' : 'Đã cập nhật kết quả test.');
    } else {
        $errors = $adminSvc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể lưu kết quả test.');
    }
    lmyc_back_result($ctx);
}

function lmyc_delete_submission(\Lms\AdminRequestService $adminSvc)
{
    global $user;

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        lmyc_back('duyet-kq');
    }

    $id      = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $adminId = isset($user['id']) ? (int) $user['id'] : 0;

    if ($adminSvc->deleteSubmission($id, $adminId)) {
        lms_flash('success', 'Đã xoá bài nộp.');
    } else {
        $errors = $adminSvc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể xoá bài nộp.');
    }
    lmyc_back('duyet-kq');
}

function lmyc_toggle_minitest(\Lms\AdminRequestService $adminSvc)
{
    global $user;

    $ctx = array(
        'class_id'   => isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0,
        'student_id' => isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0,
        'topic_id'   => isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0,
    );

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        lmyc_back_result($ctx);
    }

    $adminId = isset($user['id']) ? (int) $user['id'] : 0;
    $enabled = !empty($_POST['enable']) && (string) $_POST['enable'] === '1';

    if ($adminSvc->toggleMinitest($ctx['class_id'], $ctx['topic_id'], $enabled, $adminId)) {
        lms_flash('success', $enabled ? 'Đã bật bài Minitest cho học sinh.' : 'Đã tắt bài Minitest.');
    } else {
        $errors = $adminSvc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể thay đổi trạng thái Minitest.');
    }
    lmyc_back_result($ctx);
}
