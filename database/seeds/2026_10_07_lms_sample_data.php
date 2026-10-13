<?php

$root = dirname(dirname(__DIR__));
require $root . '/vendor/autoload.php';
require $root . '/lms/lib/lms_bootstrap.php';

/**
 * Chạy nhiều statement SQL từ file seed: xoá dòng comment (-- ...) trước khi cắt
 * theo ';' để comment không dính vào statement đầu tiên làm nó bị bỏ qua.
 *
 * @param LmsDb  $db
 * @param string $sql
 * @return int số statement đã chạy
 */
function lms_seed_run_sql(LmsDb $db, $sql)
{
    $n = 0;
    $lines = preg_split('/\r\n|\n|\r/', (string) $sql);
    $clean = array();
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || strpos($trimmed, '--') === 0) {
            continue;
        }
        $clean[] = $line;
    }
    $body = implode("\n", $clean);
    foreach (explode(';', $body) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt === '') {
            continue;
        }
        $db->safeExec($stmt);
        $n++;
    }
    return $n;
}

$dbName = getenv('LMS_SEED_DB') ? getenv('LMS_SEED_DB') : 'phuongnamv_db';

$db = new LmsDb(array(
    'host' => '127.0.0.1',
    'name' => $dbName,
    'user' => 'root',
    'pass' => '',
));

$userSvc    = new \Lms\UserService($db);
$schedSvc   = new \Lms\ScheduleService($db);
$classSvc   = new \Lms\ClassService($db, $schedSvc);
$sessionSvc = new \Lms\SessionService($db);

$db->safeExec('SET FOREIGN_KEY_CHECKS = 0');
$tables = array(
    'db_lms_session_students', 'db_lms_sessions', 'db_lms_schedules',
    'db_lms_class_students', 'db_lms_classes', 'db_lms_topics',
    'db_lms_subjects', 'db_lms_programs', 'db_lms_monthly_reviews',
    'db_lms_library_files', 'db_lms_test_submissions', 'db_lms_tests',
    'db_lms_transactions', 'db_lms_violations', 'db_lms_violation_thresholds',
    'db_lms_requests', 'db_lms_notifications', 'db_lms_audit_logs',
    'db_lms_cron_runs', 'db_lms_role_permissions', 'db_lms_modules',
    'db_lms_students', 'db_lms_teachers', 'db_lms_admins', 'db_lms_users',
);
foreach ($tables as $t) {
    $db->safeExec('TRUNCATE TABLE `' . $t . '`');
}
$db->safeExec('SET FOREIGN_KEY_CHECKS = 1');

$modulesSql = file_get_contents(__DIR__ . '/2026_10_07_lms_modules_seed.sql');
lms_seed_run_sql($db, $modulesSql);

echo "[OK] Modules + permissions seeded.\n";

$adminId = $userSvc->createUser('admin', 'admin123', 'admin');
$db->insert('db_lms_admins', array(
    'user_id'    => $adminId,
    'full_name'  => 'Quản trị viên',
    'role_level' => 'super_admin',
));

$gv1Id = $userSvc->createUser('gv01', 'gv01123456', 'teacher');
$db->insert('db_lms_teachers', array(
    'user_id'      => $gv1Id,
    'teacher_code' => 'TE001',
    'full_name'    => 'Nguyễn Văn An',
    'gender'       => 'male',
    'status'       => 'active',
    'rank'         => 'gold',
    'max_classes'  => 3,
));

$gv2Id = $userSvc->createUser('gv02', 'gv02123456', 'teacher');
$db->insert('db_lms_teachers', array(
    'user_id'      => $gv2Id,
    'teacher_code' => 'TE002',
    'full_name'    => 'Trần Thị Bình',
    'gender'       => 'female',
    'status'       => 'active',
    'rank'         => 'bronze',
    'max_classes'  => 2,
));

$gv3Id = $userSvc->createUser('gv03', 'gv03123456', 'teacher');
$db->insert('db_lms_teachers', array(
    'user_id'      => $gv3Id,
    'teacher_code' => 'TE003',
    'full_name'    => 'Lê Minh Cường',
    'gender'       => 'male',
    'status'       => 'active',
    'rank'         => 'green',
    'max_classes'  => 2,
));

echo "[OK] 1 Admin + 3 GV created.\n";

