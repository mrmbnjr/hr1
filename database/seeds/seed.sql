-- NOTE: no CREATE DATABASE / USE here. Select your database in phpMyAdmin first, then import.
-- =====================================================================
-- RAM-YUM HRMS (hr1_db) — Seed Data aligned to MMS Team 1
-- =====================================================================
-- Merchandising Management System (MMS)
--   Human Resource Management System (HRMS / HCM)  ->  TEAM 1
--
-- Import into an EMPTY database, right after schema.sql.
-- The schema is NOT modified by this file.
--
-- MODULE -> TABLE MAP (Team 1)
--   Recruitment & Onboarding
--     Applicant Management      applicants, applications, application_validations,
--                               ai_screening, academic_document_evaluations
--     Recruitment Management    departments, positions, job_postings, interviews
--     New Hire Onboarding       onboarding, onboarding_phases, onboarding_documents
--   Core HR
--     Core Human Capital Mgmt   roles, users, employees, employee_number_sequence
--     Employee Self-Service     employee_requests, password_reset_requests
--     Employee Records Mgmt     employees + onboarding_documents (per employee),
--                               activity_logs
--
-- The sample company is a small Korean restaurant / merchandising business.
-- Departments mirror the other MMS subsystems (HR, Finance, Supply Chain &
-- Inventory, Fleet & Transportation, Facilities & Administration, Sales &
-- Merchandising) so the HR data lines up with the rest of the system.
--
-- All dates are RELATIVE to the moment of import (NOW()).
--   * Open postings always have a future deadline.
--   * Rejected applications sit on an OPEN posting with a recent rejected_at,
--     so they stay visible (see ApplicationRetentionService).
--   * Closed postings only hold Hired applications, which are never purged.
--
-- Shared password for every seeded account: admin123
--
-- Demo accounts:
--   admin             (ADMIN)
--   rafael.mendoza    (MGR)  Store Manager
--   alyssa.bautista   (MGR)  Store Manager
--   maria.santos      (HR)   HR Assistant
--   juan.delacruz     (EMP)  Inventory Clerk        (onboarding in progress)
--   sofia.cabrera     (EMP)  Delivery Coordinator  (onboarding pending)
-- =====================================================================

SET @now := NOW();


-- =====================================================================
-- 1. CORE HCM — ROLES, ADMIN USER
-- =====================================================================
-- 1 = ADMIN, 2 = HR, 3 = MGR, 4 = EMP

INSERT INTO roles (role_code, role_name) VALUES
('ADMIN', 'System Administrator'),
('HR',    'HR Staff'),
('MGR',   'Manager'),
('EMP',   'Employee');

-- user_id 1 = admin
INSERT INTO users (
    employee_id, role_id, username, email, email_verified_at,
    password, status, must_change_password, created_at
) VALUES (
    NULL, 1, 'admin', 'mariodejarlomabinijr@gmail.com',
    DATE_SUB(@now, INTERVAL 200 DAY),
    '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa',
    'Active', 0, DATE_SUB(@now, INTERVAL 200 DAY)
);


-- =====================================================================
-- 2. RECRUITMENT MANAGEMENT — DEPARTMENTS & POSITIONS
-- =====================================================================
-- department_id:
-- 1 = Human Resources
-- 2 = Finance
-- 3 = Supply Chain & Inventory
-- 4 = Fleet & Transportation
-- 5 = Facilities & Administration
-- 6 = Sales & Merchandising

INSERT INTO departments (department_name, status) VALUES
('Human Resources',              'Active'),
('Finance',                      'Active'),
('Supply Chain & Inventory',     'Active'),
('Fleet & Transportation',       'Active'),
('Facilities & Administration',  'Active'),
('Sales & Merchandising',        'Active');

-- role_id = intended system role for the position (2 HR, 3 MGR, 4 EMP).
-- Positions 2, 4, 5, 7, 9, 10 and 12 have no posting yet, so "Create Job"
-- stays usable in the demo.
--
-- position_id:
-- 1  = HR Assistant                     (HR)
-- 2  = Shop Manager                     (HR,  MGR)
-- 3  = Bookkeeper                       (Finance)
-- 4  = Accounts Assistant               (Finance)
-- 5  = Stockroom Assistant              (Supply Chain & Inventory)
-- 6  = Inventory Clerk                  (Supply Chain & Inventory)
-- 7  = Purchasing Clerk                 (Supply Chain & Inventory)
-- 8  = Delivery Coordinator             (Fleet & Transportation)
-- 9  = Delivery Driver                  (Fleet & Transportation)
-- 10 = Office Assistant                 (Facilities & Administration)
-- 11 = Store Manager                    (Sales & Merchandising, MGR)
-- 12 = Sales Associate                  (Sales & Merchandising)

INSERT INTO positions (department_id, role_id, position_name) VALUES
(1, 2, 'HR Assistant'),
(1, 3, 'Shop Manager'),
(2, 4, 'Bookkeeper'),
(2, 4, 'Accounts Assistant'),
(3, 4, 'Stockroom Assistant'),
(3, 4, 'Inventory Clerk'),
(3, 4, 'Purchasing Clerk'),
(4, 4, 'Delivery Coordinator'),
(4, 4, 'Delivery Driver'),
(5, 4, 'Office Assistant'),
(6, 3, 'Store Manager'),
(6, 4, 'Sales Associate');


-- =====================================================================
-- 3. RECRUITMENT MANAGEMENT — JOB POSTINGS
-- =====================================================================
-- One posting per position (uq_job_postings_position).
-- 1 = Inventory Clerk                 (Open)
-- 2 = Store Manager                   (Closed, filled)
-- 3 = HR Assistant                    (Closed, filled)
-- 4 = Delivery Coordinator            (Open)
-- 5 = Bookkeeper                      (Open)

INSERT INTO job_postings (
    position_id, title, description, requirements,
    academic_document_required, employment_type, vacancies, max_applicants,
    status, closed_at, application_token, application_deadline,
    created_by, created_at
) VALUES
(6, 'Inventory Clerk',
 'Keep the shop stockroom organized, record incoming products, and help the owner maintain accurate stock counts.',
 'Comfort with basic spreadsheets, careful record keeping, and some retail, stockroom, or inventory experience.',
 TRUE, 'Full-Time', 5, 30, 'Open', NULL,
 'a1f9c3d84e2b4a1f9d3c7e6b0a5f2c11',
 DATE_ADD(CURDATE(), INTERVAL 14 DAY), 1, DATE_SUB(@now, INTERVAL 60 DAY)),

(11, 'Store Manager',
 'Run daily shop operations, support the sales team, manage suppliers, and keep service and store standards on track.',
 'Several years in retail or small-shop operations, dependable leadership, and basic sales and stock reporting skills.',
 TRUE, 'Full-Time', 2, 15, 'Closed', DATE_SUB(@now, INTERVAL 150 DAY),
 'b2e8d4c95f3a4b2e8c4d6f7a1b6e3d22',
 DATE_SUB(CURDATE(), INTERVAL 160 DAY), 1, DATE_SUB(@now, INTERVAL 190 DAY)),

(1, 'HR Assistant',
 'Help a small business hire and onboard staff, maintain employee files, and answer everyday team questions.',
 'Organized record keeping, clear communication, and experience with office or people-support tasks.',
 TRUE, 'Full-Time', 1, 20, 'Closed', DATE_SUB(@now, INTERVAL 110 DAY),
 'c3d7e5b06a4c5d3f9e5c8b2a7f4d1e33',
 DATE_SUB(CURDATE(), INTERVAL 125 DAY), 1, DATE_SUB(@now, INTERVAL 150 DAY)),

(8, 'Delivery Coordinator',
 'Plan local deliveries, coordinate drivers and orders, and keep customers updated on delivery times.',
 'Good organization, a helpful customer manner, and familiarity with local roads or delivery scheduling.',
 FALSE, 'Full-Time', 5, 30, 'Open', NULL,
 'd4c6f6a17b5d6e4a0f6d9c3b8a5e2f44',
 DATE_ADD(CURDATE(), INTERVAL 10 DAY), 1, DATE_SUB(@now, INTERVAL 40 DAY)),

