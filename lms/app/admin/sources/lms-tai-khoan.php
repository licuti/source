<?php
/**
 * Admin LMS — Quản lý tài khoản (Gồm 2 Tab: Tài khoản học viên & Tài khoản giáo viên).
 * Mockup: Tai-lieu-phat-trien/admin/Tài khoản HV.png, Tài khoản GV.png.
 * SRS: Tai-lieu-phat-trien/admin/Tài liệu admin.md (§6. Quản lý tài khoản).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d        = lms_db();
$user     = lms_current_user();
$alias    = 'lms-tai-khoan';
$userSvc  = new \Lms\UserService($d);

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'them-hoc-vien':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_tk_hoc_vien_form($userSvc, false);
        break;

    case 'sua-hoc-vien':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_tk_hoc_vien_form($userSvc, true);
        break;

    case 'khoa-hoc-vien':
    case 'mo-hoc-vien':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_tk_hoc_vien_toggle_status($userSvc, str_replace('-hoc-vien', '', $action));
        break;

    case 'xoa-hoc-vien':
        lms_form_guard($d, $user, $alias, 'delete');
        lms_tk_hoc_vien_delete($userSvc);
        break;

    case 'them-giao-vien':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_tk_giao_vien_form($userSvc, false);
        break;

    case 'sua-giao-vien':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_tk_giao_vien_form($userSvc, true);
        break;

    case 'khoa-giao-vien':
    case 'mo-giao-vien':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_tk_giao_vien_toggle_status($userSvc, str_replace('-giao-vien', '', $action));
        break;

    case 'xoa-giao-vien':
        lms_form_guard($d, $user, $alias, 'delete');
        lms_tk_giao_vien_delete($userSvc);
        break;

    case 'ajax-update-hoc-vien':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_tk_ajax_update_hoc_vien($d);
        break;

    case 'thanh-toan-phi-dv':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_tk_thanh_toan_phi_dv($d);
        break;

    case 'cong-lai-phi-dv':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_tk_cong_lai_phi_dv($d);
        break;

    case 'dieu-chinh-phi-dv':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_tk_dieu_chinh_phi_dv($d);
        break;

    default:
        lms_tk_list($userSvc);
        break;
}

// ===================== Danh sách 2 Tabs =====================

function lms_tk_list(\Lms\UserService $userSvc)
{
    global $title, $template, $tab, $items, $total, $totalPages, $page,
           $filterQ, $filterStatus, $filterRank, $filterSubject, $subjectList,
           $canEdit, $canDelete, $subjectMap, $feeModalTeachers, $feeModalEarliestDate,
           $d, $user, $alias;

    $tab = isset($_GET['tab']) && (string) $_GET['tab'] === 'giao-vien' ? 'giao-vien' : 'hoc-vien';

    $filterQ       = isset($_GET['q']) ? trim((string) $_GET['q']) : '';
    $filterStatus  = isset($_GET['status']) ? (string) $_GET['status'] : '';
    $filterRank    = isset($_GET['rank']) ? (string) $_GET['rank'] : '';
    $filterSubject = isset($_GET['subject_id']) ? (int) $_GET['subject_id'] : 0;
    $page          = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
    $perPage       = 20;

    $canEdit   = lms_check_permission($d, $user, $alias, 'edit', 'admin');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'admin');

    // Danh sách môn/chương trình học cho bộ lọc
    $subjectList = $d->safeFetch('SELECT id, name FROM db_lms_subjects ORDER BY name');

    if ($tab === 'giao-vien') {
        $res = $userSvc->listTeachers(
            array('q' => $filterQ, 'status' => $filterStatus, 'rank' => $filterRank),
            $page,
            $perPage
        );

        $items      = $res['items'];
        $total      = (int) $res['total'];
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

        $subjectMap = lms_tk_giao_vien_subject_map($d);

        // Danh sách toàn bộ giáo viên ACTIVE + số dư phí dịch vụ chờ thanh toán cho Modal
        $allTeacherRows = $d->safeFetch(
            'select u.id, t.teacher_code, t.full_name, t.rank'
            . ' from db_lms_users u'
            . ' join db_lms_teachers t on t.user_id = u.id'
            . ' where u.role = ? and t.status = ?'
            . ' order by t.teacher_code asc, u.id asc',
            array(\Lms\UserService::ROLE_TEACHER, 'active')
        );
        $paySvc = new \Lms\PayrollService($d);
        $allTeacherIds = array();
        foreach ($allTeacherRows as $tr) {
            $allTeacherIds[] = (int) $tr['id'];
        }
        $unpaidAllMap = $paySvc->unpaidBalancesForTeachers($allTeacherIds, false);
        $feeModalTeachers = array();
        foreach ($allTeacherRows as $tr) {
            $tid = (int) $tr['id'];
            $tr['unpaid_fee'] = isset($unpaidAllMap[$tid]) ? (float) $unpaidAllMap[$tid] : 0.0;
            $feeModalTeachers[] = $tr;
        }

        $minDate = $paySvc->getEarliestUnpaidDate($allTeacherIds);
        $feeModalEarliestDate = !empty($minDate) ? $minDate : date('Y-m-01');
    } else {
        // Tab học viên (mặc định)
        $filters = array('q' => $filterQ, 'status' => $filterStatus);
        if ($filterSubject > 0) {
            $filters['subject_id'] = $filterSubject;
        }

        $res = $userSvc->listStudents($filters, $page, $perPage);
        $items      = $res['items'];
        $total      = (int) $res['total'];
        $totalPages = $perPage > 0 ? (int) ceil($total / $perPage) : 1;

        // Bổ sung lịch học chi tiết cho từng học sinh
        foreach ($items as &$st) {
            $stId = (int) $st['id'];
            $schedRows = $d->safeFetch(
                'SELECT DISTINCT s.session_date, s.start_time, s.end_time, c.class_code, c.name as class_name '
                . ' FROM db_lms_class_students cs '
                . ' JOIN db_lms_classes c ON c.id = cs.class_id AND c.deleted_at IS NULL '
                . ' JOIN db_lms_sessions s ON s.class_id = c.id '
                . ' WHERE cs.student_id = ? AND cs.status = "active" '
                . ' ORDER BY s.session_date ASC, s.start_time ASC LIMIT 3',
                array($stId)
            );
            $st['schedules_list'] = $schedRows;
        }
        unset($st);
    }

    $title    = 'Quản lý tài khoản';
    $template = 'lms-tai-khoan/hienthi';
}

function lms_tk_giao_vien_subject_map(LmsDb $d)
{
    $rows = $d->safeFetch(
        'select s.id, s.name, p.name as program_name'
        . ' from db_lms_subjects s'
        . ' join db_lms_programs p on p.id = s.program_id'
        . ' order by p.name, s.name'
    );
    $map = array();
    foreach ($rows as $r) {
        $map[(int) $r['id']] = array(
            'name'    => (string) $r['name'],
            'program' => (string) $r['program_name'],
        );
    }
    return $map;
}

// ===================== Form Tạo / Sửa Học viên =====================

function lms_tk_hoc_vien_form(\Lms\UserService $userSvc, $isEdit)
{
    global $title, $template, $row, $errors, $isEditMode, $formId,
           $subjectOptions, $d, $user, $alias;

    $errors     = array();
    $isEditMode = $isEdit;
    $row        = array();
    $formId     = 0;

    if ($isEdit) {
        $formId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($formId <= 0) {
            lms_flash('error', 'Thiếu ID học viên.');
            header('Location: ' . lms_url($alias, 'man', array('tab' => 'hoc-vien')));
            exit;
        }
        $row = $userSvc->getStudent($formId);
        if (empty($row)) {
            lms_flash('error', 'Học viên không tồn tại.');
            header('Location: ' . lms_url($alias, 'man', array('tab' => 'hoc-vien')));
            exit;
        }
    }

    $isPost = isset($_SERVER['REQUEST_METHOD']) && (string) $_SERVER['REQUEST_METHOD'] === 'POST';

    if ($isPost) {
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
            header('Location: ' . lms_url($alias, $isEdit ? 'sua-hoc-vien' : 'them-hoc-vien', $isEdit ? array('id' => $formId) : array()));
            exit;
        }

        $in = $_POST;
        $in['free_time_slots'] = lms_hoc_vien_slots_from_post($_POST);

        // Upload ảnh đại diện nếu có
        $uploadedAvatarPath = '';
        if (!empty($_FILES['avatar_file']['name'])) {
            $uploadRes = lms_save_files($_FILES['avatar_file'], 'avatars', array(
                'maxFiles' => 1,
                'mimeWhitelist' => array('jpg', 'jpeg', 'png', 'webp')
            ));
            if ($uploadRes['ok'] && !empty($uploadRes['paths'][0])) {
                $uploadedAvatarPath = (string) $uploadRes['paths'][0];
                $in['avatar'] = 'uploads/' . $uploadedAvatarPath;
            } else {
                $errors = array('avatar_file' => !empty($uploadRes['error'])
                    ? $uploadRes['error'] : 'Không thể tải ảnh đại diện lên.');
            }
        }
        if (!empty($in['remove_avatar'])) {
            $in['avatar'] = null;
        }

        if (empty($errors)) {
            if ($isEdit) {
                $ok = $userSvc->updateStudent($formId, $in);
            } else {
                $newId = $userSvc->createStudent($in);
                $ok = $newId !== null;
                if ($ok) {
                    $formId = (int) $newId;
                }
            }

            if ($ok) {
                $message = $isEdit ? 'Đã cập nhật hồ sơ học viên.' : 'Đã tạo tài khoản học viên mới.';
                $generatedPassword = $isEdit ? '' : $userSvc->lastGeneratedPassword();
                if ($generatedPassword !== '') {
                    $message .= ' Mật khẩu tạm: ' . $generatedPassword
                        . '. Học viên cần đổi mật khẩu ở lần đăng nhập đầu tiên.';
                }
                lms_flash('success', $message);
                header('Location: ' . lms_url($alias, 'man', array('tab' => 'hoc-vien')));
                exit;
            }

            $errors = $userSvc->errors();
        }
        if ($uploadedAvatarPath !== '' && function_exists('lms_upload_dir')) {
            @unlink(lms_upload_dir() . '/' . ltrim($uploadedAvatarPath, '/\\'));
        }
        $row = array_merge($row, $in);
    }

    $row['free_time_slots'] = \Lms\UserService::decodeJsonArray(
        isset($row['free_time_slots']) ? $row['free_time_slots'] : array()
    );

    $subjectOptions = $d->safeFetch(
        'select s.id, s.name, p.name as program_name'
        . ' from db_lms_subjects s'
        . ' join db_lms_programs p on p.id = s.program_id'
        . ' order by p.name, s.name'
    );

    $title    = $isEdit ? 'Chỉnh sửa học viên' : 'Tài khoản học viên mới';
    $template = 'lms-hoc-vien/form';
}

function lms_hoc_vien_slots_from_post($post)
{
    $days   = isset($post['slot_day'])   && is_array($post['slot_day'])   ? $post['slot_day']   : array();
    $starts = isset($post['slot_start']) && is_array($post['slot_start']) ? $post['slot_start'] : array();
    $ends   = isset($post['slot_end'])   && is_array($post['slot_end'])   ? $post['slot_end']   : array();

    $slots = array();
    foreach ($days as $i => $day) {
        $day = trim((string) $day);
        $rowStarts = isset($starts[$i]) && is_array($starts[$i]) ? $starts[$i] : array();
        $rowEnds   = isset($ends[$i]) && is_array($ends[$i]) ? $ends[$i] : array();

        $count = max(count($rowStarts), count($rowEnds));
        for ($j = 0; $j < $count; $j++) {
            $start = isset($rowStarts[$j]) ? trim((string) $rowStarts[$j]) : '';
            $end   = isset($rowEnds[$j]) ? trim((string) $rowEnds[$j]) : '';
            if ($start === '' && $end === '') {
                continue;
            }
            $slots[] = array(
                'day'   => $day,
                'start' => $start,
                'end'   => $end,
            );
        }
    }
    return $slots;
}

function lms_tk_hoc_vien_toggle_status(\Lms\UserService $userSvc, $action)
{
    global $alias;
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID học viên.');
        header('Location: ' . lms_url($alias, 'man', array('tab' => 'hoc-vien')));
        exit;
    }
    $targetStatus = ($action === 'khoa') ? 'locked' : 'active';
    if ($userSvc->setUserStatus($id, $targetStatus)) {
        lms_flash('success', $action === 'khoa' ? 'Đã khoá tài khoản học viên.' : 'Đã mở khoá tài khoản học viên.');
    } else {
        lms_flash('error', $userSvc->firstError());
    }
    header('Location: ' . lms_url($alias, 'man', array('tab' => 'hoc-vien')));
    exit;
}

function lms_tk_hoc_vien_delete(\Lms\UserService $userSvc)
{
    global $alias;
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID học viên.');
        header('Location: ' . lms_url($alias, 'man', array('tab' => 'hoc-vien')));
        exit;
    }
    if ($userSvc->deleteStudent($id)) {
        lms_flash('success', 'Đã xoá học viên thành công.');
    } else {
        lms_flash('error', $userSvc->firstError());
    }
    header('Location: ' . lms_url($alias, 'man', array('tab' => 'hoc-vien')));
    exit;
}

// ===================== Form Tạo / Sửa Giáo viên =====================

function lms_tk_giao_vien_form(\Lms\UserService $userSvc, $isEdit)
{
    global $title, $template, $row, $errors, $isEditMode, $formId,
           $subjectOptions, $districts, $d, $user, $alias;

    $errors     = array();
    $isEditMode = $isEdit;
    $row        = array();
    $formId     = 0;

    if ($isEdit) {
        $formId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        if ($formId <= 0) {
            lms_flash('error', 'Thiếu ID giáo viên.');
            header('Location: ' . lms_url($alias, 'man', array('tab' => 'giao-vien')));
            exit;
        }
        $row = $userSvc->getTeacher($formId);
        if (empty($row)) {
            lms_flash('error', 'Giáo viên không tồn tại.');
            header('Location: ' . lms_url($alias, 'man', array('tab' => 'giao-vien')));
            exit;
        }
    }

    $isPost = isset($_SERVER['REQUEST_METHOD']) && (string) $_SERVER['REQUEST_METHOD'] === 'POST';

    if ($isPost) {
        if (!lms_csrf_verify()) {
            lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
            header('Location: ' . lms_url($alias, $isEdit ? 'sua-giao-vien' : 'them-giao-vien', $isEdit ? array('id' => $formId) : array()));
            exit;
        }

        $in = $_POST;

        // Chuẩn hoá các trường enum bằng LmsEnum
        if (isset($in['gender'])) {
            $in['gender'] = lms_enum_normalize('gender', $in['gender']);
        }
        if (isset($in['rank'])) {
            $in['rank'] = lms_enum_normalize('teacher_ranks', $in['rank']);
        }
        if (isset($in['status'])) {
            $in['status'] = lms_enum_normalize('teacher_statuses', $in['status']);
        }

        // Upload trước khi lưu hồ sơ để có thể báo lỗi ngay; nếu validation
        // hoặc transaction hồ sơ thất bại thì file mới sẽ được dọn lại.
        $uploadedAvatar = '';
        $uploadedAvatarPath = '';
        if (!empty($_FILES['avatar_file']['name'])) {
            $uploadRes = lms_save_files($_FILES['avatar_file'], 'avatars', array(
                'maxFiles' => 1,
                'mimeWhitelist' => array('jpg', 'jpeg', 'png', 'webp')
            ));
            if ($uploadRes['ok'] && !empty($uploadRes['paths'][0])) {
                $uploadedAvatarPath = (string) $uploadRes['paths'][0];
                $uploadedAvatar = 'uploads/' . $uploadedAvatarPath;
            } else {
                $errors = array('avatar_file' => !empty($uploadRes['error'])
                    ? $uploadRes['error'] : 'Không thể tải ảnh đại diện lên.');
            }
        }

        $ok = false;
        if (empty($errors)) {
            if ($isEdit) {
                $ok = $userSvc->updateTeacher($formId, $in);
            } else {
                $newId = $userSvc->createTeacher($in);
                $ok = $newId !== null;
                if ($ok) {
                    $formId = (int) $newId;
                }
            }
        }

        if ($ok) {
            // Chỉ thay đổi avatar sau khi hồ sơ đã lưu thành công.
            if (!empty($in['remove_avatar']) && $formId > 0) {
                $d->safeExec('UPDATE db_lms_teacher_profiles SET avatar = NULL, updated_at = NOW() WHERE teacher_id = ?', array($formId));
            }
            if ($uploadedAvatar !== '') {
                $existingProf = $d->safeFetchOne('SELECT id FROM db_lms_teacher_profiles WHERE teacher_id = ?', array($formId));
                if ($existingProf) {
                    $d->safeExec('UPDATE db_lms_teacher_profiles SET avatar = ?, updated_at = NOW() WHERE teacher_id = ?', array($uploadedAvatar, $formId));
                } else {
                    $slug = 'gv-' . $formId . '-' . time();
                    $d->insert('db_lms_teacher_profiles', array(
                        'teacher_id' => $formId,
                        'slug' => $slug,
                        'avatar' => $uploadedAvatar,
                        'show_on_website' => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ));
                }
            }

            $message = $isEdit ? 'Đã cập nhật hồ sơ giáo viên.' : 'Đã tạo tài khoản giáo viên mới.';
            $generatedPassword = $isEdit ? '' : $userSvc->lastGeneratedPassword();
            if ($generatedPassword !== '') {
                $message .= ' Mật khẩu tạm: ' . $generatedPassword
                    . '. Giáo viên cần đổi mật khẩu ở lần đăng nhập đầu tiên.';
            }
            lms_flash('success', $message);
            header('Location: ' . lms_url($alias, 'man', array('tab' => 'giao-vien')));
            exit;
        }

        if ($uploadedAvatarPath !== '' && function_exists('lms_upload_dir')) {
            @unlink(lms_upload_dir() . '/' . ltrim($uploadedAvatarPath, '/\\'));
        }
        if (empty($errors)) {
            $errors = $userSvc->errors();
        }
        $row = array_merge($row, $in);
    }

    $row['teachable_districts'] = \Lms\UserService::decodeJsonArray(
        isset($row['teachable_districts']) ? $row['teachable_districts'] : array()
    );
    $row['teachable_subjects'] = \Lms\UserService::decodeJsonArray(
        isset($row['teachable_subjects']) ? $row['teachable_subjects'] : array()
    );

    $subjectOptions = lms_tk_giao_vien_subject_map($d);
    $districts      = \Lms\UserService::HCMC_DISTRICTS;

    $title    = $isEdit ? 'Chỉnh sửa giáo viên' : 'Tài khoản giáo viên mới';
    $template = 'lms-giao-vien/form';
}

function lms_tk_giao_vien_toggle_status(\Lms\UserService $userSvc, $action)
{
    global $alias;
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID giáo viên.');
        header('Location: ' . lms_url($alias, 'man', array('tab' => 'giao-vien')));
        exit;
    }
    $targetStatus = ($action === 'khoa') ? 'locked' : 'active';
    if ($userSvc->setUserStatus($id, $targetStatus)) {
        lms_flash('success', $action === 'khoa' ? 'Đã khoá tài khoản giáo viên.' : 'Đã mở khoá tài khoản giáo viên.');
    } else {
        lms_flash('error', $userSvc->firstError());
    }
    header('Location: ' . lms_url($alias, 'man', array('tab' => 'giao-vien')));
    exit;
}

function lms_tk_giao_vien_delete(\Lms\UserService $userSvc)
{
    global $alias;
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        lms_flash('error', 'Thiếu ID giáo viên.');
        header('Location: ' . lms_url($alias, 'man', array('tab' => 'giao-vien')));
        exit;
    }
    if ($userSvc->deleteTeacher($id)) {
        lms_flash('success', 'Đã xoá giáo viên thành công.');
    } else {
        lms_flash('error', $userSvc->firstError());
    }
    header('Location: ' . lms_url($alias, 'man', array('tab' => 'giao-vien')));
    exit;
}

// ===================== Chỉnh sửa nhanh Inline (AJAX) =====================

function lms_tk_ajax_update_hoc_vien(LmsDb $d)
{
    header('Content-Type: application/json; charset=utf-8');
    if (!lms_csrf_verify()) {
        echo json_encode(array('ok' => false, 'error' => 'CSRF token không hợp lệ'));
        exit;
    }

    $userId = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
    if ($userId <= 0) {
        echo json_encode(array('ok' => false, 'error' => 'ID không hợp lệ'));
        exit;
    }

    $fields = array();
    $params = array();

    if (isset($_POST['priority_number'])) {
        $fields[] = 'priority_number = ?';
        $params[] = (int) $_POST['priority_number'];
    }

    if (isset($_POST['status'])) {
        $st = (string) $_POST['status'];
        $validStatuses = array_keys(\Lms\UserService::STUDENT_STATUSES);
        if (in_array($st, $validStatuses, true)) {
            $fields[] = 'status = ?';
            $params[] = $st;
        }
    }

    if (empty($fields)) {
        echo json_encode(array('ok' => false, 'error' => 'Không có dữ liệu thay đổi'));
        exit;
    }

    $params[] = $userId;
    $ok = $d->safeExec('UPDATE db_lms_students SET ' . implode(', ', $fields) . ' WHERE user_id = ?', $params);

    echo json_encode(array('ok' => (bool) $ok));
    exit;
}

// ===================== Modal Thanh toán phí dịch vụ (§6.7 bước 3) =====================

function lms_tk_parse_teacher_ids_from_post()
{
    $raw = isset($_POST['teacher_ids']) ? $_POST['teacher_ids'] : array();
    if (is_string($raw)) {
        $raw = explode(',', $raw);
    }
    $ids = array();
    if (is_array($raw)) {
        foreach ($raw as $v) {
            $id = (int) $v;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
    }
    return array_values($ids);
}

function lms_tk_fee_respond($isAjax, array $payload)
{
    global $alias;
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!empty($payload['ok'])) {
        $msg = isset($payload['message']) ? (string) $payload['message'] : 'Thao tác thành công.';
        if (!empty($payload['warnings']) && is_array($payload['warnings'])) {
            $msg .= ' Lưu ý: ' . implode(' ', $payload['warnings']);
        }
        lms_flash('success', $msg);
    } else {
        $err = isset($payload['error']) ? (string) $payload['error'] : 'Có lỗi xảy ra.';
        lms_flash('error', $err);
    }

    header('Location: ' . lms_url($alias, 'man', array('tab' => 'giao-vien')));
    exit;
}

function lms_tk_thanh_toan_phi_dv(LmsDb $d)
{
    global $user;
    $isAjax = !empty($_POST['ajax'])
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if (!lms_csrf_verify()) {
        lms_tk_fee_respond($isAjax, array(
            'ok'    => false,
            'error' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.',
        ));
    }

    $teacherIds = lms_tk_parse_teacher_ids_from_post();
    $dateFrom   = isset($_POST['date_from']) ? trim((string) $_POST['date_from']) : '';
    $dateTo     = isset($_POST['date_to']) ? trim((string) $_POST['date_to']) : '';
    $note       = isset($_POST['note']) ? trim((string) $_POST['note']) : '';
    $groupCode  = isset($_POST['group_code']) ? trim((string) $_POST['group_code']) : null;
    $actorId    = isset($user['id']) ? (int) $user['id'] : 0;

    $paySvc = new \Lms\PayrollService($d);
    $res    = $paySvc->payBatch($teacherIds, $dateFrom, $dateTo, $note, $actorId, $groupCode, true);

    if (empty($res['ok'])) {
        lms_tk_fee_respond($isAjax, array(
            'ok'    => false,
            'error' => $paySvc->firstError() !== '' ? $paySvc->firstError() : 'Không thể thanh toán phí dịch vụ.',
        ));
    }

    $balances = $paySvc->unpaidBalancesForTeachers($teacherIds, false);

    if ((int) $res['paid_teachers'] === 0) {
        $msg = 'Không có giao dịch chờ thanh toán nào tính đến ngày ' . $dateTo . ' cho giáo viên đã chọn.';
    } else {
        $msg = 'Đã thanh toán thành công ' . (int) $res['transactions_paid'] . ' giao dịch ('
            . number_format((float) $res['total_net_amount']) . ' đ) cho ' . (int) $res['paid_teachers'] . ' giáo viên.';
    }

    lms_tk_fee_respond($isAjax, array(
        'ok'                => true,
        'message'           => $msg,
        'group_code'        => isset($res['group_code']) ? $res['group_code'] : null,
        'paid_teachers'     => (int) $res['paid_teachers'],
        'skipped_teachers'  => (int) $res['skipped_teachers'],
        'transactions_paid' => (int) $res['transactions_paid'],
        'total_net_amount'  => (float) $res['total_net_amount'],
        'batches'           => $res['batches'],
        'warnings'          => $res['warnings'],
        'balances'          => $balances,
    ));
}

function lms_tk_cong_lai_phi_dv(LmsDb $d)
{
    global $user;
    $isAjax = !empty($_POST['ajax'])
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if (!lms_csrf_verify()) {
        lms_tk_fee_respond($isAjax, array(
            'ok'    => false,
            'error' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.',
        ));
    }

    $teacherIds     = lms_tk_parse_teacher_ids_from_post();
    $dateFrom       = isset($_POST['date_from']) ? trim((string) $_POST['date_from']) : '';
    $dateTo         = isset($_POST['date_to']) ? trim((string) $_POST['date_to']) : '';
    $reason         = isset($_POST['reason']) ? trim((string) $_POST['reason']) : 'Cộng lại các buổi từ Modal Phí dịch vụ';
    $confirmPartial = !empty($_POST['confirm_partial']);
    $actorId        = isset($user['id']) ? (int) $user['id'] : 0;

    $paySvc = new \Lms\PayrollService($d);
    $res    = $paySvc->unpayByDateRange($teacherIds, $dateFrom, $dateTo, $reason, $actorId, $confirmPartial);

    if (!empty($res['needs_confirm'])) {
        $batchLines = array();
        foreach ($res['partial_batches'] as $pb) {
            $batchLines[] = $pb['code'] . ' (' . $pb['teacher_code'] . ': ' . $pb['date_from'] . ' đến ' . $pb['date_to'] . ')';
        }
        $confirmMsg = 'Khoảng ngày bạn chọn (' . $dateFrom . ' - ' . $dateTo . ') thuộc một phần của Phiên thanh toán: '
            . implode('; ', $batchLines)
            . '. Để đảm bảo tính đúng thuế và phạt, hệ thống sẽ hoàn tác toàn bộ phiên này để bạn chọn lại đúng kỳ. Bạn có đồng ý không?';

        lms_tk_fee_respond($isAjax, array(
            'ok'              => false,
            'needs_confirm'   => true,
            'confirm_message' => $confirmMsg,
            'partial_batches' => $res['partial_batches'],
            'error'           => $confirmMsg,
        ));
    }

    if (empty($res['ok'])) {
        lms_tk_fee_respond($isAjax, array(
            'ok'    => false,
            'error' => $paySvc->firstError() !== '' ? $paySvc->firstError() : 'Không thể hoàn tác giao dịch.',
        ));
    }

    $balances = $paySvc->unpaidBalancesForTeachers($teacherIds, false);

    if ((int) $res['reverted_transactions'] === 0) {
        $msg = 'Không có giao dịch đã thanh toán nào trong khoảng ngày ' . $dateFrom . ' → ' . $dateTo . ' cần cộng lại.';
    } else {
        $msg = 'Đã cộng lại thành công ' . (int) $res['reverted_transactions'] . ' giao dịch ('
            . number_format((float) $res['reverted_amount']) . ' đ) cho ' . (int) $res['reverted_teachers'] . ' giáo viên.';
    }

    lms_tk_fee_respond($isAjax, array(
        'ok'                    => true,
        'message'               => $msg,
        'reverted_teachers'     => (int) $res['reverted_teachers'],
        'reverted_transactions' => (int) $res['reverted_transactions'],
        'reverted_amount'       => (float) $res['reverted_amount'],
        'voided_batches'        => $res['voided_batches'],
        'balances'              => $balances,
    ));
}

function lms_tk_dieu_chinh_phi_dv(LmsDb $d)
{
    global $user;
    $isAjax = !empty($_POST['ajax'])
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string) $_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

    if (!lms_csrf_verify()) {
        lms_tk_fee_respond($isAjax, array(
            'ok'    => false,
            'error' => 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.',
        ));
    }

    $teacherIds       = lms_tk_parse_teacher_ids_from_post();
    $type             = isset($_POST['type']) ? trim((string) $_POST['type']) : 'adjustment';
    $rawAmount        = isset($_POST['amount']) ? trim((string) $_POST['amount']) : '';
    $reason           = isset($_POST['reason']) ? trim((string) $_POST['reason']) : '';
    $idempotencyToken = isset($_POST['idempotency_token']) ? trim((string) $_POST['idempotency_token']) : null;
    $actorId          = isset($user['id']) ? (int) $user['id'] : 0;

    $cleanAmount = str_replace(array(',', ' ', '.'), '', $rawAmount);
    if ($cleanAmount === '' || !is_numeric($cleanAmount)) {
        lms_tk_fee_respond($isAjax, array(
            'ok'    => false,
            'error' => 'Số tiền điều chỉnh không hợp lệ (VD: +100000 hoặc -100000).',
        ));
    }

    $paySvc = new \Lms\PayrollService($d);
    $res    = $paySvc->bulkAddAdjustment($teacherIds, $type, (float) $cleanAmount, $reason, $actorId, null, $idempotencyToken);

    if (empty($res['ok'])) {
        lms_tk_fee_respond($isAjax, array(
            'ok'    => false,
            'error' => $paySvc->firstError() !== '' ? $paySvc->firstError() : 'Không thể tạo điều chỉnh tiền.',
        ));
    }

    $balances = $paySvc->unpaidBalancesForTeachers($teacherIds, false);
    $signed   = (float) $res['amount'];
    $signStr  = ($signed >= 0 ? '+' : '-') . number_format(abs($signed)) . ' đ';

    lms_tk_fee_respond($isAjax, array(
        'ok'            => true,
        'message'       => 'Đã điều chỉnh ' . $signStr . ' cho ' . (int) $res['created_count'] . ' giáo viên.',
        'created_count' => (int) $res['created_count'],
        'amount'        => $signed,
        'balances'      => $balances,
    ));
}
