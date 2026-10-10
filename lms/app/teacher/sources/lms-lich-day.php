<?php
/**
 * Teacher — Quản lý lịch (TCH-01/02, mockup §4 + §7).
 *
 * Action:
 *   - 'man'         (GET)  → form thông tin cá nhân + ghi chú tuần + calendar tuần.
 *   - 'luu-thong-tin' (POST) → lưu profile + ghi chú tuần (1 form, §4.4).
 *   - 'them-slot'   (POST) → đăng ký lịch trống.
 *   - 'xoa-slot'    (POST) → xoá lịch trống.
 *   - 'buoi'        (GET)  → chi tiết buổi học (lesson summary + BTVN, §7).
 *   - 'luu-buoi'    (POST) → ghi lesson summary + giao BTVN (kèm file).
 *   - 'cham-btvn'   (POST) → chấm BTVN cho 1 học viên.
 *   - 'gui-yeu-cau' (POST) → tạo request (thêm buổi / xin nghỉ / đổi lịch).
 *
 * Mọi POST: lms_csrf_verify() + guard quyền edit; service ném errors →
 * flash + redirect lại form (PRG), giữ ?week=.
 * Row-level: $teacherId = user id hiện tại, service tự lọc teacher_id.
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d         = lms_db();
$user      = lms_current_user();
$alias     = 'lms-lich-day';
$teacherId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'luu-thong-tin':
        lms_form_guard($d, $user, $alias, 'edit', 'teacher');
        lmld_save_profile();
        break;

    case 'them-slot':
        lms_form_guard($d, $user, $alias, 'edit', 'teacher');
        lmld_add_slot();
        break;

    case 'xoa-slot':
        lms_form_guard($d, $user, $alias, 'delete', 'teacher');
        lmld_delete_slot();
        break;

    case 'buoi':
        lmld_session();
        break;

    case 'luu-buoi':
        lms_form_guard($d, $user, $alias, 'edit', 'teacher');
        lmld_save_session();
        break;

    case 'cham-btvn':
        lms_form_guard($d, $user, $alias, 'edit', 'teacher');
        lmld_grade_student();
        break;

    case 'gui-yeu-cau':
        lms_form_guard($d, $user, $alias, 'edit', 'teacher');
        lmld_create_request();
        break;

    default:
        lmld_calendar();
        break;
}

// ===================== Helper chung =====================

/**
 * Thứ 2 của tuần từ ?week= (validate; fallback thứ 2 tuần hiện tại).
 *
 * @param array $get
 * @return string 'Y-m-d'
 */
function lmld_week_monday(array $get)
{
    $week = isset($get['week']) ? trim((string) $get['week']) : '';
    if ($week !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $week)) {
        $ts = strtotime($week);
        if ($ts !== false && date('N', $ts) === '1') {
            return $week;
        }
        // Ngày không phải thứ 2 → lùi về thứ 2 của tuần đó
        if ($ts !== false) {
            return date('Y-m-d', strtotime('last monday', $ts));
        }
    }
    return date('Y-m-d', strtotime('monday this week'));
}

/**
 * Query string tuần để giữ qua các link/action.
 *
 * @param string $monday
 * @return array
 */
function lmld_week_params($monday)
{
    return array('week' => (string) $monday);
}

/**
 * Nhóm sessions + free slots theo (ngày, giờ) cho grid calendar.
 *
 * @param array $sessionsByDate map date => array(session rows)
 * @param array $freeByDow      map dow => array(array('start','end'))
 * @param string $monday
 * @return array rows: array('hour' => int, 'label' => '7-8', 'cells' => array(7))
 */
function lmld_calendar_cells(array $sessionsByDate, array $freeByDow, $monday)
{
    $startTs = strtotime((string) $monday);
    if ($startTs === false) {
        return array();
    }

    // Khung giờ 7h → 21h (hàng 7-8 … 21-22)
    $rows = array();
    for ($h = 7; $h <= 21; $h++) {
        $cells = array();
        for ($i = 0; $i < 7; $i++) {
            $ts   = strtotime('+' . $i . ' days', $startTs);
            $date = date('Y-m-d', $ts);
            $dow  = (int) date('N', $ts) + 1; // 2..8

            $sessions = array();
            if (isset($sessionsByDate[$date])) {
                foreach ($sessionsByDate[$date] as $row) {
                    $startHour = (int) substr((string) $row['start_time'], 0, 2);
                    if ($startHour === $h) {
                        $sessions[] = $row;
                    }
                }
            }

            $free = array();
            if (isset($freeByDow[$dow])) {
                foreach ($freeByDow[$dow] as $slot) {
                    if ((int) substr((string) $slot['start'], 0, 2) === $h) {
                        $free[] = $slot;
                    }
                }
            }

            $cells[] = array(
                'date'     => $date,
                'dow'      => $dow,
                'sessions' => $sessions,
                'free'     => $free,
            );
        }
        $rows[] = array('hour' => $h, 'label' => $h . '-' . ($h + 1), 'cells' => $cells);
    }
    return $rows;
}

