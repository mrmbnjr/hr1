/* ==========================================================
   PROFILE PAGE
   Tabs, inline edit mode, password helpers, copy-to-clipboard.
   Progressive: the page is fully readable without JS, these only
   add the interactive layer on top.
========================================================== */
(() => {
    "use strict";

    const page = document.querySelector(".profile-page");
    if (!page) return;

    /* ------------------------------------------------------
       Toast
    ------------------------------------------------------ */
    const toast = document.getElementById("profileToast");
    let toastTimer = null;

    function showToast(message) {
        if (!toast) return;
        toast.innerHTML = '<i class="fa-solid fa-circle-check" aria-hidden="true"></i><span></span>';
        toast.querySelector("span").textContent = message;
        toast.hidden = false;
        requestAnimationFrame(() => toast.classList.add("is-visible"));

        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => {
            toast.classList.remove("is-visible");
            setTimeout(() => { toast.hidden = true; }, 220);
        }, 1900);
    }

    /* ------------------------------------------------------
       Tabs
    ------------------------------------------------------ */
    const tabs = Array.from(page.querySelectorAll(".profile-tab"));
    const panels = Array.from(page.querySelectorAll(".profile-panel"));
    const tabNames = tabs.map(tab => tab.dataset.tab);

    function activateTab(name, options = {}) {
        const { focus = false, syncHash = true } = options;
        if (!tabNames.includes(name)) return;

        tabs.forEach(tab => {
            const on = tab.dataset.tab === name;
            tab.classList.toggle("active", on);
            tab.setAttribute("aria-selected", String(on));
            tab.tabIndex = on ? 0 : -1;
            if (on && focus) tab.focus();
        });

        panels.forEach(panel => {
            const on = panel.id === `panel-${name}`;
            panel.classList.toggle("active", on);
            panel.hidden = !on;
        });

        if (syncHash) {
            history.replaceState(null, "", `#${name}`);
        }
    }

    if (tabs.length) {
        // Prefer a deep-link hash, otherwise keep whatever the server rendered.
        const serverTab = tabs.find(tab => tab.classList.contains("active"))?.dataset.tab || "personal";
        const hashTab = window.location.hash.replace("#", "");
        activateTab(tabNames.includes(hashTab) ? hashTab : serverTab, { syncHash: false });

        tabs.forEach((tab, index) => {
            tab.addEventListener("click", () => activateTab(tab.dataset.tab, { focus: true }));

            tab.addEventListener("keydown", event => {
                const keys = { ArrowRight: 1, ArrowLeft: -1 };
                let next = null;

                if (event.key in keys) {
                    next = (index + keys[event.key] + tabs.length) % tabs.length;
                } else if (event.key === "Home") {
                    next = 0;
                } else if (event.key === "End") {
                    next = tabs.length - 1;
                }

                if (next === null) return;
                event.preventDefault();
                activateTab(tabs[next].dataset.tab, { focus: true });
            });
        });
    }

    /* ------------------------------------------------------
       Inline edit mode
       Fields start disabled (view mode). Edit enables them,
       Cancel restores the server values, Save submits.
    ------------------------------------------------------ */
    function fieldsOf(form) {
        return Array.from(form.querySelectorAll("input:not([type='hidden']), textarea"));
    }

    function setEditing(form, editing) {
        const editBtn = form.querySelector("[data-edit-toggle]");
        const cancelBtn = form.querySelector("[data-cancel]");
        const saveBtn = form.querySelector("[data-save]");
        const hint = form.querySelector("[data-edit-hint]");

        fieldsOf(form).forEach(field => {
            if (field.hasAttribute("readonly")) return;   // role/status stay fixed
            field.disabled = !editing;
        });

        form.classList.toggle("is-editing", editing);
        if (editBtn) editBtn.hidden = editing;
        if (cancelBtn) cancelBtn.hidden = !editing;
        if (saveBtn) saveBtn.hidden = !editing;

        if (hint) {
            hint.innerHTML = editing
                ? '<i class="fa-solid fa-pen" aria-hidden="true"></i> Editing — remember to save your changes.'
                : '<i class="fa-solid fa-lock" aria-hidden="true"></i> Viewing mode — select Edit to make changes.';
        }

        if (editing) {
            const first = fieldsOf(form).find(field => !field.disabled && !field.readOnly);
            first?.focus();
        }
    }

    page.querySelectorAll("[data-editable]").forEach(form => {
        const editBtn = form.querySelector("[data-edit-toggle]");
        if (!editBtn) return;

        editBtn.addEventListener("click", () => setEditing(form, true));

        form.querySelector("[data-cancel]")?.addEventListener("click", () => {
            form.reset();                 // restore the server-rendered values
            setEditing(form, false);
        });
    });

    /* ------------------------------------------------------
       Jump buttons (hero shortcuts + inline links)
    ------------------------------------------------------ */
    page.querySelectorAll("[data-jump]").forEach(button => {
        button.addEventListener("click", () => {
            const name = button.dataset.jump;
            activateTab(name);

            document.getElementById(`panel-${name}`)?.scrollIntoView({ behavior: "smooth", block: "start" });

            if (button.hasAttribute("data-start-edit")) {
                const form = document.querySelector(`#panel-${name} [data-editable]`);
                form?.querySelector("[data-edit-toggle]")?.click();
            }
        });
    });

    /* ------------------------------------------------------
       Password: reveal, strength, confirmation
    ------------------------------------------------------ */
    page.querySelectorAll("[data-reveal]").forEach(button => {
        button.addEventListener("click", () => {
            const input = button.parentElement.querySelector("input");
            if (!input) return;

            const show = input.type === "password";
            input.type = show ? "text" : "password";
            button.setAttribute("aria-pressed", String(show));
            button.setAttribute("aria-label", show ? "Hide password" : "Show password");

            const icon = button.querySelector("i");
            icon?.classList.toggle("fa-eye", !show);
            icon?.classList.toggle("fa-eye-slash", show);
        });
    });

    const strengthInput = page.querySelector("[data-strength-input]");
    const strengthBox = page.querySelector("[data-strength]");

    if (strengthInput && strengthBox) {
        const label = strengthBox.querySelector("[data-strength-label]");
        const captions = ["Use 8+ characters with letters and numbers.", "Weak password", "Fair password", "Good password", "Strong password"];

        const score = value => {
            if (!value) return 0;
            if (value.length < 8) return 1;          // gate: too short

            let points = 1;
            if (value.length >= 12) points++;
            if (/[A-Za-z]/.test(value) && /\d/.test(value)) points++;
            if (/[^A-Za-z0-9]/.test(value)) points++;

            return Math.min(4, points);
        };

        const updateStrength = () => {
            const level = score(strengthInput.value);
            strengthBox.dataset.level = String(level);
            if (label) label.textContent = captions[level];
        };

        strengthInput.addEventListener("input", updateStrength);
        updateStrength();
    }

    const newPassword = document.getElementById("newPassword");
    const confirmPassword = document.getElementById("confirmPassword");
    const matchHint = page.querySelector("[data-match-hint]");

    if (newPassword && confirmPassword && matchHint) {
        const updateMatch = () => {
            if (!confirmPassword.value) {
                matchHint.textContent = "Both entries must match.";
                matchHint.className = "field-help";
                return;
            }
            const matches = newPassword.value === confirmPassword.value;
            matchHint.textContent = matches ? "Passwords match." : "Passwords do not match yet.";
            matchHint.className = `field-help ${matches ? "is-ok" : "is-bad"}`;
        };

        newPassword.addEventListener("input", updateMatch);
        confirmPassword.addEventListener("input", updateMatch);
    }

    /* ------------------------------------------------------
       Copy to clipboard
    ------------------------------------------------------ */
    page.querySelectorAll("[data-copy]").forEach(button => {
        button.addEventListener("click", async () => {
            const value = button.dataset.copy || "";
            if (!value) return;

            try {
                await navigator.clipboard.writeText(value);
            } catch {
                const helper = document.createElement("textarea");
                helper.value = value;
                helper.setAttribute("readonly", "");
                helper.style.position = "fixed";
                helper.style.opacity = "0";
                document.body.appendChild(helper);
                helper.select();
                try { document.execCommand("copy"); } catch { /* ignore */ }
                helper.remove();
            }

            button.classList.add("is-copied");
            setTimeout(() => button.classList.remove("is-copied"), 1400);
            showToast("Copied to clipboard");
        });
    });

    /* ------------------------------------------------------
       Flash dismiss
    ------------------------------------------------------ */
    page.querySelector(".flash-close")?.addEventListener("click", event => {
        event.currentTarget.closest(".profile-flash")?.remove();
    });
})();
