-- 1. Create a new Parent for "Sản phẩm" (ID: 100)
INSERT INTO db_module_admin (id, parent, name, icon, alias, route_name, so_thu_tu, hien_thi, quyen_han)
VALUES (100, 0, 'Quản lý Sản phẩm', 'fa-box', '', NULL, 1, 1, 1)
ON DUPLICATE KEY UPDATE name='Quản lý Sản phẩm', icon='fa-box', so_thu_tu=1, hien_thi=1;

-- Move items to "Quản lý Sản phẩm" (100)
UPDATE db_module_admin SET parent = 100, so_thu_tu = 1 WHERE id = 30; -- Sản phẩm
UPDATE db_module_admin SET parent = 100, so_thu_tu = 2 WHERE id = 22; -- Loại danh mục
UPDATE db_module_admin SET parent = 100, so_thu_tu = 3 WHERE id = 49; -- Nhóm thuộc tính

-- 2. Modify "Bán hàng & Khách hàng" (Use ID: 43)
UPDATE db_module_admin SET name = 'Bán hàng & Khách', icon = 'fa-cart-shopping', so_thu_tu = 2, hien_thi = 1 WHERE id = 43;
-- Move Khách hàng items to 43
UPDATE db_module_admin SET parent = 43, so_thu_tu = 2 WHERE id = 32; -- Đơn hàng (keep in 43)
UPDATE db_module_admin SET parent = 43, so_thu_tu = 3 WHERE id = 33; -- Liên hệ
UPDATE db_module_admin SET parent = 43, so_thu_tu = 4 WHERE id = 48; -- Bình luận
UPDATE db_module_admin SET parent = 43, so_thu_tu = 5 WHERE id = 51; -- Đăng nhận ưu đãi
UPDATE db_module_admin SET parent = 43, so_thu_tu = 6 WHERE id = 46; -- Mã khuyến mãi
-- Hide the old Khách hàng parent (31)
UPDATE db_module_admin SET hien_thi = 0 WHERE id = 31;

-- 3. Modify "Nội dung & Bài viết" (Use ID: 7)
UPDATE db_module_admin SET name = 'Quản lý Bài viết', icon = 'fa-newspaper', so_thu_tu = 3, hien_thi = 1 WHERE id = 7;
UPDATE db_module_admin SET parent = 7, so_thu_tu = 1 WHERE id = 23; -- Bài viết
UPDATE db_module_admin SET parent = 7, so_thu_tu = 2 WHERE id = 24; -- Nội dung (move from 8)
UPDATE db_module_admin SET parent = 7, so_thu_tu = 3 WHERE id = 36; -- Album ảnh

-- 4. Modify "Giao diện & Cấu hình" (Use ID: 8)
UPDATE db_module_admin SET name = 'Cấu hình hệ thống', icon = 'fa-cogs', so_thu_tu = 4, hien_thi = 1 WHERE id = 8;
UPDATE db_module_admin SET parent = 8, so_thu_tu = 1 WHERE id = 25; -- Thông tin website
UPDATE db_module_admin SET parent = 8, so_thu_tu = 2 WHERE id = 54; -- Menu
UPDATE db_module_admin SET parent = 8, so_thu_tu = 3 WHERE id = 26; -- Text
UPDATE db_module_admin SET parent = 8, so_thu_tu = 4 WHERE id = 28; -- Cấu hình seo (move from 9)
UPDATE db_module_admin SET parent = 8, so_thu_tu = 5 WHERE id = 27; -- Quản lý user (move from 10)

-- Hide old parent 9 and 10
UPDATE db_module_admin SET hien_thi = 0 WHERE id IN (9, 10);
