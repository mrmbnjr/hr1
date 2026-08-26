USE hr1_db;

ALTER TABLE employees
    ADD COLUMN applicant_id INT NULL AFTER application_id;

UPDATE employees e
INNER JOIN applications ap
    ON e.application_id = ap.application_id
SET e.applicant_id = ap.applicant_id
WHERE e.applicant_id IS NULL;

ALTER TABLE employees
    MODIFY applicant_id INT NOT NULL,
    ADD UNIQUE KEY uq_employees_applicant_id (applicant_id),
    ADD CONSTRAINT fk_employees_applicant
        FOREIGN KEY (applicant_id)
        REFERENCES applicants(applicant_id);
