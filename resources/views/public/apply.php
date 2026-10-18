<?php

$job = isset($job) && is_array($job) ? $job : [];

$error = $_SESSION['application_error'] ?? null;

$isDuplicateError = $error !== null
    && str_contains(strtolower($error), 'already submitted');

unset($_SESSION['application_error']);

$applicationToken =
    htmlspecialchars(
        $job['application_token'] ?? '',
        ENT_QUOTES,
        'UTF-8'
    );

$jobTitle = htmlspecialchars($job['title'] ?? 'Open Position', ENT_QUOTES, 'UTF-8');
$department = htmlspecialchars($job['department_name'] ?? 'Ramyum', ENT_QUOTES, 'UTF-8');
$employmentType = htmlspecialchars($job['employment_type'] ?? 'Employment', ENT_QUOTES, 'UTF-8');
$expectedSalary = htmlspecialchars(jobSalary($job), ENT_QUOTES, 'UTF-8');
$vacancies = (int)($job['vacancies'] ?? 1);
$deadline = !empty($job['application_deadline'])
    ? date('F j, Y', strtotime($job['application_deadline']))
    : 'Open until filled';
$description = trim((string)($job['description'] ?? ''));
$requirements = trim((string)($job['requirements'] ?? ''));
$academicRequired = !empty($job['academic_document_required']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply - <?= $jobTitle ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@500;600;700;800&family=Nunito:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        /* ============ Royyan theme tokens (maroon / gold / cream) ============ */
        :root {
            color-scheme: light;
            --maroon: #800020;
            --maroon-dark: #500014;
            --maroon-soft: #fff0f3;
            --gold: #c9a227;
            --gold-light: #f6e7b4;
            --cream: #fdf8ee;

            --ink: #2b1a1d;
            --ink-soft: #6b5a5e;
            --ink-faint: #a3959a;

            --page-bg: #fbf6ec;
            --card-bg: #ffffff;
            --field-bg: #fffdf8;
            --border: #eadfcf;
            --border-strong: #d9c9ae;
            --shadow: 0 6px 24px -8px rgba(80, 0, 20, 0.14);
            --shadow-lg: 0 18px 40px -16px rgba(80, 0, 20, 0.28);
            --ring: rgba(128, 0, 32, 0.16);

            --danger-bg: #fef2f2;
            --danger-border: #fecaca;
            --danger: #dc2626;
            --danger-ink: #7f1d1d;

            --radius: 16px;
            --radius-md: 12px;
            --radius-sm: 8px;

            --font-display: 'Baloo 2', 'Nunito', sans-serif;
            --font-body: 'Nunito', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        :root[data-theme="dark"] {
            color-scheme: dark;
            --maroon-soft: #3a1219;
            --gold-light: #4a3d14;
            --cream: #241a1c;
            --ink: #f4ebe6;
            --ink-soft: #bfaeb2;
            --ink-faint: #8a7a7e;
            --page-bg: #170e10;
            --card-bg: #211618;
            --field-bg: #2a1c1f;
            --border: #3a2a2d;
            --border-strong: #4d3a3e;
            --shadow: 0 6px 24px -8px rgba(0, 0, 0, 0.5);
            --shadow-lg: 0 18px 40px -16px rgba(0, 0, 0, 0.6);
            --ring: rgba(201, 162, 39, 0.28);
            --danger-bg: #3a1618;
            --danger-border: #6b2326;
            --danger-ink: #fecaca;
        }
        [data-theme="dark"] .brand-name,
        [data-theme="dark"] .card-badge,
        [data-theme="dark"] .job-section h3 i,
        [data-theme="dark"] .required { color: var(--gold) !important; }
        [data-theme="dark"] .submit-button { background: linear-gradient(135deg, #a3112f, var(--maroon)) !important; }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font-body);
            background:
                radial-gradient(900px 400px at 100% -10%, var(--gold-light), transparent 60%),
                radial-gradient(700px 380px at -10% 0%, var(--maroon-soft), transparent 60%),
                var(--page-bg);
            background-attachment: fixed;
            color: var(--ink);
            line-height: 1.55;
            -webkit-font-smoothing: antialiased;
        }

        .application-page {
            max-width: 1160px;
            margin: 0 auto;
            padding: 28px 20px 64px;
        }

        /* ============ Header ============ */
        .site-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            margin-bottom: 28px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-bottom: 3px solid var(--gold);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
        }

        .brand-lockup { display: flex; align-items: center; gap: 14px; }
        .header-actions { display: flex; align-items: center; gap: 12px; }

        .theme-toggle {
            width: 42px;
            height: 42px;
            flex: none;
            display: grid;
            place-items: center;
            border: 1px solid var(--border);
            border-radius: 50%;
            background: var(--card-bg);
            color: var(--maroon);
            box-shadow: var(--shadow);
            cursor: pointer;
            transition: transform .18s, background .18s, color .18s;
        }

        .theme-toggle:hover { transform: translateY(-2px) rotate(8deg); }
        .theme-toggle:active { transform: translateY(0) scale(.94); }
        .theme-toggle:focus-visible { outline: 3px solid var(--gold); outline-offset: 3px; }
        .theme-toggle .icon { width: 20px; height: 20px; }
        .theme-toggle .icon-moon { display: none; }
        [data-theme="dark"] .theme-toggle { color: var(--gold); }
        [data-theme="dark"] .theme-toggle .icon-sun { display: none; }
        [data-theme="dark"] .theme-toggle .icon-moon { display: block; }

        .brand-logo {
            width: 48px;
            height: 48px;
            object-fit: contain;
            border-radius: var(--radius-md);
        }

        .brand-name {
            font-family: var(--font-display);
            font-size: 22px;
            font-weight: 800;
            letter-spacing: 0.01em;
            color: var(--maroon);
            line-height: 1.1;
        }

        .brand-kicker {
            font-size: 11px;
            font-weight: 700;
            color: var(--ink-soft);
            letter-spacing: 0.1em;
            text-transform: uppercase;
        }

        .header-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--gold-light);
            color: var(--maroon-dark);
            font-size: 12px;
            font-weight: 800;
            padding: 7px 14px;
            border-radius: 999px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
        }
        [data-theme="dark"] .header-badge { color: var(--gold); }

        /* ============ Layout ============ */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 24px;
        }

        @media (min-width: 900px) {
            .content-grid {
                grid-template-columns: 0.9fr 1.1fr;
                gap: 32px;
                align-items: start;
            }
        }

        .job-card, .application-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            overflow: hidden;
        }

        @media (min-width: 900px) {
            .job-card { position: sticky; top: 24px; }
        }

        /* ============ Job card ============ */
        .job-hero {
            padding: 30px 30px 26px;
            background: linear-gradient(135deg, var(--maroon-dark), var(--maroon));
            color: #fff;
            position: relative;
        }

        .job-hero::after {
            content: '';
            position: absolute;
            right: -40px; top: -40px;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201,162,39,.35), transparent 70%);
            pointer-events: none;
        }

        .card-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            font-size: 11px;
            font-weight: 800;
            color: var(--gold-light);
            text-transform: uppercase;
            letter-spacing: 0.12em;
            margin-bottom: 10px;
        }

        .job-hero .card-title { color: #fff; margin-bottom: 6px; }

        .job-hero .card-subtitle {
            color: rgba(255, 255, 255, 0.82);
            margin-bottom: 0;
        }

        .job-body { padding: 24px 30px 30px; }

        .card-title {
            font-family: var(--font-display);
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.01em;
            line-height: 1.2;
            color: var(--ink);
            margin-bottom: 6px;
        }

        .card-subtitle {
            font-size: 14px;
            color: var(--ink-soft);
            margin-bottom: 22px;
        }

        .job-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 22px;
            padding-bottom: 20px;
            border-bottom: 1px dashed var(--border-strong);
        }

        .job-meta-tag {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--cream);
            color: var(--ink);
            border: 1px solid var(--border);
            padding: 6px 12px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
        }
        .job-meta-tag i { color: var(--maroon); font-size: 12px; }
        [data-theme="dark"] .job-meta-tag i { color: var(--gold); }

        .job-section { margin-bottom: 22px; }
        .job-section:last-child { margin-bottom: 0; }

        .job-section h3 {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--maroon);
            margin-bottom: 8px;
        }

        .job-section p {
            font-size: 14.5px;
            color: var(--ink);
            line-height: 1.7;
        }

        .job-section p.job-salary-value {
            font-size: 14px;
            font-weight: 700;
        }

        .deadline-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--gold-light);
            border-radius: var(--radius-sm);
            padding: 8px 14px;
            font-weight: 700;
            font-size: 14px;
        }

        /* ============ Application card ============ */
        .application-card { padding: 30px; }

        .application-card .card-title { font-size: 26px; }

        .error-alert {
            display: flex;
            align-items: flex-start;
            gap: 14px;
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            border-left: 4px solid var(--danger);
            padding: 16px 18px;
            border-radius: var(--radius-md);
            margin-bottom: 24px;
        }

        .error-icon {
            width: 26px; height: 26px;
            border-radius: 50%;
            background: var(--danger);
            color: #fff;
            display: flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 13px;
            flex-shrink: 0;
        }

        .error-title { font-size: 15px; font-weight: 800; color: var(--danger-ink); margin-bottom: 2px; }
        .error-message { font-size: 13.5px; color: var(--danger-ink); }
        .error-help { font-size: 12.5px; color: var(--danger-ink); margin-top: 6px; opacity: .85; }

        /* ============ Form ============ */
        fieldset { border: none; margin: 0 0 28px; padding: 0; }

        legend {
            display: flex;
            align-items: center;
            gap: 10px;
            width: 100%;
            font-family: var(--font-display);
            font-size: 17px;
            font-weight: 700;
            color: var(--ink);
            padding-bottom: 10px;
            margin-bottom: 18px;
            border-bottom: 2px solid var(--gold-light);
        }

        .legend-icon {
            width: 30px; height: 30px;
            display: inline-flex; align-items: center; justify-content: center;
            border-radius: var(--radius-sm);
            background: var(--maroon-soft);
            color: var(--maroon);
            font-size: 13px;
        }
        [data-theme="dark"] .legend-icon { color: var(--gold); }

        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .form-group { display: flex; flex-direction: column; gap: 6px; }
        .form-group.full { grid-column: 1 / -1; }

        label { font-size: 13px; font-weight: 700; color: var(--ink); }
        .required { color: var(--maroon); }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 11px 14px;
            background: var(--field-bg);
            border: 1.5px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 14px;
            font-family: inherit;
            color: var(--ink);
            transition: border-color .2s, box-shadow .2s, background .2s;
        }

        input::placeholder, textarea::placeholder { color: var(--ink-faint); }

        textarea { min-height: 90px; resize: vertical; }

        input[type="text"]:hover,
        input[type="email"]:hover,
        textarea:hover { border-color: var(--border-strong); }

        input[type="text"]:focus,
        input[type="email"]:focus,
        textarea:focus {
            outline: none;
            background: var(--card-bg);
            border-color: var(--maroon);
            box-shadow: 0 0 0 4px var(--ring);
        }

        /* File upload */
        .file-upload-box {
            background: var(--field-bg);
            border: 2px dashed var(--border-strong);
            border-radius: var(--radius-md);
            padding: 16px;
            transition: border-color .2s, background .2s;
        }

        .file-upload-box:hover,
        .file-upload-box:focus-within {
            border-color: var(--maroon);
            background: var(--maroon-soft);
        }

        .upload-hint {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 12px;
            font-size: 13px;
            color: var(--ink-soft);
        }

        .upload-hint .upload-icon {
            width: 38px; height: 38px;
            border-radius: 50%;
            display: inline-flex; align-items: center; justify-content: center;
            background: var(--gold-light);
            color: var(--maroon-dark);
            font-size: 15px;
            flex-shrink: 0;
        }
        [data-theme="dark"] .upload-hint .upload-icon { color: var(--gold); }

        input[type="file"] {
            width: 100%;
            font-family: inherit;
            font-size: 13px;
            color: var(--ink-soft);
            cursor: pointer;
        }

        input[type="file"]::file-selector-button {
            padding: 7px 14px;
            margin-right: 10px;
            border: none;
            border-radius: var(--radius-sm);
            background: var(--maroon);
            color: #fff;
            font-family: inherit;
            font-weight: 700;
            font-size: 12px;
            cursor: pointer;
            transition: background .2s;
        }

        input[type="file"]::file-selector-button:hover { background: var(--maroon-dark); }

        .file-note {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            color: var(--ink-soft);
        }
        .file-note i { color: var(--gold); }

        .academic-file-list { display: flex; flex-direction: column; gap: 8px; }
        .academic-file-list:not(:empty) { margin-top: 4px; }

        .academic-file-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-left: 3px solid var(--gold);
            padding: 8px 12px;
            border-radius: var(--radius-md);
        }

        .academic-file-info { display: flex; align-items: center; gap: 10px; min-width: 0; }

        .academic-file-icon {
            width: 30px; height: 30px;
            background: var(--maroon-soft);
            color: var(--maroon);
            font-weight: 800;
            font-size: 10px;
            border-radius: var(--radius-sm);
            display: flex; align-items: center; justify-content: center;
            flex-shrink: 0;
        }
        [data-theme="dark"] .academic-file-icon { color: var(--gold); }

        .academic-file-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--ink);
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .academic-file-size { font-size: 11px; color: var(--ink-faint); margin-left: 4px; font-weight: 500; }

        .academic-file-remove {
            background: none;
            border: none;
            color: var(--ink-faint);
            font-size: 18px;
            cursor: pointer;
            padding: 2px 8px;
            border-radius: 6px;
            transition: all .15s;
        }

        .academic-file-remove:hover { background: var(--danger-bg); color: var(--danger); }

        /* ============ Data privacy consent (inline with footnote text) ============ */
        .consent-block { margin-top: 16px; margin-bottom: 18px; }

        .consent-option {
            display: flex;
            align-items: center;      /* circle lines up with the text block */
            gap: 12px;
            cursor: pointer;
            text-align: left;
        }

        .consent-option:focus-within .consent-box {
            box-shadow: 0 0 0 4px var(--ring);
            border-color: var(--maroon);
        }

        /* The checkbox itself is hidden; .consent-box is the visible circle. */
        .consent-input {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        .consent-box {
            position: relative;
            flex: none;
            width: 22px;
            height: 22px;
            border: 2px solid var(--border-strong);
            border-radius: 50%;
            background: var(--card-bg);
            transition: border-color .2s, background .2s, box-shadow .2s;
        }

        .consent-option:hover .consent-box { border-color: var(--maroon); }

        .consent-input:checked + .consent-box {
            border-color: var(--maroon);
            background: var(--maroon);
        }

        .consent-input:checked + .consent-box::after {
            content: '';
            position: absolute;
            left: 6px;
            top: 2.5px;
            width: 5px;
            height: 10px;
            border: solid #fff;
            border-width: 0 2.5px 2.5px 0;
            transform: rotate(45deg);
        }

        [data-theme="dark"] .consent-input:checked + .consent-box {
            background: var(--gold);
            border-color: var(--gold);
        }

        [data-theme="dark"] .consent-input:checked + .consent-box::after {
            border-color: var(--maroon-dark);
        }

        .consent-text {
            font-size: 12.5px;
            line-height: 1.6;
            color: var(--ink-soft);
            font-weight: 500;
        }

        .consent-text strong { color: var(--ink); font-weight: 800; }
        .consent-text i { color: var(--gold); margin-right: 4px; }

        .consent-link {
            color: var(--maroon);
            font-weight: 800;
            text-decoration: underline;
            text-underline-offset: 2px;
        }

        .consent-link:hover { color: var(--maroon-dark); }
        [data-theme="dark"] .consent-link { color: var(--gold); }

        .consent-error {
            display: none;
            align-items: center;
            gap: 8px;
            margin-top: 12px;
            padding: 10px 14px;
            background: var(--danger-bg);
            border: 1px solid var(--danger-border);
            border-radius: var(--radius-md);
            font-size: 13px;
            font-weight: 700;
            color: var(--danger-ink);
        }

        .consent-error.is-visible { display: flex; }
        .consent-error i { color: var(--danger); }

        /* Submit */
        .submit-button {
            width: 100%;
            padding: 15px 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--maroon), var(--maroon-dark));
            color: #fff;
            border: none;
            border-bottom: 3px solid var(--gold);
            border-radius: var(--radius-md);
            font-family: var(--font-display);
            font-size: 17px;
            font-weight: 700;
            letter-spacing: .02em;
            cursor: pointer;
            transition: transform .2s, box-shadow .2s;
            box-shadow: 0 8px 18px -6px rgba(128, 0, 32, .45);
        }

        .submit-button:hover { transform: translateY(-1px); box-shadow: var(--shadow-lg); }
        .submit-button:active { transform: translateY(0); }
        .submit-button:focus-visible { outline: none; box-shadow: 0 0 0 4px var(--ring); }
        .submit-button:disabled { opacity: .75; cursor: not-allowed; transform: none; }

        /* ============ Responsive ============ */
        @media (max-width: 640px) {
            .application-page { padding: 14px 12px 40px; }
            .site-header { padding: 12px 14px; margin-bottom: 18px; }
            .header-badge { display: none; }
            .job-hero { padding: 22px 20px; }
            .job-body, .application-card { padding: 20px; }
            .form-grid { grid-template-columns: 1fr; }
            .card-title { font-size: 23px; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>
</head>

<body>

<div class="application-page">

    <header class="site-header">
        <div class="brand-lockup">
            <img
                class="brand-logo"
                src="<?= asset('images/logo.png') ?>"
                alt="Ramyum Logo"
            >
            <div>
                <div class="brand-name">RAM-YUM</div>
                <div class="brand-kicker">Korean &amp; Japanese Store</div>
            </div>
        </div>
        <div class="header-actions">
            <span class="header-badge"><i class="fa-solid fa-briefcase"></i> Careers</span>
            <button
                type="button"
                class="theme-toggle"
                id="themeToggle"
                aria-label="Toggle light and dark theme"
                aria-pressed="false"
            >
                <svg class="icon icon-sun" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="12" cy="12" r="4.2"></circle>
                    <path d="M12 2.5v2.4M12 19.1v2.4M4.6 4.6l1.7 1.7M17.7 17.7l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.6 19.4l1.7-1.7M17.7 6.3l1.7-1.7"></path>
                </svg>
                <svg class="icon icon-moon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M20.8 14.3A8.5 8.5 0 1 1 9.7 3.2a6.8 6.8 0 0 0 11.1 11.1z"></path>
                </svg>
            </button>
        </div>
    </header>

    <div class="content-grid">

        <!-- Job Information -->
        <section class="job-card" aria-labelledby="job-title">
            <div class="job-hero">
                <div class="card-badge"><i class="fa-solid fa-bowl-food"></i> Now Hiring</div>
                <h1 id="job-title" class="card-title"><?= $jobTitle ?></h1>
                <p class="card-subtitle">Bring your energy, care, and love of great food to the Ramyum team.</p>
            </div>

            <div class="job-body">
                <div class="job-meta" aria-label="Position details">
                    <span class="job-meta-tag"><i class="fa-solid fa-building"></i> <?= $department ?></span>
                    <span class="job-meta-tag"><i class="fa-solid fa-clock"></i> <?= $employmentType ?></span>
                    <span class="job-meta-tag">
                        <i class="fa-solid fa-user-group"></i>
                        <?= $vacancies ?> <?= $vacancies === 1 ? 'opening' : 'openings' ?>
                    </span>
                </div>

                <div class="job-section">
                    <h3><i class="fa-solid fa-circle-info"></i> About the Role</h3>
                    <p><?= $description !== '' ? nl2br(htmlspecialchars($description, ENT_QUOTES, 'UTF-8')) : 'We are looking for a motivated team member to help us create a warm, welcoming experience for every customer.' ?></p>
                </div>

                <?php if ($requirements !== ''): ?>
                    <div class="job-section">
                        <h3><i class="fa-solid fa-list-check"></i> What You Bring</h3>
                        <p><?= nl2br(htmlspecialchars($requirements, ENT_QUOTES, 'UTF-8')) ?></p>
                    </div>
                <?php endif; ?>

                <?php if ($expectedSalary !== ''): ?>
                    <div class="job-section">
                        <h3><i class="fa-solid fa-peso-sign"></i> Expected Salary</h3>
                        <p class="job-salary-value"><?= $expectedSalary ?></p>
                    </div>
                <?php endif; ?>

                <div class="job-section">
                    <h3><i class="fa-solid fa-calendar-days"></i> Application Deadline</h3>
                    <span class="deadline-pill"><?= htmlspecialchars($deadline, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            </div>
        </section>

        <!-- Application Form -->
        <div class="application-card">

            <h2 class="card-title">Apply for this Position</h2>
            <p class="card-subtitle">Please complete the form below and upload your required documents.</p>

            <?php if ($error !== null): ?>
                <div class="error-alert" role="alert" aria-live="polite">
                    <div class="error-icon">!</div>
                    <div>
                        <h3 class="error-title">
                            <?= $isDuplicateError
                                ? 'Application Already Submitted'
                                : 'Application Could Not Be Submitted' ?>
                        </h3>
                        <p class="error-message">
                            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <?php if ($isDuplicateError): ?>
                            <p class="error-help">
                                You cannot submit another application for the same position using the same email address.
                            </p>
                        <?php endif; ?>
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
                    <legend><span class="legend-icon"><i class="fa-solid fa-user"></i></span> Personal Information</legend>

                    <div class="form-grid">

                        <div class="form-group">
                            <label for="first_name">First Name <span class="required">*</span></label>
                            <input type="text" id="first_name" name="first_name" placeholder="Juan" required>
                        </div>

                        <div class="form-group">
                            <label for="middle_name">Middle Name</label>
                            <input type="text" id="middle_name" name="middle_name" placeholder="Optional">
                        </div>

                        <div class="form-group">
                            <label for="last_name">Last Name <span class="required">*</span></label>
                            <input type="text" id="last_name" name="last_name" placeholder="Dela Cruz" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" placeholder="you@example.com" required>
                        </div>

                        <div class="form-group full">
                            <label for="phone">Phone Number <span class="required">*</span></label>
                            <input type="text" id="phone" name="phone" placeholder="09XX XXX XXXX" required>
                        </div>

                        <div class="form-group full">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" placeholder="Street, Barangay, City, Province"></textarea>
                        </div>

                    </div>
                </fieldset>

                <fieldset>
                    <legend><span class="legend-icon"><i class="fa-solid fa-folder-open"></i></span> Documents</legend>

                    <div class="form-grid">

                        <!-- Certificates / Diplomas -->
                        <div class="form-group full">
                            <label for="academicDocuments">
                                Certificates / Diplomas
                                <?php if ($academicRequired): ?>
                                    <span class="required">*</span>
                                <?php endif; ?>
                            </label>

                            <div class="file-upload-box">
                                <div class="upload-hint">
                                    <span class="upload-icon"><i class="fa-solid fa-graduation-cap"></i></span>
                                    <span>Upload your certificates or diplomas</span>
                                </div>
                                <input
                                    type="file"
                                    id="academicDocuments"
                                    name="academic_documents[]"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    multiple
                                    <?= $academicRequired ? 'required' : '' ?>
                                >
                            </div>

                            <div id="academicFileList" class="academic-file-list" aria-live="polite"></div>

                            <span class="file-note">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>
                                    Accepted formats: PDF, JPG, PNG.
                                    <?= $academicRequired
                                        ? 'At least one document is required.'
                                        : 'Optional. You may select multiple files.' ?>
                                </span>
                            </span>
                        </div>

                        <!-- Resume Upload -->
                        <div class="form-group full">
                            <label for="resume">Resume <span class="required">*</span></label>

                            <div class="file-upload-box">
                                <div class="upload-hint">
                                    <span class="upload-icon"><i class="fa-solid fa-file-pdf"></i></span>
                                    <span>Upload your latest resume</span>
                                </div>
                                <input
                                    type="file"
                                    id="resume"
                                    name="resume"
                                    accept=".pdf"
                                    required
                                >
                            </div>

                            <span class="file-note">
                                <i class="fa-solid fa-circle-check"></i>
                                <span>PDF format only. Maximum file size 5 MB.</span>
                            </span>
                        </div>

                    </div>
                </fieldset>

                <!-- Data privacy consent: circle checkbox sits inline with the footnote text -->
                <div class="consent-block">
                    <label class="consent-option" for="data_privacy_consent">
                        <input
                            type="checkbox"
                            id="data_privacy_consent"
                            name="data_privacy_consent"
                            value="1"
                            class="consent-input"
                            required
                        >
                        <span class="consent-box" aria-hidden="true"></span>
                        <span class="consent-text">
                            <i class="fa-solid fa-lock"></i>
                            <strong>I agree.</strong> By submitting this application, I allow the hiring team to review my details in accordance with the
                            <a
                                class="consent-link"
                                href="<?= url('?page=privacy-notice') ?>"
                                target="_blank"
                                rel="noopener noreferrer"
                            >recruitment policy</a>.
                        </span>
                    </label>

                    <div class="consent-error" id="consentError" role="alert">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Please read and agree to the recruitment policy before submitting your application.</span>
                    </div>
                </div>

                <button type="submit" class="submit-button">
                    <i class="fa-solid fa-paper-plane"></i>
                    <span class="submit-label">Submit Application</span>
                </button>

            </form>

        </div>

    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const themeToggle = document.getElementById('themeToggle');
    const themeRoot = document.documentElement;
    const themeKey = 'ramyum-theme';
    let savedTheme = null;

    try {
        savedTheme = localStorage.getItem(themeKey);
    } catch (error) {
        // The theme still works for this page when storage is unavailable.
    }

    function applyTheme(theme) {
        const isDark = theme === 'dark';
        if (isDark) {
            themeRoot.setAttribute('data-theme', 'dark');
        } else {
            themeRoot.removeAttribute('data-theme');
        }
        themeToggle.setAttribute('aria-pressed', String(isDark));
    }

    const initialTheme = savedTheme ||
        (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    applyTheme(initialTheme);

    themeToggle.addEventListener('click', function () {
        const nextTheme = themeRoot.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        applyTheme(nextTheme);
        try {
            localStorage.setItem(themeKey, nextTheme);
        } catch (error) {
            // Keep the selected theme active for this page when storage is unavailable.
        }
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const input = document.getElementById('academicDocuments');
    const fileList = document.getElementById('academicFileList');

    if (!input || !fileList) {
        return;
    }

    let selectedFiles = [];

    function formatFileSize(bytes) {
        if (bytes < 1024) {
            return bytes + ' B';
        }
        if (bytes < 1024 * 1024) {
            return (bytes / 1024).toFixed(1) + ' KB';
        }
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function renderFiles() {
        fileList.innerHTML = '';

        selectedFiles.forEach(function (file, index) {
            const item = document.createElement('div');
            item.className = 'academic-file-item';

            const info = document.createElement('div');
            info.className = 'academic-file-info';

            const icon = document.createElement('div');
            icon.className = 'academic-file-icon';
            icon.textContent = 'DOC';

            const name = document.createElement('div');
            name.className = 'academic-file-name';
            name.title = file.name;
            name.textContent = file.name;

            const size = document.createElement('span');
            size.className = 'academic-file-size';
            size.textContent = '(' + formatFileSize(file.size) + ')';

            name.appendChild(size);
            info.appendChild(icon);
            info.appendChild(name);

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'academic-file-remove';
            removeButton.setAttribute('aria-label', 'Remove ' + file.name);
            removeButton.innerHTML = '&times;';

            removeButton.addEventListener('click', function () {
                selectedFiles.splice(index, 1);
                updateInputFiles();
                renderFiles();
            });

            item.appendChild(info);
            item.appendChild(removeButton);

            fileList.appendChild(item);
        });
    }

    function updateInputFiles() {
        const dataTransfer = new DataTransfer();

        selectedFiles.forEach(function (file) {
            dataTransfer.items.add(file);
        });

        input.files = dataTransfer.files;

        if (selectedFiles.length === 0) {
            input.required = <?= $academicRequired ? 'true' : 'false' ?>;
        } else {
            input.required = false;
        }
    }

    input.addEventListener('change', function () {
        Array.from(input.files).forEach(function (file) {
            const alreadyExists = selectedFiles.some(function (existingFile) {
                return (
                    existingFile.name === file.name &&
                    existingFile.size === file.size &&
                    existingFile.lastModified === file.lastModified
                );
            });

            if (!alreadyExists) {
                selectedFiles.push(file);
            }
        });

        updateInputFiles();
        renderFiles();
    });
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('form[action="?page=submit-application"]');
    const button = form ? form.querySelector('.submit-button') : null;
    const label = button ? button.querySelector('.submit-label') : null;
    const consent = document.getElementById('data_privacy_consent');
    const consentError = document.getElementById('consentError');

    if (!form || !button || !label) {
        return;
    }

    const originalLabel = label.textContent;

    form.addEventListener('submit', function (event) {
        if (consent && !consent.checked) {
            event.preventDefault();
            if (consentError) {
                consentError.classList.add('is-visible');
            }
            consent.focus();
            return;
        }

        button.disabled = true;
        label.textContent = 'Checking your documents… this can take up to a minute';
    });

    if (consent) {
        consent.addEventListener('invalid', function () {
            if (consentError) {
                consentError.classList.add('is-visible');
            }
        });

        consent.addEventListener('change', function () {
            if (consent.checked && consentError) {
                consentError.classList.remove('is-visible');
            }
        });
    }

    window.addEventListener('pageshow', function () {
        button.disabled = false;
        label.textContent = originalLabel;
    });
});
</script>

</body>
</html>