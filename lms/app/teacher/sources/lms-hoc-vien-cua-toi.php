<?php
/**
 * Teacher — Học viên của tôi (T8).
 *
 * Hiển thị học viên đang học trong các lớp của giáo viên hiện tại dạng card
 * full-width dùng chung .lmnx-card: avatar, tên lớp/tên HV, khối + môn, lịch
 * cố định và nhãn trạng thái BTVN. Bấm vào thẻ → trang chi tiết khóa học.
 *
 * Row-level: chỉ dữ liệu học viên của các lớp GV đang dạy.
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d    = lms_db();
$user = lms_current_user();

$teacherId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;
$action = isset($a) ? (string) $a : 'man';

if (!function_exists('lms_hv_group_visible_sessions')) {
    /**
     * Buổi học hiển thị ở trang lớp nhóm: tuần hiện tại + buổi kế tiếp (tối đa 3).
     *
     * @param array $sessions
     * @return array
     */
    function lms_hv_group_visible_sessions(array $sessions)
    {
        $today = date('Y-m-d');
        $monday = date('Y-m-d', strtotime('monday this week'));
        $sunday = date('Y-m-d', strtotime($monday . ' +6 days'));
        $visible = array();
        foreach ($sessions as $session) {
            if ((string) $session['status'] === 'cancelled') {
                continue;
            }
            $date = (string) $session['session_date'];
            if (strcmp($date, $monday) >= 0 && strcmp($date, $sunday) <= 0) {
                $visible[] = $session;
            }
        }
        if (count($visible) < 3) {
            $seen = array();
            foreach ($visible as $session) {
                $seen[(int) $session['id']] = true;
            }
            foreach ($sessions as $session) {
                if ((string) $session['status'] === 'cancelled' || strcmp((string) $session['session_date'], $today) < 0) {
                    continue;
                }
                if (isset($seen[(int) $session['id']])) {
                    continue;
                }
                $visible[] = $session;
                $seen[(int) $session['id']] = true;
                if (count($visible) >= 3) {
                    break;
                }
            }
        }
        if ($visible === array()) {
            $nonCancelled = array();
            foreach ($sessions as $session) {
                if ((string) $session['status'] !== 'cancelled') {
                    $nonCancelled[] = $session;
                }
            }
            $visible = $nonCancelled !== array() ? array_slice($nonCancelled, -2) : array_slice($sessions, -2);
        }
        usort($visible, function ($a, $b) {
            $cmp = strcmp((string) $a['session_date'], (string) $b['session_date']);
            return $cmp !== 0 ? $cmp : strcmp((string) $a['start_time'], (string) $b['start_time']);
        });
        return $visible;
    }
}

if ($action === 'chi-tiet-lop') {
    $classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
    $cycleSvc = new \Lms\CourseCycleService($d);
    $group = $cycleSvc->teacherGroupDetail($classId, $teacherId);
    if (empty($group)) {
        lms_flash('error', 'Lớp nhóm không tồn tại hoặc không thuộc quyền giáo viên.');
        header('Location: ' . lms_url('lms-hoc-vien-cua-toi'));
        exit;
    }
    $group['visible_sessions'] = lms_hv_group_visible_sessions($group['sessions']);
    $sessionSvc = new \Lms\SessionService($d);
    foreach ($group['visible_sessions'] as $i => $session) {
        if (trim((string) $session['hw_content']) !== '' || trim((string) $session['lesson_summary']) !== '') {
            $rows = $sessionSvc->listSessionStudentsForGrading((int) $session['id'], $teacherId);
            foreach ($rows as $j => $row) {
                $rows[$j]['hw_submission_files'] = \Lms\UserService::decodeJsonArray(
                    isset($row['hw_submission_files']) ? $row['hw_submission_files'] : array()
                );
                $rows[$j]['teacher_feedback_files'] = \Lms\UserService::decodeJsonArray(
                    isset($row['teacher_feedback_files']) ? $row['teacher_feedback_files'] : array()
                );
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
                $rows[$j]['grade_due_label'] = $label;
                $rows[$j]['grade_overdue'] = $overdue;
            }
            $group['visible_sessions'][$i]['grading_students'] = $rows;
        } else {
            $group['visible_sessions'][$i]['grading_students'] = array();
        }
    }
    $title = (string) $group['class']['name'];
    $template = 'lms-hoc-vien-cua-toi/chitiet-lop';
    return;
}

