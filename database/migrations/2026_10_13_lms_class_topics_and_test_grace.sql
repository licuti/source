-- ===================== P4+: chuyên đề lớp + hạn chấm test =====================
--
-- N1#2 (mockup ADM-03):
--  - db_lms_class_topics: lớp - chuyên đề nhiều-nhiều (form tạo/sửa lớp có
--    multi-select "Chuyên đề"). FK → classes + topics; unique(class_id, topic_id).
--  - db_lms_classes.test_grace_hours: hạn chấm test/minitest (giờ), mặc định 72h.
--
-- Forward-only. Áp dụng cho DB thật + DB test.
-- Ngày áp dụng: 2026-10-13.

-- 1. Bảng nhiều-nhiều lớp - chuyên đề ------------------------------------
CREATE TABLE IF NOT EXISTS `db_lms_class_topics` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id`    BIGINT UNSIGNED NOT NULL,
    `topic_id`    BIGINT UNSIGNED NOT NULL,
    `order_index` INT          NOT NULL DEFAULT 0,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_class_topic` (`class_id`, `topic_id`),
    KEY `idx_class_topic_topic` (`topic_id`),
    CONSTRAINT `fk_ct_class` FOREIGN KEY (`class_id`)
        REFERENCES `db_lms_classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ct_topic` FOREIGN KEY (`topic_id`)
        REFERENCES `db_lms_topics` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Hạn chấm test trên lớp ----------------------------------------------
ALTER TABLE `db_lms_classes`
    ADD COLUMN `test_grace_hours` INT NOT NULL DEFAULT 72
        COMMENT 'hạn chấm test/minitest (giờ), mặc định 72h' AFTER `hw_check_hours`;
