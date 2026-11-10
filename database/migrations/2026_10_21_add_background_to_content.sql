-- Thêm cột background cho bảng #_content (các item con của nội dung)
ALTER TABLE `db_content`
  ADD COLUMN `background` VARCHAR(500) NULL DEFAULT NULL AFTER `hinh_anh`;
