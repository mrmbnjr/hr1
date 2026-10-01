<?php

use App\Services\Auth;


/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$pageTitle =
    "My Requests";

$pageCSS =
    "my-requests.css";

$pageDescription =
    "Submit and track your employee requests.";


/*
|--------------------------------------------------------------------------
| Authorization
|--------------------------------------------------------------------------
*/

Auth::requireRole([
    'EMP'
]);


/*
|--------------------------------------------------------------------------
| Data Defaults
|--------------------------------------------------------------------------
*/

$employee =
    $employee ?? [];

$requests =
    $requests ?? [];


/*
|--------------------------------------------------------------------------
| Request Types
|--------------------------------------------------------------------------
| Each type carries a short hint (shown in the compose form) and an icon
| (shown in the inbox row and detail modal). Purely presentational — the
| value posted/stored is still just the type string.
*/

$requestTypes = [

    "Certificate of Employment" => [
        "hint" => "An official employment certificate, prepared and issued by HR.",
        "icon" => "fa-certificate",
    ],

    "Document Request" => [
        "hint" => "Copies of employment records or other company-issued documents.",
        "icon" => "fa-file-lines",
    ],

    "Profile Update" => [
        "hint" => "Changes to info you can't edit directly — name, address, contact number.",
        "icon" => "fa-id-card",
    ],

    "Payroll Concern" => [
        "hint" => "An incorrect amount, missing payment, or other payroll issue.",
        "icon" => "fa-money-check-dollar",
    ],

    "Employment Concern" => [
        "hint" => "A general concern about your work status or employment information.",
        "icon" => "fa-briefcase",
    ],

    "Other" => [
        "hint" => "Any legitimate HR request that doesn't fit the categories above.",
        "icon" => "fa-comment-dots",
    ],

];


/*
|--------------------------------------------------------------------------
| Status Metadata
|--------------------------------------------------------------------------
*/

$statusMeta = [

    "Pending" => [
        "label" => "Pending",
        "class" => "badge-gray",
        "state" => "pending",
        "editable" => true,
    ],

    "Approved" => [
        "label" => "Approved",
        "class" => "badge-green",
        "state" => "approved",
        "editable" => false,
    ],

    "Rejected" => [
        "label" => "Rejected",
        "class" => "badge-red",
        "state" => "rejected",
        "editable" => false,
    ],

    "Completed" => [
        "label" => "Completed",
        "class" => "badge-green",
        "state" => "completed",
        "editable" => false,
    ]

];


/*
|--------------------------------------------------------------------------
| Messages
|--------------------------------------------------------------------------
*/

$success =
    $_GET['success'] ?? '';

$error =
    $_GET['error'] ?? '';

?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>

<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>