(3, 'Bookkeeper',
 'Record daily sales and expenses, organize receipts, and help the owner keep simple accounts up to date.',
 'Basic bookkeeping knowledge, spreadsheet skills, and careful handling of financial records.',
 TRUE, 'Full-Time', 3, 30, 'Open', NULL,
 'e5b5a7328c6e7f5b1a7e0d4c9b6f3a55',
 DATE_ADD(CURDATE(), INTERVAL 8 DAY), 1, DATE_SUB(@now, INTERVAL 30 DAY));


-- =====================================================================
-- 4. APPLICANT MANAGEMENT — APPLICANTS & APPLICATIONS
-- =====================================================================
-- applicant_id = application_id (1:1):
-- 1  = Juan Dela Cruz      6  = Angela Fernandez
-- 2  = Maria Santos        7  = Timothy Ramos
-- 3  = Rafael Mendoza      8  = Daniel Mercado
-- 4  = Sofia Cabrera       9  = Camille Domingo
-- 5  = Jose Reyes          10 = Paolo Mendoza

INSERT INTO applicants (
    first_name, middle_name, last_name, email, phone, address, created_at
) VALUES
('Juan',     'Ramirez', 'Dela Cruz', 'juan.delacruz@gmail.com',   '0917 345 1201', 'Quezon City, Metro Manila',      DATE_SUB(@now, INTERVAL 50 DAY)),
('Maria',    'Lopez',   'Santos',    'maria.santos@yahoo.com',    '0918-234-5510', 'Manila, Metro Manila',           DATE_SUB(@now, INTERVAL 140 DAY)),
('Rafael',   'Miguel',  'Mendoza',   'rafael.mendoza@gmail.com',  '0917 662 3348', 'Makati City, Metro Manila',      DATE_SUB(@now, INTERVAL 170 DAY)),
('Sofia',    'Isabel',  'Cabrera',   'sofia.cabrera@gmail.com',   '0917 778 2234', 'Muntinlupa City, Metro Manila',  DATE_SUB(@now, INTERVAL 25 DAY)),
('Jose',     'Antonio', 'Reyes',     'jose.reyes@gmail.com',      '0920 887 4412', 'Makati City, Metro Manila',      DATE_SUB(@now, INTERVAL 9 DAY)),
('Angela',   'Rose',    'Fernandez', 'angela.fernandez@gmail.com','0917-233-8890', 'San Juan City, Metro Manila',    DATE_SUB(@now, INTERVAL 6 DAY)),
('Timothy',  'Paul',    'Ramos',     'timothy.ramos@yahoo.com',   '0917-802-3345', 'Caloocan City, Metro Manila',    DATE_SUB(@now, INTERVAL 4 DAY)),
('Daniel',   'Rafael',  'Mercado',   'daniel.mercado@gmail.com',  '0908 221 7765', 'Las Pinas City, Metro Manila',   DATE_SUB(@now, INTERVAL 12 DAY)),
('Camille',  'Joy',     'Domingo',   'camille.domingo@gmail.com', '0917-990-2231', 'Bacoor, Cavite',                 DATE_SUB(@now, INTERVAL 3 DAY)),
('Paolo',    'Miguel',  'Mendoza',   'paolo.mendoza@outlook.com', '0917-321-5590', 'Marikina City, Metro Manila',    DATE_SUB(@now, INTERVAL 2 DAY));