if ($action === 'them-buoi-lop') {
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ.');
        header('Location: ' . lms_url('lms-hoc-vien-cua-toi'));
        exit;
    }
    $classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
    $cycleSvc = new \Lms\CourseCycleService($d);
    $group = $cycleSvc->teacherGroupDetail($classId, $teacherId);
    if (empty($group)) {
        lms_flash('error', 'Lớp nhóm không tồn tại hoặc không thuộc quyền giáo viên.');
        header('Location: ' . lms_url('lms-hoc-vien-cua-toi'));
        exit;
    }
    $note = trim((string) (isset($_POST['note']) ? $_POST['note'] : ''));
    $topicName = trim((string) (isset($_POST['topic_name']) ? $_POST['topic_name'] : ''));
    if ($topicName !== '') {
        $note = ($note !== '' ? $note . ' — ' : '') . 'Chuyên đề: ' . $topicName;
    }
    $reqSvc = new \Lms\RequestService($d);
    $reqId = $reqSvc->createRequest($teacherId, array(
        'request_type' => 'add_session',
        'class_id' => $classId,
        'student_id' => 0,
        'session_date' => isset($_POST['session_date']) ? trim((string) $_POST['session_date']) : '',
        'start_time' => isset($_POST['start_time']) ? trim((string) $_POST['start_time']) : '',
        'end_time' => isset($_POST['end_time']) ? trim((string) $_POST['end_time']) : '',
        'note' => $note,
    ));
    if ($reqId) {
        lms_flash('success', 'Đã gửi yêu cầu thêm buổi cho lớp.');
    } else {
        $errs = $reqSvc->errors();
        lms_flash('error', $errs !== array() ? (string) reset($errs) : 'Không thể gửi yêu cầu thêm buổi.');
    }
    header('Location: ' . lms_url('lms-hoc-vien-cua-toi', 'chi-tiet-lop', array('class_id' => $classId)));
    exit;
}

if ($action === 'cham-btvn-lop') {
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ.');
        header('Location: ' . lms_url('lms-hoc-vien-cua-toi'));
        exit;
    }
    $classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
    $sessionId = isset($_POST['session_id']) ? (int) $_POST['session_id'] : 0;
    $ssId = isset($_POST['session_student_id']) ? (int) $_POST['session_student_id'] : 0;
    $owned = $d->safeFetchOne(
        'select s.id from db_lms_sessions s join db_lms_classes c on c.id = s.class_id'
        . ' where s.id = ? and c.id = ? and c.teacher_id = ? and c.class_type = ? and c.deleted_at is null limit 1',
        array($sessionId, $classId, $teacherId, 'group')
    );
    if (empty($owned) || $ssId <= 0) {
        lms_flash('error', 'Buổi học hoặc học viên không hợp lệ.');
        header('Location: ' . lms_url('lms-hoc-vien-cua-toi', 'chi-tiet-lop', array('class_id' => $classId)));
        exit;
    }
    $sessionSvc = new \Lms\SessionService($d);
    $ok = $sessionSvc->gradeHomework(
        $ssId,
        $teacherId,
        $_POST,
        isset($_FILES['teacher_feedback_files']) ? $_FILES['teacher_feedback_files'] : null
    );
    if ($ok) {
        lms_flash('success', 'Đã lưu kết quả chấm BTVN.');
    } else {
        $errs = $sessionSvc->errors();
        lms_flash('error', $errs !== array() ? (string) reset($errs) : 'Không thể chấm BTVN.');
    }
    header('Location: ' . lms_url('lms-hoc-vien-cua-toi', 'chi-tiet-lop', array('class_id' => $classId)) . '#session-' . $sessionId);
    exit;
}

if ($action === 'chi-tiet') {
    $classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
    $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
    $cycleId = isset($_GET['cycle_id']) ? (int) $_GET['cycle_id'] : 0;
    $cycleSvc = new \Lms\CourseCycleService($d);
    $detail = $cycleSvc->teacherCourseDetail($classId, $studentId, $teacherId, $cycleId);
    if (empty($detail)) {
        lms_flash('error', 'Khóa học không tồn tại hoặc không thuộc quyền giáo viên.');
        header('Location: ' . lms_url('lms-hoc-vien-cua-toi'));
        exit;
    }
    $title = (string) $detail['student']['full_name'];
    $template = 'lms-hoc-vien-cua-toi/chitiet';
    return;
}

