<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta
    name="viewport"
    content="width=device-width, initial-scale=1.0"
>

<title>Application Submitted</title>

<style>

    :root {
        --maroon: #8d1720;
        --maroon-dark: #5e1017;
        --gold: #f0b941;
        --ink: #281b18;
        --muted: #746863;
        --border: #e9dfd5;
    }

    * { box-sizing: border-box; }

    body {
        margin: 0;
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 28px;
        background: linear-gradient(135deg, #f8f5ef 0%, #fffaf2 48%, #f3e6db 100%);
        color: var(--ink);
        font-family: Georgia, "Times New Roman", serif;
    }

    .success-card {
        width: min(620px, 100%);
        padding: 42px;
        background: #fff;
        border: 1px solid var(--border);
        border-top: 7px solid var(--maroon);
        border-radius: 4px;
        text-align: center;
        box-shadow: 0 18px 50px rgba(94, 16, 23, .09);
    }

    .brand-logo {
        width: 76px;
        height: 76px;
        object-fit: contain;
        filter: drop-shadow(0 5px 8px rgba(94, 16, 23, .15));
    }

    .brand-name {
        margin: 10px 0 28px;
        color: var(--maroon-dark);
        font-family: Arial, sans-serif;
        font-size: 12px;
        font-weight: 700;
        letter-spacing: .16em;
    }

    .status-icon {
        width: 54px;
        height: 54px;
        display: grid;
        place-items: center;
        margin: 0 auto 20px;
        border-radius: 50%;
        background: #fff3cc;
        color: var(--maroon);
        font-family: Arial, sans-serif;
        font-size: 28px;
        font-weight: 700;
    }

    h1 { margin: 0 0 16px; color: var(--maroon-dark); font-size: clamp(29px, 5vw, 42px); line-height: 1.08; }

    p { margin: 12px 0; color: var(--muted); line-height: 1.65; }

    .notice {
        margin-top: 28px;
        padding: 20px 22px;
        background: #fff9e9;
        border: 1px solid #f2dfaa;
        border-left: 4px solid var(--gold);
        border-radius: 3px;
        text-align: left;
    }

    .notice p:first-child { margin-top: 0; }
    .notice p:last-child { margin-bottom: 0; }
    .notice strong { color: var(--maroon-dark); }

    @media (max-width: 560px) {
        body { padding: 14px; }
        .success-card { padding: 30px 21px; }
    }

</style>

</head>

<body>

<div class="success-card">

<img
    class="brand-logo"
    src="/hr1/public/assets/images/logo.png"
    alt="Ramyum Korean and Japanese Store logo"
>

<p class="brand-name">RAM-YUM | KOREAN &amp; JAPANESE STORE</p>

<div class="status-icon" aria-hidden="true">&#10003;</div>
<h1>
    Application Submitted Successfully
</h1>

<p>
    Thank you for applying to <strong>RAM-YUM</strong>.
</p>

<p>
    Your application has been successfully submitted
    and will be carefully reviewed by our recruitment team.
</p>

<div class="notice">

    <p>
        <strong>Please take note of the application deadline.</strong>
    </p>

    <p>
        If your application is selected to proceed to the next
        stage of the recruitment process, you will receive an
        email from our recruitment team after the application
        period has ended.
    </p>

    <p>
        If you do not receive an email after the application
        deadline, it means that your application was not selected
        for the next stage at this time.
    </p>

    <p>
        We sincerely appreciate your interest in joining RAM-YUM
        and the time you invested in your application.
    </p>

</div>
</div>

</body>

</html>
