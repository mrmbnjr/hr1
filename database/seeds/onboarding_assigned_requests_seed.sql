-- NOTE: no CREATE DATABASE / USE here. Select your database in phpMyAdmin first, then import.
-- =====================================================================
-- ASSIGNED REQUESTS — ENRICH EXISTING ONBOARDING DOCUMENT REQUESTS
-- =====================================================================
-- The employee "Assigned Requests" page reads onboarding_documents. The
-- rows that already exist came from the onboarding seed and only carry a
-- document name, a status and a file path, so the page's newer columns
-- (request type, priority, due date, who assigned it, timestamps) would
-- all be empty.
--
-- This file only BACKFILLS those columns on the existing rows.
--   * no rows are inserted or deleted
--   * no status is changed
--   * the onboarding screens therefore keep showing exactly the same
--     document counts and progress as before
--
-- Run after 20260926_extend_onboarding_documents_for_assigned_requests.sql.
-- =====================================================================


-- ---------------------------------------------------------------------
-- 1. Request type — derived from the document that was requested
-- ---------------------------------------------------------------------

UPDATE onboarding_documents

SET request_type = CASE

    WHEN document_name = 'Government-Issued ID'
        THEN 'Submit Required Document'

    WHEN document_name = 'NBI Clearance'
        THEN 'Submit Missing Requirement'

    WHEN document_name = 'SSS, PhilHealth, Pag-IBIG Numbers'
        THEN 'Submit Additional Employment Document'

    WHEN document_name = 'Signed Employment Contract'
        THEN 'Submit Required Document'

    ELSE 'Other'

END

WHERE request_type IS NULL;


-- ---------------------------------------------------------------------
-- 2. Who assigned it — the HR account that runs onboarding
-- ---------------------------------------------------------------------

UPDATE onboarding_documents

SET assigned_by = 6

WHERE assigned_by IS NULL;


-- ---------------------------------------------------------------------
-- 3. Action type — every one of these is a document request
-- ---------------------------------------------------------------------

UPDATE onboarding_documents

SET action_type = 'document'

WHERE action_type IS NULL;


-- ---------------------------------------------------------------------
-- 4. Created / due dates
-- ---------------------------------------------------------------------
-- Anchored to each employee's hire date so the timeline is plausible and
-- deterministic. Items still awaiting the employee (Pending) or returned
-- to them (Rejected) get a longer window than items already submitted.
--
-- Result today (2026-09-26): the older hires have past-due Pending and
-- Rejected items, which exercises the page's Overdue indicator.
-- ---------------------------------------------------------------------

UPDATE onboarding_documents od

INNER JOIN employees e
    ON od.employee_id = e.employee_id

SET
    od.created_at = DATE_ADD(e.hire_date, INTERVAL 9 HOUR),

    od.due_date = CASE

        WHEN od.status IN ('Pending', 'Rejected')
            THEN DATE_ADD(e.hire_date, INTERVAL 21 DAY)

        ELSE DATE_ADD(e.hire_date, INTERVAL 14 DAY)

    END

WHERE od.employee_id IS NOT NULL;


-- ---------------------------------------------------------------------
-- 5. Priority
-- ---------------------------------------------------------------------
-- Anything still waiting on the employee is High; an NBI Clearance that
-- is still outstanding is the one Urgent case.
-- ---------------------------------------------------------------------

UPDATE onboarding_documents

SET priority = CASE

    WHEN status IN ('Pending', 'Rejected') AND document_name = 'NBI Clearance'
        THEN 'Urgent'

    WHEN status IN ('Pending', 'Rejected')
        THEN 'High'

    ELSE 'Normal'

END;


-- ---------------------------------------------------------------------
-- 6. Submitted files
-- ---------------------------------------------------------------------
-- The original seed pointed file_path at an `onboarding_docs/` directory
-- that was never created, so those links 404'd. Repoint every row that
-- represents a real submission at a sample file that actually exists in
-- storage/uploads/assigned-requests/ (gitignored, and denied direct
-- access by storage/uploads/.htaccess — files are served through the
-- authenticated download route).
--
-- Rows still Pending have no submission, so their file_path stays NULL.
-- ---------------------------------------------------------------------

UPDATE onboarding_documents

SET
    file_path = CONCAT('onboarding_document_', document_id, '.pdf'),

    submission_mime = 'application/pdf',

    submission_file_size = CASE document_id
        WHEN 1 THEN 820
        WHEN 2 THEN 813
        WHEN 3 THEN 833
        WHEN 4 THEN 826
        WHEN 5 THEN 819
        WHEN 7 THEN 832
        WHEN 8 THEN 825
        WHEN 9 THEN 821
        WHEN 10 THEN 814
        WHEN 11 THEN 827
        WHEN 12 THEN 818
        WHEN 13 THEN 811
        WHEN 14 THEN 824
        WHEN 15 THEN 819
        WHEN 17 THEN 825
        ELSE NULL
    END

WHERE status IN ('Submitted', 'Verified', 'Rejected');


-- ---------------------------------------------------------------------
-- 7. Submission / review timestamps
-- ---------------------------------------------------------------------

UPDATE onboarding_documents

SET submitted_at = DATE_ADD(created_at, INTERVAL 2 DAY)

WHERE status IN ('Submitted', 'Verified', 'Rejected');


UPDATE onboarding_documents

SET
    reviewed_at = DATE_ADD(created_at, INTERVAL 4 DAY),
    completed_at = DATE_ADD(created_at, INTERVAL 4 DAY)

WHERE status = 'Verified';


UPDATE onboarding_documents

SET reviewed_at = DATE_ADD(created_at, INTERVAL 4 DAY)

WHERE status = 'Rejected';

