-- =====================================================================
-- RAM-YUM HRMS (hr1_db) — Seed Data
-- =====================================================================
-- Purpose : Sample data for local development and demos of the
--           Recruitment, Applicant, Onboarding, Core HCM,
--           and Employee Self-Service modules.
--
-- IMPORTANT:
-- This seed assumes the FINAL hr1_db schema.
--
-- Dependency order:
-- roles
--   -> users
--   -> departments
--   -> positions
--   -> job_postings
--   -> applicants
--   -> applications
--   -> ai_screening
--   -> interviews
--   -> onboarding
--   -> onboarding_documents
--   -> employees
--   -> employee users
--   -> employee_requests
--
-- NOTE:
-- users.employee_id is nullable because system users such as Admin
-- may not belong to an employee record.
-- =====================================================================

USE hr1_db;


-- =====================================================================
-- 1. ROLES
-- =====================================================================

INSERT INTO roles (
    role_code,
    role_name
) VALUES
('ADMIN', 'System Administrator'),
('HR',    'HR Staff'),
('MGR',   'Manager'),
('EMP',   'Employee');

-- role_id:
-- 1 = ADMIN
-- 2 = HR
-- 3 = MGR
-- 4 = EMP


-- =====================================================================
-- 2. SYSTEM USERS
-- =====================================================================
-- Test password for the sample accounts:
-- Admin!123
--
-- The password below is a placeholder bcrypt hash.
-- Replace it with password_hash() output for production use.
-- =====================================================================

INSERT INTO users (
    employee_id,
    role_id,
    username,
    password,
    status,
    must_change_password
) VALUES (
    NULL,
    1,
    'admin',
    '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa',
    'Active',
    0
);

-- user_id:
-- 1 = admin


-- =====================================================================
-- 3. DEPARTMENTS
-- =====================================================================

INSERT INTO departments (
    department_name
) VALUES
('Information Technology'),
('Human Resources'),
('Finance'),
('Marketing'),
('Operations');

-- department_id:
-- 1 = Information Technology
-- 2 = Human Resources
-- 3 = Finance
-- 4 = Marketing
-- 5 = Operations


-- =====================================================================
-- 4. POSITIONS
-- =====================================================================
-- role_id represents the intended SYSTEM ROLE associated with
-- the position.
--
-- EMP  = Employee
-- MGR  = Manager
-- HR   = HR Staff
-- ADMIN positions are generally not recruited as normal positions.
-- =====================================================================

INSERT INTO positions (
    department_id,
    role_id,
    position_name
) VALUES

(1, 4, 'Software Developer'),
(1, 3, 'IT Manager'),
(1, 4, 'UI/UX Designer'),

(2, 4, 'HR Officer'),
(2, 3, 'HR Manager'),

(3, 4, 'Financial Analyst'),

(4, 4, 'Marketing Associate'),
(4, 3, 'Marketing Manager'),

(5, 4, 'Operations Coordinator');

-- position_id:
-- 1 = Software Developer
-- 2 = IT Manager
-- 3 = UI/UX Designer
-- 4 = HR Officer
-- 5 = HR Manager
-- 6 = Financial Analyst
-- 7 = Marketing Associate
-- 8 = Marketing Manager
-- 9 = Operations Coordinator


-- =====================================================================
-- 5. JOB POSTINGS
-- =====================================================================
-- created_by references users(user_id).
-- Admin is the only system user available at this point.
-- =====================================================================

INSERT INTO job_postings (
    position_id,
    title,
    description,
    requirements,
    employment_type,
    vacancies,
    status,
    application_token,
    application_deadline,
    created_by
) VALUES

(
    1,
    'Software Developer',
    'Develop, test, and maintain web-based applications for internal HR systems.',
    'BS in Computer Science or related field; proficiency in PHP, JavaScript, and MySQL; at least 1 year of experience.',
    'Full-Time',
    2,
    'Open',
    'a1f9c3d84e2b4a1f9d3c7e6b0a5f2c11',
    '2026-09-30',
    1
),

