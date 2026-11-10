-- Migration: 2026_11_09_lms_payment_batches.sql
-- Bổ sung bảng phiên thanh toán (db_lms_payment_batches) và các cột đối soát
-- trên db_lms_transactions phục vụ chức năng "Thanh toán phí dịch vụ" (Phương án A + Payment Batch).

SET NAMES utf8mb4;

SET @dbname = DATABASE();

-- 1. Tạo bảng db_lms_payment_batches (idempotent)
CREATE TABLE IF NOT EXISTS `db_lms_payment_batches` (
    `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    `code` varchar(40) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'Mã phiên thanh toán, VD: PAY-202611-0001',
    `teacher_id` bigint(20) unsigned NOT NULL,
    `date_from` date NOT NULL,
    `date_to` date NOT NULL,
    `gross_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Tổng thu trước khấu trừ',
    `deduction_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Tổng khấu trừ (thuế, phạt, ký quỹ)',
    `net_amount` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Thực nhận trong phiên thanh toán',
    `status` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'paid' COMMENT 'paid | void',
    `note` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `created_by` bigint(20) unsigned DEFAULT NULL,
    `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `voided_at` datetime DEFAULT NULL,
    `voided_by` bigint(20) unsigned DEFAULT NULL,
    `void_reason` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
    `is_locked` tinyint(1) NOT NULL DEFAULT '0' COMMENT '1 = đã khóa sổ / nộp thuế, cấm hoàn tác',
    `locked_at` datetime DEFAULT NULL,
    `locked_by` bigint(20) unsigned DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_batch_code` (`code`),
    KEY `idx_batch_teacher_dates` (`teacher_id`, `date_from`, `date_to`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Thêm cột payment_batch_id vào db_lms_transactions (idempotent)
SET @col_batch_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'db_lms_transactions'
      AND COLUMN_NAME = 'payment_batch_id'
);
SET @sql_add_batch = IF(
    @col_batch_exists = 0,
    'ALTER TABLE `db_lms_transactions` ADD COLUMN `payment_batch_id` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `paid_at`',
    'SELECT 1'
);
PREPARE stmt_add_batch FROM @sql_add_batch;
EXECUTE stmt_add_batch;
DEALLOCATE PREPARE stmt_add_batch;

-- 3. Thêm cột paid_by vào db_lms_transactions (idempotent)
SET @col_paid_by_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'db_lms_transactions'
      AND COLUMN_NAME = 'paid_by'
);
SET @sql_add_paid_by = IF(
    @col_paid_by_exists = 0,
    'ALTER TABLE `db_lms_transactions` ADD COLUMN `paid_by` BIGINT(20) UNSIGNED NULL DEFAULT NULL AFTER `payment_batch_id`',
    'SELECT 1'
);
PREPARE stmt_add_paid_by FROM @sql_add_paid_by;
EXECUTE stmt_add_paid_by;
DEALLOCATE PREPARE stmt_add_paid_by;

-- 4. Thêm cột payment_note vào db_lms_transactions (idempotent)
SET @col_payment_note_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'db_lms_transactions'
      AND COLUMN_NAME = 'payment_note'
);
SET @sql_add_payment_note = IF(
    @col_payment_note_exists = 0,
    'ALTER TABLE `db_lms_transactions` ADD COLUMN `payment_note` VARCHAR(255) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `paid_by`',
    'SELECT 1'
);
PREPARE stmt_add_payment_note FROM @sql_add_payment_note;
EXECUTE stmt_add_payment_note;
DEALLOCATE PREPARE stmt_add_payment_note;

-- 5. Thêm UNIQUE KEY uk_txn_teacher_session_type (teacher_id, session_id, type)
SET @uk_session_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'db_lms_transactions'
      AND INDEX_NAME = 'uk_txn_teacher_session_type'
);
SET @sql_add_uk_session = IF(
    @uk_session_exists = 0,
    'ALTER TABLE `db_lms_transactions` ADD UNIQUE KEY `uk_txn_teacher_session_type` (`teacher_id`, `session_id`, `type`)',
    'SELECT 1'
);
PREPARE stmt_add_uk_session FROM @sql_add_uk_session;
EXECUTE stmt_add_uk_session;
DEALLOCATE PREPARE stmt_add_uk_session;

-- 6. Thêm INDEX idx_txn_payment_batch (payment_batch_id)
SET @idx_batch_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'db_lms_transactions'
      AND INDEX_NAME = 'idx_txn_payment_batch'
);
SET @sql_add_idx_batch = IF(
    @idx_batch_exists = 0,
    'ALTER TABLE `db_lms_transactions` ADD KEY `idx_txn_payment_batch` (`payment_batch_id`)',
    'SELECT 1'
);
PREPARE stmt_add_idx_batch FROM @sql_add_idx_batch;
EXECUTE stmt_add_idx_batch;
DEALLOCATE PREPARE stmt_add_idx_batch;
