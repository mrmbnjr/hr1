-- NOTE: no CREATE DATABASE / USE here. Select your database in phpMyAdmin first, then import.
-- =====================================================================
-- RAM-YUM HRMS (hr1_db) — Seed Data
-- =====================================================================
-- Purpose : Realistic sample data for local development and demos of the
--           Recruitment, Applicant, Onboarding, Core HCM,
--           and Employee Self-Service modules.
--
-- IMPORTANT
-- ---------------------------------------------------------------------
-- This seed assumes the FINAL hr1_db schema (schema.sql) and is designed
-- to be imported into an EMPTY database, right after schema.sql.
--
-- All dates are RELATIVE to the moment of import (NOW()), so the demo
-- always looks current no matter when it is run:
--   * Open postings always have a future deadline.
--   * Applications span the last few months (the dashboard chart needs
--     more than one month of data).
--   * Interviews for pending candidates are scheduled in the near future.
--   * Rejected applications keep a rejected_at inside the 30-day window,
--     so they stay visible in the applicant list.
--   * Closed postings are closed recently enough that their non-hired
--     applications have not been auto-purged yet (see ApplicationRetentionService).
--
-- Shared password for every seeded account: admin123
--   (bcrypt hash below; change it after the first login).
--
-- Dependency order:
--   roles
--     -> users (admin)
--     -> departments
--     -> positions
--     -> job_postings
--     -> applicants
--     -> applications
--     -> employees            (needs applications + applicants + positions)
--     -> employee users       (need employees)
--     -> ai_screening
--     -> interviews           (needs users)
--     -> onboarding
--     -> onboarding_documents (needs onboarding + employees + users)
--     -> academic_document_evaluations
--     -> onboarding_phases
--     -> employee_requests
--     -> activity_logs
--     -> application_validations
--     -> password_reset_requests
-- =====================================================================

-- Import-time anchor. Every relative date below hangs off this value.
SET @now := NOW();


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
-- Test password for every sample account: admin123
-- (placeholder bcrypt hash — replace with password_hash() output for
-- production use).
--
-- users.email is NOT NULL / UNIQUE, so every insert supplies one.
-- =====================================================================

INSERT INTO users (
    employee_id,
    role_id,
    username,
    email,
    email_verified_at,
    password,
    status,
    must_change_password,
    created_at
) VALUES (
    NULL,
    1,
    'admin',
    'mariodejarlomabinijr@gmail.com',
    DATE_SUB(@now, INTERVAL 300 DAY),
    '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa',
    'Active',
    0,
    DATE_SUB(@now, INTERVAL 300 DAY)
);

-- user_id:
-- 1 = admin


-- =====================================================================
-- 3. DEPARTMENTS
-- =====================================================================
-- status is shown by the Department overview, so the demo includes one
-- Inactive department (no positions, no employees) to exercise the badge.

INSERT INTO departments (
    department_name,
    status
) VALUES
('Information Technology', 'Active'),
('Human Resources',        'Active'),
('Finance',                'Active'),
('Marketing',              'Active'),
('Operations',             'Active'),
('Legal & Compliance',     'Inactive');

-- department_id:
-- 1 = Information Technology
-- 2 = Human Resources
-- 3 = Finance
-- 4 = Marketing
-- 5 = Operations
-- 6 = Legal & Compliance (Inactive)


-- =====================================================================
-- 4. POSITIONS
-- =====================================================================
-- role_id is the intended SYSTEM ROLE for the position.
--   EMP  = Employee
--   MGR  = Manager
--   HR   = HR Staff
-- ADMIN positions are not recruited as normal positions.
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

(5, 4, 'Operations Coordinator'),

-- The next five positions have NO job posting yet. A position can only
-- have one posting (schema: uq_job_postings_position), so leaving a few
-- unposted keeps the "Create Job" screen usable in the demo.
(1, 4, 'QA Engineer'),
(2, 4, 'Recruitment Specialist'),
(3, 4, 'Accounting Clerk'),
(4, 4, 'Content Specialist'),
(5, 3, 'Operations Supervisor');

-- position_id:
-- 1  = Software Developer       6  = Financial Analyst      11 = Recruitment Specialist
-- 2  = IT Manager               7  = Marketing Associate    12 = Accounting Clerk
-- 3  = UI/UX Designer           8  = Marketing Manager      13 = Content Specialist
-- 4  = HR Officer               9  = Operations Coordinator 14 = Operations Supervisor
-- 5  = HR Manager               10 = QA Engineer


-- =====================================================================
-- 5. JOB POSTINGS
-- =====================================================================
-- One posting per position (schema: uq_job_postings_position).
--
-- Open postings get a FUTURE deadline and no closed_at.
-- Closed postings get a past deadline and a closed_at. The two older
-- closed postings (2 and 5) only ever hold Hired applications, which the
-- retention service never removes; posting 8 is an old cancelled role
-- with no applications.
--
-- created_by references users(user_id); admin (user 1) is the only system
-- user available at this point.
-- =====================================================================

INSERT INTO job_postings (
    position_id,
    title,
    description,
    requirements,
    academic_document_required,
    employment_type,
    vacancies,
    max_applicants,
    status,
    closed_at,
    application_token,
    application_deadline,
    created_by,
    created_at
) VALUES

(
    1,
    'Software Developer',
    'Develop, test, and maintain web-based applications for internal HR systems.',
    'BS in Computer Science or related field; proficiency in PHP, JavaScript, and MySQL; at least 1 year of experience building web applications.',
    TRUE,
    'Full-Time',
    2,
    40,
    'Open',
    NULL,
    'a1f9c3d84e2b4a1f9d3c7e6b0a5f2c11',
    DATE_ADD(CURDATE(), INTERVAL 12 DAY),
    1,
    DATE_SUB(@now, INTERVAL 75 DAY)
),

(
    2,
    'IT Manager',
    'Lead the IT department, manage infrastructure projects, and mentor the development team.',
    'BS in Computer Science or related field; at least 5 years of experience including 2 in a supervisory role; strong systems and people management skills.',
    TRUE,
    'Full-Time',
    1,
    15,
    'Closed',
    DATE_SUB(@now, INTERVAL 130 DAY),
    'b2e8d4c95f3a4b2e8c4d6f7a1b6e3d22',
    DATE_SUB(CURDATE(), INTERVAL 135 DAY),
    1,
    DATE_SUB(@now, INTERVAL 150 DAY)
),

(
    3,
    'UI/UX Designer',
    'Design user interfaces and improve user experience across HRMS modules.',
    'Portfolio required; experience with Figma; solid understanding of responsive design and accessibility principles.',
    FALSE,
    'Full-Time',
    2,
    25,
    'Open',
    NULL,
    'c3d7e5b06a4c5d3f9e5c8b2a7f4d1e33',
    DATE_ADD(CURDATE(), INTERVAL 10 DAY),
    1,
    DATE_SUB(@now, INTERVAL 65 DAY)
),

(
    4,
    'HR Officer',
    'Support recruitment, onboarding, and employee records management.',
    'BS in Psychology, HR Management, or related field; strong communication skills; familiarity with labor standards is a plus.',
    TRUE,
    'Full-Time',
    2,
    35,
    'Open',
    NULL,
    'd4c6f6a17b5d6e4a0f6d9c3b8a5e2f44',
    DATE_ADD(CURDATE(), INTERVAL 9 DAY),
    1,
    DATE_SUB(@now, INTERVAL 60 DAY)
),

(
    5,
    'HR Manager',
    'Lead the HR team and oversee recruitment, employee relations, and labor compliance.',
    'BS in Psychology, HR Management, or related field; at least 5 years of HR generalist experience; leadership and labor law knowledge.',
    TRUE,
    'Full-Time',
    1,
    15,
    'Closed',
    DATE_SUB(@now, INTERVAL 180 DAY),
    'e5b5a7328c6e7f5b1a7e0d4c9b6f3a55',
    DATE_SUB(CURDATE(), INTERVAL 185 DAY),
    1,
    DATE_SUB(@now, INTERVAL 200 DAY)
),

(
    6,
    'Financial Analyst',
    'Prepare financial reports and assist in budget planning and forecasting.',
    'BS in Accountancy or Finance; CPA preferred; proficient in Excel and financial modeling.',
    TRUE,
    'Full-Time',
    1,
    20,
    'Open',
    NULL,
    'f6a4b8439d7f8a6c2b8f1e5d0a7c4b66',
    DATE_ADD(CURDATE(), INTERVAL 6 DAY),
    1,
    DATE_SUB(@now, INTERVAL 55 DAY)
),

(
    7,
    'Marketing Associate',
    'Assist in planning and executing marketing campaigns and social media content.',
    'BS in Marketing or Communications; creative writing and content creation skills.',
    FALSE,
    'Full-Time',
    3,
    50,
    'Open',
    NULL,
    'a7c3d9540e8a9b7d3c9a2f6e1b8d5c77',
    DATE_ADD(CURDATE(), INTERVAL 15 DAY),
    1,
    DATE_SUB(@now, INTERVAL 50 DAY)
),

(
    8,
    'Marketing Manager',
    'Own the marketing strategy and lead a team of associates across digital and offline channels.',
    'BS in Marketing or Business; at least 4 years of campaign management experience; budget ownership.',
    TRUE,
    'Full-Time',
    1,
    10,
    'Closed',
    DATE_SUB(@now, INTERVAL 50 DAY),
    'b8d4ea650f9b0c8e4d0b3a7f2c9e6d88',
    DATE_SUB(CURDATE(), INTERVAL 55 DAY),
    1,
    DATE_SUB(@now, INTERVAL 95 DAY)
),

