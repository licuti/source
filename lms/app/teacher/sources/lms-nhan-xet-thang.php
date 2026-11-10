<?php
/**
 * Teacher TCH-06 — Nhận xét theo tháng.
 *
 * Action:
 *   - 'man' (GET)  → danh sách card lớp/HV + form nhận xét.
 *   - 'luu' (POST) → lưu & đăng nhận xét (1-1: 1 field; lớp nhóm: nhiều field).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d = lms_db();
$user = lms_current_user();
$alias = 'lms-nhan-xet-thang';
$teacherId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;
$svc = new \Lms\MonthlyReviewService($d);
$action = isset($a) ? (string) $a : 'man';

if ($action === 'luu') {
    lms_form_guard($d, $user, $alias, 'edit', 'teacher');
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $month = isset($_POST['review_month']) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $_POST['review_month'])
        ? (string) $_POST['review_month']
        : date('Y-m');
    $classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;

    $saved = 0;
    $firstError = '';

    if (isset($_POST['reviews']) && is_array($_POST['reviews'])) {
        foreach ($_POST['reviews'] as $studentId => $content) {
            $content = trim((string) $content);
            if ($content === '') {
                continue;
            }
            $data = array(
                'class_id'        => $classId,
                'student_id'      => (int) $studentId,
                'review_month'    => $month,
                'content'         => $content,
            );
            if ($svc->saveForTeacher($teacherId, $data, true)) {
                $saved++;
            } elseif ($firstError === '') {
                $errs = $svc->errors();
                $firstError = $errs ? (string) reset($errs) : 'Không thể lưu nhận xét.';
            }
        }

        if ($saved > 0) {
            lms_flash('success', 'Đã cập nhật nhận xét tháng cho ' . $saved . ' học viên.');
        } else {
            lms_flash('error', $firstError !== '' ? $firstError : 'Vui lòng nhập nội dung nhận xét cho ít nhất một học viên.');
        }
    } else {
        // Tương thích form cũ (1 học viên / 1 field).
        $data = array(
            'class_id'         => $classId,
            'student_id'       => isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0,
            'review_month'     => $month,
            'content'          => isset($_POST['content']) ? (string) $_POST['content'] : '',
            'overall_rating'   => isset($_POST['overall_rating']) ? (string) $_POST['overall_rating'] : '',
            'progress_percent' => isset($_POST['progress_percent']) ? (string) $_POST['progress_percent'] : '',
        );
        if ($svc->saveForTeacher($teacherId, $data, true)) {
            lms_flash('success', 'Đã cập nhật nhận xét tháng.');
        } else {
            $errs = $svc->errors();
            lms_flash('error', $errs ? (string) reset($errs) : 'Không thể lưu nhận xét.');
        }
    }

    header('Location: ' . lms_url($alias, 'man', array('month' => $month, 'open' => $classId)));
    exit;
}

$month = isset($_GET['month']) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $_GET['month'])
    ? (string) $_GET['month']
    : date('Y-m');
$openClassId = isset($_GET['open']) ? (int) $_GET['open'] : 0;

$classes = $svc->listClassesForTeacher($teacherId, $month);
$months = $svc->teacherMonthOptions($teacherId);

$title = 'Nhận xét theo tháng';
$template = 'lms-nhan-xet-thang/hienthi';
