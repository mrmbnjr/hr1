-- Track when a job posting was actually closed so the application retention
-- policy can measure its 30-day window from the closure moment (the existing
-- application_deadline is a different thing entirely).
--
-- Run in phpMyAdmin against hr1_db, then append the column to schema.sql.
--
-- Existing Closed postings are backfilled with the migration time on purpose:
-- it gives them a fresh retention window instead of making every application
-- on an old posting eligible for immediate removal.

ALTER TABLE job_postings
    ADD COLUMN closed_at DATETIME NULL AFTER status;

UPDATE job_postings
SET closed_at = CURRENT_TIMESTAMP
WHERE status = 'Closed'
  AND closed_at IS NULL;