(
    3,
    'UI/UX Designer',
    'Design user interfaces and improve user experience across HRMS modules.',
    'Portfolio required; experience with Figma; understanding of responsive design principles.',
    'Full-Time',
    1,
    'Open',
    'b2e8d4c95f3a4b2e8c4d6f7a1b6e3d22',
    '2026-09-20',
    1
),

(
    4,
    'HR Officer',
    'Support recruitment, onboarding, and employee records management.',
    'BS in Psychology, HR Management, or related field; strong communication skills.',
    'Full-Time',
    2,
    'Open',
    'c3d7e5b06a4c5d3f9e5c8b2a7f4d1e33',
    '2026-09-15',
    1
),

(
    6,
    'Financial Analyst',
    'Prepare financial reports and assist in budget planning and forecasting.',
    'BS in Accountancy or Finance; CPA preferred; proficient in Excel and financial modeling.',
    'Full-Time',
    1,
    'Closed',
    'd4c6f6a17b5d6e4a0f6d9c3b8a5e2f44',
    '2026-07-31',
    1
),

(
    7,
    'Marketing Associate',
    'Assist in planning and executing marketing campaigns and social media content.',
    'BS in Marketing or Communications; creative writing and content creation skills.',
    'Full-Time',
    3,
    'Open',
    'e5b5a7328c6e7f5b1a7e0d4c9b6f3a55',
    '2026-10-15',
    1
),

(
    9,
    'Operations Coordinator',
    'Coordinate daily operational activities and support process improvement initiatives.',
    'BS in Business Administration or related field; strong organizational skills.',
    'Contract',
    1,
    'Closed',
    'f6a4b8439d7f8a6c2b8f1e5d0a7c4b66',
    '2026-06-30',
    1
);

-- posting_id:
-- 1 = Software Developer
-- 2 = UI/UX Designer
-- 3 = HR Officer
-- 4 = Financial Analyst
-- 5 = Marketing Associate
-- 6 = Operations Coordinator


-- =====================================================================
-- 6. APPLICANTS
-- =====================================================================

INSERT INTO applicants (
    first_name,
    middle_name,
    last_name,
    email,
    phone,
    address
) VALUES

(
    'Juan',
    'Ramirez',
    'Dela Cruz',
    'juan.delacruz@example.com',
    '09171234501',
    'Quezon City, Metro Manila'
),

(
    'Maria',
    'Lopez',
    'Santos',
    'maria.santos@example.com',
    '09171234502',
    'Manila, Metro Manila'
),

(
    'Jose',
    'Antonio',
    'Reyes',
    'jose.reyes@example.com',
    '09171234503',
    'Makati City, Metro Manila'
),

(
    'Ana',
    'Marie',
    'Bautista',
    'ana.bautista@example.com',
    '09171234504',
    'Pasig City, Metro Manila'
),

(
    'Mark',
    'Julian',
    'Villanueva',
    'mark.villanueva@example.com',
    '09171234505',
    'Taguig City, Metro Manila'
),

(
    'Kristine',
    'Faith',
    'Aquino',
    'kristine.aquino@example.com',
    '09171234506',
    'Caloocan City, Metro Manila'
),

(
    'Paolo',
    'Miguel',
    'Mendoza',
    'paolo.mendoza@example.com',
    '09171234507',
    'Marikina City, Metro Manila'
),

(
    'Angela',
    'Rose',
    'Fernandez',
    'angela.fernandez@example.com',
    '09171234508',
    'San Juan City, Metro Manila'
),

(
    'Ramon',
    'Luis',
    'Garcia',
    'ramon.garcia@example.com',
    '09171234509',
    'Mandaluyong City, Metro Manila'
),

(
    'Michelle',
    'Anne',
    'Torres',
    'michelle.torres@example.com',
    '09171234510',
    'Quezon City, Metro Manila'
);

-- applicant_id:
-- 1  = Juan Dela Cruz
-- 2  = Maria Santos
-- 3  = Jose Reyes
-- 4  = Ana Bautista
-- 5  = Mark Villanueva
-- 6  = Kristine Aquino
-- 7  = Paolo Mendoza
-- 8  = Angela Fernandez
-- 9  = Ramon Garcia
-- 10 = Michelle Torres


