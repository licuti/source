<?php
/**
 * Seed demo LMS (Phase 0.6, D7 — "chuyển đổi xanh", không ETL dữ liệu cũ).
 *
 * Tạo:
 *  - 1 super admin
 *  - 3 giáo viên (mix rank / % phí DV / cam kết 08)
 *  - 5 học viên (mix trạng thái, học online/offline)
 *  - programs / subjects / topics mẫu (Toán TĐN + Tiếng Anh)
 *  - 2 lớp: 1-1 (1 GV + 1 HV) và nhóm (1 GV + 4 HV)
 *  - lịch cố định + sinh sessions 4 tuần
 *  - vài tick vi phạm + transactions mẫu để demo Payroll (Phase 2)
 *  - thông báo demo
 *
 * Idempotent: xoá dữ liệu demo cũ (theo prefix username) trước khi chèn.
 *
 * Usage: php 2026_10_07_lms_demo_seed.php [db_name]
 *        (mặc định phuongnamv_db)
 */

require_once __DIR__ . '/../../lms/lib/lms_bootstrap.php';

$dbName = isset($argv[1]) ? $argv[1] : 'phuongnamv_db';
$db = new LmsDb(array('host' => '127.0.0.1', 'name' => $dbName, 'user' => 'root', 'pass' => ''));

echo "Seeding LMS demo data into `$dbName`...\n";

// ===================== Cleanup demo cũ =====================
$db->safeExec('SET FOREIGN_KEY_CHECKS = 0');
$db->safeExec('delete from db_lms_session_students');
$db->safeExec('delete from db_lms_sessions');
$db->safeExec('delete from db_lms_class_students');
$db->safeExec('delete from db_lms_schedules');
$db->safeExec('delete from db_lms_transactions');
$db->safeExec('delete from db_lms_violations');
$db->safeExec('delete from db_lms_violation_thresholds');
$db->safeExec('delete from db_lms_notifications');
$db->safeExec('delete from db_lms_classes');
$db->safeExec('delete from db_lms_topics');
$db->safeExec('delete from db_lms_subjects');
$db->safeExec('delete from db_lms_programs');
$db->safeExec('delete from db_lms_teachers');
$db->safeExec('delete from db_lms_students');
$db->safeExec('delete from db_lms_admins');
$db->safeExec('delete from db_lms_users');
$db->safeExec('SET FOREIGN_KEY_CHECKS = 1');

$userSvc       = new Lms\UserService($db);
$classSvc      = new Lms\ClassService($db);
$schedSvc      = new Lms\ScheduleService($db);
$sessionSvc    = new Lms\SessionService($db);
$notifSvc      = new Lms\NotificationService($db);

$creds = array();

// ===================== Admin =====================
$adminPass = 'admin123';
$adminId = $userSvc->createUser('admin', $adminPass, 'admin', array('email' => 'admin@aroma.edu.vn'));
$db->insert('db_lms_admins', array(
    'user_id'    => $adminId,
    'full_name'  => 'Quản trị viên AROMA',
    'role_level' => 'super_admin',
));
$creds['admin'] = array('admin', $adminPass);

