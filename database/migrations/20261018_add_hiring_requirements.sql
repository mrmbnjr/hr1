-- 2026-10-18: Hiring requirements tracked across two stages:
--   Selection      - credential, reference, and background checks that
--                    gate "Approve for Offer"
--   Pre-employment - medical/document checks that gate "Confirm Hire"

CREATE TABLE hiring_requirements (
    requirement_id INT AUTO_INCREMENT PRIMARY KEY,

    application_id INT NOT NULL,

    stage ENUM('Selection', 'Pre-employment') NOT NULL,
    requirement_type ENUM(
        'Credential',
        'Reference',
        'Background Check',
        'Medical',
        'Document',
        'Other'
    ) NOT NULL,

    status ENUM(
        'Pending',
        'Cleared',
        'Failed'
    ) DEFAULT 'Pending',

    notes TEXT,

    verified_by INT NULL,
    verified_at DATETIME NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (application_id)
        REFERENCES applications(application_id)
        ON DELETE CASCADE,

    FOREIGN KEY (verified_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);
