-- ---------------------------------------------------------------------------
-- Seed: 2026_10_07_lms_modules — đăng ký module 3 portal LMS + phân quyền role
--
-- Theo plan §4.1c (sidebar theo mockup, không theo SRS) + SRS §4 (RBAC matrix)
-- + Huong-Dan-Phat-Trien-Page.md §3.1.
--
-- Admin LMS: 14 mục (13 theo Tài liệu admin.md + Tổng quan)
-- Teacher:    7 mục
-- Student:    5 mục
--
-- Chạy 1 lần. Forward-only (KHÔNG có undo — cần xoá thì xoá tay qua admin UI).
-- ---------------------------------------------------------------------------

-- ===================== PORTAL ADMIN (14) =====================
INSERT INTO `db_lms_modules` (`parent`, `name`, `alias`, `portal`, `icon`, `so_thu_tu`, `hien_thi`) VALUES
(0, 'Tổng quan',            'lms-tong-quan',         'admin', 'fa-solid fa-gauge-high',     1, 1),
(0, 'Quản lý học viên',     'lms-hoc-vien',          'admin', 'fa-solid fa-user-graduate',  2, 1),  -- ADM-01
(0, 'Quản lý giáo viên',    'lms-giao-vien',         'admin', 'fa-solid fa-chalkboard-user',3, 1),  -- ADM-02
(0, 'Quản lý lớp học',      'lms-lop',               'admin', 'fa-solid fa-school',        4, 1),  -- ADM-03
(0, 'Học sinh - Lớp học',   'lms-lop-hoc-sinh',      'admin', 'fa-solid fa-users',         5, 1),
(0, 'Quản lý BTVN',         'lms-bai-tap-ve-nha',    'admin', 'fa-solid fa-file-pen',      6, 1),
(0, 'Quản lý vi phạm',      'lms-vi-pham',           'admin', 'fa-solid fa-triangle-exclamation', 7, 1), -- ADM-06
(0, 'Quản lý yêu cầu',      'lms-yeu-cau',           'admin', 'fa-solid fa-inbox',         8, 1),  -- ADM-05
(0, 'Quản lý lộ trình',     'lms-lo-trinh',          'admin', 'fa-solid fa-route',         9, 1),
(0, 'Quản lý nội quy',      'lms-noi-quy',           'admin', 'fa-solid fa-scroll',       10, 1),
(0, 'Quản lý thư viện',     'lms-thu-vien',          'admin', 'fa-solid fa-folder-open',   11, 1),
(0, 'Thống kê phí dịch vụ', 'lms-phi-dv',            'admin', 'fa-solid fa-money-bill-wave',12, 1), -- ADM-07
(0, 'Thống kê số buổi học', 'lms-thong-ke-buoi-hoc', 'admin', 'fa-solid fa-calendar-check',13, 1),
(0, 'Sắp xếp lớp',          'lms-sap-xep-lop',       'admin', 'fa-solid fa-shuffle',       14, 1); -- ADM-04

-- ===================== PORTAL TEACHER (7 base; TCH-06 added by later migration) =====================
INSERT INTO `db_lms_modules` (`parent`, `name`, `alias`, `portal`, `icon`, `so_thu_tu`, `hien_thi`) VALUES
(0, 'Tổng quan',                'lms-tong-quan',        'teacher', 'fa-solid fa-gauge-high',         1, 1),  -- TCH-00
(0, 'Tài khoản giáo viên',      'lms-tai-khoan',        'teacher', 'fa-solid fa-user-tie',           2, 1),
(0, 'Học viên của tôi',         'lms-hoc-vien-cua-toi', 'teacher', 'fa-solid fa-user-graduate',      3, 1),
(0, 'Quản lý lịch',             'lms-lich-day',         'teacher', 'fa-solid fa-calendar-days',      4, 1),  -- TCH-01/02
(0, 'Phí dịch vụ',              'lms-phi-dv',           'teacher', 'fa-solid fa-money-bill-wave',    5, 1),  -- TCH-05
(0, 'Vi phạm chất lượng dịch vụ','lms-vi-pham',         'teacher', 'fa-solid fa-triangle-exclamation',6, 1),
(0, 'Thư viện',                 'lms-thu-vien',         'teacher', 'fa-solid fa-folder-open',        7, 1);  -- TCH-07

