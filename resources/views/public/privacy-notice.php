<?php

/*
|--------------------------------------------------------------------------
| Privacy Notice (public)
|--------------------------------------------------------------------------
| Opened in a new tab from the consent checkbox on the job application form
| (resources/views/public/apply.php). Describes how applicant personal data
| is collected, used, shared, retained and protected.
*/

$companyName = 'RAM-YUM';
$contactEmail = 'hr@ramyum.com';

$lastUpdated = 'October 11, 2026';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Notice - <?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?></title>

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
            --border: #eadfcf;
            --border-strong: #d9c9ae;
            --shadow: 0 6px 24px -8px rgba(80, 0, 20, 0.14);

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
            --border: #3a2a2d;
            --border-strong: #4d3a3e;
            --shadow: 0 6px 24px -8px rgba(0, 0, 0, 0.5);
        }

        [data-theme="dark"] .brand-name,
        [data-theme="dark"] .doc-badge,
        [data-theme="dark"] .doc-section h2 i { color: var(--gold) !important; }

        * { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: var(--font-body);
            background:
                radial-gradient(900px 400px at 100% -10%, var(--gold-light), transparent 60%),
                radial-gradient(700px 380px at -10% 0%, var(--maroon-soft), transparent 60%),
                var(--page-bg);
            background-attachment: fixed;
            color: var(--ink);
            line-height: 1.65;
            -webkit-font-smoothing: antialiased;
        }

        .notice-page {
            max-width: 900px;
            margin: 0 auto;
            padding: 28px 20px 64px;
        }

        /* ============ Header ============ */
        .site-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 20px;
            margin-bottom: 24px;
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

        /* ============ Hero ============ */
        .notice-hero {
            padding: 30px;
            margin-bottom: 24px;
            background: linear-gradient(135deg, var(--maroon-dark), var(--maroon));
            color: #fff;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .notice-hero::after {
            content: '';
            position: absolute;
            right: -40px; top: -40px;
            width: 160px; height: 160px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(201,162,39,.35), transparent 70%);
            pointer-events: none;
        }

        .doc-badge {
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

        .notice-hero h1 {
            font-family: var(--font-display);
            font-size: 32px;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.01em;
            margin-bottom: 8px;
        }

        .notice-hero p {
            color: rgba(255, 255, 255, 0.85);
            font-size: 14.5px;
            max-width: 60ch;
        }

        .notice-meta {
            margin-top: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 12.5px;
            font-weight: 700;
            color: #fff;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.22);
            padding: 6px 14px;
            border-radius: 999px;
        }

        /* ============ Document body ============ */
        .doc-card {
            background: var(--card-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            padding: 32px;
        }

        .doc-intro {
            font-size: 15px;
            color: var(--ink);
            padding-bottom: 22px;
            margin-bottom: 24px;
            border-bottom: 1px dashed var(--border-strong);
        }

        .doc-section { margin-bottom: 28px; }
        .doc-section:last-child { margin-bottom: 0; }

        .doc-section h2 {
            display: flex;
            align-items: center;
            gap: 10px;
            font-family: var(--font-display);
            font-size: 19px;
            font-weight: 700;
            color: var(--maroon);
            margin-bottom: 10px;
        }

        .doc-section h2 i { font-size: 15px; color: var(--gold); }

        .doc-section p { font-size: 14.5px; color: var(--ink-soft); margin-bottom: 10px; }
        .doc-section p:last-child { margin-bottom: 0; }
        .doc-section strong { color: var(--ink); }

        .doc-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
            margin: 12px 0;
        }

        .doc-list li {
            position: relative;
            padding-left: 26px;
            font-size: 14.5px;
            color: var(--ink-soft);
        }

        .doc-list li::before {
            content: '\f00c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            left: 0;
            top: 1px;
            color: var(--gold);
            font-size: 12px;
        }

        .doc-callout {
            background: var(--cream);
            border: 1px solid var(--border);
            border-left: 4px solid var(--gold);
            border-radius: var(--radius-md);
            padding: 16px 18px;
            font-size: 14px;
            color: var(--ink-soft);
            margin: 14px 0;
        }

        .doc-callout strong { color: var(--maroon); }

        .contact-box {
            margin-top: 8px;
            background: var(--maroon-soft);
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 18px 20px;
        }

        .contact-box a { color: var(--maroon); font-weight: 700; text-decoration: none; }
        .contact-box a:hover { text-decoration: underline; }
        [data-theme="dark"] .contact-box a { color: var(--gold); }

        .doc-footnote {
            margin-top: 28px;
            padding-top: 20px;
            border-top: 1px dashed var(--border-strong);
            font-size: 13px;
            color: var(--ink-faint);
        }

        .doc-actions {
            margin-top: 24px;
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            padding: 11px 20px;
            border-radius: var(--radius-md);
            font-family: var(--font-display);
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            border: none;
            transition: transform .18s, box-shadow .18s, background .18s;
        }

        .btn--primary {
            background: linear-gradient(135deg, var(--maroon), var(--maroon-dark));
            color: #fff;
            border-bottom: 3px solid var(--gold);
            box-shadow: 0 8px 18px -6px rgba(128, 0, 32, .45);
        }

        .btn--primary:hover { transform: translateY(-1px); }
        [data-theme="dark"] .btn--primary { background: linear-gradient(135deg, #a3112f, var(--maroon)); }

        .btn--ghost {
            background: var(--card-bg);
            color: var(--ink);
            border: 1.5px solid var(--border-strong);
        }

        .btn--ghost:hover { border-color: var(--maroon); color: var(--maroon); }
        [data-theme="dark"] .btn--ghost:hover { color: var(--gold); border-color: var(--gold); }

        @media (max-width: 640px) {
            .notice-page { padding: 14px 12px 40px; }
            .site-header { padding: 12px 14px; }
            .header-badge { display: none; }
            .notice-hero { padding: 22px 20px; }
            .notice-hero h1 { font-size: 26px; }
            .doc-card { padding: 22px; }
        }

        @media (prefers-reduced-motion: reduce) {
            * { transition: none !important; }
        }
    </style>
</head>

<body>

<div class="notice-page">

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
            <span class="header-badge"><i class="fa-solid fa-shield-halved"></i> Data Privacy</span>
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

    <section class="notice-hero">
        <div class="doc-badge"><i class="fa-solid fa-user-shield"></i> Privacy Notice</div>
        <h1>Applicant Privacy Notice</h1>
        <p>
            How <?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?> collects, uses, shares, and protects the
            personal information you provide when you apply for a job with us.
        </p>
        <span class="notice-meta"><i class="fa-solid fa-calendar-day"></i> Last updated: <?= htmlspecialchars($lastUpdated, ENT_QUOTES, 'UTF-8') ?></span>
    </section>

    <main class="doc-card">

        <p class="doc-intro">
            <?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?> ("we", "us", or "our") is committed to protecting
            your personal information. This Privacy Notice explains what we collect from job applicants, why we collect
            it, how it is used and shared, how long we keep it, and the rights available to you. It applies to all
            personal information you submit through our online job application form, our recruitment emails, and any
            supporting documents you provide.
        </p>

        <section class="doc-section">
            <h2><i class="fa-solid fa-folder-open"></i> 1. Information We Collect</h2>
            <p>When you apply for a position, we may collect the following categories of personal information:</p>
            <ul class="doc-list">
                <li><strong>Identification and contact details</strong> — full name, email address, phone number, and mailing address.</li>
                <li><strong>Application materials</strong> — your resume or curriculum vitae, certificates, diplomas, and any cover letter you upload.</li>
                <li><strong>Recruitment records</strong> — interview notes, assessment results, and correspondence related to your application.</li>
                <li><strong>Technical information</strong> — limited data such as submission timestamps and network information used to secure the application process and prevent misuse.</li>
            </ul>
            <p>
                We only collect personal information that is relevant and necessary to evaluate your application. Please
                avoid including sensitive personal information that is not required for recruitment.
            </p>
        </section>

        <section class="doc-section">
            <h2><i class="fa-solid fa-bullseye"></i> 2. How We Use Your Information</h2>
            <p>Your personal information is used strictly for recruitment and employment purposes, including to:</p>
            <ul class="doc-list">
                <li>Assess your qualifications, experience, and suitability for the role you applied for.</li>
                <li>Verify the authenticity and consistency of the information and documents you submitted.</li>
                <li>Communicate with you about your application, interviews, and next steps.</li>
                <li>Maintain recruitment records and comply with applicable legal and regulatory obligations.</li>
                <li>Prepare your employment records if you are offered and accept a position.</li>
            </ul>
            <div class="doc-callout">
                <strong>Automated screening.</strong> Some applications are reviewed with the assistance of automated
                tools to help our recruitment team screen documents. These tools support, and do not replace, human
                decision-making in the hiring process.
            </div>
        </section>

        <section class="doc-section">
            <h2><i class="fa-solid fa-scale-balanced"></i> 3. Legal Basis and Your Consent</h2>
            <p>
                We process your personal information on the basis of your consent, our legitimate interest in
                recruiting qualified candidates, and our compliance with applicable laws. By ticking the consent box on
                the application form, you confirm that you have read this Privacy Notice and that you consent to the
                collection and processing of your personal information for recruitment and employment purposes.
            </p>
            <p>
                You may withdraw your consent at any time. Where consent is withdrawn, we will stop processing your
                information unless we are required or permitted by law to continue.
            </p>
        </section>

        <section class="doc-section">
            <h2><i class="fa-solid fa-share-nodes"></i> 4. When We Share Your Information</h2>
            <p>
                We do not sell your personal information. We may share it only as necessary and with appropriate
                safeguards, including with:
            </p>
            <ul class="doc-list">
                <li>Authorized members of our recruitment and hiring team.</li>
                <li>Service providers that support our systems, under confidentiality obligations.</li>
                <li>Government authorities or other parties where disclosure is required by law.</li>
            </ul>
        </section>

        <section class="doc-section">
            <h2><i class="fa-solid fa-clock-rotate-left"></i> 5. How Long We Keep Your Information</h2>
            <p>
                We retain applicant personal information only for as long as necessary for the purposes described in
                this notice or as required by law. In general:
            </p>
            <ul class="doc-list">
                <li>Applications that are not selected are kept for a limited retention period and then deleted or anonymized.</li>
                <li>Applications for closed positions are removed in line with our data retention policy once the posting closes.</li>
                <li>If you are hired, your information becomes part of your employment record and is retained in accordance with applicable employment and record-keeping requirements.</li>
            </ul>
        </section>

        <section class="doc-section">
            <h2><i class="fa-solid fa-lock"></i> 6. How We Protect Your Information</h2>
            <p>
                We apply organizational, physical, and technical safeguards designed to protect your personal
                information against unauthorized access, disclosure, alteration, or loss. These measures include
                access controls, encrypted transmission, and the secure storage of uploaded documents. While no method
                of transmission or storage is completely secure, we continuously review our practices to keep your
                information protected.
            </p>
        </section>

        <section class="doc-section">
            <h2><i class="fa-solid fa-user-check"></i> 7. Your Rights</h2>
            <p>Subject to applicable law, you have the right to:</p>
            <ul class="doc-list">
                <li>Be informed about how your personal information is being processed.</li>
                <li>Access the personal information we hold about you.</li>
                <li>Request correction of inaccurate or incomplete information.</li>
                <li>Request the erasure or blocking of your information under certain conditions.</li>
                <li>Object to or withdraw your consent to processing.</li>
                <li>Request a copy of your information in a portable format, where applicable.</li>
                <li>Lodge a complaint with the relevant data protection authority.</li>
            </ul>
            <p>To exercise any of these rights, please contact us using the details below.</p>
        </section>

        <section class="doc-section">
            <h2><i class="fa-solid fa-envelope"></i> 8. How to Contact Us</h2>
            <p>
                If you have questions about this Privacy Notice or wish to exercise your privacy rights, please reach
                out to our recruitment team:
            </p>
            <div class="contact-box">
                <p><strong><?= htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8') ?> — Recruitment / Data Privacy Contact</strong></p>
                <p>Email: <a href="mailto:<?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8') ?></a></p>
            </div>
        </section>

        <p class="doc-footnote">
            This Privacy Notice may be updated from time to time to reflect changes in our practices or applicable
            laws. The latest version will always be made available on this page, with the date of the most recent
            revision shown above.
        </p>

        <div class="doc-actions">
            <button type="button" class="btn btn--primary" onclick="window.close()">
                <i class="fa-solid fa-check"></i>
                <span>Close this tab</span>
            </button>
            <button type="button" class="btn btn--ghost" onclick="window.print()">
                <i class="fa-solid fa-print"></i>
                <span>Print / Save as PDF</span>
            </button>
        </div>

    </main>

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

</body>
</html>
