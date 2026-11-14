-- ---------------------------------------------------------------------------
-- Migration: 2026_11_13_lms_topics_test_files.sql
-- Bổ sung các cột test_file_url và minitest_file_url cho bảng db_lms_topics
-- phục vụ quản lý file đề test/minitest mẫu cho chuyên đề.
-- ---------------------------------------------------------------------------

ALTER TABLE `db_lms_topics`
    ADD COLUMN `test_file_url` VARCHAR(500) NULL AFTER `report_template`,
    ADD COLUMN `minitest_file_url` VARCHAR(500) NULL AFTER `test_file_url`;
