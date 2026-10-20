-- 2026-10-18: Extend the application pipeline with the Selection and
-- Offer stages, and track when the status last changed.

ALTER TABLE applications
    MODIFY COLUMN application_status
        ENUM(
            'Submitted',
            'Under Review',
            'Interview',
            'Selection',
            'Offer',
            'Hired',
            'Rejected'
        ) DEFAULT 'Submitted';

ALTER TABLE applications
    ADD COLUMN status_updated_at DATETIME NULL
    AFTER application_status;
