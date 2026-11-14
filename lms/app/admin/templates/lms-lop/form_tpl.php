<?php
/**
 * Admin LMS — Quản lý lớp học: Form Thêm / Sửa lớp học (ADM-03).
 * Thiết kế đối chiếu chuẩn mockup: C:\laragon\www\source\Tai-lieu-phat-trien\admin\Quản lý lớp học - Thêm.png
 *
 * Data do source chuẩn bị: $row, $errors, $isEditMode, $formId, $alias,
 * $programs, $subjects, $teachers, $students, $topics.
 */
$classTypes = array(
    'one_on_one' => '1 kèm 1',
    'group'      => 'Đông người',
);

$schedules   = isset($row['schedules']) && is_array($row['schedules']) ? $row['schedules'] : array();
$formScheduleRows = array();
foreach ($schedules as $sch) {
    $formScheduleRows[] = array(
        'day'   => (int) ($sch['day'] ?? 0),
        'start' => substr((string) ($sch['start'] ?? '17:30'), 0, 5),
        'end'   => substr((string) ($sch['end'] ?? '19:00'), 0, 5),
    );
}
if ($formScheduleRows === array()) {
    $formScheduleRows = array(
        array('day' => 2, 'start' => '17:30', 'end' => '19:00'),
        array('day' => 6, 'start' => '17:30', 'end' => '19:00'),
    );
}
$selStudents = array_map('intval', function_exists('lms_form_old_array') ? lms_form_old_array($_POST, $row, 'student_ids') : array());
$selTopics   = array_map('intval', function_exists('lms_form_old_array') ? lms_form_old_array($_POST, $row, 'topic_ids') : array());

// Map teacher_id → % phí mặc định (JS tự điền khi đổi giáo viên)
$teacherFee = array();
foreach ($teachers as $t) {
    $teacherFee[(int) $t['user_id']] = isset($t['service_fee_percentage'])
        ? (float) $t['service_fee_percentage'] : 48.0;
}

// Chuẩn bị danh sách options cho các components
$programOptions = array();
foreach ($programs as $p) {
    $programOptions[(int) $p['id']] = (string) $p['name'];
}

$subjectOptions = array();
if (!empty($subjects) && is_array($subjects)) {
    foreach ($subjects as $s) {
        $subjectOptions[(string) $s['id']] = (string) $s['name'];
    }
}
$subjectOptions['__new__'] = '+ Thêm loại chuyên đề mới';

$teacherOptions = array();
foreach ($teachers as $t) {
    $teacherOptions[(int) $t['user_id']] = ($t['teacher_code'] ?? '') . ' - ' . ($t['full_name'] ?? '');
}

$studentOptions = array();
foreach ($students as $st) {
    $studentOptions[(int) $st['user_id']] = ($st['student_code'] ?? '') . ' - ' . ($st['full_name'] ?? '');
}

$formUrl = $isEditMode
    ? lms_url($alias, 'sua', array('id' => $formId))
    : lms_url($alias, 'them');

$currentClassType = lms_form_old($_POST, $row, 'class_type', 'one_on_one');
?>

