/* ==========================================================
   HR1 DATABASE SEED
   Part 1 - System Setup
   ----------------------------------------------------------
   Tables:
   - roles
   - employees
   - users
   - departments
========================================================== */

USE hr1_db;

/* ==========================================================
   ROLES
========================================================== */

INSERT INTO roles (
    role_code,
    role_name
) VALUES
    ('ADMIN',   'Administrator'),
    ('HR',      'HR Staff'),
    ('MANAGER', 'Manager'),
    ('CASHIER', 'Cashier');


/* ==========================================================
   EMPLOYEES
   Existing company employees
========================================================== */

INSERT INTO employees (
    application_id,
    employee_number,
    hire_date,
    employment_status
) VALUES
    (NULL, 'EMP2026001', '2024-01-15', 'Regular'),
    (NULL, 'EMP2026002', '2024-02-10', 'Regular'),
    (NULL, 'EMP2026003', '2024-03-05', 'Regular'),
    (NULL, 'EMP2026004', '2024-04-01', 'Regular');


/* ==========================================================
   USERS
   Default Password:
   Password123
   (Replace using password_hash() before production)
========================================================== */

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
        1,
        'admin',
        '$2y$10$PLACEHOLDER_HASH',
        'Active',
        FALSE
    ),
    (
        2,
        2,
        'hrstaff',
        '$2y$10$PLACEHOLDER_HASH',
        'Active',
        FALSE
    ),
    (
        3,
        3,
        'manager',
        '$2y$10$PLACEHOLDER_HASH',
        'Active',
        FALSE
    ),
    (
        4,
        4,
        'cashier',
        '$2y$10$PLACEHOLDER_HASH',
        'Active',
        FALSE
    );


/* ==========================================================
   DEPARTMENTS
========================================================== */

INSERT INTO departments (
    department_name,
    description
) VALUES
    (
        'Human Resources',
        'Manages recruitment, employee relations, and workforce development.'
    ),
    (
        'Finance',
        'Handles financial transactions, budgeting, payroll, and accounting.'
    ),
    (
        'Sales',
        'Responsible for customer acquisition and revenue generation.'
    ),
    (
        'Information Technology',
        'Maintains hardware, software, networks, and IT support services.'
    ),
    (
        'Operations',
        'Oversees daily business operations and warehouse activities.'
    );

/* ==========================================================
   HR1 DATABASE SEED
   Part 2 - Recruitment
   ----------------------------------------------------------
   Tables:
   - job_postings
   - applicants
   - applications
========================================================== */


/* ==========================================================
   JOB POSTINGS
========================================================== */

INSERT INTO job_postings (
    department_id,
    title,
    description,
    requirements,
    employment_type,
    vacancies,
    status,
    application_deadline,
    created_by
) VALUES
    (
        1,
        'HR Assistant',
        'Assist the Human Resources department in recruitment, onboarding, and employee records.',
        'Bachelor''s Degree in Human Resource Management or related field.',
        'Full-Time',
        2,
        'Open',
        '2026-09-30',
        1
    ),
    (
        2,
        'Cashier',
        'Handle customer payments and maintain accurate cash transactions.',
        'College level or graduate with excellent customer service skills.',
        'Full-Time',
        3,
        'Open',
        '2026-09-30',
        1
    ),
    (
        3,
        'Sales Associate',
        'Assist customers and promote company products.',
        'Excellent communication and interpersonal skills.',
        'Full-Time',
        4,
        'Open',
        '2026-09-30',
        1
    ),
    (
        4,
        'IT Support Specialist',
        'Provide technical support and maintain company computer systems.',
        'Knowledge in computer troubleshooting and networking.',
        'Full-Time',
        2,
        'Open',
        '2026-09-30',
        1
    ),
    (
        5,
        'Warehouse Staff',
        'Receive, organize, and monitor warehouse inventory.',
        'Physically fit and willing to perform warehouse operations.',
        'Full-Time',
        5,
        'Open',
        '2026-09-30',
        1
    );


/* ==========================================================
   APPLICANTS
========================================================== */

