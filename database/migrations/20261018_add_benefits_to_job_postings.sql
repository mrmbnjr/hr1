ALTER TABLE job_postings
    ADD COLUMN benefits TEXT NULL
    AFTER requirements;
