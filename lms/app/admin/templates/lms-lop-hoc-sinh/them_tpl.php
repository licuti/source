<?php
/**
 * Admin LMS — Học sinh - Lớp học: form thêm lớp cho 1 HV (ADM-03).
 * Data do source chuẩn bị: $studentId, $studentName, $classOptions, $errors,
 * $filterClassType, $alias.
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
<form method="post" action="<?= e(lms_url($alias, 'them', array('student_id' => (int) $studentId, 'type' => $filterClassType))) ?>"
      class="lms-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">
    <input type="hidden" name="student_id" value="<?= e((string) (int) $studentId) ?>">

    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8 col-xl-7">
            <?php lms_component('form/input', array(
                'name'     => 'student_display',
                'label'    => 'Học viên',
                'value'    => $studentName,
                'readonly' => true,
            )); ?>

            <?php lms_component('form/select', array(
                'name'        => 'class_id',
                'label'       => 'Lớp học',
                'options'     => $classSelectOptions,
                'empty_label' => '— Chọn lớp —',
                'required'    => true,
                'hint'        => empty($classOptions)
                    ? 'Học viên đã có mặt trong tất cả lớp ' . ($filterClassType === 'one_on_one' ? '1 kèm 1' : 'đông người') . ' đang hoạt động.'
                    : '',
                'errors'      => $errors,
            )); ?>

            <?= lms_field_error($errors, 'student_id') ?>

            <?php lms_component('form/actions_bar', array(
                'label'      => 'Thêm',
                'variant'    => 'secondary',
                'icon'       => 'fa-solid fa-plus',
                'cancel_url' => lms_url($alias, 'man', array('type' => $filterClassType)),
            )); ?>
        </div>
    </div>
</form>
