<?php
/**
 * Admin LMS — Quản lý lớp học (ADM-03, Phase 1.2).
 *
 * Action:
 *   - 'man'      (GET)  → danh sách + filter + phân trang.
 *   - 'them'     (GET/POST) → form tạo lớp (POST: CSRF + validate + tạo).
 *   - 'sua'      (GET/POST) → form sửa (POST: CSRF + validate + cập nhật + audit).
 *   - 'tam-dung' / 'mo' (GET) → tạm dừng / tiếp tục lớp.
 *   - 'xoa'      (GET)  → soft-delete lớp + xoá schedules/enrolment/sessions upcoming.
 *
 * Router đã check auth gate + quyền view; mỗi action dưới check quyền edit/delete
 * trước khi xử lý (Huong-Dan-Phat-Trien-Page.md §3.4 + checklist §4).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d         = lms_db();
$user      = lms_current_user();
$alias     = 'lms-lop';
$classSvc  = new \Lms\ClassService($d);

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'them':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_form($classSvc, false);
        break;

    case 'sua':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_form($classSvc, true);
        break;

    case 'tam-dung':
    case 'mo':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_lop_toggle_status($classSvc, $action);
        break;

    case 'xoa':
        lms_form_guard($d, $user, $alias, 'delete');
        lms_lop_delete($classSvc);
        break;

    default:
        lms_lop_list($classSvc);
        break;
}

// ===================== Data cho form =====================

/**
 * Danh sách chương trình cho dropdown.
 *
 * @param LmsDb $d
 * @return array
 */
function lms_lop_programs(LmsDb $d)
{
    return $d->safeFetch('select id, name from db_lms_programs order by name');
}

/**
 * Danh sách môn học kèm program_id (option có data-program-id để JS lọc).
 *
 * @param LmsDb $d
 * @return array
 */
function lms_lop_subjects(LmsDb $d)
{
    return $d->safeFetch(
        'select id, program_id, name from db_lms_subjects order by program_id, name'
    );
}

/**
 * Giáo viên active kèm số lớp đang dạy / max_classes + % phí mặc định.
 *
 * @param LmsDb $d
 * @return array
 */
function lms_lop_teachers(LmsDb $d)
{
    return $d->safeFetch(
        'select t.user_id, t.teacher_code, t.full_name, t.service_fee_percentage,'
        . ' t.max_classes,'
        . ' (select count(*) from db_lms_classes c'
        . '   where c.teacher_id = t.user_id and c.status = "active" and c.deleted_at is null) as active_classes'
        . ' from db_lms_teachers t'
        . ' where t.status = ?'
        . ' order by t.teacher_code',
        array('active')
    );
}

/**
 * Học viên có thể enrol (chưa nghỉ học).
 *
 * @param LmsDb $d
 * @return array
 */
function lms_lop_students(LmsDb $d)
{
    return $d->safeFetch(
        'select s.user_id, s.student_code, s.full_name from db_lms_students s'
        . ' where s.status <> ?'
        . ' order by s.student_code',
        array('quit')
    );
}

// ===================== Danh sách =====================

function lms_lop_list(\Lms\ClassService $classSvc)
{
    global $title, $template, $items, $total, $totalPages, $page,
           $filterQ, $filterTeacherId, $filterProgramIds, $filterStatus,
           $filterClassType, $canEdit, $canDelete,
           $teachers, $programs, $d, $user, $alias;

    $filterQ          = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $filterTeacherId  = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
    $filterProgramIds = array();
    if (isset($_GET['program_ids']) && is_array($_GET['program_ids'])) {
        foreach ($_GET['program_ids'] as $pid) {
            $pid = (int) $pid;
            if ($pid > 0) {
                $filterProgramIds[] = $pid;
            }
        }
    }
    $filterStatus    = isset($_GET['status']) ? (string) $_GET['status'] : '';
    $filterClassType = isset($_GET['class_type']) ? (string) $_GET['class_type'] : '';
    $page            = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage         = 20;

    $res = $classSvc->listClasses(array(
        'q'            => $filterQ,
        'teacher_id'   => $filterTeacherId,
        'program_ids'  => $filterProgramIds,
        'status'       => $filterStatus,
        'class_type'   => $filterClassType,
    ), $page, $perPage);

    $items      = $res['items'];
    $total      = (int) $res['total'];
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

    $teachers = lms_lop_teachers($d);
    $programs = lms_lop_programs($d);

    $canEdit   = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'admin');

    $title    = 'Quản lý lớp học';
    $template = 'lms-lop/hienthi';
}

// ===================== Form tạo / sửa =====================