/**
 * Lớp đang dạy của GV + HV active trong từng lớp (cho modal thêm buổi).
 *
 * @param int $teacherId
 * @return array array('classes' => array, 'students' => array(classId => array))
 */
function lmld_teacher_classes($teacherId)
{
    $d = lms_db();

    $classes = $d->safeFetch(
        'select id, class_code, name, class_type, subject_id from db_lms_classes'
        . ' where teacher_id = ? and deleted_at is null order by name',
        array((int) $teacherId)
    );
    if (empty($classes)) {
        return array('classes' => array(), 'students' => array());
    }

    $classIds = array();
    foreach ($classes as $c) {
        $classIds[] = (int) $c['id'];
    }
    $placeholders = implode(', ', array_fill(0, count($classIds), '?'));
    $rows = $d->safeFetch(
        'select cs.class_id, cs.student_id, s.full_name, s.student_code'
        . ' from db_lms_class_students cs'
        . ' join db_lms_students s on s.user_id = cs.student_id'
        . ' where cs.status = ? and cs.class_id in (' . $placeholders . ')'
        . ' order by s.full_name',
        array_merge(array('active'), $classIds)
    );

    $students = array();
    foreach ($rows as $r) {
        $cid = (int) $r['class_id'];
        if (!isset($students[$cid])) {
            $students[$cid] = array();
        }
        $students[$cid][] = $r;
    }
    return array('classes' => $classes, 'students' => $students);
}

/**
 * Buổi sắp tới của 1 lớp (cho modal xin nghỉ).
 *
 * @param int $classId
 * @return array
 */
function lmld_upcoming_sessions($classId)
{
    $d = lms_db();
    return $d->safeFetch(
        'select id, session_date, start_time, end_time from db_lms_sessions'
        . ' where class_id = ? and session_date >= ? and status = ?'
        . ' order by session_date, start_time',
        array((int) $classId, date('Y-m-d'), 'upcoming')
    );
}

// ===================== Action: man (calendar) =====================

function lmld_calendar()
{
    global $title, $template, $weekMonday, $weekLabel, $prevWeek, $nextWeek,
           $profile, $weekNote, $calRows, $freeSlots, $classes, $classStudents,
           $districts, $teacherId, $canEdit, $canDelete, $d, $user, $alias;

    $schedSvc  = new \Lms\ScheduleService($d);
    $profSvc   = new \Lms\TeacherProfileService($d);
    $sessionSvc = new \Lms\SessionService($d);

    $weekMonday = lmld_week_monday($_GET);
    $prevWeek   = date('Y-m-d', strtotime('-7 days', strtotime($weekMonday)));
    $nextWeek   = date('Y-m-d', strtotime('+7 days', strtotime($weekMonday)));
    $weekLabel  = mb_convert_case(date('F', strtotime($weekMonday)), MB_CASE_TITLE, 'UTF-8')
        . ' / ' . date('Y', strtotime($weekMonday));

    $profile  = $profSvc->getProfile($teacherId);
    $weekNote = $profSvc->getWeeklyNote($teacherId, $weekMonday);

    $sessionsByDate = $sessionSvc->listWeek($teacherId, $weekMonday, 1);
    $freeByDow      = $schedSvc->freeSlotsForWeek($teacherId, $weekMonday);
    $freeSlots      = $schedSvc->listFreeSlots($teacherId);
    $calRows        = lmld_calendar_cells($sessionsByDate, $freeByDow, $weekMonday);

    $cs = lmld_teacher_classes($teacherId);
    $classes        = $cs['classes'];
    $classStudents  = $cs['students'];
    $districts      = \Lms\UserService::HCMC_DISTRICTS;

    $canEdit   = lms_check_permission($d, $user, $alias, 'edit', 'teacher');
    $canDelete = lms_check_permission($d, $user, $alias, 'delete', 'teacher');

    $title    = 'Quản lý lịch';
    $template = 'lms-lich-day/hienthi';
}

// ===================== Action: luu-thong-tin =====================

