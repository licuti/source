<?php
/**
 * Demo data cho trang Student — Nhận xét tháng (STU-04).
 *
 * - Bổ sung lịch cố định cho lớp 3 và lớp 5 (hv01) để card hiển thị đủ thông tin.
 * - Seed nhận xét tháng 09/2026 + 10/2026 cho 5 lớp đang học của hv01.
 *
 * Idempotent: dùng INSERT ... ON DUPLICATE KEY UPDATE id = id và chỉ thêm lịch
 * khi lớp/cycle chưa có lịch. Không ghi đè nhận xét đã có.
 *
 * Chạy: php database/seeds/2026_11_04_lms_student_review_demo.php
 *
 * PHP 7.3 compatible.
 */

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';
require dirname(dirname(__DIR__)) . '/lms/lib/lms_bootstrap.php';

$db = lms_db();

$studentId = 5; // hv01

// ---- Lịch cố định cho lớp 3 và lớp 5 (nếu chưa có) ----
$extraSchedules = array(
    3 => array(
        array('day' => 2, 'start' => '19:00:00', 'end' => '20:30:00'),
        array('day' => 4, 'start' => '19:00:00', 'end' => '20:30:00'),
    ),
    5 => array(
        array('day' => 3, 'start' => '18:00:00', 'end' => '19:30:00'),
        array('day' => 6, 'start' => '18:00:00', 'end' => '19:30:00'),
    ),
);

foreach ($extraSchedules as $classId => $slots) {
    $classScheduleCount = (int) $db->safeFetchOne(
        'select count(*) as c from db_lms_schedules where class_id = ?',
        array($classId)
    )['c'];
    if ($classScheduleCount === 0) {
        foreach ($slots as $slot) {
            $db->insert('db_lms_schedules', array(
                'class_id'       => $classId,
                'day_of_week'    => $slot['day'],
                'start_time'     => $slot['start'],
                'end_time'       => $slot['end'],
                'effective_from' => '2026-10-01',
                'effective_to'   => null,
            ));
        }
    }

    $cycle = $db->safeFetchOne(
        'select id from db_lms_course_cycles where class_id = ? and status = ? order by id limit 1',
        array($classId, 'active')
    );
    if (!empty($cycle)) {
        $cycleId = (int) $cycle['id'];
        $cycleScheduleCount = (int) $db->safeFetchOne(
            'select count(*) as c from db_lms_cycle_schedules where cycle_id = ?',
            array($cycleId)
        )['c'];
        if ($cycleScheduleCount === 0) {
            foreach ($slots as $slot) {
                $db->insert('db_lms_cycle_schedules', array(
                    'cycle_id'       => $cycleId,
                    'day_of_week'    => $slot['day'],
                    'start_time'     => $slot['start'],
                    'end_time'       => $slot['end'],
                    'effective_from' => '2026-10-01',
                    'effective_to'   => null,
                ));
            }
        }
    }
}

// ---- Nội quy lớp học mẫu (chỉ thay nội dung smoke test cũ) ----
$rulesSvc = new \Lms\ClassRulesService($db);
$currentRules = $rulesSvc->getRules();
if (strpos((string) $currentRules['content'], 'smoke test') !== false) {
    $admin = $db->safeFetchOne('select user_id from db_lms_admins order by user_id limit 1');
    $rulesSvc->saveRules(
        '<h3>NỘI QUY LỚP HỌC</h3>'
        . '<p><strong>1. Thời gian và điểm danh</strong></p>'
        . '<ul><li>Học viên vào lớp trước giờ học 5 phút, chuẩn bị đầy đủ sách vở và thiết bị học tập.</li>'
        . '<li>Nghỉ học cần báo trước cho giáo viên chủ nhiệm ít nhất 04 giờ.</li></ul>'
        . '<p><strong>2. Bài tập về nhà</strong></p>'
        . '<ul><li>Hoàn thành BTVN trước hạn nộp được ghi trong buổi học.</li>'
        . '<li>Bài nộp trễ sẽ được ghi nhận và ảnh hưởng đến đánh giá tháng.</li></ul>'
        . '<p><strong>3. Thái độ học tập</strong></p>'
        . '<ul><li>Tôn trọng giáo viên và các bạn, không làm việc riêng trong giờ học.</li>'
        . '<li>Chủ động trao đổi khi chưa hiểu bài, không ngại đặt câu hỏi.</li></ul>'
        . '<p><em>Chúc các em học tập hiệu quả và đạt kết quả tốt!</em></p>',
        !empty($admin) ? (int) $admin['user_id'] : 0
    );
}

// ---- Nhận xét tháng mẫu ----
$reviews = array(
    1 => array(
        '2026-10' => 'Có tiến bộ, hoàn thành tốt các bài tập được giao và cải thiện tình trạng không làm BTVN.',
        '2026-09' => 'Đã chăm chỉ hơn, cần duy trì việc làm đầy đủ BTVN trước buổi học.',
    ),
    3 => array(
        '2026-10' => 'Con tiếp thu tốt, cần luyện thêm kỹ năng Reading và chủ động ghi chú từ vựng mới.',
        '2026-09' => 'Phát âm có tiến bộ, cần tăng thời gian luyện nghe mỗi ngày.',
    ),
    5 => array(
        '2026-10' => 'Có tiến bộ rõ rệt trong việc vận dụng công thức, cần cẩn thận hơn khi trình bày bài.',
        '2026-09' => 'Nắm được kiến thức cơ bản, cần luyện thêm dạng bài tính toán nhanh.',
    ),
    10 => array(
        '2026-10' => 'Hoàn thành đầy đủ BTVN, tinh thần học tập tích cực và có tiến bộ trong tháng.',
        '2026-09' => 'Cần tập trung hơn trong giờ học, đã cải thiện tốc độ làm bài.',
    ),
    37 => array(
        '2026-10' => 'Bài viết có bố cục rõ ràng, cần khai thác thêm dẫn chứng thực tế để bài thuyết phục hơn.',
        '2026-09' => 'Diễn đạt tốt hơn, cần chú ý lỗi chính tả khi viết đoạn văn nghị luận.',
    ),
);

$inserted = 0;
foreach ($reviews as $classId => $byMonth) {
    $class = $db->safeFetchOne(
        'select id, teacher_id from db_lms_classes where id = ? limit 1',
        array($classId)
    );
    if (empty($class)) {
        continue;
    }
    $enrolled = $db->safeFetchOne(
        'select id from db_lms_class_students where class_id = ? and student_id = ? and status = ? limit 1',
        array($classId, $studentId, 'active')
    );
    if (empty($enrolled)) {
        continue;
    }

    foreach ($byMonth as $month => $content) {
        $publishedAt = $month . '-28 10:00:00';
        $ok = $db->safeExec(
            'insert into db_lms_monthly_reviews'
            . ' (student_id, class_id, teacher_id, review_month, overall_rating, content, progress_percent, published_at)'
            . ' values (?, ?, ?, ?, ?, ?, ?, ?)'
            . ' on duplicate key update id = id',
            array(
                $studentId,
                (int) $classId,
                (int) $class['teacher_id'],
                $month,
                'good',
                $content,
                75 + ((int) substr($month, -2) % 15),
                $publishedAt,
            )
        );
        if ($ok) {
            $inserted++;
        }
    }
}

echo "[OK] Seeded monthly review demo for student #" . $studentId . " (" . $inserted . " rows processed).\n";
