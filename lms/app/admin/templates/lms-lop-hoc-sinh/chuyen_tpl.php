<?php
/**
 * Admin LMS — Học sinh - Lớp học: form chuyển lớp (ADM-04).
 * Data do source chuẩn bị: $studentId, $studentName, $fromClassId, $fromClassCode,
 * $classOptions, $errors, $filterClassType, $alias.
 */
?>
<form method="post" action="<?= e(lms_url($alias, 'chuyen', array(
    'student_id' => (int) $studentId,
    'from_class_id' => (int) $fromClassId,
))) ?>" class="lms-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
    <input type="hidden" name="student_id" value="<?= e((string) (int) $studentId) ?>">
    <input type="hidden" name="from_class_id" value="<?= e((string) (int) $fromClassId) ?>">

    <div class="lms-card">
        <div class="lms-card-header"><i class="fa-solid fa-arrow-right-arrow-left me-1"></i> Chuyển lớp</div>
        <div class="lms-card-body">
            <div class="mb-3">
                <label class="lms-form-label" for="fld-student">Học viên</label>
                <input type="text" class="form-control" id="fld-student"
                       value="<?= e($studentName) ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="lms-form-label" for="fld-from">Lớp hiện tại</label>
                <input type="text" class="form-control" id="fld-from"
                       value="<?= e($fromClassCode) ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="lms-form-label" for="fld-to">Chuyển sang lớp <span class="text-danger">*</span></label>
                <select class="form-select" id="fld-to" name="to_class_id" required>
                    <option value="">— Chọn lớp —</option>
                    <?php foreach ($classOptions as $o): ?>
                        <option value="<?= e((string) (int) $o['id']) ?>">
                            <?= e((string) ($o['class_code'] ?? '')) ?>
                            — <?= e((string) ($o['program_name'] ?? '')) ?>
                            — <?= e((string) ($o['teacher_code'] ?? '')) ?> <?= e((string) ($o['teacher_name'] ?? '')) ?>
                            <?php if (!empty($o['schedules_text'])): ?> — <?= e((string) $o['schedules_text']) ?><?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($classOptions)): ?>
                    <div class="lms-text-muted-sm mt-1">
                        Không còn lớp nào phù hợp để chuyển tới.
                    </div>
                <?php endif; ?>
                <?= lms_field_error($errors, 'to_class_id') ?>
                <?= lms_field_error($errors, 'from_class_id') ?>
            </div>
        </div>
    </div>

    <div class="lms-form-actions">
        <button type="submit" class="btn btn-lms px-4">
            <i class="fa-solid fa-arrow-right-arrow-left me-1"></i> Chuyển
        </button>
        <a href="<?= e(lms_url($alias, 'man', array('type' => $filterClassType))) ?>" class="btn btn-outline-secondary">Huỷ</a>
    </div>
</form>
