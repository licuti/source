-- ADM-04 Sắp xếp lớp (Class Matching): học viên cần lưu môn học muốn học +
-- yêu cầu giới tính GV để service match GV phù hợp (SRS §ADM-04).
--
-- subject_id: môn học HV cần (tham chiếu db_lms_subjects); NULL = chưa chọn môn.
-- preferred_teacher_gender: 'male' | 'female' | NULL (NULL = không yêu cầu).

ALTER TABLE db_lms_students
  ADD COLUMN subject_id BIGINT(20) UNSIGNED DEFAULT NULL
    COMMENT 'Môn học HV cần học (ADM-04 matching)' AFTER grade,
  ADD COLUMN preferred_teacher_gender VARCHAR(10) DEFAULT NULL
    COMMENT 'male | female | NULL — yêu cầu giới tính GV (ADM-04)' AFTER subject_id,
  ADD KEY idx_student_subject (subject_id),
  ADD CONSTRAINT fk_student_subject FOREIGN KEY (subject_id)
    REFERENCES db_lms_subjects (id) ON UPDATE CASCADE;
