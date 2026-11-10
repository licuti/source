-- ---------------------------------------------------------------------------
-- Migration: 2026_10_31_lms_topics_content
-- Bổ sung cột content (Nội dung chuyên đề) tách biệt với report_template
-- (Template báo cáo) cho bảng db_lms_topics theo mockup Quản lý lộ trình.
-- ---------------------------------------------------------------------------

ALTER TABLE `db_lms_topics`
    ADD COLUMN `content` TEXT NULL COMMENT 'Nội dung tóm tắt chuyên đề' AFTER `max_sessions`;
