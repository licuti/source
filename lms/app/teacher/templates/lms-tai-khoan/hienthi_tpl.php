<?php
/**
 * Teacher — Tài khoản giáo viên (mockup teacher-ver2/Chi tiết GV.png).
 *
 * Data do source chuẩn bị: $row (hồ sơ GV), $subjectOptions, $errors, $alias.
 */

if (!function_exists('lmtk_avatar_url')) {
    /**
     * Chuẩn hoá đường dẫn avatar (uploads / media/images / URL tuyệt đối).
     *
     * @param string $raw
     * @return string
     */
    function lmtk_avatar_url($raw)
    {
        $raw = trim((string) $raw);
        if ($raw === '') {
            return '';
        }
        if (strpos($raw, 'http://') === 0 || strpos($raw, 'https://') === 0) {
            return $raw;
        }
        if (defined('_LMS_ROOT') && file_exists(_LMS_ROOT . '/media/images/' . $raw)) {
            return '/media/images/' . $raw;
        }
        if (defined('_LMS_ROOT') && file_exists(_LMS_ROOT . '/' . $raw)) {
            return '/' . ltrim($raw, '/');
        }
        return lms_upload_url(str_replace('uploads/', '', $raw));
    }
}

$avatarSrc = lmtk_avatar_url(isset($row['avatar']) ? $row['avatar'] : '');
$fullName = isset($row['full_name']) && $row['full_name'] !== '' ? (string) $row['full_name'] : (string) $row['username'];
$teachableSubjects = isset($row['teachable_subjects']) && is_array($row['teachable_subjects'])
    ? $row['teachable_subjects'] : array();
$teachableDistricts = isset($row['teachable_districts']) && is_array($row['teachable_districts'])
    ? $row['teachable_districts'] : array();
$districtOptions = array_combine(\Lms\UserService::HCMC_DISTRICTS, \Lms\UserService::HCMC_DISTRICTS);
?>

