<?php
/**
 * Seed dữ liệu mẫu cho trạng thái TRƯỚC KHI GỬI ADMIN DUYỆT TEST (Pre-request state).
 *
 * Màn hình: http://source.test/lms/teacher/lms-hoc-vien-cua-toi/chi-tiet?class_id=1&student_id=5
 * Thiết kế: C:\laragon\www\source\Tai-lieu-phat-trien\teacher\Chi tiết học viên - Lộ trình và Buổi học.png
 *
 * Nghiệp vụ:
 * - Chuyên đề thuộc môn "Math Problems" (subject_id = 10): Model Intermediate 3 (topic_id = 35)
 * - Đã mở khóa cho học viên Duy Kiên (student_id = 5) trong khóa 1 (cycle_id = 1)
 * - Đã hoàn thành đủ số buổi quy định (Đã dạy: 2/2 buổi)
 * - CHƯA gửi yêu cầu duyệt test sang Admin (chưa có bản ghi trong db_lms_tests)
 * - Hiển thị 2 nút cam song song: "Gửi test" và "Thêm buổi"
 * - Khi bấm "Gửi test" mở modal "Request test - Model Intermediate 3"
 * - Khi bấm "Thêm buổi" mở modal "Request thêm buổi học"
 *
 * Ràng buộc toàn vẹn giữa các module:
 * - db_lms_topics: định nghĩa chuyên đề
 * - db_lms_class_topics: liên kết chuyên đề với lớp 1 (dành cho các chu kỳ sau)
 * - db_lms_cycle_topics: liên kết chuyên đề với chu kỳ 1
 * - db_lms_cycle_student_topics: trạng thái unlocked cho học sinh 5
 * - db_lms_sessions: 2 buổi học finished ngày trong quá khứ gắn topic_id 35
 * - db_lms_session_students: điểm danh và kết quả BTVN của học sinh 5
 * - db_lms_tests: không có bản ghi test nào (draft/published) cho topic 35
 *
 * PHP 7.3 compatible.
 */

define('_LMS_PORTAL', 'teacher');
require_once dirname(__DIR__, 2) . '/lms/lib/lms_bootstrap.php';

$d = lms_db();

$classId = 1;
$cycleId = 1;
$studentId = 5;
$teacherId = 2;
$subjectId = 10; // Math Problems
$topicName = 'Model Intermediate 3';

echo "[SEED] Starting pre-request test demo seed...\n";

// 1. db_lms_topics
$topic = $d->safeFetchOne(
    "SELECT id FROM db_lms_topics WHERE name = ? AND subject_id = ? LIMIT 1",
    array($topicName, $subjectId)
);

if (!empty($topic)) {
    $topicId = (int) $topic['id'];
    echo "Found topic '$topicName' ID: $topicId\n";
} else {
    $d->insert('db_lms_topics', array(
        'subject_id' => $subjectId,
        'name' => $topicName,
        'has_test' => 1,
        'has_minitest' => 1,
        'max_sessions' => 2,
        'content' => 'Chuyên đề nâng cao: Phân tích mô hình toán học giải quyết bài toán lời văn phức tạp.',
        'order_index' => 3,
        'test_file_url' => 'test/202610/model-intermediate-3-test.pdf',
        'minitest_file_url' => 'test/202610/model-intermediate-3-minitest.pdf',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ));
    $topicId = (int) $d->insertId;
    echo "Inserted topic '$topicName' ID: $topicId\n";
}

// 2. db_lms_class_topics
$classTopic = $d->safeFetchOne(
    "SELECT id FROM db_lms_class_topics WHERE class_id = ? AND topic_id = ? LIMIT 1",
    array($classId, $topicId)
);
if (empty($classTopic)) {
    $d->insert('db_lms_class_topics', array(
        'class_id' => $classId,
        'topic_id' => $topicId,
        'order_index' => 3,
        'created_at' => date('Y-m-d H:i:s'),
    ));
    echo "Inserted db_lms_class_topics for class $classId, topic $topicId\n";
}

// 3. db_lms_cycle_topics
$cycleTopic = $d->safeFetchOne(
    "SELECT id FROM db_lms_cycle_topics WHERE cycle_id = ? AND topic_id = ? LIMIT 1",
    array($cycleId, $topicId)
);
if (empty($cycleTopic)) {
    $d->insert('db_lms_cycle_topics', array(
        'cycle_id' => $cycleId,
        'topic_id' => $topicId,
        'order_index' => 3,
        'max_sessions' => 2,
        'has_test' => 1,
        'has_minitest' => 1,
    ));
    echo "Inserted db_lms_cycle_topics for cycle $cycleId, topic $topicId\n";
}

// 4. db_lms_cycle_student_topics
$studentTopic = $d->safeFetchOne(
    "SELECT id FROM db_lms_cycle_student_topics WHERE cycle_id = ? AND student_id = ? AND topic_id = ? LIMIT 1",
    array($cycleId, $studentId, $topicId)
);
if (empty($studentTopic)) {
    $d->insert('db_lms_cycle_student_topics', array(
        'cycle_id' => $cycleId,
        'student_id' => $studentId,
        'topic_id' => $topicId,
        'status' => 'unlocked',
        'unlocked_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
        'unlocked_by' => $teacherId,
        'unlock_reason' => 'Mở khóa theo lộ trình học',
    ));
    echo "Inserted db_lms_cycle_student_topics unlocked for student $studentId\n";
} else {
    $d->safeExec(
        "UPDATE db_lms_cycle_student_topics SET status = 'unlocked' WHERE id = ?",
        array($studentTopic['id'])
    );
    echo "Updated db_lms_cycle_student_topics status to unlocked\n";
}

