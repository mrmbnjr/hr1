-- Create Database
CREATE DATABASE IF NOT EXISTS recruitment_system;
USE recruitment_system;

-- ===========================
-- HR Staff
-- ===========================
CREATE TABLE hr_staff (
    hr_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL
);

-- ===========================
-- Applicant
-- ===========================
CREATE TABLE applicant (
    applicant_id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    status VARCHAR(50),
    phone_number VARCHAR(20),
    date_applied DATE
);

-- ===========================
-- Job Position
-- ===========================
CREATE TABLE job_position (
    job_id INT AUTO_INCREMENT PRIMARY KEY,
    job_title VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    description TEXT,
    required_skills TEXT
);

-- ===========================
-- Resume
-- ===========================
CREATE TABLE resume (
    resume_id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    upload_date DATE,
    resume_file VARCHAR(255),
    FOREIGN KEY (applicant_id)
        REFERENCES applicant(applicant_id)
        ON DELETE CASCADE
);

-- ===========================
-- AI Evaluation
-- ===========================
CREATE TABLE ai_evaluation (
    evaluation_id INT AUTO_INCREMENT PRIMARY KEY,
    resume_id INT NOT NULL,
    match_job_score DECIMAL(5,2),
    matched_skills TEXT,
    overall_score DECIMAL(5,2),
    job_recommendation TEXT,
    strengths TEXT,
    weaknesses TEXT,
    evaluation_date DATE,
    job_id INT NOT NULL,

    FOREIGN KEY (resume_id)
        REFERENCES resume(resume_id)
        ON DELETE CASCADE,

    FOREIGN KEY (job_id)
        REFERENCES job_position(job_id)
        ON DELETE CASCADE
);

-- ===========================
-- Applicant Review
-- ===========================
CREATE TABLE applicant_review (
    decision_id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    decision VARCHAR(50),
    remarks TEXT,
    review_date DATE,
    hr_id INT NOT NULL,

    FOREIGN KEY (applicant_id)
        REFERENCES applicant(applicant_id)
        ON DELETE CASCADE,

    FOREIGN KEY (hr_id)
        REFERENCES hr_staff(hr_id)
        ON DELETE CASCADE
);

-- ===========================
-- Employee
-- ===========================
CREATE TABLE employee (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    applicant_id INT NOT NULL,
    employee_number VARCHAR(20) UNIQUE,
    hire_date DATE,
    employment_status VARCHAR(50),

    FOREIGN KEY (applicant_id)
        REFERENCES applicant(applicant_id)
        ON DELETE CASCADE
);

-- ===========================
-- Employee Assignment
-- ===========================
CREATE TABLE employee_assignment (
    assignment_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    job_id INT NOT NULL,
    assigned_date DATE,
    hr_id INT NOT NULL,

    FOREIGN KEY (employee_id)
        REFERENCES employee(employee_id)
        ON DELETE CASCADE,

    FOREIGN KEY (job_id)
        REFERENCES job_position(job_id)
        ON DELETE CASCADE,

    FOREIGN KEY (hr_id)
        REFERENCES hr_staff(hr_id)
        ON DELETE CASCADE
);

-- ===========================
-- Employee Account
-- ===========================
CREATE TABLE employee_account (
    account_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    account_status VARCHAR(50),

    FOREIGN KEY (employee_id)
        REFERENCES employee(employee_id)
        ON DELETE CASCADE
);