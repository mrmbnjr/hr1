ALTER TABLE job_postings
    ADD COLUMN max_applicants INT UNSIGNED NOT NULL DEFAULT 100
    AFTER vacancies;