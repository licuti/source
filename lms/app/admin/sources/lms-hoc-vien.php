<?php
/**
 * Admin LMS — Quản lý học viên (ADM-01, Phase 1.1).
 *
 * Action:
 *   - 'man'  (GET)  → danh sách + filter + phân trang.
 *   - 'them' (GET/POST) → form tạo học viên (POST: CSRF + validate + tạo).
 *   - 'sua'  (GET/POST) → form sửa (POST: CSRF + validate + cập nhật + audit).
 *   - 'khoa' / 'mo' (GET) → khoá / mở khoá tài khoản (đổi users.status).
 *   - 'xoa'  (GET)  → xoá hồ sơ + tài khoản (từ chối nếu đang theo lớp).
 *
 * Router đã check auth gate + quyền view; mỗi action dưới check quyền edit/delete
 * trước khi xử lý (Huong-Dan-Phat-Trien-Page.md §3.4 + checklist §4).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d        = lms_db();
$user     = lms_current_user();
$alias    = 'lms-hoc-vien';
$userSvc  = new \Lms\UserService($d);

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'them':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_hoc_vien_form($userSvc, false);
        break;

    case 'sua':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_hoc_vien_form($userSvc, true);
        break;

    case 'khoa':
    case 'mo':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_hoc_vien_toggle_status($userSvc, $action);
        break;

    case 'xoa':
        lms_form_guard($d, $user, $alias, 'delete');
        lms_hoc_vien_delete($userSvc);
        break;

    default:
        lms_hoc_vien_list($userSvc);
        break;
}

// ===================== Danh sách =====================

function lms_hoc_vien_list(\Lms\UserService $userSvc)
{
    global $title, $template, $items, $total, $totalPages, $page,
           $filterQ, $filterStatus, $canEdit, $canDelete, $d, $user, $alias;

    $filterQ      = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $filterStatus = isset($_GET['status']) ? (string) $_GET['status'] : '';
    $page         = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage      = 20;

    $res = $userSvc->listStudents(array('q' => $filterQ, 'status' => $filterStatus), $page, $perPage);

    $items     = $res['items'];
    $total     = (int) $res['total'];
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

    $canEdit   = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'admin');

    $title    = 'Quản lý học viên';
    $template = 'lms-hoc-vien/hienthi';
}

// ===================== Form tạo / sửa =====================

function lms_hoc_vien_form(\Lms\UserService $userSvc, $isEdit)
{
    global $title, $template, $row, $errors, $isEditMode, $formId, $d, $user, $alias;

    $errors      = array();
    $isEditMode  = $isEdit;
    $row         = array();
    $formId      = 0;

    if ($isEdit) {
        $formId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($formId <= 0) {
            lms_flash('error', 'Thiếu ID học viên.');
            header('Location: ' . lms_url($alias));
            exit;
        }
        $row = $userSvc->getStudent($formId);
        if (empty($row)) {
            lms_flash('error', 'Học viên không tồn tại.');
            header('Location: ' . lms_url($alias));
            exit;
        }
    }

    $isPost = isset($_SERVER['REQUEST_METHOD']) && (string) $_SERVER['REQUEST_METHOD'] === 'POST';

    if ($isPost) {
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
            header('Location: ' . lms_url($alias, $isEdit ? 'sua' : 'them', $isEdit ? array('id' => $formId) : array()));
            exit;
        }

        $in = $_POST;
        $in['free_time_slots'] = lms_hoc_vien_slots_from_post($_POST);

        if ($isEdit) {
            $ok = $userSvc->updateStudent($formId, $in);
        } else {
            $newId = $userSvc->createStudent($in);
            $ok = $newId !== null;
            if ($ok) {
                $formId = (int) $newId;
            }
        }

        if ($ok) {
            lms_flash('success', $isEdit ? 'Đã cập nhật hồ sơ học viên.' : 'Đã tạo tài khoản học viên mới.');
            header('Location: ' . lms_url($alias));
            exit;
        }

        $errors = $userSvc->errors();
        $row = array_merge($row, $in);
    }

    // free_time_slots luôn là mảng khi render
    $row['free_time_slots'] = \Lms\UserService::decodeJsonArray(
        isset($row['free_time_slots']) ? $row['free_time_slots'] : array()
    );

    $title    = $isEdit ? 'Sửa học viên' : 'Thêm học viên';
    $template = 'lms-hoc-vien/form';
}

/**
 * Chuyển 3 mảng song song từ form (slot_day[]/slot_start[]/slot_end[])
 * thành mảng các khung giờ để service validate.
 *
 * @param array $post
 * @return array
 */
function lms_hoc_vien_slots_from_post($post)
{
    $days   = isset($post['slot_day']) ? (array) $post['slot_day'] : array();
    $starts = isset($post['slot_start']) ? (array) $post['slot_start'] : array();
    $ends   = isset($post['slot_end']) ? (array) $post['slot_end'] : array();

    $out = array();
    $n = max(count($days), count($starts), count($ends));
    for ($i = 0; $i < $n; $i++) {
        $out[] = array(
            'day'   => isset($days[$i]) ? $days[$i] : '',
            'start' => isset($starts[$i]) ? $starts[$i] : '',
            'end'   => isset($ends[$i]) ? $ends[$i] : '',
        );
    }
    return $out;
}

// ===================== Khoá / mở khoá =====================

function lms_hoc_vien_toggle_status(\Lms\UserService $userSvc, $action)
{
    global $alias;

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $status = $action === 'khoa' ? 'locked' : 'active';

    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID học viên.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if ($userSvc->setUserStatus($id, $status)) {
        lms_flash('success', $status === 'locked' ? 'Đã khoá tài khoản học viên.' : 'Đã mở khoá tài khoản học viên.');
    } else {
        $errors = $userSvc->errors();
        lms_flash('error', isset($errors['system']) ? $errors['system'] : 'Không thể thay đổi trạng thái tài khoản.');
    }
    header('Location: ' . lms_url($alias));
    exit;
}

// ===================== Xoá =====================

function lms_hoc_vien_delete(\Lms\UserService $userSvc)
{
    global $alias;

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID học viên.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if ($userSvc->deleteStudent($id)) {
        lms_flash('success', 'Đã xoá học viên.');
    } else {
        $errors = $userSvc->errors();
        lms_flash('error', isset($errors['system']) ? $errors['system'] : 'Không thể xoá học viên.');
    }
    header('Location: ' . lms_url($alias));
    exit;
}
