-- =============================================================================
-- DEMO DATA (DEV ONLY): bài test đã nộp chờ giáo viên chấm
-- GV: gv01 (user_id = 2) - Lớp 1-1 "Toán 1-1 - Duy Kiên 1117" (class_id = 1)
-- HV: Phạm Duy Kiên (student_id = 5), chuyên đề 34 "Model Intermediate 2".
--
-- Mục đích: trang Teacher > Học viên của tôi > chi tiết khóa học hiển thị nút
-- "Chấm test"; bấm vào mở đúng layout "Chi tiết Chấm Test".
-- Idempotent: chạy lại không tạo bản ghi trùng.
-- =============================================================================

START TRANSACTION;

INSERT INTO db_lms_tests
    (class_id, cycle_id, topic_id, test_type, title, test_file_url, deadline, max_score, status, created_by, created_at, updated_at)
SELECT 1, 1, 34, 'test',
       '[DEMO] Model Intermediate 2 - Bài test chờ chấm',
       'test/202610/model-intermediate-2-test.pdf',
       '2026-11-30 23:59:00', 20.00, 'published', 2, NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM db_lms_tests
    WHERE cycle_id = 1 AND topic_id = 34
      AND title = '[DEMO] Model Intermediate 2 - Bài test chờ chấm'
);

SET @demo_test_id = (
    SELECT id FROM db_lms_tests
    WHERE cycle_id = 1 AND topic_id = 34
      AND title = '[DEMO] Model Intermediate 2 - Bài test chờ chấm'
    ORDER BY id DESC LIMIT 1
);

INSERT INTO db_lms_test_submissions
    (test_id, student_id, submission_files, submitted_at, submission_status, created_at, updated_at)
SELECT @demo_test_id, 5, '["test/demo/sample-submission.pdf"]', NOW(), 'submitted_on_time', NOW(), NOW()
FROM DUAL
WHERE NOT EXISTS (
    SELECT 1 FROM db_lms_test_submissions
    WHERE test_id = @demo_test_id AND student_id = 5
);

COMMIT;