(
    9,
    'Operations Coordinator',
    'Coordinate daily operational activities and support process improvement initiatives.',
    'BS in Business Administration or related field; strong organizational and coordination skills.',
    FALSE,
    'Contract',
    1,
    20,
    'Open',
    NULL,
    'c9e5fb761a0c1d9f5e1c4b8a3d0f7e99',
    DATE_ADD(CURDATE(), INTERVAL 21 DAY),
    1,
    DATE_SUB(@now, INTERVAL 40 DAY)
);

-- posting_id:
-- 1 = Software Developer     (Open)
-- 2 = IT Manager             (Closed)
-- 3 = UI/UX Designer         (Open)
-- 4 = HR Officer             (Open)
-- 5 = HR Manager             (Closed)
-- 6 = Financial Analyst      (Open)
-- 7 = Marketing Associate    (Open)
-- 8 = Marketing Manager      (Closed)
-- 9 = Operations Coordinator (Open)


-- =====================================================================
-- 6. APPLICANTS
-- =====================================================================
-- Emails use several providers and phone numbers use mixed formats so
-- the list does not look machine-generated.
-- =====================================================================

INSERT INTO applicants (
    first_name,
    middle_name,
    last_name,
    email,
    phone,
    address,
    created_at
) VALUES

('Juan',     'Ramirez', 'Dela Cruz',  'juan.delacruz@gmail.com',      '0917 345 1201',    'Quezon City, Metro Manila',        DATE_SUB(@now, INTERVAL 71 DAY)),
('Maria',    'Lopez',   'Santos',     'maria.santos@yahoo.com',       '0918-234-5510',    'Manila, Metro Manila',             DATE_SUB(@now, INTERVAL 13 DAY)),
('Jose',     'Antonio', 'Reyes',      'jose.reyes@gmail.com',         '+63 920 887 4412', 'Makati City, Metro Manila',        DATE_SUB(@now, INTERVAL 10 DAY)),
('Beatrice', 'Claire',  'Ramos',      'beatrice.ramos@outlook.com',   '0927 554 9081',    'Pasig City, Metro Manila',         DATE_SUB(@now, INTERVAL 8 DAY)),
('Elena',    'Grace',   'Rivera',     'elena.rivera@gmail.com',       '0915-717-2789',    'Taguig City, Metro Manila',        DATE_SUB(@now, INTERVAL 6 DAY)),
('Bianca',   'Mae',     'Alonzo',     'bianca.alonzo@gmail.com',      '0995 221 7783',    'Mandaluyong City, Metro Manila',   DATE_SUB(@now, INTERVAL 4 DAY)),
('Timothy',  'Paul',    'Ramos',      'timothy.ramos@yahoo.com',      '0917-802-3345',    'Caloocan City, Metro Manila',      DATE_SUB(@now, INTERVAL 3 DAY)),
('Leonardo', 'Miguel',  'Navarro',    'leonardo.navarro@gmail.com',   '0906 445 1192',    'Paranaque City, Metro Manila',     DATE_SUB(@now, INTERVAL 61 DAY)),
('Angela',   'Rose',    'Fernandez',  'angela.fernandez@gmail.com',   '0917-233-8890',    'San Juan City, Metro Manila',      DATE_SUB(@now, INTERVAL 11 DAY)),
('Grace',    'Anne',    'Villamor',   'grace.villamor@outlook.com',   '0928 110 4477',    'Marikina City, Metro Manila',      DATE_SUB(@now, INTERVAL 5 DAY)),
('Ana',      'Marie',   'Bautista',   'ana.bautista@gmail.com',       '0917 645 2210',    'Pasig City, Metro Manila',         DATE_SUB(@now, INTERVAL 56 DAY)),
('Mark',     'Julian',  'Villanueva', 'mark.villanueva@gmail.com',    '0920-778-1123',    'Taguig City, Metro Manila',        DATE_SUB(@now, INTERVAL 7 DAY)),
('Gabriel',  'Luis',    'Castillo',   'gabriel.castillo@gmail.com',   '0915 334 9908',    'Valenzuela City, Metro Manila',    DATE_SUB(@now, INTERVAL 9 DAY)),
('Michelle', 'Anne',    'Torres',     'michelle.torres@yahoo.com',    '0917-556-3387',    'Quezon City, Metro Manila',        DATE_SUB(@now, INTERVAL 21 DAY)),
('Daniel',   'Rafael',  'Mercado',    'daniel.mercado@gmail.com',     '0908 221 7765',    'Las Pinas City, Metro Manila',     DATE_SUB(@now, INTERVAL 12 DAY)),
('Camille',  'Joy',     'Domingo',    'camille.domingo@gmail.com',    '0917-990-2231',    'Bacoor, Cavite',                   DATE_SUB(@now, INTERVAL 6 DAY)),
('Kristine', 'Faith',   'Aquino',     'kristine.aquino@gmail.com',    '0926 447 8812',    'Caloocan City, Metro Manila',      DATE_SUB(@now, INTERVAL 14 DAY)),
('Paolo',    'Miguel',  'Mendoza',    'paolo.mendoza@outlook.com',    '0917-321-5590',    'Marikina City, Metro Manila',      DATE_SUB(@now, INTERVAL 5 DAY)),
('Sofia',    'Isabel',  'Cabrera',    'sofia.cabrera@gmail.com',      '0917 778 2234',    'Muntinlupa City, Metro Manila',    DATE_SUB(@now, INTERVAL 46 DAY)),
('Nathan',   'Paul',    'Manalo',     'nathan.manalo@gmail.com',      '0905-662-1147',    'Antipolo City, Rizal',             DATE_SUB(@now, INTERVAL 4 DAY)),
('Patricia', 'Mae',     'Soriano',    'patricia.soriano@gmail.com',   '0917-884-3360',    'Meycauayan, Bulacan',              DATE_SUB(@now, INTERVAL 7 DAY)),
('Ramon',    'Luis',    'Garcia',     'ramon.garcia@yahoo.com',       '0921 553 7789',    'Mandaluyong City, Metro Manila',   DATE_SUB(@now, INTERVAL 9 DAY)),
('Victor',   'Andrei',  'Salazar',    'victor.salazar@gmail.com',     '0917-445-9921',    'Pasay City, Metro Manila',         DATE_SUB(@now, INTERVAL 196 DAY)),
('Rafael',   'Miguel',  'Mendoza',    'rafael.mendoza@gmail.com',     '0917 662 3348',    'Makati City, Metro Manila',        DATE_SUB(@now, INTERVAL 146 DAY));

-- applicant_id:
-- 1  = Juan Dela Cruz      13 = Gabriel Castillo
-- 2  = Maria Santos        14 = Michelle Torres
-- 3  = Jose Reyes          15 = Daniel Mercado
-- 4  = Beatrice Ramos      16 = Camille Domingo
-- 5  = Elena Rivera        17 = Kristine Aquino
-- 6  = Bianca Alonzo       18 = Paolo Mendoza
-- 7  = Timothy Ramos       19 = Sofia Cabrera
-- 8  = Leonardo Navarro    20 = Nathan Manalo
-- 9  = Angela Fernandez    21 = Patricia Soriano
-- 10 = Grace Villamor      22 = Ramon Garcia
-- 11 = Ana Bautista        23 = Victor Salazar
-- 12 = Mark Villanueva     24 = Rafael Mendoza


-- =====================================================================
-- 7. APPLICATIONS
-- =====================================================================
-- application_id follows applicant_id (1:1 here).
--
-- Rejected applications are intentionally placed on OPEN postings only:
-- the retention service removes rejected applications from CLOSED
-- postings immediately, so keeping them on open postings keeps them
-- visible in the demo.
--
-- Hired applications on the closed postings (2 and 5) are never purged.
-- =====================================================================

INSERT INTO applications (
    applicant_id,
    posting_id,
    resume_file,
    cover_letter_file,
    academic_document_file,
    application_status,
    rejected_at,
    applied_at
) VALUES

-- Posting 1 — Software Developer (Open)
(1,  1, 'resume_1_5f3a9c2b7e1d4086.pdf',  'cover_1_1a2b3c4d5e6f7089.pdf', 'academic_1_9f8e7d6c5b4a3021.pdf', 'Hired',        NULL, DATE_SUB(@now, INTERVAL 70 DAY)),
(2,  1, 'resume_2_7d1b4e8a2c6f9035.pdf',  NULL,                            'academic_2_3c2d1e0f9a8b7c6d.pdf', 'Interview',    NULL, DATE_SUB(@now, INTERVAL 12 DAY)),
(3,  1, 'resume_3_2c8e5a1d9f3b7064.pdf',  'cover_3_4d5e6f708192a3b4.pdf', 'academic_3_6b5a4c3d2e1f0098.pdf', 'Under Review', NULL, DATE_SUB(@now, INTERVAL 9 DAY)),
(4,  1, 'resume_4_8b2f6c3e1a9d5072.pdf',  'cover_4_7e8f9012a3b4c5d6.pdf', 'academic_4_1f0e2d3c4b5a6978.pdf', 'Under Review', NULL, DATE_SUB(@now, INTERVAL 7 DAY)),
(5,  1, 'resume_5_1a9d4b7e2c6f8053.pdf',  'cover_5_a1b2c3d4e5f60718.pdf', 'academic_5_2e3d4c5b6a7f9081.pdf', 'Interview',    NULL, DATE_SUB(@now, INTERVAL 5 DAY)),
(6,  1, 'resume_6_3e7c1a5d9b2f4068.pdf',  NULL,                            'academic_6_8a7b6c5d4e3f2019.pdf', 'Submitted',    NULL, DATE_SUB(@now, INTERVAL 3 DAY)),
(7,  1, 'resume_7_9c3f8b2e6a1d5047.pdf',  NULL,                            'academic_7_4d3e2f1a0b9c8d7e.pdf', 'Rejected',     DATE_SUB(@now, INTERVAL 1 DAY), DATE_SUB(@now, INTERVAL 2 DAY)),