INSERT INTO applicants (
    first_name,
    middle_name,
    last_name,
    email,
    phone,
    address
) VALUES
    ('Juan',       'Santos',      'Dela Cruz',   'juan.delacruz@email.com',       '09171230001', 'Dasmariñas City, Cavite'),
    ('Maria',      'Lopez',       'Santos',      'maria.santos@email.com',         '09171230002', 'Imus City, Cavite'),
    ('John',       'Garcia',      'Reyes',       'john.reyes@email.com',           '09171230003', 'Bacoor City, Cavite'),
    ('Angelica',   'Ramos',       'Cruz',        'angelica.cruz@email.com',        '09171230004', 'Tagaytay City, Cavite'),
    ('Carlo',      'Perez',       'Mendoza',     'carlo.mendoza@email.com',        '09171230005', 'Silang, Cavite'),
    ('Patricia',   'Flores',      'Rivera',      'patricia.rivera@email.com',      '09171230006', 'General Trias, Cavite'),
    ('Michael',    'Torres',      'Ramos',       'michael.ramos@email.com',        '09171230007', 'Trece Martires, Cavite'),
    ('Denise',     'Aquino',      'Garcia',      'denise.garcia@email.com',        '09171230008', 'Naic, Cavite'),
    ('Kevin',      'Navarro',     'Lopez',       'kevin.lopez@email.com',          '09171230009', 'Rosario, Cavite'),
    ('Sophia',     'Bautista',    'Torres',      'sophia.torres@email.com',        '09171230010', 'Kawit, Cavite'),
    ('Mark',       'Villanueva',  'Bautista',    'mark.bautista@email.com',        '09171230011', 'Noveleta, Cavite'),
    ('Christine',  'De Leon',     'Navarro',     'christine.navarro@email.com',    '09171230012', 'Cavite City'),
    ('Daniel',     'Salazar',     'Aquino',      'daniel.aquino@email.com',        '09171230013', 'Tanza, Cavite'),
    ('Hazel',      'Fernandez',   'Villanueva',  'hazel.villanueva@email.com',     '09171230014', 'General Mariano Alvarez, Cavite'),
    ('Joshua',     'Castro',      'Perez',       'joshua.perez@email.com',         '09171230015', 'Carmona, Cavite');


/* ==========================================================
   APPLICATIONS
========================================================== */

INSERT INTO applications (
    applicant_id,
    posting_id,
    resume_file,
    cover_letter_file,
    application_status
) VALUES
    (1, 2, 'resumes/juan_delacruz.pdf',      'cover_letters/juan_delacruz.pdf',      'Submitted'),
    (2, 1, 'resumes/maria_santos.pdf',       'cover_letters/maria_santos.pdf',       'Under Review'),
    (3, 4, 'resumes/john_reyes.pdf',         'cover_letters/john_reyes.pdf',         'Interview'),
    (4, 3, 'resumes/angelica_cruz.pdf',      'cover_letters/angelica_cruz.pdf',      'Under Review'),
    (5, 5, 'resumes/carlo_mendoza.pdf',      'cover_letters/carlo_mendoza.pdf',      'Rejected'),
    (6, 2, 'resumes/patricia_rivera.pdf',    'cover_letters/patricia_rivera.pdf',    'Submitted'),
    (7, 4, 'resumes/michael_ramos.pdf',      'cover_letters/michael_ramos.pdf',      'Interview'),
    (8, 3, 'resumes/denise_garcia.pdf',      'cover_letters/denise_garcia.pdf',      'Under Review'),
    (9, 5, 'resumes/kevin_lopez.pdf',        'cover_letters/kevin_lopez.pdf',        'Interview'),
    (10,1, 'resumes/sophia_torres.pdf',      'cover_letters/sophia_torres.pdf',      'Submitted'),
    (11,2, 'resumes/mark_bautista.pdf',      'cover_letters/mark_bautista.pdf',      'Interview'),
    (12,3, 'resumes/christine_navarro.pdf',  'cover_letters/christine_navarro.pdf',  'Rejected'),
    (13,4, 'resumes/daniel_aquino.pdf',      'cover_letters/daniel_aquino.pdf',      'Under Review'),
    (14,1, 'resumes/hazel_villanueva.pdf',   'cover_letters/hazel_villanueva.pdf',   'Interview'),
    (15,5, 'resumes/joshua_perez.pdf',       'cover_letters/joshua_perez.pdf',       'Submitted');