if ($action === 'cham-test') {
    $classId = isset($_GET['class_id']) ? (int) $_GET['class_id'] : 0;
    $studentId = isset($_GET['student_id']) ? (int) $_GET['student_id'] : 0;
    $cycleId = isset($_GET['cycle_id']) ? (int) $_GET['cycle_id'] : 0;
    $topicId = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : 0;

    $cycleSvc = new \Lms\CourseCycleService($d);
    $detail = $cycleSvc->teacherCourseDetail($classId, $studentId, $teacherId, $cycleId);
    if (empty($detail)) {
        lms_flash('error', 'Khóa học không tồn tại hoặc không thuộc quyền giáo viên.');
        header('Location: ' . lms_url('lms-hoc-vien-cua-toi'));
        exit;
    }

    $topic = null;
    foreach ($detail['topics'] as $t) {
        if ((int) $t['topic_id'] === $topicId) {
            $topic = $t;
            break;
        }
    }
    if ($topic === null) {
        lms_flash('error', 'Chuyên đề không thuộc khóa học này.');
        header('Location: ' . lms_url('lms-hoc-vien-cua-toi', 'chi-tiet', array('class_id' => $classId, 'student_id' => $studentId)));
        exit;
    }

    $testsForTopic = array();
    foreach ($detail['tests'] as $te) {
        if ((int) $te['topic_id'] === $topicId) {
            $testsForTopic[(string) $te['test_type']] = $te;
        }
    }

    $title = 'Chấm Test: ' . (string) $topic['name'];
    $template = 'lms-hoc-vien-cua-toi/cham-test';
    global $hidePageTitle;
    $hidePageTitle = true;
    return;
}

if ($action === 'luu-cham-test') {
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ.');
        header('Location: ' . lms_url('lms-hoc-vien-cua-toi'));
        exit;
    }
    $classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
    $studentId = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;
    $topicId = isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0;
    $testId = isset($_POST['test_id']) ? (int) $_POST['test_id'] : 0;

    $cycleSvc = new \Lms\CourseCycleService($d);
    $ok = $cycleSvc->gradeTestSubmission(
        $testId,
        $studentId,
        $teacherId,
        $_POST,
        isset($_FILES['teacher_feedback_files']) ? $_FILES['teacher_feedback_files'] : null
    );

    if ($ok) {
        lms_flash('success', 'Đã lưu kết quả chấm điểm bài test.');
    } else {
        $errs = $cycleSvc->errors();
        lms_flash('error', !empty($errs) ? (string) reset($errs) : 'Không thể lưu kết quả chấm.');
    }
    header('Location: ' . lms_url('lms-hoc-vien-cua-toi', 'cham-test', array(
        'class_id' => $classId,
        'student_id' => $studentId,
        'topic_id' => $topicId,
    )));
    exit;
}

if ($action === 'mo-chuyen-de') {
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ.');
        header('Location: ' . lms_url('lms-hoc-vien-cua-toi'));
        exit;
    }
    $cycleSvc = new \Lms\CourseCycleService($d);
    $ok = $cycleSvc->teacherUnlockNextTopic(
        isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0,
        isset($_POST['cycle_id']) ? (int) $_POST['cycle_id'] : 0,
        isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0,
        $teacherId
    );
    lms_flash($ok ? 'success' : 'error', $ok ? 'Đã mở chuyên đề kế tiếp.' : 'Không thể mở chuyên đề kế tiếp.');
    header('Location: ' . lms_url('lms-hoc-vien-cua-toi', 'chi-tiet', array(
        'class_id' => isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0,
        'student_id' => isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0,
        'cycle_id' => isset($_POST['cycle_id']) ? (int) $_POST['cycle_id'] : 0,
    )));
    exit;
}

