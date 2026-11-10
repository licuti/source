<?php
/**
 * Teacher — Tài khoản giáo viên.
 *
 * Giáo viên tự xem/cập nhật hồ sơ cá nhân, thông tin giảng dạy và thông tin
 * thanh toán. Chỉ whitelist các trường được phép sửa trong UserService.
 *
 * Mockup: Tai-lieu-phat-trien/teacher-ver2/Chi tiết GV.png
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d = lms_db();
$user = lms_current_user();
$alias = 'lms-tai-khoan';
$teacherId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;

lms_form_guard($d, $user, $alias, 'view', 'teacher');

$userSvc = new \Lms\UserService($d);
$teacher = $userSvc->getTeacher($teacherId);
if (empty($teacher)) {
    lms_flash('error', 'Không tìm thấy hồ sơ giáo viên.');
    header('Location: ' . lms_url('lms-tong-quan'));
    exit;
}

$errors = array();

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    lms_form_guard($d, $user, $alias, 'edit', 'teacher');
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $in = $_POST;
    $in['teachable_districts'] = isset($_POST['teachable_districts']) && is_array($_POST['teachable_districts'])
        ? $_POST['teachable_districts'] : array();
    $in['teachable_subjects'] = isset($_POST['teachable_subjects']) && is_array($_POST['teachable_subjects'])
        ? $_POST['teachable_subjects'] : array();

    $uploadedAvatar = '';
    $uploadedAvatarPath = '';
    if (!empty($_FILES['avatar_file']['name'])) {
        $uploadRes = lms_save_files($_FILES['avatar_file'], 'avatars', array(
            'maxFiles'      => 1,
            'mimeWhitelist' => array('jpg', 'jpeg', 'png', 'webp'),
        ));
        if (!empty($uploadRes['ok']) && !empty($uploadRes['paths'][0])) {
            $uploadedAvatarPath = (string) $uploadRes['paths'][0];
            $uploadedAvatar = 'uploads/' . $uploadedAvatarPath;
        } else {
            $errors['avatar_file'] = !empty($uploadRes['error'])
                ? (string) $uploadRes['error'] : 'Không thể tải ảnh đại diện lên.';
        }
    }

    $ok = false;
    if (empty($errors)) {
        $ok = $userSvc->updateTeacherSelf($teacherId, $in);
    }

    if ($ok) {
        if ($uploadedAvatar !== '') {
            $profile = $d->safeFetchOne(
                'select id from db_lms_teacher_profiles where teacher_id = ? limit 1',
                array($teacherId)
            );
            if (!empty($profile)) {
                $d->safeExec(
                    'update db_lms_teacher_profiles set avatar = ?, updated_at = now() where teacher_id = ?',
                    array($uploadedAvatar, $teacherId)
                );
            } else {
                $d->insert('db_lms_teacher_profiles', array(
                    'teacher_id'     => $teacherId,
                    'slug'           => 'gv-' . $teacherId . '-' . time(),
                    'avatar'         => $uploadedAvatar,
                    'show_on_website' => 0,
                    'created_at'     => date('Y-m-d H:i:s'),
                    'updated_at'     => date('Y-m-d H:i:s'),
                ));
            }
        }

        lms_flash('success', 'Đã cập nhật tài khoản giáo viên.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    if ($uploadedAvatarPath !== '' && function_exists('lms_upload_dir')) {
        @unlink(lms_upload_dir() . '/' . ltrim($uploadedAvatarPath, '/\\'));
    }
    $errors = $errors !== array() ? $errors : $userSvc->errors();

    $row = $teacher;
    foreach (array(
        'full_name', 'email', 'phone', 'gender', 'dob', 'cccd', 'issue_date', 'issue_place',
        'current_address', 'max_classes', 'can_teach_online', 'bank_name', 'bank_account', 'bank_owner',
    ) as $key) {
        if (isset($_POST[$key])) {
            $row[$key] = trim((string) $_POST[$key]);
        }
    }
    $row['teachable_districts'] = $in['teachable_districts'];
    $row['teachable_subjects'] = $in['teachable_subjects'];
} else {
    $row = $teacher;
    $row['teachable_districts'] = \Lms\UserService::decodeJsonArray(
        isset($teacher['teachable_districts']) ? $teacher['teachable_districts'] : array()
    );
    $row['teachable_subjects'] = \Lms\UserService::decodeJsonArray(
        isset($teacher['teachable_subjects']) ? $teacher['teachable_subjects'] : array()
    );
}

$subjectOptions = array();
$subjectRows = $d->safeFetch(
    'select s.id, s.name, p.name as program_name'
    . ' from db_lms_subjects s'
    . ' join db_lms_programs p on p.id = s.program_id'
    . ' order by p.name, s.name'
);
foreach ($subjectRows as $subject) {
    $subjectOptions[(int) $subject['id']] = (string) $subject['name'];
}

$title = 'Tài khoản giáo viên';
$template = 'lms-tai-khoan/hienthi';
