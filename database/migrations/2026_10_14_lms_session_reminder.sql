-- Forward-only migration: per-enrollment one-hour session reminder state.
ALTER TABLE `db_lms_session_students`
    ADD COLUMN `reminder_sent_at` DATETIME NULL AFTER `is_graded_late`,
    ADD KEY `idx_ss_reminder_pending` (`reminder_sent_at`, `session_id`);
