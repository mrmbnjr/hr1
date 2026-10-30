# HR1 — Project Instructions

## 1. Project Overview

HR1 is a PHP 8.1+ / MySQL 8.0 Human Resource Management System (HRMS) focused on Core HR and Recruitment & Onboarding.

### Technology and Architecture
- Backend: Vanilla PHP, Composer autoloading, PDO.
- Database: MySQL 8.0.
- Frontend: HTML, CSS, and vanilla JavaScript.
- Local environment: XAMPP (Apache and MySQL).
- Entry point: `public/index.php` using the existing `?page=...` routing pattern.
- Application code: `src/`.
- Views: `resources/views/`.
- Database schema and seeds: `database/`.

Follow the existing architecture. Do not introduce frameworks, architectural patterns, or unnecessary abstractions without a clear need.

## 2. Development Principles

- Understand the existing implementation before changing it.
- Prefer small, focused changes over unnecessary rewrites.
- Reuse existing services, helpers, components, and conventions.
- Avoid duplicate business logic, redundant queries, and unnecessary files.
- Keep controllers, services, database operations, and views consistent with their existing responsibilities.
- Do not implement speculative features or expand the requested scope.
- Preserve compatibility with the existing application unless a change explicitly requires otherwise.

### Investigation

Before implementing a change, inspect only the relevant parts of the application:

- Existing routes and front-controller behavior.
- Related controllers, services, models, and views.
- Relevant database tables, relationships, and queries.
- Existing JavaScript, CSS, and shared UI components.
- Related documentation when it affects the requested work.

Trace the complete feature flow when changing business logic. Do not assume that a feature is isolated to its visible page.

Do not read every project document for every task. Consult:
- `README.MD` for project setup and application overview.
- `DEPLOYMENT.md` for hosting and deployment concerns.
- `database/schema.sql` for database structure.
- Relevant source files for actual implementation details.

When documentation conflicts with the implementation, investigate the discrepancy rather than assuming either is correct.

## 3. Implementation and Approval Boundaries

### Small Fixes

You may directly implement clearly understood, localized, low-risk fixes.

Examples:
- Correcting validation or display logic.
- Fixing a broken button or form submission.
- Correcting a SQL query.
- Fixing CSS responsiveness.
- Resolving a PHP warning or syntax error.

Do not stop for approval when the requested behavior is clear and the change is safe.

### Changes Requiring Approval

Obtain approval before making unrequested changes involving:

- Database schema changes or potentially destructive data migrations.
- Authentication, authorization, session handling, or security policies.
- Recruitment pipeline or employee lifecycle changes.
- Significant changes to business rules or approval responsibilities.
- Adding, removing, or updating production dependencies.
- External integrations, email behavior, or AI provider configuration changes.
- Deployment, production data, or infrastructure modifications.

An explicit user request authorizes the requested implementation, but does not authorize unrelated destructive operations.

When requirements are materially ambiguous, ask a focused clarification instead of inventing business rules.

## 4. Repository Protection

Protect existing user work at all times.

- Inspect `git status --short` before modifying files when Git is available.
- Review existing changes before touching affected files.
- Do not overwrite unrelated modifications.
- Never reset, discard, stash, commit, or push changes without explicit approval.
- Review the final diff before completing the task.
- Avoid modifying generated files, dependencies, or environment-specific files unless required.

Do not claim that existing changes are yours.

## 5. HRMS Business Rules

Preserve the established HRMS workflows and business rules.

### Role-Based Access Control

The established application roles are:

| Role | Responsibility |
|---|---|
| ADMIN | System administration and authorized HRMS operations |
| HR | Human Resources operations |
| MGR | Manager responsibilities and assigned approvals |
| EMP | Employee self-service and employee-specific operations |

Rules:
- Employees are employee records, not a separate user role.
- Enforce permissions on the server side, not only through navigation or UI visibility.
- Apply role restrictions at the appropriate module and operation level.
- Do not assume every user account has a linked employee record.
- Respect nullable employee associations and existing authentication helpers.
- Do not bypass existing authorization middleware or permission checks.

### Recruitment and Onboarding

The established recruitment progression is:

`Submitted → Under Review → Interview → Selection → Offer → Hired`

`Rejected` is a terminal outcome available at applicable pre-hire stages.

Preserve these principles:
- Interviewers submit evaluations and recommendations.
- HR makes the separate decision to advance applicants to Selection or reject them.
- An interviewer recommendation is not automatically a final hiring decision.
- AI screening results are decision-support information, not automatic acceptance or rejection.
- Offer and pre-employment requirements must be respected before final hiring.
- The employee's assigned role must follow the configured position-to-role relationship rather than assuming every new hire receives EMP.

Do not casually rename statuses, skip stages, or change who is authorized to make a decision.

## 6. Coding and Security Standards

### PHP and Database
- Follow existing Composer autoloading and PHP coding conventions.
- Use the existing database connection and service patterns.
- Use prepared PDO statements for database queries.
- Validate and normalize user input on the server.
- Use transactions when multiple related database operations must succeed or fail together.
- Handle exceptions and database failures appropriately.
- Avoid unnecessary queries and duplicated database logic.

### Security
- Escape rendered user-provided output using the existing project conventions.
- Enforce server-side authentication and authorization.
- Protect state-changing requests using the existing CSRF protection mechanisms.
- Validate uploaded files, file types, sizes, and storage locations.
- Never expose credentials, API keys, passwords, or sensitive environment values.
- Never place secrets in source code or commit `.env` files.

### Frontend
- Follow existing HTML, CSS, and vanilla JavaScript patterns.
- Reuse shared styles, CSS variables, and UI components.
- Maintain responsive layouts and accessible form controls.
- Keep interfaces consistent with the surrounding module.
- Avoid unnecessary visual effects, arbitrary colors, duplicated styles, and unrelated redesigns.
- Keep JavaScript behavior aligned with the existing backend validation and authorization.

## 7. Database Change Requirements

Before changing database-related functionality:

- Inspect the actual table definitions and foreign-key relationships.
- Check every affected query and dependent feature.
- Keep column names, data types, nullability, defaults, and enum values consistent.
- Update `database/schema.sql` and relevant seed data when a schema change is explicitly requested.
- Consider existing records and backward compatibility.
- Avoid destructive changes that could remove existing user data.
- Never execute schema or data changes against a production database without explicit authorization.

Do not assume that the database structure matches old documentation or previous implementations. Verify the current schema.

## 8. Verification and Quality Checks

There is currently no PHPUnit test suite.

Run checks appropriate to the changes made rather than executing every command unnecessarily.

### PHP Changes
Run `php -l` on every changed PHP file.

### Dependency Changes
Run:
- `composer validate --strict`
- `composer install`
- `composer check-platform-reqs`

### Broader Application or CI-Related Changes
The project's CI checks include:
- Composer validation.
- Dependency installation.
- Platform requirement checks.
- PHP syntax linting outside `vendor/`.
- Docker image build.

Run the relevant checks when the affected functionality warrants them.

Do not claim that a test, build, or verification passed unless it was actually executed successfully.

If a check cannot be performed, explain why.

## 9. Completion Requirements

Before considering a task complete:

1. Confirm the requested functionality has been implemented.
2. Review affected files for unintended changes.
3. Check for regressions in related functionality.
4. Run relevant verification commands.
5. Review the final Git diff when available.

Provide a concise completion report containing:

- **Implemented:** What changed.
- **Files affected:** Important modified files.
- **Verification:** Commands executed and their results.
- **Limitations:** Any unverified behavior, remaining issues, or required manual steps.

Do not report incomplete work as finished.