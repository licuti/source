USE phuongnamv_db_new;

-- Drop old table
DROP TABLE IF EXISTS db_thanhvien;

-- Create new db_customers table
CREATE TABLE IF NOT EXISTS db_customers (
    id bigint unsigned NOT NULL AUTO_INCREMENT,
    code varchar(50) NULL,
    fullname varchar(255) NOT NULL,
    phone varchar(20) NULL,
    email varchar(100) NOT NULL,
    password varchar(255) NULL,
    avatar varchar(255) NULL,
    birthday date NULL,
    gender tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: Female, 1: Male, 2: Other',
    address varchar(255) NULL,
    province_id int NULL DEFAULT 0,
    district_id int NULL DEFAULT 0,
    ward_id int NULL DEFAULT 0,
    status tinyint(1) NOT NULL DEFAULT 1 COMMENT '1: Active, 0: Banned',
    google_id varchar(100) NULL,
    facebook_id varchar(100) NULL,
    created_at timestamp NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY email (email),
    UNIQUE KEY code (code),
    KEY phone (phone),
    KEY status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
