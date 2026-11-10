<?php
/**
 * Admin LMS — Quản lý sắp xếp lớp (ADM-04).
 *
 * Mockup: Tai-lieu-phat-trien/admin/Sắp xếp lớp.png
 *         Tai-lieu-phat-trien/admin/Sắp xếp lớp - Chi tiêt.png
 * Tài liệu: Tai-lieu-phat-trien/admin/Tài liệu admin.md §4–§5.
 *
 * Màn hình gồm 3 khối tìm kiếm GV (theo tiêu chí HV, theo lịch trống, theo
 * tiêu chí GV) + bảng kết quả; modal "Chi tiết lịch giáo viên" hiển thị lịch
 * tuần, link tới hồ sơ học viên và ghi chú tuần.
 *
 * Action:
 *  - ''/'man'        (GET)  → màn hình chính; ?teacher_id= mở modal chi tiết.
 *  - 'ajax-hoc-vien' (GET)  → trả JSON tiêu chí của HV theo username (auto-fill).
 *  - 'cap-nhat-ghi-chu' (POST) → lưu ghi chú tuần của GV (CSRF + PRG).
 *  - 'an-giao-vien'  (POST) → ẩn GV khỏi danh sách gợi ý trong phiên (CSRF + PRG).
 *  - 'bo-an-giao-vien' (POST) → hiện lại toàn bộ GV đã ẩn (CSRF + PRG).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d          = lms_db();
$user       = lms_current_user();
$alias      = 'lms-sap-xep-lop';
$matchSvc   = new \Lms\MatchingService($d);
$profileSvc = new \Lms\TeacherProfileService($d);

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'ajax-hoc-vien':
        lms_form_guard($d, $user, $alias, 'view');
        lms_xep_lop_ajax_student($d);
        break;

    case 'cap-nhat-ghi-chu':
        lms_form_guard($d, $user, $alias, 'edit');
        lms_xep_lop_save_note($profileSvc);
        break;

    case 'an-giao-vien':
        lms_form_guard($d, $user, $alias, 'view');
        lms_xep_lop_hide_teacher();
        break;

    case 'bo-an-giao-vien':
        lms_form_guard($d, $user, $alias, 'view');
        lms_xep_lop_show_all_teachers();
        break;

    default:
        lms_xep_lop_list($matchSvc, $profileSvc);
        break;
}

// ===================== Helpers =====================

/**
 * Badge variant theo hạng GV (Xanh > Vàng > Đỏ).
 *
 * @param string $rank
 * @return string
 */
function lms_xep_lop_rank_variant($rank)
{
    switch ((string) $rank) {
        case 'green':
            return 'success';
        case 'gold':
            return 'warning';
        case 'bronze':
        default:
            return 'secondary';
    }
}

/**
 * Nhãn ngắn của hạng GV (mockup: Đỏ / Xanh / Vàng).
 *
 * @param string $rank
 * @return string
 */
function lms_xep_lop_rank_label($rank)
{
    $map = array('green' => 'Xanh', 'gold' => 'Vàng', 'bronze' => 'Đỏ');
    return isset($map[$rank]) ? $map[$rank] : (string) $rank;
}

/**
 * Danh sách môn/chương trình dạy (id => tên).
 *
 * @param LmsDb $d
 * @return array
 */
function lms_xep_lop_subject_map(LmsDb $d)
{
    $map = array();
    foreach ($d->safeFetch('select id, name from db_lms_subjects order by name') as $row) {
        $map[(int) $row['id']] = (string) $row['name'];
    }
    return $map;
}

/**
 * Tách quận/phường của HV (có thể là JSON hoặc chuỗi phân cách).
 *
 * @param mixed $raw
 * @return array
 */
function lms_xep_lop_student_districts($raw)
{
    $decoded = \Lms\UserService::decodeJsonArray($raw);
    if ($decoded !== array()) {
        return array_values(array_filter(array_map('trim', array_map('strval', $decoded))));
    }
    $parts = preg_split('/\s*[,;]\s*/', trim((string) $raw));
    return is_array($parts) ? array_values(array_filter($parts, 'strlen')) : array();
}

