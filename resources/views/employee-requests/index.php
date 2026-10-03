<?php

$pageTitle =
    "Employee Requests";

$pageCSS =
    "employee-requests.css";

$pageJS =
    "employee-requests.js";

$pageDescription =
    "Review and manage employee requests and password reset requests.";


if (!isset($_SESSION['user_id'])) {

    header(
        "Location: " . url("?page=login")
    );

    exit;
}


$requests =
    $requests ?? [];


/*
|--------------------------------------------------------------------------
| Employee request statuses
|--------------------------------------------------------------------------
*/

$statusMeta = [

    "Pending" => [
        "label" => "Pending",
        "class" => "badge-gray"
    ],

    "Approved" => [
        "label" => "Approved",
        "class" => "badge-green"
    ],

    "Rejected" => [
        "label" => "Rejected",
        "class" => "badge-red"
    ],

    "Completed" => [
        "label" => "Completed",
        "class" => "badge-green"
    ]

];


/*
|--------------------------------------------------------------------------
| Password reset requests ("Contact HR")
|--------------------------------------------------------------------------
|
| Supplied by EmployeeRequestsController for ADMIN and HR only. Managers
| see the combined queue without the password reset rows.
|
*/

$canViewPasswordResets =
    $canViewPasswordResets ?? false;

$passwordResetRequests =
    $passwordResetRequests ?? [];

$passwordResetPendingCount =
    $passwordResetPendingCount ?? 0;


$resetStatusMeta = [

    "Pending" => [
        "label" => "Pending",
        "class" => "badge-gray"
    ],

    "Resolved" => [
        "label" => "Resolved",
        "class" => "badge-green"
    ]

];


$formatRelativeTime = static function (?string $createdAt): string {
    if (empty($createdAt)) {
        return 'Time unavailable';
    }

    try {
        $submittedAt = new DateTimeImmutable($createdAt);
    } catch (Exception $exception) {
        return 'Time unavailable';
    }

    $elapsedSeconds = max(0, time() - $submittedAt->getTimestamp());

    if ($elapsedSeconds < 60) {
        return 'Just now';
    }

    $units = [
        86400 => 'day',
        3600  => 'hour',
        60    => 'minute',
    ];

    foreach ($units as $secondsPerUnit => $unit) {
        if ($elapsedSeconds >= $secondsPerUnit) {
            $count = intdiv($elapsedSeconds, $secondsPerUnit);
            return $count . ' ' . $unit . ($count === 1 ? '' : 's') . ' ago';
        }
    }

    return 'Just now';
};


/*
|--------------------------------------------------------------------------
| Summary counts (stat cards)
|--------------------------------------------------------------------------
|
| Employee requests and password reset requests share the "Pending" bucket
| so the queue reads as a single workload.
|
*/

$summary = [
    "total"     => count($requests) + count($passwordResetRequests),
    "pending"   => 0,
    "approved"  => 0,
    "completed" => 0,
    "rejected"  => 0,
    "resets"    => count($passwordResetRequests),
];

$employeeStatusKeys = [
    "Pending"   => "pending",
    "Approved"  => "approved",
    "Completed" => "completed",
    "Rejected"  => "rejected",
];

foreach ($requests as $request) {

    $status =
        $request["status"] ?? "Pending";

    if (isset($employeeStatusKeys[$status])) {
        $summary[$employeeStatusKeys[$status]]++;
    }
}

foreach ($passwordResetRequests as $reset) {

    if (($reset["status"] ?? "Pending") === "Pending") {
        $summary["pending"]++;
    }
}


$summaryCards = [

    [
        "filterKind"  => "all",
        "filterValue" => "All",
        "icon"        => "fa-inbox",
        "label"       => "Total Requests",
        "value"       => $summary["total"],
        "note"        => "All employee & reset requests",
    ],

    [
        "filterKind"  => "status",
        "filterValue" => "Pending",
        "icon"        => "fa-clock",
        "label"       => "Pending",
        "value"       => $summary["pending"],
        "note"        => "Awaiting HR review",
    ],

    [
        "filterKind"  => "status",
        "filterValue" => "Approved",
        "icon"        => "fa-circle-check",
        "label"       => "Approved",
        "value"       => $summary["approved"],
        "note"        => "Approved by HR",
    ],

    [
        "filterKind"  => "status",
        "filterValue" => "Completed",
        "icon"        => "fa-flag-checkered",
        "label"       => "Completed",
        "value"       => $summary["completed"],
        "note"        => "Marked as completed",
    ],

    [
        "filterKind"  => "status",
        "filterValue" => "Rejected",
        "icon"        => "fa-circle-xmark",
        "label"       => "Rejected",
        "value"       => $summary["rejected"],
        "note"        => "Declined by HR",
    ],

];

