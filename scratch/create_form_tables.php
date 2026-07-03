<?php
$pdo = new PDO('mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4', 'root', '');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

try {
    // 1. db_forms
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `db_forms` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `name` varchar(255) NOT NULL,
            `code` varchar(100) NOT NULL,
            `email_to` varchar(255) DEFAULT NULL,
            `success_message` text DEFAULT NULL,
            `is_active` tinyint(1) NOT NULL DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `code` (`code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // 2. db_form_fields
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `db_form_fields` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `form_id` int(11) NOT NULL,
            `type` varchar(50) NOT NULL,
            `name` varchar(100) NOT NULL,
            `label` varchar(255) NOT NULL,
            `placeholder` varchar(255) DEFAULT NULL,
            `options` text DEFAULT NULL,
            `is_required` tinyint(1) NOT NULL DEFAULT 0,
            `sort_order` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`),
            KEY `form_id` (`form_id`),
            CONSTRAINT `fk_form_fields` FOREIGN KEY (`form_id`) REFERENCES `db_forms` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");

    // 3. db_form_submissions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `db_form_submissions` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `form_id` int(11) NOT NULL,
            `data_payload` json NOT NULL,
            `status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: new, 1: read, 2: replied',
            `ip_address` varchar(50) DEFAULT NULL,
            `user_agent` text DEFAULT NULL,
            `reply_content` text DEFAULT NULL,
            `replied_by` int(11) DEFAULT NULL,
            `replied_at` timestamp NULL DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            KEY `form_id` (`form_id`),
            CONSTRAINT `fk_form_submissions` FOREIGN KEY (`form_id`) REFERENCES `db_forms` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    
    // Update db_module_admin mapping
    $pdo->exec("UPDATE `db_module_admin` SET `route_name` = 'admin.form.index' WHERE `id` = 33");

    echo "Success: Created form tables and updated module route.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