if ($action === 'gui-test') {
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ.');
        header('Location: ' . lms_url('lms-hoc-vien-cua-toi'));
        exit;
    }
    $classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
    $studentId = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;
    $cycleId = isset($_POST['cycle_id']) ? (int) $_POST['cycle_id'] : 0;
    $topicId = isset($_POST['topic_id']) ? (int) $_POST['topic_id'] : 0;
    $reviewScore = isset($_POST['review_score']) ? trim((string) $_POST['review_score']) : '';
    $lessonSummaryUrl = isset($_POST['lesson_summary_url']) ? trim((string) $_POST['lesson_summary_url']) : '';
    $deadlineRaw = isset($_POST['deadline']) ? trim((string) $_POST['deadline']) : '';
    $deadlineDate = isset($_POST['deadline_date']) ? trim((string) $_POST['deadline_date']) : '';
    $deadlineTime = isset($_POST['deadline_time']) ? trim((string) $_POST['deadline_time']) : '';
    $deadline = '';
    if (preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2})?$/', $deadlineRaw)) {
        $deadline = str_replace('T', ' ', $deadlineRaw);
        if (strlen($deadline) === 16) {
            $deadline .= ':00';
        }
    }
    if ($deadline === '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $deadlineDate)) {
        $timePart = '23:59:00';
        if ($deadlineTime !== '' && preg_match_all('/\b([01]?\d|2[0-3]):([0-5]\d)\b/', $deadlineTime, $timeMatches) && !empty($timeMatches[0])) {
            $lastMatch = end($timeMatches[0]);
            $parts = explode(':', $lastMatch);
            $timePart = sprintf('%02d:%02d:00', (int) $parts[0], (int) $parts[1]);
        }
        $deadline = $deadlineDate . ' ' . $timePart;
        if ($deadlineDate === date('Y-m-d') && strcmp($deadline, date('Y-m-d H:i:s')) < 0) {
            $deadline = $deadlineDate . ' 23:59:59';
        }
    }
    $back = lms_url('lms-hoc-vien-cua-toi', 'chi-tiet', array(
        'class_id' => $classId,
        'student_id' => $studentId,
        'cycle_id' => $cycleId,
    ));

    $valid = $d->safeFetchOne(
        'select ct.topic_id, ct.has_test, ct.has_minitest, t.name as topic_name, t.test_file_url, t.minitest_file_url'
        . ' from db_lms_course_cycles cc'
        . ' join db_lms_classes c on c.id = cc.class_id and c.deleted_at is null'
        . ' join db_lms_cycle_topics ct on ct.cycle_id = cc.id and ct.topic_id = ?'
        . ' join db_lms_topics t on t.id = ct.topic_id'
        . ' join db_lms_cycle_students cs on cs.cycle_id = cc.id and cs.student_id = ? and cs.status = ?'
        . ' join db_lms_cycle_student_topics cst on cst.cycle_id = cc.id and cst.topic_id = ct.topic_id'
        . ' and cst.student_id = ? and cst.status = ?'
        . ' where cc.id = ? and cc.class_id = ? and cc.status = ? and cc.teacher_id = ? and c.class_type = ?'
        . ' limit 1',
        array($topicId, $studentId, 'active', $studentId, 'unlocked', $cycleId, $classId, 'active', $teacherId, 'one_on_one')
    );
    if (empty($valid) || empty($valid['has_test'])) {
        lms_flash('error', 'Chuyên đề không hợp lệ hoặc chưa mở khóa cho học viên.');
        header('Location: ' . $back);
        exit;
    }
    if ($deadline === '' || strcmp($deadline, date('Y-m-d H:i:s')) < 0) {
        lms_flash('error', 'Deadline không hợp lệ hoặc đã qua.');
        header('Location: ' . $back);
        exit;
    }
    $exists = (int) $d->count(
        'db_lms_tests',
        'class_id = ? and cycle_id = ? and topic_id = ?',
        array($classId, $cycleId, $topicId)
    );
    if ($exists > 0) {
        lms_flash('error', 'Chuyên đề này đã có test/minitest, không thể gửi lại.');
        header('Location: ' . $back);
        exit;
    }

    $base = array(
        'class_id' => $classId,
        'cycle_id' => $cycleId,
        'topic_id' => $topicId,
        'test_file_url' => !empty($valid['test_file_url']) ? $valid['test_file_url'] : null,
        'deadline' => $deadline,
        'max_score' => 20,
        'status' => 'draft',
        'created_by' => $teacherId,
        'review_score' => $reviewScore !== '' ? $reviewScore : null,
        'lesson_summary_url' => $lessonSummaryUrl !== '' ? $lessonSummaryUrl : null,
    );
    $base['test_type'] = 'test';
    $base['title'] = $valid['topic_name'] . ' - Test';
    $ok = $d->insert('db_lms_tests', $base);
    if ($ok && !empty($valid['has_minitest'])) {
        $base['test_type'] = 'minitest';
        $base['title'] = $valid['topic_name'] . ' - Minitest';
        $base['test_file_url'] = !empty($valid['minitest_file_url']) ? $valid['minitest_file_url'] : null;
        $d->insert('db_lms_tests', $base);
    }
    if (!$ok) {
        lms_flash('error', 'Không thể tạo yêu cầu test.');
        header('Location: ' . $back);
        exit;
    }

    $cycleSvc = new \Lms\CourseCycleService($d);
    $unlockedNext = $cycleSvc->unlockNextTopicAfterTest($classId, $cycleId, $studentId, $topicId, $teacherId);
    lms_flash('success', 'Đã gửi yêu cầu test.' . ($unlockedNext ? ' Chuyên đề kế tiếp đã được mở khóa.' : ''));
    header('Location: ' . $back);
    exit;
}

