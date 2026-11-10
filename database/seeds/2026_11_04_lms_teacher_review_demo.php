<?php
/**
 * Demo data cho trang Teacher — Nhận xét theo tháng (TCH-06).
 *
 * - Bổ sung lịch cho lớp 1-1 IELTS (class_id = 4, gv01).
 * - Seed nhận xét tháng 09/2026 + 10/2026 cho:
 *   + Lớp 1-1: class 4 (Nguyễn Hoàng Mai).
 *   + Lớp nhóm: class 34 (3 HV) và class 35 (3 HV) của gv01.
 *   (Class 1 đã có dữ liệu từ seed Student.)
 *
 * Idempotent: INSERT ... ON DUPLICATE KEY UPDATE id = id, chỉ thêm lịch khi trống.
 *
 * Chạy: php database/seeds/2026_11_04_lms_teacher_review_demo.php
 *
 * PHP 7.3 compatible.
 */

require dirname(dirname(__DIR__)) . '/vendor/autoload.php';
require dirname(dirname(__DIR__)) . '/lms/lib/lms_bootstrap.php';

$db = lms_db();

// ---- Lịch lớp 4 (IELTS 1 kèm 1 - Hoàng Mai) ----
$classId = 4;
$slots = array(
    array('day' => 2, 'start' => '19:00:00', 'end' => '20:30:00'),
    array('day' => 4, 'start' => '19:00:00', 'end' => '20:30:00'),
);

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
            'effective_from' => '2026-10-05',
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
                'effective_from' => '2026-10-05',
                'effective_to'   => null,
            ));
        }
    }
}

// ---- Nhận xét mẫu ----
$reviews = array(
    4 => array(
        6 => array(
            '2026-10' => 'Con có tiến bộ rõ rệt ở kỹ năng Writing, cần duy trì luyện đề và bổ sung từ vựng học thuật.',
            '2026-09' => 'Con nắm được cấu trúc bài thi, cần cải thiện tốc độ đọc và độ chính xác khi làm Reading.',
        ),
    ),
    34 => array(
        6 => array(
            '2026-10' => 'Hoàn thành đầy đủ BTVN, chủ động phát biểu và có tiến bộ trong giải bài tập.',
            '2026-09' => 'Cần tập trung hơn khi làm bài nhóm, đã cải thiện cách trình bày lời giải.',
        ),
        7 => array(
            '2026-10' => 'Nắm chắc kiến thức cơ bản, cần luyện thêm dạng bài vận dụng nâng cao.',
            '2026-09' => 'Tích cực tham gia hoạt động nhóm, cần kiểm tra kỹ lại bài trước khi nộp.',
        ),
        8 => array(
            '2026-10' => 'Có tiến bộ trong tháng, cần chú ý hơn đến việc ghi chép và ôn tập lại bài cũ.',
            '2026-09' => 'Hoàn thành bài tập đúng hạn, cần mạnh dạn hỏi giáo viên khi chưa hiểu bài.',
        ),
    ),
    35 => array(
        7 => array(
            '2026-10' => 'Phát âm tốt hơn, cần luyện thêm phản xạ nghe và mở rộng vốn từ theo chủ đề.',
            '2026-09' => 'Tự tin hơn khi giao tiếp, cần chú ý ngữ pháp khi viết câu dài.',
        ),
        8 => array(
            '2026-10' => 'Tiếp thu tốt, hoàn thành đủ bài tập và có tiến bộ về kỹ năng Reading.',
            '2026-09' => 'Cần tăng thời gian luyện nghe, đã cải thiện cách phát âm từ khó.',
        ),
        9 => array(
            '2026-10' => 'Có tiến bộ về từ vựng, cần luyện thêm kỹ năng nói và sự tự tin khi thuyết trình.',
            '2026-09' => 'Chăm chỉ làm bài, cần chú ý đến thì và cấu trúc câu khi viết.',
        ),
    ),
);

$saved = 0;
foreach ($reviews as $classId => $students) {
    $class = $db->safeFetchOne(
        'select id, teacher_id from db_lms_classes where id = ? limit 1',
        array($classId)
    );
    if (empty($class)) {
        continue;
    }
    foreach ($students as $studentId => $months) {
        $enrolled = $db->safeFetchOne(
            'select id from db_lms_class_students where class_id = ? and student_id = ? and status = ? limit 1',
            array($classId, $studentId, 'active')
        );
        if (empty($enrolled)) {
            continue;
        }
        foreach ($months as $month => $content) {
            $ok = $db->safeExec(
                'insert into db_lms_monthly_reviews'
                . ' (student_id, class_id, teacher_id, review_month, overall_rating, content, progress_percent, published_at)'
                . ' values (?, ?, ?, ?, ?, ?, ?, ?)'
                . ' on duplicate key update id = id',
                array(
                    (int) $studentId,
                    (int) $classId,
                    (int) $class['teacher_id'],
                    $month,
                    null,
                    $content,
                    null,
                    $month . '-28 10:00:00',
                )
            );
            if ($ok) {
                $saved++;
            }
        }
    }
}

echo "[OK] Seeded teacher monthly review demo (" . $saved . " rows processed).\n";
