<?php
$pdo = new PDO("mysql:host=localhost;dbname=phuongnamv_db_new;charset=utf8mb4", "root", "");

try {
    // 1. Drop old table
    $pdo->exec("DROP TABLE IF EXISTS `db_ship`");

    // 2. Create db_shipping_methods
    $pdo->exec("CREATE TABLE IF NOT EXISTS `db_shipping_methods` (
      `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `shop_id` bigint(20) UNSIGNED NOT NULL DEFAULT '0' COMMENT '0 = Sàn, > 0 = Gian hàng',
      `name` varchar(255) NOT NULL COMMENT 'Tên hiển thị (Giao chuẩn, Hỏa tốc, GHN, DHL)',
      `carrier_code` varchar(50) NOT NULL COMMENT 'Mã hãng (custom, ghn, ghtk, dhl)',
      `is_api` tinyint(1) NOT NULL DEFAULT '0' COMMENT '0: Phí thủ công theo bảng Rates, 1: Phí động gọi từ API của Hãng',
      `api_config` json DEFAULT NULL COMMENT 'Cấu hình Token, API Key của hãng',
      `is_active` tinyint(1) NOT NULL DEFAULT '1',
      `sort_order` int(11) NOT NULL DEFAULT '0',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 3. Create db_shipping_rates
    $pdo->exec("CREATE TABLE IF NOT EXISTS `db_shipping_rates` (
      `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      `shipping_method_id` bigint(20) UNSIGNED NOT NULL COMMENT 'Liên kết với bảng methods ở trên',
      `country_code` char(2) NOT NULL DEFAULT 'VN' COMMENT 'Mã quốc gia chuẩn ISO (VN, US, JP...). Dùng * cho Toàn cầu',
      `province_code` varchar(30) DEFAULT NULL COMMENT 'Mã tỉnh/bang',
      `district_code` varchar(30) DEFAULT NULL COMMENT 'Mã quận/huyện',
      `ward_code` varchar(30) DEFAULT NULL COMMENT 'Mã phường/xã',
      `base_fee` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Phí ship cơ bản',
      `extra_fee_per_kg` decimal(15,2) NOT NULL DEFAULT '0.00' COMMENT 'Phí phụ thu vượt mức kg',
      `free_weight_kg` decimal(8,2) NOT NULL DEFAULT '0.00' COMMENT 'Khối lượng miễn phí ban đầu',
      `estimated_time` varchar(100) DEFAULT NULL COMMENT 'Thời gian dự kiến',
      `priority` int(11) NOT NULL DEFAULT '0' COMMENT 'Độ ưu tiên hiển thị',
      `is_active` tinyint(1) NOT NULL DEFAULT '1',
      `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
      `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      KEY `idx_zone` (`country_code`, `province_code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // 4. Update db_module_admin for Menu 55
    $pdo->exec("UPDATE db_module_admin SET route_name = 'admin.shipping.index' WHERE id = 55");
    
    // Check if Menu 55 exists, if not, create it for fallback
    $stmt = $pdo->query("SELECT id FROM db_module_admin WHERE id = 55");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("INSERT INTO `db_module_admin` (`id`, `name`, `alias`, `icon`, `is_active`, `sort_order`, `route_name`, `group_id`) VALUES (55, 'Cấu hình vận chuyển', 'quan-ly-van-chuyen', 'fas fa-truck', 1, 55, 'admin.shipping.index', 43)");
    }

    echo "Shipping Schema Migration Successful!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
