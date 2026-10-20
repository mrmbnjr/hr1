-- 2026-10-18: Job offers for the Offer stage.
--
-- Position and salary are snapshotted so later posting edits do not
-- rewrite offer history. One offer per application.

CREATE TABLE job_offers (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,

    application_id INT NOT NULL UNIQUE,

    offered_position VARCHAR(150) NOT NULL,
    offered_salary DECIMAL(10, 2) NOT NULL,
    employment_type ENUM(
        'Full-Time',
        'Part-Time',
        'Contract',
        'Internship'
    ) NOT NULL,

    start_date DATE NOT NULL,
    terms TEXT,

    status ENUM(
        'Draft',
        'Sent',
        'Accepted',
        'Declined'
    ) DEFAULT 'Draft',

    offer_expires_on DATE NULL,

    prepared_by INT NULL,
    sent_at DATETIME NULL,
    responded_at DATETIME NULL,
    response_note TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (application_id)
        REFERENCES applications(application_id)
        ON DELETE CASCADE,

    FOREIGN KEY (prepared_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);
