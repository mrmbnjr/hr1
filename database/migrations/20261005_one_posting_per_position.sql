-- Enforce one job posting per position, including closed postings.
--
-- Before running this migration, check for existing duplicates:
-- SELECT position_id, COUNT(*) AS posting_count
-- FROM job_postings
-- GROUP BY position_id
-- HAVING COUNT(*) > 1;
--
-- Resolve any returned rows manually before continuing. The migration does
-- not delete postings or applications, so no existing recruitment data is lost.

ALTER TABLE job_postings
    ADD UNIQUE KEY uq_job_postings_position (position_id);
