-- NOTE: no CREATE DATABASE / USE here. Select your database in phpMyAdmin first, then import.
-- =====================================================================
-- EXTEND onboarding_documents FOR THE ASSIGNED REQUESTS PAGE
-- =====================================================================
-- The employee "Assigned Requests" page is backed by HR's existing
-- Onboarding -> "Request Document" flow instead of a separate table.
--
-- `onboarding_documents` already covers the document itself
-- (document_name, instruction, file_path, remarks, status) but has no
-- employee link, no scheduling/priority, no employee response and no
-- timestamps at all. This ALTER adds those, so the page can show the
-- full request/respond/review workflow.
--
-- Nothing is renamed or dropped: every new column is nullable or has a
-- default, so the existing rows and the HR onboarding screens keep
-- working exactly as before. Columns are appended rather than inserted,
-- which keeps this ALTER independent of column ordering.
--
-- Status stays as-is. The page DERIVES its own vocabulary:
--   Pending   -> Pending Action
--   Submitted -> In Progress
--   Verified  -> Completed
--   Rejected  -> Pending Action (returned to the employee)
-- =====================================================================

ALTER TABLE onboarding_documents

    -- Direct employee link, so the page can scope rows to the signed-in
    -- employee without a three-table join. Backfilled below.
    ADD COLUMN employee_id INT NULL,

    -- Categorisation + scheduling, previously not expressible.
    ADD COLUMN request_type VARCHAR(100) NULL,
    ADD COLUMN priority ENUM('Normal', 'High', 'Urgent') NOT NULL DEFAULT 'Normal',
    ADD COLUMN due_date DATE NULL,

    -- Who asked for it.
    ADD COLUMN assigned_by INT NULL,

    -- What the employee must provide:
    --   document     -> upload a file
    --   response     -> write a reply
    --   confirmation -> simply confirm
    ADD COLUMN action_type ENUM('document', 'response', 'confirmation') NOT NULL DEFAULT 'document',

    -- Employee submission.
    ADD COLUMN response_text TEXT NULL,
    ADD COLUMN submission_file_size INT UNSIGNED NULL,
    ADD COLUMN submission_mime VARCHAR(120) NULL,
    ADD COLUMN submitted_at TIMESTAMP NULL DEFAULT NULL,

    -- HR review of the submission.
    ADD COLUMN reviewed_by INT NULL,
    ADD COLUMN reviewed_at TIMESTAMP NULL DEFAULT NULL,
    ADD COLUMN completed_at TIMESTAMP NULL DEFAULT NULL,

    -- The table previously had no timestamps, so "date assigned" was not
    -- derivable at all.
    ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;


-- ---------------------------------------------------------------------
-- Foreign keys
-- ---------------------------------------------------------------------

ALTER TABLE onboarding_documents
    ADD CONSTRAINT fk_onboarding_documents_employee
        FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id)
        ON DELETE SET NULL,

    ADD CONSTRAINT fk_onboarding_documents_assigned_by
        FOREIGN KEY (assigned_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL,

    ADD CONSTRAINT fk_onboarding_documents_reviewed_by
        FOREIGN KEY (reviewed_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL;


-- ---------------------------------------------------------------------
-- Index for the employee-scoped page query
-- ---------------------------------------------------------------------

ALTER TABLE onboarding_documents
    ADD INDEX idx_onboarding_documents_employee (employee_id, status),
    ADD INDEX idx_onboarding_documents_due (due_date);


-- ---------------------------------------------------------------------
-- Backfill employee_id for existing rows
-- ---------------------------------------------------------------------
-- onboarding_documents.onboarding_id -> onboarding.application_id
--                                    -> employees.application_id
-- Both application_id columns are UNIQUE, so this is a 1:1 mapping.
-- ---------------------------------------------------------------------

UPDATE onboarding_documents od

INNER JOIN onboarding o
    ON od.onboarding_id = o.onboarding_id

INNER JOIN employees e
    ON o.application_id = e.application_id

SET od.employee_id = e.employee_id

WHERE od.employee_id IS NULL;
