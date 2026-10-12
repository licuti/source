<?php
/**
 * Admin LMS — Sắp xếp lớp / Class Matching (ADM-04, Phase 3).
 *
 * SRS §ADM-04 + mockup §5: chọn HV đang chờ xếp → hệ thống tính điểm match
 * GV → admin duyệt + tạo lớp 1-1 tự động với cấu hình mặc định.
 *
 * Action:
 *  - 'man'         (GET)  → cột trái: danh sách HV chờ xếp; cột phải: kết quả
 *                            match GV khi chọn 1 HV (theo ?student_id).
 *  - 'chi-tiet-gv' (GET)  → chi tiết lịch tuần 1 GV (mockup §4: lịch cố định
 *                            các lớp đang dạy + lịch trống còn nhận).
 *  - 'tao-lop'     (POST) → tạo lớp 1-1 từ match (CSRF + createClassFromMatch + PRG).
 *
 * Router đã check auth gate + quyền view; action POST thêm guard edit (§3.4).
 * KHÔNG echo HTML (PRG). PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d        = lms_db();
$user     = lms_current_user();
$alias    = 'lms-sap-xep-lop';
$matchSvc = new \Lms\MatchingService($d);

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'chi-tiet-gv':
        lms_form_guard($d, $user, $alias, 'view');
        lms_xep_lop_teacher_detail($matchSvc);
        break;

    case 'tao-lop':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_xep_lop_create_class($matchSvc);
        break;

    default:
        lms_xep_lop_list($matchSvc);
        break;
}

// ===================== Helpers =====================

/**
 * Badge CSS cho hạng GV (mockup: Xanh > Vàng > Đồng).
 *
 * @param string $rank
 * @return string
 */
function lms_xep_lop_rank_badge($rank)
{
    switch ((string) $rank) {
        case 'green':
            return 'lms-badge-success';
        case 'gold':
            return 'lms-badge-warning';
        case 'bronze':
        default:
            return 'lms-badge-secondary';
    }
}

/**
 * Map subject_id → tên môn + chương trình cho filter select.
 *
 * @param LmsDb $d
 * @return array id => array('name' => , 'program' => )
 */
function lms_xep_lop_subject_map(LmsDb $d)
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

// ===================== Danh sách + kết quả match =====================

function lms_xep_lop_list(\Lms\MatchingService $matchSvc)
{
    global $title, $template, $items, $total, $totalPages, $page,
           $filterQ, $filterSubject, $subjectOptions, $studentId, $student,
           $matched, $canEdit, $d, $user, $alias;

    $filterQ       = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $filterSubject = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;
    $studentId     = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
    $page          = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage       = 20;

    $res = $matchSvc->listPendingStudents(
        array('q' => $filterQ, 'subject_id' => $filterSubject),
        $page,
        $perPage
    );

    $items      = $res['items'];
    $total      = (int) $res['total'];
    $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;
    $subjectOptions = lms_xep_lop_subject_map(lms_db());

    // Cột phải: kết quả match của HV được chọn
    $student = array();
    $matched = array();
    if ($studentId > 0) {
        $student = $matchSvc->getPendingStudent($studentId);
        if (empty($student)) {
            lms_flash('error', 'Học viên không tồn tại hoặc đã được xếp lớp.');
            $studentId = 0;
        } else {
            $matched = $matchSvc->matchTeachers($studentId);
            if ($matchSvc->hasErrors()) {
                $errors = $matchSvc->errors();
                lms_flash('error', (string) reset($errors));
            }
        }
    }

    $canEdit = lms_check_permission($d, $user, $alias, 'edit', 'admin');

    $title    = 'Sắp xếp lớp';
    $template = 'lms-sap-xep-lop/hienthi';
}

// ===================== Chi tiết lịch tuần GV =====================

function lms_xep_lop_teacher_detail(\Lms\MatchingService $matchSvc)
{
    global $title, $template, $detail, $studentId, $alias;

    $teacherId = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
    $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;

    $detail = $matchSvc->teacherWeekDetail($teacherId);
    if (empty($detail)) {
        lms_flash('error', 'Giáo viên không tồn tại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $title    = 'Chi tiết lịch giáo viên - ' . (string) $detail['teacher']['teacher_code'];
    $template = 'lms-sap-xep-lop/chitiet-gv';
}

// ===================== Tạo lớp từ match =====================

function lms_xep_lop_create_class(\Lms\MatchingService $matchSvc)
{
    global $alias;

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $studentId = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;
    $teacherId = isset($_POST['teacher_id']) ? (int) $_POST['teacher_id'] : 0;

    $backUrl = lms_url($alias, 'man', array('student_id' => $studentId));

    if ($studentId <= 0 || $teacherId <= 0) {
        lms_flash('error', 'Thiếu thông tin học viên hoặc giáo viên.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $overrides = array();
    if (isset($_POST['name']) && trim((string) $_POST['name']) !== '') {
        $overrides['name'] = trim((string) $_POST['name']);
    }
    if (isset($_POST['price_per_session']) && (string) $_POST['price_per_session'] !== '') {
        $overrides['price_per_session'] = (float) $_POST['price_per_session'];
    }
    if (isset($_POST['total_sessions']) && (string) $_POST['total_sessions'] !== '') {
        $overrides['total_sessions'] = (int) $_POST['total_sessions'];
    }

    $classId = $matchSvc->createClassFromMatch($studentId, $teacherId, $overrides);
    if ($classId === null) {
        $errors = $matchSvc->errors();
        lms_flash('error', $errors !== array()
            ? (string) reset($errors)
            : 'Không thể tạo lớp. Vui lòng thử lại.');
        header('Location: ' . $backUrl);
        exit;
    }

    $class = $matchSvc->db()->safeFetchOne(
        'select class_code from db_lms_classes where id = ? limit 1',
        array($classId)
    );
    $code = !empty($class) ? (string) $class['class_code'] : '';

    lms_flash(
        'success',
        'Đã tạo lớp ' . $code . ' và đưa học viên sang trạng thái chờ thu phí.'
            . ' Hãy điều chỉnh học phí/số buổi trong Quản lý lớp học nếu cần.'
    );
    header('Location: ' . lms_url($alias));
    exit;
}