$hvIds = array();
$hvData = array(
    array('hv01', 'hv01123456', 'ST001', 'Phạm Duy Kiên', 'Lớp 11', 'offline'),
    array('hv02', 'hv02123456', 'ST002', 'Nguyễn Hoàng Mai', 'Lớp 10', 'offline'),
    array('hv03', 'hv03123456', 'ST003', 'Võ Thanh Tùng', 'Lớp 9', 'online'),
    array('hv04', 'hv04123456', 'ST004', 'Bùi Thị Hương', 'Lớp 12', 'offline'),
    array('hv05', 'hv05123456', 'ST005', 'Đặng Quốc Bảo', 'Lớp 8', 'online'),
);
foreach ($hvData as $hv) {
    $id = $userSvc->createUser($hv[0], $hv[1], 'student');
    $db->insert('db_lms_students', array(
        'user_id'       => $id,
        'student_code'  => $hv[2],
        'full_name'     => $hv[3],
        'grade'         => $hv[4],
        'learning_type' => $hv[5],
        'status'        => 'studying',
    ));
    $hvIds[] = $id;
}

echo "[OK] 5 HV created.\n";

$db->insert('db_lms_programs', array('name' => 'Chương trình Toán THCS-THPT'));
$programId = (int) $db->insertId;

$db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Toán Đại số'));
$subject1Id = (int) $db->insertId;
$db->insert('db_lms_subjects', array('program_id' => $programId, 'name' => 'Toán Hình học'));
$subject2Id = (int) $db->insertId;

$today = date('Y-m-d');

$class1Id = $classSvc->createClass(array(
    'program_id'        => $programId,
    'subject_id'        => $subject1Id,
    'teacher_id'        => $gv1Id,
    'class_type'        => 'one_on_one',
    'name'              => 'Toán 1-1 - Duy Kiên 1117',
    'price_per_session' => 400000,
    'total_sessions'    => 16,
    'hw_deadline_time'  => '12:00',
    'start_date'        => $today,
));
$classSvc->enrollStudent($class1Id, $hvIds[0]);
$classSvc->enrollStudent($class1Id, $hvIds[1]);

$class2Id = $classSvc->createClass(array(
    'program_id'        => $programId,
    'subject_id'        => $subject2Id,
    'teacher_id'        => $gv2Id,
    'class_type'        => 'group',
    'name'              => 'Toán Nhóm - Lớp 9-12',
    'price_per_session' => 300000,
    'total_sessions'    => 12,
    'hw_deadline_time'  => '18:00',
    'start_date'        => $today,
));
$classSvc->enrollStudent($class2Id, $hvIds[2]);
$classSvc->enrollStudent($class2Id, $hvIds[3]);

echo "[OK] 2 lớp created (CLS1: 1-1 GV01+2HV, CLS2: group GV02+2HV).\n";

// ===================== ADM-04: data demo sắp xếp lớp =====================
// hv05 chuyển sang chờ xếp (tách khỏi lớp nhóm) để demo module Sắp xếp lớp.
$db->update(
    'db_lms_students',
    array(
        'status'        => 'pending_arrange',
        'district'      => 'Quận 1',
        'learning_type' => 'offline',
        'subject_id'    => $subject1Id,
        'free_time_slots' => json_encode(array(
            array('day' => 7, 'start' => '09:00', 'end' => '10:30'),
            array('day' => 3, 'start' => '19:30', 'end' => '21:00'),
        ), JSON_UNESCAPED_UNICODE),
    ),
    'user_id = ?',
    array($hvIds[4])
);

// GV dạy được môn Toán Đại số (ADM-04 match theo môn)
$db->update(
    'db_lms_teachers',
    array('teachable_subjects' => json_encode(array($subject1Id, $subject2Id), JSON_UNESCAPED_UNICODE)),
    'user_id = ?',
    array($gv1Id)
);
$db->update(
    'db_lms_teachers',
    array(
        'teachable_subjects'  => json_encode(array($subject1Id), JSON_UNESCAPED_UNICODE),
        'teachable_districts' => json_encode(array('Quận 3'), JSON_UNESCAPED_UNICODE),
    ),
    'user_id = ?',
    array($gv2Id)
);
$db->update(
    'db_lms_teachers',
    array('teachable_subjects' => json_encode(array($subject1Id), JSON_UNESCAPED_UNICODE)),
    'user_id = ?',
    array($gv3Id)
);

