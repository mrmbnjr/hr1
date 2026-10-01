<?php
$pageTitle = "Login";
$pageCSS = "login.css";
$pageJS = "login.js";
$pageDescription = "Login to your RAM-YUM Store account — Korean and Japanese Store.";
?>

<?php require_once __DIR__ . '/../includes/header.php'; ?>

<button
    type="button"
    class="theme-toggle"
    id="themeToggle"
    aria-label="Toggle light and dark theme"
    aria-pressed="false"
>
    <svg
        class="icon icon-sun"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <circle cx="12" cy="12" r="4.2"></circle>
        <path d="M12 2.5v2.4M12 19.1v2.4M4.6 4.6l1.7 1.7M17.7 17.7l1.7 1.7M2.5 12h2.4M19.1 12h2.4M4.6 19.4l1.7-1.7M17.7 6.3l1.7-1.7"></path>
    </svg>

    <svg
        class="icon icon-moon"
        viewBox="0 0 24 24"
        fill="none"
        stroke="currentColor"
        stroke-width="2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
    >
        <path d="M20.8 14.3A8.5 8.5 0 1 1 9.7 3.2a6.8 6.8 0 0 0 11.1 11.1z"></path>
    </svg>
</button>

<main class="page">
    <section class="login-card" aria-labelledby="brandTitle">

        <div class="badge" aria-hidden="true">
            <img
                src="<?= asset('images/logo.png') ?>"
                alt="RAM-YUM Store logo"
            >
        </div>

        <h1 class="brand" id="brandTitle">
            RAM-YUM <span>Store</span>
        </h1>

        <!-- =========================
             STEP 1 — LOGIN
        ========================== -->

        <div id="loginStep">

            <h2 class="page-title">Login</h2>

            <form
                class="login-form"
                id="loginForm"
                method="POST"
                action="<?= url('?page=login') ?>"
                novalidate
            >

                <div class="field">
                    <label for="username">Username</label>

                    <input
                        type="text"
                        id="username"
                        name="username"
                        autocomplete="username"
                        spellcheck="false"
                        minlength="3"
                        maxlength="50"
                        required
                    >
                </div>

                <div class="field">
                    <label for="password">Password</label>

                    <input
                        type="password"
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        minlength="3"
                        maxlength="255"
                        required
                    >
                </div>

                <p
                    class="form-message"
                    id="formMessage"
                    role="alert"
                    aria-live="polite"
                ></p>

                <button
                    type="submit"
                    class="login-btn"
                    id="loginBtn"
                >
                    <span class="btn-label">LOGIN</span>
                </button>

            </form>

        </div>


        <!-- =========================
             STEP 2 — OTP
        ========================== -->

        <div
            id="otpStep"
            class="otp-step"
            hidden
        >

            <h2 class="page-title">Verify Login</h2>

            <div class="otp-icon" aria-hidden="true">
                <svg
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    stroke-width="1.8"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                >
                    <rect x="4" y="3" width="16" height="18" rx="3"></rect>
                    <path d="M8 7h8"></path>
                    <path d="M8 11h8"></path>
                    <path d="M8 15h3"></path>
                    <circle cx="16.5" cy="16" r="1"></circle>
                </svg>
            </div>

            <p class="otp-description">
                We've sent a 6-digit verification code to your registered email address.
            </p>

            <p
                class="otp-email"
                id="otpEmail"
                aria-live="polite"
            ></p>

            <form
                class="login-form otp-form"
                id="otpForm"
                method="POST"
                action="<?= url('?page=verify-otp') ?>"
                novalidate
            >

                <div class="field">
                    <label for="otp">Verification Code</label>

                    <input
                        type="text"
                        id="otp"
                        name="otp"
                        inputmode="numeric"
                        autocomplete="one-time-code"
                        pattern="[0-9]{6}"
                        minlength="6"
                        maxlength="6"
                        placeholder="000000"
                        required
                    >
                </div>

                <p
                    class="form-message"
                    id="otpMessage"
                    role="alert"
                    aria-live="polite"
                ></p>

                <button
                    type="submit"
                    class="login-btn"
                    id="verifyOtpBtn"
                >
                    <span class="btn-label">VERIFY</span>
                </button>

            </form>

            <div class="otp-actions">

                <button
                    type="button"
                    class="resend-btn"
                    id="resendOtpBtn"
                >
                    Resend Code
                </button>

                <span
                    class="otp-timer"
                    id="otpTimer"
                    aria-live="polite"
                >
                    Resend available in 60s
                </span>

            </div>

            <button
                type="button"
                class="back-login-btn"
                id="backToLoginBtn"
            >
                Back to Login
            </button>

        </div>

    </section>
</main>

<?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>