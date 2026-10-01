-- NOTE: no CREATE DATABASE / USE here. Select your database in phpMyAdmin first, then import.
-- =====================================================================
-- RAM-YUM HRMS (hr1_db) — Seed Data
-- =====================================================================
-- Purpose : Sample data for local development and demos of the
--           Recruitment, Applicant, Onboarding, Core HCM,
--           and Employee Self-Service modules.
--
-- IMPORTANT:
-- This seed assumes the FINAL hr1_db schema.
-- Import this entire file after creating the empty hr1_db database.
--
-- Dependency order (corrected):
-- roles
--   -> users (admin)
--   -> departments
--   -> positions
--   -> job_postings
--   -> applicants
--   -> applications
--   -> ai_screening
--   -> interviews
--   -> onboarding
--   -> employees
--   -> employee users
--   -> employee_requests
--   -> additional applicants / applications / screening / interviews
--   -> additional onboarding / employees / employee users
--   -> onboarding_documents   (needs employees + users)
--   -> academic_document_evaluations
--   -> onboarding_phases
--   -> activity_logs
--
-- NOTE:
-- users.employee_id is nullable because system users such as Admin
-- may not belong to an employee record.
-- =====================================================================



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
-- admin123
--
-- The password below is a placeholder bcrypt hash.
-- Replace it with password_hash() output for production use.
--
-- NOTE: users.email is NOT NULL / UNIQUE in the schema, so every
-- users insert below supplies one.
-- =====================================================================

INSERT INTO users (
    employee_id,
    role_id,
    username,
    email,
    password,
    status,
    must_change_password
) VALUES (
    NULL,
    1,
    'admin',
    'mariodejarlomabinijr@gmail.com',
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
    academic_document_required,
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
    TRUE,
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
    TRUE,
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
    TRUE,
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
    TRUE,
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
    TRUE,
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
    TRUE,
    'Contract',
    1,
    'Closed',
    'f6a4b8439d7f8a6c2b8f1e5d0a7c4b66',
    '2026-06-30',
    1
),

(
    5,
    'HR Manager',
    'Lead the HR team and oversee recruitment, employee relations, and labor compliance.',
    'BS in Psychology, HR Management, or related field; at least 5 years of HR generalist experience; leadership and labor law knowledge.',
    TRUE,
    'Full-Time',
    1,
    'Closed',
    'a7c3d9540e8a9b7d3c9a2f6e1b8d5c77',
    '2026-08-15',
    1
);

-- posting_id:
-- 1 = Software Developer
-- 2 = UI/UX Designer
-- 3 = HR Officer
-- 4 = Financial Analyst
-- 5 = Marketing Associate
-- 6 = Operations Coordinator
-- 7 = HR Manager


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
    'juan.delacruz@gmail.com',
    '09171234501',
    'Quezon City, Metro Manila'
),

(
    'Maria',
    'Lopez',
    'Santos',
    'maria.santos@gmail.com',
    '09171234502',
    'Manila, Metro Manila'
),

(
    'Jose',
    'Antonio',
    'Reyes',
    'jose.reyes@gmail.com',
    '09171234503',
    'Makati City, Metro Manila'
),

(
    'Ana',
    'Marie',
    'Bautista',
    'ana.bautista@gmail.com',
    '09171234504',
    'Pasig City, Metro Manila'
),

(
    'Mark',
    'Julian',
    'Villanueva',
    'mark.villanueva@gmail.com',
    '09171234505',
    'Taguig City, Metro Manila'
),

(
    'Kristine',
    'Faith',
    'Aquino',
    'kristine.aquino@gmail.com',
    '09171234506',
    'Caloocan City, Metro Manila'
),

(
    'Paolo',
    'Miguel',
    'Mendoza',
    'paolo.mendoza@gmail.com',
    '09171234507',
    'Marikina City, Metro Manila'
),

(
    'Angela',
    'Rose',
    'Fernandez',
    'angela.fernandez@gmail.com',
    '09171234508',
    'San Juan City, Metro Manila'
),

