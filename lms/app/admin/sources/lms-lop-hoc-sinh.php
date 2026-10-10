<?php
/**
 * Admin LMS — Học sinh − Lớp học (ADM-04, Phase 1.3).
 *
 * Action:
 *   - 'man'    (GET)      → ma trận HV × chương trình + 2 tab + 2 ô tìm.
 *   - 'them'   (GET/POST) → form thêm lớp cho 1 HV (POST: CSRF + enroll).
 *   - 'chuyen' (GET/POST) → form chuyển lớp (POST: CSRF + transfer).
 *   - 'xoa'    (GET)      → xoá HV khỏi lớp (JS confirm class lms-confirm-delete).
 *
 * Router đã check auth gate + quyền view; mỗi action dưới check quyền edit/delete
 * trước khi xử lý (Huong-Dan-Phat-Trien-Page.md §3.4 + checklist §4).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d          = lms_db();
$user       = lms_current_user();
$alias      = 'lms-lop-hoc-sinh';
$enrolSvc   = new \Lms\EnrolmentService($d);

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'them':
        lms_form_guard($d, $user, $alias, 'edit');
        lmlhs_add($enrolSvc);
        break;

    case 'chuyen':
        lms_form_guard($d, $user, $alias, 'edit');
        lmlhs_transfer($enrolSvc);
        break;

    case 'xoa':
        lms_form_guard($d, $user, $alias, 'delete');
        lmlhs_remove($enrolSvc);
        break;

    default:
        lmlhs_list($enrolSvc);
        break;
}

// ===================== Helper =====================

/**
 * Tab hiện tại từ GET ?type (default 'group'), giữ qua pagination link.
 *
 * @return string 'group' | 'one_on_one'
 */
function lmlhs_tab_type()
{
    $type = isset($_GET['type']) ? (string) $_GET['type'] : 'group';
    return isset(\Lms\ClassService::CLASS_TYPES[$type]) ? $type : 'group';
}

// ===================== Danh sách (ma trận) =====================

function lmlhs_list(\Lms\EnrolmentService $enrolSvc)
{
    global $title, $template, $items, $programs, $total, $totalPages, $page, $perPage,
           $filterClassType, $filterQStudent, $filterQClass, $canEdit, $canDelete,
           $d, $user, $alias;

    $filterClassType = lmlhs_tab_type();
    $filterQStudent  = isset($_GET['q_student']) ? trim((string) $_GET['q_student']) : '';
    $filterQClass    = isset($_GET['q_class']) ? trim((string) $_GET['q_class']) : '';
    $page            = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage         = 20;

    $res = $enrolSvc->listMatrix(array(
        'class_type' => $filterClassType,
        'q_student'  => $filterQStudent,
        'q_class'    => $filterQClass,
    ), $page, $perPage);

    $items      = $res['items'];
    $programs   = $res['programs'];
    $total      = (int) $res['total'];
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

    $canEdit   = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'admin');

    $title    = 'Học sinh - Lớp học';
    $template = 'lms-lop-hoc-sinh/hienthi';
}

// ===================== Thêm lớp =====================

function lmlhs_add(\Lms\EnrolmentService $enrolSvc)
{
    global $title, $template, $studentId, $studentName, $classOptions,
           $errors, $filterClassType, $d, $user, $alias;

    $errors = array();
    $filterClassType = lmlhs_tab_type();
    $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
    if (isset($_POST['student_id'])) {
        $studentId = (int) $_POST['student_id'];
    }

    if ($studentId <= 0) {
        lms_flash('error', 'Thiếu ID học viên.');
        header('Location: ' . lms_url($alias, 'man', array('type' => $filterClassType)));
        exit;
    }

    $student = $d->safeFetchOne(
        'select s.full_name, s.student_code, u.username from db_lms_students s'
        . ' join db_lms_users u on u.id = s.user_id'
        . ' where s.user_id = ? limit 1',
        array($studentId)
    );
    if (empty($student)) {
        lms_flash('error', 'Học viên không tồn tại.');
        header('Location: ' . lms_url($alias, 'man', array('type' => $filterClassType)));
        exit;
    }
    $studentName = (string) $student['full_name'] . ' (' . (string) $student['student_code'] . ')';

    $isPost = isset($_SERVER['REQUEST_METHOD']) && (string) $_SERVER['REQUEST_METHOD'] === 'POST';

    if ($isPost) {
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
            header('Location: ' . lms_url($alias, 'them', array('student_id' => $studentId, 'type' => $filterClassType)));
            exit;
        }

        $classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;

        if ($enrolSvc->enrollStudent($classId, $studentId)) {
            lms_flash('success', 'Đã thêm học viên vào lớp.');
            header('Location: ' . lms_url($alias, 'man', array('type' => $filterClassType)));
            exit;
        }

        $errors = $enrolSvc->errors();
    }

    $classOptions = $enrolSvc->studentClassOptions($studentId, $filterClassType);

    $title    = 'Thêm lớp cho học viên';
    $template = 'lms-lop-hoc-sinh/them';
}

