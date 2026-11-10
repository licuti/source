<?php
/**
 * Teacher — Tổng quan (T9).
 *
 * Bố cục theo mockup:
 *  - Banner cảnh báo tick full-width dưới header (layout alert slot).
 *  - Cột giữa: "Buổi học sắp tới" + lưới thẻ "Các học viên hiện tại"
 *    (avatar, tên, các khung lịch cố định).
 *  - Cột phải: "Thông báo" — card tác vụ (ghi báo cáo, chấm bài), card hạ phí
 *    dịch vụ và thông báo gần đây.
 *
 * Row-level: chỉ dữ liệu của GV đang đăng nhập (guide §3.5).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d    = lms_db();
$user = lms_current_user();

$teacherId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;
$today     = date('Y-m-d');

$weekdays = array(
    2 => 'Thứ 2', 3 => 'Thứ 3', 4 => 'Thứ 4', 5 => 'Thứ 5',
    6 => 'Thứ 6', 7 => 'Thứ 7', 8 => 'Chủ nhật',
);

$statusLabels = array(
    'upcoming'       => 'Sắp diễn ra',
    'finished'       => 'Đã dạy',
    'cancelled'      => 'Đã huỷ',
    'absent_teacher' => 'GV vắng',
    'absent_student' => 'HV vắng',
);

// ===================== Tick + ngưỡng hạ phí (BR-TICK-03) =====================

$tickSvc = new \Lms\TickService($d);
$tickSummary = $tickSvc->teacherTickSummary($teacherId);
$realTicks = isset($tickSummary['real']) ? (int) $tickSummary['real'] : 0;
$threshold = !empty($tickSummary['threshold']) && is_array($tickSummary['threshold'])
    ? $tickSummary['threshold']
    : array();

$warnAt   = isset($threshold['ticks_to_warn']) ? (int) $threshold['ticks_to_warn'] : 0;
$demoteAt = isset($threshold['ticks_to_demote_fee']) ? (int) $threshold['ticks_to_demote_fee'] : 0;
$feeAfterDemote = isset($threshold['fee_percentage_after_demote'])
    ? (float) $threshold['fee_percentage_after_demote']
    : 0.0;

$feeDemoted = $demoteAt > 0 && $realTicks >= $demoteAt;
$warnMessage = '';
if (!$feeDemoted && $warnAt > 0 && $demoteAt > 0 && $realTicks >= $warnAt) {
    $warnMessage = 'Cảnh báo: Bạn còn ' . max(0, $demoteAt - $realTicks)
        . ' tick nữa trước khi bị hạ phí dịch vụ';
}

// ===================== Buổi học sắp tới =====================

$nextSessions = $d->safeFetch(
    'select s.id, s.session_date, s.start_time, s.end_time, s.status,'
    . ' c.name as class_name, c.class_type, c.class_code'
    . ' from db_lms_sessions s'
    . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
    . ' where s.teacher_id = ? and s.session_date >= ? and s.status = \'upcoming\''
    . ' order by s.session_date asc, s.start_time asc'
    . ' limit 3',
    array($teacherId, $today)
);

// ===================== Các học viên hiện tại =====================

$myStudents = $d->safeFetch(
    'select distinct cs.student_id, s.student_code, s.full_name'
    . ' from db_lms_class_students cs'
    . ' join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
    . ' join db_lms_students s on s.user_id = cs.student_id'
    . ' where c.teacher_id = ? and cs.status = \'active\' and c.status = \'active\''
    . ' order by s.full_name asc'
    . ' limit 12',
    array($teacherId)
);

$schedulesByStudent = array();
$scheduleRows = $d->safeFetch(
    'select cs.student_id, sch.day_of_week, sch.start_time, sch.end_time'
    . ' from db_lms_class_students cs'
    . ' join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
    . ' join db_lms_schedules sch on sch.class_id = c.id'
    . ' where c.teacher_id = ? and c.status = \'active\' and cs.status = \'active\''
    . ' and (sch.effective_to is null or sch.effective_to >= ?)'
    . ' group by cs.student_id, sch.day_of_week, sch.start_time, sch.end_time'
    . ' order by cs.student_id asc, sch.day_of_week asc, sch.start_time asc',
    array($teacherId, $today)
);
foreach ($scheduleRows as $row) {
    $sid = (int) $row['student_id'];
    $dow = (int) $row['day_of_week'];
    $schedulesByStudent[$sid][] = (isset($weekdays[$dow]) ? $weekdays[$dow] : '')
        . ' ' . substr((string) $row['start_time'], 0, 5)
        . ' - ' . substr((string) $row['end_time'], 0, 5);
}

foreach ($myStudents as $i => $stu) {
    $name = trim((string) $stu['full_name']);
    $first = preg_split('/\s+/u', $name);
    $myStudents[$i]['initials'] = $first
        ? mb_strtoupper(mb_substr((string) $first[0], 0, 1, 'UTF-8'), 'UTF-8')
        : '?';
    $myStudents[$i]['avatar_class'] = 'lms-avatar-' . ((int) $stu['student_id'] % 6);
    $myStudents[$i]['schedules'] = isset($schedulesByStudent[(int) $stu['student_id']])
        ? $schedulesByStudent[(int) $stu['student_id']]
        : array();
}

// ===================== Tác vụ cần xử lý =====================

// 1) Ghi báo cáo buổi học: buổi đã xong chưa ghi summary (gấp nhất).
$summaryTask = $d->safeFetchOne(
    'select s.id, s.session_date, s.start_time, c.name as class_name'
    . ' from db_lms_sessions s'
    . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
    . ' where s.teacher_id = ? and s.status = \'finished\' and s.summary_submitted_at is null'
    . ' order by s.session_date asc, s.start_time asc limit 1',
    array($teacherId)
);
$summaryTaskUrl = '';
if (!empty($summaryTask) && is_array($summaryTask)) {
    $summaryTaskUrl = lms_url('lms-lich-day', 'buoi', array('id' => (int) $summaryTask['id']));
}

// 2) Chấm bài: buổi có bài nộp chưa chấm cũ nhất + tổng số bài chờ chấm.
$gradingTask = $d->safeFetchOne(
    'select ses.id, ses.session_date, ses.start_time, c.name as class_name,'
    . ' count(ss.id) as ungraded_count'
    . ' from db_lms_session_students ss'
    . ' join db_lms_sessions ses on ses.id = ss.session_id'
    . ' join db_lms_classes c on c.id = ses.class_id and c.deleted_at is null'
    . ' where c.teacher_id = ? and ss.hw_submitted_at is not null and ss.hw_graded_at is null'
    . ' group by ses.id, ses.session_date, ses.start_time, c.name'
    . ' order by ses.session_date asc, ses.start_time asc limit 1',
    array($teacherId)
);
$ungradedRow = $d->safeFetchOne(
    'select count(*) as c from db_lms_session_students ss'
    . ' join db_lms_sessions ses on ses.id = ss.session_id'
    . ' join db_lms_classes c on c.id = ses.class_id and c.deleted_at is null'
    . ' where c.teacher_id = ? and ss.hw_submitted_at is not null and ss.hw_graded_at is null',
    array($teacherId)
);
$ungradedTotal = isset($ungradedRow['c']) ? (int) $ungradedRow['c'] : 0;
$gradingTaskUrl = '';
if (!empty($gradingTask) && is_array($gradingTask)) {
    $gradingTaskUrl = lms_url('lms-lich-day', 'buoi', array('id' => (int) $gradingTask['id']));
}

// ===================== Thông báo gần đây =====================

$notifSvc = new \Lms\NotificationService($d);
$recentNotifs = array();
foreach ($notifSvc->listForUser($teacherId, 5) as $n) {
    // Cảnh báo tick đã hiển thị bằng banner/card hạ phí → tránh trùng.
    if (isset($n['type']) && (string) $n['type'] === 'tick_warning') {
        continue;
    }
    $recentNotifs[] = $n;
}

$title = 'Tổng quan';
$template = 'lms-tong-quan/hienthi';
$hidePageTitle = true;
$layoutAlert = $warnMessage !== '' ? array('message' => $warnMessage) : null;
