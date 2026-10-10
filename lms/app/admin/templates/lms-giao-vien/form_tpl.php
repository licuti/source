<?php
/**
 * Admin LMS — Quản lý giáo viên: view form tạo/sửa (ADM-02).
 * Data do source chuẩn bị: $row, $errors, $isEditMode, $formId, $alias,
 * $subjectOptions (id => array('name','program')), $districts.
 */
$userStatuses   = \Lms\UserService::USER_STATUSES;
$teacherStatuses = \Lms\UserService::TEACHER_STATUSES;
$ranks          = \Lms\UserService::TEACHER_RANKS;
$genders        = \Lms\UserService::GENDERS;
$selDistricts   = isset($row['teachable_districts']) && is_array($row['teachable_districts'])
    ? $row['teachable_districts'] : array();
$selSubjects    = isset($row['teachable_subjects']) && is_array($row['teachable_subjects'])
    ? $row['teachable_subjects'] : array();

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
                <div class="lms-card-header"><i class="fa-solid fa-id-card me-1"></i> Hồ sơ giáo viên</div>
                <div class="lms-card-body">
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-code">Mã giáo viên</label>
                            <?php if ($isEditMode): ?>
                                <input type="text" class="form-control" id="fld-code"
                                       value="<?= e((string) ($row['teacher_code'] ?? '')) ?>" readonly>
                            <?php else: ?>
                                <input type="text" class="form-control" id="fld-code" name="teacher_code"
                                       value="<?= e(lms_form_old($_POST, $row, 'teacher_code')) ?>"
                                       placeholder="Tự động sinh nếu để trống">
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-fullname">Họ tên giáo viên <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="fld-fullname" name="full_name"
                                   value="<?= e(lms_form_old($_POST, $row, 'full_name')) ?>" required>
                            <?= lms_field_error($errors, 'full_name') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-gender">Giới tính</label>
                            <select class="form-select" id="fld-gender" name="gender">
                                <option value="">— Chọn —</option>
                                <?php foreach ($genders as $k => $label): ?>
                                    <option value="<?= e($k) ?>"<?= lms_selected(
                                        lms_form_old($_POST, $row, 'gender'),
                                        $k
                                    ) ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-dob">Ngày sinh</label>
                            <input type="date" class="form-control" id="fld-dob" name="dob"
                                   value="<?= e(lms_form_old($_POST, $row, 'dob')) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-cccd">CCCD</label>
                            <input type="text" class="form-control" id="fld-cccd" name="cccd"
                                   value="<?= e(lms_form_old($_POST, $row, 'cccd')) ?>" placeholder="12 chữ số">
                            <?= lms_field_error($errors, 'cccd') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-issue-date">Ngày cấp CCCD</label>
                            <input type="date" class="form-control" id="fld-issue-date" name="issue_date"
                                   value="<?= e(lms_form_old($_POST, $row, 'issue_date')) ?>">
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="lms-form-label" for="fld-issue-place">Nơi cấp CCCD</label>
                            <input type="text" class="form-control" id="fld-issue-place" name="issue_place"
                                   value="<?= e(lms_form_old($_POST, $row, 'issue_place')) ?>"
                                   placeholder="VD: Bộ Công An">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-tax">Mã số thuế</label>
                            <input type="text" class="form-control" id="fld-tax" name="tax_code"
                                   value="<?= e(lms_form_old($_POST, $row, 'tax_code')) ?>" placeholder="10 chữ số">
                            <?= lms_field_error($errors, 'tax_code') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-max-classes">Số lớp tối đa</label>
                            <input type="number" min="0" max="50" class="form-control" id="fld-max-classes"
                                   name="max_classes"
                                   value="<?= e(lms_form_old($_POST, $row, 'max_classes', '1')) ?>">
                            <?= lms_field_error($errors, 'max_classes') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="lms-card">
                <div class="lms-card-header"><i class="fa-solid fa-briefcase me-1"></i> Dạy &amp; thuế</div>
                <div class="lms-card-body">
                    <div class="mb-3">
                        <label class="lms-form-label">Môn dạy được <span class="text-danger">*</span></label>
                        <select class="form-select" name="teachable_subjects[]" multiple size="6" required>
                            <?php foreach ($subjectOptions as $sid => $s): ?>
                                <option value="<?= e((string) $sid) ?>"<?= lms_in_selected($sid, $selSubjects) ?>>
                                    <?= e($s['name']) ?> — <?= e($s['program']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="lms-text-muted-sm mt-1">Giữ Ctrl/Cmd để chọn nhiều môn.</div>
                        <?= lms_field_error($errors, 'teachable_subjects') ?>
                    </div>
                    <div class="mb-3">
                        <label class="lms-form-label">Quận có thể dạy</label>
                        <select class="form-select" name="teachable_districts[]" multiple size="6">
                            <?php foreach ($districts as $district): ?>
                                <option value="<?= e($district) ?>"<?= lms_in_selected($district, $selDistricts) ?>>
                                    <?= e($district) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?= lms_field_error($errors, 'teachable_districts') ?>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-rank">Hạng giáo viên</label>
                            <select class="form-select" id="fld-rank" name="rank">
                                <?php foreach ($ranks as $k => $label): ?>
                                    <option value="<?= e($k) ?>"<?= lms_selected(
                                        lms_form_old($_POST, $row, 'rank', 'bronze'),
                                        $k
                                    ) ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= lms_field_error($errors, 'rank') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-status">Trạng thái</label>
                            <select class="form-select" id="fld-status" name="status">
                                <?php foreach ($teacherStatuses as $k => $label): ?>
                                    <option value="<?= e($k) ?>"<?= lms_selected(
                                        lms_form_old($_POST, $row, 'status', 'active'),
                                        $k
                                    ) ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?= lms_field_error($errors, 'status') ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-fee">Mức phí dịch vụ (%) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" min="0" max="100" step="0.5" class="form-control" id="fld-fee"
                                       name="service_fee_percentage"
                                       value="<?= e(lms_form_old($_POST, $row, 'service_fee_percentage', '48')) ?>">
                                <span class="input-group-text">%</span>
                            </div>
                            <?= lms_field_error($errors, 'service_fee_percentage') ?>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end pb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="fld-online"
                                       name="can_teach_online" value="1"<?= lms_checked(
                                           lms_form_old($_POST, $row, 'can_teach_online', '1')
                                       ) ?>>
                                <label class="form-check-label" for="fld-online">Có thể dạy online</label>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end pb-3">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" id="fld-commit08"
                                       name="is_commit_08" value="1"<?= lms_checked(lms_form_old($_POST, $row, 'is_commit_08')) ?>>
                                <label class="form-check-label" for="fld-commit08">Cam kết 08 (thuế TNCN 0%)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="lms-card">
                <div class="lms-card-header"><i class="fa-solid fa-building-columns me-1"></i> Thông tin ngân hàng</div>
                <div class="lms-card-body">
                    <div class="mb-3">
                        <label class="lms-form-label" for="fld-bank-name">Tên ngân hàng</label>
                        <input type="text" class="form-control" id="fld-bank-name" name="bank_name"
                               value="<?= e(lms_form_old($_POST, $row, 'bank_name')) ?>"
                               placeholder="VD: Vietcombank">
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-bank-account">Số tài khoản</label>
                            <input type="text" class="form-control" id="fld-bank-account" name="bank_account"
                                   value="<?= e(lms_form_old($_POST, $row, 'bank_account')) ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="lms-form-label" for="fld-bank-owner">Chủ tài khoản</label>
                            <input type="text" class="form-control" id="fld-bank-owner" name="bank_owner"
                                   value="<?= e(lms_form_old($_POST, $row, 'bank_owner')) ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="lms-form-actions">
        <button type="submit" class="btn btn-lms px-4">
            <i class="fa-solid fa-floppy-disk me-1"></i> <?= $isEditMode ? 'Lưu thay đổi' : 'Tạo giáo viên' ?>
        </button>
        <a href="<?= e(lms_url($alias)) ?>" class="btn btn-outline-secondary">Huỷ</a>
    </div>
</form>
