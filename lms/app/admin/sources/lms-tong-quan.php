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
        'label' => 'Học viên chờ xếp lớp',
        'value' => $d->count('db_lms_students', 'status = ?', array('pending_arrange')),
    ),
);

// ===================== Thống kê nhóm chương trình =====================

$programStats = $d->safeFetch(
    'select p.id, p.name,'
    . ' (select count(*) from db_lms_classes c where c.program_id = p.id and c.deleted_at is null) as class_count,'
    . ' (select count(*) from db_lms_class_students cs'
    . '   join db_lms_classes c2 on c2.id = cs.class_id and c2.deleted_at is null'
    . '   where c2.program_id = p.id) as student_count,'
    . ' (select count(*) from db_lms_sessions s'
    . '   join db_lms_classes c3 on c3.id = s.class_id and c3.deleted_at is null'
    . '   where c3.program_id = p.id) as session_count'
    . ' from db_lms_programs p'
    . ' order by p.name'
);

$title    = 'Tổng quan';
$template = 'lms-tong-quan/hienthi';
