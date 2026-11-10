<?php
/**
 * Student LMS — Nội quy lớp học (STU-12).
 *
 * Đối chiếu chuẩn mockup:
 *  - Tai-lieu-phat-trien/student/Trang Nội quy lớp học.png
 *  - Tai-lieu-phat-trien/student/Tài liệu student.md (§12. Nội quy lớp học)
 *
 * PHP 7.3 compatible.
 */
if (!defined('_source')) {
    die('Error');
}

$d = lms_db();
$user = lms_current_user();
$alias = 'lms-noi-quy';

lms_form_guard($d, $user, $alias, 'view', 'student');

$svc = new \Lms\ClassRulesService($d);
$rules = $svc->getRules();

$title = 'Nội quy lớp học';
$template = 'lms-noi-quy/hienthi';