/**
 * Đọc các dòng lịch trống từ query string (sched_day[i] + sched_from[i][] + sched_to[i][]).
 *
 * Mỗi dòng: day (2..8), ranges (mảng start/end thô để render lại).
 *
 * @return array
 */
function lms_xep_lop_parse_schedule_rows()
{
    $days  = isset($_GET['sched_day']) && is_array($_GET['sched_day']) ? $_GET['sched_day'] : array();
    $froms = isset($_GET['sched_from']) && is_array($_GET['sched_from']) ? $_GET['sched_from'] : array();
    $tos   = isset($_GET['sched_to']) && is_array($_GET['sched_to']) ? $_GET['sched_to'] : array();

    $rows = array();
    foreach ($days as $i => $day) {
        $day = (int) $day;

        $fromList = isset($froms[$i]) && is_array($froms[$i]) ? $froms[$i] : array();
        $toList   = isset($tos[$i]) && is_array($tos[$i]) ? $tos[$i] : array();
        $ranges = array();
        foreach ($fromList as $j => $from) {
            $ranges[] = array(
                'start' => substr(trim((string) $from), 0, 5),
                'end'   => substr(trim((string) (isset($toList[$j]) ? $toList[$j] : '')), 0, 5),
            );
        }

        $rows[] = array(
            'day'    => isset(\Lms\UserService::WEEKDAYS[$day]) ? $day : 0,
            'ranges' => $ranges,
        );
    }
    return $rows;
}

/**
 * Chuyển các dòng lịch trống đã nhập thành tiêu chí lọc cho MatchingService.
 *
 * @param array $rows
 * @return array
 */
function lms_xep_lop_schedule_criteria(array $rows)
{
    $criteria = array();
    foreach ($rows as $row) {
        if (empty($row['day'])) {
            continue;
        }
        $ranges = array();
        foreach ($row['ranges'] as $range) {
            $start = isset($range['start']) ? $range['start'] : '';
            $end   = isset($range['end']) ? $range['end'] : '';
            if (\Lms\UserService::isValidTime($start) && \Lms\UserService::isValidTime($end)
                && strcmp($start, $end) < 0
            ) {
                $ranges[] = array('start' => $start, 'end' => $end);
            }
        }
        $criteria[] = array('day' => (int) $row['day'], 'ranges' => $ranges);
    }
    return $criteria;
}

/**
 * Query hiện tại (bỏ teacher_id + tham số router) để dựng link/hidden input.
 *
 * @return array
 */
function lms_xep_lop_current_query()
{
    $params = $_GET;
    unset($params['teacher_id'], $params['lms_route'], $params['p'], $params['a']);
    return $params;
}

/**
 * Lấy query filters từ hidden input return_query (do server sinh ra).
 *
 * @return array
 */
function lms_xep_lop_query_from_post()
{
    $params = array();
    if (!empty($_POST['return_query'])) {
        parse_str((string) $_POST['return_query'], $params);
        if (!is_array($params)) {
            $params = array();
        }
    }
    unset($params['teacher_id'], $params['lms_route'], $params['p'], $params['a']);
    return $params;
}

/**
 * Định dạng "Thứ 4 05-08-2026 15:27" cho cột Lần cuối cập nhật.
 *
 * @param string $datetime
 * @return string
 */
function lms_xep_lop_format_updated($datetime)
{
    $ts = strtotime((string) $datetime);
    if ($ts === false) {
        return '—';
    }
    $day = (int) date('N', $ts) + 1;
    $label = isset(\Lms\UserService::WEEKDAYS[$day]) ? \Lms\UserService::WEEKDAYS[$day] : '';
    return trim($label . ' ' . date('d-m-Y H:i', $ts));
}

/**
 * Tìm HV theo username (dùng cho auto-fill + lọc theo tiêu chí HV).
 *
 * @param LmsDb  $d
 * @param string $username
 * @return array
 */
