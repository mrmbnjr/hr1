USE hr1_db;


-- =====================================================
-- ROLES
-- =====================================================

INSERT INTO roles (role_code, role_name) VALUES
('ADMIN', 'Administrator'),
('HR', 'HR Staff'),
('MANAGER', 'Manager'),
('EMPLOYEE', 'Employee');


-- =====================================================
-- DEPARTMENTS
-- =====================================================

INSERT INTO departments (department_name) VALUES
('Human Resources'),
('Information Technology'),
('Finance'),
('Marketing'),
('Operations');


-- =====================================================
-- POSITIONS
-- =====================================================

INSERT INTO positions 
(department_id, position_name)
VALUES

-- HR
(1, 'HR Manager'),
(1, 'HR Staff'),

-- IT
(2, 'Software Developer'),
(2, 'System Administrator'),

-- Finance
(3, 'Accountant'),

-- Marketing
(4, 'Marketing Specialist'),

-- Operations
(5, 'Operations Manager');


-- =====================================================
-- USERS
-- Passwords are bcrypt hashes
-- Default password:
-- password123
-- =====================================================

INSERT INTO users
(employee_id, role_id, username, password, status, must_change_password)
VALUES

(NULL, 1, 'admin', 
'$2y$10$z0hkw101WUmBx0tYHRz78OxigkU4kJACdumSDj0NURZkfNe1A58ka',
'Active', TRUE),

(NULL, 2, 'hr.staff',
'$2y$10$Mgorw4RNTo6LVYR7gGpQhuzuXZUe8utSkR1w.AZLjVu9AdMT7VSdy',
'Active', TRUE),

(NULL, 3, 'manager.it',
'$2y$10$cK7rNx6AA/zaajApDi8jjuVy/MnP2F34XVR1Qd/qnORoHhlKWz7jm',
'Active', TRUE);


-- =====================================================
-- JOB POSTINGS
-- HR Staff creates these
-- created_by = HR user
-- =====================================================
INSERT INTO job_postings
(position_id,
title,
description,
requirements,
employment_type,
vacancies,
status,
application_deadline,
created_by,
application_token)
VALUES

(
3,
'Junior Software Developer',
'Develop and maintain company web applications.',
'PHP, MySQL, JavaScript, HTML, CSS',
'Full-Time',
2,
'Open',
'2026-12-31',
2,
'a8f92c7e31b54d6f90e21a7b45c83912'
),

(
5,
'Accountant',
'Handle company financial records.',
'Accounting degree, bookkeeping experience',
'Full-Time',
1,
'Open',
'2026-11-30',
2,
'c41e8b72f95a36d1097c2e84b56f0139'
),

(
7,
'Operations Manager',
'Manage daily operational activities.',
'Leadership experience and operations background',
'Full-Time',
1,
'Closed',
'2026-10-15',
2,
'd73a91f06c28b45e9172a6f38c50e124'
);



-- =====================================================
-- APPLICANTS
-- =====================================================

INSERT INTO applicants
(first_name, middle_name, last_name, email, phone, address)
VALUES

(
'Juan',
'Antonio',
'Dela Cruz',
'juan.delacruz@email.com',
'09170000001',
'Quezon City'
),

(
'Maria',
'Anne',
'Santos',
'maria.santos@email.com',
'09170000002',
'Manila'
),

(
'Pedro',
'Luis',
'Reyes',
'pedro.reyes@email.com',
'09170000003',
'Pasig City'
);



-- =====================================================
-- APPLICATIONS
-- =====================================================

INSERT INTO applications
(applicant_id,
posting_id,
resume_file,
cover_letter_file,
application_status)
VALUES

(
1,
1,
'uploads/resumes/juan_resume.pdf',
'uploads/covers/juan_cover.pdf',
'Interview'
),

(
2,
1,
'uploads/resumes/maria_resume.pdf',
'uploads/covers/maria_cover.pdf',
'Hired'
),

(
3,
2,
'uploads/resumes/pedro_resume.pdf',
NULL,
'Under Review'
);



-- =====================================================
-- AI SCREENING
-- Automatically created after resume submission
-- =====================================================

INSERT INTO ai_screening
(
application_id,
overall_score,
skills_score,
experience_score,
education_score,
keyword_score,
recommendation,
extracted_skills,
strengths,
concerns,
ai_summary
)
VALUES


(
1,
88.50,
90.00,
85.00,
90.00,
89.00,
'Highly Recommended',

'PHP, JavaScript, MySQL, HTML, CSS',

JSON_ARRAY(
'Strong programming background',
'Good database knowledge'
),

JSON_ARRAY(
'Limited professional experience'
),

'Applicant shows strong technical compatibility for the Junior Software Developer position.'
),


(
2,
92.00,
95.00,
90.00,
90.00,
93.00,
'Highly Recommended',

'PHP, JavaScript, MySQL, Laravel',

JSON_ARRAY(
'Excellent technical skills',
'Relevant project experience'
),

JSON_ARRAY(),

'Applicant is highly suitable for the developer position.'
),


(
3,
75.00,
70.00,
80.00,
75.00,
78.00,
'Recommended',

'Accounting, Bookkeeping, Excel',

JSON_ARRAY(
'Good finance background'
),

JSON_ARRAY(
'Needs interview verification'
),

'Applicant meets basic requirements and requires further evaluation.'
);



-- =====================================================
-- INTERVIEWS
-- =====================================================

INSERT INTO interviews
(
application_id,
interviewer_id,
interview_type,
interview_date,
location,
remarks,
result
)
VALUES

(
1,
3,
'Online',
'2026-08-15 10:00:00',
'Google Meet',
'Technical interview scheduled.',
'Pending'
),


(
2,
3,
'Face-to-Face',
'2026-07-20 14:00:00',
'Main Office',
'Successful interview.',
'Passed'
);



-- =====================================================
-- ONBOARDING
-- =====================================================

INSERT INTO onboarding
(
application_id,
orientation_date,
onboarding_status,
remarks
)
VALUES

(
2,
'2026-08-10',
'Ongoing',
'Employee completing requirements.'
);



-- =====================================================
-- ONBOARDING DOCUMENTS
-- =====================================================

INSERT INTO onboarding_documents
(
onboarding_id,
document_name,
file_path,
status
)
VALUES

(
1,
'Valid ID',
'uploads/documents/maria_id.pdf',
'Submitted'
),

(
1,
'Employment Contract',
'uploads/documents/maria_contract.pdf',
'Verified'
),

(
1,
'Medical Certificate',
NULL,
'Pending'
);



-- =====================================================
-- EMPLOYEES
-- =====================================================

INSERT INTO employees
(
application_id,
employee_number,
hire_date,
employment_status
)
VALUES

(
2,
'EMP-2026-001',
'2026-08-01',
'Probationary'
);



-- =====================================================
-- LINK USER ACCOUNT TO EMPLOYEE
-- Normally created automatically after hiring
-- =====================================================

UPDATE users
SET employee_id = 1
WHERE username = 'manager.it';