if ($action === 'them-buoi') {
    if (!lms_csrf_verify()) {
        lms_flash('error', 'Phiên làm việc không hợp lệ.');
        header('Location: ' . lms_url('lms-hoc-vien-cua-toi'));
        exit;
    }
    $classId = isset($_POST['class_id']) ? (int) $_POST['class_id'] : 0;
    $studentId = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;
    $cycleId = isset($_POST['cycle_id']) ? (int) $_POST['cycle_id'] : 0;
    $topicName = isset($_POST['topic_name']) ? trim((string) $_POST['topic_name']) : '';
    $note = isset($_POST['note']) ? trim((string) $_POST['note']) : '';
    $back = lms_url('lms-hoc-vien-cua-toi', 'chi-tiet', array(
        'class_id' => $classId,
        'student_id' => $studentId,
        'cycle_id' => $cycleId,
    ));

    $cycleSvc = new \Lms\CourseCycleService($d);
    $detail = $cycleSvc->teacherCourseDetail($classId, $studentId, $teacherId, $cycleId);
    if (empty($detail)) {
        lms_flash('error', 'Khóa học không tồn tại hoặc không thuộc quyền giáo viên.');
        header('Location: ' . lms_url('lms-hoc-vien-cua-toi'));
        exit;
    }
    if ($note === '') {
        lms_flash('error', 'Vui lòng nhập lý do thêm buổi học.');
        header('Location: ' . $back);
        exit;
    }

    $sessionDate = isset($_POST['session_date']) ? trim((string) $_POST['session_date']) : '';
    $startTime = isset($_POST['start_time']) ? trim((string) $_POST['start_time']) : '';
    $endTime = isset($_POST['end_time']) ? trim((string) $_POST['end_time']) : '';
    if ($sessionDate === '' || $startTime === '' || $endTime === '') {
        $schedules = isset($detail['schedules']) && is_array($detail['schedules']) ? $detail['schedules'] : array();
        if (!empty($schedules)) {
            $firstSlot = $schedules[0];
            $startTime = $startTime !== '' ? $startTime : (string) $firstSlot['start_time'];
            $endTime = $endTime !== '' ? $endTime : (string) $firstSlot['end_time'];
            if ($sessionDate === '') {
                $targetDow = (int) $firstSlot['day_of_week'];
                for ($offset = 1; $offset <= 7; $offset++) {
                    $tsCandidate = strtotime('+' . $offset . ' day');
                    $dowCandidate = (int) date('N', $tsCandidate) + 1;
                    if ($dowCandidate === $targetDow) {
                        $sessionDate = date('Y-m-d', $tsCandidate);
                        break;
                    }
                }
            }
        }
        if ($sessionDate === '') {
            $sessionDate = date('Y-m-d', strtotime('+1 day'));
        }
        if ($startTime === '') {
            $startTime = '19:00';
        }
        if ($endTime === '') {
            $endTime = '20:30';
        }
    }

    $fullNote = $topicName !== '' ? ('Chuyên đề: ' . $topicName . ' — ' . $note) : $note;
    $reqSvc = new \Lms\RequestService($d);
    $reqId = $reqSvc->createRequest($teacherId, array(
        'request_type' => 'add_session',
        'class_id' => $classId,
        'student_id' => $studentId,
        'session_date' => $sessionDate,
        'start_time' => $startTime,
        'end_time' => $endTime,
        'note' => $fullNote,
    ));
    if ($reqId) {
        lms_flash('success', 'Đã gửi yêu cầu thêm buổi học.');
    } else {
        $errs = $reqSvc->errors();
        lms_flash('error', $errs !== array() ? (string) reset($errs) : 'Không thể gửi yêu cầu thêm buổi học.');
    }
    header('Location: ' . $back);
    exit;
}

