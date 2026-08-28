USE hr1_db;

ALTER TABLE job_postings
    ADD COLUMN academic_document_required BOOLEAN NOT NULL DEFAULT FALSE AFTER requirements;

ALTER TABLE applications
    ADD COLUMN academic_document_file VARCHAR(255) NULL AFTER cover_letter_file;

CREATE TABLE IF NOT EXISTS academic_document_evaluations (
    evaluation_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT NOT NULL UNIQUE,
    document_valid BOOLEAN NOT NULL DEFAULT FALSE,
    confidence_score DECIMAL(5,2) NOT NULL DEFAULT 0,
    document_type VARCHAR(150),
    institution VARCHAR(255),
    degree VARCHAR(255),
    field_of_study VARCHAR(255),
    graduation_year VARCHAR(20),
    extracted_details JSON,
    concerns JSON,
    ai_summary TEXT,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id)
        REFERENCES applications(application_id)
        ON DELETE CASCADE
);