/* =========================================================
   RAM-YUM Store — Authentication
   - Footer icons
   - Theme-compatible login UI
   - Username/password validation
   - AJAX password authentication
   - OTP verification
   - OTP resend cooldown
========================================================= */

(function () {
    "use strict";

    /* =====================================================
       Footer corner icons
    ===================================================== */

    var CORNER_ICON =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
        'stroke-linecap="round" aria-hidden="true">' +
        '<path d="M4 9V6a2 2 0 0 1 2-2h3M20 9V6a2 2 0 0 0-2-2h-3' +
        'M4 15v3a2 2 0 0 0 2 2h3M20 15v3a2 2 0 0 1-2 2h-3"/>' +
        "</svg>";

    function renderFooterIcons(count) {

        var left = document.getElementById("footerIconsLeft");
        var right = document.getElementById("footerIconsRight");

        if (!left || !right) return;

        var html = new Array(count).fill(CORNER_ICON).join("");

        left.innerHTML = html;
        right.innerHTML = html;
    }

    renderFooterIcons(4);


    /* =====================================================
       Login elements
    ===================================================== */

    var loginStep = document.getElementById("loginStep");
    var otpStep = document.getElementById("otpStep");

    var loginForm = document.getElementById("loginForm");
    var usernameField = document.getElementById("username");
    var passwordField = document.getElementById("password");
    var loginMessage = document.getElementById("formMessage");
    var loginBtn = document.getElementById("loginBtn");

    var otpForm = document.getElementById("otpForm");
    var otpField = document.getElementById("otp");
    var otpMessage = document.getElementById("otpMessage");
    var otpEmail = document.getElementById("otpEmail");
    var verifyOtpBtn = document.getElementById("verifyOtpBtn");

    var resendOtpBtn = document.getElementById("resendOtpBtn");
    var otpTimer = document.getElementById("otpTimer");
    var backToLoginBtn = document.getElementById("backToLoginBtn");

    if (!loginForm || !otpForm) {
        return;
    }


    /* =====================================================
       Message helpers
    ===================================================== */

    function setLoginMessage(text, type) {

        loginMessage.textContent = text || "";

        loginMessage.classList.remove(
            "is-success",
            "is-error"
        );

        if (type) {
            loginMessage.classList.add(type);
        }
    }

    function setOtpMessage(text, type) {

        otpMessage.textContent = text || "";

        otpMessage.classList.remove(
            "is-success",
            "is-error"
        );

        if (type) {
            otpMessage.classList.add(type);
        }
    }


    /* =====================================================
       Field helpers
    ===================================================== */

    function clearFieldError(input) {

        if (!input) return;

        var field = input.closest(".field");

        if (field) {
            field.classList.remove("has-error");
        }
    }

    function setFieldError(input, hasError) {

        if (!input) return;

        var field = input.closest(".field");

        if (field) {
            field.classList.toggle(
                "has-error",
                hasError
            );
        }
    }


    /* =====================================================
       Login field input
    ===================================================== */

    [usernameField, passwordField].forEach(function (input) {

        input.addEventListener("input", function () {

            clearFieldError(input);
            setLoginMessage("");
        });

    });


    /* =====================================================
       LOGIN
    ===================================================== */

    loginForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();

            var username =
                usernameField.value.trim();

            var password =
                passwordField.value;

            var firstInvalid = null;

            clearFieldError(usernameField);
            clearFieldError(passwordField);

            setLoginMessage("");


            /* ---------- Validation ---------- */

            if (!username) {

                setFieldError(
                    usernameField,
                    true
                );

                firstInvalid =
                    firstInvalid || usernameField;
            }

            if (!password) {

                setFieldError(
                    passwordField,
                    true
                );

                firstInvalid =
                    firstInvalid || passwordField;
            }

            if (firstInvalid) {

                setLoginMessage(
                    "Please fill in both your username and password.",
                    "is-error"
                );

                firstInvalid.focus();

                return;
            }


            /* ---------- Loading ---------- */

            loginBtn.disabled = true;
            loginBtn.classList.add("is-loading");


            /* ---------- AJAX ---------- */

            fetch(
                "?page=login",
                {
                    method: "POST",

                    headers: {
                        "Content-Type":
                            "application/x-www-form-urlencoded",

                        "Accept":
                            "application/json"
                    },

                    body: new URLSearchParams({
                        username: username,
                        password: password
                    })
                }
            )
            .then(function (response) {

                if (!response.ok) {
                    throw new Error(
                        "HTTP " + response.status
                    );
                }

                return response.json();
            })
            .then(function (data) {

                loginBtn.disabled = false;

                loginBtn.classList.remove(
                    "is-loading"
                );


                /* ---------- Password correct ---------- */

                if (data.success) {

                    /*
                     * IMPORTANT:
                     *
                     * The user is NOT logged in yet.
                     * The server has only verified the password
                     * and sent the OTP.
                     */

                    showOtpStep(
                        data.email || ""
                    );

                    return;
                }


                /* ---------- Login failed ---------- */

                setLoginMessage(
                    data.message ||
                    "Unable to log in.",
                    "is-error"
                );

            })
            .catch(function (error) {

                loginBtn.disabled = false;

                loginBtn.classList.remove(
                    "is-loading"
                );

                setLoginMessage(
                    "Unable to connect to the server. Please try again.",
                    "is-error"
                );

                console.error(
                    "Login error:",
                    error
                );
            });

        }
    );


    /* =====================================================
       SHOW OTP STEP
    ===================================================== */

    function showOtpStep(email) {

        loginStep.hidden = true;
        otpStep.hidden = false;

        setOtpMessage("");

        otpField.value = "";

        /*
         * Display the email returned by the controller.
         *
         * The controller should preferably return a masked
         * email, not the complete address.
         */
        if (email) {
            otpEmail.textContent =
                "Code sent to " + email;
        } else {
            otpEmail.textContent =
                "Check your registered email for the code.";
        }

        startOtpCooldown(60);

        /*
         * Focus OTP input after the transition.
         */
        window.setTimeout(
            function () {
                otpField.focus();
            },
            100
        );
    }


    /* =====================================================
       OTP INPUT
    ===================================================== */

    otpField.addEventListener(
        "input",
        function () {

            /*
             * Numbers only.
             */
            otpField.value =
                otpField.value
                    .replace(/\D/g, "")
                    .slice(0, 6);

            clearFieldError(otpField);

            setOtpMessage("");

        }
    );


    /* =====================================================
       VERIFY OTP
    ===================================================== */

    otpForm.addEventListener(
        "submit",
        function (event) {

            event.preventDefault();

            var otp =
                otpField.value.trim();

            clearFieldError(otpField);
            setOtpMessage("");


            /* ---------- Validate ---------- */

            if (!/^\d{6}$/.test(otp)) {

                setFieldError(
                    otpField,
                    true
                );

                setOtpMessage(
                    "Please enter the 6-digit verification code.",
                    "is-error"
                );

                otpField.focus();

                return;
            }


            /* ---------- Loading ---------- */

            verifyOtpBtn.disabled = true;

            verifyOtpBtn.classList.add(
                "is-loading"
            );


            /* ---------- AJAX ---------- */

            fetch(
                "?page=verify-otp",
                {
                    method: "POST",

                    headers: {
                        "Content-Type":
                            "application/x-www-form-urlencoded",

                        "Accept":
                            "application/json"
                    },

                    body: new URLSearchParams({
                        otp: otp
                    })
                }
            )
            .then(function (response) {

                if (!response.ok) {
                    throw new Error(
                        "HTTP " + response.status
                    );
                }

                return response.json();
            })
            .then(function (data) {

                verifyOtpBtn.disabled = false;

                verifyOtpBtn.classList.remove(
                    "is-loading"
                );


                /* ---------- OTP successful ---------- */

                if (data.success) {

                    setOtpMessage(
                        data.message ||
                        "Login successful!",
                        "is-success"
                    );

                    /*
                     * The server has now called Auth::login().
                     */
                    window.location.href =
                        data.redirect ||
                        "?page=dashboard";

                    return;
                }


                /* ---------- OTP failed ---------- */

                setOtpMessage(
                    data.message ||
                    "Invalid verification code.",
                    "is-error"
                );

                otpField.focus();
                otpField.select();

            })
            .catch(function (error) {

                verifyOtpBtn.disabled = false;

                verifyOtpBtn.classList.remove(
                    "is-loading"
                );

                setOtpMessage(
                    "Unable to connect to the server. Please try again.",
                    "is-error"
                );

                console.error(
                    "OTP verification error:",
                    error
                );
            });

        }
    );


    /* =====================================================
       RESEND OTP
    ===================================================== */

    resendOtpBtn.addEventListener(
        "click",
        function () {

            if (resendOtpBtn.disabled) {
                return;
            }

            resendOtpBtn.disabled = true;

            setOtpMessage(
                "Sending a new verification code...",
                "is-success"
            );

            fetch(
                "?page=resend-otp",
                {
                    method: "POST",

                    headers: {
                        "Accept":
                            "application/json"
                    }
                }
            )
            .then(function (response) {

                if (!response.ok) {
                    throw new Error(
                        "HTTP " + response.status
                    );
                }

                return response.json();
            })
            .then(function (data) {

                if (data.success) {

                    setOtpMessage(
                        data.message ||
                        "A new verification code has been sent.",
                        "is-success"
                    );

                    otpField.value = "";

                    otpField.focus();

                    startOtpCooldown(60);

                } else {

                    resendOtpBtn.disabled = false;

                    setOtpMessage(
                        data.message ||
                        "Unable to resend the verification code.",
                        "is-error"
                    );
                }

            })
            .catch(function (error) {

                resendOtpBtn.disabled = false;

                setOtpMessage(
                    "Unable to connect to the server. Please try again.",
                    "is-error"
                );

                console.error(
                    "OTP resend error:",
                    error
                );
            });

        }
    );


    /* =====================================================
       OTP COOLDOWN
    ===================================================== */

    var cooldownTimer = null;

    function startOtpCooldown(seconds) {

        if (cooldownTimer) {
            window.clearInterval(
                cooldownTimer
            );
        }

        resendOtpBtn.disabled = true;

        var remaining = seconds;

        updateTimer(remaining);

        cooldownTimer =
            window.setInterval(
                function () {

                    remaining--;

                    updateTimer(
                        remaining
                    );

                    if (remaining <= 0) {

                        window.clearInterval(
                            cooldownTimer
                        );

                        cooldownTimer = null;

                        resendOtpBtn.disabled =
                            false;

                        otpTimer.textContent =
                            "You can request a new code now.";
                    }

                },
                1000
            );
    }


    function updateTimer(seconds) {

        if (seconds > 0) {

            otpTimer.textContent =
                "Resend available in " +
                seconds +
                "s";

        }
    }


    /* =====================================================
       BACK TO LOGIN
    ===================================================== */

    backToLoginBtn.addEventListener(
        "click",
        function () {

            loginStep.hidden = false;
            otpStep.hidden = true;

            otpField.value = "";

            setOtpMessage("");

            usernameField.focus();

            /*
             * This does NOT authenticate the user.
             *
             * The pending OTP session remains on the
             * server until it expires or another login
             * occurs.
             */
        }
    );

})();