-- =====================================================================
-- 7. APPLICATIONS
-- =====================================================================

INSERT INTO applications (
    applicant_id,
    posting_id,
    resume_file,
    cover_letter_file,
    application_status
) VALUES

(
    1,
    1,
    'resumes/juan_delacruz_resume.pdf',
    'cover_letters/juan_delacruz_cl.pdf',
    'Hired'
),

(
    2,
    1,
    'resumes/maria_santos_resume.pdf',
    NULL,
    'Interview'
),

(
    3,
    1,
    'resumes/jose_reyes_resume.pdf',
    'cover_letters/jose_reyes_cl.pdf',
    'Under Review'
),

(
    4,
    3,
    'resumes/ana_bautista_resume.pdf',
    'cover_letters/ana_bautista_cl.pdf',
    'Hired'
),

(
    5,
    3,
    'resumes/mark_villanueva_resume.pdf',
    NULL,
    'Rejected'
),

(
    6,
    5,
    'resumes/kristine_aquino_resume.pdf',
    'cover_letters/kristine_aquino_cl.pdf',
    'Interview'
),

(
    7,
    5,
    'resumes/paolo_mendoza_resume.pdf',
    NULL,
    'Submitted'
),

(
    8,
    2,
    'resumes/angela_fernandez_resume.pdf',
    'cover_letters/angela_fernandez_cl.pdf',
    'Under Review'
),

(
    9,
    2,
    'resumes/ramon_garcia_resume.pdf',
    NULL,
    'Submitted'
),

(
    10,
    4,
    'resumes/michelle_torres_resume.pdf',
    'cover_letters/michelle_torres_cl.pdf',
    'Rejected'
);


-- =====================================================================
-- 8. AI SCREENING
-- =====================================================================
-- Applications 7 and 9 intentionally have no AI screening because
-- they are still in Submitted status.
-- =====================================================================

INSERT INTO ai_screening (
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
) VALUES

(
    1,
    92.50,
    95.00,
    90.00,
    90.00,
    95.00,
    'Highly Recommended',
    'PHP, MySQL, JavaScript, Git, REST APIs',
    JSON_ARRAY(
        'Strong backend development experience',
        'Solid understanding of relational databases',
        'Clear, well-structured resume'
    ),
    JSON_ARRAY(
        'Limited exposure to cloud deployment'
    ),
    'Candidate demonstrates strong technical alignment with the Software Developer role and relevant hands-on project experience.'
),

(
    2,
    78.00,
    80.00,
    75.00,
    80.00,
    77.00,
    'Recommended',
    'JavaScript, HTML, CSS, PHP',
    JSON_ARRAY(
        'Good front-end foundation',
        'Relevant academic projects'
    ),
    JSON_ARRAY(
        'Limited professional work experience'
    ),
    'Candidate shows promising technical skills but has comparatively less industry experience than other applicants.'
),

(
    3,
    61.00,
    60.00,
    55.00,
    70.00,
    60.00,
    'Consider',
    'HTML, CSS, basic PHP',
    JSON_ARRAY(
        'Eagerness to learn reflected in resume objective'
    ),
    JSON_ARRAY(
        'Skill set does not fully match job requirements',
        'No prior professional experience listed'
    ),
    'Candidate meets minimum educational requirements but lacks demonstrated experience in key required skills.'
),

(
    4,
    88.00,
    85.00,
    88.00,
    90.00,
    88.00,
    'Highly Recommended',
    'Recruitment, Employee Relations, HRIS, Onboarding',
    JSON_ARRAY(
        'Direct HR internship experience',
        'Strong communication skills evident in cover letter'
    ),
    JSON_ARRAY(
        'No certification in HR management yet'
    ),
    'Candidate is well-qualified for the HR Officer role with relevant internship experience and strong soft skills.'
),

