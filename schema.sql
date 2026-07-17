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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(role_id)
    REFERENCES roles(role_id)
);

CREATE TABLE departments (
    department_id INT AUTO_INCREMENT PRIMARY KEY,
    department_name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT
);

CREATE TABLE job_positions (
    position_id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    title VARCHAR(150) NOT NULL,
    description TEXT,
    requirements TEXT,
    employment_type ENUM(
        'Full-Time',
        'Part-Time',
        'Contract',
        'Internship'
    ) NOT NULL,
    salary DECIMAL(10,2),
    vacancies INT DEFAULT 1,
    status ENUM('Open','Closed') DEFAULT 'Open',
    application_deadline DATE NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (department_id)
        REFERENCES departments(department_id),

    FOREIGN KEY (created_by)
        REFERENCES users(user_id)
);

CREATE TABLE applicants (
    applicant_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100),
    middle_name VARCHAR(100),
    last_name VARCHAR(100),
    email VARCHAR(150),
    phone VARCHAR(30),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    position_id INT NOT NULL,

    resume_file VARCHAR(255) NOT NULL,
    cover_letter_file VARCHAR(255),

    application_status ENUM(
        'Submitted',
        'AI Screened',
        'Shortlisted',
        'Interview',
        'Job Offer',
        'Hired',
        'Rejected'
    ) DEFAULT 'Submitted',

    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    UNIQUE(applicant_id, position_id),

    FOREIGN KEY (applicant_id)
        REFERENCES applicants(applicant_id),

    FOREIGN KEY (position_id)
        REFERENCES job_positions(position_id)
);

CREATE TABLE ai_screening (
    screening_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNIQUE,
    match_score DECIMAL(5,2),
    recommendation ENUM(
        'Highly Recommended',
        'Recommended',
        'Consider',
        'Not Recommended'
    ),
    extracted_skills TEXT,
    strengths TEXT,
    weaknesses TEXT,
    ai_summary TEXT,
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(application_id)
    REFERENCES applications(application_id)
);

CREATE TABLE interviews (
    interview_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT,
    interviewer_id INT,
    interview_type ENUM(
        'Phone',
        'Online',
        'Face-to-Face'
    ),
    interview_date DATETIME,
    remarks TEXT,
    result ENUM(
        'Pending',
        'Passed',
        'Failed'
    ) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(application_id)
    REFERENCES applications(application_id),
    FOREIGN KEY(interviewer_id)
    REFERENCES users(user_id)
);

CREATE TABLE job_offers (
    offer_id INT AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNIQUE,
    offered_salary DECIMAL(10,2),
    start_date DATE,
    status ENUM(
        'Pending',
        'Accepted',
        'Declined'
    ) DEFAULT 'Pending',
    offered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(application_id)
    REFERENCES applications(application_id)
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
    hire_date DATE,
    employment_status ENUM(
        'Probationary',
        'Regular',
        'Contract'
    ),
    FOREIGN KEY(application_id)
    REFERENCES applications(application_id)
);