// ===================== Giáo viên (3) =====================
// teachable_subjects để ADM-04 match theo môn; lịch trống đăng ký qua
// ScheduleService ở phần dưới để demo sắp xếp lớp.
$teachers = array(
    array('username' => 'gv01', 'password' => 'gv01123456', 'code' => 'TE001',
          'name' => 'Nguyễn Thị Phương', 'gender' => 'female', 'rank' => 'green', 'fee' => 50.00, 'commit08' => 1,
          'districts' => array('Quận 1', 'Quận 3'), 'max_classes' => 4),
    array('username' => 'gv02', 'password' => 'gv02123456', 'code' => 'TE002',
          'name' => 'Trần Văn Duy', 'gender' => 'male', 'rank' => 'gold', 'fee' => 48.00, 'commit08' => 0,
          'districts' => array('Quận 1'), 'max_classes' => 3),
    array('username' => 'gv03', 'password' => 'gv03123456', 'code' => 'TE003',
          'name' => 'Lê Minh Châu', 'gender' => 'female', 'rank' => 'bronze', 'fee' => 45.00, 'commit08' => 1,
          'districts' => array('Quận 5', 'Quận 10'), 'max_classes' => 2),
);
$teacherIds = array();
foreach ($teachers as $t) {
    $uid = $userSvc->createUser($t['username'], $t['password'], 'teacher', array('email' => $t['username'] . '@aroma.edu.vn'));
    $db->insert('db_lms_teachers', array(
        'user_id'             => $uid,
        'teacher_code'        => $t['code'],
        'full_name'           => $t['name'],
        'gender'              => $t['gender'],
        'tax_code'            => '8' . str_pad((string) $uid, 9, '0', STR_PAD_LEFT),
        'teachable_districts' => json_encode($t['districts'], JSON_UNESCAPED_UNICODE),
        // teachable_subjects gán ID sau khi tạo subjects (xem phần Curriculum)
        'teachable_subjects'  => null,
        'can_teach_online'    => 1,
        'rank'                => $t['rank'],
        'max_classes'         => $t['max_classes'],
        'status'              => 'active',
        'service_fee_percentage' => $t['fee'],
        'is_commit_08'        => $t['commit08'],
        'bank_name'           => 'Vietcombank',
        'bank_account'        => '012345678' . (string) $uid,
        'bank_owner'          => $t['name'],
    ));
    $teacherIds[$t['username']] = $uid;
    $creds[$t['username']] = array($t['username'], $t['password']);
}

// ===================== Học viên (5) =====================
$students = array(
    array('username' => 'hv01', 'password' => 'hv01123456', 'code' => 'Master001',
          'name' => 'Phạm Duy Kiên', 'grade' => 'Lớp 5', 'learning_type' => 'offline',
          'district' => 'Quận 1', 'status' => 'studying'),
    array('username' => 'hv02', 'password' => 'hv02123456', 'code' => 'Master002',
          'name' => 'Trần Bảo Ngọc', 'grade' => 'Lớp 6', 'learning_type' => 'online',
          'district' => 'Quận 3', 'status' => 'studying'),
    array('username' => 'hv03', 'password' => 'hv03123456', 'code' => 'Master003',
          'name' => 'Lê Gia Huy', 'grade' => 'Lớp 5', 'learning_type' => 'offline',
          'district' => 'Quận 1', 'status' => 'studying'),
    array('username' => 'hv04', 'password' => 'hv04123456', 'code' => 'Master004',
          'name' => 'Võ Minh Anh', 'grade' => 'Lớp 7', 'learning_type' => 'online',
          'district' => 'Quận 5', 'status' => 'waiting_fee'),
    array('username' => 'hv05', 'password' => 'hv05123456', 'code' => 'Master005',
          'name' => 'Đỗ Quốc Bảo', 'grade' => 'Lớp 5', 'learning_type' => 'offline',
          'district' => 'Quận 10', 'status' => 'pending_arrange'),
);
$studentIds = array();
foreach ($students as $s) {
    $uid = $userSvc->createUser($s['username'], $s['password'], 'student');
    $db->insert('db_lms_students', array(
        'user_id'        => $uid,
        'student_code'   => $s['code'],
        'full_name'      => $s['name'],
        'parent_name'    => 'Phụ huynh ' . $s['name'],
        'parent_phone'   => '0901' . str_pad((string) $uid, 6, '0', STR_PAD_LEFT),
        'grade'          => $s['grade'],
        'district'       => $s['district'],
        'learning_type'  => $s['learning_type'],
        'status'         => $s['status'],
    ));
    $studentIds[$s['username']] = $uid;
    $creds[$s['username']] = array($s['username'], $s['password']);
}

// ===================== Curriculum =====================
$db->insert('db_lms_programs', array('name' => 'Toán Tư duy Nepal (TĐN)', 'description' => 'Chương trình Toán tư duy Nepal'));
$progToan = (int) $db->insertId;
$db->insert('db_lms_programs', array('name' => 'Tiếng Anh giao tiếp', 'description' => 'Chương trình Tiếng Anh'));
$progAnh = (int) $db->insertId;

