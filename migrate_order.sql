USE phuongnamv_db_new;

-- Drop old tables
DROP TABLE IF EXISTS db_checkout_sessions;
DROP TABLE IF EXISTS db_order_items;
DROP TABLE IF EXISTS db_orders;
DROP TABLE IF EXISTS db_order_history;

-- Create new db_orders table
CREATE TABLE IF NOT EXISTS db_orders (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    order_code varchar(50) NOT NULL,
    
    -- Customer Info
    customer_id bigint unsigned NULL DEFAULT 0,
    customer_name varchar(255) NOT NULL,
    customer_email varchar(100) NULL,
    customer_phone varchar(20) NOT NULL,
    shipping_address varchar(255) NOT NULL,
    province_id int NULL DEFAULT 0,
    district_id int NULL DEFAULT 0,
    ward_id int NULL DEFAULT 0,
    
    -- Financials
    subtotal decimal(15,2) NOT NULL DEFAULT 0.00,
    shipping_fee decimal(15,2) NOT NULL DEFAULT 0.00,
    tax_amount decimal(15,2) NOT NULL DEFAULT 0.00,
    discount_amount decimal(15,2) NOT NULL DEFAULT 0.00,
    grand_total decimal(15,2) NOT NULL DEFAULT 0.00,
    
    -- Payment & Shipping methods
    payment_method_id int NULL DEFAULT 0,
    shipping_method_id int NULL DEFAULT 0,
    promo_code_id int NULL DEFAULT 0,
    
    -- Status
    payment_status tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: Unpaid, 1: Paid, 2: Refunded',
    order_status tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: Pending, 1: Processing, 2: Shipping, 3: Completed, 4: Cancelled',
    
    -- Notes
    customer_note text NULL,
    shop_note text NULL,
    
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY order_code (order_code),
    KEY customer_id (customer_id),
    KEY order_status (order_status),
    KEY payment_status (payment_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create new db_order_items table
CREATE TABLE IF NOT EXISTS db_order_items (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    order_id bigint unsigned NOT NULL,
    product_id int unsigned NOT NULL,
    variant_id int unsigned NULL DEFAULT 0,
    product_name varchar(255) NOT NULL,
    product_image varchar(255) NULL,
    attributes_info text NULL COMMENT 'JSON of size, color, etc.',
    quantity int NOT NULL DEFAULT 1,
    price decimal(15,2) NOT NULL DEFAULT 0.00,
    total decimal(15,2) NOT NULL DEFAULT 0.00,
    
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY order_id (order_id),
    KEY product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create new db_order_history table
CREATE TABLE IF NOT EXISTS db_order_history (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    order_id bigint unsigned NOT NULL,
    status_from tinyint(1) NULL,
    status_to tinyint(1) NOT NULL,
    note text NULL,
    created_by int unsigned NULL DEFAULT 0 COMMENT '0 for system/customer, User ID for Admin',
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY order_id (order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
