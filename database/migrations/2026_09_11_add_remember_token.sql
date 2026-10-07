-- ---------------------------------------------------------------------------
-- Migration: 2026_09_11 — thêm cột `remember_token` cho bảng db_user
--
-- Mục đích: tách remember-me token khỏi cột `token` cũ (vốn dùng cho
-- hash md5 legacy: md5(md5(token) . md5(password))). Nếu dùng chung cột,
-- việc ghi đè khi bật "ghi nhớ đăng nhập" sẽ phá vỡ đăng nhập bằng mật khẩu
-- của những user còn dùng hash md5 cũ.
--
-- An toàn: login.php cũng tự ALTER cột này ở lần request đầu (idempotent),
-- nên file này chỉ để ghi nhận lịch sử migration và cho người chạy tay.
-- ---------------------------------------------------------------------------

ALTER TABLE `db_user`
    ADD COLUMN `remember_token` TEXT NULL;