<?php
/**
 * Admin LMS — Quản lý tài khoản: Form Thêm / Chỉnh sửa học viên.
 * Dẫn chứng mockup:
 *  - Tai-lieu-phat-trien/admin/Quản lý tài khoản - Học sinh - Thêm.png
 *  - Tai-lieu-phat-trien/admin/Quản lý tài khoản - Học sinh - Sửa.png
 * SRS: Tai-lieu-phat-trien/admin/Tài liệu admin.md (§6. Quản lý tài khoản & §ADM-01).
 *
 * Chuẩn hoá theo kiến trúc Form Component & LmsEnum tập trung (đồng bộ với lms-giao-vien).
 * PHP 7.3 compatible.
 */

$formAction = ($alias === 'lms-tai-khoan')
    ? ($isEditMode ? 'sua-hoc-vien' : 'them-hoc-vien')
    : ($isEditMode ? 'sua' : 'them');

$formUrl = $isEditMode
    ? lms_url($alias, $formAction, array('id' => $formId))
    : lms_url($alias, $formAction);

if (empty($row['parent_phone']) && !empty($row['phone'])) {
    $row['parent_phone'] = $row['phone'];
}

$subjectOptions = isset($subjectOptions) && is_array($subjectOptions) ? $subjectOptions : array();

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

$selDistricts = \Lms\UserService::decodeJsonArray(isset($row['district']) ? $row['district'] : array());
if (empty($selDistricts) && !empty($row['district']) && is_string($row['district'])) {
    $selDistricts = array_values(array_filter(array_map('trim', explode(',', $row['district']))));
}

// Danh sách môn học được chọn (hỗ trợ chọn nhiều môn theo note mockup)
$selSubjects = array();
if (isset($_POST['subject_ids']) && is_array($_POST['subject_ids'])) {
    $selSubjects = array_values(array_filter(array_map('intval', $_POST['subject_ids'])));
} elseif (!empty($row['subject_ids'])) {
    $selSubjects = \Lms\UserService::decodeJsonArray($row['subject_ids']);
}
if (empty($selSubjects) && !empty($row['subject_id'])) {
    $selSubjects = array((int) $row['subject_id']);
}

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

// Nhóm các khung giờ theo thứ (day => array of [start, end]); mặc định Thứ 2 (17:30 - 19:00) khi thêm mới
$slots = isset($row['free_time_slots']) && is_array($row['free_time_slots'])
    ? $row['free_time_slots'] : array();
$slotsByDay = array();
foreach ($slots as $sItem) {
    if (!is_array($sItem)) {
        continue;
    }
    $dVal  = isset($sItem['day']) ? (int) $sItem['day'] : 0;
    $stVal = isset($sItem['start']) ? trim((string) $sItem['start']) : '';
    $etVal = isset($sItem['end']) ? trim((string) $sItem['end']) : '';
    if ($dVal > 0 && $stVal !== '' && $etVal !== '') {
        $slotsByDay[$dVal][] = array('start' => $stVal, 'end' => $etVal);
    }
}
if (empty($slotsByDay)) {
    $slotsByDay = array(
        2 => array(
            array('start' => '17:30', 'end' => '19:00'),
        ),
    );
}

// Danh sách môn / chương trình học (hỗ trợ cả 2 cấu trúc $subjectOptions từ lms-tai-khoan và lms-hoc-vien)
$subjectSelectOptions = array();
foreach ($subjectOptions as $sidKey => $subj) {
    if (!is_array($subj)) {
        continue;
    }
    $sid = isset($subj['id']) ? (string) $subj['id'] : (string) $sidKey;
    $progName = isset($subj['program_name']) ? (string) $subj['program_name'] : (isset($subj['program']) ? (string) $subj['program'] : '');
    $subjName = isset($subj['name']) ? (string) $subj['name'] : '';
    $subjectSelectOptions[$sid] = $subjName !== '' ? $subjName : $progName;
}
// Sắp xếp theo thứ tự ưu tiên trên mockup: Toán TĐN, Toán THCS, Tiếng Anh, Tiếng Việt, ..., Khác (cuối cùng)
$priorityOrder = array('Toán TĐN' => 1, 'Toán THCS' => 2, 'Tiếng Anh' => 3, 'Tiếng Việt' => 4, 'Khác' => 99);
uasort($subjectSelectOptions, function ($a, $b) use ($priorityOrder) {
    $pa = isset($priorityOrder[$a]) ? $priorityOrder[$a] : 50;
    $pb = isset($priorityOrder[$b]) ? $priorityOrder[$b] : 50;
    return $pa !== $pb ? ($pa - $pb) : strcmp((string) $a, (string) $b);
});
?>

