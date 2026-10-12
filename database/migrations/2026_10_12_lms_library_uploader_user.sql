-- Allow admin and teacher accounts to upload library files.
-- Existing uploader IDs are teacher user IDs and remain valid db_lms_users IDs.

ALTER TABLE db_lms_library_files
  DROP FOREIGN KEY fk_library_uploader,
  ADD CONSTRAINT fk_library_uploader_user FOREIGN KEY (uploaded_by)
    REFERENCES db_lms_users (id) ON UPDATE CASCADE;