/* ==========================================================
   HR1 DATABASE SEED
   Part 3 - Recruitment Process
   ----------------------------------------------------------
   Tables:
   - ai_screening
   - interviews
========================================================== */


/* ==========================================================
   AI SCREENING
   Automatically generated after application submission
========================================================== */

INSERT INTO ai_screening (
    application_id,
    match_score,
    recommendation,
    extracted_skills,
    strengths,
    weaknesses,
    ai_summary
) VALUES
    (
        1,
        78.50,
        'Consider',
        'Cash Handling, Customer Service, Communication',
        'Good communication skills and customer-oriented.',
        'Limited cashier experience.',
        'Applicant demonstrates potential for entry-level cashier responsibilities.'
    ),
    (
        2,
        94.30,
        'Highly Recommended',
        'Recruitment, Documentation, MS Office',
        'Strong HR background and excellent communication.',
        'Limited payroll experience.',
        'Excellent candidate for HR Assistant position.'
    ),
    (
        3,
        89.75,
        'Recommended',
        'Computer Troubleshooting, Networking, Windows',
        'Strong technical knowledge.',
        'Needs additional experience in server administration.',
        'Recommended for IT Support interview.'
    ),
    (
        4,
        91.20,
        'Highly Recommended',
        'Sales, Customer Service, Communication',
        'Confident communicator with retail experience.',
        'Limited inventory management knowledge.',
        'Highly suitable for Sales Associate.'
    ),
    (
        5,
        56.80,
        'Not Recommended',
        'Inventory',
        'Willing to learn.',
        'Lacks warehouse experience.',
        'Does not currently meet minimum requirements.'
    ),
    (
        6,
        82.40,
        'Recommended',
        'Cash Handling, POS System',
        'Previous cashier experience.',
        'Needs improvement in communication.',
        'Suitable for further evaluation.'
    ),
    (
        7,
        90.50,
        'Highly Recommended',
        'Networking, Hardware Repair, Windows Server',
        'Excellent technical skills.',
        'Minimal Linux experience.',
        'Strong IT Support candidate.'
    ),
    (
        8,
        84.10,
        'Recommended',
        'Sales, Marketing',
        'Friendly and persuasive.',
        'Limited product knowledge.',
        'Recommended for interview.'
    ),
    (
        9,
        87.35,
        'Recommended',
        'Inventory, Forklift Operation',
        'Warehouse experience.',
        'Needs safety certification.',
        'Good warehouse applicant.'
    ),
    (
        10,
        79.90,
        'Consider',
        'Recruitment, Filing',
        'Organized and detail-oriented.',
        'Needs more HR experience.',
        'Can be considered for HR Assistant.'
    ),
    (
        11,
        74.60,
        'Consider',
        'Cashiering, Customer Service',
        'Friendly personality.',
        'Limited work experience.',
        'May be considered after interview.'
    ),
    (
        12,
        61.20,
        'Not Recommended',
        'Sales',
        'Basic communication skills.',
        'Insufficient experience.',
        'Not recommended for current opening.'
    ),
    (
        13,
        86.70,
        'Recommended',
        'Computer Repair, Networking',
        'Good troubleshooting skills.',
        'Needs cloud technology exposure.',
        'Recommended for technical interview.'
    ),
    (
        14,
        92.80,
        'Highly Recommended',
        'Recruitment, Employee Relations',
        'Strong HR knowledge.',
        'Limited labor law exposure.',
        'Excellent HR Assistant candidate.'
    ),
    (
        15,
        77.30,
        'Consider',
        'Warehouse Operations',
        'Physically fit.',
        'Needs inventory software training.',
        'Potential warehouse employee.'
    );


/* ==========================================================
   INTERVIEWS
========================================================== */

INSERT INTO interviews (
    application_id,
    interviewer_id,
    interview_type,
    interview_date,
    remarks,
    result
) VALUES
    (
        3,
        3,
        'Face-to-Face',
        '2026-08-15 09:00:00',
        'Technical interview scheduled.',
        'Pending'
    ),
    (
        7,
        3,
        'Online',
        '2026-08-15 01:30:00',
        'Strong technical background.',
        'Passed'
    ),
    (
        9,
        3,
        'Face-to-Face',
        '2026-08-16 10:00:00',
        'Warehouse skills validated.',
        'Passed'
    ),
    (
        11,
        3,
        'Phone',
        '2026-08-17 02:00:00',
        'Communication needs improvement.',
        'Failed'
    ),
    (
        14,
        3,
        'Online',
        '2026-08-18 11:00:00',
        'Excellent HR knowledge.',
        'Pending'
    ),
    (
        4,
        3,
        'Face-to-Face',
        '2026-08-19 03:00:00',
        'Sales assessment scheduled.',
        'Pending'
    );

