/*
  assigned-requests.js
  Behaviour for resources/views/assigned-requests/index.php

  Three jobs only:
    1. Filter and sort the request table (same pattern as My Requests).
    2. Open the detail modal and show the fields that request actually
       needs (response / document / confirmation).
    3. Reflect the chosen file back to the employee before submitting.

  No validation is repeated here — the server owns that.
*/

(function () {
    'use strict';

    var page = document.querySelector('.assigned-requests-page');

    if (!page) {
        return;
    }

    function $(selector, context) {
        return (context || document).querySelector(selector);
    }

    function $$(selector, context) {
        return Array.prototype.slice.call(
            (context || document).querySelectorAll(selector)
        );
    }

    /* Human-readable size for the file the employee just picked. */
    function formatBytes(bytes) {
        if (!bytes) {
            return '0 B';
        }

        var units = ['B', 'KB', 'MB', 'GB'];
        var index = 0;
        var value = bytes;

        while (value >= 1024 && index < units.length - 1) {
            value /= 1024;
            index++;
        }

        return (index === 0 ? value : value.toFixed(1)) + ' ' + units[index];
    }


    /* ==========================================================
       TABLE FILTERS + SORT
    ========================================================== */

    var typeFilter = $('#assignedTypeFilter');
    var statusFilter = $('#assignedStatusFilter');
    var sortFilter = $('#assignedSortFilter');
    var table = $('#assignedRequestTable');
    var filteredEmpty = $('#assignedFilteredEmpty');

    var rows = table
        ? $$('tbody tr.request-row', table)
        : [];

    var PRIORITY_ORDER = { Urgent: 0, High: 1, Normal: 2 };

    /* '2026-08-10 09:00:00' -> milliseconds, or null when unparseable. */
    function toTime(value) {
        if (!value) {
            return null;
        }

        var time = Date.parse(String(value).replace(' ', 'T'));

        return isNaN(time) ? null : time;
    }

    function compare(a, b, mode) {
        var aCreated = toTime(a.dataset.arCreated);
        var bCreated = toTime(b.dataset.arCreated);

        switch (mode) {
            case 'oldest':
                return (aCreated || 0) - (bCreated || 0);

            case 'due':
                var aDue = toTime(a.dataset.arDue);
                var bDue = toTime(b.dataset.arDue);

                if (aDue === null && bDue === null) { return 0; }
                if (aDue === null) { return 1; }
                if (bDue === null) { return -1; }

                return aDue - bDue;

            case 'priority':
                var aRank = PRIORITY_ORDER[a.dataset.arPriority];
                var bRank = PRIORITY_ORDER[b.dataset.arPriority];

                return (aRank === undefined ? 9 : aRank)
                    - (bRank === undefined ? 9 : bRank);

            case 'type':
                return (a.dataset.arType || '')
                    .localeCompare(b.dataset.arType || '');

            case 'newest':
            default:
                return (bCreated || 0) - (aCreated || 0);
        }
    }

    function sortRows(mode) {
        if (!table || !rows.length) {
            return;
        }

        var body = table.tBodies[0];

        if (!body) {
            return;
        }

        rows.slice()
            .sort(function (a, b) {
                return compare(a, b, mode);
            })
            .forEach(function (row) {
                body.appendChild(row);
            });

        /* The "nothing matches" row always belongs at the end. */
        if (filteredEmpty) {
            body.appendChild(filteredEmpty);
        }
    }

    function applyFilters() {
        var type = typeFilter ? typeFilter.value : 'All';
        var status = statusFilter ? statusFilter.value : 'All';
        var mode = sortFilter ? sortFilter.value : 'newest';

        var visible = 0;

        rows.forEach(function (row) {
            var show = (type === 'All' || row.dataset.arType === type)
                && (status === 'All' || row.dataset.arStatus === status);

            row.hidden = !show;

            if (show) {
                visible++;
            }
        });

        if (filteredEmpty) {
            filteredEmpty.hidden = !(rows.length > 0 && visible === 0);
        }

        sortRows(mode);
    }

    [typeFilter, statusFilter, sortFilter].forEach(function (control) {
        if (control) {
            control.addEventListener('change', applyFilters);
        }
    });

    applyFilters();


    /* ==========================================================
       DETAIL MODAL
    ========================================================== */

    var modal = $('#arModal');

    if (!modal) {
        return;
    }

    var el = {
        title: $('#arModalTitle'),
        type: $('#arModalType'),
        priority: $('#arModalPriority'),
        infoType: $('#arInfoType'),
        infoAssignedBy: $('#arInfoAssignedBy'),
        infoAssigned: $('#arInfoAssigned'),
        infoDue: $('#arInfoDue'),
        infoPriority: $('#arInfoPriority'),
        infoStatus: $('#arInfoStatus'),
        instruction: $('#arInstruction'),
        form: $('#arForm'),
        requestId: $('#arRequestId'),
        response: $('#arResponse'),
        responseHint: $('#arResponseHint'),
        fileInput: $('#arFile'),
        fileChip: $('#arFileChip'),
        fileName: $('#arFileName'),
        fileMeta: $('#arFileMeta'),
        fileRemove: $('#arFileRemove'),
        submittedText: $('#arSubmittedText'),
        submittedFile: $('#arSubmittedFile'),
        submittedFileName: $('#arSubmittedFileName'),
        submittedFileSize: $('#arSubmittedFileSize'),
        submittedAt: $('#arSubmittedAt'),
        remarks: $('#arRemarks'),
        timeline: $('#arTimeline'),
        submitBtn: $('#arSubmitBtn')
    };

    var blocks = {
        response: modal.querySelector('[data-ar-block="response"]'),
        document: modal.querySelector('[data-ar-block="document"]'),
        confirmation: modal.querySelector('[data-ar-block="confirmation"]'),
        submitted: modal.querySelector('[data-ar-block="submitted"]'),
        remarks: modal.querySelector('[data-ar-block="remarks"]')
    };

    var lastFocused = null;
    var submitting = false;

    function openModal() {
        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('ar-modal-open');
    }

    function closeModal() {
        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('ar-modal-open');

        if (lastFocused && typeof lastFocused.focus === 'function') {
            lastFocused.focus();
        }
    }

    $$('[data-ar-close]', modal).forEach(function (trigger) {
        trigger.addEventListener('click', closeModal);
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });


    /* ---------- file picker ---------- */

    function resetFileChip() {
        el.fileChip.hidden = true;
        el.fileName.textContent = '—';
        el.fileMeta.textContent = '—';

        if (el.fileInput) {
            el.fileInput.value = '';
        }
    }

    if (el.fileInput) {
        el.fileInput.addEventListener('change', function () {
            var file = el.fileInput.files && el.fileInput.files[0];

            if (!file) {
                resetFileChip();
                return;
            }

            el.fileName.textContent = file.name;
            el.fileMeta.textContent =
                (file.type || 'Document') + ' · ' + formatBytes(file.size);
            el.fileChip.hidden = false;
        });
    }

    if (el.fileRemove) {
        el.fileRemove.addEventListener('click', resetFileChip);
    }


    /* ---------- activity timeline ---------- */

    function renderTimeline(activities) {
        el.timeline.innerHTML = '';

        if (!activities || !activities.length) {
            var empty = document.createElement('li');
            empty.className = 'ar-timeline__empty';
            empty.textContent = 'No activity recorded yet.';
            el.timeline.appendChild(empty);
            return;
        }

        activities.forEach(function (activity) {
            var item = document.createElement('li');
            item.className = 'ar-timeline__item';

            var dot = document.createElement('span');
            dot.className = 'ar-timeline__dot';

            var icon = document.createElement('i');
            icon.className = 'fa-solid ' + (activity.icon || 'fa-circle');
            dot.appendChild(icon);

            var body = document.createElement('div');
            body.className = 'ar-timeline__body';

            var time = document.createElement('span');
            time.className = 'ar-timeline__time';
            time.textContent = activity.date + ' — ' + activity.time;

            var text = document.createElement('p');
            text.className = 'ar-timeline__text';
            text.textContent = activity.description;

            body.appendChild(time);
            body.appendChild(text);

            item.appendChild(dot);
            item.appendChild(body);
            el.timeline.appendChild(item);
        });
    }


    /* ---------- populate + open ---------- */

    function openRequest(data) {
        var isFinalized = data.status === 'Approved';

        el.title.textContent = data.title;
        el.type.textContent = data.type;
        el.priority.className = 'ar-priority ar-priority--' + data.priorityTone;
        el.priority.textContent = data.priority;

        el.infoType.textContent = data.type;
        el.infoAssignedBy.textContent = data.assignedByRole
            ? data.assignedBy + ' · ' + data.assignedByRole
            : data.assignedBy;
        el.infoAssigned.textContent = data.assignedAt;
        el.infoDue.textContent = data.hasDue ? data.due : 'No due date';
        el.infoPriority.textContent = data.priority;
        el.infoStatus.className = 'badge ' + data.statusClass;
        el.infoStatus.textContent = data.statusLabel;

        el.instruction.textContent = data.description;
        el.requestId.value = data.id;


        /* Only the fields this request actually needs are rendered. */

        var showResponse = false;
        var showDocument = false;
        var showConfirm = false;

        if (!isFinalized) {
            if (data.actionType === 'document') {
                showDocument = true;
                showResponse = true;
            } else if (data.actionType === 'response') {
                showResponse = true;
            } else {
                showConfirm = true;
            }
        }

        blocks.response.hidden = !showResponse;
        blocks.document.hidden = !showDocument;
        blocks.confirmation.hidden = !showConfirm;

        if (showResponse) {
            el.response.value = data.response || '';
            el.responseHint.textContent = showDocument
                ? 'Optional remarks about your submission.'
                : 'Please provide the requested information.';
        }

        if (showDocument) {
            resetFileChip();
        }


        /* Read-only submission (shown once something has been sent). */

        var hasText = !!(data.response && data.response.trim());
        var hasFile = !!(data.fileOriginal);
        var hasSubmission = isFinalized || hasText || hasFile;

        blocks.submitted.hidden = !hasSubmission;

        if (hasSubmission) {
            el.submittedText.hidden = !hasText;
            el.submittedText.textContent = hasText ? data.response : '';

            el.submittedFile.hidden = !hasFile;

            if (hasFile) {
                el.submittedFile.href = data.fileUrl || '#';
                el.submittedFileName.textContent = data.fileOriginal;
                el.submittedFileSize.textContent =
                    data.fileSize && data.fileSize !== '—'
                        ? '· ' + data.fileSize
                        : '';
            }

            var meta = [];

            if (data.submittedAt && data.submittedAt !== '—') {
                meta.push('Submitted ' + data.submittedAt);
            }

            el.submittedAt.textContent = meta.join(' · ');
            el.submittedAt.hidden = meta.length === 0;
        }


        /* HR remarks (after review). */

        var hasRemarks = !!(data.reviewRemarks && data.reviewRemarks.trim());

        blocks.remarks.hidden = !hasRemarks;
        el.remarks.textContent = hasRemarks ? data.reviewRemarks : '';


        /* Footer action. */

        submitting = false;
        el.submitBtn.disabled = false;
        el.submitBtn.hidden = isFinalized;

        if (!isFinalized) {
            el.submitBtn.innerHTML = data.actionType === 'confirmation'
                ? '<i class="fa-solid fa-circle-check"></i> Confirm Completion'
                : '<i class="fa-solid fa-paper-plane"></i> Submit';
        }

        renderTimeline(data.activities);

        openModal();

        var closeBtn = $('.ar-modal__close', modal);

        if (closeBtn) {
            closeBtn.focus();
        }
    }


    /* ---------- triggers ---------- */

    function openFromRow(row) {
        var raw = row.dataset.arRequest;

        if (!raw) {
            return;
        }

        var data;

        try {
            data = JSON.parse(raw);
        } catch (error) {
            return;
        }

        lastFocused = row;
        openRequest(data);
    }

    rows.forEach(function (row) {
        row.addEventListener('click', function (event) {
            /* Let the Request Type button handle its own click. */
            if (event.target.closest('button, a, input, select, textarea')) {
                return;
            }

            openFromRow(row);
        });

        row.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                openFromRow(row);
            }
        });
    });

    /* The Request Type cell is a button, exactly like My Requests. */
    $$('.request-view-btn', table || document).forEach(function (button) {
        button.addEventListener('click', function (event) {
            event.stopPropagation();

            var row = button.closest('tr.request-row');

            if (row) {
                openFromRow(row);
            }
        });
    });


    /* ---------- submit ---------- */

    if (el.form) {
        el.form.addEventListener('submit', function (event) {
            if (submitting) {
                event.preventDefault();
                return;
            }

            submitting = true;
            el.submitBtn.disabled = true;
            el.submitBtn.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> Submitting…';
        });
    }

})();
