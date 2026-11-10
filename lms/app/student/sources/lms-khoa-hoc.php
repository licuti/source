<?php
/**
 * Student — Các khoá học (STU-01/02/03, mockup §3 + §4 + §5 + §6 + §8).
 *
 * Action:
 *   - 'man'       (GET)  → list class cards + lộ trình (§3.1).
 *   - 'lop'       (GET)  → chi tiết lớp: grid chuyên đề + điểm test (§5 + §3.2).
 *   - 'chuyen-de' (GET)  → danh sách buổi học + nộp BTVN + Test/Minitest (§4 + §6/§8).
 *   - 'nop-btvn'  (POST) → nộp bài tập về nhà (CSRF + row-level service).
 *   - 'nop-test'  (POST) → nộp bài test/minitest (CSRF + row-level service).
 *
 * Student can_edit = 0 → POST KHÔNG dùng lms_form_guard(..., 'edit'); chỉ cần
 * lms_csrf_verify() + kiểm tra view + row-level trong StudentCourseService.
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d    = lms_db();
$user = lms_current_user();
$alias = 'lms-khoa-hoc';
$studentId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;

$action = isset($a) ? (string) $a : 'man';

switch ($action) {
    case 'lop':
        lmkh_class();
        break;

    case 'chuyen-de':
        lmkh_topic();
        break;

    case 'nop-btvn':
        lmkh_submit_hw();
        break;

    case 'nop-test':
        lmkh_submit_test();
        break;

    default:
        lmkh_list();
        break;
}

// ===================== Helper chung =====================

/**
 * Label trạng thái nộp BTVN (hw_submission_status) cho HV.
 *
 * @param string $status
 * @return array array(label, badge class)
 */
function lmkh_submission_label($status)
{
    $map = array(
        'not_submitted'     => array('Chưa nộp BTVN', 'lms-badge-danger'),
        'submitted_on_time' => array('Đã nộp đúng hạn', 'lms-badge-success'),
        'submitted_late'    => array('Đã nộp trễ hạn', 'lms-badge-danger'),
    );
    return isset($map[$status]) ? $map[$status] : array((string) $status, 'lms-badge-secondary');
}

/**
 * Tuỳ chọn upload cho lms_save_files (cli khi chạy smoke harness).
 *
 * @return array
 */
function lmkh_save_opts()
{
    return PHP_SAPI === 'cli' ? array('cli' => true) : array();
}

/**
 * Tiêu đề buổi: "Thứ 2 21/7".
 *
 * @param array $session
 * @return string
 */
function lmkh_session_title(array $session)
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
 * Redirect về trang chứa buổi học sau khi nộp BTVN (theo hidden field 'back').
 *
 * @param array|null $session row từ getSessionForStudent()
 * @return void (luôn header + exit tại caller)
 */
function lmkh_hw_redirect($session)
{
    global $alias;

    if (!is_array($session) || empty($session['id'])) {
        header('Location: ' . lms_url($alias));
        return;
    }
    $sessionId = (int) $session['id'];
    header('Location: ' . lms_url('lms-lich-hoc', 'buoi', array('id' => $sessionId)));
}

/**
 * Chuẩn bị deadline + trạng thái cho danh sách buổi (dùng cho chuyen-de + buoi).
 *
 * @param \Lms\StudentCourseService $svc
 * @param array                     $sessions
 * @return void (sửa $sessions tại chỗ)
 */
function lmkh_prepare_sessions(\Lms\StudentCourseService $svc, array &$sessions)
{
    $today = date('Y-m-d');
    foreach ($sessions as $i => $s) {
        $future = strcmp((string) $s['session_date'], $today) > 0;
        $deadline = $svc->hwDeadline($s);
        $cd = $svc->deadlineCountdown($deadline);

        $sessions[$i]['is_future'] = $future;
        $sessions[$i]['is_open'] = !$future;
        $sessions[$i]['deadline'] = $deadline;
        $sessions[$i]['deadline_label'] = $deadline !== null
            ? date('H:i d/m/Y', strtotime($deadline))
            : '';
        $sessions[$i]['deadline_text'] = $cd['text'];
        $sessions[$i]['deadline_overdue'] = $cd['overdue'];
        $sessions[$i]['hw_label'] = lmkh_submission_label(
            isset($s['hw_submission_status']) ? (string) $s['hw_submission_status'] : 'not_submitted'
        );
        $sessions[$i]['graded'] = !empty($s['hw_graded_at']);
    }
}

// ===================== Action: man (danh sách lớp + lộ trình) =====================