$subjects = array(
    array($progToan, 'Math Problems'),
    array($progToan, 'Math IQ'),
    array($progAnh, 'Listening'),
    array($progAnh, 'Speaking'),
);
$subjectIds = array();
foreach ($subjects as $s) {
    $db->insert('db_lms_subjects', array('program_id' => $s[0], 'name' => $s[1]));
    $subjectIds[$s[1]] = (int) $db->insertId;
}

// teachable_subjects + môn muốn học của HV được gán trong phần ADM-04 bên dưới
// (sau khi đã có subjectIds).

$topics = array(
    array($subjectIds['Math Problems'], 'Phép cộng trừ cơ bản', 0, 0, 8, 1),
    array($subjectIds['Math Problems'], 'Phép nhân chia', 0, 0, 8, 2),
    array($subjectIds['Math Problems'], 'Bài toán có lời văn', 1, 0, 10, 3),
    array($subjectIds['Math IQ'], 'Dãy số quy luật', 0, 1, 6, 1),
    array($subjectIds['Math IQ'], 'Hình học tư duy', 1, 0, 6, 2),
    array($subjectIds['Listening'], 'Nghe cơ bản', 0, 0, 8, 1),
    array($subjectIds['Speaking'], 'Giao tiếp hàng ngày', 0, 1, 8, 1),
);
foreach ($topics as $t) {
    $db->insert('db_lms_topics', array(
        'subject_id'   => $t[0],
        'name'         => $t[1],
        'has_test'     => $t[2],
        'has_minitest' => $t[3],
        'max_sessions' => $t[4],
        'order_index'  => $t[5],
    ));
}

// ===================== ADM-04: môn dạy được + lịch trống =====================
$db->update(
    'db_lms_teachers',
    array('teachable_subjects' => json_encode(array(
        $subjectIds['Math Problems'], $subjectIds['Math IQ'],
    ), JSON_UNESCAPED_UNICODE)),
    'user_id = ?',
    array($teacherIds['gv01'])
);
$db->update(
    'db_lms_teachers',
    array('teachable_subjects' => json_encode(array(
        $subjectIds['Math Problems'], $subjectIds['Math IQ'], $subjectIds['Speaking'],
    ), JSON_UNESCAPED_UNICODE)),
    'user_id = ?',
    array($teacherIds['gv02'])
);
$db->update(
    'db_lms_teachers',
    array('teachable_subjects' => json_encode(array(
        $subjectIds['Math Problems'], $subjectIds['Listening'],
    ), JSON_UNESCAPED_UNICODE)),
    'user_id = ?',
    array($teacherIds['gv03'])
);

// hv05 (chờ xếp, Quận 10, offline) cần học Math Problems
$db->update(
    'db_lms_students',
    array(
        'subject_id' => $subjectIds['Math Problems'],
        'free_time_slots' => json_encode(array(
            array('day' => 2, 'start' => '18:00', 'end' => '19:30'),
            array('day' => 4, 'start' => '18:30', 'end' => '20:00'),
        ), JSON_UNESCAPED_UNICODE),
    ),
    'user_id = ?',
    array($studentIds['hv05'])
);

// Lịch trống: gv03 dạy Quận 10 → khớp hv05; gv01/gv02 Quận 1/3 → không khớp hv05
$schedSvc->addFreeSlot($teacherIds['gv01'], 3, '18:00', '19:30');
$schedSvc->addFreeSlot($teacherIds['gv01'], 5, '18:00', '19:30');
$schedSvc->addFreeSlot($teacherIds['gv02'], 6, '09:00', '10:30');
$schedSvc->addFreeSlot($teacherIds['gv03'], 2, '17:30', '19:30');
$schedSvc->addFreeSlot($teacherIds['gv03'], 4, '18:00', '20:30');