function lms_xep_lop_find_student(LmsDb $d, $username)
{
    $row = $d->safeFetchOne(
        'select s.user_id, s.student_code, s.full_name, s.gender,'
        . ' s.subject_id, s.district, s.learning_type, s.preferred_teacher_gender,'
        . ' s.free_time_slots, u.username'
        . ' from db_lms_students s'
        . ' join db_lms_users u on u.id = s.user_id'
        . ' where u.username = ? and u.role = ? limit 1',
        array((string) $username, 'student')
    );
    if (empty($row)) {
        return array();
    }
    $row['free_time_slots'] = \Lms\UserService::decodeJsonArray(
        isset($row['free_time_slots']) ? $row['free_time_slots'] : array()
    );
    $row['districts'] = lms_xep_lop_student_districts(isset($row['district']) ? $row['district'] : '');
    return $row;
}

// ===================== Màn hình chính =====================

function lms_xep_lop_list(\Lms\MatchingService $matchSvc, \Lms\TeacherProfileService $profileSvc)
{
    global $title, $template, $items, $total, $filters, $schedRows, $options,
           $detail, $weeklyNote, $weekStart, $queryParams, $hiddenCount,
           $canEdit, $autoError, $studentInfo, $autofillFor, $alias, $d, $user;

    $options = array(
        'subjects'       => lms_xep_lop_subject_map($d),
        'districts'      => \Lms\UserService::HCMC_DISTRICTS,
        'genders'        => \Lms\UserService::GENDERS,
        'statuses'       => \Lms\UserService::TEACHER_STATUSES,
        'learning_types' => \Lms\UserService::LEARNING_TYPES,
    );

    $filters = array(
        'use_criteria'     => isset($_GET['use_criteria']) && (string) $_GET['use_criteria'] === '1',
        'use_schedule'     => isset($_GET['use_schedule']) && (string) $_GET['use_schedule'] === '1',
        'student_username' => isset($_GET['student_username']) ? trim((string) $_GET['student_username']) : '',
        's_gender'         => isset($_GET['s_gender']) ? trim((string) $_GET['s_gender']) : '',
        's_subject_id'     => isset($_GET['s_subject_id']) ? (int) $_GET['s_subject_id'] : 0,
        's_districts'      => isset($_GET['s_districts']) && is_array($_GET['s_districts'])
            ? array_values(array_filter(array_map('trim', array_map('strval', $_GET['s_districts']))))
            : array(),
        's_learning_type'  => isset($_GET['s_learning_type']) ? trim((string) $_GET['s_learning_type']) : '',
        'g_q'              => isset($_GET['g_q']) ? trim((string) $_GET['g_q']) : '',
        'g_gender'         => isset($_GET['g_gender']) ? trim((string) $_GET['g_gender']) : '',
        'g_subject_id'     => isset($_GET['g_subject_id']) ? (int) $_GET['g_subject_id'] : 0,
        'g_districts'      => isset($_GET['g_districts']) && is_array($_GET['g_districts'])
            ? array_values(array_filter(array_map('trim', array_map('strval', $_GET['g_districts']))))
            : array(),
        'g_status'         => isset($_GET['g_status']) ? trim((string) $_GET['g_status']) : '',
        'sort'             => isset($_GET['sort']) ? trim((string) $_GET['sort']) : 'rank_desc',
        'student_filled'   => isset($_GET['student_filled']) ? trim((string) $_GET['student_filled']) : '',
    );

    $sortOptions = array('rank_desc', 'rank_asc', 'updated_desc', 'updated_asc');
    if (!in_array($filters['sort'], $sortOptions, true)) {
        $filters['sort'] = 'rank_desc';
    }
    if (!isset($options['genders'][$filters['s_gender']])) {
        $filters['s_gender'] = '';
    }
    if (!isset($options['genders'][$filters['g_gender']])) {
        $filters['g_gender'] = '';
    }
    if (!isset($options['statuses'][$filters['g_status']])) {
        $filters['g_status'] = '';
    }
    if (!isset($options['learning_types'][$filters['s_learning_type']])) {
        $filters['s_learning_type'] = '';
    }

    // Lịch trống: dòng đang nhập + tiêu chí lọc.
    $hasScheduleInput = isset($_GET['sched_day']);
    $schedRows = lms_xep_lop_parse_schedule_rows();
    if ($schedRows === array() && !$hasScheduleInput) {
        $schedRows[] = array(
            'day'    => 2,
            'ranges' => array(array('start' => '', 'end' => '')),
        );
    }
    $scheduleCriteria = $filters['use_schedule'] && $hasScheduleInput
        ? lms_xep_lop_schedule_criteria($schedRows)
        : array();

    // Auto-fill tiêu chí HV từ username — chỉ 1 lần cho mỗi username để admin
    // vẫn xoá/sửa được tiêu chí ở các lần tìm sau (không ghi ngược hồ sơ HV).
    $studentInfo = array();
    $autoError = '';
    $autofillFor = $filters['student_filled'];
    if ($filters['use_criteria'] && $filters['student_username'] !== ''
        && $autofillFor !== $filters['student_username']
    ) {
        $studentInfo = lms_xep_lop_find_student($d, $filters['student_username']);
        if ($studentInfo === array()) {
            $autoError = 'Không tìm thấy tài khoản học viên "' . $filters['student_username'] . '".';
        } else {
            $autofillFor = $filters['student_username'];
            if ($filters['s_gender'] === '') {
                $preferred = isset($studentInfo['preferred_teacher_gender'])
                    ? (string) $studentInfo['preferred_teacher_gender']
                    : '';
                $filters['s_gender'] = $preferred !== '' ? $preferred : (string) $studentInfo['gender'];
            }
            if ($filters['s_subject_id'] <= 0) {
                $filters['s_subject_id'] = (int) $studentInfo['subject_id'];
            }
            if ($filters['s_districts'] === array()) {
                $filters['s_districts'] = $studentInfo['districts'];
            }
            if ($filters['s_learning_type'] === '') {
                $filters['s_learning_type'] = (string) $studentInfo['learning_type'];
            }
        }
    }

    $genders = array();
    if ($filters['use_criteria'] && $filters['s_gender'] !== '') {
        $genders[] = $filters['s_gender'];
    }
    if ($filters['g_gender'] !== '') {
        $genders[] = $filters['g_gender'];
    }

    $subjectIds = array();
    if ($filters['use_criteria'] && $filters['s_subject_id'] > 0) {
        $subjectIds[] = $filters['s_subject_id'];
    }
    if ($filters['g_subject_id'] > 0) {
        $subjectIds[] = $filters['g_subject_id'];
    }

    $districtGroups = array();
    if ($filters['use_criteria'] && $filters['s_districts'] !== array()) {
        $districtGroups[] = $filters['s_districts'];
    }
    if ($filters['g_districts'] !== array()) {
        $districtGroups[] = $filters['g_districts'];
    }

    $res = $matchSvc->searchTeachers(array(
        'q'               => $filters['g_q'],
        'genders'         => $genders,
        'subject_ids'     => $subjectIds,
        'district_groups' => $districtGroups,
        'status'          => $filters['g_status'],
        'online'          => $filters['use_criteria'] && $filters['s_learning_type'] === 'online',
        'schedule'        => $scheduleCriteria,
        'sort'            => $filters['sort'],
    ));

    $hiddenIds = isset($_SESSION['lms_xep_lop_hidden']) && is_array($_SESSION['lms_xep_lop_hidden'])
        ? $_SESSION['lms_xep_lop_hidden']
        : array();
    $hiddenMap = array();
    foreach ($hiddenIds as $hid) {
        $hiddenMap[(int) $hid] = true;
    }

    $items = array();
    foreach ($res['items'] as $row) {
        if (isset($hiddenMap[(int) $row['teacher_id']])) {
            continue;
        }
        $items[] = $row;
    }
    $total = count($items);
    $hiddenCount = count($hiddenMap);

    $queryParams = lms_xep_lop_current_query();

    // Modal chi tiết lịch GV.
    $detail = array();
    $weeklyNote = array();
    $weekStart = date('Y-m-d', strtotime('-' . ((int) date('N') - 1) . ' days'));
    $teacherId = isset($_GET['teacher_id']) ? (int) $_GET['teacher_id'] : 0;
    if ($teacherId > 0) {
        $detail = $matchSvc->teacherWeekDetail($teacherId);
        if (empty($detail)) {
            lms_flash('error', 'Giáo viên không tồn tại.');
        } else {
            $weeklyNote = $profileSvc->getWeeklyNote($teacherId, $weekStart);
        }
    }

    $canEdit = lms_check_permission($d, $user, $alias, 'edit', 'admin');

    $title    = 'Quản lý sắp xếp lớp';
    $template = 'lms-sap-xep-lop/hienthi';
}