(
    5,
    45.00,
    40.00,
    42.00,
    55.00,
    43.00,
    'Not Recommended',
    'Customer Service, MS Office',
    JSON_ARRAY(
        'Good communication background'
    ),
    JSON_ARRAY(
        'No HR-related coursework or experience',
        'Resume does not address job requirements'
    ),
    'Candidate profile does not align closely with the requirements of the HR Officer position.'
),

(
    6,
    82.00,
    80.00,
    82.00,
    85.00,
    81.00,
    'Recommended',
    'Social Media Management, Content Writing, Canva',
    JSON_ARRAY(
        'Active social media portfolio',
        'Relevant coursework in marketing communications'
    ),
    JSON_ARRAY(
        'Limited experience with paid advertising campaigns'
    ),
    'Candidate has solid foundational marketing skills suitable for an associate-level role.'
),

(
    8,
    74.00,
    76.00,
    68.00,
    78.00,
    74.00,
    'Recommended',
    'Figma, Adobe XD, Wireframing, Prototyping',
    JSON_ARRAY(
        'Strong design portfolio',
        'Familiarity with modern UI tools'
    ),
    JSON_ARRAY(
        'No formal UX research experience listed'
    ),
    'Candidate shows solid design fundamentals and a promising portfolio for the UI/UX Designer role.'
),

(
    10,
    38.00,
    35.00,
    30.00,
    50.00,
    37.00,
    'Not Recommended',
    'Basic Excel, Data Entry',
    JSON_ARRAY(
        'Attention to detail noted in references'
    ),
    JSON_ARRAY(
        'No accounting or finance-related background',
        'Missing key certifications for the role'
    ),
    'Candidate profile falls short of the technical and educational requirements for the Financial Analyst role.'
);


-- =====================================================================
-- 9. INTERVIEWS
-- =====================================================================
-- interviewer_id references users(user_id).
-- Admin is used as the sample interviewer.
-- =====================================================================

INSERT INTO interviews (
    application_id,
    interviewer_id,
    interview_type,
    interview_date,
    location,
    remarks,
    result
) VALUES

(
    1,
    1,
    'Face-to-Face',
    '2026-08-05 10:00:00',
    'RAM-YUM Head Office, 3F Conference Room',
    'Candidate demonstrated strong problem-solving skills and cultural fit during the technical panel interview.',
    'Passed'
),

(
    2,
    1,
    'Online',
    '2026-08-20 14:00:00',
    'Google Meet',
    'Interview scheduled to assess technical skills and team collaboration style.',
    'Pending'
),

(
    4,
    1,
    'Face-to-Face',
    '2026-08-01 09:30:00',
    'RAM-YUM Head Office, HR Interview Room',
    'Candidate articulated a clear understanding of HR processes and showed strong interpersonal skills.',
    'Passed'
),

(
    6,
    1,
    'Phone',
    '2026-08-22 11:00:00',
    'N/A',
    'Initial phone screening to evaluate communication skills before final interview round.',
    'Pending'
),

(
    10,
    1,
    'Online',
    '2026-07-15 13:00:00',
    'Google Meet',
    'Candidate was unable to answer core technical finance questions during the assessment.',
    'Failed'
);


-- =====================================================================
-- 10. ONBOARDING
-- =====================================================================
-- Final status values:
-- Pending
-- In Progress
-- Completed
-- Overdue
-- =====================================================================

INSERT INTO onboarding (
    application_id,
    orientation_date,
    started_at,
    completed_at,
    onboarding_status,
    remarks
) VALUES

(
    1,
    '2026-08-17',
    '2026-08-11',
    '2026-08-17',
    'Completed',
    'Employee has completed orientation and submitted all required documents.'
),

(
    4,
    '2026-08-10',
    '2026-08-10',
    NULL,
    'In Progress',
    'Employee attended orientation and is awaiting submission of remaining pre-employment documents.'
);

-- onboarding_id:
-- 1 = Juan Dela Cruz
-- 2 = Ana Bautista


-- =====================================================================
-- 11. ONBOARDING DOCUMENTS
-- =====================================================================

INSERT INTO onboarding_documents (
    onboarding_id,
    document_name,
    file_path,
    status
) VALUES