// ===================== Lớp học (2) =====================
// Lớp 1-1: gv01 + hv01 — Toán TĐN
$class11 = $classSvc->createClass(array(
    'program_id'        => $progToan,
    'subject_id'        => $subjectIds['Math Problems'],
    'teacher_id'        => $teacherIds['gv01'],
    'class_type'        => 'one_on_one',
    'name'              => 'Toán TĐN 1-1 - Duy Kiên',
    'price_per_session' => 500000,
    'total_sessions'    => 24,
    'hw_deadline_time'  => '12:00',
    'hw_check_hours'    => 24,
));
$classSvc->enrollStudent($class11, $studentIds['hv01']);

// Lớp nhóm: gv02 + hv02..hv04 — Toán IQ (hv05 chờ xếp riêng ở ADM-04)
$classGroup = $classSvc->createClass(array(
    'program_id'        => $progToan,
    'subject_id'        => $subjectIds['Math IQ'],
    'teacher_id'        => $teacherIds['gv02'],
    'class_type'        => 'group',
    'name'              => 'Toán IQ - Nhóm Quận 1 (3 HV)',
    'price_per_session' => 350000,
    'teacher_percentage' => 55.00,
    'total_sessions'    => 16,
    'hw_deadline_time'  => '12:00',
    'hw_check_hours'    => 24,
));
foreach (array('hv02', 'hv03', 'hv04') as $u) {
    $classSvc->enrollStudent($classGroup, $studentIds[$u]);
}

// ===================== Lịch cố định =====================
$monday = date('Y-m-d', strtotime('monday this week'));
// Lịch hiệu lực từ 4 tuần trước để demo sessions/tick/payroll 1 tháng
$schedFrom = date('Y-m-d', strtotime('-4 weeks', strtotime($monday)));
$schedSvc->addSchedule($class11, 2, '17:30', '19:00', $schedFrom);     // T2
$schedSvc->addSchedule($class11, 4, '17:30', '19:00', $schedFrom);     // T4
$schedSvc->addSchedule($classGroup, 3, '18:30', '20:00', $schedFrom);  // T3
$schedSvc->addSchedule($classGroup, 5, '18:30', '20:00', $schedFrom);  // T5

// ===================== Sessions 4 tuần =====================
$from = $monday;
$to = date('Y-m-d', strtotime('+4 weeks'));
$n1 = $sessionSvc->generateForClass($class11, $teacherIds['gv01'], $from, $to);
$n2 = $sessionSvc->generateForClass($classGroup, $teacherIds['gv02'], $from, $to);

// Sinh thêm 1 tháng QUÁ KHỨ để demo tick/payroll (BR-TICK/BR-PAY)
$fromPast = date('Y-m-d', strtotime('-4 weeks', strtotime($monday)));
$toPast = date('Y-m-d', strtotime('-1 day'));
$n1 += $sessionSvc->generateForClass($class11, $teacherIds['gv01'], $fromPast, $toPast);
$n2 += $sessionSvc->generateForClass($classGroup, $teacherIds['gv02'], $fromPast, $toPast);

// Đánh dấu các buổi đã qua là finished + tạo session_students + transactions mẫu
$monthAgo = date('Y-m-d', strtotime('-1 month'));
$pastSessions = $db->safeFetch(
    'select * from db_lms_sessions where session_date < ? order by session_date',
    array(date('Y-m-d'))
);
$finished = 0;
foreach ($pastSessions as $sess) {
    $db->update(
        'db_lms_sessions',
        array(
            'status' => 'finished',
            'lesson_summary' => 'Buổi học diễn ra tốt đẹp. Học viên tiếp thu tốt.',
            'summary_submitted_at' => $sess['session_date'] . ' ' . $sess['start_time'],
        ),
        'id = ?',
        array((int) $sess['id'])
    );
    $sessionSvc->ensureSessionStudents((int) $sess['id']);
    $finished++;

    // Transaction phí dịch vụ cho mỗi buổi finished (demo Payroll Phase 2)
    $class = $db->safeFetchOne('select * from db_lms_classes where id = ?', array((int) $sess['class_id']));
    if (!empty($class)) {
        $price = (float) $class['price_per_session'];
        $pct = $class['teacher_percentage'] !== null
            ? (float) $class['teacher_percentage']
            : (float) $db->safeFetchOne('select service_fee_percentage from db_lms_teachers where user_id = ?', array((int) $sess['teacher_id']))['service_fee_percentage'];
        $amount = round($price * $pct / 100, 2);
        $db->insert('db_lms_transactions', array(
            'teacher_id'      => (int) $sess['teacher_id'],
            'session_id'      => (int) $sess['id'],
            'amount'          => $amount,
            'type'            => 'salary_session',
            'reason'          => 'Phí dịch vụ buổi ' . $sess['session_date'],
            'transaction_date' => $sess['session_date'],
            'period_month'    => substr($sess['session_date'], 0, 7),
        ));
    }
}