// ===================== AJAX: auto-fill tiêu chí HV =====================

function lms_xep_lop_ajax_student(LmsDb $d)
{
    header('Content-Type: application/json; charset=utf-8');

    $username = isset($_GET['username']) ? trim((string) $_GET['username']) : '';
    if ($username === '') {
        echo json_encode(array('ok' => false, 'error' => 'Vui lòng nhập username học viên.'));
        exit;
    }

    $student = lms_xep_lop_find_student($d, $username);
    if ($student === array()) {
        echo json_encode(array('ok' => false, 'error' => 'Không tìm thấy tài khoản học viên.'));
        exit;
    }

    $preferred = isset($student['preferred_teacher_gender'])
        ? (string) $student['preferred_teacher_gender']
        : '';

    echo json_encode(array(
        'ok'      => true,
        'student' => array(
            'username'    => (string) $student['username'],
            'student_code' => (string) $student['student_code'],
            'full_name'   => (string) $student['full_name'],
            'gender'      => $preferred !== '' ? $preferred : (string) $student['gender'],
            'subject_id'  => (int) $student['subject_id'],
            'districts'   => $student['districts'],
            'learning_type' => (string) $student['learning_type'],
        ),
    ));
    exit;
}

// ===================== Ghi chú tuần =====================

function lms_xep_lop_save_note(\Lms\TeacherProfileService $profileSvc)
{
    global $alias;

    $returnQuery = lms_xep_lop_query_from_post();

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, '', $returnQuery));
        exit;
    }

    $teacherId = isset($_POST['teacher_id']) ? (int) $_POST['teacher_id'] : 0;
    $weekStart = isset($_POST['week_start']) ? trim((string) $_POST['week_start']) : '';
    $note = isset($_POST['note']) ? (string) $_POST['note'] : '';

    $back = $returnQuery;
    if ($teacherId > 0) {
        $back['teacher_id'] = $teacherId;
    }

    if ($teacherId <= 0) {
        lms_flash('error', 'Giáo viên không hợp lệ.');
        header('Location: ' . lms_url($alias, '', $back));
        exit;
    }

    if (!$profileSvc->upsertWeeklyNote($teacherId, $weekStart, $note)) {
        $errors = $profileSvc->errors();
        lms_flash('error', $errors !== array() ? (string) reset($errors) : 'Không thể lưu ghi chú.');
    } else {
        lms_flash('success', 'Đã cập nhật ghi chú tuần cho giáo viên.');
    }

    header('Location: ' . lms_url($alias, '', $back));
    exit;
}

