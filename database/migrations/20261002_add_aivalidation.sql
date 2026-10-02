-- AI Validation audit + results table.
-- Run in phpMyAdmin against hr1_db. Also append to your main schema file.
--
-- application_id is NULL when a submission does not become an application.
-- UNIQUE on a nullable column allows multiple unlinked audit rows.

CREATE TABLE application_validations (
    validation_id INT AUTO_INCREMENT PRIMARY KEY,

    submission_reference VARCHAR(30) NOT NULL UNIQUE,

    application_id INT NULL UNIQUE,
    posting_id INT NOT NULL,

    applicant_name VARCHAR(300) NOT NULL,
    applicant_email VARCHAR(150) NOT NULL,
    ip_address VARCHAR(45) NULL,

    validation_status ENUM(
        'PROCESSING',
        'VALID',
        'REVIEW',
        'INVALID'
    ) NOT NULL,

    identity_status ENUM(
        'Consistent',
        'Minor Differences',
        'Conflict',
        'Unverifiable',
        'Not Checked'
    ) NOT NULL DEFAULT 'Not Checked',

    education_status ENUM(
        'Consistent',
        'Minor Differences',
        'Conflict',
        'Unverifiable',
        'Not Checked'
    ) NOT NULL DEFAULT 'Not Checked',

    experience_status ENUM(
        'Consistent',
        'Minor Differences',
        'Conflict',
        'Unverifiable',
        'Not Checked'
    ) NOT NULL DEFAULT 'Not Checked',

    credential_status ENUM(
        'Consistent',
        'Minor Differences',
        'Conflict',
        'Unverifiable',
        'Not Checked'
    ) NOT NULL DEFAULT 'Not Checked',

    consistency_score DECIMAL(5,2) NULL,

    findings JSON,
    validation_summary TEXT,

    ai_model VARCHAR(100) NULL,

    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_validations_email (applicant_email, processed_at),
    INDEX idx_validations_ip (ip_address, processed_at),
    INDEX idx_validations_status (validation_status, processed_at),

    CONSTRAINT fk_validations_application
        FOREIGN KEY (application_id)
        REFERENCES applications(application_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_validations_posting
        FOREIGN KEY (posting_id)
        REFERENCES job_postings(posting_id)
        ON DELETE CASCADE
);