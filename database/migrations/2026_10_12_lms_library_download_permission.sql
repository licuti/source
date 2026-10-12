-- Admin may disable downloads for individual library resources (teacher mockup §10.3).
ALTER TABLE db_lms_library_files
  ADD COLUMN is_downloadable TINYINT(1) NOT NULL DEFAULT 1
    COMMENT 'Admin controls whether teachers may download this file' AFTER download_count;