// ===================== Chuyển lớp =====================

function lmlhs_transfer(\Lms\EnrolmentService $enrolSvc)
{
    global $title, $template, $studentId, $studentName, $fromClassId, $fromClassCode,
           $classOptions, $errors, $filterClassType, $d, $user, $alias;

    $errors = array();
    $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
    if (isset($_POST['student_id'])) {
        $studentId = (int) $_POST['student_id'];
    }
    $fromClassId = isset($_GET['from_class_id']) ? (int) $_GET['from_class_id'] : 0;
    if (isset($_POST['from_class_id'])) {
        $fromClassId = (int) $_POST['from_class_id'];
    }

    if ($studentId <= 0 || $fromClassId <= 0) {
        lms_flash('error', 'Thiếu thông tin học viên hoặc lớp.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $student = $d->safeFetchOne(
        'select s.full_name, s.student_code from db_lms_students s'
        . ' where s.user_id = ? limit 1',
        array($studentId)
    );
    $fromClass = $d->safeFetchOne(
        'select c.id, c.class_code, c.class_type from db_lms_classes c'
        . ' where c.id = ? and c.deleted_at is null limit 1',
        array($fromClassId)
    );

    if (empty($student) || empty($fromClass)) {
        lms_flash('error', 'Học viên hoặc lớp học không tồn tại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $filterClassType = (string) $fromClass['class_type'];
    if (!isset(\Lms\ClassService::CLASS_TYPES[$filterClassType])) {
        $filterClassType = 'group';
    }
    $studentName   = (string) $student['full_name'] . ' (' . (string) $student['student_code'] . ')';
    $fromClassCode = (string) $fromClass['class_code'];

    $isPost = isset($_SERVER['REQUEST_METHOD']) && (string) $_SERVER['REQUEST_METHOD'] === 'POST';

    if ($isPost) {
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
            header('Location: ' . lms_url($alias, 'chuyen', array(
                'student_id' => $studentId,
                'from_class_id' => $fromClassId,
            )));
            exit;
        }

        $toClassId = isset($_POST['to_class_id']) ? (int) $_POST['to_class_id'] : 0;

        if ($enrolSvc->transferStudent($studentId, $fromClassId, $toClassId)) {
            lms_flash('success', 'Đã chuyển học viên sang lớp mới.');
            header('Location: ' . lms_url($alias, 'man', array('type' => $filterClassType)));
            exit;
        }

        $errors = $enrolSvc->errors();
    }

    $classOptions = $enrolSvc->studentClassOptions($studentId, $filterClassType, $fromClassId);

    $title    = 'Chuyển lớp';
    $template = 'lms-lop-hoc-sinh/chuyen';
}

// ===================== Xoá khỏi lớp =====================

function lmlhs_remove(\Lms\EnrolmentService $enrolSvc)
{
    global $alias;

    $type = lmlhs_tab_type();

    $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
    $classId   = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;

    if ($studentId <= 0 || $classId <= 0) {
        lms_flash('error', 'Thiếu thông tin học viên hoặc lớp.');
        header('Location: ' . lms_url($alias, 'man', array('type' => $type)));
        exit;
    }

    if ($enrolSvc->removeStudentFromClass($classId, $studentId)) {
        lms_flash('success', 'Đã xoá học viên khỏi lớp. Các buổi đã có dữ liệu được giữ lại.');
    } else {
        $errors = $enrolSvc->errors();
        lms_flash('error', isset($errors['student_id'])
            ? $errors['student_id']
            : (isset($errors['system']) ? $errors['system'] : 'Không thể xoá học viên khỏi lớp.'));
    }
    header('Location: ' . lms_url($alias, 'man', array('type' => $type)));
    exit;
}
