<?php
/**
 * Admin LMS — Tổng quan (T9).
 *
 * Chỉ đọc (SELECT): 4 thẻ số liệu + bảng thống kê nhóm chương trình.
 * Form CRUD thuộc Phase 1.1–1.3.
 *
 * PHP 7.3 compatible.
 */

if (!defined('_source')) {
    die('Error');
}

$d    = lms_db();
$user = lms_current_user();

// ===================== 4 thẻ số liệu =====================

$stats = array(
    array(
        'icon'  => 'fa-solid fa-user-graduate',
        'label' => 'Học viên đang học',
        'value' => $d->count('db_lms_students', 'status = ?', array('studying')),
    ),
    array(
        'icon'  => 'fa-solid fa-chalkboard-user',
        'label' => 'Giáo viên đang hoạt động',
        'value' => $d->count('db_lms_teachers', 'status = ?', array('active')),
    ),
    array(
        'icon'  => 'fa-solid fa-school',
        'label' => 'Lớp đang hoạt động',
        'value' => $d->count('db_lms_classes', 'status = ? and deleted_at is null', array('active')),
    ),
    array(
        'icon'  => 'fa-solid fa-shuffle',
        'label' => 'Lớp học chờ xếp',
        'value' => $d->count('db_lms_classes', 'status = ? and deleted_at is null', array('arranging')),
    ),
);

// ===================== Thống kê nhóm chương trình =====================

$programStats = $d->safeFetch(
    'select p.id, p.name,'
    . ' (select count(*) from db_lms_classes c where c.program_id = p.id and c.status = ? and c.deleted_at is null) as active_count,'
    . ' (select count(*) from db_lms_classes c where c.program_id = p.id and c.status = ? and c.deleted_at is null) as arranging_count,'
    . ' (select count(*) from db_lms_classes c where c.program_id = p.id and c.status = ? and c.deleted_at is null) as paused_count'
    . ' from db_lms_programs p'
    . ' order by p.name'
    , array('active', 'arranging', 'paused')
);

// ===================== Lịch tuần này (timeline 7 ngày) =====================

$weekStart = date('Y-m-d', strtotime('monday this week'));
$weekEnd   = date('Y-m-d', strtotime('sunday this week'));

$weekSessions = $d->safeFetch(
    'select s.id, s.session_date, s.start_time, s.end_time, s.status,'
    . ' c.id as class_id, c.class_code, c.name as class_name, c.class_type,'
    . ' subj.name as subject_name,'
    . ' t.full_name as teacher_name,'
    . ' (select count(*) from db_lms_class_students cs'
    . '   where cs.class_id = c.id and cs.status = \'active\') as student_count'
    . ' from db_lms_sessions s'
    . ' join db_lms_classes c on c.id = s.class_id and c.deleted_at is null'
    . ' join db_lms_subjects subj on subj.id = c.subject_id'
    . ' join db_lms_teachers t on t.user_id = c.teacher_id'
    . ' where s.session_date between ? and ?'
    . ' order by s.session_date asc, s.start_time asc',
    array($weekStart, $weekEnd)
);

$weekdays = array(
    2 => 'Thứ 2', 3 => 'Thứ 3', 4 => 'Thứ 4', 5 => 'Thứ 5',
    6 => 'Thứ 6', 7 => 'Thứ 7', 8 => 'Chủ nhật',
);

$sessionStatusLabels = array(
    'upcoming'       => array('Sắp diễn ra', 'lms-badge-info'),
    'finished'       => array('Đã dạy', 'lms-badge-success'),
    'cancelled'      => array('Đã huỷ', 'lms-badge-secondary'),
    'absent_teacher' => array('GV vắng', 'lms-badge-danger'),
    'absent_student' => array('HV vắng', 'lms-badge-warning'),
);

$title    = 'Tổng quan';
$template = 'lms-tong-quan/hienthi';
