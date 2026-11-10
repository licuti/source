-- Migration: B? sung avatar, subject_ids, waiting_from, expected_start_date cho db_lms_students theo mockup Qu?n lý tài kho?n HV
ALTER TABLE db_lms_students
  ADD COLUMN avatar VARCHAR(255) DEFAULT NULL COMMENT '?nh d?i di?n h?c viên' AFTER full_name,
  ADD COLUMN subject_ids TEXT DEFAULT NULL COMMENT 'JSON danh sách môn h?c HV dang ký' AFTER subject_id,
  ADD COLUMN waiting_from DATE DEFAULT NULL COMMENT 'Ch? s?p x?p t? ngày' AFTER free_time_slots,
  ADD COLUMN expected_start_date DATE DEFAULT NULL COMMENT 'Ngày d? ki?n b?t d?u h?c' AFTER waiting_from;

ALTER TABLE db_lms_students
  MODIFY COLUMN district VARCHAR(255) DEFAULT NULL COMMENT 'Qu?n/huy?n (ho?c JSON danh sách qu?n)';
