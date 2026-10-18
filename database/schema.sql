CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_code VARCHAR(30) UNIQUE NOT NULL,
    role_name VARCHAR(100) NOT NULL
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NULL UNIQUE,
    role_id INT NOT NULL,

    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    email_verified_at DATETIME NULL,
    password VARCHAR(255) NOT NULL,

    status ENUM('Active', 'Inactive') DEFAULT 'Active',
    must_change_password BOOLEAN DEFAULT TRUE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (role_id)
        REFERENCES roles(role_id)
);

CREATE TABLE login_otps (
    otp_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_login_otps_user_active (user_id, used_at),
    INDEX idx_login_otps_expires (expires_at),

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE password_reset_codes (
    reset_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    expires_at DATETIME NOT NULL,
    used_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_password_reset_codes_user_active (user_id, used_at),
    INDEX idx_password_reset_codes_expires (expires_at),

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE CASCADE
);

CREATE TABLE password_reset_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    identifier VARCHAR(150) NOT NULL,
    contact VARCHAR(150) NOT NULL,
    message TEXT NULL,
    status ENUM('Pending', 'Approved', 'Rejected', 'Completed') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    resolved_at DATETIME NULL,
    resolved_by INT NULL,

    INDEX idx_password_reset_requests_status (status, created_at),

    FOREIGN KEY (resolved_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);

CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) UNIQUE NOT NULL,

    status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE positions (
    position_id INT AUTO_INCREMENT PRIMARY KEY,

    department_id INT NOT NULL,
    role_id INT NOT NULL,

    position_name VARCHAR(150) NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (department_id)
        REFERENCES departments(department_id),

    FOREIGN KEY (role_id)
        REFERENCES roles(role_id)
);

CREATE TABLE job_postings (
    posting_id INT AUTO_INCREMENT PRIMARY KEY,

    position_id INT NOT NULL,

    title VARCHAR(150) NOT NULL,
    description TEXT,
    requirements TEXT,

    -- Newline-separated benefits entered by HR. NULL until confirmed.
    benefits TEXT NULL,

    academic_document_required BOOLEAN NOT NULL DEFAULT FALSE,

    employment_type ENUM(
        'Full-Time',
        'Part-Time',
        'Contract',
        'Internship'
    ) NOT NULL,

    vacancies INT DEFAULT 1,

    max_applicants INT UNSIGNED NOT NULL DEFAULT 100,

    status ENUM(
        'Open',
        'Closed'
    ) DEFAULT 'Open',

    -- Set when the posting transitions to 'Closed'. Drives the application
    -- retention window (see ApplicationRetentionService).
    closed_at DATETIME NULL,

    application_token VARCHAR(64) UNIQUE NULL,

    application_deadline DATE NOT NULL,

    created_by INT NOT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uq_job_postings_position (position_id),

    FOREIGN KEY (position_id)
        REFERENCES positions(position_id),

    FOREIGN KEY (created_by)
        REFERENCES users(user_id)
);

CREATE TABLE applicants (
    applicant_id INT AUTO_INCREMENT PRIMARY KEY,

    first_name VARCHAR(100) NOT NULL,
    middle_name VARCHAR(100),
    last_name VARCHAR(100) NOT NULL,

    email VARCHAR(150) UNIQUE NOT NULL,
    phone VARCHAR(30) UNIQUE NOT NULL,

    address TEXT,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,

    applicant_id INT NOT NULL,
    posting_id INT NOT NULL,

    resume_file VARCHAR(255) NOT NULL,
    cover_letter_file VARCHAR(255),
    academic_document_file VARCHAR(255),

    application_status ENUM(
        'Submitted',
        'Under Review',
        'Interview',
        'Hired',
        'Rejected'
    ) DEFAULT 'Submitted',

    rejected_at DATETIME NULL,

    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE (applicant_id, posting_id),

    FOREIGN KEY (applicant_id)
        REFERENCES applicants(applicant_id),

    FOREIGN KEY (posting_id)
        REFERENCES job_postings(posting_id)
);

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

