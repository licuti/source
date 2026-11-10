-- Forward-only migration for portal-scoped password reset OTPs.
CREATE TABLE IF NOT EXISTS `db_lms_password_reset_otps` (
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

CREATE TABLE IF NOT EXISTS `db_lms_password_reset_requests` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `identifier_hash` CHAR(64) NOT NULL,
    `ip_hash` CHAR(64) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_reset_request_rate` (`identifier_hash`, `ip_hash`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