// ===================== Tick vi phạm mẫu =====================
$db->insert('db_lms_violations', array(
    'teacher_id'     => $teacherIds['gv02'],
    'class_id'       => $classGroup,
    'tick_amount'    => 1,
    'reason'         => 'manual_penalty',
    'note'           => 'Đi trễ 15 phút (admin ghi)',
    'auto_generated' => 0,
    'violation_date' => date('Y-m-d', strtotime('-10 days')),
));

// 1 tick tự sinh (cron) cho gv01: nộp lesson summary trễ
$lateSession = $db->safeFetchOne(
    'select id, class_id from db_lms_sessions where teacher_id = ? and status = ? order by session_date desc limit 1',
    array($teacherIds['gv01'], 'finished')
);
if (!empty($lateSession)) {
    $db->insert('db_lms_violations', array(
        'teacher_id'     => $teacherIds['gv01'],
        'class_id'       => (int) $lateSession['class_id'],
        'session_id'     => (int) $lateSession['id'],
        'tick_amount'    => 1,
        'reason'         => 'late_lesson_summary',
        'note'           => 'Cron quét: summary trễ hạn (demo)',
        'auto_generated' => 1,
        'violation_date' => date('Y-m-d', strtotime('-3 days')),
    ));
}

// ===================== Violation thresholds (BR-TICK-03) =====================
$db->insert('db_lms_violation_thresholds', array(
    'min_classes'              => 1,
    'max_classes'              => 2,
    'ticks_to_warn'            => 3,
    'ticks_to_demote_fee'      => 5,
    'fee_percentage_after_demote' => 30.00,
    'ticks_exempted'           => 1,
    'effective_from'           => date('Y-m-d', strtotime('-1 month')),
));

// ===================== Thông báo demo =====================
$notifSvc->notify($teacherIds['gv02'], 'tick_warning', 'Cảnh báo vi phạm', 'Bạn có 1 vi phạm nội quy mới.', 'lms/teacher.php?p=lms-vi-pham');
$notifSvc->notify($studentIds['hv01'], 'session_reminder', 'Nhắc lịch học', 'Buổi học tiếp theo vào thứ 2 tuần sau.', 'lms/student.php?p=lms-lich-hoc');

// ===================== Tóm tắt =====================
echo "\n=== SEED XONG ===\n";
echo "Admin:       1 (super_admin)\n";
echo "Giáo viên:   " . count($teacherIds) . "\n";
echo "Học viên:    " . count($studentIds) . "\n";
echo "Lớp 1-1:     $class11 (sessions sinh: $n1)\n";
echo "Lớp nhóm:    $classGroup (sessions sinh: $n2)\n";
echo "Buổi đã qua (finished): $finished\n";
echo "Transactions mẫu: " . (int) $db->count('db_lms_transactions') . "\n";
echo "Vi phạm mẫu: " . (int) $db->count('db_lms_violations') . "\n";
echo "\n--- Tài khoản demo ---\n";
foreach ($creds as $label => $c) {
    $role = ($label === 'admin') ? 'admin' : (strpos($label, 'gv') === 0 ? 'teacher' : 'student');
    $url = ($label === 'admin') ? 'lms/admin.php' : (($role === 'teacher') ? 'lms/teacher.php' : 'lms/student.php');
    printf("%-8s %-12s %s  (%s)\n", $label, $c[0], $c[1], $url);
}
