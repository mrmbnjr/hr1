/* Position Details page — employee search + Edit Position modal */
(function () {
    'use strict';

    /* ---------- Employee search (client-side, rows already on the page) ---------- */
    var search = document.getElementById('posEmployeeSearch');
    var table  = document.getElementById('posEmployeeTable');
    var empty  = document.getElementById('posEmployeeEmpty');

    if (search && table) {
        search.addEventListener('input', function () {
            var term    = search.value.trim().toLowerCase();
            var rows    = table.querySelectorAll('tbody tr');
            var visible = 0;

            rows.forEach(function (row) {
                var match = term === '' || (row.getAttribute('data-search') || '').indexOf(term) !== -1;
                row.hidden = !match;
                if (match) { visible++; }
            });

            if (empty) { empty.hidden = visible !== 0; }
        });
    }

    /* ---------- Edit Position modal ---------- */
    var modal   = document.getElementById('posEditModal');
    var openBtn = document.getElementById('posEditBtn');
    var form    = document.getElementById('posEditForm');
    var errorEl = document.getElementById('posEditError');
    var saveBtn = document.getElementById('posEditSave');

    if (!modal || !openBtn || !form) { return; }

    function showError(message) {
        errorEl.textContent = message;
        errorEl.hidden = false;
    }

    function openModal() {
        errorEl.hidden = true;
        modal.hidden = false;
        var first = form.querySelector('input[name="position_name"]');
        if (first) { first.focus(); first.select(); }
    }

    function closeModal() {
        modal.hidden = true;
        openBtn.focus();
    }

    openBtn.addEventListener('click', openModal);

    modal.querySelectorAll('[data-close]').forEach(function (el) {
        el.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && !modal.hidden) { closeModal(); }
    });

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        errorEl.hidden = true;

        var name = form.elements['position_name'].value.trim();
        if (name === '') {
            showError('Position name is required.');
            return;
        }

        saveBtn.disabled = true;
        var originalLabel = saveBtn.textContent;
        saveBtn.textContent = 'Saving…';

        fetch(form.getAttribute('data-save-url'), {
            method: 'POST',
            body: new FormData(form),
            credentials: 'same-origin',
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(function (response) {
                return response.json().catch(function () { return null; });
            })
            .then(function (data) {
                if (data && data.success) {
                    window.location.reload();
                    return;
                }
                showError((data && data.message) || 'Could not save the position. Please try again.');
            })
            .catch(function () {
                showError('Network error. Please check your connection and try again.');
            })
            .then(function () {
                saveBtn.disabled = false;
                saveBtn.textContent = originalLabel;
            });
    });
})();
