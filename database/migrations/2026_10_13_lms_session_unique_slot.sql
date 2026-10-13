-- Make session generation idempotent even when two cron processes overlap.
ALTER TABLE db_lms_sessions
  ADD UNIQUE KEY uniq_session_fixed_slot (class_id, session_date, start_time);
