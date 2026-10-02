# Deploying HR1 to a Hostinger subdomain

Target: `https://hr1.yourdomain.com`  |  Requires: PHP 8.1+ (8.2 or 8.3 recommended), MySQL

## 1. Create the subdomain
hPanel → **Domains → Subdomains** → create `hr1`. Note the folder Hostinger assigns
(usually `public_html/hr1` or `domains/yourdomain.com/public_html/hr1`). That folder
is the document root, and it must contain `index.php` directly.

## 2. Set the PHP version
hPanel → **Advanced → PHP Configuration** → choose **PHP 8.2 or 8.3** for the subdomain.
Make sure the extensions `pdo_mysql`, `mbstring`, `curl`, `fileinfo`, `openssl` are ticked (default on Hostinger).

## 3. Create the database
hPanel → **Databases → Management** → create a MySQL database and user. Hostinger prefixes both
(e.g. `u123456789_hr1`). Write down the name, user and password.

## 4. Import the SQL
hPanel → **phpMyAdmin** → open your new database → **Import**:
1. `database/schema.sql`
2. `database/seeds/seed.sql` (demo data — skip it if you want an empty system, but you then need to create an admin user yourself)
3. `database/seeds/onboarding_assigned_requests_seed.sql` (optional, only with the demo seed)

The `migrations/` files are not needed on a fresh database; `schema.sql` already includes them.
For an existing installation that already created `application_validations`, run
`database/migrations/20261002_extend_ai_validation_for_processing.sql` once.

## 5. Upload the files
Upload the **contents** of the `hr1` folder (not the folder itself) into the subdomain folder.
Easiest: upload the zip through **File Manager** and use *Extract*.
Make sure hidden files came along: `.htaccess`, `.user.ini`, `.env`.

Expected result inside the subdomain folder:
```
index.php  .htaccess  .user.ini  .env  src/  resources/  public/  storage/  vendor/  database/ ...
```

## 6. Edit `.env` (File Manager → right-click → Edit)
Set `APP_URL`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`. Keep `APP_ENV=production`, `APP_DEBUG=false`.
Rejected applicants are hidden from the applicant list after 30 days by default; set
`REJECTED_APPLICANT_RETENTION_DAYS` to change that period.
Leave `APP_BASE_PATH` empty. For Gmail OTP emails, `MAIL_PASSWORD` must be a Google **App Password**.

## 7. Permissions
Folders `755`, files `644`. `storage/uploads/` and `storage/logs/` must be writable by PHP
(755 normally works on Hostinger; use 775 only if uploads fail).

## 8. SSL
hPanel → **Security → SSL** → install the free SSL for `hr1.yourdomain.com`. `.htaccess` then redirects all HTTP to HTTPS.

## 9. Smoke test
1. Open `https://hr1.yourdomain.com` → login page with logo and styling.
2. Log in → OTP email arrives → dashboard loads.
3. Open a job's application link, submit a test application (uploads to `storage/uploads/applications`).
4. Check these are blocked (403/404): `/.env`, `/src/`, `/vendor/autoload.php`, `/storage/uploads/`, `/database/schema.sql`.

## Troubleshooting
| Symptom | Check |
|---|---|
| Blank page / "Something went wrong" | `storage/logs/php-error.log`. Temporarily set `APP_DEBUG=true` to see the message, then set it back. |
| "vendor/ folder is missing" | Re-upload `vendor/` (or run `composer install --no-dev` over SSH). |
| Database connection error | `DB_*` in `.env`; Hostinger host is `localhost`, name and user carry the `u123456789_` prefix. |
| Unstyled page / missing images | `public/assets/` uploaded? Hard refresh (Ctrl+F5). |
| Redirect loop | Delete rule 1 (HTTPS) in `.htaccess` temporarily and confirm SSL is active. |
| Times are off by hours | `APP_TIMEZONE` in `.env` (default `Asia/Manila`). |
| Uploads fail | Folder permissions on `storage/uploads/`; PHP upload limits (`.user.ini` or hPanel PHP Configuration). |
| OTP email not sent | Gmail App Password, `MAIL_*` values, and that outbound SMTP is allowed for your plan. |

## Local development
Under XAMPP at `http://localhost/hr1/` everything still works: URLs adapt automatically
(`APP_BASE_PATH` stays empty). Use `APP_DEBUG=true` and your local DB credentials in a local `.env`.
