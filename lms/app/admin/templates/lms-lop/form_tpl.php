<?php
/**
 * Admin LMS — Quản lý lớp học: view form tạo/sửa (ADM-03).
 * Data do source chuẩn bị: $row, $errors, $isEditMode, $formId, $alias,
 * $programs, $subjects, $teachers, $students.
 */
$classTypes = \Lms\ClassService::CLASS_TYPES;
$weekdays   = \Lms\UserService::WEEKDAYS;

$schedules  = isset($row['schedules']) && is_array($row['schedules']) ? $row['schedules'] : array();
$selStudents = lms_form_old_array($_POST, $row, 'student_ids');

// Map teacher_id → % phí mặc định (JS điền khi đổi lựa chọn)
$teacherFee = array();
foreach ($teachers as $t) {
    $teacherFee[(int) $t['user_id']] = isset($t['service_fee_percentage'])
        ? (float) $t['service_fee_percentage'] : 48.0;
}

$formUrl = $isEditMode
    ? lms_url($alias, 'sua', array('id' => $formId))
    : lms_url($alias, 'them');
?>

<form method="post" action="<?= e($formUrl) ?>" class="lms-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="lms-card">
                <div class="lms-card-header"><i class="fa-solid fa-school me-1"></i> Thông tin lớp</div>
                <div class="lms-card-body">
                    <?php if ($isEditMode): ?>
                        <div class="mb-3">
                            <label class="lms-form-label" for="fld-code">Mã lớp</label>
                            <input type="text" class="form-control" id="fld-code"
                                   value="<?= e((string) ($row['class_code'] ?? '')) ?>" readonly>
                        </div>
                    <?php endif; ?>
                    <div class="mb-3">
                        <label class="lms-form-label" for="fld-name">Tên lớp</label>
                        <input type="text" class="form-control" id="fld-name" name="name"
                               value="<?= e(lms_form_old($_POST, $row, 'name')) ?>"
                               placeholder="VD: Toán 1-1 - Nguyễn Văn A">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-program">Chương trình <span class="text-danger">*</span></label>
                            <select class="form-select" id="fld-program" name="program_id" required>
                                <option value="">— Chọn chương trình —</option>
                                <?php foreach ($programs as $p): ?>
                                    <option value="<?= e((string) (int) $p['id']) ?>"<?= lms_selected(
                                        lms_form_old($_POST, $row, 'program_id'),
                                        (int) $p['id']
                                    ) ?>><?= e((string) ($p['name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= lms_field_error($errors, 'program_id') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-subject">Chương trình học <span class="text-danger">*</span></label>
                            <select class="form-select" id="fld-subject" name="subject_id" required>
                                <option value="">— Chọn môn học —</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= e((string) (int) $s['id']) ?>"
                                            data-program-id="<?= e((string) (int) $s['program_id']) ?>"<?= lms_selected(
                                                lms_form_old($_POST, $row, 'subject_id'),
                                                (int) $s['id']
                                            ) ?>><?= e((string) ($s['name'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= lms_field_error($errors, 'subject_id') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-class-type">Loại lớp <span class="text-danger">*</span></label>
                            <select class="form-select" id="fld-class-type" name="class_type">
                                <?php foreach ($classTypes as $k => $label): ?>
                                    <option value="<?= e($k) ?>"<?= lms_selected(
                                        lms_form_old($_POST, $row, 'class_type', 'one_on_one'),
                                        $k
                                    ) ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= lms_field_error($errors, 'class_type') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-teacher">Giáo viên <span class="text-danger">*</span></label>
                            <select class="form-select" id="fld-teacher" name="teacher_id" required>
                                <option value="">— Chọn giáo viên —</option>
                                <?php foreach ($teachers as $t): ?>
                                    <option value="<?= e((string) (int) $t['user_id']) ?>"<?= lms_selected(
                                        lms_form_old($_POST, $row, 'teacher_id'),
                                        (int) $t['user_id']
                                    ) ?>>
                                        <?= e((string) ($t['teacher_code'] ?? '')) ?> — <?= e((string) ($t['full_name'] ?? '')) ?>
                                        (<?= e((string) (int) ($t['active_classes'] ?? 0)) ?>/<?= e((string) (int) ($t['max_classes'] ?? 0)) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?= lms_field_error($errors, 'teacher_id') ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="lms-form-label" for="fld-students">Học viên <span class="text-danger">*</span></label>
                        <select class="form-select" id="fld-students" name="student_ids[]" multiple size="6" required>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= e((string) (int) $s['user_id']) ?>"<?= lms_in_selected((int) $s['user_id'], $selStudents) ?>>
                                    <?= e((string) ($s['student_code'] ?? '')) ?> — <?= e((string) ($s['full_name'] ?? '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="lms-text-muted-sm mt-1" id="fld-students-hint">
                            Giữ Ctrl/Cmd để chọn nhiều học viên.
                        </div>
                        <?= lms_field_error($errors, 'student_ids') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="lms-card">
                <div class="lms-card-header"><i class="fa-solid fa-money-bill-wave me-1"></i> Học phí &amp; cấu hình</div>
                <div class="lms-card-body">
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-price">Học phí 1 buổi (VNĐ) <span class="text-danger">*</span></label>
                            <input type="number" min="0" step="1000" class="form-control" id="fld-price"
                                   name="price_per_session"
                                   value="<?= e(lms_form_old($_POST, $row, 'price_per_session', '0')) ?>">
                            <?= lms_field_error($errors, 'price_per_session') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-fee">Phần trăm GV nhận (%)</label>
                            <div class="input-group">
                                <input type="number" min="0" max="100" step="0.5" class="form-control" id="fld-fee"
                                       name="teacher_percentage"
                                       value="<?= e(lms_form_old($_POST, $row, 'teacher_percentage')) ?>">
                                <span class="input-group-text">%</span>
                            </div>
                            <div class="lms-text-muted-sm mt-1">Để trống để kế thừa mức phí mặc định của giáo viên.</div>
                            <?= lms_field_error($errors, 'teacher_percentage') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-start-date">Áp dụng từ ngày</label>
                            <input type="date" class="form-control" id="fld-start-date" name="start_date"
                                   value="<?= e(lms_form_old($_POST, $row, 'start_date', date('Y-m-d'))) ?>">
                            <?= lms_field_error($errors, 'start_date') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-total">Số buổi khoá đầu</label>
                            <input type="number" min="0" max="999" class="form-control" id="fld-total"
                                   name="total_sessions"
                                   value="<?= e(lms_form_old($_POST, $row, 'total_sessions', '0')) ?>">
                            <?= lms_field_error($errors, 'total_sessions') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-hw-deadline">Hạn giao BTVN</label>
                            <input type="time" class="form-control" id="fld-hw-deadline" name="hw_deadline_time"
                                   value="<?= e(lms_form_old($_POST, $row, 'hw_deadline_time', '12:00')) ?>">
                            <?= lms_field_error($errors, 'hw_deadline_time') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-hw-check">Hạn check BTVN (giờ)</label>
                            <input type="number" min="0" max="720" class="form-control" id="fld-hw-check"
                                   name="hw_check_hours"
                                   value="<?= e(lms_form_old($_POST, $row, 'hw_check_hours', '24')) ?>">
                            <?= lms_field_error($errors, 'hw_check_hours') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="lms-card">
                <div class="lms-card-header"><i class="fa-solid fa-calendar-days me-1"></i> Lịch học cố định hàng tuần</div>
                <div class="lms-card-body">
                    <div id="lms-schedules">
                        <?php if ($schedules !== array()): ?>
                            <?php foreach ($schedules as $sch): ?>
                                <div class="lms-slots-row">
                                    <select class="form-select" name="sch_day[]">
                                        <option value="">— Thứ —</option>
                                        <?php foreach ($weekdays as $k => $label): ?>
                                            <option value="<?= e((string) $k) ?>"<?= lms_selected(
                                                isset($sch['day']) ? $sch['day'] : '',
                                                $k
                                            ) ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="time" class="form-control" name="sch_start[]"
                                           value="<?= e(isset($sch['start']) ? (string) $sch['start'] : '') ?>">
                                    <input type="time" class="form-control" name="sch_end[]"
                                           value="<?= e(isset($sch['end']) ? (string) $sch['end'] : '') ?>">
                                    <button type="button" class="lms-slot-remove" aria-label="Xoá lịch học">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-outline-lms btn-sm" id="lms-schedule-add">
                        <i class="fa-solid fa-plus me-1"></i> Thêm lịch học
                    </button>
                    <div class="lms-text-muted-sm mt-1">Buổi học cho 4 tuần tiếp theo được tự động sinh từ lịch này.</div>
                    <?= lms_field_error($errors, 'schedules') ?>
                </div>
            </div>
        </div>
    </div>

    <div class="lms-form-actions">
        <button type="submit" class="btn btn-lms px-4">
            <i class="fa-solid fa-floppy-disk me-1"></i> <?= $isEditMode ? 'Lưu thay đổi' : 'Tạo lớp học' ?>
        </button>
        <a href="<?= e(lms_url($alias)) ?>" class="btn btn-outline-secondary">Huỷ</a>
    </div>
</form>

<script>
    (function ($) {
        'use strict';
        var weekdayOptions = <?= json_encode($weekdays) ?>;
        var teacherFees = <?= json_encode($teacherFee) ?>;
        var rows = $('#lms-schedules');

        // ---------- Lịch học: thêm / xoá dòng ----------
        function newRow(day, start, end) {
            var opts = '<option value="">— Thứ —</option>';
            $.each(weekdayOptions, function (k, label) {
                opts += '<option value="' + k + '"' + (String(k) === String(day) ? ' selected' : '') + '>'
                    + label + '</option>';
            });
            var $row = $('<div class="lms-slots-row">' +
                '<select class="form-select" name="sch_day[]">' + opts + '</select>' +
                '<input type="time" class="form-control" name="sch_start[]" value="' + (start || '') + '">' +
                '<input type="time" class="form-control" name="sch_end[]" value="' + (end || '') + '">' +
                '<button type="button" class="lms-slot-remove" aria-label="Xoá lịch học">' +
                '<i class="fa-solid fa-xmark"></i></button>' +
                '</div>');
            rows.append($row);
        }

        $('#lms-schedule-add').on('click', function () {
            newRow('', '', '');
        });

        rows.on('click', '.lms-slot-remove', function () {
            $(this).closest('.lms-slots-row').remove();
        });

        // ---------- Lọc môn học theo chương trình ----------
        var $program = $('#fld-program');
        var $subject = $('#fld-subject');

        function filterSubjects() {
            var pid = String($program.val() || '');
            var $visible = $subject.find('option[data-program-id]').filter(function () {
                return pid === '' || $(this).attr('data-program-id') === pid;
            });
            $subject.find('option[data-program-id]').each(function () {
                $(this).toggle(pid === '' || $(this).attr('data-program-id') === pid);
            });
            var $selected = $subject.find('option:selected');
            if ($selected.length && $selected.attr('data-program-id') && $selected.attr('data-program-id') !== pid) {
                $subject.val($visible.first().attr('value') || '');
            }
        }

        $program.on('change', filterSubjects);
        filterSubjects();

        // ---------- % phí mặc định của GV ----------
        var $teacher = $('#fld-teacher');
        var $fee = $('#fld-fee');

        function syncTeacherFee() {
            var tid = String($teacher.val() || '');
            if (tid !== '' && teacherFees[tid] !== undefined && $fee.val() === '') {
                $fee.val(teacherFees[tid]);
            }
        }

        $teacher.on('change', function () {
            var tid = String($teacher.val() || '');
            if (tid !== '' && teacherFees[tid] !== undefined) {
                $fee.val(teacherFees[tid]);
            }
        });
        syncTeacherFee();

        // ---------- Giới hạn học viên theo loại lớp ----------
        var $type = $('#fld-class-type');
        var $students = $('#fld-students');
        var $hint = $('#fld-students-hint');

        function syncStudentLimit() {
            if ($type.val() === 'one_on_one') {
                var selected = $students.val() || [];
                if (selected.length > 1) {
                    $students.val([selected[0]]);
                }
                $hint.text('Lớp 1 kèm 1 chỉ được chọn 1 học viên.');
            } else {
                $hint.text('Giữ Ctrl/Cmd để chọn nhiều học viên.');
            }
        }

        $type.on('change', syncStudentLimit);
        $students.on('change', syncStudentLimit);
        syncStudentLimit();
    })(jQuery);
</script>
