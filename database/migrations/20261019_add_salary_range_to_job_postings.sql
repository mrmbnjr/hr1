-- 2026-10-19: Job postings carry a salary range so applicants see
-- compensation expectations alongside the qualifications.

ALTER TABLE job_postings
    ADD COLUMN salary_range VARCHAR(100) NULL
    AFTER requirements;
