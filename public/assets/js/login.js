/* =========================================================
   Login Page
   - Footer icons
   - Form validation
   - AJAX login
========================================================= */

(function () {
    "use strict";

    /* ---------- Footer corner icons ---------- */
    var CORNER_ICON =
        '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" ' +
        'stroke-linecap="round" aria-hidden="true">' +
        '<path d="M4 9V6a2 2 0 0 1 2-2h3M20 9V6a2 2 0 0 1-2-2h-3' +
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

    /* ---------- Login form ---------- */

    var form = document.getElementById("loginForm");
    var usernameField = document.getElementById("username");
    var passwordField = document.getElementById("password");
    var message = document.getElementById("formMessage");
    var loginBtn = document.getElementById("loginBtn");

    function setError(text) {
        message.textContent = text || "";
        message.classList.remove("is-success");
    }

    function clearFieldError(input) {
        input.closest(".field").classList.remove("has-error");
    }

    [usernameField, passwordField].forEach(function (input) {
        input.addEventListener("input", function () {
            clearFieldError(input);
            setError("");
        });
    });

    form.addEventListener("submit", function (event) {
        event.preventDefault();

        var username = usernameField.value.trim();
        var password = passwordField.value;
        var firstInvalid = null;

        [usernameField, passwordField].forEach(clearFieldError);

        if (!username) firstInvalid = firstInvalid || usernameField;
        if (!password) firstInvalid = firstInvalid || passwordField;

        if (firstInvalid) {
            usernameField.closest(".field").classList.toggle("has-error", !username);
            passwordField.closest(".field").classList.toggle("has-error", !password);

            setError("Please fill in both your username and password.");
            firstInvalid.focus();
            return;
        }

        setError("");

        loginBtn.disabled = true;
        loginBtn.classList.add("is-loading");

        fetch("/hr1/public/?page=login", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded"
            },
            body: new URLSearchParams({
                username: username,
                password: password
            })
        })
        .then(response => response.json())
        .then(data => {

            loginBtn.disabled = false;
            loginBtn.classList.remove("is-loading");

            if (data.success) {

                message.textContent = "Login successful!";
                message.classList.add("is-success");

                window.location.href = "/hr1/public/?page=dashboard";

            } else {

                message.textContent = data.message;
                message.classList.add("is-error");

            }

        })
        .catch(error => {

            loginBtn.disabled = false;
            loginBtn.classList.remove("is-loading");

            message.textContent = "Server error.";
            message.classList.add("is-error");

            console.error(error);

        });
    });

})();