if ($canViewPasswordResets) {

    $summaryCards[] = [
        "filterKind"  => "category",
        "filterValue" => "reset",
        "icon"        => "fa-key",
        "label"       => "Password Resets",
        "value"       => $summary["resets"],
        "note"        => "Account recovery requests",
    ];
}


$hasAnyRows =
    !empty($requests) ||
    !empty($passwordResetRequests);

?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>

<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>


<div class="main-content">

    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>


    <div class="employee-request-page">

        <!-- ======================================================
            SUMMARY CARDS
        ======================================================= -->

        <section class="summary-grid" aria-label="Request summary">

            <?php foreach ($summaryCards as $card): ?>

                <button
                    type="button"
                    class="stat-card summary-card"
                    data-filter-kind="<?= htmlspecialchars(
                        $card['filterKind']
                    ) ?>"
                    data-filter-value="<?= htmlspecialchars(
                        $card['filterValue']
                    ) ?>"
                >

                    <div class="stat-icon">

                        <i class="fa-solid <?= htmlspecialchars(
                            $card['icon']
                        ) ?>"></i>

                    </div>


                    <div class="stat-content">

                        <span><?= htmlspecialchars(
                            $card['label']
                        ) ?></span>

                        <strong><?= (int) $card['value'] ?></strong>

                        <small><?= htmlspecialchars(
                            $card['note']
                        ) ?></small>

                    </div>

                </button>

            <?php endforeach; ?>

        </section>


        <!-- ======================================================
            FILTER BAR
        ======================================================= -->

        <section class="filter-bar">

            <div class="filter-search">

                <i class="fa-solid fa-magnifying-glass"></i>

                <input
                    type="text"
                    id="requestSearch"
                    placeholder="Search by name..."
                    autocomplete="off"
                >

            </div>


            <?php if ($canViewPasswordResets): ?>

                <select id="categoryFilter">

                    <option value="All">
                        All Requests
                    </option>

                    <option value="employee">
                        Employee Requests
                    </option>

                    <option value="reset">
                        Password Resets
                    </option>

                </select>

            <?php endif; ?>


            <select id="statusFilter">

                <option value="All">
                    All Status
                </option>

                <option value="Pending">
                    Pending
                </option>

                <option value="Approved">
                    Approved
                </option>

                <option value="Rejected">
                    Rejected
                </option>

                <option value="Completed">
                    Completed
                </option>

                <?php if ($canViewPasswordResets): ?>

                    <option value="Resolved">
                        Resolved
                    </option>

                <?php endif; ?>

            </select>


            <select id="sortFilter">

                <option value="newest">
                    Newest
                </option>

                <option value="oldest">
                    Oldest
                </option>

                <option value="name-az">
                    Name (A-Z)
                </option>

                <option value="name-za">
                    Name (Z-A)
                </option>

                <option value="status">
                    Status
                </option>

            </select>

        </section>


        <!-- ======================================================
            UNIFIED REQUEST TABLE
        ======================================================= -->

        <section class="table-card">

            <div class="table-scroll">

                <table
                    class="employee-request-table"
                    id="employeeRequestTable"
                >

                    <thead>

                        <tr>

                            <th>
                                Requester
                            </th>

                            <th>
                                Type
                            </th>

                            <th>
                                Details
                            </th>

                            <th>
                                Status
                            </th>

                            <th></th>

                        </tr>

                    </thead>


                    <tbody>

                        <?php if (!$hasAnyRows): ?>

                            <tr>

                                <td colspan="5">

                                    <div class="empty-state">

                                        <i class="fa-solid fa-inbox"></i>

                                        <p>
                                            No employee requests found.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($requests as $request): ?>

                                <?php

                                $status =
                                    $request['status']
                                    ?? 'Pending';


                                $meta =
                                    $statusMeta[$status]
                                    ?? $statusMeta['Pending'];


                                $fullName =
                                    trim(
                                        $request['fullname']
                                        ?? ''
                                    );


                                if ($fullName === '') {

                                    $fullName =
                                        'Unknown Employee';
                                }


                                $initial =
                                    strtoupper(
                                        substr(
                                            $fullName,
                                            0,
                                            1
                                        )
                                    );

                                ?>

                                <tr
                                    class="request-row"
                                    data-kind="employee"
                                    tabindex="0"
                                    role="button"

                                    data-id="<?= htmlspecialchars(
                                        $request['request_id']
                                        ?? ''
                                    ) ?>"

                                    data-department="<?= htmlspecialchars(
                                        $request['department_name']
                                        ?? ''
                                    ) ?>"

                                    data-request-type="<?= htmlspecialchars(
                                        $request['request_type']
                                        ?? ''
                                    ) ?>"

                                    data-status="<?= htmlspecialchars(
                                        $status
                                    ) ?>"

                                    data-name="<?= htmlspecialchars(
                                        $fullName
                                    ) ?>"

                                    data-employee-number="<?= htmlspecialchars(
                                        $request['employee_number']
                                        ?? ''
                                    ) ?>"

                                    data-subject="<?= htmlspecialchars(
                                        $request['subject']
                                        ?? ''
                                    ) ?>"

                                    data-description="<?= htmlspecialchars(
                                        $request['description']
                                        ?? ''
                                    ) ?>"

                                    data-hr-remarks="<?= htmlspecialchars(
                                        $request['hr_remarks']
                                        ?? ''
                                    ) ?>"

                                    data-date="<?= htmlspecialchars(
                                        $request['requested_at']
                                        ?? ''
                                    ) ?>"
                                >


                                    <!-- Requester -->

                                    <td>

                                        <div class="request-cell">

                                            <div class="avatar-circle">

                                                <?= htmlspecialchars(
                                                    $initial
                                                ) ?>

                                            </div>


                                            <div>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $fullName
                                                    ) ?>

                                                </strong>


                                                <span class="sub-text">

                                                    <?= htmlspecialchars(
                                                        $request[
                                                            'employee_number'
                                                        ] ?? ''
                                                    ) ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- Type -->

                                    <td>

                                        <span class="request-kind">

                                            <i class="fa-solid fa-file-lines"></i>

                                            Employee Request

                                        </span>

                                    </td>


                                    <!-- Details -->

                                    <td>

                                        <button
                                            type="button"
                                            class="request-type request-view-btn"
                                            data-request-id="<?= htmlspecialchars(
                                                $request[
                                                    'request_id'
                                                ] ?? ''
                                            ) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $request[
                                                    'request_type'
                                                ] ?? '—'
                                            ) ?>

                                        </button>


                                        <span class="sub-text">

                                            <?= htmlspecialchars(
                                                $request[
                                                    'department_name'
                                                ] ?? '—'
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- Status -->

                                    <td>

                                        <span
                                            class="badge <?= htmlspecialchars(
                                                $meta['class']
                                            ) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $meta['label']
                                            ) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <span class="sub-text application-time">
                                            <?= htmlspecialchars(
                                                $formatRelativeTime($request['requested_at'] ?? null)
                                            ) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>


                            <?php foreach ($passwordResetRequests as $reset): ?>

                                <?php

                                $resetStatus =
                                    $reset['status']
                                    ?? 'Pending';


                                $resetMeta =
                                    $resetStatusMeta[$resetStatus]
                                    ?? $resetStatusMeta['Pending'];


                                $resetName =
                                    trim(
                                        (string) (
                                            $reset['full_name']
                                            ?? ''
                                        )
                                    );


                                if ($resetName === '') {

                                    $resetName =
                                        'Unknown Requester';
                                }


                                $resetInitial =
                                    strtoupper(
                                        substr(
                                            $resetName,
                                            0,
                                            1
                                        )
                                    );


                                $resetMessage =
                                    trim(
                                        (string) (
                                            $reset['message']
                                            ?? ''
                                        )
                                    );

                                ?>

                                <tr
                                    class="request-row reset-row"
                                    data-kind="reset"
                                    tabindex="0"
                                    role="button"

                                    data-id="<?= htmlspecialchars(
                                        $reset['request_id'] ?? ''
                                    ) ?>"

                                    data-name="<?= htmlspecialchars(
                                        $resetName
                                    ) ?>"

                                    data-identifier="<?= htmlspecialchars(
                                        $reset['identifier'] ?? ''
                                    ) ?>"

                                    data-contact="<?= htmlspecialchars(
                                        $reset['contact'] ?? ''
                                    ) ?>"

                                    data-message="<?= htmlspecialchars(
                                        $reset['message'] ?? ''
                                    ) ?>"

                                    data-status="<?= htmlspecialchars(
                                        $resetStatus
                                    ) ?>"

                                    data-created="<?= htmlspecialchars(
                                        $reset['created_at'] ?? ''
                                    ) ?>"

                                    data-date="<?= htmlspecialchars(
                                        $reset['created_at'] ?? ''
                                    ) ?>"

                                    data-resolved-at="<?= htmlspecialchars(
                                        $reset['resolved_at'] ?? ''
                                    ) ?>"

                                    data-resolved-by="<?= htmlspecialchars(
                                        $reset['resolved_by_username']
                                        ?? ''
                                    ) ?>"
                                >


                                    <!-- Requester -->

                                    <td>

                                        <div class="request-cell">

                                            <div class="avatar-circle">

                                                <?= htmlspecialchars(
                                                    $resetInitial
                                                ) ?>

                                            </div>


                                            <div>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $resetName
                                                    ) ?>

                                                </strong>


                                                <span class="sub-text">

                                                    <?= htmlspecialchars(
                                                        $reset['identifier']
                                                        ?? ''
                                                    ) ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- Type -->

                                    <td>

                                        <span class="request-kind request-kind--reset">

                                            <i class="fa-solid fa-key"></i>

                                            Password Reset

                                        </span>

                                    </td>


                                    <!-- Details -->

                                    <td>

                                        <strong class="request-detail-value">

                                            <?= htmlspecialchars(
                                                $reset['contact']
                                                ?? '—'
                                            ) ?>

                                        </strong>


                                        <span class="sub-text">

                                            <?= htmlspecialchars(
                                                $resetMessage !== ''
                                                    ? $resetMessage
                                                    : 'Account recovery request'
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- Status -->

                                    <td>

                                        <span
                                            class="badge <?= htmlspecialchars(
                                                $resetMeta['class']
                                            ) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $resetMeta['label']
                                            ) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <span class="sub-text application-time">
                                            <?= htmlspecialchars(
                                                $formatRelativeTime($reset['created_at'] ?? null)
                                            ) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>


                        <!-- Toggled by JS when the active filters hide every row -->

                        <tr class="filter-empty" id="filterEmpty" hidden>

                            <td colspan="5">

                                <div class="empty-state">

                                    <i class="fa-solid fa-filter-circle-xmark"></i>

                                    <p>
                                        No requests match the current filters.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>


            <!-- ======================================================
                TABLE FOOTER
            ======================================================= -->

            <div class="table-footer">

                <span
                    class="results-count"
                    id="resultsCount"
                ></span>


                <div
                    class="pagination"
                    id="pagination"
                >

                    <button
                        class="page-btn"
                        id="prevPage"
                        type="button"
                        aria-label="Previous page"
                    >

                        <i class="fa-solid fa-chevron-left"></i>

                    </button>


                    <div
                        class="page-numbers"
                        id="pageNumbers"
                    ></div>


                    <button
                        class="page-btn"
                        id="nextPage"
                        type="button"
                        aria-label="Next page"
                    >

                        <i class="fa-solid fa-chevron-right"></i>

                    </button>

                </div>

            </div>

        </section>

    </div>


    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>


    <!-- ==========================================================
        EMPLOYEE REQUEST MODAL
    =========================================================== -->

    <div
        class="request-modal"
        id="requestModal"
        aria-hidden="true"
    >

        <div class="request-modal-backdrop"></div>


        <div
            class="request-modal-content"
            role="dialog"
            aria-modal="true"
            aria-labelledby="requestModalTitle"
        >


            <!-- ==================================================
                HEADER
            =================================================== -->

            <div class="request-modal-header">

                <div>

                    <span class="modal-eyebrow">
                        Employee Request
                    </span>


                    <h2 id="requestModalTitle">
                        Request Details
                    </h2>

                </div>


                <button
                    type="button"
                    class="request-modal-close"
                    id="requestModalClose"
                    aria-label="Close request details"
                >

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>


            <!-- ==================================================
                BODY
            =================================================== -->

            <div class="request-modal-body">


                <!-- ==================================================
                    STEP 1
                =================================================== -->

                <div
                    class="request-modal-step"
                    id="requestStepOne"
                >


                    <!-- Employee -->

                    <div class="request-detail-section">

                        <h3>

                            <i class="fa-solid fa-user"></i>

                            Employee Information

                        </h3>


                        <div class="request-detail-grid">

                            <div class="request-detail-item">

                                <span class="detail-label">
                                    Employee
                                </span>

                                <strong id="modalEmployeeName">
                                    —
                                </strong>

                            </div>


                            <div class="request-detail-item">

                                <span class="detail-label">
                                    Employee Number
                                </span>

                                <strong id="modalEmployeeNumber">
                                    —
                                </strong>

                            </div>


                            <div class="request-detail-item">

                                <span class="detail-label">
                                    Department
                                </span>

                                <strong id="modalDepartment">
                                    —
                                </strong>

                            </div>

                        </div>

                    </div>


                    <!-- Request -->

                    <div class="request-detail-section">

                        <h3>

                            <i class="fa-solid fa-file-lines"></i>

                            Request Information

                        </h3>


                        <div class="request-detail-grid">

                            <div class="request-detail-item">

                                <span class="detail-label">
                                    Request Type
                                </span>

                                <strong id="modalRequestType">
                                    —
                                </strong>

                            </div>


                            <div class="request-detail-item">

                                <span class="detail-label">
                                    Date Submitted
                                </span>

                                <strong id="modalRequestDate">
                                    —
                                </strong>

                            </div>


                            <div class="request-detail-item">

                                <span class="detail-label">
                                    Status
                                </span>

                                <span id="modalRequestStatus">
                                    —
                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- Subject -->

                    <div class="request-detail-section">

                        <h3>

                            <i class="fa-solid fa-heading"></i>

                            Subject

                        </h3>


                        <div
                            class="request-detail-message"
                            id="modalRequestSubject"
                        >
                            —
                        </div>

                    </div>


                    <!-- Description -->

                    <div class="request-detail-section">

                        <h3>

                            <i class="fa-solid fa-align-left"></i>

                            Request Details

                        </h3>


                        <div
                            class="request-detail-message"
                            id="modalRequestDescription"
                        >
                            —
                        </div>

                    </div>

                </div>


                <!-- ==================================================
                    STEP 2
                =================================================== -->

                <div
                    class="request-modal-step"
                    id="requestStepTwo"
                    hidden
                >


                    <!-- Status -->

                    <div class="request-detail-section">

                        <h3>

                            <i class="fa-solid fa-list-check"></i>

                            Request Status

                        </h3>


                        <div class="request-status-list">


                            <button
                                type="button"
                                class="request-status-item"
                                data-status="Pending"
                            >

                                <span class="status-dot"></span>

                                <div>

                                    <strong>
                                        Pending
                                    </strong>

                                    <span>
                                        Request is waiting for HR review.
                                    </span>

                                </div>

                            </button>


                            <button
                                type="button"
                                class="request-status-item"
                                data-status="Approved"
                            >

                                <span class="status-dot"></span>

                                <div>

                                    <strong>
                                        Approved
                                    </strong>

                                    <span>
                                        Request has been approved by HR.
                                    </span>

                                </div>

                            </button>


                            <button
                                type="button"
                                class="request-status-item"
                                data-status="Rejected"
                            >

                                <span class="status-dot"></span>

                                <div>

                                    <strong>
                                        Rejected
                                    </strong>

                                    <span>
                                        Request has been rejected by HR.
                                    </span>

                                </div>

                            </button>


                            <button
                                type="button"
                                class="request-status-item"
                                data-status="Completed"
                            >

                                <span class="status-dot"></span>

                                <div>

                                    <strong>
                                        Completed
                                    </strong>

                                    <span>
                                        Request has been completed.
                                    </span>

                                </div>

                            </button>

                        </div>

                    </div>


                    <!-- HR Remarks -->

                    <div class="request-detail-section">

                        <h3>

                            <i class="fa-solid fa-comment-dots"></i>

                            HR Remarks

                        </h3>


                        <textarea
                            id="modalRequestRemarks"
                            class="request-remarks-input"
                            rows="5"
                            placeholder="Enter HR remarks..."
                        ></textarea>


                        <p
                            class="request-readonly-message"
                            id="requestReadonlyMessage"
                            hidden
                        >

                            <i class="fa-solid fa-lock"></i>

                            This request has already been finalized
                            and can no longer be changed.

                        </p>

                    </div>

                </div>

            </div>


            <!-- ==================================================
                FOOTER
            =================================================== -->

            <div class="request-modal-footer">
                <button
                    type="button"
                    class="modal-btn modal-btn-secondary"
                    id="requestModalBack"
                    hidden
                >

                    <i class="fa-solid fa-arrow-left"></i>

                    Back

                </button>


                <div class="request-modal-actions">
                    <button
                        type="button"
                        class="modal-btn modal-btn-primary"
                        id="requestModalNext"
                    >

                        Next

                        <i class="fa-solid fa-arrow-right"></i>

                    </button>


                    <button
                        type="button"
                        class="modal-btn modal-btn-primary"
                        id="requestModalSave"
                        hidden
                    >

                        <i class="fa-solid fa-check"></i>

                        Save Changes

                    </button>
                </div>

            </div>

        </div>

    </div>


    <!-- ==========================================================
        PASSWORD RESET REQUEST MODAL
    =========================================================== -->

    <div
        class="request-modal"
        id="resetRequestModal"
        aria-hidden="true"
    >

        <div class="request-modal-backdrop"></div>


        <div
            class="request-modal-content"
            role="dialog"
            aria-modal="true"
            aria-labelledby="resetRequestModalTitle"
        >


            <div class="request-modal-header">

                <div>

                    <span class="modal-eyebrow">
                        Password Reset Request
                    </span>


                    <h2 id="resetRequestModalTitle">
                        Request Details
                    </h2>

                </div>


                <button
                    type="button"
                    class="request-modal-close"
                    id="resetRequestModalClose"
                    aria-label="Close request details"
                >

                    <i class="fa-solid fa-xmark"></i>

                </button>

            </div>


            <div class="request-modal-body">


                <!-- Requester -->

                <div class="request-detail-section">

                    <h3>

                        <i class="fa-solid fa-user"></i>

                        Requester Information

                    </h3>


                    <div class="request-detail-grid">

                        <div class="request-detail-item">

                            <span class="detail-label">
                                Full Name
                            </span>

                            <strong id="resetModalName">
                                —
                            </strong>

                        </div>


                        <div class="request-detail-item">

                            <span class="detail-label">
                                Username / Employee ID
                            </span>

                            <strong id="resetModalIdentifier">
                                —
                            </strong>

                        </div>


                        <div class="request-detail-item">

                            <span class="detail-label">
                                Contact
                            </span>

                            <strong id="resetModalContact">
                                —
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- Request -->

                <div class="request-detail-section">

                    <h3>

                        <i class="fa-solid fa-file-lines"></i>

                        Request Information

                    </h3>


                    <div class="request-detail-grid">

                        <div class="request-detail-item">

                            <span class="detail-label">
                                Date Requested
                            </span>

                            <strong id="resetModalCreated">
                                —
                            </strong>

                        </div>


                        <div class="request-detail-item">

                            <span class="detail-label">
                                Status
                            </span>

                            <span id="resetModalStatus">
                                —
                            </span>

                        </div>


                        <div class="request-detail-item">

                            <span class="detail-label">
                                Handled By
                            </span>

                            <strong id="resetModalResolvedBy">
                                —
                            </strong>

                        </div>

                    </div>

                </div>


                <!-- Message -->

                <div class="request-detail-section">

                    <h3>

                        <i class="fa-solid fa-align-left"></i>

                        Message to HR

                    </h3>


                    <div
                        class="request-detail-message"
                        id="resetModalMessage"
                    >
                        —
                    </div>

                </div>

            </div>


            <div class="request-modal-footer">

                <div class="request-modal-actions">

                    <button
                        type="button"
                        class="modal-btn modal-btn-secondary"
                        id="resetRequestModalCloseFooter"
                    >

                        Close

                    </button>


                    <button
                        type="button"
                        class="modal-btn modal-btn-primary"
                        id="resetRequestModalAction"
                    >

                        <i class="fa-solid fa-check"></i>

                        Mark as resolved

                    </button>

                </div>

            </div>

        </div>

    </div>

</div>


<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>
