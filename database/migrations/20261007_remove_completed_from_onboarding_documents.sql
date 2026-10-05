UPDATE onboarding_documents
SET
    status = 'Approved',
    completed_at = NULL
WHERE status = 'Completed';

ALTER TABLE onboarding_documents
    DROP COLUMN completed_at,
    MODIFY COLUMN status
        ENUM('Pending', 'Approved', 'Rejected')
        DEFAULT 'Pending';
