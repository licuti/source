-- Migration: 2026_11_04_lms_teacher_account_module.sql
-- Trang "Tài khoản giáo viên" (teacher tự cập nhật hồ sơ) + đổi tên menu
-- "Vi phạm nội quy" → "Vi phạm chất lượng dịch vụ".

SET NAMES utf8mb4;

INSERT INTO `db_lms_modules` (`parent`, `name`, `alias`, `portal`, `icon`, `so_thu_tu`, `hien_thi`)
SELECT 0, 'Tài khoản giáo viên', 'lms-tai-khoan', 'teacher', 'fa-solid fa-user-tie', 2, 1
WHERE NOT EXISTS (
  SELECT 1 FROM `db_lms_modules`
  WHERE `alias` = 'lms-tai-khoan' AND `portal` = 'teacher'
);

-- Sắp xếp lại sidebar Teacher (idempotent).
UPDATE `db_lms_modules`
SET `so_thu_tu` = CASE `alias`
    WHEN 'lms-tong-quan' THEN 1
    WHEN 'lms-tai-khoan' THEN 2
    WHEN 'lms-hoc-vien-cua-toi' THEN 3
    WHEN 'lms-lich-day' THEN 4
    WHEN 'lms-phi-dv' THEN 5
    WHEN 'lms-vi-pham' THEN 6
    WHEN 'lms-thu-vien' THEN 7
    WHEN 'lms-nhan-xet-thang' THEN 8
    ELSE `so_thu_tu`
  END
WHERE `portal` = 'teacher'
  AND `alias` IN (
    'lms-tong-quan', 'lms-tai-khoan', 'lms-hoc-vien-cua-toi', 'lms-lich-day',
    'lms-phi-dv', 'lms-vi-pham', 'lms-thu-vien', 'lms-nhan-xet-thang'
  );

UPDATE `db_lms_modules`
SET `name` = 'Vi phạm chất lượng dịch vụ'
WHERE `portal` = 'teacher' AND `alias` = 'lms-vi-pham';

INSERT INTO `db_lms_role_permissions` (`id_module`, `role`, `can_view`, `can_edit`, `can_delete`)
SELECT m.id, 'teacher', 1, 1, 0
FROM `db_lms_modules` m
WHERE m.alias = 'lms-tai-khoan' AND m.portal = 'teacher'
  AND NOT EXISTS (
    SELECT 1 FROM `db_lms_role_permissions` p
    WHERE p.id_module = m.id AND p.role = 'teacher'
  );