-- ===================== PORTAL STUDENT (5) =====================
INSERT INTO `db_lms_modules` (`parent`, `name`, `alias`, `portal`, `icon`, `so_thu_tu`, `hien_thi`) VALUES
(0, 'Tổng quan',       'lms-tong-quan',      'student', 'fa-solid fa-gauge-high',             1, 1),  -- STU-00
(0, 'Các khoá học',    'lms-khoa-hoc',       'student', 'fa-solid fa-book-open',              2, 1),  -- STU-01
(0, 'Lịch học',        'lms-lich-hoc',       'student', 'fa-solid fa-calendar-days',          3, 1),  -- STU-02
(0, 'Nhận xét tháng',  'lms-nhan-xet-thang', 'student', 'fa-solid fa-comment-dots',           4, 1),  -- STU-04
(0, 'Nội quy lớp học', 'lms-noi-quy',        'student', 'fa-solid fa-scroll',                 5, 1);

-- ===================== Module ẩn: trang thông báo (chuông header, T10) =====================
-- hien_thi = 0 → không hiện sidebar (14 admin / 8 teacher after TCH-06 migration / 5 student),
-- nhưng có page riêng để router phục vụ + endpoint AJAX (auth gate + CSRF + quyền portal).
INSERT INTO `db_lms_modules` (`parent`, `name`, `alias`, `portal`, `icon`, `so_thu_tu`, `hien_thi`) VALUES
(0, 'Thông báo', 'lms-thong-bao', 'admin',   'fa-solid fa-bell', 99, 0),
(0, 'Thông báo', 'lms-thong-bao', 'teacher', 'fa-solid fa-bell', 99, 0),
(0, 'Thông báo', 'lms-thong-bao', 'student', 'fa-solid fa-bell', 99, 0);

-- ===================== Module: nhật ký hệ thống (audit log viewer, N3#11) =====================
-- Chỉ admin, hiện trên sidebar (hien_thi = 1, read-only). Migration
-- 2026_10_13_lms_audit_log_module.sql đăng ký cùng dòng này — giữ đồng bộ
-- để re-seed sample_data không bị mất module.
INSERT INTO `db_lms_modules` (`parent`, `name`, `alias`, `portal`, `icon`, `so_thu_tu`, `hien_thi`)
SELECT 0, 'Nhật ký hệ thống', 'lms-audit-log', 'admin', 'fa-solid fa-clipboard-list', 15, 1
WHERE NOT EXISTS (
    SELECT 1 FROM `db_lms_modules` WHERE alias = 'lms-audit-log' AND portal = 'admin'
);

-- ===================== PHÂN QUYỀN THEO ROLE =====================
-- Admin: toàn quyền (super_admin còn bypass qua lms_check_permission).
-- Chỉ module hiện trên sidebar (hien_thi = 1); module ẩn lms-thong-bao có block riêng bên dưới.
INSERT INTO `db_lms_role_permissions` (`id_module`, `role`, `can_view`, `can_edit`, `can_delete`)
SELECT m.id, 'admin', 1, 1, 1 FROM `db_lms_modules` m WHERE m.portal = 'admin' and m.hien_thi = 1;

-- Teacher: xem + sửa module portal teacher (không xoá).
INSERT INTO `db_lms_role_permissions` (`id_module`, `role`, `can_view`, `can_edit`, `can_delete`)
SELECT m.id, 'teacher', 1, 1, 0 FROM `db_lms_modules` m WHERE m.portal = 'teacher' and m.hien_thi = 1;

-- Student: chỉ xem.
INSERT INTO `db_lms_role_permissions` (`id_module`, `role`, `can_view`, `can_edit`, `can_delete`)
SELECT m.id, 'student', 1, 0, 0 FROM `db_lms_modules` m WHERE m.portal = 'student' and m.hien_thi = 1;

-- Module ẩn lms-thong-bao: 3 role đều được xem (trang + endpoint AJAX chuông header).
INSERT INTO `db_lms_role_permissions` (`id_module`, `role`, `can_view`, `can_edit`, `can_delete`)
SELECT m.id, m.portal, 1, 0, 0 FROM `db_lms_modules` m WHERE m.alias = 'lms-thong-bao';
