<?php
/**
 * Admin LMS — Quản lý nội quy: Trình soạn thảo Rich Text nội quy trung tâm (ADM-11).
 *
 * Đối chiếu chuẩn mockup:
 *  - Tai-lieu-phat-trien/admin/nội quy.png
 *  - Tai-lieu-phat-trien/admin/Tài liệu admin.md (§11. Quản lý nội quy)
 *
 * Đã chuẩn hoá 100% theo hệ thống LMS UI/Form Components:
 *  - form/rich_editor, form/actions_bar
 *
 * Data từ source lms-noi-quy.php:
 *  $rules, $row, $errors, $canEdit, $alias.
 */

$contentVal = isset($rules['content']) ? (string) $rules['content'] : '';
?>

<form method="post" action="<?= e(lms_url($alias)) ?>" id="lmsClassRulesForm" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

    <?php lms_component('form/rich_editor', array(
        'name'        => 'content',
        'id'          => 'fld-class-rules',
        'value'       => $contentVal,
        'placeholder' => 'Chỉnh sửa nội quy tại đây ...',
        'min_height'  => 420,
        'required'    => true,
        'readonly'    => empty($canEdit),
        'bare'        => true,
        'row'         => isset($row) ? $row : array(),
        'errors'      => isset($errors) ? $errors : array(),
    )); ?>

    <?php if (!empty($canEdit)): ?>
        <?php lms_component('form/actions_bar', array(
            'align'   => 'end',
            'label'   => 'Lưu',
            'variant' => 'secondary',
            'size'    => 'md',
            'class'   => 'mt-3 mb-2',
        )); ?>
    <?php endif; ?>
</form>