// Lịch trống: GV01 khớp T7, GV03 khớp cả T7 lẫn T3, GV02 sai quận (Quận 3)
$schedSvc->addFreeSlot($gv1Id, 7, '08:30', '11:00');
$schedSvc->addFreeSlot($gv3Id, 7, '08:30', '11:00');
$schedSvc->addFreeSlot($gv3Id, 3, '19:00', '21:00');
$schedSvc->addFreeSlot($gv2Id, 3, '18:30', '20:30');

echo "[OK] ADM-04 demo data: hv05 pending_arrange (Quận 1, Toán Đại số).\n";

$monday = date('Y-m-d', strtotime('monday this week'));

$schedSvc->addSchedule($class1Id, 3, '17:30', '19:00', $monday);
$schedSvc->addSchedule($class1Id, 5, '17:30', '19:00', $monday);

$schedSvc->addSchedule($class2Id, 2, '19:30', '21:00', $monday);
$schedSvc->addSchedule($class2Id, 4, '19:30', '21:00', $monday);
$schedSvc->addSchedule($class2Id, 6, '08:00', '09:30', $monday);

echo "[OK] Lịch cố định created.\n";

$fromDate = date('Y-m-d', strtotime('-2 weeks', strtotime($monday)));
$toDate   = date('Y-m-d', strtotime('+2 weeks', strtotime($monday)));

$c1 = $sessionSvc->generateForClass($class1Id, $gv1Id, $fromDate, $toDate);
$c2 = $sessionSvc->generateForClass($class2Id, $gv2Id, $fromDate, $toDate);

$pastSessions = $db->safeFetch(
    'select id from db_lms_sessions where session_date < ? and status = ? limit 5',
    array($today, 'upcoming')
);
foreach ($pastSessions as $ps) {
    $db->update('db_lms_sessions', array('status' => 'finished'), 'id = ?', array((int) $ps['id']));
}

$totalStudents = 0;
$allSessions = $db->safeFetch('select id from db_lms_sessions');
foreach ($allSessions as $s) {
    $totalStudents += $sessionSvc->ensureSessionStudents((int) $s['id']);
}

echo "[OK] Sessions: class1={$c1}, class2={$c2}, total_session_students={$totalStudents}.\n";

// ===================== Violation thresholds (BR-TICK-03) =====================
// Script này TRUNCATE db_lms_violation_thresholds ở đầu (xem list $truncate),
// nên phải insert lại đủ 3 khoảng — đồng nhất với
// 2026_10_10_lms_violation_thresholds_seed.sql.
// fee_percentage_after_demote là tỷ lệ trực tiếp (30% < mặc định 48%).
foreach (array(
    array(1, 3, 2),
    array(4, 7, 3),
    array(8, null, 4),
) as $threshold) {
    $db->insert('db_lms_violation_thresholds', array(
        'min_classes'                 => $threshold[0],
        'max_classes'                 => $threshold[1],
        'ticks_to_warn'               => 5,
        'ticks_to_demote_fee'         => 8,
        'fee_percentage_after_demote' => 30.00,
        'ticks_exempted'              => $threshold[2],
        'effective_from'              => '2026-10-10',
    ));
}

echo "[OK] Violation thresholds seeded (3 khoảng, fee_after_demote=30%).\n";

$db->insert('db_lms_notifications', array(
    'user_id' => $adminId,
    'type'    => 'system',
    'title'   => 'Hệ thống sẵn sàng',
    'message' => 'Dữ liệu mẫu Phase 0.6 đã được seed thành công.',
    'is_read' => 0,
));

foreach (array($gv1Id, $gv2Id) as $tid) {
    $db->insert('db_lms_notifications', array(
        'user_id' => $tid,
        'type'    => 'session_reminder',
        'title'   => 'Nhắc lịch dạy',
        'message' => 'Bạn có buổi dạy sắp tới. Vui lòng chuẩn bị giáo án.',
        'is_read' => 0,
    ));
}

$db->insert('db_lms_notifications', array(
    'user_id' => $hvIds[0],
    'type'    => 'session_reminder',
    'title'   => 'Lịch học sắp tới',
    'message' => 'Bạn có buổi học Toán Đại số trong tuần này.',
    'is_read' => 0,
));

echo "[OK] Notifications seeded.\n";

echo "\n=== SEED COMPLETE ===\n";
echo "DB: {$dbName}\n";
echo "Admin: admin / admin123\n";
echo "GV: gv01/gv01123456, gv02/gv02123456, gv03/gv03123456\n";
echo "HV: hv01/hv01123456 .. hv05/hv05123456\n";
echo "Classes: 2 | Sessions: " . ($c1 + $c2) . "\n";