<form method="post" action="<?= e($formUrl) ?>" enctype="multipart/form-data" class="lms-form" novalidate>
    <input type="hidden" name="csrf_token" value="<?= e(lms_csrf_token()) ?>">

    <div class="row">
        <div class="col-lg-8">

            <!-- 1. Số ưu tiên (Only số - đặt đầu tiên theo mockup) -->
            <?php lms_component('form/input', array(
                'name'        => 'priority_number',
                'label'       => 'Số ưu tiên',
                'type'        => 'number',
                'min'         => 0,
                'step'        => 1,
                'placeholder' => 'VD: 1',
                'default'     => '1',
                'attrs'       => array(
                    'inputmode' => 'numeric',
                    'oninput'   => "this.value = this.value.replace(/[^0-9]/g, '');",
                ),
            )); ?>

            <!-- 2. Họ tên học viên * -->
            <?php lms_component('form/input', array(
                'name'        => 'full_name',
                'label'       => 'Họ tên học viên',
                'placeholder' => 'VD: Hà Phương',
                'required'    => true,
            )); ?>

            <!-- 3. Username * -->
            <?php lms_component('form/input', array(
                'name'        => 'username',
                'label'       => 'Username',
                'placeholder' => 'VD: haphuong227',
                'required'    => true,
            )); ?>

            <!-- 4. Password * -->
            <?php lms_component('form/input', array(
                'name'        => 'password',
                'label'       => 'Password',
                'type'        => 'password',
                'value'       => '',
                'placeholder' => $isEditMode ? 'VD: 12345678 (Để trống nếu giữ nguyên)' : 'VD: 12345678',
                'required'    => !$isEditMode,
            )); ?>

            <!-- 5. User code -->
            <?php lms_component('form/input', array(
                'name'        => 'student_code',
                'label'       => 'User code',
                'placeholder' => 'VD: Master227',
                'value'       => $isEditMode ? (string) (isset($row['student_code']) ? $row['student_code'] : '') : null,
                'readonly'    => $isEditMode,
            )); ?>

            <!-- 6. Email * -->
            <?php lms_component('form/input', array(
                'name'        => 'email',
                'label'       => 'Email',
                'type'        => 'email',
                'placeholder' => 'VD: haphuong@gmail.com',
                'required'    => true,
            )); ?>

            <!-- 7. Họ tên phụ huynh -->
            <?php lms_component('form/input', array(
                'name'        => 'parent_name',
                'label'       => 'Họ tên phụ huynh',
                'placeholder' => 'VD: mai phương',
            )); ?>

            <!-- 8. Số điện thoại * -->
            <?php lms_component('form/input', array(
                'name'        => 'parent_phone',
                'label'       => 'Số điện thoại',
                'type'        => 'tel',
                'placeholder' => 'VD: 0988098097',
                'required'    => true,
            )); ?>

            <!-- 9. Hình thức học (Online / Offline) -->
            <?php lms_component('form/select', array(
                'name'        => 'learning_type',
                'label'       => 'Hình thức học',
                'enum'        => 'learning_types',
                'empty_label' => false,
                'default'     => 'online',
            )); ?>

            <!-- 10. Địa chỉ hiện tại -->
            <?php lms_component('form/input', array(
                'name'        => 'current_address',
                'label'       => 'Địa chỉ hiện tại',
                'placeholder' => 'VD: 181 Cao Thắng, Phường Hoà Hưng, TPHCM',
            )); ?>

            <!-- 11. Quận / Phường (Multi-select tags: các quận TP.HCM + toàn bộ phường Thủ Đức) -->
            <?php lms_component('form/multi_select_tags', array(
                'name'        => 'teachable_districts[]',
                'label'       => 'Quận / Phường',
                'selected'    => $selDistricts,
                'groups'      => $districtGroups,
                'placeholder' => 'VD: Quận 1, Quận 2, Quận 3, ...',
            )); ?>

            <!-- 12. Lớp -->
            <?php lms_component('form/input', array(
                'name'        => 'grade',
                'label'       => 'Lớp',
                'placeholder' => 'VD: 5',
            )); ?>

            <!-- 13. Chương trình học (Cho chọn nhiều môn theo note mockup) -->
            <?php lms_component('form/multi_select_tags', array(
                'name'        => 'subject_ids[]',
                'label'       => 'Chương trình học',
                'options'     => $subjectSelectOptions,
                'selected'    => $selSubjects,
                'placeholder' => 'Toán TĐN 1-1',
            )); ?>

            <!-- 14. Trường học -->
            <?php lms_component('form/input', array(
                'name'        => 'school_name',
                'label'       => 'Trường học',
                'placeholder' => 'VD: Trường tiểu học Yên Thế',
            )); ?>

            <!-- 15. Giới tính giáo viên (Nam, Nữ, Tuỳ ý) -->
            <?php lms_component('form/select', array(
                'name'        => 'preferred_teacher_gender',
                'label'       => 'Giới tính',
                'options'     => array(
                    'male'   => 'Nam',
                    'female' => 'Nữ',
                    ''       => 'Tuỳ ý',
                ),
                'empty_label' => false,
                'default'     => 'male',
            )); ?>

            <!-- 16. Thời gian rảnh (Cụm Thứ + Khung giờ + Thêm giờ / Thêm ngày) -->
            <?php
            $slotRows = array();
            foreach ($slotsByDay as $dVal => $timeList) {
                $ranges = array();
                foreach ($timeList as $tItem) {
                    $ranges[] = array('start' => $tItem['start'], 'end' => $tItem['end']);
                }
                $slotRows[] = array('day' => (int) $dVal, 'ranges' => $ranges);
            }

            ob_start();
            lms_component('form/schedule_slots', array(
                'id'            => 'lmsStudentSlots',
                'rows'          => $slotRows,
                'day_name'      => 'slot_day',
                'start_name'    => 'slot_start',
                'end_name'      => 'slot_end',
                'default_start' => '17:30',
                'default_end'   => '19:00',
            ));
            $slotControlHtml = ob_get_clean();
            lms_component('form/field_row', array(
                'label'   => 'Thời gian rảnh',
                'name'    => 'free_time_slots',
                'align'   => 'start',
                'control' => $slotControlHtml,
                'errors'  => $errors,
            ));
            ?>

            <!-- 17. Chờ sắp xếp từ (Mặc định hôm nay, có thể chỉnh sửa) -->
            <?php lms_component('form/date_picker', array(
                'name'        => 'waiting_from',
                'label'       => 'Chờ sắp xếp từ',
                'placeholder' => date('d/m/Y'),
                'value'       => !empty($row['waiting_from']) ? (string) $row['waiting_from'] : date('Y-m-d'),
            )); ?>

            <!-- 18. Ngày dự kiến bắt đầu học -->
            <?php lms_component('form/date_picker', array(
                'name'        => 'expected_start_date',
                'label'       => 'Ngày dự kiến bắt đầu học',
                'placeholder' => date('d/m/Y'),
                'value'       => !empty($row['expected_start_date']) ? (string) $row['expected_start_date'] : date('Y-m-d'),
            )); ?>

            <!-- 19. Ghi chú -->
            <?php lms_component('form/textarea', array(
                'name'        => 'note',
                'label'       => 'Ghi chú',
                'rows'        => 3,
                'placeholder' => '',
            )); ?>

            <!-- 20. Trạng thái (Đang sắp xếp, Chờ học phí, Đang học, Nghỉ học, Tạm nghỉ) -->
            <?php lms_component('form/select', array(
                'name'    => 'status',
                'label'   => 'Trạng thái',
                'enum'    => 'student_statuses',
                'default' => 'pending_arrange',
            )); ?>

            <!-- 21. Ảnh đại diện (Chọn hình png,jpg,jpeg + preview box có nút xoá đỏ) -->
            <?php lms_component('form/avatar_picker', array(
                'name'        => 'avatar_file',
                'remove_name' => 'remove_avatar',
                'label'       => 'Ảnh đại diện',
                'value'       => $avatarSrc,
            )); ?>

            <!-- 22. Nút Submit canh giữa -->
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