function lmld_save_profile()
{
    global $alias, $teacherId, $d;

    $weekMonday = lmld_week_monday($_GET);

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, '', lmld_week_params($weekMonday)));
        exit;
    }

    $profSvc = new \Lms\TeacherProfileService($d);

    // Chú ý: mỗi lệnh clearErrors() nên đọc errors ngay sau khi gọi để gộp
    $okProfile = $profSvc->updateProfile($teacherId, $_POST);
    $profileErrors = $profSvc->errors();

    $okNote = $profSvc->upsertWeeklyNote(
        $teacherId,
        $weekMonday,
        isset($_POST['week_note']) ? (string) $_POST['week_note'] : ''
    );
    $noteErrors = $profSvc->errors();

    if ($okProfile && $okNote) {
        lms_flash('success', 'Đã cập nhật thông tin và ghi chú tuần.');
        header('Location: ' . lms_url($alias, '', lmld_week_params($weekMonday)));
        exit;
    }

    // Gộp errors của 2 service, flash thông báo đầu tiên
    $errors = array_merge($profileErrors, $noteErrors);
    lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể lưu. Vui lòng kiểm tra lại các trường.');
    header('Location: ' . lms_url($alias, '', lmld_week_params($weekMonday)));
    exit;
}

// ===================== Action: them-slot =====================

function lmld_add_slot()
{
    global $alias, $teacherId, $d;

    $weekMonday = lmld_week_monday($_GET);
    $params     = lmld_week_params($weekMonday);

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, '', $params));
        exit;
    }

    $schedSvc = new \Lms\ScheduleService($d);
    $id = $schedSvc->addFreeSlot(
        $teacherId,
        isset($_POST['day_of_week']) ? (int) $_POST['day_of_week'] : 0,
        isset($_POST['start_time']) ? (string) $_POST['start_time'] : '',
        isset($_POST['end_time']) ? (string) $_POST['end_time'] : ''
    );

    if ($id !== null) {
        lms_flash('success', 'Đã đăng ký lịch trống.');
    } else {
        $errors = $schedSvc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể đăng ký lịch trống.');
    }
    header('Location: ' . lms_url($alias, '', $params));
    exit;
}

// ===================== Action: xoa-slot =====================

function lmld_delete_slot()
{
    global $alias, $teacherId, $d;

    $weekMonday = lmld_week_monday($_GET);
    $params     = lmld_week_params($weekMonday);

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, '', $params));
        exit;
    }

    $schedSvc = new \Lms\ScheduleService($d);
    $slotId = isset($_POST['slot_id']) ? (int) $_POST['slot_id'] : 0;

    if ($schedSvc->deleteFreeSlot($teacherId, $slotId)) {
        lms_flash('success', 'Đã xoá lịch trống.');
    } else {
        $errors = $schedSvc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể xoá lịch trống.');
    }
    header('Location: ' . lms_url($alias, '', $params));
    exit;
}

// ===================== Action: buoi =====================

function lmld_session()
{
    global $title, $template, $session, $students, $summaryLocked, $deadlineLabel,
           $sessionId, $weekMonday, $teacherId, $canEdit, $d, $user, $alias;

    $sessionId  = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $weekMonday = lmld_week_monday($_GET);
    $sessionSvc = new \Lms\SessionService($d);

    $canEdit = lms_check_permission($d, $user, $alias, 'edit', 'teacher');

    $session = $sessionSvc->getSessionForTeacher($sessionId, $teacherId);
    if ($session === null) {
        lms_flash('error', 'Buổi học không tồn tại hoặc không thuộc lớp của bạn.');
        header('Location: ' . lms_url($alias, '', lmld_week_params($weekMonday)));
        exit;
    }

    // BR deadline: chỉ sửa trước 12h trưa ngày kế tiếp
    $deadline = $sessionSvc->computeHwDeadline(
        (string) $session['session_date'],
        isset($session['hw_deadline_time']) ? $session['hw_deadline_time'] : null
    );
    $summaryLocked = $deadline !== null && strcmp(date('Y-m-d H:i:s'), $deadline) > 0;
    $deadlineLabel = $deadline !== null
        ? date('H:i d/m/Y', strtotime($deadline))
        : '';

    // Học viên + trạng thái chấm BTVN
    $students = $sessionSvc->listSessionStudentsForGrading($sessionId, $teacherId);
    foreach ($students as $i => $row) {
        $files = \Lms\UserService::decodeJsonArray(
            isset($row['hw_submission_files']) ? $row['hw_submission_files'] : array()
        );
        $students[$i]['hw_submission_files'] = $files;

        // Hạn chấm = hw_submitted_at + hw_check_hours
        $label = 'Chưa nộp bài';
        $overdue = false;
        if (!empty($row['hw_submitted_at'])) {
            $checkHours = isset($row['hw_check_hours']) ? (int) $row['hw_check_hours'] : 24;
            $dueTs = strtotime((string) $row['hw_submitted_at'] . ' +' . max(0, $checkHours) . ' hours');
            $diff = $dueTs - time();
            if ($diff < 0) {
                $overdue = true;
                $mins = (int) round(abs($diff) / 60);
                $label = $mins >= 60
                    ? 'Hạn chấm: bạn đã chấm trễ ' . (int) round($mins / 60) . ' giờ'
                    : 'Hạn chấm: bạn đã chấm trễ ' . $mins . ' phút';
            } else {
                $hours = (int) round($diff / 3600);
                $label = $hours >= 1
                    ? 'Hạn chấm: bạn còn ' . $hours . ' giờ để chấm'
                    : 'Hạn chấm: bạn còn ' . (int) round($diff / 60) . ' phút để chấm';
            }
        }
        $students[$i]['grade_due_label'] = $label;
        $students[$i]['grade_overdue']   = $overdue;
    }

    $title    = 'Buổi học ' . lmld_session_title($session);
    $template = 'lms-lich-day/buoi';
}