<div class="main-content">

    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>


    <div class="employee-request-page">

        <!-- ======================================================
             SUCCESS / ERROR MESSAGES
        ======================================================= -->

        <?php if ($success === 'created'): ?>

            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                Your request has been submitted successfully.
            </div>

        <?php endif; ?>


        <?php if ($success === 'updated'): ?>

            <div class="alert alert-success">
                <i class="fa-solid fa-circle-check"></i>
                Your changes have been saved.
            </div>

        <?php endif; ?>


        <?php if ($error !== ''): ?>

            <?php
            $errorMessage = match ($error) {

                'invalid-type' =>
                    'Please select a valid request type.',

                'subject' =>
                    'Please enter a subject.',

                'description' =>
                    'Please enter a description.',

                default =>
                    'Unable to submit your request.'

            };
            ?>

            <div class="alert alert-error">
                <i class="fa-solid fa-circle-exclamation"></i>
                <?= htmlspecialchars($errorMessage) ?>
            </div>

        <?php endif; ?>

        <!-- ======================================================
             FILTER BAR
        ======================================================= -->

        <section class="filter-bar">

            <div class="filter-controls">

                <select id="requestTypeFilter">
                    <option value="All">All Request Types</option>
                    <?php foreach ($requestTypes as $type => $meta): ?>
                        <option value="<?= htmlspecialchars($type) ?>"><?= htmlspecialchars($type) ?></option>
                    <?php endforeach; ?>
                </select>

                <select id="statusFilter">
                    <option value="All">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                    <option value="rejected">Rejected</option>
                    <option value="completed">Completed</option>
                </select>

                <select id="sortFilter">
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="type">Request Type</option>
                    <option value="status">Status</option>
                </select>

            </div>

            <button type="button" id="openRequestForm" class="btn-primary">
                <i class="fa-solid fa-plus"></i>
                New Request
            </button>

        </section>


        <!-- ======================================================
             REQUEST TABLE
        ======================================================= -->

        <section class="table-card">

            <div class="table-scroll">

                <table class="employee-request-table" id="myRequestTable">

                    <thead>

                        <tr>
                            <th>Request</th>
                            <th>Request Type</th>
                            <th>Date Submitted</th>
                            <th>Status</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($requests)): ?>

                            <tr class="empty-row">

                                <td colspan="4">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-inbox"></i>
                                        <p>You have not submitted any requests yet.</p>
                                    </div>
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($requests as $request): ?>

                                <?php

                                $status =
                                    $request['status'] ?? 'Pending';

                                $meta =
                                    $statusMeta[$status] ?? $statusMeta['Pending'];

                                $type =
                                    $request['request_type'] ?? 'Other';

                                $typeMeta =
                                    $requestTypes[$type] ?? ['hint' => '', 'icon' => 'fa-circle-question'];

                                $remarks =
                                    trim($request['hr_remarks'] ?? '');

                                $description =
                                    $request['description'] ?? '';

                                $subject =
                                    $request['subject'] ?? $type;

                                $initial =
                                    strtoupper(
                                        substr(
                                            trim($subject) !== '' ? $subject : $type,
                                            0,
                                            1
                                        )
                                    );

                                ?>

                                <tr
                                    class="request-row"
                                    tabindex="0"
                                    role="button"
                                    data-id="<?= htmlspecialchars($request['id'] ?? '') ?>"
                                    data-type="<?= htmlspecialchars($type) ?>"
                                    data-icon="<?= htmlspecialchars($typeMeta['icon']) ?>"
                                    data-subject="<?= htmlspecialchars($subject) ?>"
                                    data-description="<?= htmlspecialchars($description) ?>"
                                    data-status="<?= htmlspecialchars($meta['state']) ?>"
                                    data-status-label="<?= htmlspecialchars($meta['label']) ?>"
                                    data-badge-class="<?= htmlspecialchars($meta['class']) ?>"
                                    data-editable="<?= $meta['editable'] ? '1' : '0' ?>"
                                    data-remarks="<?= htmlspecialchars($remarks) ?>"
                                    data-date="<?= htmlspecialchars($request['requested_at'] ?? '') ?>"
                                >

                                    <td>
                                        <div class="request-cell">
                                            <div>
                                                <strong><?= htmlspecialchars($subject) ?></strong>
                                                <span class="sub-text"><?= htmlspecialchars(mb_strimwidth($description, 0, 90, '…')) ?></span>
                                            </div>
                                        </div>
                                    </td>

                                    <td>
                                        <button type="button" class="request-type request-view-btn" data-request-id="<?= htmlspecialchars($request['id'] ?? '') ?>">
                                            <?= htmlspecialchars($type) ?>
                                        </button>
                                    </td>

                                    <td>
                                        <?= !empty($request['requested_at']) ? htmlspecialchars($request['requested_at']) : '—' ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= htmlspecialchars($meta['class']) ?>"><?= htmlspecialchars($meta['label']) ?></span>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>

    </div>

    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>

</div>

<!-- ==========================================================
     NEW REQUEST MODAL (compose)
=========================================================== -->

<div id="newRequestModal" class="ar-modal" aria-hidden="true">
    <div class="ar-modal__backdrop" data-close-modal></div>
    <div class="ar-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="newRequestTitle">
        <header class="ar-modal__head">
            <div class="ar-modal__head-text">
                <span class="ar-modal__eyebrow">Submit Request</span>
                <h2 id="newRequestTitle">New Request</h2>
            </div>
            <button type="button" class="ar-modal__close" data-close-modal aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </header>
        <div class="ar-modal__body">
            <form id="newRequestForm" method="POST" action="?page=my-requests-create" class="ar-form">
                <div class="ar-section">
                    <h3><i class="fa-solid fa-pen-to-square"></i> Request Details</h3>
                    <div class="ar-field">
                        <label for="requestType" class="ar-field__label">Request Type</label>
                        <select id="requestType" name="request_type" required>
                            <option value="" hidden selected>Select request type</option>
                            <?php foreach ($requestTypes as $type => $meta): ?>
                                <option value="<?= htmlspecialchars($type) ?>" data-hint="<?= htmlspecialchars($meta['hint']) ?>">
                                    <?= htmlspecialchars($type) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="ar-field__hint" id="requestTypeHint">Choose a type to see what it's for.</p>
                    </div>
                    <div class="ar-field">
                        <label for="requestSubject" class="ar-field__label">Subject</label>
                        <input type="text" id="requestSubject" name="subject" maxlength="150" required placeholder="Enter the subject of your request">
                    </div>
                    <div class="ar-field">
                        <label for="requestDescription" class="ar-field__label">Description</label>
                        <textarea id="requestDescription" name="description" rows="6" maxlength="2000" required placeholder="Explain what you are requesting..."></textarea>
                    </div>
                </div>
            </form>
        </div>
        <footer class="ar-modal__foot">
            <button type="button" class="btn-secondary" data-close-modal>Cancel</button>
            <button type="submit" form="newRequestForm" class="btn-primary" id="submitRequestBtn">
                <i class="fa-solid fa-paper-plane"></i> Submit Request
            </button>
        </div>
    </div>
