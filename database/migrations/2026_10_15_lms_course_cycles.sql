-- ---------------------------------------------------------------------------
-- Migration: 2026_10_15_lms_course_cycles
-- Course cycles (Khóa 1/2/3), per-cycle snapshots and per-student unlocks.
-- Forward-only, MySQL 5.7 compatible. Run after all LMS 2026_10_14 migrations.
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS `db_lms_course_cycles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `cycle_no` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active'
        COMMENT 'active | completed | cancelled',
    `teacher_id` BIGINT UNSIGNED NOT NULL,
    `price_per_session` DECIMAL(15,2) NOT NULL DEFAULT 0,
    `teacher_percentage` DECIMAL(5,2) NULL,
    `hw_deadline_time` TIME NULL,
    `hw_check_hours` INT NOT NULL DEFAULT 24,
    `test_grace_hours` INT NOT NULL DEFAULT 72,
    `start_date` DATE NULL,
    `expected_end_date` DATE NULL,
    `total_sessions` INT NOT NULL DEFAULT 0,
    `remaining_sessions` INT NOT NULL DEFAULT 0,
    `closed_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_cycle_class_no` (`class_id`, `cycle_no`),
    KEY `idx_cycle_class_status` (`class_id`, `status`),
    CONSTRAINT `fk_cycle_class` FOREIGN KEY (`class_id`)
        REFERENCES `db_lms_classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cycle_teacher` FOREIGN KEY (`teacher_id`)
        REFERENCES `db_lms_teachers` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `db_lms_cycle_students` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cycle_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active'
        COMMENT 'active | completed | left',
    `enrolled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `left_at` DATETIME NULL,
    `snapshot_data` JSON NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_cycle_student` (`cycle_id`, `student_id`),
    KEY `idx_cycle_student_status` (`student_id`, `status`),
    CONSTRAINT `fk_cycle_student_cycle` FOREIGN KEY (`cycle_id`)
        REFERENCES `db_lms_course_cycles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cycle_student_user` FOREIGN KEY (`student_id`)
        REFERENCES `db_lms_students` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `db_lms_cycle_schedules` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cycle_id` BIGINT UNSIGNED NOT NULL,
    `day_of_week` TINYINT NOT NULL COMMENT '2=Monday .. 8=Sunday',
    `start_time` TIME NOT NULL,
    `end_time` TIME NOT NULL,
    `effective_from` DATE NOT NULL,
    `effective_to` DATE NULL,
    PRIMARY KEY (`id`),
    KEY `idx_cycle_schedule` (`cycle_id`, `day_of_week`, `start_time`),
    CONSTRAINT `fk_cycle_schedule_cycle` FOREIGN KEY (`cycle_id`)
        REFERENCES `db_lms_course_cycles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `db_lms_cycle_topics` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cycle_id` BIGINT UNSIGNED NOT NULL,
    `topic_id` BIGINT UNSIGNED NOT NULL,
    `order_index` INT NOT NULL DEFAULT 0,
    `max_sessions` INT NULL,
    `has_test` TINYINT(1) NOT NULL DEFAULT 0,
    `has_minitest` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_cycle_topic` (`cycle_id`, `topic_id`),
    KEY `idx_cycle_topic_order` (`cycle_id`, `order_index`),
    CONSTRAINT `fk_cycle_topic_cycle` FOREIGN KEY (`cycle_id`)
        REFERENCES `db_lms_course_cycles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cycle_topic_topic` FOREIGN KEY (`topic_id`)
        REFERENCES `db_lms_topics` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `db_lms_cycle_student_topics` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `cycle_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL,
    `topic_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'locked' COMMENT 'locked | unlocked',
    `unlocked_at` DATETIME NULL,
    `unlocked_by` BIGINT UNSIGNED NULL,
    `unlock_reason` VARCHAR(255) NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_cycle_student_topic` (`cycle_id`, `student_id`, `topic_id`),
    KEY `idx_student_topic_status` (`student_id`, `status`),
    CONSTRAINT `fk_cycle_st_topic_cycle` FOREIGN KEY (`cycle_id`)
        REFERENCES `db_lms_course_cycles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cycle_st_topic_student` FOREIGN KEY (`student_id`)
        REFERENCES `db_lms_students` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_cycle_st_topic_topic` FOREIGN KEY (`topic_id`)
        REFERENCES `db_lms_topics` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_cycle_st_topic_unlocker` FOREIGN KEY (`unlocked_by`)
        REFERENCES `db_lms_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `db_lms_cycle_session_completions` (
    `session_id` BIGINT UNSIGNED NOT NULL,
    `cycle_id` BIGINT UNSIGNED NOT NULL,
    `completed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`session_id`),
    KEY `idx_cycle_completion` (`cycle_id`, `completed_at`),
    CONSTRAINT `fk_cycle_completion_session` FOREIGN KEY (`session_id`)
        REFERENCES `db_lms_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cycle_completion_cycle` FOREIGN KEY (`cycle_id`)
        REFERENCES `db_lms_course_cycles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- New columns are added only when absent so the migration is safe to re-run.
SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE db_lms_sessions ADD COLUMN cycle_id BIGINT UNSIGNED NULL AFTER class_id, ADD KEY idx_session_cycle_date (cycle_id, session_date)',
    'SELECT 1'
) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'db_lms_sessions' AND COLUMN_NAME = 'cycle_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE db_lms_sessions ADD CONSTRAINT fk_session_cycle FOREIGN KEY (cycle_id) REFERENCES db_lms_course_cycles (id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
) FROM information_schema.TABLE_CONSTRAINTS
 WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'db_lms_sessions' AND CONSTRAINT_NAME = 'fk_session_cycle');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE db_lms_tests ADD COLUMN cycle_id BIGINT UNSIGNED NULL AFTER class_id, ADD KEY idx_test_cycle_type (cycle_id, test_type)',
    'SELECT 1'
) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'db_lms_tests' AND COLUMN_NAME = 'cycle_id');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE db_lms_tests ADD CONSTRAINT fk_test_cycle FOREIGN KEY (cycle_id) REFERENCES db_lms_course_cycles (id) ON DELETE SET NULL ON UPDATE CASCADE',
    'SELECT 1'
) FROM information_schema.TABLE_CONSTRAINTS
 WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = 'db_lms_tests' AND CONSTRAINT_NAME = 'fk_test_cycle');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    COUNT(*) = 0,
    'ALTER TABLE db_lms_students ADD COLUMN gender VARCHAR(30) NULL AFTER full_name, ADD KEY idx_student_gender (gender)',
    'SELECT 1'
) FROM information_schema.COLUMNS
 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'db_lms_students' AND COLUMN_NAME = 'gender');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill one immutable snapshot for each existing class.
INSERT INTO db_lms_course_cycles (
    class_id, cycle_no, name, status, teacher_id, price_per_session,
    teacher_percentage, hw_deadline_time, hw_check_hours, test_grace_hours,
    start_date, expected_end_date, total_sessions, remaining_sessions
)
SELECT c.id, 1, 'Khóa 1',
       CASE WHEN c.status = 'finished' THEN 'completed'
            WHEN c.status = 'cancelled' THEN 'cancelled' ELSE 'active' END,
       c.teacher_id, c.price_per_session, c.teacher_percentage,
       c.hw_deadline_time, c.hw_check_hours, c.test_grace_hours,
       c.start_date, c.expected_end_date, c.total_sessions, c.remaining_sessions
FROM db_lms_classes c
LEFT JOIN db_lms_course_cycles cc ON cc.class_id = c.id AND cc.cycle_no = 1
WHERE cc.id IS NULL AND c.status <> 'arranging';

INSERT INTO db_lms_cycle_students (cycle_id, student_id, status, enrolled_at)
SELECT cc.id, cs.student_id,
       CASE WHEN cs.status = 'active' THEN 'active' ELSE 'completed' END,
       cs.enrolled_at
FROM db_lms_course_cycles cc
JOIN db_lms_class_students cs ON cs.class_id = cc.class_id
LEFT JOIN db_lms_cycle_students existing
  ON existing.cycle_id = cc.id AND existing.student_id = cs.student_id
WHERE cc.cycle_no = 1 AND existing.id IS NULL;

INSERT INTO db_lms_cycle_schedules (cycle_id, day_of_week, start_time, end_time, effective_from, effective_to)
SELECT cc.id, s.day_of_week, s.start_time, s.end_time, s.effective_from, s.effective_to
FROM db_lms_course_cycles cc
JOIN db_lms_schedules s ON s.class_id = cc.class_id
LEFT JOIN db_lms_cycle_schedules existing
  ON existing.cycle_id = cc.id AND existing.day_of_week = s.day_of_week
 AND existing.start_time = s.start_time AND existing.end_time = s.end_time
WHERE cc.cycle_no = 1 AND existing.id IS NULL;

INSERT INTO db_lms_cycle_topics (cycle_id, topic_id, order_index, max_sessions, has_test, has_minitest)
SELECT cc.id, t.id, COALESCE(ct.order_index, t.order_index), t.max_sessions, t.has_test, t.has_minitest
FROM db_lms_course_cycles cc
JOIN db_lms_topics t ON t.subject_id = (SELECT subject_id FROM db_lms_classes WHERE id = cc.class_id)
LEFT JOIN db_lms_class_topics ct ON ct.class_id = cc.class_id AND ct.topic_id = t.id
LEFT JOIN db_lms_cycle_topics existing ON existing.cycle_id = cc.id AND existing.topic_id = t.id
WHERE cc.cycle_no = 1 AND existing.id IS NULL
  AND (ct.id IS NOT NULL OR NOT EXISTS (SELECT 1 FROM db_lms_class_topics x WHERE x.class_id = cc.class_id));

UPDATE db_lms_sessions s
JOIN db_lms_course_cycles cc ON cc.class_id = s.class_id AND cc.cycle_no = 1
SET s.cycle_id = cc.id
WHERE s.cycle_id IS NULL;

UPDATE db_lms_tests t
JOIN db_lms_course_cycles cc ON cc.class_id = t.class_id AND cc.cycle_no = 1
SET t.cycle_id = cc.id
WHERE t.cycle_id IS NULL;

-- Record already-finished sessions as consumed exactly once, then correct the
-- cycle balance from the immutable snapshot's configured total.
INSERT IGNORE INTO db_lms_cycle_session_completions (session_id, cycle_id)
SELECT s.id, s.cycle_id FROM db_lms_sessions s
WHERE s.cycle_id IS NOT NULL AND s.status = 'finished';

UPDATE db_lms_course_cycles cc
SET cc.remaining_sessions = GREATEST(0, cc.total_sessions - (
    SELECT COUNT(*) FROM db_lms_cycle_session_completions done WHERE done.cycle_id = cc.id
));

INSERT INTO db_lms_cycle_student_topics (cycle_id, student_id, topic_id, status, unlocked_at, unlock_reason)
SELECT cs.cycle_id, cs.student_id, ct.topic_id,
       CASE WHEN ct.order_index = first_topic.first_order THEN 'unlocked' ELSE 'locked' END,
       CASE WHEN ct.order_index = first_topic.first_order THEN cs.enrolled_at ELSE NULL END,
       CASE WHEN ct.order_index = first_topic.first_order THEN 'initial enrollment' ELSE NULL END
FROM db_lms_cycle_students cs
JOIN db_lms_cycle_topics ct ON ct.cycle_id = cs.cycle_id
JOIN (
    SELECT cycle_id, MIN(order_index) AS first_order
    FROM db_lms_cycle_topics GROUP BY cycle_id
) first_topic ON first_topic.cycle_id = ct.cycle_id
LEFT JOIN db_lms_cycle_student_topics existing
  ON existing.cycle_id = cs.cycle_id AND existing.student_id = cs.student_id AND existing.topic_id = ct.topic_id
WHERE existing.id IS NULL;