<form method="post" action="<?= e(lms_url($alias)) ?>" enctype="multipart/form-data" class="lms-ta-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

    <?php if (!empty($errors['system']) || !empty($errors['avatar_file'])): ?>
        <div class="alert alert-danger py-2">
            <?= e(!empty($errors['system']) ? $errors['system'] : $errors['avatar_file']) ?>
        </div>
    <?php endif; ?>

    <!-- ===================== Hồ sơ tóm tắt ===================== -->
    <div class="lms-card mb-3">
        <div class="lms-ta-profile-head">
            <div class="lms-ta-avatar">
                <?php if ($avatarSrc !== ''): ?>
                    <img src="<?= e($avatarSrc) ?>" alt="<?= e($fullName) ?>">
                <?php else: ?>
                    <span class="lms-ta-avatar-fallback"><?= e(lms_avatar_text($fullName)) ?></span>
                <?php endif; ?>
            </div>

            <div class="lms-ta-profile-info">
                <div class="lms-ta-name"><?= e($fullName) ?></div>
                <div class="lms-ta-contact"><i class="fa-solid fa-user-tie"></i> Giáo viên</div>
                <div class="lms-ta-contact"><i class="fa-regular fa-envelope"></i> <?= e((string) $row['email']) ?></div>
                <div class="lms-ta-contact"><i class="fa-solid fa-phone"></i> <?= e((string) $row['phone']) ?></div>
            </div>

            <label class="lms-ta-photo-btn" for="lms-ta-avatar-file">
                <i class="fa-solid fa-camera"></i> Đổi Ảnh
            </label>
            <input type="file" id="lms-ta-avatar-file" name="avatar_file" class="d-none"
                   accept="image/jpeg,image/png,image/webp">
        </div>
    </div>

    <!-- ===================== Thông tin cá nhân ===================== -->
    <div class="lms-card mb-3">
        <div class="lms-card-body">
            <div class="lms-ta-section-title">
                <span class="lms-ta-section-icon"><i class="fa-solid fa-user"></i></span>
                Thông tin cá nhân
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <?php lms_component('form/input', array(
                        'name'        => 'full_name',
                        'label'       => 'Họ tên',
                        'placeholder' => 'VD: Thái Phụng',
                        'required'    => true,
                    )); ?>
                </div>
                <div class="col-md-6">
                    <?php lms_component('form/input', array(
                        'name'        => 'phone',
                        'label'       => 'Số điện thoại',
                        'type'        => 'tel',
                        'placeholder' => 'VD: 0988098097',
                        'required'    => true,
                    )); ?>
                </div>
                <div class="col-md-6">
                    <?php lms_component('form/input', array(
                        'name'        => 'email',
                        'label'       => 'Email',
                        'type'        => 'email',
                        'placeholder' => 'VD: haphuong@gmail.com',
                        'required'    => true,
                    )); ?>
                </div>
                <div class="col-md-6">
                    <?php lms_component('form/date_picker', array(
                        'name'        => 'dob',
                        'label'       => 'Ngày sinh',
                        'placeholder' => '04/12/2003',
                    )); ?>
                </div>
                <div class="col-md-6">
                    <?php lms_component('form/select', array(
                        'name'        => 'gender',
                        'label'       => 'Giới tính',
                        'enum'        => 'genders',
                        'empty_label' => 'Chọn giới tính',
                    )); ?>
                </div>
                <div class="col-md-6">
                    <?php lms_component('form/input', array(
                        'name'        => 'cccd',
                        'label'       => 'CCCD',
                        'placeholder' => 'VD: 0988098097',
                    )); ?>
                </div>
                <div class="col-md-6">
                    <?php lms_component('form/date_picker', array(
                        'name'        => 'issue_date',
                        'label'       => 'Ngày cấp',
                        'placeholder' => '04/12/2003',
                    )); ?>
                </div>
                <div class="col-md-6">
                    <?php lms_component('form/input', array(
                        'name'        => 'issue_place',
                        'label'       => 'Nơi cấp',
                        'placeholder' => 'VD: Bộ Công an',
                    )); ?>
                </div>
                <div class="col-md-6">
                    <?php lms_component('form/input', array(
                        'name'        => 'current_address',
                        'label'       => 'Địa chỉ hiện tại',
                        'placeholder' => 'VD: 181 Cao Thắng, Phường Hoà Hưng, TP.HCM',
                    )); ?>
                </div>
                <div class="col-md-6">
                    <?php lms_component('form/multi_select_tags', array(
                        'name'        => 'teachable_districts[]',
                        'label'       => 'Quận/Phường',
                        'selected'    => $teachableDistricts,
                        'options'     => $districtOptions,
                        'placeholder' => 'VD: Quận 1, Quận 2, Quận 3, ...',
                    )); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== Thông tin giảng dạy ===================== -->
    <div class="lms-card mb-3">
        <div class="lms-card-body">
            <div class="lms-ta-section-title">
                <span class="lms-ta-section-icon lms-ta-section-icon-warm"><i class="fa-solid fa-graduation-cap"></i></span>
                Thông tin giảng dạy
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <?php lms_component('form/multi_select_tags', array(
                        'name'        => 'teachable_subjects[]',
                        'label'       => 'Chương trình dạy',
                        'selected'    => $teachableSubjects,
                        'options'     => $subjectOptions,
                        'placeholder' => 'Chọn chương trình dạy',
                        'hint'        => 'Chọn thêm nhiều môn: Toán TĐN, Toán THCS, Tiếng Anh, Tiếng Việt, Khác (được thêm).',
                    )); ?>
                </div>
                <div class="col-md-6">
                    <?php lms_component('form/input', array(
                        'name'        => 'max_classes',
                        'label'       => 'Số lớp tối đa có thể nhận',
                        'type'        => 'number',
                        'min'         => 0,
                        'max'         => 50,
                        'placeholder' => 'VD: 2',
                    )); ?>
                </div>
                <div class="col-md-6">
                    <?php lms_component('form/select', array(
                        'name'    => 'can_teach_online',
                        'label'   => 'Có thể dạy online',
                        'options' => array('1' => 'Có', '0' => 'Không'),
                    )); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================== Thông tin thanh toán ===================== -->
    <div class="lms-card mb-3">
        <div class="lms-card-body">
            <div class="lms-ta-section-title">
                <span class="lms-ta-section-icon lms-ta-section-icon-warm"><i class="fa-solid fa-building-columns"></i></span>
                Thông tin thanh toán
            </div>
            <p class="lms-ta-note">Thông tin tài khoản ngân hàng để nhận thanh toán từ Master Study.</p>

            <div class="row g-3">
                <div class="col-md-6">
                    <?php lms_component('form/input', array(
                        'name'        => 'bank_name',
                        'label'       => 'Tên Ngân Hàng',
                        'placeholder' => 'VD: TMCP Công Thương Việt Nam - Viettinbank',
                    )); ?>
                </div>
                <div class="col-md-6">
                    <?php lms_component('form/input', array(
                        'name'        => 'bank_account',
                        'label'       => 'Số tài khoản',
                        'placeholder' => 'VD: 123xxxxxxxxx',
                    )); ?>
                </div>
                <div class="col-md-6">
                    <?php lms_component('form/input', array(
                        'name'        => 'bank_owner',
                        'label'       => 'Chủ tài khoản',
                        'placeholder' => 'VD: NGO THAI PHUNG',
                    )); ?>
                </div>
            </div>
        </div>
    </div>

    <div class="lms-ta-actions">
        <a class="btn btn-outline-secondary" href="<?= e(lms_url($alias)) ?>">Hủy</a>
        <button type="submit" class="btn btn-lms">Lưu thay đổi</button>
    </div>
</form>

<script>
(function () {
    var input = document.getElementById('lms-ta-avatar-file');
    if (!input) {
        return;
    }
    input.addEventListener('change', function () {
        var file = this.files && this.files[0];
        if (!file) {
            return;
        }
        var img = document.querySelector('.lms-ta-avatar img');
        var fallback = document.querySelector('.lms-ta-avatar-fallback');
        var reader = new FileReader();
        reader.onload = function (e) {
            if (!img) {
                img = document.createElement('img');
                var wrap = document.querySelector('.lms-ta-avatar');
                if (wrap) {
                    wrap.appendChild(img);
                }
            }
            if (fallback) {
                fallback.style.display = 'none';
            }
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);
    });
})();
</script>
