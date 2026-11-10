<?php
/**
 * Admin LMS — Quản lý nội quy (ADM-11).
 *
 * Đối chiếu chuẩn mockup:
 *  - Tai-lieu-phat-trien/admin/nội quy.png
 *  - Tai-lieu-phat-trien/admin/Tài liệu admin.md (§11. Quản lý nội quy)
 *
 * PHP 7.3 compatible.
 */
if (!defined('_source')) {
    die('Error');
}

$d = lms_db();
$user = lms_current_user();
$alias = 'lms-noi-quy';

lms_form_guard($d, $user, $alias, 'view', 'admin');

$svc = new \Lms\ClassRulesService($d);
$errors = array();
$canEdit = lms_check_permission($d, $user, $alias, 'edit', 'admin');

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    lms_form_guard($d, $user, $alias, 'edit', 'admin');
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ.');
        header('Location: ' . lms_url($alias));
        exit;
    }
    $postedContent = isset($_POST['content']) ? (string) $_POST['content'] : '';
    if ($svc->saveRules($postedContent, isset($user['id']) ? (int) $user['id'] : 0)) {
        lms_flash('success', 'Đã cập nhật nội quy lớp học.');
        header('Location: ' . lms_url($alias));
        exit;
    }
    $errors = $svc->errors();
}

$rules = $svc->getRules();
if (isset($_POST['content'])) {
    $rules['content'] = (string) $_POST['content'];
}
$row = $rules;

$title = 'Quản lý nội quy';
$template = 'lms-noi-quy/hienthi';
