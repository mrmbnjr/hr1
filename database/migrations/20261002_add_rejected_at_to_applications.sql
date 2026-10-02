ALTER TABLE applications
    ADD COLUMN rejected_at DATETIME NULL AFTER application_status;

UPDATE applications
SET rejected_at = CURRENT_TIMESTAMP
WHERE application_status = 'Rejected';