-- ==========================================================
-- Unify onboarding_documents.status onto the canonical
-- request vocabulary used by employee_requests:
--
--     ENUM('Pending','Approved','Rejected','Completed')
--
-- Data migration:
--     Submitted -> Pending    (HR requested; submitted_at now
--                              carries the "employee submitted" signal)
--     Verified  -> Approved
--     Rejected  -> Rejected
--     Pending   -> Pending
--     Completed is new: HR "Mark completed" after approval.
--
-- The ENUM is widened to a superset first, the rows are mapped,
-- then it is narrowed to the final four. This avoids MySQL
-- truncating stored values to '' under strict mode.
-- ==========================================================

-- 1) Widen (old + new values) so no stored value is lost.
ALTER TABLE onboarding_documents
    MODIFY COLUMN status
        ENUM('Pending','Submitted','Verified','Rejected','Approved','Completed')
        DEFAULT 'Pending';

-- 2) Migrate the data.
UPDATE onboarding_documents SET status = 'Pending'  WHERE status = 'Submitted';
UPDATE onboarding_documents SET status = 'Approved' WHERE status = 'Verified';

-- 3) Narrow to the canonical four (nullable + DEFAULT preserved).
ALTER TABLE onboarding_documents
    MODIFY COLUMN status
        ENUM('Pending','Approved','Rejected','Completed')
        DEFAULT 'Pending';
