<?php
/**
 * Admin LMS — Học sinh - Lớp học: form chuyển lớp (ADM-03).
 * Data do source chuẩn bị: $studentId, $studentName, $fromClassId, $fromClassCode,
 * $classOptions, $errors, $filterClassType, $alias.
 */
$classSelectOptions = array();
foreach ($classOptions as $o) {
    $parts = array((string) ($o['class_code'] ?? ''));
    if (!empty($o['program_name'])) {
        $parts[] = (string) $o['program_name'];
    }
    $teacher = trim((string) ($o['teacher_code'] ?? '') . ' ' . (string) ($o['teacher_name'] ?? ''));
    if ($teacher !== '') {
        $parts[] = $teacher;
    }
    if (!empty($o['schedules_text'])) {
        $parts[] = (string) $o['schedules_text'];
    }
    $classSelectOptions[(int) $o['id']] = implode(' — ', $parts);
}
?>
<form method="post" action="<?= e(lms_url($alias, 'chuyen', array(
    'student_id'    => (int) $studentId,
    'from_class_id' => (int) $fromClassId,
))) ?>" class="lms-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
    <input type="hidden" name="student_id" value="<?= e((string) (int) $studentId) ?>">
    <input type="hidden" name="from_class_id" value="<?= e((string) (int) $fromClassId) ?>">

    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8 col-xl-7">
            <?php lms_component('form/input', array(
                'name'     => 'student_display',
                'label'    => 'Học viên',
                'value'    => $studentName,
                'readonly' => true,
            )); ?>

            <?php lms_component('form/input', array(
                'name'     => 'from_class_display',
                'label'    => 'Lớp hiện tại',
                'value'    => $fromClassCode,
                'readonly' => true,
            )); ?>

            <?php lms_component('form/select', array(
                'name'        => 'to_class_id',
                'label'       => 'Chuyển sang lớp',
                'options'     => $classSelectOptions,
                'empty_label' => '— Chọn lớp —',
                'required'    => true,
                'hint'        => empty($classOptions) ? 'Không còn lớp nào phù hợp để chuyển tới.' : '',
                'errors'      => $errors,
            )); ?>

            <?= lms_field_error($errors, 'from_class_id') ?>

            <?php lms_component('form/actions_bar', array(
                'label'      => 'Chuyển',
                'variant'    => 'secondary',
                'icon'       => 'fa-solid fa-arrow-right-arrow-left',
                'cancel_url' => lms_url($alias, 'man', array('type' => $filterClassType)),
            )); ?>
        </div>
    </div>
</form>
