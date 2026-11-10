-- Migration: 2026_11_09_lms_payment_idempotency_and_group.sql
-- 1. Bổ sung group_code vào db_lms_payment_batches để gom nhiều batch cùng một phiên bấm thanh toán
-- 2. Bổ sung idempotency_key vào db_lms_transactions để chống double-click cho các giao dịch không có session

SET NAMES utf8mb4;

SET @dbname = DATABASE();

-- 1. Thêm cột group_code vào db_lms_payment_batches (idempotent)
SET @col_group_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'db_lms_payment_batches'
      AND COLUMN_NAME = 'group_code'
);
SET @sql_add_group = IF(
    @col_group_exists = 0,
    'ALTER TABLE `db_lms_payment_batches` ADD COLUMN `group_code` VARCHAR(40) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `code`',
    'SELECT 1'
);
PREPARE stmt_add_group FROM @sql_add_group;
EXECUTE stmt_add_group;
DEALLOCATE PREPARE stmt_add_group;

-- 2. Thêm index cho group_code
SET @idx_group_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'db_lms_payment_batches'
      AND INDEX_NAME = 'idx_batch_group_code'
);
SET @sql_add_idx_group = IF(
    @idx_group_exists = 0,
    'ALTER TABLE `db_lms_payment_batches` ADD KEY `idx_batch_group_code` (`group_code`)',
    'SELECT 1'
);
PREPARE stmt_add_idx_group FROM @sql_add_idx_group;
EXECUTE stmt_add_idx_group;
DEALLOCATE PREPARE stmt_add_idx_group;

-- 3. Thêm cột idempotency_key vào db_lms_transactions (idempotent)
SET @col_idem_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'db_lms_transactions'
      AND COLUMN_NAME = 'idempotency_key'
);
SET @sql_add_idem = IF(
    @col_idem_exists = 0,
    'ALTER TABLE `db_lms_transactions` ADD COLUMN `idempotency_key` VARCHAR(64) COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL AFTER `payment_note`',
    'SELECT 1'
);
PREPARE stmt_add_idem FROM @sql_add_idem;
EXECUTE stmt_add_idem;
DEALLOCATE PREPARE stmt_add_idem;

-- 4. Thêm UNIQUE KEY uk_txn_idempotency
SET @uk_idem_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_SCHEMA = @dbname
      AND TABLE_NAME = 'db_lms_transactions'
      AND INDEX_NAME = 'uk_txn_idempotency'
);
SET @sql_add_uk_idem = IF(
    @uk_idem_exists = 0,
    'ALTER TABLE `db_lms_transactions` ADD UNIQUE KEY `uk_txn_idempotency` (`idempotency_key`)',
    'SELECT 1'
);
PREPARE stmt_add_uk_idem FROM @sql_add_uk_idem;
EXECUTE stmt_add_uk_idem;
DEALLOCATE PREPARE stmt_add_uk_idem;
