<?php

$job = isset($job) && is_array($job) ? $job : [];

$error = $_SESSION['application_error'] ?? null;

unset($_SESSION['application_error']);

$applicationToken =
    htmlspecialchars(
        $job['application_token'],
        ENT_QUOTES,
        'UTF-8'
    );

$jobTitle = htmlspecialchars($job['title'] ?? 'Open Position', ENT_QUOTES, 'UTF-8');
$department = htmlspecialchars($job['department_name'] ?? 'Ramyum', ENT_QUOTES, 'UTF-8');
$employmentType = htmlspecialchars($job['employment_type'] ?? 'Employment', ENT_QUOTES, 'UTF-8');
$vacancies = (int)($job['vacancies'] ?? 1);
$deadline = !empty($job['application_deadline'])
    ? date('F j, Y', strtotime($job['application_deadline']))
    : 'Open until filled';
$description = trim((string)($job['description'] ?? ''));
$requirements = trim((string)($job['requirements'] ?? ''));
?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Apply - <?= $jobTitle ?></title>

    <style>

        :root {
            --maroon: #741b1b;
            --maroon-dark: #5d1515;
            --maroon-tint: #fbeeee;
            --bg: #f6f4f1;
            --ink: #241010;
            --muted: #6b6b6b;
            --border: #e5dfda;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Helvetica,
                Arial,
                sans-serif;
            background: var(--bg);
            color: var(--ink);
        }

        .application-page {
            max-width: 780px;
            margin: 48px auto;
            padding: 20px;
        }

        .job-card,
        .application-card {
            background: #ffffff;
            border-radius: 16px;
            padding: 36px;
            margin-bottom: 24px;
            border: 1px solid var(--border);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.06);
        }

        .brand {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            color: var(--maroon);
            margin-bottom: 14px;
        }

        h1 {
            margin: 0 0 10px;
            font-size: 28px;
            letter-spacing: -0.01em;
        }

        h2 {
            margin: 0 0 6px;
            font-size: 22px;
            letter-spacing: -0.01em;
        }

        .subtitle {
            margin: 0 0 28px;
            color: var(--muted);
            font-size: 15px;
            line-height: 1.6;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin: 18px 0;
        }

        .job-meta span {
            padding: 6px 14px;
            border-radius: 20px;
            background: var(--maroon-tint);
            color: var(--maroon-dark);
            font-size: 13px;
            font-weight: 600;
        }

        .job-section {
            margin-top: 22px;
        }

        .job-section h3 {
            margin: 0 0 8px;
            font-size: 15px;
        }

        .job-section p {
            margin: 0;
            color: var(--muted);
            line-height: 1.65;
            font-size: 14.5px;
        }

        /*
         * Application error / duplicate warning
         */
        .error {
            display: flex;
            align-items: flex-start;
            gap: 14px;

            background: #fff8e7;
            color: #5d1515;

            border: 1px solid #c89b3c;
            border-left: 4px solid var(--maroon);

            padding: 18px 20px;
            border-radius: 12px;

            margin: 0 0 28px;
        }

        .error-icon {
            flex-shrink: 0;

            width: 36px;
            height: 36px;

            display: flex;
            align-items: center;
            justify-content: center;

            background: var(--maroon);
            color: #ffffff;

            border-radius: 50%;

            font-size: 17px;
            font-weight: 700;
        }

        .error-content {
            flex: 1;
        }

        .error-title {
            margin: 0 0 6px;

            font-size: 16px;
            font-weight: 700;

            color: var(--maroon);
        }

        .error-message {
            margin: 0;

            line-height: 1.5;
            font-size: 14px;
        }

        .error-help {
            margin: 7px 0 0;

            color: #704f4f;
            font-size: 13px;
            line-height: 1.5;
        }

        fieldset {
            border: none;
            padding: 0;
            margin: 0 0 28px;
        }

        fieldset legend {
            font-size: 14px;
            font-weight: 700;
            color: var(--ink);
            padding: 0 0 14px;
            width: 100%;
            border-bottom: 1px solid var(--border);
            margin-bottom: 18px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }

        .form-group.full {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            font-size: 13.5px;
            color: #3a2f2f;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 12px 14px;

            border: 1px solid #d5ceca;
            border-radius: 8px;

            font-size: 14px;
            font-family: inherit;

            background: #fdfcfb;
            color: var(--ink);

            transition: border-color 0.15s ease, box-shadow 0.15s ease, background 0.15s ease;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        textarea:focus {
            outline: none;
            border-color: var(--maroon);
            background: #ffffff;
            box-shadow: 0 0 0 3px var(--maroon-tint);
        }

        .required {
            color: var(--maroon);
        }

        .file-input-wrap {
            position: relative;
        }

        input[type="file"] {
            width: 100%;
            padding: 10px 14px;
            border: 1px dashed #c9beb8;
            border-radius: 8px;
            background: #fdfcfb;
            font-size: 13.5px;
            font-family: inherit;
            color: var(--muted);
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease;
        }

        input[type="file"]:hover {
            border-color: var(--maroon);
            background: var(--maroon-tint);
        }

        input[type="file"]::file-selector-button {
            padding: 7px 14px;
            margin-right: 12px;
            border: none;
            border-radius: 6px;
            background: var(--maroon);
            color: #ffffff;
            font-size: 13px;
            font-weight: 600;
            font-family: inherit;
            cursor: pointer;
            transition: background 0.15s ease;
        }

        input[type="file"]::file-selector-button:hover {
            background: var(--maroon-dark);
        }

        .submit-button {
            margin-top: 4px;

            width: 100%;
            padding: 15px;

            border: none;
            border-radius: 9px;

            background: var(--maroon);
            color: #ffffff;

            font-size: 15px;
            font-weight: 700;

            cursor: pointer;

            transition:
                background 0.2s ease,
                transform 0.1s ease;
        }

        .submit-button:hover {
            background: var(--maroon-dark);
        }

        .submit-button:active {
            transform: translateY(1px);
        }

        .form-footnote {
            margin: 14px 0 0;
            text-align: center;
            font-size: 12.5px;
            color: #a3a3a3;
        }

        .file-note {
            font-size: 12px;
            color: #928685;
        }

        .application-page {
            max-width: 1160px;
            margin: 0 auto;
            padding: 28px 28px 64px;
        }

        .site-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            padding: 4px 0 30px;
        }

        .brand-lockup { display: flex; align-items: center; gap: 14px; }

        .brand-logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
            filter: drop-shadow(0 5px 8px rgba(94, 16, 23, 0.15));
        }

        .brand-name {
            margin: 0;
            color: var(--maroon-dark);
            font-size: 22px;
            font-weight: 700;
            letter-spacing: .04em;
        }

        .brand-kicker,
        .header-note {
            font-family: Arial, sans-serif;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: .14em;
            text-transform: uppercase;
        }

        .brand-kicker { margin: 3px 0 0; color: var(--muted); }
        .header-note { color: var(--maroon); }

        .content-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr) minmax(420px, 1.05fr);
            gap: 24px;
            align-items: start;
        }

        .job-card {
            position: sticky;
            top: 24px;
            overflow: hidden;
            border-top: 7px solid var(--maroon);
        }

        .job-card h1 {
            font-size: clamp(32px, 4vw, 48px);
            line-height: 1.04;
            letter-spacing: -0.025em;
        }

        .job-card .brand { margin-bottom: 18px; }

        .job-meta span {
            border: 1px solid #f2dfaa;
            border-radius: 2px;
            background: #fff7df;
        }

        .job-section {
            padding-top: 20px;
            border-top: 1px solid var(--border);
        }

        .job-section p {
            white-space: pre-line;
        }

        .application-card h2 { margin-bottom: 8px; }

        @media (min-width: 701px) {
            .job-card,
            .application-card {
                margin-bottom: 0;
                box-shadow: 0 18px 50px rgba(94, 16, 23, 0.08);
            }
        }

        @media (max-width: 700px) {

            .application-page {
                margin: 0 auto;
                padding: 16px 14px 40px;
            }

            .site-header { padding-bottom: 20px; }
            .brand-logo { width: 56px; height: 56px; }
            .brand-name { font-size: 18px; }
            .header-note { display: none; }
            .content-grid { grid-template-columns: 1fr; }
            .job-card { position: static; }

            .job-card,
            .application-card {
                padding: 22px;
                border-radius: 14px;
            }

            h1 {
                font-size: 24px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-group.full {
                grid-column: auto;
            }

            .error {
                padding: 16px;
            }

            .error-icon {
                width: 32px;
                height: 32px;
                font-size: 15px;
            }

            .error-title {
                font-size: 15px;
            }

        }

    </style>

</head>

<body>

<div class="application-page">

    <header class="site-header">
        <div class="brand-lockup">
            <img
                class="brand-logo"
                src="/hr1/public/assets/images/logo.png"
                alt="Ramyum Korean and Japanese Store logo"
            >
            <div>
                <p class="brand-name">RAM-YUM</p>
                <p class="brand-kicker">Korean &amp; Japanese Store</p>
            </div>
        </div>
        <span class="header-note">Join our team</span>
    </header>

    <div class="content-grid">

        <section class="job-card" aria-labelledby="job-title">
            <p class="brand">Now hiring</p>
            <h1 id="job-title"><?= $jobTitle ?></h1>
            <p class="subtitle">Bring your energy, care, and love of great food to the Ramyum team.</p>

            <div class="job-meta" aria-label="Position details">
                <span><?= $department ?></span>
                <span><?= $employmentType ?></span>
                <span><?= $vacancies ?> <?= $vacancies === 1 ? 'opening' : 'openings' ?></span>
            </div>

            <div class="job-section">
                <h3>About the role</h3>
                <p><?= $description !== '' ? nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')) : 'We are looking for a motivated team member to help us create a warm, welcoming experience for every customer.' ?></p>
            </div>

            <?php if ($requirements !== ''): ?>
                <div class="job-section">
                    <h3>What you bring</h3>
                    <p><?= nl2br(htmlspecialchars($requirements, ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            <?php endif; ?>

            <div class="job-section">
                <h3>Application deadline</h3>
                <p><?= htmlspecialchars($deadline, ENT_QUOTES, 'UTF-8') ?></p>
            </div>
        </section>

    <div class="application-card">

        <h2>
            Apply for this Position
        </h2>

        <p class="subtitle">
            Please complete the form below and upload
            your resume.
        </p>


        <?php if ($error !== null): ?>

            <div
                class="error"
                role="alert"
                aria-live="polite"
            >

                <div class="error-icon">
                    !
                </div>

                <div class="error-content">

                    <h3 class="error-title">
                        Application Already Submitted
                    </h3>

                    <p class="error-message">
                        <?= htmlspecialchars(
                            $error,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <p class="error-help">
                        You cannot submit another application
                        for the same position using the same
                        email address.
                    </p>

                </div>

            </div>

        <?php endif; ?>


        <form
            method="POST"
            action="?page=submit-application"
            enctype="multipart/form-data"
        >

            <input
                type="hidden"
                name="application_token"
                value="<?= $applicationToken ?>"
            >

            <fieldset>

                <legend>Personal Information</legend>

                <div class="form-grid">

                    <div class="form-group">

                        <label>
                            First Name
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="first_name"
                            placeholder="Juan"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Middle Name
                        </label>

                        <input
                            type="text"
                            name="middle_name"
                            placeholder="Optional"
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Last Name
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="last_name"
                            placeholder="Dela Cruz"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Email
                            <span class="required">*</span>
                        </label>

                        <input
                            type="email"
                            name="email"
                            placeholder="you@example.com"
                            required
                        >

                    </div>


                    <div class="form-group">

                        <label>
                            Phone Number
                            <span class="required">*</span>
                        </label>

                        <input
                            type="text"
                            name="phone"
                            placeholder="09XX XXX XXXX"
                            required
                        >

                    </div>


                    <div class="form-group full">

                        <label>
                            Address
                        </label>

                        <textarea
                            name="address"
                            placeholder="Street, Barangay, City, Province"
                        ></textarea>

                    </div>

                </div>

            </fieldset>


            <fieldset>

                <legend>Documents</legend>

                <div class="form-grid">

                    <div class="form-group full">

                        <label>
                            Academic Certificate / Diploma
                            <?php if (!empty($job['academic_document_required'])): ?><span class="required">*</span><?php endif; ?>
                        </label>

                        <div class="file-input-wrap">

                            <input
                                type="file"
                                name="academic_document"
                                accept=".pdf,.jpg,.jpeg,.png"
                                <?= !empty($job['academic_document_required']) ? 'required' : '' ?>
                            >

                        </div>

                        <span class="file-note">
                            PDF, JPG, or PNG. <?= !empty($job['academic_document_required']) ? 'Required for this position.' : 'Optional.' ?>
                        </span>

                    </div>


                    <div class="form-group full">

                        <label>
                            Resume
                            <span class="required">*</span>
                        </label>

                        <div class="file-input-wrap">

                            <input
                                type="file"
                                name="resume"
                                accept=".pdf,.doc,.docx"
                                required
                            >

                        </div>

                        <span class="file-note">
                            PDF, DOC, or DOCX. Maximum 5 MB.
                        </span>

                    </div>

                </div>

            </fieldset>


            <button
                type="submit"
                class="submit-button"
            >
                Submit Application
            </button>

            <p class="form-footnote">
                By submitting, you agree to let our recruitment
                team review the information provided above.
            </p>

        </form>

    </div>

    </div>

</div>

</body>

</html>