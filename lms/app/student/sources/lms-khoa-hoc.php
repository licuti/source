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

    if (!is_array($session)) {
        header('Location: ' . lms_url($alias));
        return;
    }
    $sessionId = (int) $session['id'];

    if (isset($_POST['back']) && (string) $_POST['back'] === 'buoi') {
        header('Location: ' . lms_url('lms-lich-hoc', 'buoi', array('id' => $sessionId)));
        return;
    }

    $classId = (int) $session['class_id'];
    $topicId = 0;
    $topicIds = isset($session['topic_ids']) ? $session['topic_ids'] : array();
    if (!empty($topicIds)) {
        $topicId = (int) $topicIds[0];
    } elseif (!empty($session['topic_id'])) {
        $topicId = (int) $session['topic_id'];
    }

    if ($topicId > 0) {
        header('Location: ' . lms_url($alias, 'chuyen-de', array('class_id' => $classId, 'topic_id' => $topicId)));
        return;
    }
    header('Location: ' . lms_url($alias, 'lop', array('class_id' => $classId)));
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
    global $title, $template, $groups, $studentId, $d;

    $svc = new \Lms\StudentCourseService($d);
    $classes = $svc->listMyClasses($studentId);

    // Group theo môn (danh mục, §3.1)
    $groups = array();
    foreach ($classes as $c) {
        $subject = (string) $c['subject_name'];
        if ($subject === '') {
            $subject = 'Khác';
        }
        if (!isset($groups[$subject])) {
            $groups[$subject] = array();
        }
        $groups[$subject][] = $c;
    }

    $title    = 'Các khoá học';
    $template = 'lms-khoa-hoc/hienthi';
}

// ===================== Action: lop (chi tiết lớp + chuyên đề) =====================

function lmkh_class()
{
    global $title, $template, $class, $topics, $tests, $studentId, $d, $alias;

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

    // Điểm test/minitest gần nhất theo topic (cho badge §5)
    $scoreMap = array();
    foreach ($tests as $t) {
        if (empty($t['submitted_at']) || $t['score_y_percent'] === null) {
            continue;
        }
        $tid = (int) $t['topic_id'];
        $type = (string) $t['test_type'];
        $score = (float) $t['score_y_percent'];
        if (!isset($scoreMap[$tid][$type]) || $score > $scoreMap[$tid][$type]) {
            $scoreMap[$tid][$type] = $score;
        }
    }
    foreach ($topics as $i => $topic) {
        $tid = (int) $topic['id'];
        $topics[$i]['best_test_score'] = isset($scoreMap[$tid]['test']) ? $scoreMap[$tid]['test'] : null;
        $topics[$i]['best_minitest_score'] = isset($scoreMap[$tid]['minitest']) ? $scoreMap[$tid]['minitest'] : null;
    }

    $title    = 'Lớp ' . (string) $class['name'];
    $template = 'lms-khoa-hoc/lop';
}

// ===================== Action: chuyen-de (buổi học + nộp bài) =====================

function lmkh_topic()
{
    global $title, $template, $class, $topic, $sessions, $tests, $studentId, $d, $alias;

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

    $sessions = $svc->listSessionsForTopic($classId, $topicId, $studentId);
    lmkh_prepare_sessions($svc, $sessions);

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

    $title    = 'Chuyên đề: ' . (string) $topic['name'];
    $template = 'lms-khoa-hoc/chuyen-de';
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
