<?php
/**
 * Student — Nội quy lớp học (mockup §12).
 *
 * Action:
 *   - 'man' (GET) → khung nội quy read-only.
 *
 * Nội dung do admin nhập ở module "Quản lý nội quy" (Phase 3). Giai đoạn này hiển
 * thị placeholder (plan STU quyết định #2 — không tạo bảng/service admin).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d    = lms_db();
$user = lms_current_user();
$alias = 'lms-noi-quy';
$studentId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    default:
        lmnq_show();
        break;
}

// ===================== Action: man =====================

function lmnq_show()
{
    global $title, $template, $rules;

    // Placeholder: admin nhập nội quy qua module "Quản lý nội quy" (Phase 3).
    // Khi có dữ liệu, thay mảng này bằng query db_lms_class_rules.
    $rules = array();

    $title    = 'Nội quy lớp học';
    $template = 'lms-noi-quy/hienthi';
}
