<?php
/**
 * Admin LMS — Quản lý giáo viên (ADM-02, Phase 1.1).
 *
 * Action:
 *   - 'man'  (GET)  → danh sách + filter + phân trang.
 *   - 'them' (GET/POST) → form tạo giáo viên (POST: CSRF + validate + tạo).
 *   - 'sua'  (GET/POST) → form sửa (POST: CSRF + validate + cập nhật + audit).
 *   - 'khoa' / 'mo' (GET) → khoá / mở khoá tài khoản.
 *   - 'xoa'  (GET)  → xoá hồ sơ + tài khoản (từ chối nếu đang chủ nhiệm lớp).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d       = lms_db();
$user    = lms_current_user();
$alias   = 'lms-giao-vien';
$userSvc = new \Lms\UserService($d);

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'them':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_giao_vien_form($userSvc, false);
        break;

    case 'sua':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_giao_vien_form($userSvc, true);
        break;

    case 'khoa':
    case 'mo':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_giao_vien_toggle_status($userSvc, $action);
        break;

    case 'xoa':
        lms_form_guard($d, $user, $alias, 'delete');
        lms_giao_vien_delete($userSvc);
        break;

    default:
        lms_giao_vien_list($userSvc);
        break;
}

/**
 * Map subject_id → tên môn + tên chương trình (dùng cho list + form).
 *
 * @param LmsDb $d
 * @return array id => array('name' => , 'program' => )
 */
function lms_giao_vien_subject_map(LmsDb $d)
{
    $rows = $d->safeFetch(
        'select s.id, s.name, p.name as program_name'
        . ' from db_lms_subjects s'
        . ' join db_lms_programs p on p.id = s.program_id'
        . ' order by p.name, s.name'
    );
    $map = array();
    foreach ($rows as $r) {
        $map[(int) $r['id']] = array(
            'name'    => (string) $r['name'],
            'program' => (string) $r['program_name'],
        );
    }
    return $map;
}

// ===================== Danh sách =====================

function lms_giao_vien_list(\Lms\UserService $userSvc)
{
    global $title, $template, $items, $total, $totalPages, $page,
           $filterQ, $filterStatus, $filterRank, $canEdit, $canDelete,
           $subjectMap, $d, $user, $alias;

    $filterQ      = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $filterStatus = isset($_GET['status']) ? (string) $_GET['status'] : '';
    $filterRank   = isset($_GET['rank']) ? (string) $_GET['rank'] : '';
    $page         = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage      = 20;

    $res = $userSvc->listTeachers(
        array('q' => $filterQ, 'status' => $filterStatus, 'rank' => $filterRank),
        $page,
        $perPage
    );

    $items      = $res['items'];
    $total      = (int) $res['total'];
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

    $subjectMap = lms_giao_vien_subject_map(lms_db());

    $canEdit   = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'admin');

    $title    = 'Quản lý giáo viên';
    $template = 'lms-giao-vien/hienthi';
}

// ===================== Form tạo / sửa =====================

function lms_giao_vien_form(\Lms\UserService $userSvc, $isEdit)
{
    global $title, $template, $row, $errors, $isEditMode, $formId,
           $subjectOptions, $districts, $d, $user, $alias;

    $errors     = array();
    $isEditMode = $isEdit;
    $row        = array();
    $formId     = 0;

    if ($isEdit) {
        $formId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($formId <= 0) {
            lms_flash('error', 'Thiếu ID giáo viên.');
            header('Location: ' . lms_url($alias));
            exit;
        }
        $row = $userSvc->getTeacher($formId);
        if (empty($row)) {
            lms_flash('error', 'Giáo viên không tồn tại.');
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
        // Checkbox không submit khi bỏ tick → ép kiểu tường minh
        $in['can_teach_online'] = isset($_POST['can_teach_online']) ? 1 : 0;
        $in['is_commit_08']     = isset($_POST['is_commit_08']) ? 1 : 0;

        if ($isEdit) {
            $ok = $userSvc->updateTeacher($formId, $in);
        } else {
            $newId = $userSvc->createTeacher($in);
            $ok = $newId !== null;
            if ($ok) {
                $formId = (int) $newId;
            }
        }

        if ($ok) {
            lms_flash('success', $isEdit ? 'Đã cập nhật hồ sơ giáo viên.' : 'Đã tạo tài khoản giáo viên mới.');
            header('Location: ' . lms_url($alias));
            exit;
        }

        $errors = $userSvc->errors();
        $row = array_merge($row, $in);
    }

    // JSON fields luôn là mảng khi render
    $row['teachable_subjects']  = \Lms\UserService::decodeJsonArray(isset($row['teachable_subjects']) ? $row['teachable_subjects'] : array());
    $row['teachable_districts'] = \Lms\UserService::decodeJsonArray(isset($row['teachable_districts']) ? $row['teachable_districts'] : array());

    $subjectOptions = lms_giao_vien_subject_map(lms_db());
    $districts      = \Lms\UserService::HCMC_DISTRICTS;

    $title    = $isEdit ? 'Sửa giáo viên' : 'Thêm giáo viên';
    $template = 'lms-giao-vien/form';
}

// ===================== Khoá / mở khoá =====================

function lms_giao_vien_toggle_status(\Lms\UserService $userSvc, $action)
{
    global $alias;

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $status = $action === 'khoa' ? 'locked' : 'active';

    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID giáo viên.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if ($userSvc->setUserStatus($id, $status)) {
        lms_flash('success', $status === 'locked' ? 'Đã khoá tài khoản giáo viên.' : 'Đã mở khoá tài khoản giáo viên.');
    } else {
        $errors = $userSvc->errors();
        lms_flash('error', isset($errors['system']) ? $errors['system'] : 'Không thể thay đổi trạng thái tài khoản.');
    }
    header('Location: ' . lms_url($alias));
    exit;
}

// ===================== Xoá =====================

function lms_giao_vien_delete(\Lms\UserService $userSvc)
{
    global $alias;

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID giáo viên.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if ($userSvc->deleteTeacher($id)) {
        lms_flash('success', 'Đã xoá giáo viên.');
    } else {
        $errors = $userSvc->errors();
        lms_flash('error', isset($errors['system']) ? $errors['system'] : 'Không thể xoá giáo viên.');
    }
    header('Location: ' . lms_url($alias));
    exit;
}
