-- ---------------------------------------------------------------------------
-- Migration: 2026_10_07 — dọn toàn bộ cột Pancake POS / CRM không còn sử dụng
--
-- Dọn sạch schema sau khi gỡ bỏ toàn bộ module tích hợp Pancake POS/CRM.
-- ---------------------------------------------------------------------------

-- 1) Bảng db_thongtin: cấu hình API Pancake
ALTER TABLE `db_thongtin` DROP COLUMN `pancake_api_key`;
ALTER TABLE `db_thongtin` DROP COLUMN `pancake_shop_id`;
ALTER TABLE `db_thongtin` DROP COLUMN `pancake_api_url`;
ALTER TABLE `db_thongtin` DROP COLUMN `pancake_sync_to_crm`;

-- 2) Bảng db_sanpham & biến thể: ID liên kết sản phẩm trên Pancake
ALTER TABLE `db_sanpham` DROP COLUMN `pancake_product_id`;
ALTER TABLE `db_sanpham_bienthe` DROP COLUMN `pancake_variation_id`;
ALTER TABLE `db_sanpham_bienthe_backup` DROP COLUMN `pancake_variation_id`;

-- 3) Bảng db_category: ID liên kết danh mục trên Pancake
ALTER TABLE `db_category` DROP COLUMN `pancake_category_id`;

-- 4) Bảng db_dathang: ID liên kết đơn hàng trên Pancake
ALTER TABLE `db_dathang` DROP COLUMN `pancake_order_id`;

-- 5) Bảng db_thuoctinh & db_thuoctinh_giatri: ID thuộc tính trên Pancake
ALTER TABLE `db_thuoctinh` DROP COLUMN `pancake_attr_id`;
ALTER TABLE `db_thuoctinh_giatri` DROP COLUMN `pancake_attr_val_id`;

-- 6) Bảng địa chỉ hành chính: ID địa giới trên Pancake
ALTER TABLE `db_thanhpho` DROP COLUMN `pancake_id`;
ALTER TABLE `db_huyen` DROP COLUMN `pancake_id`;
ALTER TABLE `db_xa` DROP COLUMN `pancake_id`;
