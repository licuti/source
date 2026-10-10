<?php
/**
 * Teacher — Học viên của tôi (T8).
 *
 * Hiển thị danh sách học viên đang học trong các lớp của giáo viên hiện tại.
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

// ===================== Danh sách học viên =====================
//
// Schema thực tế:
//   db_lms_users        : id (PK), username, email, phone, status (tài khoản)
//   db_lms_students     : user_id (PK = db_lms_users.id), student_code, full_name, grade, status (học)
//   db_lms_class_students: class_id, student_id (= students.user_id), status
//   db_lms_sessions     : KHÔNG có student_id — theo học viên nằm ở db_lms_session_students

$today = date('Y-m-d');

$students = $d->safeFetch(
    'select u.id as user_id, u.username, u.email, u.phone, u.created_at,'
    . ' u.status as user_status,'
    . ' s.student_code, s.full_name, s.grade, s.status as student_status,'
    // Số lớp đang học với GV này
    . ' (select count(*) from db_lms_class_students cs'
    . '    join db_lms_classes c on c.id = cs.class_id'
    . '   where cs.student_id = u.id and c.teacher_id = ?'
    . '     and c.deleted_at is null and cs.status = \'active\') as class_count,'
    // Buổi đã dạy (session_students -> sessions -> classes)
    . ' (select count(*) from db_lms_session_students ss'
    . '    join db_lms_sessions ses on ses.id = ss.session_id'
    . '    join db_lms_classes c2 on c2.id = ses.class_id'
    . '   where ss.student_id = u.id and c2.teacher_id = ?'
    . '     and c2.deleted_at is null and ses.session_date < ?'
    . '     and ses.status = \'finished\') as sessions_finished,'
    // Buổi sắp tới
    . ' (select count(*) from db_lms_session_students ss'
    . '    join db_lms_sessions ses on ses.id = ss.session_id'
    . '    join db_lms_classes c3 on c3.id = ses.class_id'
    . '   where ss.student_id = u.id and c3.teacher_id = ?'
    . '     and c3.deleted_at is null and ses.session_date >= ?'
    . '     and ses.status = \'upcoming\') as sessions_upcoming'
    . ' from db_lms_users u'
    . ' join db_lms_students s on s.user_id = u.id'
    . ' where s.user_id in ('
    . '     select cs.student_id from db_lms_class_students cs'
    . '      join db_lms_classes c on c.id = cs.class_id'
    . '    where c.teacher_id = ? and c.deleted_at is null and cs.status = \'active\''
    . ' )'
    . ' order by s.full_name asc, u.id desc',
    array($teacherId, $teacherId, $today, $teacherId, $today, $teacherId)
);

$statusLabels = array(
    'pending_arrange' => array('Chờ xếp lớp', 'lms-badge-secondary'),
    'studying'        => array('Đang học', 'lms-badge-success'),
    'graduated'       => array('Tốt nghiệp', 'lms-badge-info'),
    'dropped'         => array('Bỏ học', 'lms-badge-danger'),
);

$userStatusLabels = array(
    'active'  => array('Đang hoạt động', 'lms-badge-success'),
    'locked'  => array('Đã khoá', 'lms-badge-danger'),
    'pending' => array('Chờ kích hoạt', 'lms-badge-warning'),
);

$title    = 'Học viên của tôi';
$template = 'lms-hoc-vien-cua-toi/hienthi';