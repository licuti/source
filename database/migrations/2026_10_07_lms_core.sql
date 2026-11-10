-- ---------------------------------------------------------------------------
-- Migration: 2026_10_07_lms_core — Schema nghiệp vụ LMS
--
-- Theo ERD_MasterStudy.md §2.2–§2.8 (adapt MySQL). Chạy SAU 2026_10_07_lms_auth.
-- Quy ước giống file auth (VARCHAR(30) thay ENUM, DATETIME thay TIMESTAMP,
-- BIGINT UNSIGNED PK/FK, InnoDB + utf8mb4). Forward-only.
-- ---------------------------------------------------------------------------

-- ===================== 2.2. Nhóm Lộ trình học (Curriculum) =====================

CREATE TABLE `db_lms_programs` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `name`        VARCHAR(100) NOT NULL,
    `description` TEXT         NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_subjects` (
    `id`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `program_id` BIGINT UNSIGNED NOT NULL,
    `name`       VARCHAR(100) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_subject_program` (`program_id`),
    CONSTRAINT `fk_subject_program` FOREIGN KEY (`program_id`)
        REFERENCES `db_lms_programs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_topics` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `subject_id`      BIGINT UNSIGNED NOT NULL,
    `name`            VARCHAR(150) NOT NULL,
    `has_test`        TINYINT(1) NOT NULL DEFAULT 0,
    `has_minitest`    TINYINT(1) NOT NULL DEFAULT 0,
    `max_sessions`    INT          NULL,
    `order_index`     INT     NOT NULL DEFAULT 0,
    `report_template` TEXT         NULL                  COMMENT 'HTML template nhận xét tháng',
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_topic_subject_order` (`subject_id`, `order_index`),
    CONSTRAINT `fk_topic_subject` FOREIGN KEY (`subject_id`)
        REFERENCES `db_lms_subjects` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== 2.3. Nhóm Lớp học & Thời khoá biểu =====================

CREATE TABLE `db_lms_classes` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_code`         VARCHAR(30)  NOT NULL,
    `program_id`         BIGINT UNSIGNED NOT NULL,
    `subject_id`         BIGINT UNSIGNED NOT NULL,
    `teacher_id`         BIGINT UNSIGNED NOT NULL,
    `class_type`         VARCHAR(30)  NOT NULL             COMMENT 'one_on_one | group',
    `name`               VARCHAR(100)     NULL              COMMENT 'VD: "Toán 1-1 - Duy Kiên 1117"',
    `price_per_session`  DECIMAL(15,2) NOT NULL DEFAULT 0,
    `teacher_percentage` DECIMAL(5,2)     NULL              COMMENT 'ghi đè teachers.service_fee_percentage',
    `total_sessions`     INT          NOT NULL DEFAULT 0,
    `remaining_sessions` INT          NOT NULL DEFAULT 0,
    `hw_deadline_time`   TIME             NULL              COMMENT 'VD: 12:00 — hạn giao BTVN',
    `hw_check_hours`     INT          NOT NULL DEFAULT 24  COMMENT 'hạn chấm BTVN (giờ)',
    `test_grace_hours`   INT          NOT NULL DEFAULT 72  COMMENT 'hạn chấm test/minitest (giờ)',
    `status`             VARCHAR(30)  NOT NULL DEFAULT 'active'
                         COMMENT 'arranging | active | paused | finished | cancelled',
    `start_date`         DATE             NULL,
    `expected_end_date`  DATE             NULL,
    `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`         DATETIME     NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_class_code` (`class_code`),
    KEY `idx_class_teacher_status` (`teacher_id`, `status`),
    KEY `idx_class_subject` (`subject_id`),
    KEY `idx_class_type_status` (`class_type`, `status`),
    KEY `idx_class_deleted` (`deleted_at`),
    CONSTRAINT `fk_class_program`  FOREIGN KEY (`program_id`)
        REFERENCES `db_lms_programs` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_class_subject`  FOREIGN KEY (`subject_id`)
        REFERENCES `db_lms_subjects` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_class_teacher`  FOREIGN KEY (`teacher_id`)
        REFERENCES `db_lms_teachers` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_class_students` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id`    BIGINT UNSIGNED NOT NULL,
    `student_id`  BIGINT UNSIGNED NOT NULL,
    `enrolled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `status`      VARCHAR(30) NOT NULL DEFAULT 'active'
                  COMMENT 'active | transferred_out | completed',
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_class_student` (`class_id`, `student_id`),
    KEY `idx_student_class_status` (`student_id`, `status`),
    CONSTRAINT `fk_cs_class`   FOREIGN KEY (`class_id`)
        REFERENCES `db_lms_classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_cs_student` FOREIGN KEY (`student_id`)
        REFERENCES `db_lms_students` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_schedules` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id`       BIGINT UNSIGNED NOT NULL,
    `day_of_week`    TINYINT     NOT NULL                  COMMENT '2=Thứ2 .. 8=Chủ nhật',
    `start_time`     TIME        NOT NULL,
    `end_time`       TIME        NOT NULL,
    `effective_from` DATE        NOT NULL,
    `effective_to`   DATE            NULL                  COMMENT 'NULL = vô thời hạn',
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_schedule_class_dow` (`class_id`, `day_of_week`),
    KEY `idx_schedule_dow_time` (`day_of_week`, `start_time`),
    CONSTRAINT `fk_schedule_class` FOREIGN KEY (`class_id`)
        REFERENCES `db_lms_classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Lớp - chuyên đề (nhiều-nhiều; form ADM-03 multi-select chuyên đề)
CREATE TABLE `db_lms_class_topics` (
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

-- ===================== 2.4. Nhóm Buổi học, BTVN & Test =====================

CREATE TABLE `db_lms_sessions` (
    `id`                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id`           BIGINT UNSIGNED NOT NULL,
    `cycle_id`           BIGINT UNSIGNED     NULL,
    `teacher_id`         BIGINT UNSIGNED NOT NULL,
    `topic_id`           BIGINT UNSIGNED     NULL,
    `session_date`       DATE        NOT NULL,
    `start_time`         TIME            NULL,
    `end_time`           TIME            NULL,
    `lesson_summary`     TEXT            NULL,
    `hw_content`         TEXT            NULL,
    `hw_deadline`        DATETIME        NULL
                         COMMENT 'tự tính = session_date + 1 ngày + classes.hw_deadline_time',
    `hw_attached_files`  JSON            NULL,
    `status`             VARCHAR(30) NOT NULL DEFAULT 'upcoming'
                         COMMENT 'upcoming | finished | cancelled | absent_teacher | absent_student',
    `is_summary_late`    TINYINT(1)  NOT NULL DEFAULT 0,
    `summary_submitted_at` DATETIME    NULL,
    `created_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`         DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_session_fixed_slot` (`class_id`, `session_date`, `start_time`),
    KEY `idx_session_class_date` (`class_id`, `session_date`),
    KEY `idx_session_cycle_date` (`cycle_id`, `session_date`),
    KEY `idx_session_teacher_date` (`teacher_id`, `session_date`),
    KEY `idx_session_status_date` (`status`, `session_date`),
    CONSTRAINT `fk_session_class`   FOREIGN KEY (`class_id`)
        REFERENCES `db_lms_classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_session_teacher` FOREIGN KEY (`teacher_id`)
        REFERENCES `db_lms_teachers` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_session_topic`   FOREIGN KEY (`topic_id`)
        REFERENCES `db_lms_topics` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_session_students` (
    `id`                   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `session_id`           BIGINT UNSIGNED NOT NULL,
    `student_id`           BIGINT UNSIGNED NOT NULL,
    `hw_submission_files`  JSON            NULL    COMMENT 'tối đa 4 file',
    `hw_submitted_at`      DATETIME        NULL,
    `hw_submission_status` VARCHAR(30) NOT NULL DEFAULT 'not_submitted'
                           COMMENT 'not_submitted | submitted_on_time | submitted_late',
    `teacher_feedback_text` TEXT           NULL,
    `teacher_feedback_files` JSON          NULL,
    `hw_graded_at`         DATETIME        NULL,
    `is_graded_late`       TINYINT(1)  NOT NULL DEFAULT 0,
    `reminder_sent_at`     DATETIME        NULL,
    `grade`                DECIMAL(5,2)    NULL,
    `status`               VARCHAR(30) NOT NULL DEFAULT 'not_submitted'
                           COMMENT 'not_submitted | submitted | graded | overdue',
    `created_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_session_student` (`session_id`, `student_id`),
    KEY `idx_ss_student_status` (`student_id`, `status`),
    KEY `idx_ss_reminder_pending` (`reminder_sent_at`, `session_id`),
    CONSTRAINT `fk_ss_session` FOREIGN KEY (`session_id`)
        REFERENCES `db_lms_sessions` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_ss_student` FOREIGN KEY (`student_id`)
        REFERENCES `db_lms_students` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_tests` (
    `id`          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id`    BIGINT UNSIGNED NOT NULL,
    `cycle_id`    BIGINT UNSIGNED     NULL,
    `topic_id`    BIGINT UNSIGNED NOT NULL,
    `test_type`   VARCHAR(30) NOT NULL              COMMENT 'test | minitest',
    `title`       VARCHAR(150)    NULL,
    `test_file_url` VARCHAR(500)  NULL,
    `deadline`    DATETIME    NOT NULL,
    `max_score`   DECIMAL(5,2) NOT NULL DEFAULT 20.00,
    `status`      VARCHAR(30) NOT NULL DEFAULT 'draft'  COMMENT 'draft | published | closed',
    `created_by`  BIGINT UNSIGNED     NULL,
    `created_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_test_class_type` (`class_id`, `test_type`),
    KEY `idx_test_cycle_type` (`cycle_id`, `test_type`),
    KEY `idx_test_status_deadline` (`status`, `deadline`),
    CONSTRAINT `fk_test_class`   FOREIGN KEY (`class_id`)
        REFERENCES `db_lms_classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_test_topic`   FOREIGN KEY (`topic_id`)
        REFERENCES `db_lms_topics` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_test_creator` FOREIGN KEY (`created_by`)
        REFERENCES `db_lms_teachers` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_test_submissions` (
    `id`                  BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `test_id`             BIGINT UNSIGNED NOT NULL,
    `student_id`          BIGINT UNSIGNED NOT NULL,
    `submission_files`    JSON            NULL,
    `submitted_at`        DATETIME        NULL,
    `submission_status`   VARCHAR(30) NOT NULL DEFAULT 'not_submitted'
                          COMMENT 'not_submitted | submitted_on_time | submitted_late',
    `score_x`             DECIMAL(5,2)    NULL    COMMENT 'điểm X/20',
    `score_y_percent`     DECIMAL(5,2)    NULL    COMMENT 'phần trăm Y%',
    `graded_at`           DATETIME        NULL,
    `is_graded_late`      TINYINT(1)  NOT NULL DEFAULT 0,
    `teacher_feedback_files` JSON         NULL,
    `created_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_test_student` (`test_id`, `student_id`),
    KEY `idx_tsub_student_status` (`student_id`, `submission_status`),
    CONSTRAINT `fk_tsub_test`    FOREIGN KEY (`test_id`)
        REFERENCES `db_lms_tests` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_tsub_student` FOREIGN KEY (`student_id`)
        REFERENCES `db_lms_students` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Course-cycle tables are created by 2026_10_15_lms_course_cycles.sql for
-- existing installations. They are included here for fresh LMS databases.
CREATE TABLE `db_lms_course_cycles` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id` BIGINT UNSIGNED NOT NULL,
    `cycle_no` INT NOT NULL,
    `name` VARCHAR(100) NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'active',
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
    PRIMARY KEY (`id`), UNIQUE KEY `uniq_cycle_class_no` (`class_id`, `cycle_no`),
    KEY `idx_cycle_class_status` (`class_id`, `status`),
    CONSTRAINT `fk_cycle_class` FOREIGN KEY (`class_id`) REFERENCES `db_lms_classes` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cycle_teacher` FOREIGN KEY (`teacher_id`) REFERENCES `db_lms_teachers` (`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_cycle_students` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `cycle_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL, `status` VARCHAR(30) NOT NULL DEFAULT 'active',
    `enrolled_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, `left_at` DATETIME NULL,
    `snapshot_data` JSON NULL, PRIMARY KEY (`id`), UNIQUE KEY `uniq_cycle_student` (`cycle_id`, `student_id`),
    KEY `idx_cycle_student_status` (`student_id`, `status`),
    CONSTRAINT `fk_cycle_student_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `db_lms_course_cycles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cycle_student_user` FOREIGN KEY (`student_id`) REFERENCES `db_lms_students` (`user_id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_cycle_schedules` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `cycle_id` BIGINT UNSIGNED NOT NULL,
    `day_of_week` TINYINT NOT NULL, `start_time` TIME NOT NULL, `end_time` TIME NOT NULL,
    `effective_from` DATE NOT NULL, `effective_to` DATE NULL, PRIMARY KEY (`id`),
    KEY `idx_cycle_schedule` (`cycle_id`, `day_of_week`, `start_time`),
    CONSTRAINT `fk_cycle_schedule_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `db_lms_course_cycles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_cycle_topics` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `cycle_id` BIGINT UNSIGNED NOT NULL,
    `topic_id` BIGINT UNSIGNED NOT NULL, `order_index` INT NOT NULL DEFAULT 0,
    `max_sessions` INT NULL, `has_test` TINYINT(1) NOT NULL DEFAULT 0, `has_minitest` TINYINT(1) NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`), UNIQUE KEY `uniq_cycle_topic` (`cycle_id`, `topic_id`), KEY `idx_cycle_topic_order` (`cycle_id`, `order_index`),
    CONSTRAINT `fk_cycle_topic_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `db_lms_course_cycles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cycle_topic_topic` FOREIGN KEY (`topic_id`) REFERENCES `db_lms_topics` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_cycle_student_topics` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT, `cycle_id` BIGINT UNSIGNED NOT NULL,
    `student_id` BIGINT UNSIGNED NOT NULL, `topic_id` BIGINT UNSIGNED NOT NULL,
    `status` VARCHAR(30) NOT NULL DEFAULT 'locked', `unlocked_at` DATETIME NULL,
    `unlocked_by` BIGINT UNSIGNED NULL, `unlock_reason` VARCHAR(255) NULL,
    PRIMARY KEY (`id`), UNIQUE KEY `uniq_cycle_student_topic` (`cycle_id`, `student_id`, `topic_id`),
    KEY `idx_student_topic_status` (`student_id`, `status`),
    CONSTRAINT `fk_cycle_st_topic_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `db_lms_course_cycles` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cycle_st_topic_student` FOREIGN KEY (`student_id`) REFERENCES `db_lms_students` (`user_id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_cycle_st_topic_topic` FOREIGN KEY (`topic_id`) REFERENCES `db_lms_topics` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_cycle_st_topic_unlocker` FOREIGN KEY (`unlocked_by`) REFERENCES `db_lms_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_cycle_session_completions` (
    `session_id` BIGINT UNSIGNED NOT NULL,
    `cycle_id` BIGINT UNSIGNED NOT NULL,
    `completed_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`session_id`), KEY `idx_cycle_completion` (`cycle_id`, `completed_at`),
    CONSTRAINT `fk_cycle_completion_session` FOREIGN KEY (`session_id`) REFERENCES `db_lms_sessions` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cycle_completion_cycle` FOREIGN KEY (`cycle_id`) REFERENCES `db_lms_course_cycles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== 2.5. Nhóm Requests =====================

CREATE TABLE `db_lms_requests` (
    `id`           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `request_type` VARCHAR(30) NOT NULL
                   COMMENT 'add_session | send_test | change_schedule | request_leave | request_bonus | other',
    `class_id`     BIGINT UNSIGNED     NULL,
    `session_id`   BIGINT UNSIGNED     NULL,
    `teacher_id`   BIGINT UNSIGNED     NULL,
    `student_id`   BIGINT UNSIGNED     NULL,
    `title`        VARCHAR(200)        NULL,
    `description`  TEXT                NULL,
    `payload`      JSON                NULL    COMMENT 'dữ liệu tuỳ loại: lý do nghỉ, lịch mới...',
    `status`       VARCHAR(30) NOT NULL DEFAULT 'pending'
                   COMMENT 'pending | approved | rejected | cancelled',
    `processed_by` BIGINT UNSIGNED     NULL,
    `processed_at` DATETIME            NULL,
    `admin_note`   TEXT                NULL,
    `created_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_request_type_status` (`request_type`, `status`),
    KEY `idx_request_teacher_status` (`teacher_id`, `status`),
    KEY `idx_request_class` (`class_id`),
    CONSTRAINT `fk_request_class`   FOREIGN KEY (`class_id`)
        REFERENCES `db_lms_classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_request_session` FOREIGN KEY (`session_id`)
        REFERENCES `db_lms_sessions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_request_teacher` FOREIGN KEY (`teacher_id`)
        REFERENCES `db_lms_teachers` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_request_student` FOREIGN KEY (`student_id`)
        REFERENCES `db_lms_students` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_request_processor` FOREIGN KEY (`processed_by`)
        REFERENCES `db_lms_admins` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== 2.6. Nhóm Tài chính & Vi phạm =====================

CREATE TABLE `db_lms_violation_thresholds` (
    `id`                       BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `min_classes`              INT NOT NULL,
    `max_classes`              INT     NULL    COMMENT 'NULL = không giới hạn trên',
    `ticks_to_warn`            INT NOT NULL,
    `ticks_to_demote_fee`      INT NOT NULL,
    `fee_percentage_after_demote` DECIMAL(5,2) NOT NULL,
    `ticks_exempted`           INT NOT NULL DEFAULT 0,
    `effective_from`           DATE NOT NULL,
    `effective_to`             DATE     NULL,
    `created_at`               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`               DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_threshold_range` (`min_classes`, `max_classes`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_violations` (
    `id`             BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `teacher_id`     BIGINT UNSIGNED NOT NULL,
    `class_id`       BIGINT UNSIGNED     NULL,
    `session_id`     BIGINT UNSIGNED     NULL,
    `tick_amount`    TINYINT     NOT NULL DEFAULT 1   COMMENT '+1 phạt | -1 miễn giảm thủ công',
    `reason`         VARCHAR(30) NOT NULL
                     COMMENT 'late_lesson_summary | late_hw_grading | late_test_grading | manual_penalty | manual_exempt',
    `note`           TEXT                NULL,
    `auto_generated` TINYINT(1)  NOT NULL DEFAULT 1   COMMENT '1=cron, 0=admin tạo tay',
    `violation_date` DATE        NOT NULL,
    `created_by`     BIGINT UNSIGNED     NULL,
    `created_at`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    -- Idempotency (R1): cron chạy lại không tạo tick trùng.
    -- MySQL 5.7 cho phép nhiều hàng có session_id NULL trong UNIQUE nên tick
    -- thủ công (không session) không bị chặn.
    UNIQUE KEY `uniq_tick` (`teacher_id`, `session_id`, `reason`, `violation_date`),
    KEY `idx_violation_teacher_date` (`teacher_id`, `violation_date`),
    KEY `idx_violation_reason_date` (`reason`, `violation_date`),
    CONSTRAINT `fk_violation_teacher` FOREIGN KEY (`teacher_id`)
        REFERENCES `db_lms_teachers` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_violation_class`  FOREIGN KEY (`class_id`)
        REFERENCES `db_lms_classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_violation_session` FOREIGN KEY (`session_id`)
        REFERENCES `db_lms_sessions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_violation_creator` FOREIGN KEY (`created_by`)
        REFERENCES `db_lms_admins` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_transactions` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `teacher_id`      BIGINT UNSIGNED NOT NULL,
    `session_id`      BIGINT UNSIGNED     NULL,
    `amount`          DECIMAL(15,2) NOT NULL  COMMENT 'âm hoặc dương',
    `type`            VARCHAR(30) NOT NULL
                      COMMENT 'salary_session | penalty | bonus | tax | deposit_deduct | adjustment',
    `reason`          VARCHAR(255)        NULL,
    `reference_id`    BIGINT UNSIGNED     NULL  COMMENT 'FK logic tới violations/requests',
    `transaction_date` DATE    NOT NULL,
    `period_month`    CHAR(7)     NOT NULL  COMMENT 'VD: 2026-07 — group tính lương',
    `created_by`      BIGINT UNSIGNED     NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_txn_teacher_month` (`teacher_id`, `period_month`),
    KEY `idx_txn_type_date` (`type`, `transaction_date`),
    KEY `idx_txn_session` (`session_id`),
    CONSTRAINT `fk_txn_teacher` FOREIGN KEY (`teacher_id`)
        REFERENCES `db_lms_teachers` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_txn_session` FOREIGN KEY (`session_id`)
        REFERENCES `db_lms_sessions` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_txn_creator` FOREIGN KEY (`created_by`)
        REFERENCES `db_lms_admins` (`user_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== 2.7. Nhóm Nhận xét & Thư viện =====================

CREATE TABLE `db_lms_monthly_reviews` (
    `id`              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `student_id`      BIGINT UNSIGNED NOT NULL,
    `class_id`        BIGINT UNSIGNED NOT NULL,
    `teacher_id`      BIGINT UNSIGNED NOT NULL,
    `review_month`    CHAR(7)     NOT NULL,
    `overall_rating`  VARCHAR(30)     NULL
                      COMMENT 'excellent | good | average | need_improvement',
    `content`         TEXT        NOT NULL,
    `progress_percent` DECIMAL(5,2)    NULL,
    `published_at`    DATETIME        NULL,
    `created_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_review_month` (`student_id`, `class_id`, `review_month`),
    KEY `idx_review_month` (`review_month`),
    CONSTRAINT `fk_review_student` FOREIGN KEY (`student_id`)
        REFERENCES `db_lms_students` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_review_class`   FOREIGN KEY (`class_id`)
        REFERENCES `db_lms_classes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_review_teacher` FOREIGN KEY (`teacher_id`)
        REFERENCES `db_lms_teachers` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_library_files` (
    `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `class_id`         BIGINT UNSIGNED     NULL  COMMENT 'NULL = tài liệu chung',
    `uploaded_by`      BIGINT UNSIGNED NOT NULL,
    `title`            VARCHAR(200)        NULL,
    `file_type`        VARCHAR(30) NOT NULL
                       COMMENT 'slide | exam | video | audio | document | other',
    `file_url`         VARCHAR(500) NOT NULL  COMMENT 'local qua CKFinder (thay S3)',
    `file_size_bytes`  BIGINT          NULL,
    `subject_id`       BIGINT UNSIGNED     NULL,
    `topic_id`         BIGINT UNSIGNED     NULL,
    `description`      TEXT                NULL,
    `download_count`   INT          NOT NULL DEFAULT 0,
    `created_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `deleted_at`       DATETIME        NULL,
    PRIMARY KEY (`id`),
    KEY `idx_library_class_type` (`class_id`, `file_type`),
    KEY `idx_library_subject` (`subject_id`),
    KEY `idx_library_deleted` (`deleted_at`),
    CONSTRAINT `fk_library_class`   FOREIGN KEY (`class_id`)
        REFERENCES `db_lms_classes` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_library_uploader` FOREIGN KEY (`uploaded_by`)
        REFERENCES `db_lms_teachers` (`user_id`) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT `fk_library_subject` FOREIGN KEY (`subject_id`)
        REFERENCES `db_lms_subjects` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_library_topic`   FOREIGN KEY (`topic_id`)
        REFERENCES `db_lms_topics` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== 2.8. Nhóm Hỗ trợ & Kiểm toán =====================

CREATE TABLE `db_lms_notifications` (
    `id`        BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id`   BIGINT UNSIGNED NOT NULL,
    `type`      VARCHAR(30) NOT NULL
                COMMENT 'tick_warning | request_approved | request_rejected | session_reminder | payment_received | system',
    `title`     VARCHAR(200)        NULL,
    `message`   TEXT                NULL,
    `link_url`  VARCHAR(500)        NULL,
    `is_read`   TINYINT(1)  NOT NULL DEFAULT 0,
    `read_at`   DATETIME            NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_notif_user_read` (`user_id`, `is_read`, `created_at`),
    CONSTRAINT `fk_notif_user` FOREIGN KEY (`user_id`)
        REFERENCES `db_lms_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Portal-scoped password reset OTPs (SYS-03). Kept here as well as in the
-- forward-only migration so fresh database installs have the reset schema.
CREATE TABLE `db_lms_password_reset_otps` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `user_id` BIGINT UNSIGNED NOT NULL,
    `portal` VARCHAR(30) NOT NULL,
    `identifier_hash` CHAR(64) NOT NULL,
    `otp_hash` VARCHAR(255) NOT NULL,
    `expires_at` DATETIME NOT NULL,
    `attempt_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
    `reset_token_hash` CHAR(64) NULL,
    `verified_at` DATETIME NULL,
    `used_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_reset_user_portal` (`user_id`, `portal`, `created_at`),
    KEY `idx_reset_active` (`portal`, `used_at`, `expires_at`),
    CONSTRAINT `fk_reset_otp_user` FOREIGN KEY (`user_id`)
        REFERENCES `db_lms_users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_password_reset_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifier_hash` CHAR(64) NOT NULL,
    `ip_hash` CHAR(64) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_reset_request_rate` (`identifier_hash`, `ip_hash`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `db_lms_audit_logs` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `actor_user_id` BIGINT UNSIGNED     NULL  COMMENT 'NULL nếu cronjob',
    `actor_type`    VARCHAR(30) NOT NULL DEFAULT 'user'
                    COMMENT 'user | admin | teacher | student | system_cron',
    `action`        VARCHAR(100) NOT NULL  COMMENT 'VD: update_teacher_percentage',
    `target_type`   VARCHAR(50)  NOT NULL  COMMENT 'VD: classes, violations',
    `target_id`     BIGINT       NOT NULL,
    `before_data`   JSON             NULL,
    `after_data`    JSON             NULL,
    `ip_address`    VARCHAR(45)      NULL,
    `user_agent`    VARCHAR(255)     NULL,
    `created_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_audit_actor_date` (`actor_user_id`, `created_at`),
    KEY `idx_audit_target` (`target_type`, `target_id`),
    KEY `idx_audit_action_date` (`action`, `created_at`),
    CONSTRAINT `fk_audit_actor` FOREIGN KEY (`actor_user_id`)
        REFERENCES `db_lms_users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ===================== Cron (D5) =====================
-- Lịch sử chạy cron để idempotency + cảnh báo khi cron chết (R5).

CREATE TABLE `db_lms_cron_runs` (
    `id`            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `job_name`      VARCHAR(50)  NOT NULL  COMMENT 'tick_scan | session_generate | payroll_close',
    `started_at`    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `finished_at`   DATETIME     NULL,
    `status`        VARCHAR(30)  NOT NULL DEFAULT 'running'
                    COMMENT 'running | success | failed',
    `rows_affected` INT          NOT NULL DEFAULT 0,
    `error_message` TEXT             NULL,
    PRIMARY KEY (`id`),
    KEY `idx_cron_job_started` (`job_name`, `started_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
