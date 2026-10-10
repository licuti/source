<?php
/**
 * Teacher — Tổng quan (T9).
 *
 * Chỉ đọc: cảnh báo tick (nếu gần ngưỡng db_lms_violation_thresholds) + lịch dạy tuần.
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

// ===================== Tick trong tháng + ngưỡng =====================

$monthStart = date('Y-m-01');
$today = date('Y-m-d');

$tickCount = $d->count(
    'db_lms_violations',
    'teacher_id = ? and tick_amount > 0 and violation_date >= ?',
    array($teacherId, $monthStart)
);

// Số lớp đang dạy
$classCount = $d->count(
    'db_lms_classes',
    'teacher_id = ? and status = ? and deleted_at is null',
    array($teacherId, 'active')
);

// Ngưỡng áp dụng hôm nay
$threshold = $d->safeFetchOne(
    'select * from db_lms_violation_thresholds'
    . ' where effective_from <= ? and (effective_to is null or effective_to >= ?)'
    . ' and min_classes <= ? and (max_classes is null or max_classes >= ?)'
    . ' order by min_classes desc limit 1',
    array($today, $today, $classCount, $classCount)
);

$warnMessage = '';
if (!empty($threshold) && is_array($threshold)) {
    $ticksToWarn = (int) $threshold['ticks_to_warn'];
    if ($ticksToWarn > 0 && $tickCount >= max(1, $ticksToWarn - 2)) {
        $remain = $ticksToWarn - $tickCount;
        if ($remain > 0) {
            $warnMessage = 'Bạn đã tích luỹ <strong>' . $tickCount . '/' . $ticksToWarn
                . '</strong> tick trong tháng. Còn <strong>' . $remain
                . '</strong> tick nữa sẽ bị cảnh báo. Hãy chấm bài đúng hạn!';
        } else {
            $warnMessage = 'Bạn đã vượt ngưỡng cảnh báo (' . $ticksToWarn
                . ' tick/tháng). Vui lòng liên hệ quản trị viên.';
        }
    }
}

// ===================== Lịch dạy tuần này =====================

$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd   = date('Y-m-d', strtotime('sunday this week'));

$weekSessions = $d->safeFetch(
    'select s.id, s.session_date, s.start_time, s.end_time, s.status,'
    . ' c.name as class_name, c.class_type'
    . ' from db_lms_sessions s'
    . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
    . ' where s.teacher_id = ? and s.session_date between ? and ?'
    . ' order by s.session_date, s.start_time',
    array($teacherId, $weekStart, $weekEnd)
);

$weekdays = array(
    2 => 'Thứ 2', 3 => 'Thứ 3', 4 => 'Thứ 4', 5 => 'Thứ 5',
    6 => 'Thứ 6', 7 => 'Thứ 7', 8 => 'Chủ nhật',
);

$statusLabels = array(
    'upcoming'       => array('Sắp diễn ra', 'lms-badge-info'),
    'finished'       => array('Đã dạy', 'lms-badge-success'),
    'cancelled'      => array('Đã huỷ', 'lms-badge-secondary'),
    'absent_teacher' => array('GV vắng', 'lms-badge-danger'),
    'absent_student' => array('HV vắng', 'lms-badge-warning'),
);

$title    = 'Tổng quan';
$template = 'lms-tong-quan/hienthi';