-- Posting 3 — UI/UX Designer (Open, no academic document required)
(8,  3, 'resume_8_4b8e2c6f1a9d3057.pdf',  'cover_8_c3d4e5f607182a3b.pdf', NULL, 'Hired',        NULL, DATE_SUB(@now, INTERVAL 60 DAY)),
(9,  3, 'resume_9_6f1a4d8b2c7e5093.pdf',  'cover_9_b2c3d4e5f6071829.pdf', NULL, 'Under Review', NULL, DATE_SUB(@now, INTERVAL 10 DAY)),
(10, 3, 'resume_10_2e6b9c3f1a8d4075.pdf', NULL,                            NULL, 'Submitted',    NULL, DATE_SUB(@now, INTERVAL 4 DAY)),

-- Posting 4 — HR Officer (Open)
(11, 4, 'resume_11_8c2f5a1e9b3d7064.pdf', 'cover_11_5e6f708192a3b4c5.pdf', 'academic_11_7a8b9c0d1e2f3045.pdf', 'Hired',        NULL, DATE_SUB(@now, INTERVAL 55 DAY)),
(12, 4, 'resume_12_1d5b8e2c6f9a3047.pdf', NULL,                             'academic_12_3f4e5d6c7b8a9021.pdf', 'Rejected',     DATE_SUB(@now, INTERVAL 4 DAY), DATE_SUB(@now, INTERVAL 6 DAY)),
(13, 4, 'resume_13_7a3e6c1f9b2d5084.pdf', 'cover_13_8f9012a3b4c5d6e7.pdf', 'academic_13_9c8d7e6f5a4b3021.pdf', 'Interview',    NULL, DATE_SUB(@now, INTERVAL 8 DAY)),

-- Posting 6 — Financial Analyst (Open)
(14, 6, 'resume_14_5c9b2e6f1a3d8074.pdf', 'cover_14_2a3b4c5d6e7f8091.pdf', 'academic_14_1b2c3d4e5f607182.pdf', 'Rejected',     DATE_SUB(@now, INTERVAL 18 DAY), DATE_SUB(@now, INTERVAL 20 DAY)),
(15, 6, 'resume_15_3b7e1c5a9f2d4068.pdf', NULL,                             'academic_15_6e7f8091a2b3c4d5.pdf', 'Interview',    NULL, DATE_SUB(@now, INTERVAL 11 DAY)),
(16, 6, 'resume_16_9e2a6c1f4b8d3057.pdf', 'cover_16_4c5d6e7f8091a2b3.pdf', 'academic_16_5d6e7f8091a2b3c4.pdf', 'Under Review', NULL, DATE_SUB(@now, INTERVAL 5 DAY)),

-- Posting 7 — Marketing Associate (Open, no academic document required)
(17, 7, 'resume_17_1f6d3b8e2c5a9074.pdf', 'cover_17_9a0b1c2d3e4f5061.pdf', NULL, 'Interview',    NULL, DATE_SUB(@now, INTERVAL 13 DAY)),
(18, 7, 'resume_18_4a9c2f7e1b6d3058.pdf', NULL,                            NULL, 'Submitted',    NULL, DATE_SUB(@now, INTERVAL 4 DAY)),
(19, 7, 'resume_19_7e1b5d9a3c8f2064.pdf', 'cover_19_6d7e8f9012a3b4c5.pdf', NULL, 'Hired',        NULL, DATE_SUB(@now, INTERVAL 45 DAY)),
(20, 7, 'resume_20_2c8f4a1e9b6d5073.pdf', NULL,                            NULL, 'Submitted',    NULL, DATE_SUB(@now, INTERVAL 3 DAY)),
(21, 7, 'resume_21_5b2e8c1f4a9d6073.pdf', 'cover_21_3e4f5061728394a5.pdf', NULL, 'Under Review', NULL, DATE_SUB(@now, INTERVAL 6 DAY)),

-- Posting 9 — Operations Coordinator (Open, no academic document required)
(22, 9, 'resume_22_8f3c1a6e2b5d4079.pdf', NULL, NULL, 'Submitted', NULL, DATE_SUB(@now, INTERVAL 8 DAY)),

-- Posting 5 — HR Manager (Closed, hired before close)
(23, 5, 'resume_23_6a1e4b9c2f7d3058.pdf', 'cover_23_7f8091a2b3c4d5e6.pdf', 'academic_23_8b9c0d1e2f3a4051.pdf', 'Hired', NULL, DATE_SUB(@now, INTERVAL 195 DAY)),

-- Posting 2 — IT Manager (Closed, hired before close)
(24, 2, 'resume_24_3d9b6f1a5c2e8074.pdf', 'cover_24_1c2d3e4f50617283.pdf', 'academic_24_0d1e2f3a4b5c6071.pdf', 'Hired', NULL, DATE_SUB(@now, INTERVAL 145 DAY));


-- =====================================================================
-- 8. EMPLOYEES
-- =====================================================================
-- Department is derived: employees.position_id -> positions.department_id.
--
-- Employees must exist BEFORE their user accounts and before
-- onboarding_documents (employee_id FK).
-- =====================================================================

INSERT INTO employees (
    application_id,
    applicant_id,
    employee_number,
    position_id,
    hire_date,
    employment_status
) VALUES
(1,  1,  CONCAT('EMP-', YEAR(CURDATE()), '-0001'), 1, DATE_SUB(CURDATE(), INTERVAL 35 DAY),  'Probationary'),
(11, 11, CONCAT('EMP-', YEAR(CURDATE()), '-0002'), 4, DATE_SUB(CURDATE(), INTERVAL 14 DAY),  'Probationary'),
(8,  8,  CONCAT('EMP-', YEAR(CURDATE()), '-0003'), 3, DATE_SUB(CURDATE(), INTERVAL 28 DAY),  'Probationary'),
(19, 19, CONCAT('EMP-', YEAR(CURDATE()), '-0004'), 7, DATE_SUB(CURDATE(), INTERVAL 3 DAY),   'Probationary'),
(23, 23, CONCAT('EMP-', YEAR(CURDATE()), '-0005'), 5, DATE_SUB(CURDATE(), INTERVAL 180 DAY), 'Regular'),
(24, 24, CONCAT('EMP-', YEAR(CURDATE()), '-0006'), 2, DATE_SUB(CURDATE(), INTERVAL 130 DAY), 'Regular');

-- employee_id:
-- 1 = Juan Dela Cruz      (Software Developer)
-- 2 = Ana Bautista        (HR Officer)
-- 3 = Leonardo Navarro    (UI/UX Designer)
-- 4 = Sofia Cabrera       (Marketing Associate)
-- 5 = Victor Salazar      (HR Manager)
-- 6 = Rafael Mendoza      (IT Manager)


-- =====================================================================
-- 9. EMPLOYEE USER ACCOUNTS
-- =====================================================================
-- Linked to employees through users.employee_id. One account per role so
-- every dashboard can be demonstrated:
--   EMP  -> Juan, Ana, Leonardo, Sofia
--   HR   -> Victor   (the HR staff account used for onboarding)
--   MGR  -> Rafael   (the manager account used for hiring decisions)
--
-- must_change_password is 1 only for the two accounts whose onboarding is
-- still open, which mirrors the real first-login flow.
-- =====================================================================