function lmkh_list()
{
    global $title, $template, $classes, $studentId, $d, $hidePageTitle;

    $svc = new \Lms\StudentCourseService($d);
    $classes = $svc->listMyClasses($studentId);

    $classIds = array();
    foreach ($classes as $class) {
        $classIds[] = (int) $class['id'];
    }
    $scheduleMap = array();
    if ($classIds !== array()) {
        $reviewSvc = new \Lms\MonthlyReviewService($d);
        $scheduleMap = $reviewSvc->scheduleRowsMap($classIds);
    }

    $studentRow = $d->safeFetchOne(
        'select grade from db_lms_students where user_id = ? limit 1',
        array($studentId)
    );
    $studentGrade = isset($studentRow['grade']) ? trim((string) $studentRow['grade']) : '';

    foreach ($classes as $i => $class) {
        $classId = (int) $class['id'];
        $cycleId = isset($class['cycle_id']) ? (int) $class['cycle_id'] : 0;

        $classes[$i]['schedule_rows'] = isset($scheduleMap[$classId]) ? $scheduleMap[$classId] : array();
        $classes[$i]['grade'] = $studentGrade;

        $hwRow = $d->safeFetchOne(
            'select s.session_date, s.hw_content, ss.hw_submitted_at, ss.hw_graded_at'
            . ' from db_lms_sessions s'
            . ' left join db_lms_session_students ss'
            . '   on ss.session_id = s.id and ss.student_id = ?'
            . ' where s.class_id = ? and (s.cycle_id = ? or ? = 0)'
            . '   and s.hw_content is not null and s.hw_content <> \'\''
            . ' order by s.session_date desc, s.start_time desc limit 1',
            array($studentId, $classId, $cycleId, $cycleId)
        );

        if (empty($hwRow)) {
            $classes[$i]['hw_label'] = 'Giáo viên chưa giao BTVN';
            $classes[$i]['hw_class'] = 'text-danger';
        } elseif (!empty($hwRow['hw_graded_at'])) {
            $classes[$i]['hw_label'] = 'Giáo viên đã chấm BTVN';
            $classes[$i]['hw_class'] = 'text-success';
        } elseif (!empty($hwRow['hw_submitted_at'])) {
            $classes[$i]['hw_label'] = 'Học sinh đã nộp BTVN';
            $classes[$i]['hw_class'] = 'text-success';
        } else {
            $classes[$i]['hw_label'] = 'Học sinh chưa nộp BTVN';
            $classes[$i]['hw_class'] = 'text-danger';
        }
    }

    $title         = 'Các khoá học của tôi';
    $template      = 'lms-khoa-hoc/hienthi';
    $hidePageTitle = true;
}

// ===================== Action: lop (chi tiết lớp + chuyên đề) =====================