// 5. Ensure NO tests exist in db_lms_tests for this topic
$d->safeExec("DELETE FROM db_lms_tests WHERE class_id = ? AND cycle_id = ? AND topic_id = ?", array($classId, $cycleId, $topicId));
echo "Ensured 0 tests in db_lms_tests for topic $topicId\n";

// 6. db_lms_sessions: Ensure 2 finished sessions exist
$sessions = $d->safeFetch(
    "SELECT id FROM db_lms_sessions WHERE class_id = ? AND cycle_id = ? AND topic_id = ? AND status = 'finished'",
    array($classId, $cycleId, $topicId)
);

if (count($sessions) < 2) {
    // Session 1
    $d->insert('db_lms_sessions', array(
        'class_id' => $classId,
        'cycle_id' => $cycleId,
        'teacher_id' => $teacherId,
        'topic_id' => $topicId,
        'topic_ids' => json_encode(array($topicId)),
        'session_date' => date('Y-m-d', strtotime('-3 days')),
        'start_time' => '17:30:00',
        'end_time' => '19:00:00',
        'lesson_summary' => 'Lý thuyết và bài toán mẫu: Hướng dẫn kỹ thuật phân tích sơ đồ Model Intermediate 3.',
        'hw_content' => 'Làm bài tập 1, 2, 3 trong tài liệu phát triển.',
        'hw_attached_files' => json_encode(array('test/202610/model-intermediate-2-test.pdf'), JSON_UNESCAPED_UNICODE),
        'hw_deadline' => date('Y-m-d 12:00:00', strtotime('-2 days')),
        'status' => 'finished',
        'is_summary_late' => 0,
        'summary_submitted_at' => date('Y-m-d 19:30:00', strtotime('-3 days')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
    ));
    $s1 = (int) $d->insertId;
    $d->insert('db_lms_session_students', array(
        'session_id' => $s1,
        'student_id' => $studentId,
        'hw_submission_files' => json_encode(array('test/demo/sample-submission.pdf'), JSON_UNESCAPED_UNICODE),
        'hw_submitted_at' => date('Y-m-d 08:30:00', strtotime('-2 days')),
        'hw_submission_status' => 'submitted_on_time',
        'status' => 'approved',
        'grade' => 9.0,
        'teacher_feedback_text' => 'Bài làm rất tốt, vẽ sơ đồ đoạn thẳng chuẩn xác và tính đúng đáp số.',
        'teacher_feedback_files' => json_encode(array('test/demo/sample-submission.pdf'), JSON_UNESCAPED_UNICODE),
        'hw_graded_at' => date('Y-m-d 20:00:00', strtotime('-2 days')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-3 days')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
    ));

    // Session 2
    $d->insert('db_lms_sessions', array(
        'class_id' => $classId,
        'cycle_id' => $cycleId,
        'teacher_id' => $teacherId,
        'topic_id' => $topicId,
        'topic_ids' => json_encode(array($topicId)),
        'session_date' => date('Y-m-d', strtotime('-1 days')),
        'start_time' => '17:30:00',
        'end_time' => '19:00:00',
        'lesson_summary' => 'Luyện tập nâng cao và giải bài toán tổng hợp Model Intermediate 3.',
        'hw_content' => 'Làm bài tập 4, 5, 6 trang 35. Chuẩn bị cho bài test chuyên đề.',
        'hw_attached_files' => json_encode(array('test/202610/model-intermediate-2-test.pdf'), JSON_UNESCAPED_UNICODE),
        'hw_deadline' => date('Y-m-d 12:00:00'),
        'status' => 'finished',
        'is_summary_late' => 0,
        'summary_submitted_at' => date('Y-m-d 19:30:00', strtotime('-1 days')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 days')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-1 days')),
    ));
    $s2 = (int) $d->insertId;
    $d->insert('db_lms_session_students', array(
        'session_id' => $s2,
        'student_id' => $studentId,
        'hw_submission_files' => json_encode(array('test/demo/sample-submission.pdf'), JSON_UNESCAPED_UNICODE),
        'hw_submitted_at' => date('Y-m-d 10:15:00'),
        'hw_submission_status' => 'submitted_on_time',
        'status' => 'approved',
        'grade' => 9.5,
        'teacher_feedback_text' => 'Hoàn thành xuất sắc bài tập tổng hợp nâng cao.',
        'teacher_feedback_files' => json_encode(array('test/demo/sample-submission.pdf'), JSON_UNESCAPED_UNICODE),
        'hw_graded_at' => date('Y-m-d 21:00:00', strtotime('-1 days')),
        'created_at' => date('Y-m-d H:i:s', strtotime('-1 days')),
        'updated_at' => date('Y-m-d H:i:s', strtotime('-1 days')),
    ));
    echo "Created 2 finished sessions with IDs $s1 and $s2\n";
}

echo "[SEED] Pre-request test demo seed finished successfully!\n";