-- 50 additional candidates, allowing a demo with 50 non-hired applicants
-- plus 10 hires. Names and contact details are synthetic demo data.
INSERT INTO applicants (
    first_name, middle_name, last_name, email, phone, address, created_at
) VALUES
('Alyssa', NULL, 'Bautista', 'candidate11@example.com', '09170000011', 'Quezon City, Metro Manila', DATE_SUB(@now, INTERVAL 180 DAY)),
('Marco', NULL, 'Villanueva', 'candidate12@example.com', '09170000012', 'Manila, Metro Manila', DATE_SUB(@now, INTERVAL 48 DAY)),
('Bianca', NULL, 'Navarro', 'candidate13@example.com', '09170000013', 'Makati City, Metro Manila', DATE_SUB(@now, INTERVAL 47 DAY)),
('Paolo', NULL, 'Castillo', 'candidate14@example.com', '09170000014', 'Pasig City, Metro Manila', DATE_SUB(@now, INTERVAL 46 DAY)),
('Jasmine', NULL, 'Rivera', 'candidate15@example.com', '09170000015', 'Muntinlupa City, Metro Manila', DATE_SUB(@now, INTERVAL 45 DAY)),
('Noel', NULL, 'Garcia', 'candidate16@example.com', '09170000016', 'Marikina City, Metro Manila', DATE_SUB(@now, INTERVAL 44 DAY)),
('Trisha', NULL, 'Mendoza', 'candidate17@example.com', '09170000017', 'Caloocan City, Metro Manila', DATE_SUB(@now, INTERVAL 43 DAY)),
('Ramon', NULL, 'Santos', 'candidate18@example.com', '09170000018', 'Taguig City, Metro Manila', DATE_SUB(@now, INTERVAL 42 DAY)),
('Ella', NULL, 'Reyes', 'candidate19@example.com', '09170000019', 'Pasay City, Metro Manila', DATE_SUB(@now, INTERVAL 41 DAY)),
('Gabriel', NULL, 'Flores', 'candidate20@example.com', '09170000020', 'Paranaque City, Metro Manila', DATE_SUB(@now, INTERVAL 40 DAY)),
('Mika', NULL, 'Aquino', 'candidate21@example.com', '09170000021', 'San Juan City, Metro Manila', DATE_SUB(@now, INTERVAL 39 DAY)),
('Enzo', NULL, 'Ramos', 'candidate22@example.com', '09170000022', 'Valenzuela City, Metro Manila', DATE_SUB(@now, INTERVAL 38 DAY)),
('Angela', NULL, 'Cruz', 'candidate23@example.com', '09170000023', 'Las Pinas City, Metro Manila', DATE_SUB(@now, INTERVAL 37 DAY)),
('Joshua', NULL, 'Torres', 'candidate24@example.com', '09170000024', 'Quezon City, Metro Manila', DATE_SUB(@now, INTERVAL 36 DAY)),
('Bea', NULL, 'Diaz', 'candidate25@example.com', '09170000025', 'Manila, Metro Manila', DATE_SUB(@now, INTERVAL 35 DAY)),
('Carlo', NULL, 'Gonzales', 'candidate26@example.com', '09170000026', 'Makati City, Metro Manila', DATE_SUB(@now, INTERVAL 34 DAY)),
('Nina', NULL, 'Pascual', 'candidate27@example.com', '09170000027', 'Pasig City, Metro Manila', DATE_SUB(@now, INTERVAL 33 DAY)),
('Luis', NULL, 'Fernandez', 'candidate28@example.com', '09170000028', 'Muntinlupa City, Metro Manila', DATE_SUB(@now, INTERVAL 32 DAY)),
('Camille', NULL, 'Lopez', 'candidate29@example.com', '09170000029', 'Marikina City, Metro Manila', DATE_SUB(@now, INTERVAL 31 DAY)),
('Andre', NULL, 'Hernandez', 'candidate30@example.com', '09170000030', 'Caloocan City, Metro Manila', DATE_SUB(@now, INTERVAL 30 DAY)),
('Faith', NULL, 'Morales', 'candidate31@example.com', '09170000031', 'Taguig City, Metro Manila', DATE_SUB(@now, INTERVAL 29 DAY)),
('Miguel', NULL, 'Aquino', 'candidate32@example.com', '09170000032', 'Pasay City, Metro Manila', DATE_SUB(@now, INTERVAL 28 DAY)),
('Joy', NULL, 'Salazar', 'candidate33@example.com', '09170000033', 'Paranaque City, Metro Manila', DATE_SUB(@now, INTERVAL 27 DAY)),
('Dylan', NULL, 'Mercado', 'candidate34@example.com', '09170000034', 'San Juan City, Metro Manila', DATE_SUB(@now, INTERVAL 26 DAY)),
('Katrina', NULL, 'Manalo', 'candidate35@example.com', '09170000035', 'Valenzuela City, Metro Manila', DATE_SUB(@now, INTERVAL 25 DAY)),
('Francis', NULL, 'Domingo', 'candidate36@example.com', '09170000036', 'Las Pinas City, Metro Manila', DATE_SUB(@now, INTERVAL 24 DAY)),
('Rhea', NULL, 'Santiago', 'candidate37@example.com', '09170000037', 'Quezon City, Metro Manila', DATE_SUB(@now, INTERVAL 23 DAY)),
('Mark', NULL, 'Villanueva', 'candidate38@example.com', '09170000038', 'Manila, Metro Manila', DATE_SUB(@now, INTERVAL 22 DAY)),
('Celine', NULL, 'Dela Rosa', 'candidate39@example.com', '09170000039', 'Makati City, Metro Manila', DATE_SUB(@now, INTERVAL 21 DAY)),
('Arvin', NULL, 'Gutierrez', 'candidate40@example.com', '09170000040', 'Pasig City, Metro Manila', DATE_SUB(@now, INTERVAL 20 DAY)),
('Mae', NULL, 'Valdez', 'candidate41@example.com', '09170000041', 'Muntinlupa City, Metro Manila', DATE_SUB(@now, INTERVAL 19 DAY)),
('Joseph', NULL, 'Cortez', 'candidate42@example.com', '09170000042', 'Marikina City, Metro Manila', DATE_SUB(@now, INTERVAL 18 DAY)),
('Lara', NULL, 'Aguilar', 'candidate43@example.com', '09170000043', 'Caloocan City, Metro Manila', DATE_SUB(@now, INTERVAL 17 DAY)),
('Vincent', NULL, 'Padilla', 'candidate44@example.com', '09170000044', 'Taguig City, Metro Manila', DATE_SUB(@now, INTERVAL 16 DAY)),
('Patricia', NULL, 'Luna', 'candidate45@example.com', '09170000045', 'Pasay City, Metro Manila', DATE_SUB(@now, INTERVAL 15 DAY)),
('Eric', NULL, 'Bautista', 'candidate46@example.com', '09170000046', 'Paranaque City, Metro Manila', DATE_SUB(@now, INTERVAL 14 DAY)),
('Shaina', NULL, 'Castro', 'candidate47@example.com', '09170000047', 'San Juan City, Metro Manila', DATE_SUB(@now, INTERVAL 13 DAY)),
('Kenneth', NULL, 'Ocampo', 'candidate48@example.com', '09170000048', 'Valenzuela City, Metro Manila', DATE_SUB(@now, INTERVAL 12 DAY)),
('Diana', NULL, 'Estrada', 'candidate49@example.com', '09170000049', 'Las Pinas City, Metro Manila', DATE_SUB(@now, INTERVAL 11 DAY)),
('Julius', NULL, 'Pineda', 'candidate50@example.com', '09170000050', 'Quezon City, Metro Manila', DATE_SUB(@now, INTERVAL 10 DAY)),
('Therese', NULL, 'Cabrera', 'candidate51@example.com', '09170000051', 'Manila, Metro Manila', DATE_SUB(@now, INTERVAL 9 DAY)),
('Adrian', NULL, 'Soriano', 'candidate52@example.com', '09170000052', 'Makati City, Metro Manila', DATE_SUB(@now, INTERVAL 8 DAY)),
('Mara', NULL, 'Rosales', 'candidate53@example.com', '09170000053', 'Pasig City, Metro Manila', DATE_SUB(@now, INTERVAL 7 DAY)),
('Rico', NULL, 'Jimenez', 'candidate54@example.com', '09170000054', 'Muntinlupa City, Metro Manila', DATE_SUB(@now, INTERVAL 6 DAY)),
('Isabel', NULL, 'Velasco', 'candidate55@example.com', '09170000055', 'Marikina City, Metro Manila', DATE_SUB(@now, INTERVAL 5 DAY)),
('Nathan', NULL, 'Bernardo', 'candidate56@example.com', '09170000056', 'Caloocan City, Metro Manila', DATE_SUB(@now, INTERVAL 4 DAY)),
('Chloe', NULL, 'Abad', 'candidate57@example.com', '09170000057', 'Taguig City, Metro Manila', DATE_SUB(@now, INTERVAL 3 DAY)),
('Derek', NULL, 'Ferrer', 'candidate58@example.com', '09170000058', 'Pasay City, Metro Manila', DATE_SUB(@now, INTERVAL 2 DAY)),
('Samantha', NULL, 'Tan', 'candidate59@example.com', '09170000059', 'Paranaque City, Metro Manila', DATE_SUB(@now, INTERVAL 1 DAY)),
('Brent', NULL, 'Lim', 'candidate60@example.com', '09170000060', 'San Juan City, Metro Manila', @now);

-- Posting 1 (Inventory Clerk, Open): hired and active candidates
-- Posting 2 (Store Manager, Closed): hired only
-- Posting 3 (HR Assistant, Closed): one historical hire
-- Posting 4 (Delivery Coordinator, Open): hired and active candidates
-- Posting 5 (Bookkeeper, Open): hired and active candidates
INSERT INTO applications (
    applicant_id, posting_id, resume_file, cover_letter_file,
    academic_document_file, application_status, rejected_at, applied_at
) VALUES
(1,  1, 'resume_1_5f3a9c2b7e1d4086.pdf',  'cover_1_1a2b3c4d5e6f7089.pdf',  'academic_1_9f8e7d6c5b4a3021.pdf',  'Hired',        NULL, DATE_SUB(@now, INTERVAL 50 DAY)),
(2,  3, 'resume_2_7d1b4e8a2c6f9035.pdf',  'cover_2_4d5e6f708192a3b4.pdf',  'academic_2_3c2d1e0f9a8b7c6d.pdf',  'Hired',        NULL, DATE_SUB(@now, INTERVAL 140 DAY)),
(3,  2, 'resume_3_2c8e5a1d9f3b7064.pdf',  'cover_3_7e8f9012a3b4c5d6.pdf',  'academic_3_6b5a4c3d2e1f0098.pdf',  'Hired',        NULL, DATE_SUB(@now, INTERVAL 170 DAY)),
(4,  4, 'resume_4_8b2f6c3e1a9d5072.pdf',  'cover_4_a1b2c3d4e5f60718.pdf',  NULL,                              'Hired',        NULL, DATE_SUB(@now, INTERVAL 25 DAY)),
(5,  1, 'resume_5_1a9d4b7e2c6f8053.pdf',  'cover_5_b2c3d4e5f6071829.pdf',  'academic_5_2e3d4c5b6a7f9081.pdf',  'Interview',    NULL, DATE_SUB(@now, INTERVAL 9 DAY)),
(6,  1, 'resume_6_3e7c1a5d9b2f4068.pdf',  NULL,                            'academic_6_8a7b6c5d4e3f2019.pdf',  'Under Review', NULL, DATE_SUB(@now, INTERVAL 6 DAY)),
(7,  1, 'resume_7_9c3f8b2e6a1d5047.pdf',  NULL,                            'academic_7_4d3e2f1a0b9c8d7e.pdf',  'Rejected',     DATE_SUB(@now, INTERVAL 1 DAY), DATE_SUB(@now, INTERVAL 4 DAY)),
(8,  5, 'resume_8_4b8e2c6f1a9d3057.pdf',  'cover_8_c3d4e5f607182a3b.pdf',  'academic_8_6e7f8091a2b3c4d5.pdf',  'Interview',    NULL, DATE_SUB(@now, INTERVAL 12 DAY)),
(9,  5, 'resume_9_6f1a4d8b2c7e5093.pdf',  NULL,                            'academic_9_5d6e7f8091a2b3c4.pdf',  'Submitted',    NULL, DATE_SUB(@now, INTERVAL 3 DAY)),
(10, 4, 'resume_10_2e6b9c3f1a8d4075.pdf', NULL,                            NULL,                              'Submitted',    NULL, DATE_SUB(@now, INTERVAL 2 DAY));

