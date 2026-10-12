<?php
/**
 * Student — Nội quy lớp học (mockup §12).
 *
 * Action:
 *   - 'man' (GET) → khung nội quy read-only.
 *
 * Nội dung do admin quản lý ở module lms-noi-quy; render read-only và escape HTML.
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
    $content = (new \Lms\ClassRulesService(lms_db()))->getRules();
    $rules = trim((string) $content['content']) === ''
        ? array()
        : array(array('title' => 'Nội quy chung', 'content' => $content['content']));

    $title    = 'Nội quy lớp học';
    $template = 'lms-noi-quy/hienthi';
}
