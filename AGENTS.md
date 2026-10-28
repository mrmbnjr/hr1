# Project guidance

HR1 is a PHP 8.1+ / MySQL 8.0 HR and recruitment app. `index.php` is the front controller; application code lives in `src/`, views in `resources/views/`, and database schema/seeds in `database/`.

## Run locally

- Use XAMPP with Apache and MySQL, and open `http://localhost/hr1/`.
- Configure the project-root `.env` with database credentials and any required mail or Gemini settings. Never commit secrets.
- Create an empty MySQL database and import `database/schema.sql`; optionally import `database/seeds/seed.sql` for demo data.
- Install PHP dependencies with `composer install` when needed.

## Check changes

- There is no PHPUnit suite. CI runs `composer validate --strict`, `composer install`, `composer check-platform-reqs`, PHP syntax linting outside `vendor/`, and a Docker image build.
- For PHP changes, run `php -l` on changed PHP files. For dependency changes, run the Composer validation and platform checks above.
- Keep changes consistent with the existing vanilla PHP, HTML, CSS, and JavaScript patterns. Use prepared PDO statements, validate inputs, and escape rendered output.
- Read `README.MD` and `DEPLOYMENT.md` for detailed application and operational guidance.