-- Add the 50 extra applicants to applications 11-60. Six are hired and the
-- remaining 44 are non-hired; their posting counts stay under each limit.
INSERT INTO applications (
    applicant_id, posting_id, resume_file, cover_letter_file,
    academic_document_file, application_status, rejected_at, applied_at
)
SELECT
    a.applicant_id,
    CASE
        WHEN a.applicant_id = 11 THEN 2
        WHEN a.applicant_id IN (12, 16) THEN 1
        WHEN a.applicant_id IN (13, 14) THEN 4
        WHEN a.applicant_id = 15 THEN 5
        WHEN MOD(a.applicant_id, 3) = 0 THEN 1
        WHEN MOD(a.applicant_id, 3) = 1 THEN 4
        ELSE 5
    END,
    CONCAT('resume_', a.applicant_id, '_seed.pdf'),
    CASE WHEN MOD(a.applicant_id, 2) = 0
        THEN CONCAT('cover_', a.applicant_id, '_seed.pdf')
        ELSE NULL
    END,
    CASE
        WHEN a.applicant_id IN (11, 12, 15, 16)
          OR (a.applicant_id NOT IN (11, 13, 14, 15)
              AND MOD(a.applicant_id, 3) IN (0, 2))
            THEN CONCAT('academic_', a.applicant_id, '_seed.pdf')
        ELSE NULL
    END,
    CASE
        WHEN a.applicant_id BETWEEN 11 AND 16 THEN 'Hired'
        WHEN a.applicant_id BETWEEN 17 AND 26 THEN 'Submitted'
        WHEN MOD(a.applicant_id, 4) = 0 THEN 'Rejected'
        WHEN MOD(a.applicant_id, 4) = 1 THEN 'Interview'
        ELSE 'Under Review'
    END,
    CASE WHEN a.applicant_id > 16 AND MOD(a.applicant_id, 4) = 0
        THEN DATE_SUB(@now, INTERVAL 1 DAY)
        ELSE NULL
    END,
    CASE WHEN a.applicant_id = 11
        THEN DATE_SUB(@now, INTERVAL 170 DAY)
        ELSE DATE_SUB(@now, INTERVAL (60 - a.applicant_id) DAY)
    END
FROM applicants a
WHERE a.applicant_id BETWEEN 11 AND 60;

-- Detailed AI screening examples; remaining applications receive completed
-- generated screening records below.
INSERT INTO ai_screening (
    application_id, overall_score, skills_score, experience_score,
    education_score, keyword_score, recommendation, extracted_skills,
    strengths, concerns, ai_summary, processed_at
) VALUES
(1, 92.50, 95.00, 90.00, 90.00, 95.00, 'Highly Recommended',
 'Inventory Tracking, Stock Control, MS Excel, Purchase Orders, Receiving & Inspection',
 JSON_ARRAY('Hands-on stock control and cycle-count experience', 'Strong spreadsheet and reporting skills'),
 JSON_ARRAY('Limited exposure to barcode scanning systems'),
 'Candidate demonstrates strong alignment with the Inventory Clerk role.',
 DATE_SUB(@now, INTERVAL 50 DAY)),
(2, 89.00, 88.00, 86.00, 90.00, 90.00, 'Highly Recommended',
 'Recruitment, Employee Relations, HRIS, Onboarding',
 JSON_ARRAY('Direct HR internship experience', 'Strong communication skills'),
 JSON_ARRAY('No certification in HR management yet'),
 'Candidate is well qualified for the HR Assistant role.',
 DATE_SUB(@now, INTERVAL 140 DAY)),
(3, 91.00, 89.00, 92.00, 90.00, 92.00, 'Highly Recommended',
 'Team Leadership, Campaign Planning, Pricing Strategy, Supplier Negotiation',
 JSON_ARRAY('Proven team leadership', 'Managed promotions and supplier contracts'),
 JSON_ARRAY('Limited experience with HR-specific platforms'),
 'Candidate is highly aligned with the Store Manager role.',
 DATE_SUB(@now, INTERVAL 170 DAY)),
(4, 83.00, 82.00, 80.00, 85.00, 83.00, 'Recommended',
 'Scheduling, Route Knowledge, Dispatch Coordination, MS Office',
 JSON_ARRAY('Good organizational skills', 'Familiar with delivery scheduling'),
 JSON_ARRAY('Limited full-time dispatch experience'),
 'Candidate has solid coordination skills suitable for the Delivery Coordinator role.',
 DATE_SUB(@now, INTERVAL 25 DAY)),
(5, 87.00, 88.00, 85.00, 88.00, 87.00, 'Recommended',
 'Warehouse Operations, Inventory Counting, Excel, Procurement Support',
 JSON_ARRAY('Warehouse and receiving experience', 'Familiar with purchase order workflows'),
 JSON_ARRAY('Short tenure in the most recent role'),
 'Candidate is well aligned with the opening and merits an interview.',
 DATE_SUB(@now, INTERVAL 9 DAY)),
(6, 71.00, 68.00, 65.00, 80.00, 70.00, 'Consider',
 'MS Office, Basic Bookkeeping, Customer Service',
 JSON_ARRAY('Eagerness to learn', 'Relevant coursework in business administration'),
 JSON_ARRAY('No prior inventory experience listed'),
 'Candidate meets the educational requirement but needs more hands-on experience.',
 DATE_SUB(@now, INTERVAL 6 DAY)),
(7, 44.00, 40.00, 42.00, 55.00, 43.00, 'Not Recommended',
 'Customer Service, MS Office',
 JSON_ARRAY('Good communication background'),
 JSON_ARRAY('No inventory or warehouse experience', 'Resume does not address the job requirements'),
 'Candidate profile does not align with the Inventory Clerk requirements.',
 DATE_SUB(@now, INTERVAL 4 DAY)),
(8, 78.00, 76.00, 75.00, 82.00, 78.00, 'Recommended',
 'Bookkeeping, Excel, Accounts Payable, Accounts Receivable',
 JSON_ARRAY('Relevant accounting coursework', 'Analytical mindset'),
 JSON_ARRAY('Limited full-time accounting experience'),
 'Candidate has useful accounting foundations and should be assessed further.',
 DATE_SUB(@now, INTERVAL 12 DAY));

-- Every application, including those still marked Submitted, has a completed
-- screening record so opening the application does not require another AI run.
INSERT INTO ai_screening (
    application_id, overall_score, skills_score, experience_score,
    education_score, keyword_score, recommendation, extracted_skills,
    strengths, concerns, ai_summary, processed_at
)
SELECT
    scored.application_id,
    scored.overall_score,
    LEAST(100, scored.overall_score + 3),
    GREATEST(0, scored.overall_score - 4),
    LEAST(100, scored.overall_score + 1),
    scored.overall_score,
    CASE
        WHEN scored.overall_score >= 85 THEN 'Highly Recommended'
        WHEN scored.overall_score >= 75 THEN 'Recommended'
        WHEN scored.overall_score >= 60 THEN 'Consider'
        ELSE 'Not Recommended'
    END,
    'Customer service, record keeping, teamwork, and basic computer skills',
    JSON_ARRAY('Completed resume screening', 'Relevant small-business skills identified'),
    JSON_ARRAY(),
    'Screening results are saved and ready for recruiter review.',
    DATE_SUB(@now, INTERVAL (MOD(scored.application_id, 3)) DAY)
