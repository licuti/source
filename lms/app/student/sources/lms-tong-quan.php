<?php
/**
 * Student — Tổng quan (T9).
 *
 * Chỉ đọc: lớp đang học + buổi học sắp tới + thông báo.
 * Row-level: chỉ dữ liệu của HV đang đăng nhập (guide §3.5).
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d    = lms_db();
$user = lms_current_user();

$studentId = is_array($user) && !empty($user['id']) ? (int) $user['id'] : 0;
$today = date('Y-m-d');

// ===================== Lớp đang học =====================

$classes = $d->safeFetch(
    'select c.id, c.name,'
    . ' (select group_concat('
    . '    concat(case sch.day_of_week'
    . '      when 2 then \'Thứ 2\' when 3 then \'Thứ 3\' when 4 then \'Thứ 4\' when 5 then \'Thứ 5\''
    . '      when 6 then \'Thứ 6\' when 7 then \'Thứ 7\' else \'Chủ nhật\' end,'
    . '    \' \', time_format(sch.start_time, \'%H:%i\'), \' - \', time_format(sch.end_time, \'%H:%i\'))'
    . '    order by sch.day_of_week, sch.start_time separator \'|\')'
    . '  from db_lms_cycle_schedules sch'
    . '  join db_lms_course_cycles cc on cc.id = sch.cycle_id and cc.status = \'active\''
    . '  where cc.class_id = c.id'
    . '    and sch.effective_from <= ?'
    . '    and (sch.effective_to is null or sch.effective_to >= ?)) as schedule_lines'
    . ' from db_lms_class_students cs'
    . ' join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
    . ' where cs.student_id = ? and cs.status = ? and c.status = ?'
    . ' order by c.name',
    array($today, $today, $studentId, 'active', 'active')
);

// ===================== Buổi học sắp tới =====================

$upcomingRows = $d->safeFetch(
    'select s.id, s.session_date, s.start_time, s.end_time, s.status,'
    . ' c.name as class_name, c.id as class_id'
    . ' from db_lms_session_students ss2'
    . ' join db_lms_sessions s on s.id = ss2.session_id'
    . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
    . ' where ss2.student_id = ? and s.status = ?'
    . '   and timestamp(s.session_date, s.start_time) >= now()'
    . ' order by s.session_date, s.start_time limit 10',
    array($studentId, 'upcoming')
);
$upcoming = array();
$courseSvc = new \Lms\StudentCourseService($d);
foreach ($upcomingRows as $upcomingRow) {
    if ($courseSvc->getSessionForStudent((int) $upcomingRow['id'], $studentId) !== null) {
        $upcoming[] = $upcomingRow;
        break;
    }
}

// ===================== Thông báo =====================

$notifSvc = new \Lms\NotificationService($d);
$notifications = $notifSvc->listForUser($studentId, 3);

$title    = 'Tổng quan';
$hidePageTitle = true;
$template = 'lms-tong-quan/hienthi';