CREATE TABLE ai_screening (
    screening_id INT AUTO_INCREMENT PRIMARY KEY,

    application_id INT NOT NULL UNIQUE,

    overall_score DECIMAL(5,2) NOT NULL,

    skills_score DECIMAL(5,2) NOT NULL,
    experience_score DECIMAL(5,2) NOT NULL,
    education_score DECIMAL(5,2) NOT NULL,
    keyword_score DECIMAL(5,2) NOT NULL,

    recommendation ENUM(
        'Highly Recommended',
        'Recommended',
        'Consider',
        'Not Recommended'
    ) NOT NULL,

    extracted_skills TEXT,

    strengths JSON,
    concerns JSON,

    ai_summary TEXT,

    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (application_id)
        REFERENCES applications(application_id)
        ON DELETE CASCADE
);

CREATE TABLE interviews (
    interview_id INT AUTO_INCREMENT PRIMARY KEY,

    application_id INT NOT NULL UNIQUE,
    interviewer_id INT NOT NULL,

    interview_type ENUM(
        'Phone',
        'Online',
        'Face-to-Face'
    ) NOT NULL,

    interview_date DATETIME NOT NULL,

    location VARCHAR(255),

    remarks TEXT,

    result ENUM(
        'Pending',
        'Passed',
        'Failed'
    ) DEFAULT 'Pending',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (application_id)
        REFERENCES applications(application_id),

    FOREIGN KEY (interviewer_id)
        REFERENCES users(user_id)
);

CREATE TABLE onboarding (
    onboarding_id INT AUTO_INCREMENT PRIMARY KEY,

    application_id INT UNIQUE,

    orientation_date DATE,

    started_at DATE,
    completed_at DATE,

    onboarding_status ENUM(
        'Pending',
        'In Progress',
        'Completed',
        'Overdue'
    ) DEFAULT 'Pending',

    system_access_setup_completed BOOLEAN NOT NULL DEFAULT FALSE,
    orientation_setup_completed BOOLEAN NOT NULL DEFAULT FALSE,
    document_submission_completed BOOLEAN NOT NULL DEFAULT FALSE,
    document_verification_completed BOOLEAN NOT NULL DEFAULT FALSE,

    remarks TEXT,

    FOREIGN KEY (application_id)
        REFERENCES applications(application_id)
);

CREATE TABLE onboarding_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,

    onboarding_id INT NOT NULL,
    employee_id INT NULL,

    document_name VARCHAR(150) NOT NULL,
    instruction VARCHAR(255),
    file_path VARCHAR(255),
    remarks TEXT NULL,

    status ENUM(
        'Pending',
        'Approved',
        'Rejected'
    ) DEFAULT 'Pending',

    -- Request workflow extensions
    request_type VARCHAR(100) NULL,
    priority ENUM('Normal', 'High', 'Urgent') NOT NULL DEFAULT 'Normal',
    due_date DATE NULL,
    assigned_by INT NULL,
    action_type ENUM('document', 'response', 'confirmation') NOT NULL DEFAULT 'document',

    -- Employee submission
    response_text TEXT NULL,
    submission_file_size INT UNSIGNED NULL,
    submission_mime VARCHAR(120) NULL,
    submitted_at TIMESTAMP NULL DEFAULT NULL,

    -- HR review
    reviewed_by INT NULL,
    reviewed_at TIMESTAMP NULL DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_onboarding_documents_employee (employee_id, status),
    INDEX idx_onboarding_documents_due (due_date),

    FOREIGN KEY (onboarding_id)
        REFERENCES onboarding(onboarding_id)
        ON DELETE CASCADE,

    CONSTRAINT fk_onboarding_documents_assigned_by
        FOREIGN KEY (assigned_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL,

    CONSTRAINT fk_onboarding_documents_reviewed_by
        FOREIGN KEY (reviewed_by)
        REFERENCES users(user_id)
        ON DELETE SET NULL
);