<form method="post" action="<?= e($formUrl) ?>" class="lms-form" id="lmsClassForm" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

    <div class="row">
        <div class="col-lg-8">

            <!-- ===================== KHỐI 1: THÔNG TIN LỚP HỌC ===================== -->
            
            <!-- 1. Nhóm chương trình -->
            <?php lms_component('form/select', array(
                'name'        => 'program_id',
                'label'       => 'Nhóm chương trình',
                'options'     => $programOptions,
                'empty_label' => '— Chọn nhóm chương trình —',
                'required'    => true,
                'id'          => 'fld-program',
            )); ?>

            <!-- 2. Danh mục chính (không dùng để giới hạn chuyên đề) -->
            <?php
            $selectedSubject = function_exists('lms_form_old')
                ? (string) lms_form_old($_POST, $row, 'subject_id', '')
                : (isset($row['subject_id']) ? (string) $row['subject_id'] : '');
            ?>
            <?php lms_component('form/select', array(
                'name'        => 'subject_id',
                'label'       => 'Danh mục chính',
                'options'     => $subjectOptions,
                'empty_label' => '— Chọn danh mục chính —',
                'value'       => $selectedSubject,
                'required'    => true,
                'id'          => 'fld-subject',
                'row'         => $row,
                'errors'      => $errors,
            )); ?>

            <!-- Ô nhập tên danh mục mới (ẩn/hiện tự động khi chọn "+ Thêm loại chuyên đề mới") -->
            <div id="newSubjectRowWrap" style="<?= $selectedSubject === '__new__' ? '' : 'display: none;' ?>">
                <?php lms_component('form/input', array(
                    'name'        => 'new_subject_name',
                    'id'          => 'fld-new-subject-name',
                    'label'       => 'Loại chuyên đề mới',
                    'placeholder' => 'Nhập tên danh mục mới (VD: Math Problems, Reading...)',
                    'required'    => $selectedSubject === '__new__',
                    'row'         => $row,
                    'errors'      => $errors,
                )); ?>
            </div>

            <!-- 3. Loại lớp -->
            <?php lms_component('form/select', array(
                'name'        => 'class_type',
                'label'       => 'Loại lớp',
                'options'     => $classTypes,
                'default'     => 'one_on_one',
                'required'    => true,
                'id'          => 'fld-class-type',
            )); ?>

            <!-- 4. Mã lớp -->
            <?php lms_component('form/input', array(
                'name'        => 'name',
                'label'       => 'Mã lớp',
                'placeholder' => 'VD: Toán 227',
                'required'    => true,
                'id'          => 'fld-name',
            )); ?>

            <!-- 5. Chọn giáo viên -->
            <?php lms_component('form/select', array(
                'name'        => 'teacher_id',
                'label'       => 'Chọn giáo viên',
                'options'     => $teacherOptions,
                'empty_label' => '— Chọn giáo viên —',
                'required'    => true,
                'id'          => 'fld-teacher',
            )); ?>

            <!-- 6. Chọn học viên (1-1 hoặc Đông người) -->
            <?php
            ob_start();
            ?>
            <!-- Dropdown chọn 1 học viên (khi là 1 kèm 1) -->
            <div id="wrapper-student-single" style="<?= $currentClassType === 'group' ? 'display:none;' : '' ?>">
                <?php lms_component('form/select', array(
                    'id'          => 'fld-student-single',
                    'name'        => 'student_ids[]',
                    'options'     => $studentOptions,
                    'empty_label' => '— Chọn học viên —',
                    'value'       => !empty($selStudents) ? (string) $selStudents[0] : '',
                    'disabled'    => ($currentClassType === 'group'),
                    'required'    => ($currentClassType !== 'group'),
                    'bare'        => true,
                )); ?>
            </div>

            <!-- Multi-select chọn nhiều học viên (khi là Đông người) -->
            <div id="wrapper-student-multi" style="<?= $currentClassType !== 'group' ? 'display:none;' : '' ?>">
                <?php lms_component('form/select', array(
                    'id'       => 'fld-student-multi',
                    'name'     => 'student_ids[]',
                    'options'  => $studentOptions,
                    'value'    => $selStudents,
                    'class'    => 'select2-multiple',
                    'disabled' => ($currentClassType !== 'group'),
                    'required' => ($currentClassType === 'group'),
                    'attrs'    => array(
                        'multiple'         => true,
                        'data-placeholder' => '— Chọn học viên —',
                        'style'            => 'width: 100%;',
                    ),
                    'bare'     => true,
                )); ?>
                <div class="text-muted mt-1" style="font-size: 0.78rem;">
                    *Cho phép tìm kiếm và chọn nhiều học viên nếu là lớp đông người.
                </div>
            </div>
            <?php
            $studentControl = ob_get_clean();
            lms_component('form/field_row', array(
                'label'    => 'Chọn học viên',
                'name'     => 'student_ids',
                'id'       => 'fld-students',
                'required' => true,
                'control'  => $studentControl,
                'errors'   => $errors,
            ));
            ?>

            <!-- 7. Lịch cố định -->
            <?php
            ob_start();
            lms_component('form/class_schedule_slots', array(
                'id'       => 'lms-schedules-container',
                'rows'     => $formScheduleRows,
                'can_edit' => true,
            ));
            $schedulesControl = ob_get_clean();
            lms_component('form/field_row', array(
                'label'    => 'Lịch cố định',
                'name'     => 'schedules',
                'align'    => 'start',
                'required' => true,
                'control'  => $schedulesControl,
                'errors'   => $errors,
            ));
            ?>

            <!-- 8. Học phí 1 buổi -->
            <?php lms_component('form/addon_input', array(
                'name'        => 'price_per_session',
                'label'       => 'Học phí 1 buổi',
                'placeholder' => 'VD: 560000',
                'suffix'      => 'đồng',
                'type'        => 'number',
                'min'         => 0,
                'step'        => 1000,
                'required'    => true,
                'id'          => 'fld-price',
            )); ?>

            <!-- 9. Phần trăm gv nhận -->
            <?php
            $feeControl = lms_component_render('form/addon_input', array(
                'name'        => 'teacher_percentage',
                'label'       => 'Phần trăm gv nhận',
                'placeholder' => 'VD: 48',
                'suffix'      => '%',
                'type'        => 'number',
                'min'         => 0,
                'max'         => 100,
                'step'        => 0.5,
                'required'    => true,
                'id'          => 'fld-fee',
                'bare'        => true,
            ));
            $feeControl .= '<div class="lms-tf-hint">*Hiện theo số mặc định trong tài khoản gv, nhưng vẫn cho phép chỉnh ở đây (không ảnh hưởng tới tài khoản)</div>';
            if ($isEditMode) {
                $feeControl .= '<div class="lms-tf-hint mt-1">'
                    . 'Mặc định GV: <strong id="fld-fee-default-value">—</strong> · '
                    . '<a href="#" id="fld-fee-default-link" class="link-danger fw-semibold">Lấy mặc định GV</a>'
                    . '</div>';
            }
            lms_component('form/field_row', array(
                'label'    => 'Phần trăm gv nhận',
                'name'     => 'teacher_percentage',
                'id'       => 'fld-fee',
                'required' => true,
                'control'  => $feeControl,
                'errors'   => $errors,
            ));
            ?>

            <!-- 10. Áp dụng từ ngày -->
            <?php lms_component('form/date_picker', array(
                'name'     => 'start_date',
                'label'    => 'Áp dụng từ ngày',
                'default'  => date('Y-m-d'),
                'required' => true,
                'id'       => 'fld-start-date',
                'hint'     => $isEditMode
                    ? '*Khi điều chỉnh học phí hoặc % GV nhận, mức mới sẽ áp dụng cho các buổi học từ ngày áp dụng trở đi (các buổi đã dạy trước đó giữ nguyên mức cũ).'
                    : '',
                'row'      => $row,
                'errors'   => $errors,
            )); ?>

            <!-- 11. Ghi chú -->
            <?php lms_component('form/textarea', array(
                'name'        => 'note',
                'label'       => 'Ghi chú',
                'rows'        => 3,
                'placeholder' => 'Ghi chú về lớp học...',
                'id'          => 'fld-note',
            )); ?>

            <!-- 12. Chọn chuyên đề -->
            <?php
            $topicGroups = array();
            foreach ($topics as $t) {
                $groupName = isset($t['subject_name']) && trim((string) $t['subject_name']) !== ''
                    ? (string) $t['subject_name']
                    : 'Khác';
                $topicGroups[$groupName][] = $t;
            }
            ob_start();
            ?>
            <select class="form-select select2-multiple" id="fld-topics" name="topic_ids[]" multiple data-placeholder="— Chọn chuyên đề —" style="width: 100%;">
                <?php foreach ($topicGroups as $groupName => $groupTopics): ?>
                    <optgroup label="<?= e($groupName) ?>">
                        <?php foreach ($groupTopics as $t): ?>
                            <option value="<?= (int) $t['id'] ?>"
                                    data-subject-id="<?= (int) $t['subject_id'] ?>"<?= in_array((int) $t['id'], $selTopics, true) ? ' selected' : '' ?>>
                                <?= e($t['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
            <?php
            $topicsControl = ob_get_clean();
            lms_component('form/field_row', array(
                'label'   => 'Chọn chuyên đề',
                'name'    => 'topic_ids',
                'id'      => 'fld-topics',
                'align'   => 'start',
                'control' => $topicsControl,
                'hint'    => '*Cho phép chọn nhiều chuyên đề thuộc nhiều Danh mục.',
                'errors'  => $errors,
            ));
            ?>

            <!-- ===================== KHỐI 2: ĐĂNG KÝ KHOÁ ĐẦU ===================== -->
            <div class="border-top pt-4 mt-4">
                <h5 class="fw-bold text-dark mb-3">Đăng ký khoá đầu:</h5>

                <!-- Số buổi: -->
                <?php lms_component('form/addon_input', array(
                    'name'        => 'total_sessions',
                    'label'       => 'Số buổi:',
                    'placeholder' => 'VD: 24',
                    'suffix'      => 'buổi',
                    'type'        => 'number',
                    'min'         => 0,
                    'default'     => '24',
                    'id'          => 'fld-total-sessions',
                )); ?>

                <!-- Ngày bắt đầu -->
                <?php lms_component('form/date_picker', array(
                    'name'    => 'cycle_start_date',
                    'label'   => 'Ngày bắt đầu',
                    'default' => date('Y-m-d'),
                    'id'      => 'fld-cycle-start-date',
                    'row'     => $row,
                    'errors'  => $errors,
                )); ?>
            </div>

            <!-- ===================== KHỐI 3: CÀI ĐẶT ===================== -->
            <div class="border-top pt-4 mt-4">
                <div class="d-flex align-items-baseline justify-content-between mb-3">
                    <h5 class="fw-bold text-dark mb-0">Cài đặt</h5>
                    <span class="text-muted small" id="lms-setting-note">
                        mặc định cho lớp 1-1:
                    </span>
                </div>

                <!-- 1. Hạn giao BTVN -->
                <?php
                ob_start();
                ?>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Toggle switch -->
                    <div class="form-check form-switch me-1 mb-0">
                        <input class="form-check-input" type="checkbox" id="sw-hw-deadline" checked style="cursor: pointer;">
                    </div>
                    <!-- Input giờ -->
                    <div style="width: 120px;">
                        <?php lms_component('form/input', array(
                            'type'    => 'time',
                            'id'      => 'fld-hw-deadline',
                            'name'    => 'hw_deadline_time',
                            'value'   => lms_form_old($_POST, $row, 'hw_deadline_time', '12:00'),
                            'class'   => 'text-center',
                            'bare'    => true,
                        )); ?>
                    </div>
                    <span class="text-muted small">của ngày hôm sau</span>

                    <!-- Phạt GV -->
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <span class="text-dark fw-semibold small">Phạt GV</span>
                        <div style="width: 100px;">
                            <?php lms_component('form/addon_input', array(
                                'name'     => 'hw_penalty_ticks',
                                'value'    => lms_form_old($_POST, $row, 'hw_penalty_ticks', '1'),
                                'suffix'   => 'tick',
                                'type'     => 'number',
                                'min'      => 0,
                                'class'    => 'text-center',
                                'bare'     => true,
                            )); ?>
                        </div>
                    </div>
                </div>
                <?php
                $hwDeadlineControl = ob_get_clean();
                lms_component('form/field_row', array(
                    'label'       => 'Hạn giao BTVN',
                    'id'          => 'fld-hw-deadline',
                    'control'     => $hwDeadlineControl,
                    'label_col'   => 'col-12 col-sm-4',
                    'control_col' => 'col-12 col-sm-8',
                ));
                ?>

                <!-- 2. Hạn check BTVN -->
                <?php
                ob_start();
                ?>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Toggle switch -->
                    <div class="form-check form-switch me-1 mb-0">
                        <input class="form-check-input" type="checkbox" id="sw-hw-check" checked style="cursor: pointer;">
                    </div>
                    <!-- Input số giờ -->
                    <div style="width: 85px;">
                        <?php lms_component('form/input', array(
                            'type'    => 'number',
                            'id'      => 'fld-hw-check',
                            'name'    => 'hw_check_hours',
                            'value'   => lms_form_old($_POST, $row, 'hw_check_hours', '0'),
                            'class'   => 'text-center',
                            'attrs'   => array('min' => 0, 'max' => 720),
                            'bare'    => true,
                        )); ?>
                    </div>
                    <span class="text-muted small">giờ trước buổi tiếp theo</span>

                    <!-- Phạt GV -->
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <span class="text-dark fw-semibold small">Phạt GV</span>
                        <div style="width: 100px;">
                            <?php lms_component('form/addon_input', array(
                                'name'     => 'hw_check_penalty_ticks',
                                'value'    => lms_form_old($_POST, $row, 'hw_check_penalty_ticks', '1'),
                                'suffix'   => 'tick',
                                'type'     => 'number',
                                'min'      => 0,
                                'class'    => 'text-center',
                                'bare'     => true,
                            )); ?>
                        </div>
                    </div>
                </div>
                <?php
                $hwCheckControl = ob_get_clean();
                lms_component('form/field_row', array(
                    'label'       => 'Hạn check BTVN',
                    'id'          => 'fld-hw-check',
                    'control'     => $hwCheckControl,
                    'hint'        => '*Chỉ áp dụng nếu học sinh có nộp BTVN trước deadline GV giao.',
                    'label_col'   => 'col-12 col-sm-4',
                    'control_col' => 'col-12 col-sm-8',
                ));
                ?>

                <!-- 3. Hạn chấm test -->
                <?php
                ob_start();
                ?>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <!-- Toggle switch -->
                    <div class="form-check form-switch me-1 mb-0">
                        <input class="form-check-input" type="checkbox" id="sw-test-grace" checked style="cursor: pointer;">
                    </div>
                    <!-- Input số giờ -->
                    <div style="width: 85px;">
                        <?php lms_component('form/input', array(
                            'type'    => 'number',
                            'id'      => 'fld-test-grace',
                            'name'    => 'test_grace_hours',
                            'value'   => lms_form_old($_POST, $row, 'test_grace_hours', '72'),
                            'class'   => 'text-center',
                            'attrs'   => array('min' => 0, 'max' => 720),
                            'bare'    => true,
                        )); ?>
                    </div>
                    <span class="text-muted small">giờ kể từ khi hs nộp bài</span>

                    <!-- Phạt GV -->
                    <div class="ms-auto d-flex align-items-center gap-2">
                        <span class="text-dark fw-semibold small">Phạt GV</span>
                        <div style="width: 100px;">
                            <?php lms_component('form/addon_input', array(
                                'name'     => 'test_penalty_ticks',
                                'value'    => lms_form_old($_POST, $row, 'test_penalty_ticks', '1'),
                                'suffix'   => 'tick',
                                'type'     => 'number',
                                'min'      => 0,
                                'class'    => 'text-center',
                                'bare'     => true,
                            )); ?>
                        </div>
                    </div>
                </div>
                <?php
                $testGraceControl = ob_get_clean();
                lms_component('form/field_row', array(
                    'label'       => 'Hạn chấm test',
                    'id'          => 'fld-test-grace',
                    'control'     => $testGraceControl,
                    'label_col'   => 'col-12 col-sm-4',
                    'control_col' => 'col-12 col-sm-8',
                ));
                ?>
            </div>

            <!-- ===================== NÚT THAO TÁC ===================== -->
            <div class="d-flex justify-content-center gap-3 mt-4 pt-3 mb-5">
                <?php lms_component('button', array(
                    'label'   => $isEditMode ? 'Lưu thay đổi' : 'Thêm',
                    'type'    => 'submit',
                    'variant' => 'primary',
                    'size'    => 'md',
                    'class'   => 'px-5 py-2 fw-bold',
                )); ?>
                <?php lms_component('button', array(
                    'label'   => 'Huỷ',
                    'href'    => lms_url($alias),
                    'variant' => 'outline-primary',
                    'class'   => 'px-4 py-2',
                )); ?>
            </div>

        </div>
    </div>
</form>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        (function ($) {
            'use strict';
        var teacherFees = <?= json_encode($teacherFee) ?>;

        // ---------- 1. Toggle thêm loại chuyên đề mới (không lọc theo Nhóm chương trình) ----------
        var $subject = $('#fld-subject');
        var $newSubjectWrap = $('#newSubjectRowWrap');
        var $newSubjectInput = $('#fld-new-subject-name');

        function toggleNewSubject() {
            var isNew = ($subject.val() === '__new__');
            if (isNew) {
                $newSubjectWrap.show();
                $newSubjectInput.prop('required', true);
            } else {
                $newSubjectWrap.hide();
                $newSubjectInput.prop('required', false);
            }
        }

        $subject.on('change', function () {
            toggleNewSubject();
            if ($subject.val() === '__new__') {
                $newSubjectInput.focus();
            }
        });
        if ($.fn.select2) {
            $subject.on('change.select2', function () {
                toggleNewSubject();
                if ($subject.val() === '__new__') {
                    $newSubjectInput.focus();
                }
            });
        }
        toggleNewSubject();

        // ---------- 3. Chọn chuyên đề (không giới hạn theo Danh mục) ----------
        var $topics = $('#fld-topics');
        if ($.fn.select2 && !$topics.data('select2')) {
            $topics.select2({
                theme: 'bootstrap-5',
                width: '100%',
                placeholder: '— Chọn chuyên đề —'
            });
        }

        // ---------- 4. Tự điền % phí mặc định của GV ----------
        var isEditMode = <?= $isEditMode ? 'true' : 'false' ?>;
        var $teacher = $('#fld-teacher');
        var $fee = $('#fld-fee');

        function updateFeeDefaultHint() {
            var tid = String($teacher.val() || '');
            var hasDefault = tid !== '' && teacherFees[tid] !== undefined;
            $('#fld-fee-default-value').text(hasDefault ? (teacherFees[tid] + '%') : '—');
        }

        if (!isEditMode) {
            $teacher.on('change', function () {
                var tid = String($(this).val() || '');
                if (tid !== '' && teacherFees[tid] !== undefined) {
                    $fee.val(teacherFees[tid]);
                }
            });
        }

        if ($fee.val() === '') {
            var initTid = String($teacher.val() || '');
            if (initTid !== '' && teacherFees[initTid] !== undefined) {
                $fee.val(teacherFees[initTid]);
            }
        }

        $teacher.on('change', updateFeeDefaultHint);
        updateFeeDefaultHint();

        $('#fld-fee-default-link').on('click', function (event) {
            event.preventDefault();
            var tid = String($teacher.val() || '');
            if (tid !== '' && teacherFees[tid] !== undefined) {
                $fee.val(teacherFees[tid]);
            }
        });

        // ---------- 5. Logic chuyển đổi Loại lớp: 1 kèm 1 vs Đông người ----------
        var $type = $('#fld-class-type');
        var $singleWrap = $('#wrapper-student-single');
        var $multiWrap = $('#wrapper-student-multi');
        var $singleSelect = $('#fld-student-single');
        var $multiSelect = $('#fld-student-multi');
        var $settingNote = $('#lms-setting-note');
        var $hwCheckInput = $('#fld-hw-check');

        function onClassTypeChange() {
            var isGroup = ($type.val() === 'group');

            if (isGroup) {
                // Lớp đông người
                $singleWrap.hide();
                $singleSelect.prop('disabled', true).prop('required', false);
                $multiWrap.show();
                $multiSelect.prop('disabled', false).prop('required', true);

                if ($.fn.select2 && !$multiSelect.data('select2')) {
                    $multiSelect.select2({
                        theme: 'bootstrap-5',
                        width: '100%',
                        placeholder: '— Chọn học viên —'
                    });
                }

                // Đồng bộ từ single sang multi nếu multi chưa có giá trị
                var singleVal = $singleSelect.val();
                var curMulti = $multiSelect.val() || [];
                if (singleVal && curMulti.length === 0) {
                    $multiSelect.val([singleVal]).trigger('change.select2');
                }

                $settingNote.text('mặc định cho lớp đông người:');
                if ($hwCheckInput.val() === '0' || $hwCheckInput.val() === '') {
                    $hwCheckInput.val('24');
                }
            } else {
                // Lớp 1 kèm 1
                $multiWrap.hide();
                $multiSelect.prop('disabled', true).prop('required', false);
                $singleWrap.show();
                $singleSelect.prop('disabled', false).prop('required', true);

                // Đồng bộ từ multi sang single nếu single chưa có
                var multiVals = $multiSelect.val() || [];
                if (multiVals.length > 0 && !$singleSelect.val()) {
                    $singleSelect.val(multiVals[0]).trigger('change');
                }

                $settingNote.text('mặc định cho lớp 1-1:');
                if ($hwCheckInput.val() === '24') {
                    $hwCheckInput.val('0');
                }
            }
        }

        $type.on('change', onClassTypeChange);
        onClassTypeChange();

        // Đồng bộ giá trị giữa 2 select khi người dùng chọn
        $singleSelect.on('change', function () {
            var val = $(this).val();
            $multiSelect.val(val ? [val] : []).trigger('change.select2');
        });

        })(window.jQuery);
    });
</script>
