<?php
/**
 * Student — Nội quy lớp học (view, mockup §12).
 * Data do source chuẩn bị: $rules (mảng nội quy; Phase 3 admin mới nhập → placeholder).
 */
?>
<div class="lms-card">
    <div class="lms-card-header">
        <i class="fa-solid fa-scroll me-1"></i> Nội quy lớp học
    </div>
    <div class="lms-card-body">
        <?php if (empty($rules)): ?>
            <div class="lmnq-placeholder text-center py-4">
                <i class="fa-solid fa-scroll" style="font-size:2.4rem;color:#F26B2A;"></i>
                <p class="mt-3 mb-1"><strong>Nội quy đang được cập nhật</strong></p>
                <p class="text-muted mb-0" style="font-size:.9rem;">
                    Trung tâm đang soạn nội quy lớp học. Nội quy sẽ được hiển thị tại đây
                    ngay khi ban quản trị hoàn tất.
                </p>
            </div>
        <?php else: ?>
            <ol class="lmnq-list">
                <?php foreach ($rules as $rule): ?>
                    <li>
                        <?php if (!empty($rule['title'])): ?>
                            <strong><?= e((string) $rule['title']) ?></strong>
                        <?php endif; ?>
                        <?php if (!empty($rule['content'])): ?>
                            <div><?= nl2br(e((string) $rule['content'])) ?></div>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </div>
</div>
