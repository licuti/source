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

// ===================== Lớp đang học =====================

$classes = $d->safeFetch(
    'select c.id, c.name, c.class_type, c.total_sessions, c.remaining_sessions,'
    . ' c.price_per_session, sub.name as subject_name, t.full_name as teacher_name'
    . ' from db_lms_class_students cs'
    . ' join db_lms_classes c on c.id = cs.class_id and c.deleted_at is null'
    . ' left join db_lms_subjects sub on sub.id = c.subject_id'
    . ' left join db_lms_teachers t on t.user_id = c.teacher_id'
    . ' where cs.student_id = ? and cs.status = ? and c.status = ?'
    . ' order by c.name',
    array($studentId, 'active', 'active')
);

// ===================== Buổi học sắp tới =====================

$today = date('Y-m-d');

$upcoming = $d->safeFetch(
    'select s.id, s.session_date, s.start_time, s.end_time, s.status,'
    . ' c.name as class_name, c.id as class_id,'
    . ' (select ss.status from db_lms_session_students ss where ss.session_id = s.id and ss.student_id = ?) as hw_status'
    . ' from db_lms_session_students ss2'
    . ' join db_lms_sessions s on s.id = ss2.session_id'
    . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
    . ' where ss2.student_id = ? and s.session_date >= ?'
    . ' order by s.session_date, s.start_time limit 10',
    array($studentId, $studentId, $today)
);

// ===================== Thông báo =====================

$notifSvc = new \Lms\NotificationService($d);
$notifications = $notifSvc->listForUser($studentId, 5);

$hwLabels = array(
    'not_submitted'    => array('Chưa nộp BTVN', 'lms-badge-warning'),
    'submitted'        => array('Đã nộp', 'lms-badge-info'),
    'graded'           => array('Đã chấm', 'lms-badge-success'),
    'overdue'          => array('Quá hạn', 'lms-badge-danger'),
);

$title    = 'Tổng quan';
$template = 'lms-tong-quan/hienthi';