</div>

<!-- ==========================================================
     REQUEST DETAIL / EDIT MODAL
=========================================================== -->

<div id="requestDetailModal" class="ar-modal" aria-hidden="true">
    <div class="ar-modal__backdrop" data-close-modal></div>
    <div class="ar-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="detailTitle">
        <form method="POST" action="?page=my-requests-update" id="detailEditForm">
            <input type="hidden" name="request_id" id="editRequestId">
            <header class="ar-modal__head">
                <div class="ar-modal__head-text">
                    <span class="ar-modal__eyebrow">Employee Request</span>
                    <h2 id="detailTitle">Request Details</h2>
                </div>
                <button type="button" class="ar-modal__close" data-close-modal aria-label="Close request details">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </header>
            <div class="ar-modal__body">
                <div class="ar-section">
                    <h3><i class="fa-solid fa-circle-info"></i> Request Information</h3>
                    <div class="ar-info-grid">
                        <div class="ar-info">
                            <span class="ar-info__label">Request Type</span>
                            <strong class="ar-info__value" id="detailRequestType">—</strong>
                        </div>
                        <div class="ar-info">
                            <span class="ar-info__label">Date Submitted</span>
                            <strong class="ar-info__value" id="detailRequestDate">—</strong>
                        </div>
                        <div class="ar-info">
                            <span class="ar-info__label">Status</span>
                            <span class="badge badge-gray" id="detailRequestStatus">—</span>
                        </div>
                    </div>
                    <div class="ar-remarks" id="hrRemarksSection" hidden>
                        <h4><i class="fa-solid fa-comment-dots"></i> HR Remarks</h4>
                        <p id="detailHrRemarks">—</p>
                    </div>
                </div>
                <div class="ar-section">
                    <h3><i class="fa-solid fa-heading"></i> Subject</h3>
                    <div class="ar-field">
                        <input type="text" id="editSubject" name="subject" maxlength="150" required>
                    </div>
                </div>
                <div class="ar-section">
                    <h3><i class="fa-solid fa-align-left"></i> Request Details</h3>
                    <div class="ar-field">
                        <textarea id="editDescription" name="description" rows="6" maxlength="2000" required></textarea>
                    </div>
                </div>
            </div>
            <footer class="ar-modal__foot">
                <button type="submit" class="btn-primary" id="detailSaveBtn" hidden>
                    <i class="fa-solid fa-check"></i> Save Changes
                </button>
            </footer>
        </form>
    </div>
</div>


