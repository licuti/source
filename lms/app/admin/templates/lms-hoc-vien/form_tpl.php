<?php
/**
 * Admin LMS — Quản lý học viên: view form tạo/sửa (ADM-01).
 * Data do source chuẩn bị: $row, $errors, $isEditMode, $formId, $alias.
 */
$userStatuses = \Lms\UserService::USER_STATUSES;
$studentStatuses = \Lms\UserService::STUDENT_STATUSES;
$learningTypes = \Lms\UserService::LEARNING_TYPES;
$districts = \Lms\UserService::HCMC_DISTRICTS;
$weekdays = \Lms\UserService::WEEKDAYS;
$slots = isset($row['free_time_slots']) && is_array($row['free_time_slots']) ? $row['free_time_slots'] : array();

$formUrl = $isEditMode
    ? lms_url($alias, 'sua', array('id' => $formId))
    : lms_url($alias, 'them');
?>

<form method="post" action="<?= e($formUrl) ?>" class="lms-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="lms-card">
                <div class="lms-card-header"><i class="fa-solid fa-key me-1"></i> Tài khoản đăng nhập</div>
                <div class="lms-card-body">
                    <div class="mb-3">
                        <label class="lms-form-label" for="fld-username">Tên đăng nhập <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="fld-username" name="username"
                               value="<?= e(lms_form_old($_POST, $row, 'username')) ?>" required>
                        <?= lms_field_error($errors, 'username') ?>
                    </div>
                    <div class="mb-3">
                        <label class="lms-form-label" for="fld-password">
                            Mật khẩu <?= $isEditMode ? '<span class="lms-text-muted-sm">(để trống để giữ nguyên)</span>' : '<span class="text-danger">*</span>' ?>
                        </label>
                        <input type="text" class="form-control" id="fld-password" name="password"
                               value="<?= e(lms_form_old($_POST, $row, 'password')) ?>" autocomplete="off">
                        <?php if (!$isEditMode): ?>
                            <div class="lms-text-muted-sm mt-1">Để trống nếu muốn hệ thống tự sinh mật khẩu (10 ký tự).</div>
                        <?php endif; ?>
                        <?= lms_field_error($errors, 'password') ?>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-email">Email</label>
                            <input type="email" class="form-control" id="fld-email" name="email"
                                   value="<?= e(lms_form_old($_POST, $row, 'email')) ?>">
                            <?= lms_field_error($errors, 'email') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-phone">Số điện thoại</label>
                            <input type="tel" class="form-control" id="fld-phone" name="phone"
                                   value="<?= e(lms_form_old($_POST, $row, 'phone')) ?>" placeholder="0901xxxxxx">
                            <?= lms_field_error($errors, 'phone') ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="lms-form-label" for="fld-user-status">Trạng thái tài khoản</label>
                        <select class="form-select" id="fld-user-status" name="user_status">
                            <?php foreach ($userStatuses as $k => $label): ?>
                                <option value="<?= e($k) ?>"<?= lms_selected(
                                    lms_form_old($_POST, $row, 'user_status', 'active'),
                                    $k
                                ) ?>><?= e($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="lms-card">
                <div class="lms-card-header"><i class="fa-solid fa-id-card me-1"></i> Hồ sơ học viên</div>
                <div class="lms-card-body">
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-code">Mã học viên</label>
                            <?php if ($isEditMode): ?>
                                <input type="text" class="form-control" id="fld-code"
                                       value="<?= e((string) ($row['student_code'] ?? '')) ?>" readonly>
                            <?php else: ?>
                                <input type="text" class="form-control" id="fld-code" name="student_code"
                                       value="<?= e(lms_form_old($_POST, $row, 'student_code')) ?>"
                                       placeholder="Tự động sinh nếu để trống">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-fullname">Họ tên học viên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fld-fullname" name="full_name"
                                   value="<?= e(lms_form_old($_POST, $row, 'full_name')) ?>" required>
                            <?= lms_field_error($errors, 'full_name') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-parent-name">Họ tên phụ huynh</label>
                            <input type="text" class="form-control" id="fld-parent-name" name="parent_name"
                                   value="<?= e(lms_form_old($_POST, $row, 'parent_name')) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-parent-phone">Số điện thoại phụ huynh</label>
                            <input type="tel" class="form-control" id="fld-parent-phone" name="parent_phone"
                                   value="<?= e(lms_form_old($_POST, $row, 'parent_phone')) ?>" placeholder="0901xxxxxx">
                            <?= lms_field_error($errors, 'parent_phone') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-grade">Lớp</label>
                            <input type="text" class="form-control" id="fld-grade" name="grade"
                                   value="<?= e(lms_form_old($_POST, $row, 'grade')) ?>" placeholder="Lớp 5">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-school">Trường học</label>
                            <input type="text" class="form-control" id="fld-school" name="school_name"
                                   value="<?= e(lms_form_old($_POST, $row, 'school_name')) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-learning-type">Hình thức học</label>
                            <select class="form-select" id="fld-learning-type" name="learning_type">
                                <?php foreach ($learningTypes as $k => $label): ?>
                                    <option value="<?= e($k) ?>"<?= lms_selected(
                                        lms_form_old($_POST, $row, 'learning_type', 'offline'),
                                        $k
                                    ) ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= lms_field_error($errors, 'learning_type') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-status">Trạng thái học viên</label>
                            <select class="form-select" id="fld-status" name="status">
                                <?php foreach ($studentStatuses as $k => $label): ?>
                                    <option value="<?= e($k) ?>"<?= lms_selected(
                                        lms_form_old($_POST, $row, 'status', 'pending_arrange'),
                                        $k
                                    ) ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= lms_field_error($errors, 'status') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-district">Quận</label>
                            <select class="form-select" id="fld-district" name="district">
                                <option value="">— Chọn quận —</option>
                                <?php foreach ($districts as $district): ?>
                                    <option value="<?= e($district) ?>"<?= lms_selected(
                                        lms_form_old($_POST, $row, 'district'),
                                        $district
                                    ) ?>><?= e($district) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= lms_field_error($errors, 'district') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-ward">Phường</label>
                            <input type="text" class="form-control" id="fld-ward" name="ward"
                                   value="<?= e(lms_form_old($_POST, $row, 'ward')) ?>">
                        </div>
                        <div class="col-12 mb-3">
                            <label class="lms-form-label" for="fld-address">Địa chỉ hiện tại</label>
                            <input type="text" class="form-control" id="fld-address" name="current_address"
                                   value="<?= e(lms_form_old($_POST, $row, 'current_address')) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-priority">Số ưu tiên</label>
                            <input type="number" min="0" max="999" class="form-control" id="fld-priority"
                                   name="priority_number"
                                   value="<?= e(lms_form_old($_POST, $row, 'priority_number', '0')) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="lms-card">
                <div class="lms-card-header"><i class="fa-solid fa-clock me-1"></i> Khung giờ rảnh</div>
                <div class="lms-card-body">
                    <div id="lms-slots">
                        <?php if ($slots !== array()): ?>
                            <?php foreach ($slots as $slot): ?>
                                <div class="lms-slots-row">
                                    <select class="form-select" name="slot_day[]">
                                        <option value="">— Thứ —</option>
                                        <?php foreach ($weekdays as $k => $label): ?>
                                            <option value="<?= e((string) $k) ?>"<?= lms_selected(
                                                isset($slot['day']) ? $slot['day'] : '',
                                                $k
                                            ) ?>><?= e($label) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="time" class="form-control" name="slot_start[]"
                                           value="<?= e(isset($slot['start']) ? (string) $slot['start'] : '') ?>">
                                    <input type="time" class="form-control" name="slot_end[]"
                                           value="<?= e(isset($slot['end']) ? (string) $slot['end'] : '') ?>">
                                    <button type="button" class="lms-slot-remove" aria-label="Xoá khung giờ">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="btn btn-outline-lms btn-sm" id="lms-slot-add">
                        <i class="fa-solid fa-plus me-1"></i> Thêm khung giờ
                    </button>
                    <?= lms_field_error($errors, 'free_time_slots') ?>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="lms-card">
                <div class="lms-card-header"><i class="fa-solid fa-note-sticky me-1"></i> Ghi chú</div>
                <div class="lms-card-body">
                    <textarea class="form-control" name="note" rows="3"
                              placeholder="VD: phụ huynh dễ tính, cần GV nam..."><?= e(lms_form_old($_POST, $row, 'note')) ?></textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="lms-form-actions">
        <button type="submit" class="btn btn-lms px-4">
            <i class="fa-solid fa-floppy-disk me-1"></i> <?= $isEditMode ? 'Lưu thay đổi' : 'Tạo học viên' ?>
        </button>
        <a href="<?= e(lms_url($alias)) ?>" class="btn btn-outline-secondary">Huỷ</a>
    </div>
</form>

<script>
    (function ($) {
        'use strict';
        var weekdayOptions = <?= json_encode($weekdays) ?>;
        var rows = $('#lms-slots');

        function newRow(day, start, end) {
            var opts = '<option value="">— Thứ —</option>';
            $.each(weekdayOptions, function (k, label) {
                opts += '<option value="' + k + '"' + (String(k) === String(day) ? ' selected' : '') + '>'
                    + label + '</option>';
            });
            var $row = $('<div class="lms-slots-row">' +
                '<select class="form-select" name="slot_day[]">' + opts + '</select>' +
                '<input type="time" class="form-control" name="slot_start[]" value="' + (start || '') + '">' +
                '<input type="time" class="form-control" name="slot_end[]" value="' + (end || '') + '">' +
                '<button type="button" class="lms-slot-remove" aria-label="Xoá khung giờ">' +
                '<i class="fa-solid fa-xmark"></i></button>' +
                '</div>');
            rows.append($row);
        }

        $('#lms-slot-add').on('click', function () {
            newRow('', '', '');
        });

        rows.on('click', '.lms-slot-remove', function () {
            $(this).closest('.lms-slots-row').remove();
        });
    })(jQuery);
</script>
