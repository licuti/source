-- Phase 2 (Tai chinh): bo sung trang thai duyet cho db_lms_transactions
-- BR-PAY-04 / ADM-07: quy trình draft -> pending -> paid
-- Run on: phuongnamv_db va phuongnamv_db_lms_test

ALTER TABLE `db_lms_transactions`
    ADD COLUMN `status`  VARCHAR(30) NOT NULL DEFAULT 'draft'
        COMMENT 'draft | pending | paid' AFTER `period_month`,
    ADD COLUMN `paid_at` DATETIME    NULL AFTER `status`,
    ADD KEY `idx_txn_month_status` (`period_month`, `status`);
