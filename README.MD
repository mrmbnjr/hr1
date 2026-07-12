1. Define the Problem
- Problem: HR spends too much time reviewing resumes manually.
- Users: Admin, Hr and Manager
- Goal: Automatically analyze resumes and rank candidates.

2. Requirements
<h3>What the system can do.</h3>

important
- Upload resume
- Analyze resume
- Compare with job description
- Display matching score
- Generate recommendations

future functions
- Rankings
- sent email automatically

<h3>How the system should perform.</h3>

- Fast processing
- Secure data
- Responsive design
- Easy to use
- User Friendly

3. Plan the Features
Authentication
├── Login
├── Logout

Resume Module
├── Upload PDF/DOCX
├── Extract text
├── AI Analysis

Results
├── Match Score
├── Skills Found
├── Missing Skills
├── Download Report

4. Database Design
-----
Users
-----
id
name
email
password

-------
Resumes
-------
id
user_id
filename
upload_date

-------
Results
-------
id
resume_id
score
recommendation

5. Tech stack
UI/UX Design: Figma
Frontend: html, css, js
backend: js and php
Database: mysql
AI: Gemini
Storage: local storage

6. Project Structure

hr1/
│
├── client/
│   ├── css/
│   ├── js/
│   ├── images/
│   ├── includes/
│   │   ├── header.php
│   │   ├── footer.php
│   │   └── navbar.php
│   ├── login.php
│   ├── dashboard.php
│   └── index.php
│
├── server/
│   ├── config/
│   │   └── database.php
│   ├── controllers/
│   │   ├── AuthController.php
│   │   ├── EmployeeController.php
│   │   └── ApplicantController.php
│   ├── models/
│   │   ├── User.php
│   │   ├── Employee.php
│   │   └── Applicant.php
│   ├── middleware/
│   │   └── auth.php
│   └── helpers/
│       └── functions.php
│
├── uploads/
│   ├── resumes/
│   └── profile_pictures/
│
└── database/
│   └── hrms.sql
│
├── database/
│    └── hrms.sql
│