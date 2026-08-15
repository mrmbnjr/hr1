CREATE DATABASE hr1_db;

USE hr1_db;

CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_code VARCHAR(30) UNIQUE NOT NULL,
    role_name VARCHAR(100) NOT NULL
);

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NULL,
    role_id INT NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    status ENUM('Active','Inactive') DEFAULT 'Active',
    must_change_password BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(role_id)
    REFERENCES roles(role_id)
);

CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE positions (
    position_id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    position_name VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (department_id)
        REFERENCES departments(department_id)
);

CREATE TABLE job_postings (
    posting_id INT AUTO_INCREMENT PRIMARY KEY,
    position_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    requirements TEXT,
    employment_type ENUM(
        'Full-Time',
        'Part-Time',
        'Contract',
        'Internship'
    ) NOT NULL,
    vacancies INT DEFAULT 1,
    status ENUM(
        'Open',
        'Closed'
    ) DEFAULT 'Open',
    application_token VARCHAR(64) UNIQUE NULL,
    application_deadline DATE NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

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

    application_status ENUM(
        'Submitted',
        'Under Review',
        'Interview',
        'Hired',
        'Rejected'
    ) DEFAULT 'Submitted',

    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(applicant_id, posting_id),

    FOREIGN KEY (applicant_id)
        REFERENCES applicants(applicant_id),

    FOREIGN KEY (posting_id)
        REFERENCES job_postings(posting_id)
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

    CONSTRAINT fk_ai_screening_application
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
    onboarding_status ENUM(
        'Pending',
        'Ongoing',
        'Completed'
    ) DEFAULT 'Pending',
    remarks TEXT,
    FOREIGN KEY(application_id)
    REFERENCES applications(application_id)
);

CREATE TABLE onboarding_documents (
    document_id INT AUTO_INCREMENT PRIMARY KEY,
    onboarding_id INT,
    document_name VARCHAR(150),
    file_path VARCHAR(255),
    status ENUM(
        'Pending',
        'Submitted',
        'Verified'
    ) DEFAULT 'Pending',
    FOREIGN KEY(onboarding_id)
    REFERENCES onboarding(onboarding_id)
);

CREATE TABLE employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,

    application_id INT UNIQUE,

    employee_number VARCHAR(30) UNIQUE,

    position_id INT,
    department_id INT,

    hire_date DATE NOT NULL,

    employment_status ENUM(
        'Probationary',
        'Regular',
        'Contract'
    ),

    FOREIGN KEY(application_id)
        REFERENCES applications(application_id),

    FOREIGN KEY(position_id)
        REFERENCES positions(position_id),

    FOREIGN KEY(department_id)
        REFERENCES departments(department_id)
);