CREATE TABLE onboarding_phases (
    phase_id INT AUTO_INCREMENT PRIMARY KEY,
    onboarding_id INT NOT NULL,

    phase_number INT NOT NULL,
    phase_name VARCHAR(100) NOT NULL,
    phase_description TEXT,

    status ENUM(
        'pending',
        'in-progress',
        'completed'
    ) DEFAULT 'pending',

    completed_at TIMESTAMP NULL DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uq_onboarding_phase (onboarding_id, phase_number),
    FOREIGN KEY (onboarding_id)
        REFERENCES onboarding(onboarding_id)
        ON DELETE CASCADE
);

CREATE TABLE employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,

    application_id INT UNIQUE,
    applicant_id INT NOT NULL UNIQUE,

    employee_number VARCHAR(30) UNIQUE,

    position_id INT NOT NULL,

    hire_date DATE NOT NULL,

    employment_status ENUM(
        'Probationary',
        'Regular',
        'Contract'
    ) DEFAULT 'Probationary',

    FOREIGN KEY (application_id)
        REFERENCES applications(application_id),

    FOREIGN KEY (applicant_id)
        REFERENCES applicants(applicant_id),

    FOREIGN KEY (position_id)
        REFERENCES positions(position_id)
);

CREATE TABLE employee_number_sequence (
    sequence_id TINYINT UNSIGNED PRIMARY KEY,
    next_number INT UNSIGNED NOT NULL
);

INSERT INTO employee_number_sequence (sequence_id, next_number)
VALUES (1, 1);

ALTER TABLE users
ADD CONSTRAINT fk_users_employee
FOREIGN KEY (employee_id)
REFERENCES employees(employee_id);

ALTER TABLE onboarding_documents
ADD CONSTRAINT fk_onboarding_documents_employee
FOREIGN KEY (employee_id)
REFERENCES employees(employee_id)
ON DELETE SET NULL;

CREATE TABLE activity_logs (
    activity_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    employee_id INT NULL,
    action VARCHAR(150) NOT NULL,
    page VARCHAR(100) NULL,
    request_method VARCHAR(10) NOT NULL,
    ip_address VARCHAR(45) NULL,
    user_agent VARCHAR(500) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_activity_employee_created (employee_id, created_at),
    INDEX idx_activity_user_created (user_id, created_at),

    FOREIGN KEY (user_id)
        REFERENCES users(user_id)
        ON DELETE SET NULL,

    FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id)
        ON DELETE SET NULL
);

CREATE TABLE employee_requests (
    request_id INT AUTO_INCREMENT PRIMARY KEY,

    employee_id INT NOT NULL,

    request_type ENUM(
        'Certificate of Employment',
        'Document Request',
        'Profile Update',
        'Payroll Concern',
        'Employment Concern',
        'Other'
    ) NOT NULL,

    subject VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,

    status ENUM(
        'Pending',
        'Approved',
        'Rejected',
        'Completed'
    ) DEFAULT 'Pending',

    hr_remarks TEXT,

    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    resolved_at TIMESTAMP NULL,

    resolved_by INT NULL,

    FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id),

    FOREIGN KEY (resolved_by)
        REFERENCES users(user_id)
);

CREATE TABLE academic_document_evaluations (
    evaluation_id INT AUTO_INCREMENT PRIMARY KEY,

    application_id INT NOT NULL,

    file_name VARCHAR(255) NOT NULL,
    original_file_name VARCHAR(255) NOT NULL,

    is_primary BOOLEAN NOT NULL DEFAULT FALSE,

    evaluation_status ENUM(
        'Pending',
        'Processing',
        'Evaluated',
        'Failed'
    ) NOT NULL DEFAULT 'Pending',

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

    processed_at TIMESTAMP NULL DEFAULT NULL,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_academic_evaluations_application (
        application_id
    ),

    INDEX idx_academic_evaluations_primary (
        application_id,
        is_primary
    ),

    FOREIGN KEY (application_id)
        REFERENCES applications(application_id)
        ON DELETE CASCADE
);