/* ==========================================================
   HR1 DATABASE SEED
   Part 4 - Employee Onboarding
   ----------------------------------------------------------
   Tables:
   - onboarding
   - onboarding_documents
========================================================== */


/* ==========================================================
   ONBOARDING
   Existing Employees
========================================================== */

INSERT INTO onboarding (
    application_id,
    orientation_date,
    onboarding_status,
    remarks
) VALUES
    (
        NULL,
        '2024-01-20',
        'Completed',
        'Orientation completed successfully.'
    ),
    (
        NULL,
        '2024-02-15',
        'Completed',
        'Orientation completed successfully.'
    ),
    (
        NULL,
        '2024-03-10',
        'Completed',
        'Orientation completed successfully.'
    ),
    (
        NULL,
        '2024-04-05',
        'Completed',
        'Orientation completed successfully.'
    );


/* ==========================================================
   ONBOARDING DOCUMENTS
========================================================== */

INSERT INTO onboarding_documents (
    onboarding_id,
    document_name,
    file_path,
    status
) VALUES

    /* ======================================================
       Employee 1 - Administrator
    ====================================================== */

    (
        1,
        'Resume',
        'documents/admin/resume.pdf',
        'Verified'
    ),
    (
        1,
        'NBI Clearance',
        'documents/admin/nbi_clearance.pdf',
        'Verified'
    ),
    (
        1,
        'Medical Certificate',
        'documents/admin/medical_certificate.pdf',
        'Verified'
    ),
    (
        1,
        'Birth Certificate',
        'documents/admin/birth_certificate.pdf',
        'Verified'
    ),
    (
        1,
        'Government IDs',
        'documents/admin/government_ids.pdf',
        'Verified'
    ),

    /* ======================================================
       Employee 2 - HR Staff
    ====================================================== */

    (
        2,
        'Resume',
        'documents/hrstaff/resume.pdf',
        'Verified'
    ),
    (
        2,
        'NBI Clearance',
        'documents/hrstaff/nbi_clearance.pdf',
        'Verified'
    ),
    (
        2,
        'Medical Certificate',
        'documents/hrstaff/medical_certificate.pdf',
        'Verified'
    ),
    (
        2,
        'Birth Certificate',
        'documents/hrstaff/birth_certificate.pdf',
        'Verified'
    ),
    (
        2,
        'Government IDs',
        'documents/hrstaff/government_ids.pdf',
        'Verified'
    ),

    /* ======================================================
       Employee 3 - Manager
    ====================================================== */

    (
        3,
        'Resume',
        'documents/manager/resume.pdf',
        'Verified'
    ),
    (
        3,
        'NBI Clearance',
        'documents/manager/nbi_clearance.pdf',
        'Verified'
    ),
    (
        3,
        'Medical Certificate',
        'documents/manager/medical_certificate.pdf',
        'Verified'
    ),
    (
        3,
        'Birth Certificate',
        'documents/manager/birth_certificate.pdf',
        'Verified'
    ),
    (
        3,
        'Government IDs',
        'documents/manager/government_ids.pdf',
        'Verified'
    ),

    /* ======================================================
       Employee 4 - Cashier
    ====================================================== */

    (
        4,
        'Resume',
        'documents/cashier/resume.pdf',
        'Verified'
    ),
    (
        4,
        'NBI Clearance',
        'documents/cashier/nbi_clearance.pdf',
        'Verified'
    ),
    (
        4,
        'Medical Certificate',
        'documents/cashier/medical_certificate.pdf',
        'Verified'
    ),
    (
        4,
        'Birth Certificate',
        'documents/cashier/birth_certificate.pdf',
        'Verified'
    ),
    (
        4,
        'Government IDs',
        'documents/cashier/government_ids.pdf',
        'Verified'
    );