<script>
(function () {

    /* ---------- generic modal open/close ---------- */

    function openModal(modal) {
        if (modal.classList && modal.classList.contains('ar-modal')) {
            modal.classList.add('is-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('modal-open');
            return;
        }

        modal.classList.add('is-open');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
    }

    function closeModal(modal) {
        if (modal.classList && modal.classList.contains('ar-modal')) {
            modal.classList.remove('is-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('modal-open');
            return;
        }

        modal.classList.remove('is-open');
        modal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
    }

    document.querySelectorAll('.modal, .ar-modal').forEach(function (modal) {
        modal.querySelectorAll('[data-close-modal]').forEach(function (el) {
            el.addEventListener('click', function () { closeModal(modal); });
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('.modal, .ar-modal').forEach(function (modal) {
            if (modal.classList && modal.classList.contains('ar-modal')) {
                if (modal.classList.contains('is-open')) closeModal(modal);
                return;
            }

            if (modal.classList.contains('is-open')) closeModal(modal);
        });
    });


    /* ---------- compose modal ---------- */

    const composeModal = document.getElementById('newRequestModal');
    const openComposeBtn = document.getElementById('openRequestForm');
    const typeSelect = document.getElementById('requestType');
    const typeHint = document.getElementById('requestTypeHint');
    const submitBtn = document.getElementById('submitRequestBtn');

    openComposeBtn?.addEventListener('click', function () {
        openModal(composeModal);
        typeSelect.focus();
    });

    typeSelect?.addEventListener('change', function () {
        const selected = typeSelect.selectedOptions[0];
        typeHint.textContent = selected?.dataset.hint || "Choose a type to see what it's for.";
    });

    composeModal?.querySelector('form')?.addEventListener('submit', function () {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Submitting…';
    });


    /* ---------- deep link ---------- */

    const composeParam = new URLSearchParams(window.location.search).get('compose');

    if (composeParam && composeModal) {
        const preselected = Array.from(typeSelect.options).find(function (option) {
            return option.value === composeParam;
        });

        if (preselected) {
            typeSelect.value = preselected.value;
            typeSelect.dispatchEvent(new Event('change'));
        }

        openModal(composeModal);
        typeSelect.focus();

        if (window.history.replaceState) {
            const clean = new URL(window.location.href);
            clean.searchParams.delete('compose');
            window.history.replaceState(null, '', clean.pathname + clean.search);
        }
    }


    /* ---------- detail / edit modal ---------- */

    const detailModal = document.getElementById('requestDetailModal');
    const detailTitle = document.getElementById('detailTitle');
    const detailRequestType = document.getElementById('detailRequestType');
    const detailRequestDate = document.getElementById('detailRequestDate');
    const detailRequestStatus = document.getElementById('detailRequestStatus');
    const detailHrRemarks = document.getElementById('detailHrRemarks');
    const detailEditForm = document.getElementById('detailEditForm');
    const editRequestId = document.getElementById('editRequestId');
    const editSubject = document.getElementById('editSubject');
    const editDescription = document.getElementById('editDescription');
    const detailSaveBtn = document.getElementById('detailSaveBtn');

    const typeFilter = document.getElementById('requestTypeFilter');
    const statusFilter = document.getElementById('statusFilter');
    const sortFilter = document.getElementById('sortFilter');
    const rows = Array.from(document.querySelectorAll('.request-row'));

    function setStatusBadge(element, status, cssClass) {
        const className = cssClass || 'badge-gray';
        element.className = 'badge ' + className;
        element.textContent = status || 'Pending';
    }

    rows.forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (event.target.closest('button, input, select, textarea')) {
                return;
            }

            const d = row.dataset;

            detailTitle.textContent = d.subject || d.type || 'Request Details';
            detailRequestType.textContent = d.type || '—';
            detailRequestDate.textContent = d.date || '—';
            setStatusBadge(detailRequestStatus, d.statusLabel || 'Pending', d.badgeClass || 'badge-gray');

            detailHrRemarks.textContent = d.remarks ? d.remarks : '—';

            editRequestId.value = d.id;
            editSubject.value = d.subject || '';
            editDescription.value = d.description || '';

            const isPending = (d.status || 'pending') === 'pending';
            const hasRemarks = !!(d.remarks && d.remarks.trim());

            document.getElementById('hrRemarksSection').hidden = isPending || !hasRemarks;
            detailHrRemarks.textContent = hasRemarks ? d.remarks : '';

            editSubject.disabled = !isPending;
            editDescription.disabled = !isPending;

            // Force hide save button if not pending
            if (!isPending) {
                detailSaveBtn.style.display = 'none';
            } else {
                detailSaveBtn.style.display = '';
                detailSaveBtn.hidden = false;
            }

            if (!isPending) {
                editSubject.setAttribute('aria-readonly', 'true');
                editDescription.setAttribute('aria-readonly', 'true');
            } else {
                editSubject.removeAttribute('aria-readonly');
                editDescription.removeAttribute('aria-readonly');
            }

            openModal(detailModal);
        });
    });

    detailEditForm?.addEventListener('submit', function () {
        detailSaveBtn.disabled = true;
        detailSaveBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
    });

    /* ---------- filters + sort ---------- */

    function sortRows(list, mode) {
        const tableBody = document.querySelector('#myRequestTable tbody');
        if (!tableBody) return;

        const sorted = [...list].sort(function (a, b) {
            const aDate = new Date(a.dataset.date || 0).getTime();
            const bDate = new Date(b.dataset.date || 0).getTime();

            switch (mode) {
                case 'oldest':
                    return aDate - bDate;
                case 'type':
                    return (a.dataset.type || '').localeCompare(b.dataset.type || '');
                case 'status':
                    return (a.dataset.status || '').localeCompare(b.dataset.status || '');
                case 'newest':
                default:
                    return bDate - aDate;
            }
        });

        sorted.forEach(row => tableBody.appendChild(row));
    }

    function applyTableFilters() {
        const activeType = typeFilter?.value || 'All';
        const activeStatus = statusFilter?.value || 'All';
        const sortMode = sortFilter?.value || 'newest';

        rows.forEach(function (row) {
            const matchesType = activeType === 'All' || row.dataset.type === activeType;
            const matchesStatus = activeStatus === 'All' || row.dataset.status === activeStatus;
            const show = matchesType && matchesStatus;
            row.hidden = !show;
        });

        sortRows(rows, sortMode);
    }

    typeFilter?.addEventListener('change', applyTableFilters);
    statusFilter?.addEventListener('change', applyTableFilters);
    sortFilter?.addEventListener('change', applyTableFilters);

    applyTableFilters();

})();
</script>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>
