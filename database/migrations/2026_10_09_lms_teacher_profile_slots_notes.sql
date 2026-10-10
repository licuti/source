-- ---------------------------------------------------------------------------
-- Migration: 2026_10_09_lms_teacher_profile_slots_notes — TCH-01/02 (lms-lich-day)
--
-- Bổ sung cho module Quản lý lịch của portal Teacher (mockup §4 + §7):
--  1. db_lms_teachers.current_address     — "Địa chỉ hiện tại" (§4.1)
--  2. db_lms_sessions.topic_ids           — multi-select chuyên đề (§7.1);
--     giữ topic_id FK đơn để tương thích code/admin cũ.
--  3. db_lms_teacher_free_slots           — "Đăng ký lịch trống" (§4.2, ô xanh)
--  4. db_lms_teacher_notes                — "Ghi chú" theo tuần (§4.4)
--
-- Chạy SAU 2026_10_07_lms_core. Forward-only.
-- ---------------------------------------------------------------------------

-- 1. Địa chỉ hiện tại của giáo viên -----------------------------------------
ALTER TABLE `db_lms_teachers`
    ADD COLUMN `current_address` VARCHAR(255) NULL
        COMMENT 'Địa chỉ GV đang sinh sống (mockup §4.1)'
        AFTER `tax_code`;

-- 2. Multi-select chuyên đề của buổi học ------------------------------------
ALTER TABLE `db_lms_sessions`
    ADD COLUMN `topic_ids` JSON NULL
        COMMENT 'Nhiều chuyên đề (§7.1); topic_id FK đơn giữ làm tương thích'
        AFTER `topic_id`;

-- 3. Lịch trống giáo viên đăng ký nhận thêm lớp -----------------------------
CREATE TABLE `db_lms_teacher_free_slots` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `teacher_id`     BIGINT UNSIGNED NOT NULL,
    `day_of_week`    TINYINT     NOT NULL                  COMMENT '2=Thứ2 .. 8=Chủ nhật',
    `start_time`     TIME        NOT NULL,
    `end_time`       TIME        NOT NULL,
    `effective_from` DATE        NOT NULL,
    `effective_to`   DATE            NULL                  COMMENT 'NULL = vô thời hạn',
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_tfs_teacher_dow` (`teacher_id`, `day_of_week`),
    CONSTRAINT `fk_free_slot_teacher` FOREIGN KEY (`teacher_id`)
        REFERENCES `db_lms_teachers` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Ghi chú tuần của giáo viên ---------------------------------------------
CREATE TABLE `db_lms_teacher_notes` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `week_start` DATE        NOT NULL                  COMMENT 'thứ 2 của tuần',
    `note`       TEXT            NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_teacher_week` (`teacher_id`, `week_start`),
    CONSTRAINT `fk_note_teacher` FOREIGN KEY (`teacher_id`)
        REFERENCES `db_lms_teachers` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
