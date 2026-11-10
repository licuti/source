-- Migration: 2026_10_20_lms_teacher_profiles.sql
-- Bảng phụ db_lms_teacher_profiles phục vụ hiển thị hồ sơ truyền thông giáo viên ra website ngoài.

CREATE TABLE IF NOT EXISTS `db_lms_teacher_profiles` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `teacher_id` bigint(20) unsigned NOT NULL COMMENT 'FK trỏ tới db_lms_teachers.user_id',
  `slug` varchar(255) NOT NULL COMMENT 'Slug đường dẫn tĩnh ngoài web',
  `title_badge` varchar(255) DEFAULT NULL COMMENT 'Ví dụ: IELTS 8.0 OVERALL, Thạc sĩ TESOL',
  `avatar` varchar(255) DEFAULT NULL COMMENT 'Ảnh đại diện chân dung truyền thông',
  `quote` text DEFAULT NULL COMMENT 'Câu châm ngôn, slogan cá nhân',
  `intro_text` text DEFAULT NULL COMMENT 'Đoạn giới thiệu ngắn',
  `certificates` text DEFAULT NULL COMMENT 'Chứng chỉ nổi bật',
  `experience` text DEFAULT NULL COMMENT 'Kinh nghiệm giảng dạy & làm việc',
  `awards` text DEFAULT NULL COMMENT 'Giải thưởng đạt được',
  `education` text DEFAULT NULL COMMENT 'Trình độ học vấn',
  `education_experience` text DEFAULT NULL COMMENT 'Học vấn & kinh nghiệm giảng dạy (legacy)',
  `diploma_images` text DEFAULT NULL COMMENT 'JSON mảng ảnh scan bằng cấp chuyên môn',
  `intro_video` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn video giới thiệu',
  `video_thumbnail` varchar(255) DEFAULT NULL COMMENT 'Ảnh thumbnail video giới thiệu',
  `course_ids` text DEFAULT NULL COMMENT 'JSON mảng ID các khóa học liên quan',
  `so_thu_tu` int(11) DEFAULT 0 COMMENT 'Thứ tự hiển thị',
  `show_on_website` tinyint(1) DEFAULT 0 COMMENT '1: Hiển thị ngoài website, 0: Ẩn',
  `seo_title` varchar(255) DEFAULT NULL,
  `seo_keyword` varchar(255) DEFAULT NULL,
  `seo_des` text DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_teacher_id` (`teacher_id`),
  UNIQUE KEY `uniq_slug` (`slug`),
  KEY `idx_show_website` (`show_on_website`),
  KEY `idx_so_thu_tu` (`so_thu_tu`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
