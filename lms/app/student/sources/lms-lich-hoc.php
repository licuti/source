<?php
/**
 * Student — Lịch học (STU-02, mockup §9 + §4).
 *
 * Action:
 *   - 'man'  (GET) → calendar tuần (7 cột × khung giờ 7-22, §9).
 *   - 'buoi' (GET) → chi tiết buổi học (§4) + nút Nộp BTVN.
 *
 * Chỉ view; nộp bài thực hiện qua POST lms-khoa-hoc/nop-btvn (student can_edit=0).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d    = lms_db();
$user = lms_current_user();
$alias = 'lms-lich-hoc';
$studentId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'buoi':
        lmlh_session();
        break;

    default:
        lmlh_calendar();
        break;
}

// ===================== Helper chung =====================

/**
 * Thứ 2 của tuần từ ?week= (validate; fallback thứ 2 tuần hiện tại).
 *
 * @param array $get
 * @return string 'Y-m-d'
 */
function lmlh_week_monday(array $get)
{
    $week = isset($get['week']) ? trim((string) $get['week']) : '';
    if ($week !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $week)) {
        $ts = strtotime($week);
        if ($ts !== false && date('N', $ts) === '1') {
            return $week;
        }
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
function lmlh_week_params($monday)
{
    return array('week' => (string) $monday);
}

/**
 * Nhóm sessions theo (ngày, giờ) cho grid calendar (pattern lmld_calendar_cells).
 *
 * @param array  $sessionsByDate map date => array(session rows)
 * @param string $monday
 * @return array rows: array('hour' => int, 'label' => '7-8', 'cells' => array(7))
 */
function lmlh_calendar_cells(array $sessionsByDate, $monday)
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
            $ts = strtotime('+' . $i . ' days', $startTs);
            $date = date('Y-m-d', $ts);

            $sessions = array();
            if (isset($sessionsByDate[$date])) {
                foreach ($sessionsByDate[$date] as $row) {
                    $startHour = (int) substr((string) $row['start_time'], 0, 2);
                    if ($startHour === $h) {
                        $sessions[] = $row;
                    }
                }
            }

            $cells[] = array(
                'date'     => $date,
                'sessions' => $sessions,
            );
        }
        $rows[] = array('hour' => $h, 'label' => $h . '-' . ($h + 1), 'cells' => $cells);
    }
    return $rows;
}

/**
 * Class CSS ô lịch theo trạng thái buổi (3 màu, §9 L201-204).
 *
 * @param string $status
 * @return string
 */
function lmlh_cell_class($status)
{
    if ($status === 'cancelled' || $status === 'absent_teacher' || $status === 'absent_student') {
        return 'lms-cal-cancelled';
    }
    if ($status === 'upcoming') {
        return 'lms-cal-upcoming';
    }
    return 'lms-cal-finished';
}

/**
 * Tiêu đề buổi: "Thứ 2 21/7".
 *
 * @param array $session
 * @return string
 */
function lmlh_session_title(array $session)
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

/**
 * Label trạng thái nộp BTVN (mirror lmkh_submission_label cho portal student).
 *
 * @param string $status
 * @return array
 */
function lmlh_submission_label($status)
{
    $map = array(
        'not_submitted'     => array('Chưa nộp BTVN', 'lms-badge-danger'),
        'submitted_on_time' => array('Đã nộp đúng hạn', 'lms-badge-success'),
        'submitted_late'    => array('Đã nộp trễ hạn', 'lms-badge-danger'),
    );
    return isset($map[$status]) ? $map[$status] : array((string) $status, 'lms-badge-secondary');
}

// ===================== Action: man (calendar tuần) =====================

function lmlh_calendar()
{
    global $title, $template, $weekMonday, $weekLabel, $prevWeek, $nextWeek,
           $calRows, $studentId, $d, $alias;

    $sessionSvc = new \Lms\SessionService($d);

    $weekMonday = lmlh_week_monday($_GET);
    $prevWeek = date('Y-m-d', strtotime('-7 days', strtotime($weekMonday)));
    $nextWeek = date('Y-m-d', strtotime('+7 days', strtotime($weekMonday)));
    $weekLabel = mb_convert_case(date('F', strtotime($weekMonday)), MB_CASE_TITLE, 'UTF-8')
        . ' / ' . date('Y', strtotime($weekMonday));

    $sessionsByDate = $sessionSvc->listWeekForStudent($studentId, $weekMonday, 1);
    $calRows = lmlh_calendar_cells($sessionsByDate, $weekMonday);

    $title    = 'Lịch học';
    $template = 'lms-lich-hoc/hienthi';
}

// ===================== Action: buoi (chi tiết buổi học) =====================

function lmlh_session()
{
    global $title, $template, $session, $sessionId, $weekMonday, $studentId, $d, $alias;

    $sessionId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    $weekMonday = lmlh_week_monday($_GET);

    $svc = new \Lms\StudentCourseService($d);

    $session = $svc->getSessionForStudent($sessionId, $studentId);
    if ($session === null) {
        lms_flash('error', 'Buổi học không tồn tại hoặc không thuộc lớp của bạn.');
        header('Location: ' . lms_url($alias, '', lmlh_week_params($weekMonday)));
        exit;
    }

    $deadline = $svc->hwDeadline($session);
    $cd = $svc->deadlineCountdown($deadline);
    $session['deadline'] = $deadline;
    $session['deadline_label'] = $deadline !== null
        ? date('H:i d/m/Y', strtotime($deadline))
        : '';
    $session['deadline_text'] = $cd['text'];
    $session['deadline_overdue'] = $cd['overdue'];
    $session['is_future'] = strcmp((string) $session['session_date'], date('Y-m-d')) > 0;
    $session['is_open'] = !$session['is_future'];
    $session['hw_label'] = lmlh_submission_label(
        isset($session['hw_submission_status']) ? (string) $session['hw_submission_status'] : 'not_submitted'
    );
    $session['graded'] = !empty($session['hw_graded_at']);

    $title    = 'Buổi học ' . lmlh_session_title($session);
    $template = 'lms-lich-hoc/buoi';
}