(
    'Ramon',
    'Luis',
    'Garcia',
    'ramon.garcia@gmail.com',
    '09171234509',
    'Mandaluyong City, Metro Manila'
),

(
    'Michelle',
    'Anne',
    'Torres',
    'michelle.torres@gmail.com',
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
    academic_document_file,
    application_status
) VALUES

(
    1,
    1,
    'resumes/juan_delacruz_resume.pdf',
    'cover_letters/juan_delacruz_cl.pdf',
    'academic/juan_delacruz_diploma.pdf',
    'Hired'
),

(
    2,
    1,
    'resumes/maria_santos_resume.pdf',
    NULL,
    'academic/maria_santos_diploma.pdf',
    'Interview'
),

(
    3,
    1,
    'resumes/jose_reyes_resume.pdf',
    'cover_letters/jose_reyes_cl.pdf',
    NULL,
    'Under Review'
),

(
    4,
    3,
    'resumes/ana_bautista_resume.pdf',
    'cover_letters/ana_bautista_cl.pdf',
    'academic/ana_bautista_diploma.pdf',
    'Hired'
),

(
    5,
    3,
    'resumes/mark_villanueva_resume.pdf',
    NULL,
    NULL,
    'Rejected'
),

(
    6,
    5,
    'resumes/kristine_aquino_resume.pdf',
    'cover_letters/kristine_aquino_cl.pdf',
    'academic/kristine_aquino_diploma.pdf',
    'Interview'
),

(
    7,
    5,
    'resumes/paolo_mendoza_resume.pdf',
    NULL,
    NULL,
    'Submitted'
),

(
    8,
    2,
    'resumes/angela_fernandez_resume.pdf',
    'cover_letters/angela_fernandez_cl.pdf',
    'academic/angela_fernandez_diploma.pdf',
    'Under Review'
),

(
    9,
    2,
    'resumes/ramon_garcia_resume.pdf',
    NULL,
    NULL,
    'Submitted'
),

