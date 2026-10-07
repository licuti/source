-- ---------------------------------------------------------------------------
-- Seed: 2026_10_07_lms_modules — đăng ký module 3 portal LMS + phân quyền role
--
-- Theo plan §4.1c (sidebar theo mockup, không theo SRS) + SRS §4 (RBAC matrix)
-- + Huong-Dan-Phat-Trien-Page.md §3.1.
--
-- Admin LMS: 14 mục (13 theo Tài liệu admin.md + Tổng quan)
-- Teacher:    6 mục
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

-- ===================== PORTAL TEACHER (6) =====================
INSERT INTO `db_lms_modules` (`parent`, `name`, `alias`, `portal`, `icon`, `so_thu_tu`, `hien_thi`) VALUES
(0, 'Tổng quan',         'lms-tong-quan',          'teacher', 'fa-solid fa-gauge-high',         1, 1),  -- TCH-00
(0, 'Học viên của tôi',  'lms-hoc-vien-cua-toi',   'teacher', 'fa-solid fa-user-graduate',      2, 1),
(0, 'Quản lý lịch',      'lms-lich-day',           'teacher', 'fa-solid fa-calendar-days',      3, 1),  -- TCH-01/02
(0, 'Phí dịch vụ',       'lms-phi-dv',             'teacher', 'fa-solid fa-money-bill-wave',    4, 1),  -- TCH-05
(0, 'Vi phạm nội quy',   'lms-vi-pham',            'teacher', 'fa-solid fa-triangle-exclamation',5, 1),
(0, 'Thư viện',          'lms-thu-vien',           'teacher', 'fa-solid fa-folder-open',        6, 1);  -- TCH-07

-- ===================== PORTAL STUDENT (5) =====================
INSERT INTO `db_lms_modules` (`parent`, `name`, `alias`, `portal`, `icon`, `so_thu_tu`, `hien_thi`) VALUES
(0, 'Tổng quan',       'lms-tong-quan',      'student', 'fa-solid fa-gauge-high',             1, 1),  -- STU-00
(0, 'Các khoá học',    'lms-khoa-hoc',       'student', 'fa-solid fa-book-open',              2, 1),  -- STU-01
(0, 'Lịch học',        'lms-lich-hoc',       'student', 'fa-solid fa-calendar-days',          3, 1),  -- STU-02
(0, 'Nhận xét tháng',  'lms-nhan-xet-thang', 'student', 'fa-solid fa-comment-dots',           4, 1),  -- STU-04
(0, 'Nội quy lớp học', 'lms-noi-quy',        'student', 'fa-solid fa-scroll',                 5, 1);

-- ===================== PHÂN QUYỀN THEO ROLE =====================
-- Admin: toàn quyền (super_admin còn bypass qua lms_check_permission).
INSERT INTO `db_lms_role_permissions` (`id_module`, `role`, `can_view`, `can_edit`, `can_delete`)
SELECT m.id, 'admin', 1, 1, 1 FROM `db_lms_modules` m WHERE m.portal = 'admin';

-- Teacher: xem + sửa module portal teacher (không xoá).
INSERT INTO `db_lms_role_permissions` (`id_module`, `role`, `can_view`, `can_edit`, `can_delete`)
SELECT m.id, 'teacher', 1, 1, 0 FROM `db_lms_modules` m WHERE m.portal = 'teacher';

-- Student: chỉ xem.
INSERT INTO `db_lms_role_permissions` (`id_module`, `role`, `can_view`, `can_edit`, `can_delete`)
SELECT m.id, 'student', 1, 0, 0 FROM `db_lms_modules` m WHERE m.portal = 'student';