INSERT INTO users (
    employee_id,
    role_id,
    username,
    email,
    email_verified_at,
    password,
    status,
    must_change_password,
    created_at
) VALUES
(1, 4, 'juan.delacruz',     'juan.delacruz@gmail.com',     DATE_SUB(@now, INTERVAL 35 DAY),  '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0, DATE_SUB(@now, INTERVAL 35 DAY)),
(2, 4, 'ana.bautista',      'ana.bautista@gmail.com',      DATE_SUB(@now, INTERVAL 14 DAY),  '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 1, DATE_SUB(@now, INTERVAL 14 DAY)),
(3, 4, 'leonardo.navarro',  'leonardo.navarro@gmail.com',  DATE_SUB(@now, INTERVAL 28 DAY),  '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0, DATE_SUB(@now, INTERVAL 28 DAY)),
(4, 4, 'sofia.cabrera',     'sofia.cabrera@gmail.com',     DATE_SUB(@now, INTERVAL 3 DAY),   '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 1, DATE_SUB(@now, INTERVAL 3 DAY)),
(5, 2, 'victor.salazar',    'victor.salazar@gmail.com',    DATE_SUB(@now, INTERVAL 180 DAY), '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0, DATE_SUB(@now, INTERVAL 180 DAY)),
(6, 3, 'rafael.mendoza',    'rafael.mendoza@gmail.com',    DATE_SUB(@now, INTERVAL 130 DAY), '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0, DATE_SUB(@now, INTERVAL 130 DAY));

-- user_id:
-- 2 = Juan Dela Cruz (EMP)
-- 3 = Ana Bautista (EMP)
-- 4 = Leonardo Navarro (EMP)
-- 5 = Sofia Cabrera (EMP)
-- 6 = Victor Salazar (HR)
-- 7 = Rafael Mendoza (MGR)


-- =====================================================================
-- 10. AI SCREENING
-- =====================================================================
-- One result per application that has moved past 'Submitted'. The five
-- Submitted applications intentionally have no screening yet.
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
(1,  92.50, 95.00, 90.00, 90.00, 95.00, 'Highly Recommended', 'PHP, MySQL, JavaScript, Git, REST APIs',
    JSON_ARRAY('Strong backend development experience', 'Solid understanding of relational databases', 'Clear, well-structured resume'),
    JSON_ARRAY('Limited exposure to cloud deployment'),
    'Candidate demonstrates strong technical alignment with the Software Developer role and relevant hands-on project experience.'),
(2,  84.00, 86.00, 80.00, 85.00, 84.00, 'Recommended', 'JavaScript, HTML, CSS, PHP, Bootstrap',
    JSON_ARRAY('Good front-end foundation', 'Relevant academic and freelance projects'),
    JSON_ARRAY('Limited production deployment experience'),
    'Candidate shows solid technical skills and would fit the team with light mentoring.'),
(3,  71.00, 68.00, 65.00, 80.00, 70.00, 'Consider', 'HTML, CSS, basic PHP, Git',
    JSON_ARRAY('Eagerness to learn reflected in the resume objective', 'Relevant coursework in web development'),
    JSON_ARRAY('Skill set does not fully match the job requirements', 'No prior professional experience listed'),
    'Candidate meets the educational requirement but needs to demonstrate more hands-on development experience.'),
(4,  76.00, 78.00, 72.00, 80.00, 74.00, 'Recommended', 'JavaScript, React, CSS, Figma',
    JSON_ARRAY('Modern front-end tooling experience', 'Good eye for interface detail'),
    JSON_ARRAY('Backend exposure is limited'),
    'Candidate is a reasonable fit for a developer role with a front-end emphasis.'),
(5,  87.00, 88.00, 85.00, 88.00, 87.00, 'Recommended', 'PHP, Laravel, MySQL, REST APIs, Docker',
    JSON_ARRAY('Full-stack project experience', 'Familiar with deployment pipelines'),
    JSON_ARRAY('Short tenure in the most recent role'),
    'Candidate is well aligned with the Software Developer opening and merits a technical interview.'),
(7,  44.00, 40.00, 42.00, 55.00, 43.00, 'Not Recommended', 'Customer Service, MS Office',
    JSON_ARRAY('Good communication background'),
    JSON_ARRAY('No software development coursework or experience', 'Resume does not address the job requirements'),
    'Candidate profile does not align with the requirements of the Software Developer position.'),
(8,  90.00, 92.00, 86.00, 92.00, 89.00, 'Highly Recommended', 'Figma, Design Systems, User Research, Prototyping',
    JSON_ARRAY('Strong portfolio with accessible interfaces', 'Experience collaborating with developers'),
    JSON_ARRAY('Limited experience in enterprise applications'),
    'Candidate has the portfolio quality and collaboration skills expected for the UI/UX Designer role.'),
(9,  74.00, 76.00, 68.00, 78.00, 74.00, 'Recommended', 'Figma, Adobe XD, Wireframing, Prototyping',
    JSON_ARRAY('Strong design portfolio', 'Familiarity with modern UI tools'),
    JSON_ARRAY('No formal UX research experience listed'),
    'Candidate shows solid design fundamentals and a promising portfolio for the UI/UX Designer role.'),
(11, 89.00, 88.00, 86.00, 90.00, 90.00, 'Highly Recommended', 'Recruitment, Employee Relations, HRIS, Onboarding',
    JSON_ARRAY('Direct HR internship experience', 'Strong communication skills evident in the cover letter'),
    JSON_ARRAY('No certification in HR management yet'),
    'Candidate is well qualified for the HR Officer role with relevant experience and strong soft skills.'),
(12, 39.00, 35.00, 30.00, 50.00, 38.00, 'Not Recommended', 'Customer Service, MS Office',
    JSON_ARRAY('Good communication background'),
    JSON_ARRAY('No HR-related coursework or experience', 'Resume does not address the job requirements'),
    'Candidate profile does not align closely with the requirements of the HR Officer position.'),
(13, 81.00, 80.00, 79.00, 84.00, 80.00, 'Recommended', 'Recruitment, HRIS, Policy Writing, Employee Relations',
    JSON_ARRAY('Hands-on HR operations experience', 'Clear understanding of onboarding workflows'),
    JSON_ARRAY('No professional HR certification listed'),
    'Candidate demonstrates practical HR experience suitable for the HR Officer opening.'),
(14, 42.00, 45.00, 38.00, 48.00, 40.00, 'Not Recommended', 'Bookkeeping, Data Entry, MS Excel',
    JSON_ARRAY('Basic spreadsheet proficiency'),
    JSON_ARRAY('No relevant degree listed', 'Limited financial analysis experience'),
    'Candidate does not currently meet the academic and technical requirements for the Financial Analyst role.'),
(15, 78.00, 76.00, 75.00, 82.00, 78.00, 'Recommended', 'Excel, Budgeting, Forecasting, Power BI',
    JSON_ARRAY('Relevant financial modeling coursework', 'Analytical mindset'),
    JSON_ARRAY('Limited full-time finance experience'),
    'Candidate has useful analytical foundations and should be assessed further in the technical interview.'),
(16, 68.00, 70.00, 62.00, 74.00, 66.00, 'Consider', 'Accounting, Excel, QuickBooks',
    JSON_ARRAY('Bookkeeping experience'),
    JSON_ARRAY('No professional certification yet', 'Forecasting exposure is limited'),
    'Candidate meets minimum requirements but is less experienced than other applicants for the role.'),
(17, 83.00, 82.00, 80.00, 85.00, 83.00, 'Recommended', 'Social Media Management, Content Writing, Canva',
    JSON_ARRAY('Active social media portfolio', 'Relevant coursework in marketing communications'),
    JSON_ARRAY('Limited experience with paid advertising campaigns'),
    'Candidate has solid foundational marketing skills suitable for an associate-level role.'),
(19, 88.00, 86.00, 88.00, 86.00, 89.00, 'Highly Recommended', 'Campaign Planning, SEO, Analytics, Copywriting',
    JSON_ARRAY('Managed measurable digital campaigns', 'Strong written communication'),
    JSON_ARRAY('Needs more exposure to offline events'),
    'Candidate has a strong marketing background and can contribute quickly to campaign execution.'),
(21, 72.00, 75.00, 68.00, 74.00, 71.00, 'Consider', 'Social Media, Canva, Content Planning, Community Management',
    JSON_ARRAY('Consistent content portfolio'),
    JSON_ARRAY('Limited reporting and analytics experience'),
    'Candidate has a promising creative profile for the Marketing Associate role.'),
(23, 93.00, 90.00, 94.00, 92.00, 93.00, 'Highly Recommended', 'Employee Relations, HRIS, Labor Compliance, Coaching',
    JSON_ARRAY('Extensive HR generalist experience', 'Strong stakeholder management'),
    JSON_ARRAY('Salary expectations require confirmation'),
    'Candidate is highly aligned with the HR Manager role and has relevant leadership experience.'),
(24, 91.00, 89.00, 92.00, 90.00, 92.00, 'Highly Recommended', 'Team Leadership, Systems Administration, Cloud Infrastructure, Budgeting',
    JSON_ARRAY('Proven team leadership', 'Deep infrastructure and security background'),
    JSON_ARRAY('Limited experience with HR-specific platforms'),
    'Candidate is highly aligned with the IT Manager role and brings strong leadership experience.');


-- =====================================================================
-- 11. INTERVIEWS
-- =====================================================================
-- interviewer_id references users(user_id):
--   1 = admin, 6 = Victor Salazar (HR), 7 = Rafael Mendoza (MGR)
--
-- Hired candidates were interviewed before their hire date (Passed);
-- candidates still in the Interview stage have a date in the near future.
-- =====================================================================

INSERT INTO interviews (
    application_id,
    interviewer_id,
    interview_type,
    interview_date,
    location,
    remarks,
    result,
    created_at
) VALUES
(1,  7, 'Face-to-Face', DATE_SUB(@now, INTERVAL 40 DAY), 'RAM-YUM Head Office, 3F Conference Room', 'Candidate demonstrated strong problem-solving skills and cultural fit during the technical panel interview.', 'Passed', DATE_SUB(@now, INTERVAL 41 DAY)),
(2,  1, 'Online',       DATE_ADD(@now, INTERVAL 3 DAY),  'Google Meet', 'Scheduled to assess technical skills and team collaboration style.', 'Pending', DATE_SUB(@now, INTERVAL 2 DAY)),
(5,  7, 'Online',       DATE_ADD(@now, INTERVAL 2 DAY),  'Google Meet', 'Technical round focused on back-end architecture and code review.', 'Pending', DATE_SUB(@now, INTERVAL 1 DAY)),
(8,  1, 'Online',       DATE_SUB(@now, INTERVAL 33 DAY), 'Google Meet', 'Portfolio review completed; candidate explained design decisions clearly.', 'Passed', DATE_SUB(@now, INTERVAL 34 DAY)),
(11, 6, 'Face-to-Face', DATE_SUB(@now, INTERVAL 20 DAY), 'RAM-YUM Head Office, HR Interview Room', 'Candidate articulated a clear understanding of HR processes and showed strong interpersonal skills.', 'Passed', DATE_SUB(@now, INTERVAL 21 DAY)),
(13, 6, 'Face-to-Face', DATE_ADD(@now, INTERVAL 4 DAY),  'RAM-YUM Head Office, HR Interview Room', 'Scheduled to discuss employee relations cases and HR policy implementation.', 'Pending', DATE_SUB(@now, INTERVAL 2 DAY)),
(14, 1, 'Online',       DATE_SUB(@now, INTERVAL 12 DAY), 'Google Meet', 'Candidate was unable to answer core technical finance questions during the assessment.', 'Failed', DATE_SUB(@now, INTERVAL 13 DAY)),
(15, 7, 'Online',       DATE_ADD(@now, INTERVAL 5 DAY),  'Google Meet', 'Technical interview scheduled for financial modeling and reporting skills.', 'Pending', DATE_SUB(@now, INTERVAL 1 DAY)),
(17, 6, 'Phone',        DATE_ADD(@now, INTERVAL 1 DAY),  'N/A', 'Initial phone screening to evaluate communication skills before the final interview round.', 'Pending', DATE_SUB(@now, INTERVAL 1 DAY)),
(19, 6, 'Face-to-Face', DATE_SUB(@now, INTERVAL 16 DAY), 'RAM-YUM Head Office, Marketing Room', 'Candidate presented a practical campaign plan for a new service launch.', 'Passed', DATE_SUB(@now, INTERVAL 17 DAY)),
(23, 1, 'Online',       DATE_SUB(@now, INTERVAL 186 DAY),'Google Meet', 'Candidate demonstrated strong HR leadership and compliance knowledge.', 'Passed', DATE_SUB(@now, INTERVAL 187 DAY)),
(24, 1, 'Face-to-Face', DATE_SUB(@now, INTERVAL 136 DAY),'RAM-YUM Head Office, 3F Conference Room', 'Candidate outlined an infrastructure modernization roadmap and led the panel discussion well.', 'Passed', DATE_SUB(@now, INTERVAL 137 DAY));


-- =====================================================================
-- 12. ONBOARDING
-- =====================================================================
-- One record per hired employee. Statuses cover all four enum values so
-- every badge is visible: Completed, In Progress, Pending, Overdue.
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
(1,  DATE_SUB(CURDATE(), INTERVAL 33 DAY), DATE_SUB(CURDATE(), INTERVAL 35 DAY), DATE_SUB(CURDATE(), INTERVAL 31 DAY), 'Completed',   TRUE,  TRUE,  TRUE,  TRUE,  'Employee has completed orientation and submitted all required documents.'),
(11, DATE_SUB(CURDATE(), INTERVAL 12 DAY), DATE_SUB(CURDATE(), INTERVAL 14 DAY), NULL,                                 'In Progress', TRUE,  TRUE,  FALSE, FALSE, 'Employee attended orientation and is awaiting submission of remaining pre-employment documents.'),
(8,  DATE_SUB(CURDATE(), INTERVAL 26 DAY), DATE_SUB(CURDATE(), INTERVAL 28 DAY), DATE_SUB(CURDATE(), INTERVAL 24 DAY), 'Completed',   TRUE,  TRUE,  TRUE,  TRUE,  'Designer completed orientation and all document checks.'),
(19, NULL,                                  NULL,                                  NULL,                                 'Pending',     FALSE, FALSE, FALSE, FALSE, 'Newly hired; onboarding checklist has not been started yet.'),
(23, DATE_SUB(CURDATE(), INTERVAL 178 DAY), DATE_SUB(CURDATE(), INTERVAL 180 DAY), DATE_SUB(CURDATE(), INTERVAL 176 DAY), 'Completed', TRUE, TRUE,  TRUE,  TRUE,  'HR Manager completed orientation and all document checks.'),
(24, DATE_SUB(CURDATE(), INTERVAL 128 DAY), DATE_SUB(CURDATE(), INTERVAL 130 DAY), NULL,                                'Overdue',     TRUE,  TRUE,  FALSE, FALSE, 'Outstanding pre-employment documents are past their due date and need follow-up.');

-- onboarding_id:
-- 1 = Juan Dela Cruz     4 = Sofia Cabrera
-- 2 = Ana Bautista       5 = Victor Salazar
-- 3 = Leonardo Navarro   6 = Rafael Mendoza


-- =====================================================================
-- 13. ONBOARDING DOCUMENTS
-- =====================================================================
-- Base rows only. The request/assignment columns, submission timestamps
-- and file paths are filled in by the UPDATE block at the end of this
-- file (kept deterministic by keying off status).
--
-- assigned_by = 6 (Victor Salazar, HR).
-- =====================================================================

INSERT INTO onboarding_documents (
    onboarding_id,
    employee_id,
    document_name,
    instruction,
    status,
    request_type,
    priority,
    due_date,
    assigned_by,
    action_type,
    created_at,
    submitted_at
) VALUES
-- Juan Dela Cruz (completed)
(1, 1, 'Government-Issued ID',                 'Upload a clear scan of your valid government ID.',              'Approved',  'Submit Required Document',             'Normal', DATE_SUB(CURDATE(), INTERVAL 21 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 35 DAY),  DATE_SUB(@now, INTERVAL 33 DAY)),
(1, 1, 'NBI Clearance',                        'Upload your latest NBI clearance.',                             'Approved',  'Submit Missing Requirement',           'Normal', DATE_SUB(CURDATE(), INTERVAL 21 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 35 DAY),  DATE_SUB(@now, INTERVAL 33 DAY)),
(1, 1, 'SSS, PhilHealth, Pag-IBIG Numbers',    'Provide your government contribution numbers.',                 'Approved',  'Submit Additional Employment Document', 'Normal', DATE_SUB(CURDATE(), INTERVAL 21 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 35 DAY),  DATE_SUB(@now, INTERVAL 33 DAY)),
(1, 1, 'Signed Employment Contract',           'Return the signed copy of your employment contract.',           'Approved',  'Submit Required Document',             'Normal', DATE_SUB(CURDATE(), INTERVAL 21 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 35 DAY),  DATE_SUB(@now, INTERVAL 33 DAY)),
(1, 1, 'Medical Certificate',                  'Upload your pre-employment medical certificate.',               'Approved', 'Submit Additional Employment Document', 'Normal', DATE_SUB(CURDATE(), INTERVAL 21 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 35 DAY),  DATE_SUB(@now, INTERVAL 33 DAY)),

-- Ana Bautista (in progress)
(2, 2, 'Government-Issued ID',                 'Upload a clear scan of your valid government ID.',              'Pending',  'Submit Required Document',             'High',   DATE_SUB(CURDATE(), INTERVAL 7 DAY),  6, 'document', DATE_SUB(@now, INTERVAL 14 DAY),  DATE_SUB(@now, INTERVAL 12 DAY)),
(2, 2, 'NBI Clearance',                        'Upload your latest NBI clearance.',                             'Pending',  'Submit Missing Requirement',           'Urgent', DATE_SUB(CURDATE(), INTERVAL 2 DAY),  6, 'document', DATE_SUB(@now, INTERVAL 14 DAY),  NULL),
(2, 2, 'SSS, PhilHealth, Pag-IBIG Numbers',    'Provide your government contribution numbers.',                 'Pending',  'Submit Additional Employment Document', 'High',   DATE_ADD(CURDATE(), INTERVAL 3 DAY),  6, 'document', DATE_SUB(@now, INTERVAL 14 DAY),  DATE_SUB(@now, INTERVAL 12 DAY)),
(2, 2, 'Signed Employment Contract',           'Return the signed copy of your employment contract.',           'Approved', 'Submit Required Document',             'Normal', DATE_SUB(CURDATE(), INTERVAL 7 DAY),  6, 'document', DATE_SUB(@now, INTERVAL 14 DAY),  DATE_SUB(@now, INTERVAL 12 DAY)),

-- Leonardo Navarro (completed)
(3, 3, 'Government-Issued ID',                 'Upload a clear scan of your valid government ID.',              'Approved', 'Submit Required Document',             'Normal', DATE_SUB(CURDATE(), INTERVAL 14 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 28 DAY),  DATE_SUB(@now, INTERVAL 26 DAY)),
(3, 3, 'NBI Clearance',                        'Upload your latest NBI clearance.',                             'Approved', 'Submit Missing Requirement',           'Normal', DATE_SUB(CURDATE(), INTERVAL 14 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 28 DAY),  DATE_SUB(@now, INTERVAL 26 DAY)),
(3, 3, 'Signed Employment Contract',           'Return the signed copy of your employment contract.',           'Approved', 'Submit Required Document',             'Normal', DATE_SUB(CURDATE(), INTERVAL 14 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 28 DAY),  DATE_SUB(@now, INTERVAL 26 DAY)),

-- Sofia Cabrera (pending — nothing submitted yet)
(4, 4, 'Government-Issued ID',                 'Upload a clear scan of your valid government ID.',              'Pending', 'Submit Required Document',             'High',   DATE_ADD(CURDATE(), INTERVAL 11 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 3 DAY),   NULL),
(4, 4, 'NBI Clearance',                        'Upload your latest NBI clearance.',                             'Pending', 'Submit Missing Requirement',           'High',   DATE_ADD(CURDATE(), INTERVAL 11 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 3 DAY),   NULL),
(4, 4, 'Signed Employment Contract',           'Return the signed copy of your employment contract.',           'Pending', 'Submit Required Document',             'High',   DATE_ADD(CURDATE(), INTERVAL 11 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 3 DAY),   NULL),

-- Victor Salazar (completed)
(5, 5, 'Government-Issued ID',                 'Upload a clear scan of your valid government ID.',              'Approved', 'Submit Required Document',             'Normal', DATE_SUB(CURDATE(), INTERVAL 159 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 180 DAY), DATE_SUB(@now, INTERVAL 178 DAY)),
(5, 5, 'NBI Clearance',                        'Upload your latest NBI clearance.',                             'Approved', 'Submit Missing Requirement',           'Normal', DATE_SUB(CURDATE(), INTERVAL 159 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 180 DAY), DATE_SUB(@now, INTERVAL 178 DAY)),
(5, 5, 'SSS, PhilHealth, Pag-IBIG Numbers',    'Provide your government contribution numbers.',                 'Approved', 'Submit Additional Employment Document', 'Normal', DATE_SUB(CURDATE(), INTERVAL 159 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 180 DAY), DATE_SUB(@now, INTERVAL 178 DAY)),
(5, 5, 'Signed Employment Contract',           'Return the signed copy of your employment contract.',           'Approved', 'Submit Required Document',             'Normal', DATE_SUB(CURDATE(), INTERVAL 159 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 180 DAY), DATE_SUB(@now, INTERVAL 178 DAY)),

-- Rafael Mendoza (overdue)
(6, 6, 'Government-Issued ID',                 'Upload a clear scan of your valid government ID.',              'Pending',  'Submit Required Document',             'High',   DATE_SUB(CURDATE(), INTERVAL 109 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 130 DAY), DATE_SUB(@now, INTERVAL 128 DAY)),
(6, 6, 'NBI Clearance',                        'Upload your latest NBI clearance.',                             'Pending',  'Submit Missing Requirement',           'Urgent', DATE_SUB(CURDATE(), INTERVAL 109 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 130 DAY), NULL),
(6, 6, 'SSS, PhilHealth, Pag-IBIG Numbers',    'Provide your government contribution numbers.',                 'Rejected', 'Submit Additional Employment Document', 'High',   DATE_SUB(CURDATE(), INTERVAL 109 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 130 DAY), DATE_SUB(@now, INTERVAL 128 DAY)),
(6, 6, 'Signed Employment Contract',           'Return the signed copy of your employment contract.',           'Approved', 'Submit Required Document',             'Normal', DATE_SUB(CURDATE(), INTERVAL 109 DAY), 6, 'document', DATE_SUB(@now, INTERVAL 130 DAY), DATE_SUB(@now, INTERVAL 128 DAY));


-- =====================================================================
-- 14. ACADEMIC DOCUMENT EVALUATIONS
-- =====================================================================
-- Only postings with academic_document_required = TRUE collect these, and
-- only applications that moved past 'Submitted' have been evaluated.
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
(1,  'academic_1_9f8e7d6c5b4a3021.pdf',  'Juan Dela Cruz - Diploma.pdf',       TRUE, 'Evaluated', TRUE,  98.00, 'Diploma', 'Polytechnic University of the Philippines', 'Bachelor of Science', 'Computer Science',     '2022', JSON_OBJECT('honors', 'Cum Laude'),                     JSON_ARRAY(), 'Document appears authentic and supports the academic requirement.', DATE_SUB(@now, INTERVAL 68 DAY)),
(2,  'academic_2_3c2d1e0f9a8b7c6d.pdf',  'Maria Santos - Diploma.pdf',         TRUE, 'Evaluated', TRUE,  96.00, 'Diploma', 'University of the Philippines Diliman',     'Bachelor of Science', 'Computer Science',     '2023', JSON_OBJECT('major', 'Software Engineering'),          JSON_ARRAY(), 'Document is valid and relevant to the Software Developer role.', DATE_SUB(@now, INTERVAL 10 DAY)),
(3,  'academic_3_6b5a4c3d2e1f0098.pdf',  'Jose Reyes - Diploma.pdf',           TRUE, 'Evaluated', TRUE,  94.00, 'Diploma', 'Ateneo de Manila University',               'Bachelor of Science', 'Information Technology','2021', JSON_OBJECT(),                                       JSON_ARRAY('Graduation year could not be fully cross-checked'), 'Document is valid; the transcript matches the declared degree.', DATE_SUB(@now, INTERVAL 7 DAY)),
(4,  'academic_4_1f0e2d3c4b5a6978.pdf',  'Beatrice Ramos - Diploma.pdf',       TRUE, 'Evaluated', TRUE,  92.00, 'Diploma', 'Mapua University',                          'Bachelor of Science', 'Computer Engineering', '2020', JSON_OBJECT(),                                       JSON_ARRAY('Degree title differs slightly from the preferred program'), 'Document is valid and the field is closely related to the role.', DATE_SUB(@now, INTERVAL 5 DAY)),
(5,  'academic_5_2e3d4c5b6a7f9081.pdf',  'Elena Rivera - Diploma.pdf',         TRUE, 'Evaluated', TRUE,  97.00, 'Diploma', 'De La Salle University',                    'Bachelor of Science', 'Computer Science',     '2019', JSON_OBJECT('honors', 'Dean''s Lister'),               JSON_ARRAY(), 'Document is valid and supports the candidate''s software background.', DATE_SUB(@now, INTERVAL 3 DAY)),
(7,  'academic_7_4d3e2f1a0b9c8d7e.pdf',  'Timothy Ramos - Certificate.pdf',    TRUE, 'Evaluated', FALSE, 88.00, 'Certificate of Completion', 'Private Training Institute', NULL, 'Computer Literacy', '2021', JSON_OBJECT(), JSON_ARRAY('Submitted document is not an acceptable academic credential'), 'Document could not be accepted as evidence of the required degree.', DATE_SUB(@now, INTERVAL 1 DAY)),
(11, 'academic_11_7a8b9c0d1e2f3045.pdf', 'Ana Bautista - Diploma.pdf',         TRUE, 'Evaluated', TRUE,  96.00, 'Diploma', 'University of Santo Tomas',                 'Bachelor of Science', 'Psychology',           '2021', JSON_OBJECT('major', 'Industrial Psychology'),         JSON_ARRAY(), 'Document is valid and relevant to the HR Officer position.', DATE_SUB(@now, INTERVAL 53 DAY)),
(12, 'academic_12_3f4e5d6c7b8a9021.pdf', 'Mark Villanueva - Diploma.pdf',      TRUE, 'Evaluated', TRUE,  90.00, 'Diploma', 'Far Eastern University',                    'Bachelor of Science', 'Business Administration','2018', JSON_OBJECT(), JSON_ARRAY('Degree is not in a people-management field'), 'Document is authentic but not closely aligned with the HR Officer requirement.', DATE_SUB(@now, INTERVAL 4 DAY)),
(13, 'academic_13_9c8d7e6f5a4b3021.pdf', 'Gabriel Castillo - Diploma.pdf',     TRUE, 'Evaluated', TRUE,  95.00, 'Diploma', 'University of the Philippines Los Banos',   'Bachelor of Science', 'Development Communication', '2020', JSON_OBJECT(), JSON_ARRAY(), 'Document is valid and relevant to the HR Officer role.', DATE_SUB(@now, INTERVAL 6 DAY)),
(14, 'academic_14_1b2c3d4e5f607182.pdf', 'Michelle Torres - Certificate.pdf',  TRUE, 'Evaluated', FALSE, 91.00, 'Certificate of Completion', 'Private Training Institute', NULL, 'Office Administration', '2020', JSON_OBJECT(), JSON_ARRAY('Submitted document is not an acceptable academic credential'), 'Document could not be accepted as evidence of the required finance degree.', DATE_SUB(@now, INTERVAL 18 DAY)),
(15, 'academic_15_6e7f8091a2b3c4d5.pdf', 'Daniel Mercado - Diploma.pdf',       TRUE, 'Evaluated', TRUE,  94.00, 'Diploma', 'Polytechnic University of the Philippines', 'Bachelor of Science', 'Accountancy',          '2019', JSON_OBJECT('certification', 'CPA Board Passer'),      JSON_ARRAY(), 'Document is valid and supports the finance requirement.', DATE_SUB(@now, INTERVAL 9 DAY)),
(16, 'academic_16_5d6e7f8091a2b3c4.pdf', 'Camille Domingo - Diploma.pdf',      TRUE, 'Evaluated', TRUE,  89.00, 'Diploma', 'San Beda University',                       'Bachelor of Science', 'Financial Management', '2022', JSON_OBJECT(), JSON_ARRAY('No professional certification listed'), 'Document is valid; certification would strengthen the application.', DATE_SUB(@now, INTERVAL 3 DAY)),
(23, 'academic_23_8b9c0d1e2f3a4051.pdf', 'Victor Salazar - Diploma.pdf',       TRUE, 'Evaluated', TRUE,  99.00, 'Diploma', 'Ateneo de Manila University',               'Bachelor of Science', 'Psychology',           '2010', JSON_OBJECT('certification', 'HR Certification Institute'), JSON_ARRAY(), 'Document is valid and supports the candidate''s HR leadership profile.', DATE_SUB(@now, INTERVAL 193 DAY)),
(24, 'academic_24_0d1e2f3a4b5c6071.pdf', 'Rafael Mendoza - Diploma.pdf',       TRUE, 'Evaluated', TRUE,  98.00, 'Diploma', 'University of the Philippines Diliman',     'Bachelor of Science', 'Computer Engineering', '2008', JSON_OBJECT('certification', 'AWS Solutions Architect'),  JSON_ARRAY(), 'Document is valid and supports the candidate''s IT leadership profile.', DATE_SUB(@now, INTERVAL 143 DAY));


-- =====================================================================
-- 15. ONBOARDING PHASES
-- =====================================================================
-- Five phases per onboarding record, kept in step with onboarding_status.
-- =====================================================================

INSERT INTO onboarding_phases (
    onboarding_id,
    phase_number,
    phase_name,
    phase_description,
    status,
    completed_at
) VALUES
-- Juan Dela Cruz — completed
(1, 1, 'System & Access Setup',      'Employee account and access are created.',            'completed', DATE_SUB(@now, INTERVAL 35 DAY)),
(1, 2, 'Orientation & Setup',        'Employee attends the orientation session.',            'completed', DATE_SUB(@now, INTERVAL 33 DAY)),
(1, 3, 'Document Submission',        'Employee submits all required documents.',             'completed', DATE_SUB(@now, INTERVAL 32 DAY)),
(1, 4, 'Document Verification',      'HR verifies submitted documents.',                     'completed', DATE_SUB(@now, INTERVAL 31 DAY)),
(1, 5, 'Final Onboarding Completion','System confirms all onboarding requirements.',         'completed', DATE_SUB(@now, INTERVAL 31 DAY)),

-- Ana Bautista — in progress
(2, 1, 'System & Access Setup',      'Employee account and access are created.',            'completed',   DATE_SUB(@now, INTERVAL 14 DAY)),
(2, 2, 'Orientation & Setup',        'Employee attends the orientation session.',            'completed',   DATE_SUB(@now, INTERVAL 12 DAY)),
(2, 3, 'Document Submission',        'Employee submits all required documents.',             'in-progress', NULL),
(2, 4, 'Document Verification',      'HR verifies submitted documents.',                     'pending',     NULL),
(2, 5, 'Final Onboarding Completion','System confirms all onboarding requirements.',         'pending',     NULL),

-- Leonardo Navarro — completed
(3, 1, 'System & Access Setup',      'Employee account and access are created.',            'completed', DATE_SUB(@now, INTERVAL 28 DAY)),
(3, 2, 'Orientation & Setup',        'Employee attends the orientation session.',            'completed', DATE_SUB(@now, INTERVAL 26 DAY)),
(3, 3, 'Document Submission',        'Employee submits all required documents.',             'completed', DATE_SUB(@now, INTERVAL 25 DAY)),
(3, 4, 'Document Verification',      'HR verifies submitted documents.',                     'completed', DATE_SUB(@now, INTERVAL 24 DAY)),
(3, 5, 'Final Onboarding Completion','System confirms all onboarding requirements.',         'completed', DATE_SUB(@now, INTERVAL 24 DAY)),

-- Sofia Cabrera — pending
(4, 1, 'System & Access Setup',      'Employee account and access are created.',            'pending', NULL),
(4, 2, 'Orientation & Setup',        'Employee attends the orientation session.',            'pending', NULL),
(4, 3, 'Document Submission',        'Employee submits all required documents.',             'pending', NULL),
(4, 4, 'Document Verification',      'HR verifies submitted documents.',                     'pending', NULL),
(4, 5, 'Final Onboarding Completion','System confirms all onboarding requirements.',         'pending', NULL),

-- Victor Salazar — completed
(5, 1, 'System & Access Setup',      'Employee account and access are created.',            'completed', DATE_SUB(@now, INTERVAL 180 DAY)),
(5, 2, 'Orientation & Setup',        'Employee attends the orientation session.',            'completed', DATE_SUB(@now, INTERVAL 178 DAY)),
(5, 3, 'Document Submission',        'Employee submits all required documents.',             'completed', DATE_SUB(@now, INTERVAL 177 DAY)),
(5, 4, 'Document Verification',      'HR verifies submitted documents.',                     'completed', DATE_SUB(@now, INTERVAL 176 DAY)),
(5, 5, 'Final Onboarding Completion','System confirms all onboarding requirements.',         'completed', DATE_SUB(@now, INTERVAL 176 DAY)),

-- Rafael Mendoza — overdue
(6, 1, 'System & Access Setup',      'Employee account and access are created.',            'completed',   DATE_SUB(@now, INTERVAL 130 DAY)),
(6, 2, 'Orientation & Setup',        'Employee attends the orientation session.',            'completed',   DATE_SUB(@now, INTERVAL 128 DAY)),
(6, 3, 'Document Submission',        'Employee submits all required documents.',             'in-progress', NULL),
(6, 4, 'Document Verification',      'HR verifies submitted documents.',                     'pending',     NULL),
(6, 5, 'Final Onboarding Completion','System confirms all onboarding requirements.',         'pending',     NULL);


-- =====================================================================
-- 16. EMPLOYEE REQUESTS
-- =====================================================================
-- Employee Self-Service requests (separate from onboarding documents).
-- resolved_by references users(user_id); HR user is 6, admin is 1.
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
(1, 'Certificate of Employment', 'Request for Certificate of Employment',    'I would like to request a Certificate of Employment for personal documentation purposes.', 'Completed', 'Certificate prepared and released to the employee.',            DATE_SUB(@now, INTERVAL 20 DAY), DATE_SUB(@now, INTERVAL 19 DAY), 6),
(1, 'Profile Update',            'Update Contact Information',               'I would like to update my contact information in my employee record.',                       'Pending',   NULL,                                                            DATE_SUB(@now, INTERVAL 4 DAY),  NULL, NULL),
(2, 'Document Request',          'Request for Employment Contract Copy',     'I would like to request a digital copy of my signed employment contract.',                   'Approved',  'Request approved. HR will prepare the requested document.',      DATE_SUB(@now, INTERVAL 9 DAY),  NULL, 6),
(2, 'Employment Concern',        'Question Regarding Employment Status',     'I would like clarification regarding my current probationary employment status.',            'Pending',   NULL,                                                            DATE_SUB(@now, INTERVAL 2 DAY),  NULL, NULL),
(3, 'Profile Update',            'Update Home Address',                      'Please update my home address in the employee records to my current residence.',             'Completed', 'Address updated in the employee record.',                        DATE_SUB(@now, INTERVAL 15 DAY), DATE_SUB(@now, INTERVAL 14 DAY), 6),
(4, 'Payroll Concern',           'First Payroll Enrolment Question',         'I would like to confirm the bank account details on file for my first payroll.',             'Pending',   NULL,                                                            DATE_SUB(@now, INTERVAL 1 DAY),  NULL, NULL),
(5, 'Certificate of Employment', 'Request for Certificate of Employment',    'Requesting a Certificate of Employment for a bank loan application.',                        'Rejected',  'Request could not be processed because the loan form was missing.', DATE_SUB(@now, INTERVAL 12 DAY), DATE_SUB(@now, INTERVAL 11 DAY), 6);


-- =====================================================================
-- 17. ACTIVITY LOGS
-- =====================================================================
-- Spread over the last month so the activity feed looks organic.
-- =====================================================================

INSERT INTO activity_logs (
    user_id,
    employee_id,
    action,
    page,
    request_method,
    ip_address,
    user_agent,
    created_at
) VALUES
(1, NULL, 'Logged in with OTP',                    'login',                  'POST', '203.0.113.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 28 DAY)),
(1, NULL, 'Viewed recruitment dashboard',          'recruitment',            'GET',  '203.0.113.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 28 DAY)),
(6, 5,    'Logged in with OTP',                    'login',                  'POST', '203.0.113.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 27 DAY)),
(6, 5,    'Created a job posting',                 'recruitment',            'POST', '203.0.113.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 26 DAY)),
(1, NULL, 'Reviewed applicant profile',            'applicants/review',      'GET',  '203.0.113.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 25 DAY)),
(2, 1,    'Logged in with OTP',                    'login',                  'POST', '198.51.100.7', 'Mozilla/5.0 (Linux; Android 13)',            DATE_SUB(@now, INTERVAL 24 DAY)),
(2, 1,    'Viewed employee dashboard',             'dashboard/employee',     'GET',  '198.51.100.7', 'Mozilla/5.0 (Linux; Android 13)',            DATE_SUB(@now, INTERVAL 24 DAY)),
(2, 1,    'Submitted a certificate request',       'employee-requests',      'POST', '198.51.100.7', 'Mozilla/5.0 (Linux; Android 13)',            DATE_SUB(@now, INTERVAL 20 DAY)),
(6, 5,    'Marked an employee request completed',  'employee-requests',      'POST', '203.0.113.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 19 DAY)),
(1, NULL, 'Exported recruitment report',           'recruitment/statistics', 'GET',  '203.0.113.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 18 DAY)),
(6, 5,    'Updated an onboarding checklist',       'onboarding',             'POST', '203.0.113.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 16 DAY)),
(3, 2,    'Logged in with OTP',                    'login',                  'POST', '198.51.100.31', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_5)', DATE_SUB(@now, INTERVAL 15 DAY)),
(3, 2,    'Viewed onboarding status',              'onboarding',             'GET',  '198.51.100.31', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_5)', DATE_SUB(@now, INTERVAL 15 DAY)),
(7, 6,    'Logged in with OTP',                    'login',                  'POST', '203.0.113.88', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 13 DAY)),
(7, 6,    'Scheduled an interview',                'applicants/review',      'POST', '203.0.113.88', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 12 DAY)),
(4, 3,    'Logged in with OTP',                    'login',                  'POST', '198.51.100.44', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)',   DATE_SUB(@now, INTERVAL 11 DAY)),
(4, 3,    'Viewed employee dashboard',             'dashboard/employee',     'GET',  '198.51.100.44', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)',   DATE_SUB(@now, INTERVAL 11 DAY)),
(6, 5,    'Assigned an onboarding document',       'assigned-requests',      'POST', '203.0.113.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 10 DAY)),
(1, NULL, 'Updated system settings',               'settings',               'POST', '203.0.113.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 9 DAY)),
(6, 5,    'Logged in with OTP',                    'login',                  'POST', '203.0.113.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 8 DAY)),
(5, 4,    'Logged in with OTP',                    'login',                  'POST', '198.51.100.19', 'Mozilla/5.0 (Linux; Android 12)',            DATE_SUB(@now, INTERVAL 6 DAY)),
(5, 4,    'Submitted a payroll concern',           'employee-requests',      'POST', '198.51.100.19', 'Mozilla/5.0 (Linux; Android 12)',            DATE_SUB(@now, INTERVAL 5 DAY)),
(1, NULL, 'Reviewed applicant profile',            'applicants/review',      'GET',  '203.0.113.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 4 DAY)),
(6, 5,    'Rejected an application',               'applicants/review',      'POST', '203.0.113.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 4 DAY)),
(3, 2,    'Logged in with OTP',                    'login',                  'POST', '198.51.100.31', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 13_5)', DATE_SUB(@now, INTERVAL 3 DAY)),
(1, NULL, 'Viewed recruitment dashboard',          'recruitment',            'GET',  '203.0.113.10', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 2 DAY)),
(6, 5,    'Logged in with OTP',                    'login',                  'POST', '203.0.113.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 1 DAY)),
(6, 5,    'Viewed onboarding status',              'onboarding',             'GET',  '203.0.113.24', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)', DATE_SUB(@now, INTERVAL 6 HOUR));


-- =====================================================================
-- 18. APPLICATION VALIDATIONS (AI pre-screening audit)
-- =====================================================================
-- Linked to the applications they cleared, so they are never removed by
-- the unlinked-row cleanup. One INVALID row demonstrates a blocked
-- submission; the rest are VALID or REVIEW.
-- =====================================================================

INSERT INTO application_validations (
    submission_reference,
    application_id,
    posting_id,
    applicant_name,
    applicant_email,
    ip_address,
    validation_status,
    identity_status,
    education_status,
    experience_status,
    credential_status,
    consistency_score,
    findings,
    validation_summary,
    ai_model,
    processed_at
) VALUES
('VAL-9F3C1A7B2E', 1,  1, 'Juan Dela Cruz',     'juan.delacruz@gmail.com',     '203.0.113.51', 'VALID',  'Consistent', 'Consistent', 'Consistent', 'Consistent', 96.00,
    JSON_ARRAY(JSON_OBJECT('category','identity','severity','info','message','Name on the resume matches the government ID.'), JSON_OBJECT('category','education','severity','info','message','Diploma is consistent with the declared degree.')),
    'All documents are consistent with the submitted application details.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 70 DAY)),
('VAL-2B7D4E9A1C', 2,  1, 'Maria Santos',       'maria.santos@yahoo.com',      '203.0.113.52', 'VALID',  'Consistent', 'Consistent', 'Consistent', 'Consistent', 91.00,
    JSON_ARRAY(JSON_OBJECT('category','education','severity','info','message','Diploma matches the declared program and institution.')),
    'Documents are consistent; no material discrepancies were found.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 12 DAY)),
('VAL-5A1E8C3D7F', 4,  1, 'Beatrice Ramos',     'beatrice.ramos@outlook.com',  '203.0.113.53', 'REVIEW', 'Consistent', 'Minor Differences', 'Consistent', 'Consistent', 78.00,
    JSON_ARRAY(JSON_OBJECT('category','education','severity','minor','message','Degree title differs slightly from the preferred program.')),
    'Minor differences noted; recommend a quick manual check of the diploma.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 7 DAY)),
('VAL-C4F2B6D09A', 8,  3, 'Leonardo Navarro',   'leonardo.navarro@gmail.com',  '203.0.113.54', 'VALID',  'Consistent', 'Consistent', 'Consistent', 'Consistent', 94.00,
    JSON_ARRAY(JSON_OBJECT('category','experience','severity','info','message','Portfolio links are reachable and relevant.')),
    'Documents and portfolio are consistent with the application.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 60 DAY)),
('VAL-7E3A9B1C5D', 9,  3, 'Angela Fernandez',   'angela.fernandez@gmail.com',  '203.0.113.55', 'REVIEW', 'Consistent', 'Consistent', 'Minor Differences', 'Consistent', 80.00,
    JSON_ARRAY(JSON_OBJECT('category','experience','severity','minor','message','Employment dates overlap by one month and should be confirmed.')),
    'One timeline overlap was detected; recommend confirming with the applicant.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 10 DAY)),
('VAL-1D6F8A2E4B', 11, 4, 'Ana Bautista',       'ana.bautista@gmail.com',      '203.0.113.56', 'VALID',  'Consistent', 'Consistent', 'Consistent', 'Consistent', 93.00,
    JSON_ARRAY(JSON_OBJECT('category','education','severity','info','message','Diploma matches the declared psychology program.')),
    'All documents are consistent with the submitted application details.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 55 DAY)),
('VAL-8B2C4D6E0F', 14, 6, 'Michelle Torres',    'michelle.torres@yahoo.com',   '203.0.113.57', 'INVALID','Consistent', 'Conflict',   'Not Checked', 'Conflict', 41.00,
    JSON_ARRAY(JSON_OBJECT('category','education','severity','major','message','Submitted certificate is not an acceptable academic credential.'), JSON_OBJECT('category','credential','severity','major','message','No finance-related qualification was provided.')),
    'The submitted document does not satisfy the academic requirement for this role.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 20 DAY)),
('VAL-3F5A7C9E1B', 15, 6, 'Daniel Mercado',     'daniel.mercado@gmail.com',    '203.0.113.58', 'VALID',  'Consistent', 'Consistent', 'Consistent', 'Consistent', 89.00,
    JSON_ARRAY(JSON_OBJECT('category','credential','severity','info','message','CPA certification is consistent with the declared qualification.')),
    'Documents are consistent; the professional certification was verified against the provided copy.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 11 DAY)),
('VAL-6C8E0A2B4D', 19, 7, 'Sofia Cabrera',      'sofia.cabrera@gmail.com',     '203.0.113.59', 'VALID',  'Consistent', 'Consistent', 'Consistent', 'Consistent', 92.00,
    JSON_ARRAY(JSON_OBJECT('category','identity','severity','info','message','Name and contact details match the application form.')),
    'All documents are consistent with the submitted application details.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 45 DAY)),
('VAL-0A2B4C6D8E', 24, 2, 'Rafael Mendoza',     'rafael.mendoza@gmail.com',    '203.0.113.60', 'VALID',  'Consistent', 'Consistent', 'Consistent', 'Consistent', 95.00,
    JSON_ARRAY(JSON_OBJECT('category','credential','severity','info','message','Professional certification copy matches the declared credential.')),
    'Documents are consistent; the candidate''s credentials support the seniority of the role.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 145 DAY));


-- =====================================================================
-- 19. PASSWORD RESET REQUESTS ("Contact HR")
-- =====================================================================
-- Shows up on the Employee Requests page for ADMIN / HR. One row is
-- already completed so both states are visible.
-- =====================================================================

INSERT INTO password_reset_requests (
    full_name,
    identifier,
    contact,
    message,
    status,
    created_at,
    resolved_at,
    resolved_by
) VALUES
('Kristine Aquino', 'kristine.aquino',      'kristine.aquino@gmail.com',  'I no longer have access to the email address on my account and need help signing in.', 'Pending',  DATE_SUB(@now, INTERVAL 1 DAY),  NULL, NULL),
('Nathan Manalo',   'nathan.manalo',        '0905-662-1147',              'I forgot my password after being away for a few weeks. Please help me reset it.',      'Pending',  DATE_SUB(@now, INTERVAL 3 HOUR), NULL, NULL),
('Paolo Mendoza',   'paolo.mendoza',        'paolo.mendoza@outlook.com',  'Requesting a password reset for my applicant account.',                                'Completed', DATE_SUB(@now, INTERVAL 6 DAY),  DATE_SUB(@now, INTERVAL 5 DAY), 1);


-- =====================================================================
-- 20. ONBOARDING DOCUMENT ENRICHMENT
-- =====================================================================
-- Fill the submission/review columns and file paths deterministically,
-- based on status, so the Assigned Requests screen is fully populated
-- without depending on a second seed file.
-- =====================================================================

-- Every document the employee actually submitted has a stored file.
UPDATE onboarding_documents
SET
    file_path = CONCAT('onboarding_document_', document_id, '.pdf'),
    submission_mime = 'application/pdf',
    submission_file_size = 780 + (document_id * 7)
WHERE submitted_at IS NOT NULL;

-- Approved / Rejected documents were reviewed by HR.
UPDATE onboarding_documents
SET
    reviewed_by = assigned_by,
    reviewed_at = DATE_ADD(created_at, INTERVAL 4 DAY)
WHERE status IN ('Approved', 'Rejected');

-- Response-only requests keep the reviewer/assignee traceable.
UPDATE onboarding_documents
SET response_text = 'Noted and verified against the submitted record.'
WHERE status = 'Approved'
  AND response_text IS NULL;


-- =====================================================================
-- 21. EMPLOYEE NUMBER SEQUENCE
-- =====================================================================
-- Six employees were seeded above, so the next generated number is 7.

UPDATE employee_number_sequence
SET next_number = 7
WHERE sequence_id = 1;


-- =====================================================================
-- END OF SEED DATA
-- =====================================================================