(
    10,
    4,
    'resumes/michelle_torres_resume.pdf',
    'cover_letters/michelle_torres_cl.pdf',
    'academic/michelle_torres_diploma.pdf',
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
--
-- NOTE: the four *_completed booleans are NOT NULL columns on
-- onboarding, so they're set explicitly here to stay consistent with
-- onboarding_status/remarks instead of silently defaulting to FALSE.
-- =====================================================================

INSERT INTO onboarding (
    application_id,
    orientation_date,
    started_at,
    completed_at,
    onboarding_status,
    system_access_setup_completed,
    orientation_setup_completed,
    document_submission_completed,
    document_verification_completed,
    remarks
) VALUES

(
    1,
    '2026-08-17',
    '2026-08-11',
    '2026-08-17',
    'Completed',
    TRUE,
    TRUE,
    TRUE,
    TRUE,
    'Employee has completed orientation and submitted all required documents.'
),

(
    4,
    '2026-08-10',
    '2026-08-10',
    NULL,
    'In Progress',
    TRUE,
    TRUE,
    FALSE,
    FALSE,
    'Employee attended orientation and is awaiting submission of remaining pre-employment documents.'
);

-- onboarding_id:
-- 1 = Juan Dela Cruz
-- 2 = Ana Bautista


-- =====================================================================
-- 11. EMPLOYEES
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
--
-- NOTE: employees must exist BEFORE onboarding_documents is seeded,
-- because onboarding_documents.employee_id references employees.
-- =====================================================================

INSERT INTO employees (
    application_id,
    applicant_id,
    employee_number,
    position_id,
    hire_date,
    employment_status
) VALUES

(
    1,
    1,
    'EMP-2026-0001',
    1,
    '2026-08-17',
    'Probationary'
),

(
    4,
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
-- 12. EMPLOYEE USER ACCOUNTS
-- =====================================================================
-- These accounts are linked to employees through users.employee_id.
--
-- Both employees are assigned the EMP system role because their
-- positions are regular employee positions.
--
-- NOTE: email added to satisfy the NOT NULL / UNIQUE constraint on
-- users.email — reusing each person's applicant email for realism.
-- =====================================================================

INSERT INTO users (
    employee_id,
    role_id,
    username,
    email,
    password,
    status,
    must_change_password
) VALUES

(
    1,
    4,
    'juan.delacruz',
    'juan.delacruz@gmail.com',
    '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa',
    'Active',
    1
),

(
    2,
    4,
    'ana.bautista',
    'ana.bautista@gmail.com',
    '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa',
    'Active',
    1
);

-- user_id:
-- 2 = Juan Dela Cruz
-- 3 = Ana Bautista


-- =====================================================================
-- 13. EMPLOYEE REQUESTS
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
-- 14. ADDITIONAL APPLICANTS
-- =====================================================================

INSERT INTO applicants (
    first_name,
    middle_name,
    last_name,
    email,
    phone,
    address
) VALUES
('Leonardo', 'Miguel', 'Navarro', 'leonardo.navarro@gmail.com', '09171234511', 'Paranaque City, Metro Manila'),
('Sofia', 'Isabel', 'Cabrera', 'sofia.cabrera@gmail.com', '09171234512', 'Muntinlupa City, Metro Manila'),
('Daniel', 'Rafael', 'Mercado', 'daniel.mercado@gmail.com', '09171234513', 'Las Pinas City, Metro Manila'),
('Beatrice', 'Claire', 'Ramos', 'beatrice.ramos@gmail.com', '09171234514', 'Pasay City, Metro Manila'),
('Gabriel', 'Luis', 'Castillo', 'gabriel.castillo@gmail.com', '09171234515', 'Valenzuela City, Metro Manila'),
('Camille', 'Joy', 'Domingo', 'camille.domingo@gmail.com', '09171234516', 'Bacoor, Cavite'),
('Nathan', 'Paul', 'Manalo', 'nathan.manalo@gmail.com', '09171234517', 'Antipolo City, Rizal'),
('Elena', 'Grace', 'Rivera', 'elena.rivera@gmail.com', '09171234518', 'Imus, Cavite'),
('Victor', 'Andrei', 'Salazar', 'victor.salazar@gmail.com', '09171234519', 'Cainta, Rizal'),
('Patricia', 'Mae', 'Soriano', 'patricia.soriano@gmail.com', '09171234520', 'Meycauayan, Bulacan');

-- applicant_id 11-20 are Leonardo Navarro through Patricia Soriano.


-- =====================================================================
-- 15. ADDITIONAL APPLICATIONS
-- =====================================================================

INSERT INTO applications (
    applicant_id,
    posting_id,
    resume_file,
    cover_letter_file,
    academic_document_file,
    application_status
) VALUES
(11, 2, 'resumes/leonardo_navarro_resume.pdf', 'cover_letters/leonardo_navarro_cl.pdf', 'academic/leonardo_navarro_diploma.pdf', 'Hired'),
(12, 5, 'resumes/sofia_cabrera_resume.pdf', 'cover_letters/sofia_cabrera_cl.pdf', 'academic/sofia_cabrera_diploma.pdf', 'Hired'),
(13, 6, 'resumes/daniel_mercado_resume.pdf', NULL, 'academic/daniel_mercado_diploma.pdf', 'Interview'),
(14, 1, 'resumes/beatrice_ramos_resume.pdf', 'cover_letters/beatrice_ramos_cl.pdf', 'academic/beatrice_ramos_diploma.pdf', 'Under Review'),
(15, 3, 'resumes/gabriel_castillo_resume.pdf', 'cover_letters/gabriel_castillo_cl.pdf', 'academic/gabriel_castillo_diploma.pdf', 'Interview'),
(16, 4, 'resumes/camille_domingo_resume.pdf', NULL, 'academic/camille_domingo_diploma.pdf', 'Rejected'),
(17, 5, 'resumes/nathan_manalo_resume.pdf', NULL, NULL, 'Submitted'),
(18, 1, 'resumes/elena_rivera_resume.pdf', 'cover_letters/elena_rivera_cl.pdf', 'academic/elena_rivera_diploma.pdf', 'Interview'),
(19, 7, 'resumes/victor_salazar_resume.pdf', 'cover_letters/victor_salazar_cl.pdf', 'academic/victor_salazar_diploma.pdf', 'Hired'),
(20, 5, 'resumes/patricia_soriano_resume.pdf', NULL, 'academic/patricia_soriano_diploma.pdf', 'Under Review');

-- application_id 11-20 follow the applicant order above.


-- =====================================================================
-- 16. ADDITIONAL AI SCREENING RESULTS
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
(11, 89.00, 90.00, 86.00, 92.00, 88.00, 'Highly Recommended', 'Figma, Design Systems, User Research, Prototyping', JSON_ARRAY('Strong portfolio with accessible interfaces', 'Experience collaborating with developers'), JSON_ARRAY('Limited experience in enterprise applications'), 'Candidate has the portfolio quality and collaboration skills expected for the UI/UX Designer role.'),
(12, 86.00, 84.00, 88.00, 85.00, 87.00, 'Highly Recommended', 'Campaign Planning, SEO, Analytics, Copywriting', JSON_ARRAY('Managed measurable digital campaigns', 'Strong written communication'), JSON_ARRAY('Needs more exposure to offline events'), 'Candidate has a strong marketing background and can contribute quickly to campaign execution.'),
(13, 70.00, 72.00, 65.00, 75.00, 68.00, 'Consider', 'Excel, Budgeting, Forecasting, Power BI', JSON_ARRAY('Relevant financial modeling coursework'), JSON_ARRAY('Limited full-time finance experience'), 'Candidate has useful analytical foundations but should be assessed further in the technical interview.'),
(14, 83.00, 86.00, 78.00, 82.00, 84.00, 'Recommended', 'PHP, Laravel, MySQL, REST APIs, Git', JSON_ARRAY('Relevant web application projects', 'Good database fundamentals'), JSON_ARRAY('Limited production deployment experience'), 'Candidate appears well aligned with the development role and merits technical review.'),
(15, 80.00, 78.00, 81.00, 80.00, 79.00, 'Recommended', 'Recruitment, HRIS, Policy Writing, Employee Relations', JSON_ARRAY('Hands-on HR operations experience', 'Clear understanding of onboarding workflows'), JSON_ARRAY('No professional HR certification listed'), 'Candidate demonstrates practical HR experience suitable for the HR Officer opening.'),
(16, 42.00, 45.00, 38.00, 48.00, 40.00, 'Not Recommended', 'Bookkeeping, Data Entry, MS Excel', JSON_ARRAY('Basic spreadsheet proficiency'), JSON_ARRAY('No relevant degree listed', 'Limited financial analysis experience'), 'Candidate does not currently meet the academic and technical requirements for the Financial Analyst role.'),
(18, 76.00, 80.00, 70.00, 78.00, 75.00, 'Recommended', 'PHP, JavaScript, Vue, MySQL', JSON_ARRAY('Strong coding assessment sample'), JSON_ARRAY('Short commercial experience'), 'Candidate shows good technical promise and should proceed through the interview process.'),
(19, 91.00, 88.00, 92.00, 90.00, 93.00, 'Highly Recommended', 'Employee Relations, HRIS, Labor Compliance, Coaching', JSON_ARRAY('Extensive HR generalist experience', 'Strong stakeholder management'), JSON_ARRAY('Salary expectations require confirmation'), 'Candidate is highly aligned with the HR Manager role and has relevant leadership experience.'),
(20, 73.00, 75.00, 70.00, 74.00, 72.00, 'Recommended', 'Social Media, Canva, Content Planning, Community Management', JSON_ARRAY('Consistent content portfolio'), JSON_ARRAY('Limited reporting and analytics experience'), 'Candidate has a promising creative profile for the Marketing Associate role.');


-- =====================================================================
-- 17. ADDITIONAL INTERVIEWS
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
(11, 1, 'Online', '2026-08-25 10:00:00', 'Google Meet', 'Portfolio review completed; candidate explained design decisions clearly.', 'Passed'),
(12, 1, 'Face-to-Face', '2026-08-26 13:30:00', 'RAM-YUM Head Office, Marketing Room', 'Candidate presented a practical campaign plan for a new service launch.', 'Passed'),
(13, 1, 'Online', '2026-09-08 09:00:00', 'Google Meet', 'Technical interview scheduled for financial modeling and reporting skills.', 'Pending'),
(15, 1, 'Face-to-Face', '2026-08-27 11:00:00', 'RAM-YUM Head Office, HR Interview Room', 'Candidate discussed employee relations cases and HR policy implementation.', 'Passed'),
(18, 1, 'Phone', '2026-09-09 15:00:00', 'N/A', 'Initial technical screening scheduled.', 'Pending'),
(19, 1, 'Online', '2026-08-28 14:00:00', 'Google Meet', 'Candidate demonstrated strong HR leadership and compliance knowledge.', 'Passed');


-- =====================================================================
-- 18. ADDITIONAL ONBOARDING RECORDS
-- =====================================================================

INSERT INTO onboarding (
    application_id,
    orientation_date,
    started_at,
    completed_at,
    onboarding_status,
    system_access_setup_completed,
    orientation_setup_completed,
    document_submission_completed,
    document_verification_completed,
    remarks
) VALUES
(11, '2026-09-01', '2026-08-31', '2026-09-01', 'Completed', TRUE, TRUE, TRUE, TRUE, 'Designer completed orientation and all document checks.'),
(12, '2026-09-02', '2026-09-01', NULL, 'In Progress', TRUE, TRUE, TRUE, FALSE, 'Marketing employee is awaiting final HR verification.'),
(19, '2026-09-05', '2026-09-04', NULL, 'In Progress', TRUE, TRUE, FALSE, FALSE, 'HR Manager has completed orientation and is submitting remaining documents.');

-- onboarding_id 3 = Leonardo Navarro, 4 = Sofia Cabrera, 5 = Victor Salazar.


-- =====================================================================
-- 19. ADDITIONAL EMPLOYEES
-- =====================================================================

INSERT INTO employees (
    application_id,
    applicant_id,
    employee_number,
    position_id,
    hire_date,
    employment_status
) VALUES
(11, 11, 'EMP-2026-0003', 3, '2026-09-01', 'Probationary'),
(12, 12, 'EMP-2026-0004', 7, '2026-09-02', 'Probationary'),
(19, 19, 'EMP-2026-0005', 5, '2026-09-05', 'Regular');

-- employee_id 3 = Leonardo Navarro, 4 = Sofia Cabrera, 5 = Victor Salazar.

UPDATE employee_number_sequence
SET next_number = 6
WHERE sequence_id = 1;


-- =====================================================================
-- 20. ADDITIONAL EMPLOYEE USER ACCOUNTS
-- =====================================================================
-- NOTE: email added for the same reason as section 12 above.
-- =====================================================================

INSERT INTO users (
    employee_id,
    role_id,
    username,
    email,
    password,
    status,
    must_change_password
) VALUES
(3, 4, 'leonardo.navarro', 'leonardo.navarro@gmail.com', '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 1),
(4, 4, 'sofia.cabrera', 'sofia.cabrera@gmail.com', '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 1),
(5, 2, 'victor.salazar', 'victor.salazar@gmail.com', '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0);

-- user_id:
-- 4 = Leonardo Navarro
-- 5 = Sofia Cabrera
-- 6 = Victor Salazar (HR Staff)


-- =====================================================================
-- 21. ONBOARDING DOCUMENTS
-- =====================================================================
-- MOVED HERE from earlier in the file. This table has foreign keys to:
--   employees (employee_id)  -> seeded in sections 11 and 19
--   users (assigned_by)      -> user_id 6 is created in section 20
-- so it can only be seeded after both exist.
--
-- Part A: rows for the "Assigned Requests" flow
--   assigned_by = 6 (HR Staff), action_type = 'document'
-- =====================================================================

INSERT INTO onboarding_documents (
    onboarding_id,
    employee_id,
    document_name,
    file_path,
    status,
    request_type,
    assigned_by,
    action_type,
    priority,
    created_at,
    due_date
) VALUES

(
    1, 1, 'Government-Issued ID', 'onboarding_document_1.pdf', 'Verified',
    'Submit Required Document', 6, 'document', 'Normal', '2026-08-17 09:00:00', '2026-08-31'
),

(
    1, 1, 'NBI Clearance', 'onboarding_document_2.pdf', 'Verified',
    'Submit Missing Requirement', 6, 'document', 'Normal', '2026-08-17 09:00:00', '2026-08-31'
),

(
    1, 1, 'SSS, PhilHealth, Pag-IBIG Numbers', 'onboarding_document_3.pdf', 'Verified',
    'Submit Additional Employment Document', 6, 'document', 'Normal', '2026-08-17 09:00:00', '2026-08-31'
),

(
    1, 1, 'Signed Employment Contract', 'onboarding_document_4.pdf', 'Verified',
    'Submit Required Document', 6, 'document', 'Normal', '2026-08-17 09:00:00', '2026-08-31'
),

(
    2, 2, 'Government-Issued ID', 'onboarding_document_5.pdf', 'Submitted',
    'Submit Required Document', 6, 'document', 'High', '2026-08-10 09:00:00', '2026-08-31'
),

(
    2, 2, 'NBI Clearance', NULL, 'Pending',
    'Submit Missing Requirement', 6, 'document', 'Urgent', '2026-08-10 09:00:00', '2026-08-31'
),

(
    2, 2, 'SSS, PhilHealth, Pag-IBIG Numbers', 'onboarding_document_7.pdf', 'Submitted',
    'Submit Additional Employment Document', 6, 'document', 'High', '2026-08-10 09:00:00', '2026-08-31'
),

(
    2, 2, 'Signed Employment Contract', 'onboarding_document_8.pdf', 'Verified',
    'Submit Required Document', 6, 'document', 'Normal', '2026-08-10 09:00:00', '2026-08-24'
);

-- Part B: additional onboarding documents (onboarding_id 3-5)
-- employee_id is set so these rows are linked to their employees
-- (3 = Leonardo, 4 = Sofia, 5 = Victor), same as Part A.

INSERT INTO onboarding_documents (
    onboarding_id,
    employee_id,
    document_name,
    file_path,
    status
) VALUES
(3, 3, 'Government-Issued ID', 'onboarding_docs/leonardo_navarro_id.pdf', 'Verified'),
(3, 3, 'NBI Clearance', 'onboarding_docs/leonardo_navarro_nbi.pdf', 'Verified'),
(3, 3, 'Signed Employment Contract', 'onboarding_docs/leonardo_navarro_contract.pdf', 'Verified'),
(4, 4, 'Government-Issued ID', 'onboarding_docs/sofia_cabrera_id.pdf', 'Verified'),
(4, 4, 'NBI Clearance', 'onboarding_docs/sofia_cabrera_nbi.pdf', 'Submitted'),
(4, 4, 'Signed Employment Contract', 'onboarding_docs/sofia_cabrera_contract.pdf', 'Verified'),
(5, 5, 'Government-Issued ID', 'onboarding_docs/victor_salazar_id.pdf', 'Submitted'),
(5, 5, 'NBI Clearance', NULL, 'Pending'),
(5, 5, 'Signed Employment Contract', 'onboarding_docs/victor_salazar_contract.pdf', 'Submitted');


-- =====================================================================
-- 22. ACADEMIC DOCUMENT EVALUATIONS
-- =====================================================================
-- Table name: academic_document_evaluations (the name the PHP models use).
--
-- file_name and original_file_name are NOT NULL columns, so every row
-- supplies both, plus is_primary / evaluation_status / processed_at
-- for records that represent a completed evaluation.
-- =====================================================================

INSERT INTO academic_document_evaluations (
    application_id,
    file_name,
    original_file_name,
    is_primary,
    evaluation_status,
    document_valid,
    confidence_score,
    document_type,
    institution,
    degree,
    field_of_study,
    graduation_year,
    extracted_details,
    concerns,
    ai_summary,
    processed_at
) VALUES
(1, 'juan_delacruz_diploma.pdf', 'Juan Dela Cruz - Diploma.pdf', TRUE, 'Evaluated', TRUE, 98.00, 'Diploma', 'Polytechnic University of the Philippines', 'Bachelor of Science', 'Computer Science', '2022', JSON_OBJECT('honors', 'Cum Laude'), JSON_ARRAY(), 'Document appears authentic and supports the academic requirement.', '2026-07-28 10:00:00'),
(4, 'ana_bautista_diploma.pdf', 'Ana Bautista - Diploma.pdf', TRUE, 'Evaluated', TRUE, 96.00, 'Diploma', 'University of Santo Tomas', 'Bachelor of Science', 'Psychology', '2021', JSON_OBJECT('major', 'Industrial Psychology'), JSON_ARRAY(), 'Document is valid and relevant to the HR Officer position.', '2026-07-25 10:00:00'),
(8, 'angela_fernandez_diploma.pdf', 'Angela Fernandez - Diploma.pdf', TRUE, 'Evaluated', TRUE, 94.00, 'Diploma', 'De La Salle-College of Saint Benilde', 'Bachelor of Arts', 'Multimedia Arts', '2023', JSON_OBJECT('portfolio_reference', 'Included'), JSON_ARRAY('Degree title differs from the preferred design degree'), 'Document is valid and the field is closely related to the role.', '2026-08-05 10:00:00'),
(10, 'michelle_torres_diploma.pdf', 'Michelle Torres - Certificate.pdf', TRUE, 'Evaluated', FALSE, 91.00, 'Certificate of Completion', 'Private Training Institute', NULL, 'Office Administration', '2020', JSON_OBJECT(), JSON_ARRAY('Submitted document is not an acceptable academic credential'), 'Document could not be accepted as evidence of the required finance degree.', '2026-07-10 10:00:00'),
(11, 'leonardo_navarro_diploma.pdf', 'Leonardo Navarro - Diploma.pdf', TRUE, 'Evaluated', TRUE, 97.00, 'Diploma', 'University of the Philippines Diliman', 'Bachelor of Fine Arts', 'Industrial Design', '2022', JSON_OBJECT('honors', 'Dean''s Lister'), JSON_ARRAY(), 'Document is valid and supports the applicant''s design background.', '2026-08-22 10:00:00'),
(12, 'sofia_cabrera_diploma.pdf', 'Sofia Cabrera - Diploma.pdf', TRUE, 'Evaluated', TRUE, 95.00, 'Diploma', 'Miriam College', 'Bachelor of Arts', 'Communication', '2021', JSON_OBJECT(), JSON_ARRAY(), 'Document is valid and relevant to the marketing position.', '2026-08-23 10:00:00'),
(19, 'victor_salazar_diploma.pdf', 'Victor Salazar - Diploma.pdf', TRUE, 'Evaluated', TRUE, 99.00, 'Diploma', 'Ateneo de Manila University', 'Bachelor of Science', 'Psychology', '2018', JSON_OBJECT('certification', 'HR Certification Institute'), JSON_ARRAY(), 'Document is valid and supports the candidate''s HR leadership profile.', '2026-08-24 10:00:00');


-- =====================================================================
-- 23. ONBOARDING PHASES
-- =====================================================================

INSERT INTO onboarding_phases (
    onboarding_id,
    phase_number,
    phase_name,
    phase_description,
    status,
    completed_at
) VALUES
(1, 1, 'System & Access Setup', 'Employee account and access are created.', 'completed', '2026-08-11 09:00:00'),
(1, 2, 'Orientation & Setup', 'Employee attends the orientation session.', 'completed', '2026-08-12 09:00:00'),
(1, 3, 'Document Submission', 'Employee submits all required documents.', 'completed', '2026-08-15 09:00:00'),
(1, 4, 'Document Verification', 'HR verifies submitted documents.', 'completed', '2026-08-16 09:00:00'),
(1, 5, 'Final Onboarding Completion', 'System confirms all onboarding requirements.', 'completed', '2026-08-17 09:00:00'),
(2, 1, 'System & Access Setup', 'Employee account and access are created.', 'completed', '2026-08-10 09:00:00'),
(2, 2, 'Orientation & Setup', 'Employee attends the orientation session.', 'completed', '2026-08-10 13:00:00'),
(2, 3, 'Document Submission', 'Employee submits all required documents.', 'in-progress', NULL),
(2, 4, 'Document Verification', 'HR verifies submitted documents.', 'pending', NULL),
(2, 5, 'Final Onboarding Completion', 'System confirms all onboarding requirements.', 'pending', NULL),
(3, 1, 'System & Access Setup', 'Employee account and access are created.', 'completed', '2026-08-31 09:00:00'),
(3, 2, 'Orientation & Setup', 'Employee attends the orientation session.', 'completed', '2026-09-01 09:00:00'),
(3, 3, 'Document Submission', 'Employee submits all required documents.', 'completed', '2026-09-01 15:00:00'),
(3, 4, 'Document Verification', 'HR verifies submitted documents.', 'completed', '2026-09-01 16:00:00'),
(3, 5, 'Final Onboarding Completion', 'System confirms all onboarding requirements.', 'completed', '2026-09-01 17:00:00'),
(4, 1, 'System & Access Setup', 'Employee account and access are created.', 'completed', '2026-09-01 09:00:00'),
(4, 2, 'Orientation & Setup', 'Employee attends the orientation session.', 'completed', '2026-09-02 09:00:00'),
(4, 3, 'Document Submission', 'Employee submits all required documents.', 'completed', '2026-09-03 10:00:00'),
(4, 4, 'Document Verification', 'HR verifies submitted documents.', 'in-progress', NULL),
(4, 5, 'Final Onboarding Completion', 'System confirms all onboarding requirements.', 'pending', NULL),
(5, 1, 'System & Access Setup', 'Employee account and access are created.', 'completed', '2026-09-04 09:00:00'),
(5, 2, 'Orientation & Setup', 'Employee attends the orientation session.', 'completed', '2026-09-05 09:00:00'),
(5, 3, 'Document Submission', 'Employee submits all required documents.', 'in-progress', NULL),
(5, 4, 'Document Verification', 'HR verifies submitted documents.', 'pending', NULL),
(5, 5, 'Final Onboarding Completion', 'System confirms all onboarding requirements.', 'pending', NULL);


-- =====================================================================
-- 24. ACTIVITY LOGS
-- =====================================================================

INSERT INTO activity_logs (
    user_id,
    employee_id,
    action,
    page,
    request_method,
    ip_address,
    user_agent
) VALUES
(1, NULL, 'Viewed recruitment dashboard', '/recruitment', 'GET', '127.0.0.1', 'Seeded demo activity'),
(1, NULL, 'Reviewed applicant profile', '/applicants/review', 'GET', '127.0.0.1', 'Seeded demo activity'),
(2, 1, 'Viewed employee dashboard', '/dashboard/employee', 'GET', '127.0.0.1', 'Seeded demo activity'),
(2, 1, 'Submitted profile update request', '/employee-requests', 'POST', '127.0.0.1', 'Seeded demo activity'),
(3, 2, 'Viewed employee dashboard', '/dashboard/employee', 'GET', '127.0.0.1', 'Seeded demo activity'),
(4, 3, 'Completed first login', '/login', 'POST', '127.0.0.1', 'Seeded demo activity'),
(5, 4, 'Viewed employee records', '/employee-records', 'GET', '127.0.0.1', 'Seeded demo activity'),
(6, 5, 'Viewed onboarding status', '/onboarding', 'GET', '127.0.0.1', 'Seeded demo activity');


-- =====================================================================
-- END OF SEED DATA
-- =====================================================================