-- ---------------------------------------------------------------------------
-- Migration: 2026_10_07_lms_auth — Nhóm Quản lý Tài khoản LMS + phân quyền
--
-- Theo ERD_MasterStudy.md §2.1 (adapt MySQL) + Huong-Dan-Phat-Trien-Page.md §3.1
-- + plan 1791257567462 (D3: 3 portal đối xứng dùng chung db_lms_users).
--
-- Quy ước:
--   - ENUM(...)  -> VARCHAR(30) + validate app-level (MySQL ENUM khó thay đổi — plan §3)
--   - TIMESTAMP  -> DATETIME DEFAULT CURRENT_TIMESTAMP / ON UPDATE CURRENT_TIMESTAMP
--   - PK/FK      -> BIGINT UNSIGNED (đồng nhất, tránh mismatch kiểu FK)
--   - engine InnoDB, charset utf8mb4 COLLATE utf8mb4_unicode_ci
--   - KHÔNG đụng bảng db_* e-commerce (D2 + R9)
--
-- Forward-only. Chạy thử trên phuongnamv_db_lms_test trước.
-- ---------------------------------------------------------------------------

-- 1. db_lms_users -----------------------------------------------------------
-- Tài khoản đăng nhập chung cho 3 portal (admin/teacher/student).
-- Pattern auth copy từ admin/ đã hardened: bcrypt + rate-limit + remember-me
-- (random_bytes + sha256 + rotate).
CREATE TABLE `db_lms_users` (
    `id`                    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `username`              VARCHAR(50)  NOT NULL,
    `password`              VARCHAR(255) NOT NULL                COMMENT 'bcrypt hash (cost >= 10)',
    `email`                 VARCHAR(100)     NULL,
    `phone`                 VARCHAR(20)      NULL,
    `role`                  VARCHAR(30)  NOT NULL                COMMENT 'admin | teacher | student (validate app)',
    `status`                VARCHAR(30)  NOT NULL DEFAULT 'active' COMMENT 'active | locked | pending',
    `last_login_at`         DATETIME        NULL,
    `last_login_ip`         VARCHAR(45)     NULL,
    `remember_token`        VARCHAR(64)     NULL                 COMMENT 'sha256 hex, rotate mỗi lần dùng',
    `remember_token_expires_at` DATETIME    NULL,
    `failed_login_count`    INT UNSIGNED NOT NULL DEFAULT 0,
    `locked_until`          DATETIME        NULL                 COMMENT 'rate-limit: khoá tạm khi sai liên tiếp',
    `password_changed_at`   DATETIME        NULL,
    `must_change_password`  TINYINT(1)  NOT NULL DEFAULT 0
                            COMMENT 'BR-AUTH-01: buộc đổi mật khẩu ở lần đăng nhập đầu tiên',
    `created_at`            DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_username` (`username`),
    UNIQUE KEY `uniq_email` (`email`),
    KEY `idx_role_status` (`role`, `status`),
    KEY `idx_remember_token` (`remember_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. db_lms_admins ----------------------------------------------------------
-- Hồ sơ Admin LMS (portal lms/admin.php). Riêng biệt với db_user e-commerce.
CREATE TABLE `db_lms_admins` (
    `user_id`       BIGINT UNSIGNED NOT NULL,
    `full_name`     VARCHAR(100)    NULL,
    `role_level`    VARCHAR(30)     NULL DEFAULT 'staff'  COMMENT 'super_admin | staff | viewer',
    `permissions`   JSON            NULL                   COMMENT 'mảng quyền cụ thể (tuỳ chọn)',
    `created_at`    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`    DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    CONSTRAINT `fk_admins_user` FOREIGN KEY (`user_id`)
        REFERENCES `db_lms_users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. db_lms_students --------------------------------------------------------
-- Hồ sơ học viên (ERD §2.1).
CREATE TABLE `db_lms_students` (
    `user_id`           BIGINT UNSIGNED NOT NULL,
    `student_code`      VARCHAR(20)  NOT NULL                COMMENT 'VD: Master227',
    `full_name`         VARCHAR(100) NOT NULL,
    `gender`            VARCHAR(30)     NULL                 COMMENT 'male | female | other',
    `parent_name`       VARCHAR(100)     NULL,
    `parent_phone`      VARCHAR(20)      NULL,
    `grade`             VARCHAR(20)      NULL                 COMMENT 'VD: "Lớp 5"',
    `school_name`       VARCHAR(100)     NULL,
    `district`          VARCHAR(100)     NULL,
    `ward`              VARCHAR(100)     NULL,
    `current_address`   VARCHAR(255)     NULL,
    `learning_type`     VARCHAR(30)  NOT NULL DEFAULT 'offline' COMMENT 'online | offline',
    `priority_number`   INT          NOT NULL DEFAULT 0,
    `status`            VARCHAR(30)  NOT NULL DEFAULT 'pending_arrange'
                        COMMENT 'pending_arrange | waiting_fee | studying | paused | quit',
    `free_time_slots`   JSON            NULL                 COMMENT 'khung giờ rảnh theo ngày trong tuần',
    `note`              TEXT            NULL,
    `created_at`        DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`        DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uniq_student_code` (`student_code`),
    KEY `idx_student_status` (`status`),
    KEY `idx_student_district` (`district`, `ward`),
    CONSTRAINT `fk_students_user` FOREIGN KEY (`user_id`)
        REFERENCES `db_lms_users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. db_lms_teachers --------------------------------------------------------
-- Hồ sơ giáo viên (ERD §2.1). Thêm cột `status` (ERD ghi index status nhưng
-- thiếu cột — bổ sung cho đủ).
CREATE TABLE `db_lms_teachers` (
    `user_id`               BIGINT UNSIGNED NOT NULL,
    `teacher_code`          VARCHAR(20)  NOT NULL             COMMENT 'VD: TE125',
    `full_name`             VARCHAR(100) NOT NULL,
    `gender`                VARCHAR(30)      NULL             COMMENT 'male | female | other',
    `dob`                   DATE             NULL,
    `cccd`                  VARCHAR(20)      NULL              COMMENT '12 số, validate app',
    `issue_date`            DATE             NULL,
    `issue_place`           VARCHAR(100)     NULL,
    `tax_code`              VARCHAR(20)      NULL              COMMENT 'MST 10 số, validate app',
    `teachable_districts`   JSON             NULL              COMMENT 'mảng quận có thể dạy',
    `teachable_subjects`    JSON             NULL              COMMENT 'mảng subject_id',
    `can_teach_online`      TINYINT(1)   NOT NULL DEFAULT 1,
    `rank`                  VARCHAR(30)  NOT NULL DEFAULT 'bronze'
                            COMMENT 'bronze | gold | green (green = ưu tiên cao nhất)',
    `max_classes`           INT          NOT NULL DEFAULT 1,
    `status`                VARCHAR(30)  NOT NULL DEFAULT 'active'
                            COMMENT 'active | paused | quit',
    `service_fee_percentage` DECIMAL(5,2) NOT NULL DEFAULT 48.00
                            COMMENT '% phí dịch vụ mặc định (ADM-02)',
    `is_commit_08`          TINYINT(1)   NOT NULL DEFAULT 0
                            COMMENT 'Cam kết 08: thuế TNCN 0%',
    `bank_name`             VARCHAR(100)     NULL,
    `bank_account`          VARCHAR(50)      NULL,
    `bank_owner`            VARCHAR(100)     NULL,
    `created_at`            DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at`            DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`user_id`),
    UNIQUE KEY `uniq_teacher_code` (`teacher_code`),
    UNIQUE KEY `uniq_cccd` (`cccd`),
    KEY `idx_teacher_rank` (`rank`),
    KEY `idx_teacher_status` (`status`),
    CONSTRAINT `fk_teachers_user` FOREIGN KEY (`user_id`)
        REFERENCES `db_lms_users` (`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. db_lms_modules ---------------------------------------------------------
-- Đăng ký module LMS (menu + cổng vào router), tách theo portal.
-- Mirror cơ chế db_module_admin của e-commerce (Huong-Dan §1.2, §3.1).
CREATE TABLE `db_lms_modules` (
    `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `parent`    INT UNSIGNED NOT NULL DEFAULT 0,
    `name`      VARCHAR(255) NOT NULL,
    `alias`     VARCHAR(100) NOT NULL               COMMENT '[a-z0-9-]+, khớp sources/<alias>.php',
    `portal`    VARCHAR(30)  NOT NULL               COMMENT 'admin | teacher | student',
    `icon`      VARCHAR(100) NOT NULL DEFAULT '',
    `so_thu_tu` INT          NOT NULL DEFAULT 1,
    `hien_thi`  TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_alias_portal` (`alias`, `portal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. db_lms_role_permissions ------------------------------------------------
-- Phân quyền theo role + module (RBAC matrix SRS §4). Khác e-commerce
-- (gán theo user) vì 3 portal đối xứng và role đã có trong db_lms_users.
CREATE TABLE `db_lms_role_permissions` (
    `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_module`  INT UNSIGNED NOT NULL,
    `role`       VARCHAR(30)  NOT NULL              COMMENT 'admin | teacher | student',
    `can_view`   TINYINT(1)   NOT NULL DEFAULT 0,
    `can_edit`   TINYINT(1)   NOT NULL DEFAULT 0,
    `can_delete` TINYINT(1)   NOT NULL DEFAULT 0,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uniq_module_role` (`id_module`, `role`),
    KEY `idx_role` (`role`),
    CONSTRAINT `fk_perm_module` FOREIGN KEY (`id_module`)
        REFERENCES `db_lms_modules` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
