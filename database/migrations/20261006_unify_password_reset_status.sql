-- ==========================================================
-- Unify password_reset_requests.status onto the canonical
-- request vocabulary used by employee_requests:
--
--     ENUM('Pending','Approved','Rejected','Completed')
--
-- Data migration:
--     Resolved -> Completed
--     Pending  -> Pending
--
-- Password resets have no Approve stage: HR marks the request
-- completed once the password is actually reset, or rejects it.
-- 'Approved' exists in the vocabulary for consistency but resets
-- never enter it.
--
-- Widened first, mapped, then narrowed to avoid strict-mode
-- truncation of stored values.
-- ==========================================================

-- 1) Widen (old + new values) so no stored value is lost.
ALTER TABLE password_reset_requests
    MODIFY COLUMN status
        ENUM('Pending','Resolved','Approved','Rejected','Completed')
        NOT NULL DEFAULT 'Pending';

-- 2) Migrate the data.
UPDATE password_reset_requests SET status = 'Completed' WHERE status = 'Resolved';

-- 3) Narrow to the canonical four (NOT NULL + DEFAULT preserved).
ALTER TABLE password_reset_requests
    MODIFY COLUMN status
        ENUM('Pending','Approved','Rejected','Completed')
        NOT NULL DEFAULT 'Pending';