function lms_lop_form(\Lms\ClassService $classSvc, $isEdit)
{
    global $title, $template, $row, $errors, $isEditMode, $formId,
           $programs, $subjects, $teachers, $students, $d, $user, $alias;

    $errors     = array();
    $isEditMode = $isEdit;
    $row        = array();
    $formId     = 0;

    if ($isEdit) {
        $formId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($formId <= 0) {
            lms_flash('error', 'Thiếu ID lớp học.');
            header('Location: ' . lms_url($alias));
            exit;
        }
        $row = $classSvc->getClass($formId);
        if (empty($row)) {
            lms_flash('error', 'Lớp học không tồn tại.');
            header('Location: ' . lms_url($alias));
            exit;
        }
        // TIME column trả 'HH:MM:SS' → chỉ giữ 'HH:MM' cho input[type=time]
        if (isset($row['hw_deadline_time'])) {
            $row['hw_deadline_time'] = substr((string) $row['hw_deadline_time'], 0, 5);
        }
        // Lịch từ DB → định dạng mảng cho form
        $schedules = array();
        foreach ((isset($row['schedules']) ? $row['schedules'] : array()) as $sch) {
            $schedules[] = array(
                'day'   => (int) $sch['day_of_week'],
                'start' => substr((string) $sch['start_time'], 0, 5),
                'end'   => substr((string) $sch['end_time'], 0, 5),
            );
        }
        $row['schedules'] = $schedules;
    }

    $isPost = isset($_SERVER['REQUEST_METHOD']) && (string) $_SERVER['REQUEST_METHOD'] === 'POST';

    if ($isPost) {
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
            header('Location: ' . lms_url($alias, $isEdit ? 'sua' : 'them', $isEdit ? array('id' => $formId) : array()));
            exit;
        }

        $in = $_POST;
        $in['schedules']   = lms_lop_schedules_from_post($_POST);
        $in['student_ids'] = isset($_POST['student_ids']) ? (array) $_POST['student_ids'] : array();

        if ($isEdit) {
            $ok = $classSvc->updateClass($formId, $in);
        } else {
            $newId = $classSvc->createClass($in);
            $ok = $newId !== null;
            if ($ok) {
                $formId = (int) $newId;
            }
        }

        if ($ok) {
            lms_flash('success', $isEdit ? 'Đã cập nhật lớp học.' : 'Đã tạo lớp học mới.');
            header('Location: ' . lms_url($alias));
            exit;
        }

        $errors = $classSvc->errors();
        $row = array_merge($row, $in);
    }

    // schedules + student_ids luôn là mảng khi render
    if (!isset($row['schedules']) || !is_array($row['schedules'])) {
        $row['schedules'] = array();
    }
    if (!isset($row['student_ids']) || !is_array($row['student_ids'])) {
        $row['student_ids'] = array();
    }
    if (!isset($row['hw_deadline_time']) || (string) $row['hw_deadline_time'] === '') {
        $row['hw_deadline_time'] = '12:00';
    }
    if (!isset($row['hw_check_hours']) || (string) $row['hw_check_hours'] === '') {
        $row['hw_check_hours'] = 24;
    }

    $programs  = lms_lop_programs($d);
    $subjects  = lms_lop_subjects($d);
    $teachers  = lms_lop_teachers($d);
    $students  = lms_lop_students($d);

    $title    = $isEdit ? 'Sửa lớp học' : 'Thêm lớp học';
    $template = 'lms-lop/form';
}

/**
 * Chuyển 3 mảng song song từ form (sch_day[]/sch_start[]/sch_end[])
 * thành mảng các khung giờ để service validate.
 *
 * @param array $post
 * @return array
 */
function lms_lop_schedules_from_post($post)
{
    $days   = isset($post['sch_day']) ? (array) $post['sch_day'] : array();
    $starts = isset($post['sch_start']) ? (array) $post['sch_start'] : array();
    $ends   = isset($post['sch_end']) ? (array) $post['sch_end'] : array();

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

// ===================== Tạm dừng / tiếp tục =====================

function lms_lop_toggle_status(\Lms\ClassService $classSvc, $action)
{
    global $alias;

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $status = $action === 'tam-dung' ? 'paused' : 'active';

    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID lớp học.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if ($classSvc->setClassStatus($id, $status)) {
        lms_flash('success', $status === 'paused' ? 'Đã tạm dừng lớp học.' : 'Đã tiếp tục lớp học.');
    } else {
        $errors = $classSvc->errors();
        lms_flash('error', isset($errors['system']) ? $errors['system'] : 'Không thể thay đổi trạng thái lớp học.');
    }
    header('Location: ' . lms_url($alias));
    exit;
}

// ===================== Xoá =====================

function lms_lop_delete(\Lms\ClassService $classSvc)
{
    global $alias;

    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID lớp học.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if ($classSvc->deleteClass($id)) {
        lms_flash('success', 'Đã xoá lớp học. Các buổi đã hoàn thành được giữ lại làm lịch sử.');
    } else {
        $errors = $classSvc->errors();
        lms_flash('error', isset($errors['system']) ? $errors['system'] : 'Không thể xoá lớp học.');
    }
    header('Location: ' . lms_url($alias));
    exit;
}
