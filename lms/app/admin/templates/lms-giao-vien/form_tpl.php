<?php
/**
 * Admin LMS — Quản lý tài khoản: Form Chỉnh sửa giáo viên / Thêm giáo viên.
 * Dẫn chứng mockup: Tai-lieu-phat-trien/admin/Quản lý tài khoản -Giáo Viên - Sửa .png
 *                    + Tai-lieu-phat-trien/admin-ver2/Tài khoản GV mới.png (thêm GV)
 * SRS: Tai-lieu-phat-trien/admin/Tài liệu admin.md (§6. Quản lý tài khoản & §20).
 *
 * Lưu ý mockup admin-ver2: có field "OSF" (giá trị "Hạng 1") nhưng hệ thống KHÔNG
 * có cột/enum nào cho OSF — đã chốt bỏ khỏi form, header vẫn hiển thị "Hạng: Xanh/Vàng/Đỏ".
 *
 * Đã chuẩn hoá theo Kiến trúc Form Component & LmsEnum tập trung.
 * PHP 7.3 compatible.
 */

$formAction = ($alias === 'lms-tai-khoan')
    ? ($isEditMode ? 'sua-giao-vien' : 'them-giao-vien')
    : ($isEditMode ? 'sua' : 'them');

$cancelUrl = ($alias === 'lms-tai-khoan')
    ? lms_url($alias, 'man', array('tab' => 'giao-vien'))
    : lms_url($alias);

$formUrl = $isEditMode
    ? lms_url($alias, $formAction, array('id' => $formId))
    : lms_url($alias, $formAction);

$selDistricts = isset($row['teachable_districts']) && is_array($row['teachable_districts'])
    ? $row['teachable_districts'] : array();

$selSubjects = isset($row['teachable_subjects']) && is_array($row['teachable_subjects'])
    ? $row['teachable_subjects'] : array();

// Xử lý link avatar
$avatarSrc = '';
if (!empty($row['avatar'])) {
    $rawAv = (string) $row['avatar'];
    if (strpos($rawAv, 'http://') === 0 || strpos($rawAv, 'https://') === 0) {
        $avatarSrc = $rawAv;
    } elseif (defined('_LMS_ROOT') && file_exists(_LMS_ROOT . '/media/images/' . $rawAv)) {
        $avatarSrc = '/media/images/' . $rawAv;
    } elseif (defined('_LMS_ROOT') && file_exists(_LMS_ROOT . '/' . $rawAv)) {
        $avatarSrc = '/' . ltrim($rawAv, '/');
    } elseif (function_exists('lms_upload_url')) {
        $avatarSrc = lms_upload_url(str_replace('uploads/', '', $rawAv));
    } else {
        $avatarSrc = '/' . ltrim($rawAv, '/');
    }
}

// Phân nhóm quận TP.HCM và các phường Thủ Đức cho component multi_select_tags
$districtGroups = array(
    'Các quận TP.HCM' => array(
        'Quận 1', 'Quận 3', 'Quận 4', 'Quận 5', 'Quận 6', 'Quận 7', 'Quận 8',
        'Quận 10', 'Quận 11', 'Quận 12', 'Bình Thạnh', 'Gò Vấp', 'Phú Nhuận',
        'Tân Bình', 'Tân Phú', 'Bình Tân', 'Bình Chánh', 'Cần Giờ', 'Củ Chi', 'Hóc Môn', 'Nhà Bè'
    ),
    'Khu vực TP. Thủ Đức (Tất cả phường)' => array(
        'P. An Khánh (Thủ Đức)', 'P. An Lợi Đông (Thủ Đức)', 'P. An Phú (Thủ Đức)',
        'P. Bình Chiểu (Thủ Đức)', 'P. Bình Thọ (Thủ Đức)', 'P. Hiệp Bình Chánh (Thủ Đức)',
        'P. Hiệp Bình Phước (Thủ Đức)', 'P. Hiệp Phú (Thủ Đức)', 'P. Linh Chiểu (Thủ Đức)',
        'P. Linh Đông (Thủ Đức)', 'P. Linh Tây (Thủ Đức)', 'P. Linh Trung (Thủ Đức)',
        'P. Linh Xuân (Thủ Đức)', 'P. Long Bình (Thủ Đức)', 'P. Long Phước (Thủ Đức)',
        'P. Long Thạnh Mỹ (Thủ Đức)', 'P. Long Trường (Thủ Đức)', 'P. Phú Hữu (Thủ Đức)',
        'P. Phước Bình (Thủ Đức)', 'P. Phước Long A (Thủ Đức)', 'P. Phước Long B (Thủ Đức)',
        'P. Tam Bình (Thủ Đức)', 'P. Tam Phú (Thủ Đức)', 'P. Tăng Nhơn Phú A (Thủ Đức)',
        'P. Tăng Nhơn Phú B (Thủ Đức)', 'P. Thạnh Mỹ Lợi (Thủ Đức)', 'P. Thảo Điền (Thủ Đức)',
        'P. Thủ Thiêm (Thủ Đức)', 'P. Trường Thạnh (Thủ Đức)', 'P. Trường Thọ (Thủ Đức)'
    ),
);