(
    1,
    'Government-Issued ID',
    'onboarding_docs/juan_delacruz_id.pdf',
    'Verified'
),

(
    1,
    'NBI Clearance',
    'onboarding_docs/juan_delacruz_nbi.pdf',
    'Verified'
),

(
    1,
    'SSS, PhilHealth, Pag-IBIG Numbers',
    'onboarding_docs/juan_delacruz_govnums.pdf',
    'Verified'
),

(
    1,
    'Signed Employment Contract',
    'onboarding_docs/juan_delacruz_contract.pdf',
    'Verified'
),

(
    2,
    'Government-Issued ID',
    'onboarding_docs/ana_bautista_id.pdf',
    'Submitted'
),

(
    2,
    'NBI Clearance',
    NULL,
    'Pending'
),

(
    2,
    'SSS, PhilHealth, Pag-IBIG Numbers',
    'onboarding_docs/ana_bautista_govnums.pdf',
    'Submitted'
),

(
    2,
    'Signed Employment Contract',
    'onboarding_docs/ana_bautista_contract.pdf',
    'Verified'
);


-- =====================================================================
-- 12. EMPLOYEES
-- =====================================================================
-- Department is intentionally NOT stored here.
--
-- An employee's department is determined through:
--
-- employees.position_id
--       ↓
-- positions.department_id
--       ↓
-- departments
-- =====================================================================

INSERT INTO employees (
    application_id,
    employee_number,
    position_id,
    hire_date,
    employment_status
) VALUES

(
    1,
    'EMP-2026-0001',
    1,
    '2026-08-17',
    'Probationary'
),

(
    4,
    'EMP-2026-0002',
    4,
    '2026-08-10',
    'Probationary'
);

-- employee_id:
-- 1 = Juan Dela Cruz
-- 2 = Ana Bautista


-- =====================================================================
-- 13. EMPLOYEE USER ACCOUNTS
-- =====================================================================
-- These accounts are linked to employees through users.employee_id.
--
-- Both employees are assigned the EMP system role because their
-- positions are regular employee positions.
-- =====================================================================

INSERT INTO users (
    employee_id,
    role_id,
    username,
    password,
    status,
    must_change_password
) VALUES

(
    1,
    4,
    'juan.delacruz',
    '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa',
    'Active',
    1
),

(
    2,
    4,
    'ana.bautista',
    '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa',
    'Active',
    1
);

-- user_id:
-- 2 = Juan Dela Cruz
-- 3 = Ana Bautista


-- =====================================================================
-- 14. EMPLOYEE REQUESTS
-- =====================================================================
-- Sample Employee Self-Service requests.
--
-- These requests are completely separate from onboarding documents.
-- They are created by employees after they have an employee record.
-- =====================================================================

INSERT INTO employee_requests (
    employee_id,
    request_type,
    subject,
    description,
    status,
    hr_remarks,
    requested_at,
    resolved_at,
    resolved_by
) VALUES

(
    1,
    'Certificate of Employment',
    'Request for Certificate of Employment',
    'I would like to request a Certificate of Employment for personal documentation purposes.',
    'Completed',
    'Certificate prepared and released to the employee.',
    '2026-08-18 09:15:00',
    '2026-08-18 14:30:00',
    1
),

(
    1,
    'Profile Update',
    'Update Contact Information',
    'I would like to update my contact information in my employee record.',
    'Pending',
    NULL,
    '2026-08-19 10:20:00',
    NULL,
    NULL
),

(
    2,
    'Document Request',
    'Request for Employment Contract Copy',
    'I would like to request a digital copy of my signed employment contract.',
    'Approved',
    'Request approved. HR will prepare the requested document.',
    '2026-08-18 13:45:00',
    NULL,
    1
),

(
    2,
    'Employment Concern',
    'Question Regarding Employment Status',
    'I would like clarification regarding my current probationary employment status.',
    'Pending',
    NULL,
    '2026-08-19 08:40:00',
    NULL,
    NULL
);


-- =====================================================================
-- END OF SEED DATA
-- =====================================================================