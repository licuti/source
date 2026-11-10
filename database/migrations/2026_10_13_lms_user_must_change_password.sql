-- ===================== P4+: buộc đổi mật khẩu lần đầu (BR-AUTH-01) =====================
--
-- N3#9: tài khoản được admin tạo (hoặc mật khẩu được đặt lại) phải đổi mật khẩu
-- ở lần đăng nhập đầu tiên. Cột này do createUser tự set khi password rỗng
-- (tự sinh) hoặc khi admin chọn "Yêu cầu đổi mật khẩu".
--
-- Forward-only. Áp dụng cho DB thật + DB test.
-- Ngày áp dụng: 2026-10-13.

ALTER TABLE `db_lms_users`
    ADD COLUMN `must_change_password` TINYINT(1) NOT NULL DEFAULT 0
        COMMENT 'BR-AUTH-01: buộc đổi mật khẩu ở lần đăng nhập đầu tiên'
        AFTER `password_changed_at`;
