<?php
/**
 * Admin LMS — Quản lý lộ trình (ADM-10 & §18 Form Thêm/Sửa chuyên đề).
 * Dẫn chứng mockup:
 * - Tai-lieu-phat-trien/admin/Quản lý lộ trình.png
 * - Tai-lieu-phat-trien/admin/chuyên đề mới.png .. chuyên đề mới (4).png
 *
 * PHP 7.3 compatible.
 */
if (!defined('_source')) {
    die('Error');
}

$d = lms_db();
$user = lms_current_user();
$alias = 'lms-lo-trinh';
$svc = new \Lms\AdminReportService($d);
$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'them':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lms_lo_trinh_form($svc, false);
        break;

    case 'sua':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        lms_lo_trinh_form($svc, true);
        break;

    case 'duplicate':
        lms_form_guard($d, $user, $alias, 'edit', 'admin');
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ.');
            header('Location: ' . lms_url($alias));
            exit;
        }
        $id = isset($_POST['id']) ? (int) $_POST['id'] : (isset($_GET['id']) ? (int) $_GET['id'] : 0);
        if ($svc->duplicateTopic($id)) {
            lms_flash('success', 'Đã nhân bản chuyên đề thành công.');
        } else {
            $errs = $svc->errors();
            lms_flash('error', $errs ? reset($errs) : 'Không thể nhân bản chuyên đề.');
        }
        header('Location: ' . lms_url($alias));
        exit;

    case 'xoa':
        lms_form_guard($d, $user, $alias, 'delete', 'admin');
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ.');
            header('Location: ' . lms_url($alias));
            exit;
        }
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($svc->deleteTopic($id)) {
            lms_flash('success', 'Đã xoá chuyên đề.');
        } else {
            $errs = $svc->errors();
            lms_flash('error', $errs ? reset($errs) : 'Không thể xoá chuyên đề.');
        }
        header('Location: ' . lms_url($alias));
        exit;

    default:
        lms_form_guard($d, $user, $alias, 'view', 'admin');
        lms_lo_trinh_list($svc);
        break;
}

/**
 * Màn hình danh sách Quản lý lộ trình (ADM-10).
 *
 * @param \Lms\AdminReportService $svc
 * @return void
 */
function lms_lo_trinh_list(\Lms\AdminReportService $svc)
{
    global $title, $template, $items, $total, $totalPages, $page, $q,
           $subjectId, $testFilter, $subjects, $canEdit, $canDelete, $alias, $d, $user;

    $q = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $subjectId = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;
    $testFilter = isset($_GET['has_test']) ? (string) $_GET['has_test'] : '';
    $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage = 20;

    $res = $svc->topicList(array(
        'q'          => $q,
        'subject_id' => $subjectId,
        'has_test'   => $testFilter,
    ), $page, $perPage);

    $items = $res['items'];
    $total = (int) $res['total'];
    $totalPages = max(1, (int) ceil($total / $perPage));
    $subjects = $svc->listSubjects();
    $canEdit = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'admin');

    $title = 'Quản lý lộ trình';
    $template = 'lms-lo-trinh/hienthi';
}

/**
 * Form Thêm / Sửa chuyên đề (§18).
 *
 * @param \Lms\AdminReportService $svc
 * @param bool                    $isEdit
 * @return void
 */
function lms_lo_trinh_form(\Lms\AdminReportService $svc, $isEdit)
{
    global $title, $template, $row, $errors, $subjects, $isEditMode, $formId, $alias;

    $isEditMode = (bool) $isEdit;
    $errors = array();
    $formId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $row = $isEditMode ? $svc->getTopic($formId) : array(
        'has_test' => '0',
    );

    if ($isEditMode && empty($row)) {
        lms_flash('error', 'Chuyên đề không tồn tại.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ.');
            header('Location: ' . lms_url($alias));
            exit;
        }
        $savedId = $svc->saveTopic($_POST, $isEditMode ? $formId : 0, isset($_FILES) ? $_FILES : array());
        if ($savedId !== false) {
            lms_flash('success', $isEditMode ? 'Đã cập nhật chuyên đề.' : 'Đã thêm chuyên đề mới.');
            header('Location: ' . lms_url($alias));
            exit;
        }
        $errors = $svc->errors();
    }

    $subjects = $svc->listSubjects();
    $title = $isEditMode ? 'Chỉnh sửa chuyên đề' : 'Thêm chuyên đề mới';
    $template = 'lms-lo-trinh/form';
}