// Mảng môn học / chương trình dạy
$teachingProgramOptions = array();
if (isset($subjectOptions) && is_array($subjectOptions)) {
    foreach ($subjectOptions as $sid => $s) {
        $teachingProgramOptions[$sid] = $s['name'];
    }
}
$firstSubjectId = !empty($selSubjects) ? (string) $selSubjects[0] : '';

$monthTicks = isset($row['month_ticks']) ? (int) $row['month_ticks'] : 0;
$monthFee   = isset($row['month_fee']) ? (float) $row['month_fee'] : 0.0;
?>

<form method="post" action="<?= e($formUrl) ?>" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

    <div class="row">
        <div class="col-lg-8">
            <!-- 1. Họ tên giáo viên -->
        <?php lms_component('form/input', array(
            'name'        => 'full_name',
            'label'       => 'Họ tên giáo viên',
            'placeholder' => 'VD: Thái Phụng',
            'required'    => true,
        )); ?>

        <!-- 2. Username -->
        <?php lms_component('form/input', array(
            'name'        => 'username',
            'label'       => 'Username',
            'placeholder' => 'VD: TE125',
            'required'    => true,
        )); ?>

        <?php lms_component('form/input', array(
            'name'        => 'password',
            'label'       => 'Mật khẩu',
            'type'        => 'password',
            'value'       => '',
            'placeholder' => $isEditMode ? 'Để trống để giữ nguyên' : 'Để trống để hệ thống tự sinh',
            'hint'        => 'Mật khẩu tự sinh sẽ được yêu cầu đổi ở lần đăng nhập đầu tiên.',
        )); ?>

        <!-- 3. User code -->
        <?php lms_component('form/input', array(
            'name'        => 'teacher_code',
            'label'       => 'User code',
            'placeholder' => 'VD: TE125',
        )); ?>

        <!-- 4. Số điện thoại -->
        <?php lms_component('form/input', array(
            'name'        => 'phone',
            'label'       => 'Số điện thoại',
            'type'        => 'tel',
            'placeholder' => 'VD: 0707676299',
            'required'    => true,
        )); ?>

        <!-- 5. Email -->
        <?php lms_component('form/input', array(
            'name'        => 'email',
            'label'       => 'Email',
            'type'        => 'email',
            'placeholder' => 'VD: ngothaiphung412@gmail.com',
            'required'    => true,
        )); ?>

        <!-- 6. CCCD -->
        <?php lms_component('form/input', array(
            'name'        => 'cccd',
            'label'       => 'CCCD',
            'placeholder' => 'VD: 0795565656',
        )); ?>

        <!-- 7. Ngày cấp -->
        <?php lms_component('form/date_picker', array(
            'name'        => 'issue_date',
            'label'       => 'Ngày cấp',
            'placeholder' => '04/12/2003',
        )); ?>

        <!-- 8. Nơi cấp -->
        <?php lms_component('form/input', array(
            'name'        => 'issue_place',
            'label'       => 'Nơi cấp',
            'placeholder' => 'VD: Bộ Công An',
        )); ?>

        <!-- 9. Mã số thuế -->
        <?php lms_component('form/input', array(
            'name'        => 'tax_code',
            'label'       => 'Mã số thuế',
            'placeholder' => 'VD: 3215484562',
        )); ?>

        <!-- 10. Địa chỉ hiện tại -->
        <?php lms_component('form/input', array(
            'name'        => 'current_address',
            'label'       => 'Địa chỉ hiện tại',
            'placeholder' => 'VD: 181 Cao Thắng, Phường Hoà Hưng, TPHCM',
        )); ?>

        <!-- 11. Quận / Phường có thể dạy -->
        <?php lms_component('form/multi_select_tags', array(
            'name'        => 'teachable_districts[]',
            'label'       => 'Quận / Phường có thể dạy',
            'selected'    => $selDistricts,
            'groups'      => $districtGroups,
            'placeholder' => 'VD: Quận 1, Quận 2, Quận 3, ...',
        )); ?>

        <!-- 12. Ngày sinh -->
        <?php lms_component('form/date_picker', array(
            'name'        => 'dob',
            'label'       => 'Ngày sinh',
            'placeholder' => '04/12/2003',
        )); ?>

        <!-- 13. Giới tính -->
        <?php lms_component('form/select', array(
            'name'  => 'gender',
            'label' => 'Giới tính',
            'enum'  => 'genders',
        )); ?>

        <!-- 14. Chương trình dạy -->
        <?php lms_component('form/multi_select_tags', array(
            'name'        => 'teachable_subjects[]',
            'label'       => 'Chương trình dạy',
            'options'     => $teachingProgramOptions,
            'selected'    => $selSubjects,
            'placeholder' => 'Chọn một hoặc nhiều môn có thể dạy',
            'hint'        => 'Chọn được nhiều môn (Toán TĐN, Toán THCS, Tiếng Anh, Tiếng Việt, Khác…); dữ liệu dùng cho module Sắp xếp lớp.',
        )); ?>

        <!-- 15. Số lớp tối đa có thể nhận -->
        <?php lms_component('form/input', array(
            'name'        => 'max_classes',
            'label'       => 'Số lớp tối đa có thể nhận',
            'type'        => 'number',
            'min'         => 0,
            'max'         => 50,
            'placeholder' => 'VD: 2',
            'default'     => '1',
        )); ?>

        <!-- 16. Có thể dạy online -->
        <?php lms_component('form/select', array(
            'name'    => 'can_teach_online',
            'label'   => 'Có thể dạy online',
            'enum'    => 'yes_no',
            'default' => '1',
        )); ?>

        <!-- 17. Hạng giáo viên -->
        <?php lms_component('form/select', array(
            'name'    => 'rank',
            'label'   => 'Hạng giáo viên',
            'enum'    => 'teacher_ranks',
            'default' => 'bronze',
        )); ?>

        <!-- 18. Số tick tháng này -->
        <?php lms_component('form/addon_input', array(
            'name'     => 'month_ticks',
            'label'    => 'Số tick tháng này',
            'value'    => $monthTicks,
            'suffix'   => 'tick',
            'readonly' => true,
        )); ?>

        <!-- 19. Phí dịch vụ -->
        <?php lms_component('form/addon_input', array(
            'name'     => 'month_fee',
            'label'    => 'Phí dịch vụ',
            'value'    => number_format($monthFee, 0, ',', '.'),
            'suffix'   => 'đồng',
            'readonly' => true,
        )); ?>

        <!-- 20. Mức phí DV -->
        <?php lms_component('form/addon_input', array(
            'name'        => 'service_fee_percentage',
            'label'       => 'Mức phí DV',
            'type'        => 'number',
            'step'        => '0.5',
            'min'         => 0,
            'max'         => 100,
            'suffix'      => '%',
            'placeholder' => 'VD: 45',
            'default'     => '48',
        )); ?>

        <!-- 21. Tên ngân hàng -->
        <?php lms_component('form/input', array(
            'name'        => 'bank_name',
            'label'       => 'Tên ngân hàng',
            'placeholder' => 'VD: TMCP Công thương Việt Nam - Vietinbank',
        )); ?>

        <!-- 22. Số tài khoản NH -->
        <?php lms_component('form/input', array(
            'name'        => 'bank_account',
            'label'       => 'Số tài khoản NH',
            'placeholder' => 'VD: 565656565',
        )); ?>

        <!-- 23. Tên chủ tài khoản NH -->
        <?php lms_component('form/input', array(
            'name'        => 'bank_owner',
            'label'       => 'Tên chủ tài khoản NH',
            'uppercase'   => true,
            'placeholder' => 'NGO THAI PHUNG',
        )); ?>

        <!-- 24. Cam kết 08 -->
        <?php lms_component('form/select', array(
            'name'    => 'is_commit_08',
            'label'   => 'Cam kết 08',
            'enum'    => 'yes_no',
            'default' => '0',
        )); ?>

        <!-- 25. Trạng thái -->
        <?php lms_component('form/select', array(
            'name'    => 'status',
            'label'   => 'Trạng thái',
            'enum'    => 'teacher_statuses',
            'default' => 'active',
        )); ?>

        <?php lms_component('form/select', array(
            'name'    => 'user_status',
            'label'   => 'Trạng thái tài khoản',
            'options' => \Lms\UserService::USER_STATUSES,
            'default' => 'active',
        )); ?>

        <!-- 26. Ảnh đại diện -->
        <?php lms_component('form/avatar_picker', array(
            'name'        => 'avatar_file',
            'remove_name' => 'remove_avatar',
            'label'       => 'Ảnh đại diện',
            'value'       => $avatarSrc,
        )); ?>

        <!-- 27. Nút Submit -->
        <div class="text-center mt-4 mb-5">
            <?php lms_component('button', array(
                'label'   => $isEditMode ? 'Lưu' : 'Thêm',
                'type'    => 'submit',
                'variant' => 'primary',
                'size'    => 'lg',
            )); ?>
        </div>
        </div>
    </div>
</form>
