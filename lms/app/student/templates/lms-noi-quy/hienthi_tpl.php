<?php
/**
 * Student LMS — Nội quy lớp học: khung nội dung chỉ đọc (STU-12).
 *
 * Đối chiếu chuẩn mockup:
 *  - Tai-lieu-phat-trien/student/Trang Nội quy lớp học.png
 *  - Tai-lieu-phat-trien/student/Tài liệu student.md (§12. Nội quy lớp học)
 *
 * Nội dung do Admin nhập tại /lms/admin/lms-noi-quy; học sinh chỉ đọc.
 * Dùng ClassRulesService::formatForDisplay() để escape văn bản thuần hoặc
 * sanitize HTML trước khi render.
 *
 * Data từ source lms-noi-quy.php: $rules.
 */

$rawContent = isset($rules['content']) ? (string) $rules['content'] : '';
$hasContent = !\Lms\ClassRulesService::isEmptyContent($rawContent);
?>

<div class="lms-rules-panel">
    <?php if ($hasContent): ?>
        <div class="lms-rich-content lms-rules-content">
            <?= \Lms\ClassRulesService::formatForDisplay($rawContent) ?>
        </div>
    <?php else: ?>
        <p class="lms-rules-empty mb-0">Hiện text nội quy admin nhập ....</p>
    <?php endif; ?>
</div>
