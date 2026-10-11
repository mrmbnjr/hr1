# HR1 — HR & Recruitment Management System

An AI-assisted HR and recruitment platform for **RAM-YUM**. It handles the full hiring
lifecycle — public job vacancies, applicant submission, resume and credential screening,
interviews, hiring, onboarding and employee self-service — with Google Gemini used to
score resumes and validate submitted documents.

![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white)
![Gemini](https://img.shields.io/badge/AI-Google%20Gemini-8E75B2)
![Docker](https://img.shields.io/badge/Docker-Supported-2496ED?logo=docker&logoColor=white)
![CI](https://img.shields.io/badge/CI-GitHub%20Actions-2088FF?logo=githubactions&logoColor=white)

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [AI Capabilities](#ai-capabilities)
- [Tech Stack](#tech-stack)
- [Requirements](#requirements)
- [User Roles](#user-roles)
- [Application Routing](#application-routing)
- [Getting Started](#getting-started)
  - [Standard Installation](#standard-installation)
  - [Run with Docker](#run-with-docker)
- [Configuration](#configuration)
- [Usage](#usage)
- [Project Structure](#project-structure)
- [Database](#database)
- [Testing & CI](#testing--ci)
- [Security](#security)
- [Deployment](#deployment)
- [Development Notes](#development-notes)

---

## Overview

| | |
| --- | --- |
| **Problem** | HR spends too much time manually reviewing resumes and credentials, and hiring data is spread across disconnected tools. |
| **Solution** | A single PHP/MySQL portal that runs the hiring pipeline end to end and uses AI to screen applicants. |
| **Users** | Administrators, HR Staff, Managers and Employees — plus public (unauthenticated) applicants. |
| **Goal** | Automate resume analysis, rank candidates, and carry hires through onboarding into employee records. |

The system is deployed as the document root of a subdomain (e.g. `https://hr1.yourdomain.com`)
or as a subfolder during local development (e.g. `http://localhost/hr1/`). `index.php` in the
project root is the single front controller.

---

## Features

### Authentication & Security

- Username/password login followed by a **6-digit email OTP** (5-minute expiry, 5 attempts, 60-second resend cooldown).
- Session hardening: `use_strict_mode`, HTTP-only cookies, `SameSite=Lax`, secure cookie over HTTPS, session ID regeneration on login.
- Role-Based Access Control (RBAC) with four roles.
- Forced password change on first login (`must_change_password`).
- Inactive-account blocking.
- Self-service password reset by email code **or** by username, plus a "Contact HR" escalation path.

### Recruitment & Job Postings

- Create, edit, close and delete job postings.
- Per-posting vacancies, applicant limits, deadlines and status.
- Auto-generated application tokens for shareable apply links.
- One posting per position, with closed-posting tracking.

### Applicant Management & AI Screening

- Public careers page listing only open, non-expired, non-full vacancies.
- Application form with resume and academic-document upload.
- AI resume scoring (overall, skills, experience, education, keyword).
- AI credential/document validation with identity and consistency checks.
- Applicant review workspace, resume and document download, evaluation re-run.
- Candidate ranking via match scores and recommendations.

### Interviews & Hiring

- Schedule interviews (date, time, type, location/link, interviewer, notes) with an automatic email to the applicant.
- Hire or reject applicants.
- Hiring creates the employee account and emails the login credentials.

### Onboarding

- Onboarding phases per hire.
- Document requests issued by HR, submitted by the employee, then verified or rejected.
- Backs the employee "Assigned Requests" workflow.

### Employee Self-Service

- Employee dashboard.
- My Requests (create, track, update).
- Assigned Requests (submit documents/actions requested by HR).
- Profile management and password change.

### Human Capital

- Department and position management.
- Department overview with Active/Inactive status.
- Department statistics and position statistics.

### Employee Records

- Employee directory with record view and profile editing.

### User Management & Administration

- Admin-only user management (list, edit profile, enable/disable, action).
- Activity logging across the application.

### Dashboards, Reports & Statistics

- Role-aware dashboard with growth chart data.
- Recruitment statistics, department statistics and position statistics.

### Data Retention & Audit

- Configurable retention for rejected and closed-posting applications.
- Opportunistic cleanup on page loads, plus a last-run marker.
- Activity log of user actions.

---

## AI Capabilities

AI features are powered by **Google Gemini** through `App\Services\GeminiService`. All calls
use structured JSON output (a response schema) and never make the final hiring decision — the
model reports findings and the application applies the rules.

| Operation | Method | Purpose |
| --- | --- | --- |
| Resume screening | `evaluateResume()` | Scores a resume against a job posting (overall, skills, experience, education, keyword) and returns a recommendation. |
| Credential validation | `evaluateAcademicDocuments()` | Reviews all submitted certificates in a single pass — readability, document type, institution, identity match. |
| Submission validation | `validateSubmission()` | One request covering the resume and every credential; reports identity, education, experience and credential consistency. |

**Recommendation values:** `Highly Recommended`, `Recommended`, `Consider`, `Not Recommended`.

**Guardrails built into the prompts:**

- Evaluation is restricted to job-related qualifications; protected characteristics (age, gender, race, religion, disability, etc.) are explicitly excluded.
- A resume whose visible name does not match the applicant (`identity_match = false`) is forced to score 0 and `Not Recommended`.
- Document text is treated as untrusted input; embedded instructions are ignored.
- Accepted file types: `PDF`, `JPG/JPEG`, `PNG`; max 5 MB per file, with a combined batch cap.

Configure with `GEMINI_API_KEY`, `GEMINI_MODEL` (default `gemini-2.5-flash`) and `GEMINI_TIMEOUT`.

---

## Tech Stack

| Layer | Technology |
| --- | --- |
| UI/UX Design | Figma |
| Frontend | HTML, CSS, JavaScript (vanilla) |
| Backend | PHP 8.1+ (8.2/8.3 recommended) |
| Database | MySQL 8.0 |
| Web Server | Apache / LiteSpeed with `mod_rewrite` |
| Dependencies | Composer — `phpmailer/phpmailer ^7.1` |
| AI | Google Gemini API |
| Email | SMTP via PHPMailer |
| Containers | Docker, Docker Compose |
| CI | GitHub Actions |
| Storage | Local filesystem (`storage/`) |

---

## Requirements

**Server / runtime**

- PHP **8.1+** (8.2 or 8.3 recommended; the Docker image ships 8.2)
- PHP extensions: `pdo_mysql`, `mbstring`, `curl`, `fileinfo`, `openssl`
- MySQL 8.0 (or a compatible MySQL/MariaDB)
- Composer 2
- Apache or LiteSpeed with `mod_rewrite` (`.htaccess` enforces HTTPS, blocks private folders and sets security headers)

**Optional**

- Docker Desktop, or Docker Engine with the Compose plugin

**Non-functional goals:** fast processing, secure data handling, responsive design, ease of use.

---

## User Roles

Applicants are **public, unauthenticated** visitors — they do not have accounts. The four
authenticated roles (stored as `role_code`) are:

| Role code | Role name | Primary modules |
| --- | --- | --- |
| `ADMIN` | System Administrator | Everything, plus User Management, Human Capital, System Settings |
| `MGR` | Manager | Dashboard, Recruitment, Applicants, Interviews, Hiring Decisions, Statistics |
| `HR` | HR Staff | Dashboard, Recruitment, Applicants, Interviews, Onboarding, Employee Requests |
| `EMP` | Employee | Dashboard, My Requests, Assigned Requests, Profile |

`ADMIN` always bypasses role checks (full access). Guarding is enforced in controllers via
`Auth::requireRole([...])` and `Auth::requireLogin()`.

---

## Application Routing

`index.php` dispatches on a single query parameter, `?page=<name>`. Representative routes:

| Group | Routes |
| --- | --- |
| Public | `home`, `careers`, `job-details`, `apply`, `submit-application`, `application-success` |
| Auth | `login`, `verify-otp`, `resend-otp`, `logout`, `reset-password`, `reset-send-code`, `reset-verify-code`, `reset-by-username`, `reset-hr-request` |
| Dashboard | `dashboard`, `dashboard-growth` |
| Applicants | `applicants`, `review`, `download-resume`, `download-academic-document`, `evaluate-resume`, `evaluate-academic-document`, `scheduleInterview`, `hire-applicant`, `reject-applicant` |
| Recruitment | `recruitment`, `create`, `store`, `edit`, `update`, `close`, `delete`, `statistics` |
| Onboarding | `onboarding`, `onboarding-view`, `onboarding-request-document`, `onboarding-update-document`, `onboarding-delete-document`, `onboarding-verify-document`, `onboarding-reject-document` |
| Human Capital | `human-capital`, `department-statistics`, `position-statistics`, `save-department`, `delete-department`, `create-position`, `save-position`, `delete-position`, `get-department` |
| Employees | `employee-records`, `view`, `employee-requests`, `employee-requests-update`, `password-reset-request-update` |
| Self-service | `my-requests`, `my-requests-create`, `my-requests-update`, `assigned-requests`, `assigned-requests-submit`, `assigned-requests-download` |
| Admin | `user-management`, `edit-user-profile`, `user-management-profile-update`, `user-management-action` |
| Account | `profile`, `profile-action`, `change-password` |

Unknown pages return **404** (`resources/views/misc/soon.php`).

---

## Getting Started

### Standard Installation

**1. Install dependencies**

```sh
composer install --no-dev --optimize-autoloader
```

This installs `phpmailer/phpmailer`. The `vendor/` folder is included in the deployment
package, so a fresh deployment can skip this step.

**2. Create the `.env` file**

Create a `.env` in the project root and fill in the database, Gemini and mail values.
See [Configuration](#configuration) for the full variable reference.

> [!NOTE]
> This repository does **not** ship a `.env` (`.gitignore` excludes `.env.*`).
> Create `.env` manually using the reference table below.

**3. Import the database**

Into an empty MySQL database, in this order:

1. `database/schema.sql` — full schema (already includes every migration)
2. `database/seeds/seed.sql` — demo data (optional)

**4. Log in and secure the account**

Log in with a seeded account and **change the password immediately**.

For step-by-step Hostinger instructions, see [DEPLOYMENT.md](DEPLOYMENT.md).

### Run with Docker

The Compose file uses the published Docker Hub image
[`mrmbnjr/hr1:latest`](https://hub.docker.com/r/mrmbnjr/hr1).
The image contains the application; the Compose file and database SQL files
are provided separately in the deployment bundle. Docker Hub does not store
the ZIP bundle or your database volume/uploads.

#### Get the deployment bundle

Download [`hr1-docker-share.zip`](./hr1-docker-share.zip) from this
repository. It contains only the files needed to start the app
(`docker-compose.yml`, `docker/.env.example`, `database/schema.sql`, and
`database/seeds/seed.sql`).

**Never add `.env` or `docker\.env` to the ZIP**; those files may contain
credentials. The example environment file is safe to share.

#### Start it

**Prerequisites:** Docker Desktop on Windows/macOS, or Docker Engine with the
Compose plugin on Linux. Extract the ZIP, open PowerShell (or a terminal) in
the extracted folder, and follow these steps:

1. If the Docker Hub repository is private, sign in with an account that has
   access:

```powershell
   docker login
```

2. Create a private environment file and edit it:

```powershell
   Copy-Item docker\.env.example docker\.env
   notepad docker\.env
```

   Set unique values for `DOCKER_DB_PASSWORD` and
   `DOCKER_MYSQL_ROOT_PASSWORD`. Add your own Gemini and SMTP credentials if
   AI analysis and email are needed. Do not share this file with anyone.

3. For a disposable demo with sample data, set
   `DOCKER_SEED_DEMO_DATA=true` in `docker\.env` **before the first startup**.
   Seeding is off by default. Seeded demo accounts use `admin123`; change that
   password immediately and never use demo seeding for real data.

4. Pull the application image from
   [Docker Hub](https://hub.docker.com/r/mrmbnjr/hr1) and start the
   containers:

```powershell
   docker compose --env-file docker\.env pull
   docker compose --env-file docker\.env up -d --no-build
```

5. Check that the containers are running, then open
   `http://localhost:8080` (or the port set in `DOCKER_HTTP_PORT`):

```powershell
   docker compose --env-file docker\.env ps
```

**Compose services**

| Service | Role |
| --- | --- |
| `mysql` | MySQL 8.0 with a health check |
| `schema-init` | One-shot job: imports `schema.sql` into an empty DB and optionally seeds demo data |
| `analysis` | PHP 8.2 + Apache application container |

**Data and upgrades**

- Uploaded documents and MySQL data live in Docker volumes (`hr1_mysql_data`, `hr1_uploads`). **Back them up before moving the project** — they are not part of the source folder.
- When upgrading an existing MySQL volume, keep the exact database and root passwords originally used to create it. Changing Compose environment values alone does not rotate credentials in an existing volume.
- If upgrading an older running container that already has uploads, copy those files out of the old container before recreating it.

> [!WARNING]
> Demo accounts use the password `admin123`. Change it immediately, and never enable demo seeding on a real deployment.

---

## Configuration

Configuration is read from `.env` (loaded by `src/Config/env.php`). Environment variables set by
Docker/Compose/host take precedence over the local file.

### Application

| Variable | Default | Description |
| --- | --- | --- |
| `APP_NAME` | `RAM-YUM HR1` | Application name. |
| `APP_ENV` | `production` | Environment label (`local` / `production`). |
| `APP_DEBUG` | `false` | Show detailed errors. **Keep `false` in production.** |
| `APP_URL` | *(empty)* | Absolute base URL, used where a full URL is required (e.g. email links). |
| `APP_TIMEZONE` | `Asia/Manila` | PHP timezone. |
| `APP_BASE_PATH` | *(auto)* | URL prefix when served from a subfolder (e.g. `/hr1`). Leave empty to auto-detect. |

### Database

| Variable | Default | Description |
| --- | --- | --- |
| `DB_HOST` | `localhost` | MySQL host. |
| `DB_PORT` | `3306` | MySQL port. |
| `DB_NAME` | `hr1_db` | Database name. |
| `DB_USER` | `root` | Database user. |
| `DB_PASSWORD` | *(empty)* | Database password. |

### AI (Google Gemini)

| Variable | Default | Description |
| --- | --- | --- |
| `GEMINI_API_KEY` | *(empty)* | API key. Required for any AI feature. |
| `GEMINI_MODEL` | `gemini-2.5-flash` | Model name. |
| `GEMINI_TIMEOUT` | `60` | Request timeout in seconds (minimum 10). |

### Mail (SMTP)

| Variable | Default | Description |
| --- | --- | --- |
| `MAIL_HOST` | *(empty)* | SMTP host (e.g. `smtp.gmail.com`). |
| `MAIL_PORT` | `587` | SMTP port. |
| `MAIL_ENCRYPTION` | `tls` | Encryption (`tls` / `ssl`). |
| `MAIL_USERNAME` | *(empty)* | SMTP username. |
| `MAIL_PASSWORD` | *(empty)* | SMTP password. For Gmail this must be an **App Password**. |
| `MAIL_FROM_ADDRESS` | *(empty)* | From address. |
| `MAIL_FROM_NAME` | `RAM-YUM Recruitment` | From display name. |
| `HR_NOTIFICATION_EMAIL` | *(falls back to `MAIL_FROM_ADDRESS`)* | Inbox that receives "Contact HR" password-reset requests. |

### Data retention

| Variable | Default | Description |
| --- | --- | --- |
| `REJECTED_APPLICANT_RETENTION_DAYS` | `30` | Days a rejected application is kept before it is hidden. |
| `CLOSED_APPLICATION_RETENTION_DAYS` | `30` | Days after a posting closes before its non-hired applications are removed. |
| `APPLICATION_CLEANUP_INTERVAL` | `3600` | Minimum seconds between opportunistic cleanup runs (`0` = every load). |

### Docker-only (read from `docker/.env`)

| Variable | Default | Description |
| --- | --- | --- |
| `DOCKER_DB_NAME` | `hr1_db` | Database name created in the container. |
| `DOCKER_DB_USER` | `hr1` | Database user created in the container. |
| `DOCKER_DB_PASSWORD` | *(required)* | Application DB password. |
| `DOCKER_MYSQL_ROOT_PASSWORD` | *(required)* | MySQL root password. |
| `DOCKER_SEED_DEMO_DATA` | `false` | Load demo data into a new database. Set **before** first startup. |
| `DOCKER_APP_ENV` | `production` | `APP_ENV` inside the container. |
| `DOCKER_APP_DEBUG` | `false` | `APP_DEBUG` inside the container. |
| `DOCKER_HTTP_PORT` | `8080` | Host port mapped to container port 80. |
| `APP_TIMEZONE` | `Asia/Manila` | Container timezone. |
| `GEMINI_*`, `MAIL_*`, `HR_NOTIFICATION_EMAIL` | — | Forwarded into the container. |

---

## Usage

### Applicant (public)

1. Open `?page=careers` to browse open vacancies.
2. Open a vacancy (`?page=job-details&id=...`) and start the application (`?page=apply&token=...`).
3. Upload a resume and academic documents and submit.
4. The submission is validated immediately by AI; the applicant lands on the success page.

### HR / Recruiter

1. Sign in (`?page=login`) → enter the email OTP → dashboard.
2. Manage vacancies under `?page=recruitment` (create, edit, close, delete).
3. Review applicants under `?page=applicants`; open a candidate with `?page=review`.
4. Run **Evaluate Resume** / **Evaluate Academic Document** to get AI scores and consistency findings.
5. **Schedule Interview** (emails the applicant) and then **Hire** or **Reject**.
6. Hiring creates the employee account and emails the credentials.
7. Continue the hire through `?page=onboarding` (request, verify or reject documents).

### Manager

Same as HR for recruitment, applicants, interviews, hiring decisions and statistics.

### Administrator

- Manage accounts under `?page=user-management`.
- Maintain departments and positions under `?page=human-capital`.
- Review department and position statistics.

### Employee

- Dashboard with growth data.
- Create and track requests under `?page=my-requests`.
- Respond to HR document requests under `?page=assigned-requests`.
- Manage profile and password under `?page=profile` / `?page=change-password`.

---

## Project Structure

`index.php` is the front controller in the project root (the subdomain document root). The
`public/` folder contains only static assets.

```
hr1/                          # Subdomain document root
├── index.php                 # Front controller (routing)
├── .htaccess                 # HTTPS, blocks private folders, security headers
├── .user.ini                 # PHP limits (upload size, memory, logging)
├── .env                      # Configuration (never public, never committed)
├── robots.txt
├── composer.json / composer.lock
├── Dockerfile                # Multi-stage production image (PHP 8.2 + Apache)
├── docker-compose.yml        # mysql + schema-init + analysis services
├── DEPLOYMENT.md             # Hostinger deployment guide
│
├── docker/                   # docker/.env.example, php-production.ini
├── .github/workflows/ci.yml  # Composer validation, PHP lint, Docker build
│
├── src/                      # Application code (blocked from the browser)
│   ├── bootstrap.php         # Paths, composer, .env, PHP settings, session
│   ├── App/
│   │   ├── Controllers/      # Auth, Dashboard, Applicant, Recruitment, Onboarding,
│   │   │                     # HumanCapital, EmployeeRecords, EmployeeRequests,
│   │   │                     # AssignedRequests, UserManagement, Profile, PasswordReset
│   │   ├── Models/           # Applicant, Recruitment, Onboarding, EmployeeRecords,
│   │   │                     # EmployeeRequests, HumanCapital, Profile, User, ...
│   │   └── Services/         # GeminiService, MailService, Auth, ActivityLogger,
│   │                         # Resume/Academic/Application validation, retention
│   ├── Core/
│   │   ├── Database.php
│   │   └── helpers.php       # env(), config(), url(), asset(), base_url()
│   └── Config/
│       ├── app.php           # app name, debug, timezone, retention windows
│       ├── database.php
│       ├── env.php           # .env loader
│       ├── path.php          # BASE_PATH, SRC_PATH, VIEW_PATH, PUBLIC_PATH, STORAGE_PATH
│       └── session.php
│
├── resources/views/          # PHP templates (blocked from the browser)
│   ├── auth/ public/ dashboard/ applicants/ recruitment/ onboarding/
│   ├── human-capital/ employee-records/ employee-requests/ assigned-requests/
│   ├── user-management/ profile/ reset-password/ misc/
│   └── includes/             # header, footer, navbar, sidebar, scripts
│
├── public/assets/            # css/, js/, images/ (the only public folder)
│
├── storage/                  # Private files (blocked from the browser)
│   ├── uploads/
│   │   ├── applications/     # Resumes + academic documents
│   │   ├── assigned-requests/
│   │   └── pending/          # Staged uploads before submission
│   ├── logs/php-error.log
│   └── retention.last-run
│
├── database/
│   ├── schema.sql            # Final schema (already includes all migrations)
│   ├── migrations/           # Only for upgrading an older database
│   └── seeds/seed.sql        # Demo data
│
└── vendor/                   # Composer packages (blocked from the browser)
```

---

## Database

`schema.sql` defines **21 tables**, including:

`roles`, `users`, `login_otps`, `password_reset_codes`, `password_reset_requests`,
`departments`, `positions`, `job_postings`, `applicants`, `applications`,
`application_validations`, `ai_screening`, `interviews`, `onboarding`,
`onboarding_documents`, `onboarding_phases`, `employees`, `employee_number_sequence`,
`activity_logs`, `employee_requests`, `academic_document_evaluations`.

### Demo accounts (`seed.sql`)

All seeded accounts share the password **`admin123`**.

| Username | Role | Notes |
| --- | --- | --- |
| `admin` | `ADMIN` | System Administrator |
| `rafael.mendoza` | `MGR` | Manager |
| `maria.santos` | `HR` | HR Staff |
| `juan.delacruz` | `EMP` | Employee |
| `sofia.cabrera` | `EMP` | Must change password on first login |

The seed is self-contained (it also fills the onboarding "assigned requests" columns) and
generates synthetic candidates so the demo has data to rank.

### Migrations

`database/migrations/` is **only** needed to upgrade an older installation — a fresh
`schema.sql` already includes every migration. Examples:

- `20261002_extend_ai_validation_for_processing.sql`
- `20261003_add_password_reset.sql`
- `20261004_add_department_status.sql`

---

## Testing & CI

There is no PHPUnit test suite. Automated checks run through **GitHub Actions**
(`.github/workflows/ci.yml`) on every push to `main` and on pull requests:

1. `composer validate --strict`
2. `composer install` and `composer check-platform-reqs`
3. `php -l` lint of every `.php` file outside `vendor/`
4. Docker image build and inspection

---

## Security

| Area | Implementation |
| --- | --- |
| Authentication | Password hashing (`password_hash`) + email OTP |
| Authorization | Role-Based Access Control (`ADMIN`, `HR`, `MGR`, `EMP`) |
| Data protection | Prepared SQL statements (PDO) |
| Input protection | Output escaping (`htmlspecialchars`) + server-side file validation (extension + MIME) |
| Session | HTTP-only cookie, `SameSite=Lax`, strict mode, ID regeneration on login |
| Account security | Inactive-account blocking, forced first-login password change |
| Transport | HTTPS enforced via `.htaccess` |
| Filesystem | `src/`, `vendor/`, `storage/`, `database/`, `resources/` and dotfiles are blocked from direct access |
| Audit | Activity logging (`activity_logs`) |

> [!IMPORTANT]
> Never commit `.env`, and always change the seeded `admin123` password before going live.

---

## Deployment

A complete Hostinger subdomain walkthrough (subdomain creation, PHP version, database import,
permissions, SSL and smoke tests) is in [DEPLOYMENT.md](DEPLOYMENT.md).

---

## Development Notes

### URL helpers

Never hard-code `/hr1/public/...`. Use the helpers so the app works both at a subdomain root
and in a local subfolder:

| Context | Usage |
| --- | --- |
| PHP and views | `url('?page=dashboard')` and `asset('css/style.css')` |
| JavaScript | Relative URLs, such as `'?page=login'` |

`asset()` appends a `?v=<mtime>` cache-buster so browsers fetch updated CSS/JS after a deploy.

### Conventions

- `index.php` routes on `?page=`; controllers live in `App\Controllers`, data access in `App\Models`.
- Configuration lives in `src/Config/*.php` and is read through `config('file.key')`.
- Environment values are read through `env('KEY', $default)`.
- Controllers emit JSON for AJAX endpoints and render views for pages.

---

## License

Proprietary. See `composer.json` (`"license": "proprietary"`).