$today = date('Y-m-d');
//
// Nhãn trạng thái BTVN (theo mockup):
//  - 'Chưa chấm BTVN' (đỏ): có bài đã nộp chưa chấm      → ưu tiên cao nhất
//  - 'Chưa giao BTVN' (đỏ): có buổi sắp tới chưa giao bài
//  - 'Đã chấm BTVN'  (xanh): mọi bài đã nộp đều đã chấm
//  - 'Chưa có BTVN'  (xám): lớp chưa có buổi nào có BTVN
$cards = $d->safeFetch(
    'select'
    . ' cc.class_id, c.name as class_name, c.class_type,'
    . ' subj.name as subject_name,'
    . ' cs.student_id, s.full_name, s.grade, s.avatar,'
    // BTVN: số bài đã nộp chưa chấm
    . ' (select count(*) from db_lms_session_students ss'
    . '    join db_lms_sessions ses on ses.id = ss.session_id'
    . '   where ses.class_id = c.id and ses.cycle_id = cc.id and ss.student_id = cs.student_id'
    . '     and ss.hw_submitted_at is not null and ss.hw_graded_at is null)'
    . '   as ungraded_count,'
    // BTVN: số buổi sắp tới chưa giao bài
    . ' (select count(*) from db_lms_sessions ses'
    . '   where ses.class_id = c.id and ses.cycle_id = cc.id and ses.status = \'upcoming\''
    . '     and ses.session_date >= ?'
    . '     and (ses.hw_content is null or ses.hw_content = \'\'))'
    . '   as unassigned_count,'
    // BTVN: số bài đã chấm
    . ' (select count(*) from db_lms_session_students ss'
    . '    join db_lms_sessions ses on ses.id = ss.session_id'
    . '   where ses.class_id = c.id and ses.cycle_id = cc.id and ss.student_id = cs.student_id'
    . '     and ss.hw_graded_at is not null) as graded_count'
    . ' from db_lms_cycle_students cs'
    . ' join db_lms_course_cycles cc on cc.id = cs.cycle_id and cc.status = \'active\''
    . ' join db_lms_classes c on c.id = cc.class_id and c.deleted_at is null'
    . ' join db_lms_subjects subj on subj.id = c.subject_id'
    . ' join db_lms_students s on s.user_id = cs.student_id'
    . ' where cc.teacher_id = ? and cs.status = \'active\''
    . '   and c.status = \'active\''
    . ' order by s.full_name asc, c.name asc',
    array($today, $teacherId)
);