FROM (
    SELECT
        ap.application_id,
        CAST(60 + MOD(ap.application_id * 7, 35) AS DECIMAL(5,2)) AS overall_score
    FROM applications ap
    WHERE ap.application_id > 8
) scored;

-- Academic document evaluations for the original application examples.
INSERT INTO academic_document_evaluations (
    application_id, file_name, original_file_name, is_primary,
    evaluation_status, document_valid, confidence_score, document_type,
    institution, degree, field_of_study, graduation_year,
    extracted_details, concerns, ai_summary, processed_at
) VALUES
(1, 'academic_1_9f8e7d6c5b4a3021.pdf', 'Juan Dela Cruz - Diploma.pdf', TRUE, 'Evaluated', TRUE, 98.00, 'Diploma',
 'Polytechnic University of the Philippines', 'Bachelor of Science', 'Business Administration (Operations Management)', '2022',
 JSON_OBJECT('honors', 'Cum Laude'), JSON_ARRAY(),
 'Document appears authentic and supports the academic requirement.', DATE_SUB(@now, INTERVAL 49 DAY)),
(2, 'academic_2_3c2d1e0f9a8b7c6d.pdf', 'Maria Santos - Diploma.pdf', TRUE, 'Evaluated', TRUE, 96.00, 'Diploma',
 'University of Santo Tomas', 'Bachelor of Science', 'Psychology', '2021',
 JSON_OBJECT('major', 'Industrial Psychology'), JSON_ARRAY(),
 'Document is valid and relevant to the HR Assistant position.', DATE_SUB(@now, INTERVAL 139 DAY)),
(3, 'academic_3_6b5a4c3d2e1f0098.pdf', 'Rafael Mendoza - Diploma.pdf', TRUE, 'Evaluated', TRUE, 98.00, 'Diploma',
 'University of the Philippines Diliman', 'Bachelor of Science', 'Business Administration (Marketing)', '2008',
 JSON_OBJECT('honors', 'Magna Cum Laude'), JSON_ARRAY(),
 'Document is valid and supports the candidate''s merchandising leadership profile.', DATE_SUB(@now, INTERVAL 169 DAY)),
(5, 'academic_5_2e3d4c5b6a7f9081.pdf', 'Jose Reyes - Diploma.pdf', TRUE, 'Evaluated', TRUE, 97.00, 'Diploma',
 'De La Salle University', 'Bachelor of Science', 'Industrial Engineering', '2019',
 JSON_OBJECT('honors', 'Dean''s Lister'), JSON_ARRAY(),
 'Document is valid and supports the candidate''s operations background.', DATE_SUB(@now, INTERVAL 8 DAY)),
(6, 'academic_6_8a7b6c5d4e3f2019.pdf', 'Angela Fernandez - Diploma.pdf', TRUE, 'Evaluated', TRUE, 94.00, 'Diploma',
 'Ateneo de Manila University', 'Bachelor of Science', 'Business Administration', '2021',
 JSON_OBJECT(), JSON_ARRAY('Graduation year could not be fully cross-checked'),
 'Document is valid; the transcript matches the declared degree.', DATE_SUB(@now, INTERVAL 5 DAY)),
(7, 'academic_7_4d3e2f1a0b9c8d7e.pdf', 'Timothy Ramos - Certificate.pdf', TRUE, 'Evaluated', FALSE, 88.00, 'Certificate of Completion',
 'Private Training Institute', NULL, 'Computer Literacy', '2021',
 JSON_OBJECT(), JSON_ARRAY('Submitted document is not an acceptable academic credential'),
 'Document could not be accepted as evidence of the required degree.', DATE_SUB(@now, INTERVAL 3 DAY)),
(8, 'academic_8_6e7f8091a2b3c4d5.pdf', 'Daniel Mercado - Diploma.pdf', TRUE, 'Evaluated', TRUE, 94.00, 'Diploma',
 'Polytechnic University of the Philippines', 'Bachelor of Science', 'Accountancy', '2019',
 JSON_OBJECT('certification', 'CPA Board Passer'), JSON_ARRAY(),
 'Document is valid and supports the accounting requirement.', DATE_SUB(@now, INTERVAL 11 DAY));

-- Save completed evaluations for every other supplied academic document too.
INSERT INTO academic_document_evaluations (
    application_id, file_name, original_file_name, is_primary,
    evaluation_status, document_valid, confidence_score, document_type,
    institution, degree, field_of_study, graduation_year,
    extracted_details, concerns, ai_summary, processed_at
)
SELECT
    ap.application_id,
    ap.academic_document_file,
    CONCAT('Candidate ', ap.applicant_id, ' - Academic Record.pdf'),
    TRUE,
    'Evaluated',
    TRUE,
    92.00,
    'Academic Record',
    'Local Community College',
    'Certificate or Diploma',
    'Business and Operations',
    '2022',
    JSON_OBJECT('source', 'seeded demo evaluation'),
    JSON_ARRAY(),
    'Academic document was evaluated and is ready for recruiter review.',
    DATE_SUB(@now, INTERVAL (MOD(ap.application_id, 3)) DAY)
FROM applications ap
WHERE ap.application_id > 8
  AND ap.academic_document_file IS NOT NULL;

