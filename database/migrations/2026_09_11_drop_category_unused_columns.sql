-- ---------------------------------------------------------------------------
-- Migration: 2026_09_11 — dọn cột không còn dùng trên bảng db_category
--
-- Trước đây việc này được thực hiện bởi `category_drop_unused_columns()`
-- chạy trong request runtime mỗi lần mở module Danh mục (dù có cache session).
-- Đó là anti-pattern: thay đổi schema ngay trong request.
--
-- Từ đợt vá 7.3, hàm đó chỉ chạy khi RUNTIME_MIGRATIONS=true trong .env.
-- Cách khuyến nghị: chạy file này MỘT LẦN bằng tay/tool DB, rồi để
-- RUNTIME_MIGRATIONS=false.
--
-- Idempotent: chạy lại nhiều lần vẫn an toàn nếu bỏ qua lỗi "unknown column".
-- ---------------------------------------------------------------------------

-- 1) Dọn dữ liệu cột `icon` (giữ lại cột, chỉ xoá nội dung)
UPDATE `db_category` SET `icon` = '' WHERE `icon` IS NOT NULL AND `icon` != '';

-- 2) Dọn dữ liệu cột `home` / `menu` (đặt 0)
UPDATE `db_category` SET `home` = 0;
UPDATE `db_category` SET `menu` = 0;

-- 3) Bỏ cột. Nếu cột không tồn tại, MySQL sẽ báo lỗi — bỏ qua và tiếp tục.
ALTER TABLE `db_category` DROP COLUMN `icon`;
ALTER TABLE `db_category` DROP COLUMN `home`;
ALTER TABLE `db_category` DROP COLUMN `menu`;