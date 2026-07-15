CREATE DATABASE ramyum;

USE ramyum;

CREATE TABLE users(

id INT AUTO_INCREMENT PRIMARY KEY,

username VARCHAR(50) UNIQUE,

password VARCHAR(255)

);

UPDATE users
SET password = '$2y$10$B1q62GRANViJl4Dv1b0by.V93S0zSl96yNcodzX6y77WsDxKEjJWW'
WHERE username = 'admin';

-- ==========================================
-- RAM-YUM MMS Prototype Database
-- Authentication & Role-Based Access Control
-- ==========================================

CREATE DATABASE IF NOT EXISTS ramyum;
USE ramyum;

-- ==========================================
-- Roles
-- ==========================================

CREATE TABLE roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_code VARCHAR(30) NOT NULL UNIQUE,
    role_name VARCHAR(50) NOT NULL
);

INSERT INTO roles (role_code, role_name) VALUES
('admin', 'Administrator'),
('hr', 'HR Staff'),
('manager', 'Manager'),
('cashier', 'Cashier'),
('warehouse', 'Warehouse Staff'),
('accountant', 'Accountant');

-- ==========================================
-- Employees
-- ==========================================

CREATE TABLE employees (
    employee_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_no VARCHAR(20) NOT NULL UNIQUE,

    first_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    last_name VARCHAR(50) NOT NULL,

    email VARCHAR(100) UNIQUE,
    contact_no VARCHAR(20),

    gender ENUM('Male','Female'),

    hire_date DATE,

    status ENUM('Active','Inactive')
    DEFAULT 'Active'
);

-- ==========================================
-- Users
-- ==========================================

CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,

    employee_id INT NOT NULL,
    role_id INT NOT NULL,

    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,

    account_status ENUM('Active','Disabled')
    DEFAULT 'Active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (employee_id)
        REFERENCES employees(employee_id)
        ON DELETE CASCADE,

    FOREIGN KEY (role_id)
        REFERENCES roles(role_id)
);

-- ==========================================
-- Sample Employees
-- ==========================================

INSERT INTO employees
(employee_no, first_name, last_name, email, contact_no, gender, hire_date)
VALUES

('EMP001','System','Administrator','admin@ramyum.com','09111111111','Male','2026-07-01'),

('EMP002','Maria','Santos','hr@ramyum.com','09222222222','Female','2026-07-01'),

('EMP003','Mark','Reyes','manager@ramyum.com','09333333333','Male','2026-07-01'),

('EMP004','John','Dela Cruz','cashier@ramyum.com','09444444444','Male','2026-07-01'),

('EMP005','Peter','Garcia','warehouse@ramyum.com','09555555555','Male','2026-07-01'),

('EMP006','Anna','Lopez','accountant@ramyum.com','09666666666','Female','2026-07-01');

-- ==========================================
-- Sample Users
-- Password for ALL accounts:
-- admin123
-- ==========================================

INSERT INTO users
(employee_id, role_id, username, password)
VALUES

(1,1,'admin',
'$2y$10$B1q62GRANViJl4Dv1b0by.V93S0zSl96yNcodzX6y77WsDxKEjJWW'),

(2,2,'hrstaff',
'$2y$10$B1q62GRANViJl4Dv1b0by.V93S0zSl96yNcodzX6y77WsDxKEjJWW'),

(3,3,'manager',
'$2y$10$B1q62GRANViJl4Dv1b0by.V93S0zSl96yNcodzX6y77WsDxKEjJWW'),

(4,4,'cashier',
'$2y$10$B1q62GRANViJl4Dv1b0by.V93S0zSl96yNcodzX6y77WsDxKEjJWW'),

(5,5,'warehouse',
'$2y$10$B1q62GRANViJl4Dv1b0by.V93S0zSl96yNcodzX6y77WsDxKEjJWW'),

(6,6,'accountant',
'$2y$10$B1q62GRANViJl4Dv1b0by.V93S0zSl96yNcodzX6y77WsDxKEjJWW');