USE phuongnamv_db_new;

-- Drop old tables
DROP TABLE IF EXISTS db_khuyenmai;
DROP TABLE IF EXISTS db_khuyenmai_ls;

-- Create new db_promo_codes table
CREATE TABLE IF NOT EXISTS db_promo_codes (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    shop_id bigint unsigned NOT NULL DEFAULT 0,
    code varchar(50) NOT NULL,
    name varchar(255) NOT NULL,
    description text NULL,
    discount_type tinyint(1) NOT NULL DEFAULT 1 COMMENT '1: Percentage, 2: Fixed Amount, 3: Free Shipping',
    discount_value decimal(15,2) NOT NULL DEFAULT 0.00,
    max_discount_amount decimal(15,2) NOT NULL DEFAULT 0.00,
    min_order_amount decimal(15,2) NOT NULL DEFAULT 0.00,
    start_date datetime NOT NULL,
    end_date datetime NOT NULL,
    usage_limit int NOT NULL DEFAULT 0 COMMENT '0: Unlimited',
    usage_per_user int NOT NULL DEFAULT 1 COMMENT '0: Unlimited',
    apply_to tinyint(1) NOT NULL DEFAULT 1 COMMENT '1: All Orders, 2: Specific Products, 3: Specific Categories',
    is_active tinyint(1) NOT NULL DEFAULT 1,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY code (code),
    KEY shop_id (shop_id),
    KEY is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create new db_promo_code_usage table
CREATE TABLE IF NOT EXISTS db_promo_code_usage (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    promo_code_id bigint unsigned NOT NULL,
    user_id bigint unsigned NOT NULL,
    order_id bigint unsigned NOT NULL,
    discount_applied decimal(15,2) NOT NULL DEFAULT 0.00,
    used_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY promo_code_id (promo_code_id),
    KEY user_id (user_id),
    KEY order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
