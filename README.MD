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

### Security Features
| Security Area        | Implementation                    |
| -------------------- | --------------------------------- |
| **Authentication**   | Password Hashing + Email OTP      |
| **Authorization**    | Role-Based Access Control         |
| **Data Protection**  | Prepared SQL Statements           |
| **Input Protection** | Output Escaping + File Validation |
| **Account Security** | Inactive Account Blocking         |
| **Audit**            | Activity Logging                  |

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

## 4. Set up
### Dependencies
1. `composer install --no-dev --optimize-autoloader` (installs phpmailer/phpmailer; `vendor/` is already included in the deployment package)
2. Copy `.env.example` to `.env` and fill in the database, Gemini and mail values
3. Import `database/schema.sql`, then `database/seeds/seed.sql` (demo data) into an empty MySQL database
4. Change the seeded admin password after the first login

Step-by-step Hostinger instructions: see [DEPLOYMENT.md](DEPLOYMENT.md).

### Run with Docker

Install Docker Desktop (or Docker Engine with the Compose plugin), then copy
`docker/.env.example` to `docker/.env`, replace both database passwords, and run:

```sh
docker compose --env-file docker/.env up --build -d
```

Open `http://localhost:8080` (or the configured `DOCKER_HTTP_PORT`). To load
sample accounts and data into a new database, set `DOCKER_SEED_DEMO_DATA=true`
in `docker/.env` before the first startup. Demo accounts use the password
`admin123`; change it immediately and do not enable demo seeding for a real
deployment. Uploaded documents and MySQL data are stored in Docker volumes.
Back up those volumes before moving the project; they are not included when you
share the source folder.

For OTP email and AI analysis, fill in the SMTP settings and `GEMINI_API_KEY`
in `docker/.env`. When upgrading an existing MySQL volume, keep the exact
database and root passwords originally used to create it; changing Compose
environment values alone does not rotate credentials in an existing volume.

If upgrading an older running container that already has uploads, copy those files
out of that container before recreating it; the new named upload volume cannot
recover files from the old container's writable layer automatically.

## 5. Tech stack
- UI/UX Design: Figma
- Frontend: html, css, js
- backend: js and php
- Database: mysql
- AI: Gemini
- Storage: local storage

## 6. Project Structure

`index.php` is the front controller and lives in the project root, which is the
document root of the subdomain. `public/` only contains static assets.

```
hr1/                          <- subdomain document root
├── index.php                 <- front controller (routing)
├── .htaccess                 <- HTTPS, blocks private folders, security headers
├── .user.ini                 <- PHP limits (upload size, memory, logging)
├── .env / .env.example       <- configuration (never public, never committed)
├── robots.txt
├── composer.json / composer.lock
│
├── src/                      <- application code (blocked from the browser)
│   ├── bootstrap.php         <- paths, composer, .env, PHP settings, session
│   ├── App/
│   │   ├── Controllers/
│   │   ├── Models/
│   │   └── Services/
│   ├── Core/
│   │   ├── Database.php
│   │   └── helpers.php       <- env(), config(), url(), asset()
│   └── Config/
│       ├── app.php
│       ├── database.php
│       ├── env.php
│       ├── path.php
│       └── session.php
│
├── resources/views/          <- PHP templates (blocked from the browser)
│
├── public/
│   └── assets/               <- css/, js/, images/ (the only public folder)
│
├── storage/                  <- private files (blocked from the browser)
│   ├── uploads/
│   │   ├── applications/     <- resumes + academic documents
│   │   └── assigned-requests/
│   └── logs/php-error.log
│
├── database/
│   ├── schema.sql            <- final schema (already includes all migrations)
│   ├── migrations/           <- only for upgrading an older database
│   └── seeds/
└── vendor/                   <- Composer packages (blocked from the browser)
```

### URL helpers
Never hard-code `/hr1/public/...`. In PHP and views use `url('?page=dashboard')`
and `asset('css/style.css')`; in JavaScript use relative URLs such as `'?page=login'`.
