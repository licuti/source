-- Migration: 2026_11_09_lms_session_fee_snapshot.sql
-- Snapshot học phí 1 buổi (price_per_session) và % phí dịch vụ GV (teacher_percentage)
-- trực tiếp trên từng buổi học (db_lms_sessions) để khi Admin điều chỉnh mức phí
-- giữa kỳ/tháng, các buổi đã dạy trước ngày áp dụng giữ nguyên mức cũ.

SET NAMES utf8mb4;

SET @dbname = DATABASE();

-- 1. Thêm cột price_per_session vào db_lms_sessions (idempotent)
SET @col_price_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'db_lms_sessions'
      AND COLUMN_NAME = 'price_per_session'
);
SET @sql_add_price = IF(
    @col_price_exists = 0,
    'ALTER TABLE `db_lms_sessions` ADD COLUMN `price_per_session` DECIMAL(15,2) NULL DEFAULT NULL AFTER `end_time`',
    'SELECT 1'
);
PREPARE stmt_add_price FROM @sql_add_price;
EXECUTE stmt_add_price;
DEALLOCATE PREPARE stmt_add_price;

-- 2. Thêm cột teacher_percentage vào db_lms_sessions (idempotent)
SET @col_pct_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'db_lms_sessions'
      AND COLUMN_NAME = 'teacher_percentage'
);
SET @sql_add_pct = IF(
    @col_pct_exists = 0,
    'ALTER TABLE `db_lms_sessions` ADD COLUMN `teacher_percentage` DECIMAL(5,2) NULL DEFAULT NULL AFTER `price_per_session`',
    'SELECT 1'
);
PREPARE stmt_add_pct FROM @sql_add_pct;
EXECUTE stmt_add_pct;
DEALLOCATE PREPARE stmt_add_pct;

-- 3. Backfill dữ liệu cho các buổi học hiện có
UPDATE `db_lms_sessions` s
JOIN `db_lms_classes` c ON c.id = s.class_id
LEFT JOIN `db_lms_course_cycles` cc ON cc.id = s.cycle_id
LEFT JOIN `db_lms_teachers` t ON t.user_id = s.teacher_id
LEFT JOIN `db_lms_transactions` tr ON tr.session_id = s.id AND tr.type = 'salary_session'
SET
    s.price_per_session = COALESCE(
        s.price_per_session,
        cc.price_per_session,
        c.price_per_session
    ),
    s.teacher_percentage = COALESCE(
        s.teacher_percentage,
        CASE
            WHEN tr.id IS NOT NULL AND COALESCE(cc.price_per_session, c.price_per_session, 0) > 0
            THEN ROUND((tr.amount / COALESCE(cc.price_per_session, c.price_per_session)) * 100, 2)
            ELSE NULL
        END,
        cc.teacher_percentage,
        c.teacher_percentage,
        t.service_fee_percentage
    )
WHERE s.price_per_session IS NULL OR s.teacher_percentage IS NULL;
