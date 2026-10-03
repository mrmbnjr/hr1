/* public/assets/js/user-edit.js
   Admin → User Management → Edit Profile */
(function () {
    'use strict';

    var form = document.getElementById('editProfileForm');
    if (!form) return;

    var saveBtns = form.ownerDocument.querySelectorAll('.js-save');
    var dirty = false;
    var submitting = false;

    /* ---------- Validation ---------- */
    function field(el) { return el.closest('.ue-field'); }

    function setError(el, msg) {
        var wrap = field(el);
        if (!wrap) return;
        var out = wrap.querySelector('.ue-error');
        if (msg) {
            wrap.classList.add('has-error');
            if (out) out.textContent = msg;
        } else {
            wrap.classList.remove('has-error');
            if (out) out.textContent = '';
        }
    }

    var rules = {
        first_name: function (v) { return v.trim() && v.length <= 100 ? '' : 'Enter a valid first name.'; },
        last_name: function (v) { return v.trim() && v.length <= 100 ? '' : 'Enter a valid last name.'; },
        email: function (v) {
            if (!v.trim()) return 'Email is required.';
            if (v.length > 150) return 'Email is too long.';
            return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()) ? '' : 'Enter a valid email address.';
        },
        phone: function (v) {
            if (!v.trim()) return 'Phone is required.';
            return /^[0-9+()\-\s]{7,30}$/.test(v) ? '' : 'Enter a valid phone number.';
        },
        username: function (v) {
            if (!v.trim()) return 'Username is required.';
            return /^[A-Za-z0-9._-]{3,50}$/.test(v.trim()) ? '' : 'Use 3–50 letters, numbers, . _ or -.';
        },
        account_email: function (v) {
            if (!v.trim()) return 'Sign-in email is required.';
            if (v.length > 150) return 'Email is too long.';
            return /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v.trim()) ? '' : 'Enter a valid email address.';
        },
        role_id: function (v) { return v ? '' : 'Select a role.'; },
        status: function (v) { return (v === 'Active' || v === 'Inactive') ? '' : 'Select a status.'; }
    };

    function validateField(name) {
        var el = form.elements[name];
        if (!el || !rules[name]) return true;
        var msg = rules[name](el.value);
        setError(el, msg);
        return !msg;
    }

    Object.keys(rules).forEach(function (name) {
        var el = form.elements[name];
        if (!el) return;
        el.addEventListener('blur', function () { validateField(name); });
        el.addEventListener('input', function () {
            if (field(el).classList.contains('has-error')) validateField(name);
        });
    });

    function validateAll() {
        var firstBad = null;
        Object.keys(rules).forEach(function (name) {
            if (!validateField(name) && !firstBad) firstBad = form.elements[name];
        });
        if (firstBad) {
            firstBad.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstBad.focus({ preventScroll: true });
        }
        return !firstBad;
    }

    /* ---------- Character counter (address) ---------- */
    (function () {
        var area = form.elements['address'];
        var counter = document.getElementById('addressCount');
        if (!area || !counter) return;

        var max = parseInt(area.getAttribute('maxlength'), 10) || 500;
        function update() { counter.textContent = area.value.length + ' / ' + max; }

        area.addEventListener('input', update);
        update();
    })();

    /* ---------- Toast ---------- */
    function toast(message, type) {
        var wrap = document.getElementById('toastWrap');
        if (!wrap) return;
        var t = document.createElement('div');
        t.className = 'ue-toast' + (type === 'error' ? ' error' : '');

        var icon = document.createElement('i');
        icon.className = 'fa-solid ' + (type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check');
        icon.setAttribute('aria-hidden', 'true');

        var text = document.createElement('span');
        text.textContent = message;

        t.appendChild(icon);
        t.appendChild(text);
        wrap.appendChild(t);
        requestAnimationFrame(function () { t.classList.add('show'); });

        var removed = false;
        function remove() { if (!removed) { removed = true; t.remove(); } }

        setTimeout(function () {
            t.classList.remove('show');
            t.addEventListener('transitionend', remove);
            setTimeout(remove, 400); // fallback if transitionend never fires
        }, 3500);
    }

    /* ---------- Submit ---------- */
    function setSaving(on) {
        submitting = on;
        saveBtns.forEach(function (b) {
            b.disabled = on;
            b.innerHTML = on
                ? '<i class="fa-solid fa-spinner fa-spin"></i> Saving…'
                : '<i class="fa-solid fa-floppy-disk"></i> Save Changes';
        });
    }

    form.addEventListener('submit', function (ev) {
        ev.preventDefault();
        if (submitting) return;
        if (!validateAll()) {
            toast('Please fix the highlighted fields.', 'error');
            return;
        }

        setSaving(true);
        var data = new FormData(form);

        fetch(form.getAttribute('action'), {
            method: 'POST',
            body: data,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (res) {
                return res.json().then(function (json) { return { ok: res.ok, json: json }; });
            })
            .then(function (r) {
                if (r.ok && r.json.success) {
                    dirty = false;
                    toast(r.json.message || 'Profile updated successfully.');
                    setTimeout(function () {
                        window.location.href = r.json.redirect || '?page=user-management';
                    }, 900);
                } else {
                    // Server-side field errors: { errors: { email: "Already in use" } }
                    var errs = (r.json && r.json.errors) || {};
                    Object.keys(errs).forEach(function (name) {
                        if (form.elements[name]) setError(form.elements[name], errs[name]);
                    });
                    toast((r.json && r.json.message) || 'Could not save changes.', 'error');
                    setSaving(false);
                }
            })
            .catch(function () {
                toast('Network error. Please try again.', 'error');
                setSaving(false);
            });
    });

    /* ---------- Unsaved-changes guard ---------- */
    form.addEventListener('input', function () { dirty = true; });
    form.addEventListener('change', function () { dirty = true; });
    window.addEventListener('beforeunload', function (e) {
        if (dirty && !submitting) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
})();