// Tính nhãn trạng thái BTVN cho từng thẻ (theo mockup: chỉ 3 trạng thái).
foreach ($cards as $i => $card) {
    if ((int) $card['ungraded_count'] > 0) {
        $cards[$i]['hw_label'] = 'Chưa chấm BTVN';
        $cards[$i]['hw_class'] = 'text-danger';
    } elseif ((int) $card['unassigned_count'] > 0) {
        $cards[$i]['hw_label'] = 'Chưa giao BTVN';
        $cards[$i]['hw_class'] = 'text-danger';
    } elseif ((int) $card['graded_count'] > 0) {
        $cards[$i]['hw_label'] = 'Đã chấm BTVN';
        $cards[$i]['hw_class'] = 'text-success';
    } else {
        $cards[$i]['hw_label'] = 'Chưa giao BTVN';
        $cards[$i]['hw_class'] = 'text-danger';
    }

    // Tiêu đề thẻ: lớp nhóm hiện tên lớp, lớp 1-1 hiện tên học viên (theo mockup).
    $cards[$i]['card_title'] = (string) $card['class_type'] === 'group'
        ? (string) $card['class_name']
        : (string) $card['full_name'];

    // Dòng mô tả: "Lớp 5 - Toán" (khối + môn).
    $grade = trim((string) $card['grade']);
    $cards[$i]['grade_subject'] = $grade !== ''
        ? $grade . ' - ' . (string) $card['subject_name']
        : (string) $card['subject_name'];

    $rawAvatar = trim((string) $card['avatar']);
    $cards[$i]['avatar_url'] = $rawAvatar !== ''
        ? lms_upload_url(str_replace('uploads/', '', $rawAvatar))
        : '';
    $cards[$i]['is_group'] = (string) $card['class_type'] === 'group';
    $cards[$i]['student_count'] = 1;
    $cards[$i]['link_url'] = (string) $card['class_type'] === 'group'
        ? lms_url('lms-hoc-vien-cua-toi', 'chi-tiet-lop', array('class_id' => (int) $card['class_id']))
        : lms_url('lms-hoc-vien-cua-toi', 'chi-tiet', array(
            'class_id' => (int) $card['class_id'],
            'student_id' => (int) $card['student_id'],
        ));
}

// Lớp nhóm: gom nhiều học viên thành 1 thẻ/lớp (theo mockup Cấp 1).
$groupCards = array();
$singleCards = array();
foreach ($cards as $card) {
    if (empty($card['is_group'])) {
        $singleCards[] = $card;
        continue;
    }
    $cid = (int) $card['class_id'];
    if (!isset($groupCards[$cid])) {
        $groupCards[$cid] = array(
            'class_id' => $cid,
            'class_type' => 'group',
            'is_group' => true,
            'card_title' => (string) $card['class_name'],
            'subject_name' => (string) $card['subject_name'],
            'avatar_url' => '',
            'student_count' => 0,
            'ungraded_count' => 0,
            'graded_count' => 0,
            'unassigned_count' => (int) $card['unassigned_count'],
            'grades' => array(),
            'link_url' => $card['link_url'],
        );
    }
    $groupCards[$cid]['student_count']++;
    $groupCards[$cid]['ungraded_count'] += (int) $card['ungraded_count'];
    $groupCards[$cid]['graded_count'] += (int) $card['graded_count'];
    $grade = trim((string) $card['grade']);
    if ($grade !== '') {
        $groupCards[$cid]['grades'][$grade] = true;
    }
}

foreach ($groupCards as $cid => $card) {
    if ((int) $card['ungraded_count'] > 0) {
        $groupCards[$cid]['hw_label'] = 'Chưa chấm BTVN';
        $groupCards[$cid]['hw_class'] = 'text-danger';
    } elseif ((int) $card['unassigned_count'] > 0) {
        $groupCards[$cid]['hw_label'] = 'Chưa giao BTVN';
        $groupCards[$cid]['hw_class'] = 'text-danger';
    } elseif ((int) $card['graded_count'] > 0) {
        $groupCards[$cid]['hw_label'] = 'Đã chấm BTVN';
        $groupCards[$cid]['hw_class'] = 'text-success';
    } else {
        $groupCards[$cid]['hw_label'] = 'Chưa giao BTVN';
        $groupCards[$cid]['hw_class'] = 'text-danger';
    }
    $gradeLabels = array_keys($groupCards[$cid]['grades']);
    $groupCards[$cid]['grade_subject'] = $gradeLabels !== array()
        ? implode(', ', $gradeLabels) . ' - ' . $groupCards[$cid]['subject_name']
        : $groupCards[$cid]['subject_name'];
}

$cards = array_merge(array_values($groupCards), $singleCards);

$classIds = array();
foreach ($cards as $card) {
    $classIds[] = (int) $card['class_id'];
}
$classIds = array_values(array_unique($classIds));
$scheduleMap = array();
if ($classIds !== array()) {
    $scheduleMap = (new \Lms\MonthlyReviewService($d))->scheduleRowsMap($classIds);
}
foreach ($cards as $i => $card) {
    $cid = (int) $card['class_id'];
    $cards[$i]['schedule_rows'] = isset($scheduleMap[$cid]) ? $scheduleMap[$cid] : array();
}

$title    = 'Học viên của tôi';
$template = 'lms-hoc-vien-cua-toi/hienthi';
