<?php
/**
 * Admin LMS — Học sinh - Lớp học: form thêm lớp cho 1 HV (ADM-04).
 * Data do source chuẩn bị: $studentId, $studentName, $classOptions, $errors,
 * $filterClassType, $alias.
 */
?>
<form method="post" action="<?= e(lms_url($alias, 'them', array('student_id' => (int) $studentId, 'type' => $filterClassType))) ?>"
      class="lms-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
    <input type="hidden" name="student_id" value="<?= e((string) (int) $studentId) ?>">

    <div class="lms-card">
        <div class="lms-card-header"><i class="fa-solid fa-plus me-1"></i> Thêm lớp cho học viên</div>
        <div class="lms-card-body">
            <div class="mb-3">
                <label class="lms-form-label" for="fld-student">Học viên</label>
                <input type="text" class="form-control" id="fld-student"
                       value="<?= e($studentName) ?>" readonly>
            </div>
            <div class="mb-3">
                <label class="lms-form-label" for="fld-class">Lớp học <span class="text-danger">*</span></label>
                <select class="form-select" id="fld-class" name="class_id" required>
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
                        Học viên đã có mặt trong tất cả lớp <?= e($filterClassType === 'one_on_one' ? '1 kèm 1' : 'đông người') ?> đang hoạt động.
                    </div>
                <?php endif; ?>
                <?= lms_field_error($errors, 'class_id') ?>
                <?= lms_field_error($errors, 'student_id') ?>
            </div>
        </div>
    </div>

    <div class="lms-form-actions">
        <button type="submit" class="btn btn-lms px-4">
            <i class="fa-solid fa-plus me-1"></i> Thêm
        </button>
        <a href="<?= e(lms_url($alias, 'man', array('type' => $filterClassType))) ?>" class="btn btn-outline-secondary">Huỷ</a>
    </div>
</form>
