-- Phase 2 (Teacher chi tiet hoc vien): bo sung truong cho Request test
-- review_score: diem review nhap trong modal Request test
-- lesson_summary_url: link lesson summary chuyen de sau
-- Run on: phuongnamv_db va phuongnamv_db_lms_test

ALTER TABLE `db_lms_tests`
    ADD COLUMN `review_score` VARCHAR(100) NULL AFTER `max_score`,
    ADD COLUMN `lesson_summary_url` VARCHAR(500) NULL AFTER `review_score`;