/**
 * Tiêu đề buổi: "Thứ 2 21/7".
 *
 * @param array $session
 * @return string
 */
function lmld_session_title(array $session)
{
    $date = (string) $session['session_date'];
    $ts = strtotime($date);
    if ($ts === false) {
        return $date;
    }
    $dow = (int) date('N', $ts) + 1;
    $wd = isset(\Lms\UserService::WEEKDAYS[$dow]) ? \Lms\UserService::WEEKDAYS[$dow] : '';
    return $wd . ' ' . (int) date('j', $ts) . '/' . (int) date('n', $ts);
}

// ===================== Action: luu-buoi =====================

function lmld_save_session()
{
    global $alias, $teacherId, $d;

    $sessionId = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
    $weekMonday = lmld_week_monday($_GET);

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, 'buoi', array('id' => $sessionId, 'week' => $weekMonday)));
        exit;
    }

    $sessionSvc = new \Lms\SessionService($d);
    $files = isset($_FILES['hw_files']) ? $_FILES['hw_files'] : null;

    $ok = $sessionSvc->updateLessonSummary($sessionId, $teacherId, $_POST, $files);

    if ($ok) {
        lms_flash('success', 'Đã lưu lesson summary và giao bài tập về nhà.');
    } else {
        $errors = $sessionSvc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể lưu lesson summary.');
    }
    header('Location: ' . lms_url($alias, 'buoi', array('id' => $sessionId, 'week' => $weekMonday)));
    exit;
}

// ===================== Action: cham-btvn =====================

function lmld_grade_student()
{
    global $alias, $teacherId, $d;

    $sessionId = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
    $weekMonday = lmld_week_monday($_GET);

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, 'buoi', array('id' => $sessionId, 'week' => $weekMonday)));
        exit;
    }

    $sessionSvc = new \Lms\SessionService($d);
    $ssId = isset($_POST['session_student_id']) ? (int) $_POST['session_student_id'] : 0;
    $files = isset($_FILES['teacher_feedback_files']) ? $_FILES['teacher_feedback_files'] : null;

    $ok = $sessionSvc->gradeHomework($ssId, $teacherId, $_POST, $files);

    if ($ok) {
        lms_flash('success', 'Đã chấm bài tập về nhà.');
    } else {
        $errors = $sessionSvc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể chấm bài.');
    }
    header('Location: ' . lms_url($alias, 'buoi', array('id' => $sessionId, 'week' => $weekMonday)));
    exit;
}

// ===================== Action: gui-yeu-cau =====================

function lmld_create_request()
{
    global $alias, $teacherId, $d;

    $weekMonday = lmld_week_monday($_GET);
    $params     = lmld_week_params($weekMonday);

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, '', $params));
        exit;
    }

    $reqSvc = new \Lms\RequestService($d);

    // change_schedule gửi các cặp slot song song
    $in = $_POST;
    if (isset($in['request_type']) && (string) $in['request_type'] === 'change_schedule') {
        $in['slots'] = lmld_slots_from_post($_POST);
    }

    $id = $reqSvc->createRequest($teacherId, $in);

    if ($id !== null) {
        lms_flash('success', 'Đã gửi yêu cầu. Quản trị viên sẽ duyệt trong thời gian sớm nhất.');
    } else {
        $errors = $reqSvc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể tạo yêu cầu.');
    }
    header('Location: ' . lms_url($alias, '', $params));
    exit;
}

/**
 * Chuyển 3 mảng song song từ form (slot_date[]/slot_start[]/slot_end[])
 * thành mảng các khung giờ.
 *
 * @param array $post
 * @return array
 */
function lmld_slots_from_post($post)
{
    $days   = isset($post['slot_date']) ? (array) $post['slot_date'] : array();
    $starts = isset($post['slot_start']) ? (array) $post['slot_start'] : array();
    $ends   = isset($post['slot_end']) ? (array) $post['slot_end'] : array();

    $out = array();
    $n = max(count($days), count($starts), count($ends));
    for ($i = 0; $i < $n; $i++) {
        $out[] = array(
            'date'  => isset($days[$i]) ? (string) $days[$i] : '',
            'start' => isset($starts[$i]) ? (string) $starts[$i] : '',
            'end'   => isset($ends[$i]) ? (string) $ends[$i] : '',
        );
    }
    return $out;
}