// ===================== Ẩn / hiện GV trong danh sách gợi ý =====================

function lms_xep_lop_hide_teacher()
{
    global $alias;

    $returnQuery = lms_xep_lop_query_from_post();

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, '', $returnQuery));
        exit;
    }

    $teacherId = isset($_POST['teacher_id']) ? (int) $_POST['teacher_id'] : 0;
    if ($teacherId <= 0) {
        lms_flash('error', 'Giáo viên không hợp lệ.');
        header('Location: ' . lms_url($alias, '', $returnQuery));
        exit;
    }

    if (!isset($_SESSION['lms_xep_lop_hidden']) || !is_array($_SESSION['lms_xep_lop_hidden'])) {
        $_SESSION['lms_xep_lop_hidden'] = array();
    }
    $_SESSION['lms_xep_lop_hidden'][$teacherId] = $teacherId;

    lms_flash('success', 'Đã ẩn giáo viên khỏi danh sách gợi ý trong phiên làm việc này.');
    header('Location: ' . lms_url($alias, '', $returnQuery));
    exit;
}

function lms_xep_lop_show_all_teachers()
{
    global $alias;

    $returnQuery = lms_xep_lop_query_from_post();

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        header('Location: ' . lms_url($alias, '', $returnQuery));
        exit;
    }

    unset($_SESSION['lms_xep_lop_hidden']);
    lms_flash('success', 'Đã hiện lại toàn bộ giáo viên đã ẩn.');
    header('Location: ' . lms_url($alias, '', $returnQuery));
    exit;
}