-- Application validations (AI pre-screening audit). One INVALID row shows a
-- blocked submission.
INSERT INTO application_validations (
    submission_reference, application_id, posting_id, applicant_name,
    applicant_email, ip_address, validation_status, identity_status,
    education_status, experience_status, credential_status,
    consistency_score, findings, validation_summary, ai_model, processed_at
) VALUES
('VAL-9F3C1A7B2E', 1, 1, 'Juan Dela Cruz', 'juan.delacruz@gmail.com', '203.0.113.51', 'VALID',
 'Consistent', 'Consistent', 'Consistent', 'Consistent', 96.00,
 JSON_ARRAY(JSON_OBJECT('category','identity','severity','info','message','Name on the resume matches the government ID.')),
 'All documents are consistent with the submitted application details.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 50 DAY)),
('VAL-1D6F8A2E4B', 2, 3, 'Maria Santos', 'maria.santos@yahoo.com', '203.0.113.52', 'VALID',
 'Consistent', 'Consistent', 'Consistent', 'Consistent', 93.00,
 JSON_ARRAY(JSON_OBJECT('category','education','severity','info','message','Diploma matches the declared psychology program.')),
 'All documents are consistent with the submitted application details.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 140 DAY)),
('VAL-5A1E8C3D7F', 5, 1, 'Jose Reyes', 'jose.reyes@gmail.com', '203.0.113.53', 'REVIEW',
 'Consistent', 'Minor Differences', 'Consistent', 'Consistent', 78.00,
 JSON_ARRAY(JSON_OBJECT('category','education','severity','minor','message','Degree title differs slightly from the preferred program.')),
 'Minor differences noted; recommend a quick manual check of the diploma.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 9 DAY)),
('VAL-8B2C4D6E0F', 7, 1, 'Timothy Ramos', 'timothy.ramos@yahoo.com', '203.0.113.54', 'INVALID',
 'Consistent', 'Conflict', 'Not Checked', 'Conflict', 41.00,
 JSON_ARRAY(JSON_OBJECT('category','education','severity','major','message','Submitted certificate is not an acceptable academic credential.')),
 'The submitted document does not satisfy the academic requirement for this role.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 4 DAY)),
('VAL-3F5A7C9E1B', 8, 5, 'Daniel Mercado', 'daniel.mercado@gmail.com', '203.0.113.55', 'VALID',
 'Consistent', 'Consistent', 'Consistent', 'Consistent', 89.00,
 JSON_ARRAY(JSON_OBJECT('category','credential','severity','info','message','CPA board passer record is consistent with the declared qualification.')),
 'Documents are consistent; the declared qualification was verified.', 'gemini-2.5-flash', DATE_SUB(@now, INTERVAL 12 DAY));

-- Provide completed validation records for all remaining applications.
INSERT INTO application_validations (
    submission_reference, application_id, posting_id, applicant_name,
    applicant_email, ip_address, validation_status, identity_status,
    education_status, experience_status, credential_status,
    consistency_score, findings, validation_summary, ai_model, processed_at
)
SELECT
    CONCAT('SEED-', LPAD(ap.application_id, 6, '0')),
    ap.application_id,
    ap.posting_id,
    CONCAT(a.first_name, ' ', a.last_name),
    a.email,
    NULL,
    'VALID',
    'Consistent',
    'Consistent',
    'Consistent',
    'Consistent',
    90.00,
    JSON_ARRAY(JSON_OBJECT('category', 'identity', 'severity', 'info', 'message', 'Seeded candidate details are internally consistent.')),
    'Application validation is complete and ready for recruiter review.',
    'seeded-demo',
    DATE_SUB(@now, INTERVAL (MOD(ap.application_id, 3)) DAY)
FROM applications ap
JOIN applicants a ON a.applicant_id = ap.applicant_id
WHERE ap.application_id NOT IN (1, 2, 5, 7, 8);

-- =====================================================================
-- 5. CORE HCM / EMPLOYEE RECORDS — EMPLOYEES & USER ACCOUNTS
-- =====================================================================
-- Department is derived: employees.position_id -> positions.department_id.
--
-- employee_id:
-- 1 = Rafael Mendoza  (Store Manager,        Regular)
-- 2 = Maria Santos    (HR Assistant,         Regular)
-- 3 = Juan Dela Cruz  (Inventory Clerk,      Probationary)
-- 4 = Sofia Cabrera   (Delivery Coordinator, Probationary)

INSERT INTO employees (
    application_id, applicant_id, employee_number,
    position_id, hire_date, employment_status
) VALUES
(3, 3, CONCAT('EMP-', YEAR(CURDATE()), '-0001'), 11, DATE_SUB(CURDATE(), INTERVAL 150 DAY), 'Regular'),
(2, 2, CONCAT('EMP-', YEAR(CURDATE()), '-0002'), 1,  DATE_SUB(CURDATE(), INTERVAL 110 DAY), 'Regular'),
(1, 1, CONCAT('EMP-', YEAR(CURDATE()), '-0003'), 6,  DATE_SUB(CURDATE(), INTERVAL 20 DAY),  'Probationary'),
(4, 4, CONCAT('EMP-', YEAR(CURDATE()), '-0004'), 8,  DATE_SUB(CURDATE(), INTERVAL 5 DAY),   'Probationary');

-- Six more hires bring the employee roster to 10, including a second manager.
INSERT INTO employees (
    application_id, applicant_id, employee_number,
    position_id, hire_date, employment_status
) VALUES
(11, 11, CONCAT('EMP-', YEAR(CURDATE()), '-0005'), 11, DATE_SUB(CURDATE(), INTERVAL 150 DAY), 'Regular'),
(12, 12, CONCAT('EMP-', YEAR(CURDATE()), '-0006'), 6,  DATE_SUB(CURDATE(), INTERVAL 40 DAY),  'Probationary'),
(13, 13, CONCAT('EMP-', YEAR(CURDATE()), '-0007'), 8,  DATE_SUB(CURDATE(), INTERVAL 38 DAY),  'Probationary'),
(14, 14, CONCAT('EMP-', YEAR(CURDATE()), '-0008'), 8,  DATE_SUB(CURDATE(), INTERVAL 36 DAY),  'Probationary'),
(15, 15, CONCAT('EMP-', YEAR(CURDATE()), '-0009'), 3,  DATE_SUB(CURDATE(), INTERVAL 34 DAY),  'Probationary'),
(16, 16, CONCAT('EMP-', YEAR(CURDATE()), '-0010'), 6,  DATE_SUB(CURDATE(), INTERVAL 32 DAY),  'Probationary');

-- user_id:
-- 2 = Rafael Mendoza (MGR)
-- 3 = Maria Santos   (HR)
-- 4 = Juan Dela Cruz (EMP)
-- 5 = Sofia Cabrera  (EMP, must change password on first login)
INSERT INTO users (
    employee_id, role_id, username, email, email_verified_at,
    password, status, must_change_password, created_at
) VALUES
(1, 3, 'rafael.mendoza', 'rafael.mendoza@gmail.com', DATE_SUB(@now, INTERVAL 150 DAY), '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0, DATE_SUB(@now, INTERVAL 150 DAY)),
(2, 2, 'maria.santos',    'maria.santos@yahoo.com',   DATE_SUB(@now, INTERVAL 110 DAY), '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0, DATE_SUB(@now, INTERVAL 110 DAY)),
(3, 4, 'juan.delacruz',   'juan.delacruz@gmail.com',  DATE_SUB(@now, INTERVAL 20 DAY),  '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0, DATE_SUB(@now, INTERVAL 20 DAY)),
(4, 4, 'sofia.cabrera',   'sofia.cabrera@gmail.com',  DATE_SUB(@now, INTERVAL 5 DAY),   '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 1, DATE_SUB(@now, INTERVAL 5 DAY));

INSERT INTO users (
    employee_id, role_id, username, email, email_verified_at,
    password, status, must_change_password, created_at
) VALUES
(5, 3, 'alyssa.bautista', 'candidate11@example.com', DATE_SUB(@now, INTERVAL 150 DAY), '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0, DATE_SUB(@now, INTERVAL 150 DAY)),
(6, 4, 'marco.villanueva', 'candidate12@example.com', DATE_SUB(@now, INTERVAL 40 DAY), '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0, DATE_SUB(@now, INTERVAL 40 DAY)),
(7, 4, 'bianca.navarro', 'candidate13@example.com', DATE_SUB(@now, INTERVAL 38 DAY), '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0, DATE_SUB(@now, INTERVAL 38 DAY)),
(8, 4, 'paolo.castillo', 'candidate14@example.com', DATE_SUB(@now, INTERVAL 36 DAY), '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0, DATE_SUB(@now, INTERVAL 36 DAY)),
(9, 4, 'jasmine.rivera', 'candidate15@example.com', DATE_SUB(@now, INTERVAL 34 DAY), '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0, DATE_SUB(@now, INTERVAL 34 DAY)),
(10, 4, 'noel.garcia', 'candidate16@example.com', DATE_SUB(@now, INTERVAL 32 DAY), '$2y$10$4qosLS/s3G.aXV3x/BVSvetdSsmrv8ReFDFAbyQXj6gbn2kT1ucIa', 'Active', 0, DATE_SUB(@now, INTERVAL 32 DAY));


-- =====================================================================
-- 6. RECRUITMENT MANAGEMENT — INTERVIEWS
-- =====================================================================
-- interviewer_id: 1 = admin, 2 = Rafael (MGR), 3 = Maria (HR)

INSERT INTO interviews (
    application_id, interviewer_id, interview_type, interview_date,
    location, remarks, result, created_at
) VALUES
(1, 2, 'Face-to-Face', DATE_SUB(@now, INTERVAL 33 DAY),  'RAM-YUM Head Office, 3F Conference Room', 'Strong grasp of stock control and good cultural fit.', 'Passed', DATE_SUB(@now, INTERVAL 34 DAY)),
(2, 1, 'Online',       DATE_SUB(@now, INTERVAL 128 DAY), 'Google Meet', 'Clear understanding of HR processes and strong interpersonal skills.', 'Passed', DATE_SUB(@now, INTERVAL 129 DAY)),
(3, 1, 'Face-to-Face', DATE_SUB(@now, INTERVAL 158 DAY), 'RAM-YUM Head Office, 3F Conference Room', 'Presented a clear promotions and pricing plan and led the panel discussion well.', 'Passed', DATE_SUB(@now, INTERVAL 159 DAY)),
(4, 3, 'Phone',        DATE_SUB(@now, INTERVAL 12 DAY),  'N/A', 'Good communication; organized and dependable.', 'Passed', DATE_SUB(@now, INTERVAL 13 DAY)),
(5, 2, 'Online',       DATE_ADD(@now, INTERVAL 3 DAY),   'Google Meet', 'Interview on warehouse operations and inventory reconciliation.', 'Pending', DATE_SUB(@now, INTERVAL 1 DAY)),
(8, 3, 'Online',       DATE_ADD(@now, INTERVAL 4 DAY),   'Google Meet', 'Interview on payables, receivables, and Excel skills.', 'Pending', DATE_SUB(@now, INTERVAL 1 DAY));


-- =====================================================================
-- 7. NEW HIRE ONBOARDING
-- =====================================================================
-- onboarding_id:
-- 1 = Rafael (Completed)   2 = Maria (Completed)
-- 3 = Juan   (In Progress) 4 = Sofia (Pending)

INSERT INTO onboarding (
    application_id, orientation_date, started_at, completed_at,
    onboarding_status, system_access_setup_completed,
    orientation_setup_completed, document_submission_completed,
    document_verification_completed, remarks
) VALUES
(3, DATE_SUB(CURDATE(), INTERVAL 148 DAY), DATE_SUB(CURDATE(), INTERVAL 150 DAY), DATE_SUB(CURDATE(), INTERVAL 145 DAY), 'Completed',   TRUE,  TRUE,  TRUE,  TRUE,  'Completed orientation and all document checks.'),
(2, DATE_SUB(CURDATE(), INTERVAL 108 DAY), DATE_SUB(CURDATE(), INTERVAL 110 DAY), DATE_SUB(CURDATE(), INTERVAL 105 DAY), 'Completed',   TRUE,  TRUE,  TRUE,  TRUE,  'Completed orientation and all document checks.'),
(1, DATE_SUB(CURDATE(), INTERVAL 18 DAY),  DATE_SUB(CURDATE(), INTERVAL 20 DAY),  NULL,                                'In Progress', TRUE,  TRUE,  FALSE, FALSE, 'Attended orientation; remaining pre-employment documents are pending.'),
(4, NULL,                                  NULL,                                  NULL,                                'Pending',     FALSE, FALSE, FALSE, FALSE, 'Newly hired; onboarding checklist has not been started yet.');

INSERT INTO onboarding_phases (
    onboarding_id, phase_number, phase_name, phase_description, status, completed_at
) VALUES
-- Rafael — completed
(1, 1, 'System & Access Setup',       'Employee account and access are created.',    'completed', DATE_SUB(@now, INTERVAL 150 DAY)),
(1, 2, 'Orientation & Setup',         'Employee attends the orientation session.',   'completed', DATE_SUB(@now, INTERVAL 148 DAY)),
(1, 3, 'Document Submission',         'Employee submits all required documents.',    'completed', DATE_SUB(@now, INTERVAL 148 DAY)),
(1, 4, 'Document Verification',       'HR verifies submitted documents.',            'completed', DATE_SUB(@now, INTERVAL 146 DAY)),
(1, 5, 'Final Onboarding Completion', 'System confirms all onboarding requirements.','completed', DATE_SUB(@now, INTERVAL 145 DAY)),
-- Maria — completed
(2, 1, 'System & Access Setup',       'Employee account and access are created.',    'completed', DATE_SUB(@now, INTERVAL 110 DAY)),
(2, 2, 'Orientation & Setup',         'Employee attends the orientation session.',   'completed', DATE_SUB(@now, INTERVAL 108 DAY)),
(2, 3, 'Document Submission',         'Employee submits all required documents.',    'completed', DATE_SUB(@now, INTERVAL 108 DAY)),
(2, 4, 'Document Verification',       'HR verifies submitted documents.',            'completed', DATE_SUB(@now, INTERVAL 106 DAY)),
(2, 5, 'Final Onboarding Completion', 'System confirms all onboarding requirements.','completed', DATE_SUB(@now, INTERVAL 105 DAY)),
-- Juan — in progress
(3, 1, 'System & Access Setup',       'Employee account and access are created.',    'completed',   DATE_SUB(@now, INTERVAL 20 DAY)),
(3, 2, 'Orientation & Setup',         'Employee attends the orientation session.',   'completed',   DATE_SUB(@now, INTERVAL 18 DAY)),
(3, 3, 'Document Submission',         'Employee submits all required documents.',    'in-progress', NULL),
(3, 4, 'Document Verification',       'HR verifies submitted documents.',            'pending',     NULL),
(3, 5, 'Final Onboarding Completion', 'System confirms all onboarding requirements.','pending',     NULL),
-- Sofia — pending
(4, 1, 'System & Access Setup',       'Employee account and access are created.',    'pending', NULL),
(4, 2, 'Orientation & Setup',         'Employee attends the orientation session.',   'pending', NULL),
(4, 3, 'Document Submission',         'Employee submits all required documents.',    'pending', NULL),
(4, 4, 'Document Verification',       'HR verifies submitted documents.',            'pending', NULL),
(4, 5, 'Final Onboarding Completion', 'System confirms all onboarding requirements.','pending', NULL);

-- assigned_by: 1 = admin (Rafael / Maria), 3 = Maria HR (Juan / Sofia).
-- file_path and review columns are filled by the UPDATE block in section 10.
INSERT INTO onboarding_documents (
    onboarding_id, employee_id, document_name, instruction, status,
    request_type, priority, due_date, assigned_by, action_type,
    created_at, submitted_at
) VALUES
-- Rafael (completed)
(1, 1, 'Government-Issued ID',       'Upload a clear scan of your valid government ID.',         'Approved', 'Submit Required Document',   'Normal', DATE_SUB(CURDATE(), INTERVAL 135 DAY), 1, 'document', DATE_SUB(@now, INTERVAL 150 DAY), DATE_SUB(@now, INTERVAL 148 DAY)),
(1, 1, 'NBI Clearance',              'Upload your latest NBI clearance.',                        'Approved', 'Submit Missing Requirement', 'Normal', DATE_SUB(CURDATE(), INTERVAL 135 DAY), 1, 'document', DATE_SUB(@now, INTERVAL 150 DAY), DATE_SUB(@now, INTERVAL 148 DAY)),
(1, 1, 'Signed Employment Contract', 'Return the signed copy of your employment contract.',     'Approved', 'Submit Required Document',   'Normal', DATE_SUB(CURDATE(), INTERVAL 135 DAY), 1, 'document', DATE_SUB(@now, INTERVAL 150 DAY), DATE_SUB(@now, INTERVAL 148 DAY)),
-- Maria (completed)
(2, 2, 'Government-Issued ID',       'Upload a clear scan of your valid government ID.',         'Approved', 'Submit Required Document',   'Normal', DATE_SUB(CURDATE(), INTERVAL 95 DAY),  1, 'document', DATE_SUB(@now, INTERVAL 110 DAY), DATE_SUB(@now, INTERVAL 108 DAY)),
(2, 2, 'NBI Clearance',              'Upload your latest NBI clearance.',                        'Approved', 'Submit Missing Requirement', 'Normal', DATE_SUB(CURDATE(), INTERVAL 95 DAY),  1, 'document', DATE_SUB(@now, INTERVAL 110 DAY), DATE_SUB(@now, INTERVAL 108 DAY)),
(2, 2, 'Signed Employment Contract', 'Return the signed copy of your employment contract.',     'Approved', 'Submit Required Document',   'Normal', DATE_SUB(CURDATE(), INTERVAL 95 DAY),  1, 'document', DATE_SUB(@now, INTERVAL 110 DAY), DATE_SUB(@now, INTERVAL 108 DAY)),
-- Juan (in progress)
(3, 3, 'Government-Issued ID',       'Upload a clear scan of your valid government ID.',         'Approved', 'Submit Required Document',   'Normal', DATE_ADD(CURDATE(), INTERVAL 7 DAY),   3, 'document', DATE_SUB(@now, INTERVAL 20 DAY), DATE_SUB(@now, INTERVAL 18 DAY)),
(3, 3, 'NBI Clearance',              'Upload your latest NBI clearance.',                        'Pending',  'Submit Missing Requirement', 'High',   DATE_ADD(CURDATE(), INTERVAL 7 DAY),   3, 'document', DATE_SUB(@now, INTERVAL 20 DAY), DATE_SUB(@now, INTERVAL 17 DAY)),
(3, 3, 'Signed Employment Contract', 'Return the signed copy of your employment contract.',     'Pending',  'Submit Required Document',   'High',   DATE_ADD(CURDATE(), INTERVAL 7 DAY),   3, 'document', DATE_SUB(@now, INTERVAL 20 DAY), NULL),
-- Sofia (pending, nothing submitted yet)
(4, 4, 'Government-Issued ID',       'Upload a clear scan of your valid government ID.',         'Pending',  'Submit Required Document',   'High',   DATE_ADD(CURDATE(), INTERVAL 14 DAY),  3, 'document', DATE_SUB(@now, INTERVAL 5 DAY), NULL),
(4, 4, 'NBI Clearance',              'Upload your latest NBI clearance.',                        'Pending',  'Submit Missing Requirement', 'High',   DATE_ADD(CURDATE(), INTERVAL 14 DAY),  3, 'document', DATE_SUB(@now, INTERVAL 5 DAY), NULL),
(4, 4, 'Signed Employment Contract', 'Return the signed copy of your employment contract.',     'Pending',  'Submit Required Document',   'High',   DATE_ADD(CURDATE(), INTERVAL 14 DAY),  3, 'document', DATE_SUB(@now, INTERVAL 5 DAY), NULL);


-- =====================================================================
-- 8. EMPLOYEE SELF-SERVICE
-- =====================================================================
-- resolved_by: 3 = Maria (HR)

INSERT INTO employee_requests (
    employee_id, request_type, subject, description, status,
    hr_remarks, requested_at, resolved_at, resolved_by
) VALUES
(3, 'Certificate of Employment', 'Request for Certificate of Employment',
 'I would like to request a Certificate of Employment for personal documentation purposes.',
 'Completed', 'Certificate prepared and released to the employee.',
 DATE_SUB(@now, INTERVAL 10 DAY), DATE_SUB(@now, INTERVAL 9 DAY), 3),
(3, 'Profile Update', 'Update Contact Information',
 'I would like to update my contact information in my employee record.',
 'Pending', NULL, DATE_SUB(@now, INTERVAL 3 DAY), NULL, NULL),
(1, 'Document Request', 'Request for Employment Contract Copy',
 'I would like to request a digital copy of my signed employment contract.',
 'Approved', 'Request approved. HR will prepare the requested document.',
 DATE_SUB(@now, INTERVAL 6 DAY), NULL, 3),
(2, 'Employment Concern', 'Question Regarding Leave Credits',
 'I would like clarification regarding my current leave credits.',
 'Completed', 'Leave credits reviewed and confirmed with the employee.',
 DATE_SUB(@now, INTERVAL 15 DAY), DATE_SUB(@now, INTERVAL 14 DAY), 3),
(4, 'Payroll Concern', 'First Payroll Enrolment Question',
 'I would like to confirm the bank account details on file for my first payroll.',
 'Pending', NULL, DATE_SUB(@now, INTERVAL 1 DAY), NULL, NULL);

-- "Contact HR" password reset requests (shown to ADMIN / HR).
INSERT INTO password_reset_requests (
    full_name, identifier, contact, message, status,
    created_at, resolved_at, resolved_by
) VALUES
('Paolo Mendoza', 'paolo.mendoza', 'paolo.mendoza@outlook.com',
 'I forgot my password and need help signing in.',
 'Pending', DATE_SUB(@now, INTERVAL 3 HOUR), NULL, NULL),
('Angela Fernandez', 'angela.fernandez', 'angela.fernandez@gmail.com',
 'Requesting a password reset for my applicant account.',
 'Completed', DATE_SUB(@now, INTERVAL 6 DAY), DATE_SUB(@now, INTERVAL 5 DAY), 1);


-- =====================================================================
-- 9. EMPLOYEE RECORDS MANAGEMENT — ACTIVITY LOGS
-- =====================================================================
-- user_id / employee_id: 1/NULL admin, 2/1 Rafael, 3/2 Maria, 4/3 Juan,
-- 5/4 Sofia, 6/5 Alyssa, and 7-11/6-10 the additional employees

INSERT INTO activity_logs (
    user_id, employee_id, action, page, request_method,
    ip_address, user_agent, created_at
) VALUES
(1, NULL, 'Logged in with OTP',                   'login',                  'POST', '203.0.113.10',  'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',   DATE_SUB(@now, INTERVAL 20 DAY)),
(1, NULL, 'Viewed recruitment dashboard',         'recruitment',            'GET',  '203.0.113.10',  'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',   DATE_SUB(@now, INTERVAL 20 DAY)),
(3, 2,    'Logged in with OTP',                   'login',                  'POST', '203.0.113.24',  'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',   DATE_SUB(@now, INTERVAL 18 DAY)),
(3, 2,    'Updated an onboarding checklist',      'onboarding',             'POST', '203.0.113.24',  'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',   DATE_SUB(@now, INTERVAL 17 DAY)),
(4, 3,    'Logged in with OTP',                   'login',                  'POST', '198.51.100.7',  'Mozilla/5.0 (Linux; Android 13)',             DATE_SUB(@now, INTERVAL 15 DAY)),
(4, 3,    'Submitted a certificate request',      'employee-requests',      'POST', '198.51.100.7',  'Mozilla/5.0 (Linux; Android 13)',             DATE_SUB(@now, INTERVAL 10 DAY)),
(3, 2,    'Marked an employee request completed', 'employee-requests',      'POST', '203.0.113.24',  'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',   DATE_SUB(@now, INTERVAL 9 DAY)),
(2, 1,    'Scheduled an interview',               'applicants/review',      'POST', '203.0.113.88',  'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',   DATE_SUB(@now, INTERVAL 8 DAY)),
(1, NULL, 'Reviewed applicant profile',           'applicants/review',      'GET',  '203.0.113.10',  'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',   DATE_SUB(@now, INTERVAL 5 DAY)),
(3, 2,    'Viewed employee records',              'employees',              'GET',  '203.0.113.24',  'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',   DATE_SUB(@now, INTERVAL 3 DAY)),
(3, 2,    'Rejected an application',              'applicants/review',      'POST', '203.0.113.24',  'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',   DATE_SUB(@now, INTERVAL 1 DAY)),
(5, 4,    'Logged in with OTP',                   'login',                  'POST', '198.51.100.19', 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0)',    DATE_SUB(@now, INTERVAL 1 DAY)),
(3, 2,    'Viewed onboarding status',             'onboarding',             'GET',  '203.0.113.24',  'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',   DATE_SUB(@now, INTERVAL 6 HOUR));


-- =====================================================================
-- 10. ONBOARDING DOCUMENT ENRICHMENT + EMPLOYEE NUMBER SEQUENCE
-- =====================================================================

-- Every submitted document has a stored file.
UPDATE onboarding_documents
SET
    file_path = CONCAT('onboarding_document_', document_id, '.pdf'),
    submission_mime = 'application/pdf',
    submission_file_size = 780 + (document_id * 7)
WHERE submitted_at IS NOT NULL;

-- Approved / Rejected documents were reviewed by whoever assigned them.
UPDATE onboarding_documents
SET
    reviewed_by = assigned_by,
    reviewed_at = DATE_ADD(created_at, INTERVAL 4 DAY)
WHERE status IN ('Approved', 'Rejected');

UPDATE onboarding_documents
SET response_text = 'Noted and verified against the submitted record.'
WHERE status = 'Approved'
  AND response_text IS NULL;

-- Ten employees were seeded, so the next generated number is 11.
UPDATE employee_number_sequence
SET next_number = 11
WHERE sequence_id = 1;


-- =====================================================================
-- END OF SEED DATA
-- =====================================================================