function lmkh_class()
{
    global $title, $template, $class, $topics, $topicGroups, $sessions, $tests, $studentId, $d, $alias, $hidePageTitle;

    $classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;

    $svc = new \Lms\StudentCourseService($d);
    $class = $svc->getClassForStudent($classId, $studentId);
    if ($class === null) {
        lms_flash('error', 'Lớp học không tồn tại hoặc bạn không đang học lớp này.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $data = $svc->listTopicsForClass($classId, $studentId);
    $topics = $data['topics'];
    $tests = $data['tests'];

    // Nhóm chuyên đề theo Danh mục môn học (Math Problems, Math IQ, ...)
    $topicGroups = array();
    foreach ($topics as $topic) {
        $groupName = isset($topic['subject_name']) && trim((string) $topic['subject_name']) !== ''
            ? (string) $topic['subject_name']
            : 'Chuyên đề';
        $topicGroups[$groupName][] = $topic;
    }

    // Danh sách buổi học kèm nội dung BTVN & nhận xét
    $sessions = $svc->listSessionsForClass($classId, $studentId);
    lmkh_prepare_sessions($svc, $sessions);

    $title    = (string) $class['name'];
    $template = 'lms-khoa-hoc/lop';
    $hidePageTitle = true;
}

// ===================== Action: chuyen-de (buổi học + nộp bài) =====================

function lmkh_topic()
{
    global $title, $template, $class, $topic, $tests, $studentId, $d, $alias, $hidePageTitle;

    $classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
    $topicId = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : 0;

    $svc = new \Lms\StudentCourseService($d);
    $class = $svc->getClassForStudent($classId, $studentId);
    if ($class === null) {
        lms_flash('error', 'Lớp học không tồn tại hoặc bạn không đang học lớp này.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    // Chuyên đề phải thuộc môn của lớp
    $topic = null;
    $data = $svc->listTopicsForClass($classId, $studentId);
    foreach ($data['topics'] as $t) {
        if ((int) $t['id'] === $topicId) {
            $topic = $t;
            break;
        }
    }
    if ($topic === null) {
        lms_flash('error', 'Chuyên đề không thuộc lớp học này.');
        header('Location: ' . lms_url($alias, 'lop', array('class_id' => $classId)));
        exit;
    }

    $tests = $svc->listTestsForTopic($classId, $topicId, $studentId);
    foreach ($tests as $i => $t) {
        $cd = $svc->deadlineCountdown(isset($t['deadline']) ? (string) $t['deadline'] : null);
        $tests[$i]['deadline_text'] = $cd['text'];
        $tests[$i]['deadline_overdue'] = $cd['overdue'];
        $tests[$i]['deadline_label'] = !empty($t['deadline'])
            ? date('H:i d/m/Y', strtotime((string) $t['deadline']))
            : '';
        $tests[$i]['sub_status_label'] = lmkh_submission_label(
            isset($t['submission_status']) ? (string) $t['submission_status'] : 'not_submitted'
        );
    }

    $title         = 'Chuyên đề: ' . (string) $topic['name'];
    $template      = 'lms-khoa-hoc/chuyen-de';
    $hidePageTitle = true;
}

// ===================== Action: nop-btvn (POST) =====================

function lmkh_submit_hw()
{
    global $alias, $studentId, $d, $user;

    $sessionId = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        $svc0 = new \Lms\StudentCourseService($d);
        lmkh_hw_redirect($svc0->getSessionForStudent($sessionId, $studentId));
        exit;
    }

    // Student can_edit = 0 → chỉ kiểm tra view (không dùng lms_form_guard 'edit')
    if (!lms_check_permission($d, $user, $alias, 'view', 'student')) {
        lms_flash('error', 'Bạn không có quyền truy cập mục này.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $svc = new \Lms\StudentCourseService($d);
    $session = $svc->getSessionForStudent($sessionId, $studentId);
    if ($session === null) {
        lms_flash('error', 'Buổi học không tồn tại hoặc không thuộc lớp của bạn.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $files = isset($_FILES['hw_files']) ? $_FILES['hw_files'] : null;
    $replace = !empty($_POST['replace_files']);

    $ok = $svc->submitHomework($sessionId, $studentId, $files, $replace, lmkh_save_opts());

    if ($ok) {
        lms_flash('success', 'Đã nộp bài tập về nhà.');
    } else {
        $errors = $svc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể nộp bài tập về nhà.');
    }

    lmkh_hw_redirect($session);
    exit;
}

// ===================== Action: nop-test (POST) =====================

function lmkh_submit_test()
{
    global $alias, $studentId, $d, $user;

    $testId = isset($_POST['test_id']) ? (int) $_POST['test_id'] : 0;

    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ. Vui lòng tải lại trang và thử lại.');
        $svc0 = new \Lms\StudentCourseService($d);
        $test0 = $svc0->getTestForStudent($testId, $studentId);
        if ($test0 === null) {
            header('Location: ' . lms_url($alias));
        } else {
            header('Location: ' . lms_url(
                $alias,
                'chuyen-de',
                array('class_id' => (int) $test0['class_id'], 'topic_id' => (int) $test0['topic_id'])
            ));
        }
        exit;
    }

    if (!lms_check_permission($d, $user, $alias, 'view', 'student')) {
        lms_flash('error', 'Bạn không có quyền truy cập mục này.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $svc = new \Lms\StudentCourseService($d);
    $test = $svc->getTestForStudent($testId, $studentId);
    if ($test === null) {
        lms_flash('error', 'Bài test không tồn tại hoặc không thuộc lớp của bạn.');
        header('Location: ' . lms_url($alias));
        exit;
    }

    $files = isset($_FILES['test_files']) ? $_FILES['test_files'] : null;

    $ok = $svc->submitTest($testId, $studentId, $files, lmkh_save_opts());

    if ($ok) {
        lms_flash('success', 'Đã nộp bài test.');
    } else {
        $errors = $svc->errors();
        lms_flash('error', !empty($errors) ? reset($errors) : 'Không thể nộp bài test.');
    }

    header('Location: ' . lms_url(
        $alias,
        'chuyen-de',
        array('class_id' => (int) $test['class_id'], 'topic_id' => (int) $test['topic_id'])
    ));
    exit;
}
