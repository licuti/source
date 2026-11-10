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

// Redirect module cũ sang module gộp Quản lý tài khoản
$tabAction = (isset($a) && $a === 'them') ? 'them-hoc-vien' : ((isset($a) && $a === 'sua') ? 'sua-hoc-vien' : 'man');
$redirectParams = array('tab' => 'hoc-vien');
if (isset($_GET['id'])) {
    $redirectParams['id'] = (int) $_GET['id'];
}
header('Location: ' . lms_url('lms-tai-khoan', $tabAction, $redirectParams));
exit;

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
    global $title, $template, $row, $errors, $isEditMode, $formId,
           $subjectOptions, $d, $user, $alias;

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

        // Upload ảnh đại diện nếu có
        if (!empty($_FILES['avatar_file']['name'])) {
            $uploadRes = lms_save_files($_FILES['avatar_file'], 'avatars', array(
                'maxFiles' => 1,
                'mimeWhitelist' => array('jpg', 'jpeg', 'png', 'webp')
            ));
            if ($uploadRes['ok'] && !empty($uploadRes['paths'][0])) {
                $in['avatar'] = 'uploads/' . (string) $uploadRes['paths'][0];
            } else {
                $errors = array('avatar_file' => !empty($uploadRes['error'])
                    ? $uploadRes['error'] : 'Không thể tải ảnh đại diện lên.');
            }
        }
        if (!empty($in['remove_avatar'])) {
            $in['avatar'] = null;
        }

        if (empty($errors)) {
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
                $message = $isEdit ? 'Đã cập nhật hồ sơ học viên.' : 'Đã tạo tài khoản học viên mới.';
                $generatedPassword = $isEdit ? '' : $userSvc->lastGeneratedPassword();
                if ($generatedPassword !== '') {
                    $message .= ' Mật khẩu tạm: ' . $generatedPassword
                        . '. Học viên cần đổi mật khẩu ở lần đăng nhập đầu tiên.';
                }
                lms_flash('success', $message);
                header('Location: ' . lms_url($alias));
                exit;
            }

            $errors = $userSvc->errors();
        }
        $row = array_merge($row, $in);
    }

    // free_time_slots luôn là mảng khi render
    $row['free_time_slots'] = \Lms\UserService::decodeJsonArray(
        isset($row['free_time_slots']) ? $row['free_time_slots'] : array()
    );

    $subjectOptions = lms_hoc_vien_subject_map(lms_db());

    $title    = $isEdit ? 'Chỉnh sửa học viên' : 'Tài khoản học viên mới';
    $template = 'lms-hoc-vien/form';
}

/**
 * Map subject_id → tên môn + tên chương trình (dùng cho form chọn môn).
 *
 * @param LmsDb $d
 * @return array id => array('name' => , 'program' => )
 */
function lms_hoc_vien_subject_map(LmsDb $d)
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

/**
 * Chuyển các mảng slot_day[i] / slot_start[i][] / slot_end[i][] từ form
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
    foreach ($days as $i => $day) {
        $rowStarts = isset($starts[$i]) && is_array($starts[$i]) ? $starts[$i] : array();
        $rowEnds   = isset($ends[$i]) && is_array($ends[$i]) ? $ends[$i] : array();

        $n = max(count($rowStarts), count($rowEnds));
        for ($j = 0; $j < $n; $j++) {
            $out[] = array(
                'day'   => $day,
                'start' => isset($rowStarts[$j]) ? $rowStarts[$j] : '',
                'end'   => isset($rowEnds[$j]) ? $rowEnds[$j] : '',
            );
        }
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
