## 1. Define the Problem
- Problem: HR spends too much time reviewing resumes manually.
- Users: Admin, Hr and Manager
- Goal: Automatically analyze resumes and rank candidates.

## 2. Requirements
### What the system can do.

##### Important
- Upload resume
- Analyze resume
- Compare with job description
- Display matching score
- Generate recommendations

##### Future functions
- Rankings
- sent email automatically
- validate the resume immediately when upload by applicant

### How the system should perform.

- Fast processing
- Secure data
- Responsive design
- Easy to use
- User Friendly

## 3. Plan the Features
```
- Authentication
├── Login ✅
├── Logout✅
├── Sessions✅
├── Roles Authorization✅
    ├── Admin
    ├── Hr Staff
    ├── Manager
    └── Employees

- Users View
Admin
├── Dashboard
├── Job Managements
├── Manage Users
├── Roles & Permissions
├── Reports
├── System Settings

Manager
├── Dashboard
├── Job Managements
├── Applications
├── Interviews
    └── Schedule Interview
├── Hiring Decisions
├── Reports


HR Staff
├── Dashboard
├── Job Management
├── Applications
├── Interviews
├── Onboarding

Applicant
├── Home
├── Job Vacancies
    └── Application Form
├── About Us
├── Contacts

- Resume Module
├── Upload PDF/DOCX
├── Extract text
├── AI Analysis

- Results
├── Match Score
├── Skills Found
├── Missing Skills
├── Download Report
```

## 4. Database Design
### Users
- id
- name
- email
- password

### Resumes
- id
- user_id
- filename
- upload_date

### Results
- id
- resume_id
- score
- recommendation

## 5. Tech stack
- UI/UX Design: Figma
- Frontend: html, css, js
- backend: js and php
- Database: mysql
- AI: Gemini
- Storage: local storage

## 6. Project Structure

``` 
hr1/
│
├── src/
│   └── App/
│   │   ├── Controllers/
│   │   │   ├── DashboardController.php
│   │   │   ├── ApplicantController.php
│   │   │   ├── RecruitmentController.php
│   │   │   ├── OnboardingController.php
│   │   │   ├── EmployeeRecordsController.php
│   │   │   ├── EmployeeSelfServiceController.php
│   │   │   ├── LoginController.php
│   │   │   ├── LogoutController.php
│   │   │   ├── ReportController.php
│   │   │   └── ProfileController.php
│   │   ├── Middleware/
│   │   ├── Models/
│   │   └── Services/
│   ├── Routes/
│   │   └── router.php
│   ├── Core/
│   │   └── Database.php
│   └── Config/
│       ├── app.php
│       ├── database.php
│       ├── env.php
│       ├── path.php
│       └── session.php
│
├── resources/
│   │
│   ├── views/
│   │   ├── dashboard/
│   │   │   └── index.php
│   │   ├── applicants/
│   │   │   ├── index.php
│   │   │   ├── create.php
│   │   │   └── view.php
│   │   ├── includes/
│   │   │   ├── footer.php
│   │   │   ├── sidebar.php
│   │   │   ├── navbar.php
│   │   │   └── header.php
│   │   │── applicants/
│   │   │   ├── index.php
│   │   │   ├── create.php
│   │   │   ├── edit.php
│   │   │   └── view.php
│   │   │── recruitment/
│   │   │   ├── index.php
│   │   │   ├── create.php
│   │   │   └── edit.php
│   │   │── onboarding/
│   │   │   └── index.php
│   │   │── employees/
│   │   │   ├── index.php
│   │   │   ├── profile.php
│   │   │   └── records.php
│   │   │── users/
│   │   │   ├── index.php
│   │   │   ├── create.php
│   │   │   └── edit.php
│   │   │── reports/
│   │   │   └── index.php
│   │   │── settings/
│   │   │   └── index.php
│   │   └── profile/
│   │       └── index.php
│   │   
│   └── assets/
│
├── public/
│   ├── index.php
│   └── assets/
public/
└── assets/
│   ├── js/
│   │   ├── script.js          // Shared (sidebar, navbar, helpers)
│   │   ├── dashboard.js
│   │   ├── applicants.js
│   │   ├── recruitment.js
│   │   ├── onboarding.js
│   │   ├── employee.js
│   │   └── login.js│       
│   ├── css
│   │   ├── style.css          // Shared (sidebar, navbar, helpers)
│   │   ├── dashboard.css
│   │   ├── applicants.css
│   │   ├── recruitment.css
│   │   ├── onboarding.css
│   │   ├── employee.css
│   │   └── login.css│       
│   └── images
│
├── tests/
├── database/
│   ├── seeds/
│   └── migration/
├── vendor/
├── composer.json
├── .gitignore
└── README.md
```