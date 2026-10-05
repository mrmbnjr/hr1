<?php

use App\Services\AssignedDocumentService;

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


/*
|--------------------------------------------------------------------------
| Onboarding document requests (files requested by HR / Admin)
|--------------------------------------------------------------------------
|
| Supplied by EmployeeRequestsController. These are the documents HR or
| Administration requested during onboarding, including the ones employees
| have already submitted back for review.
|
*/

$documentRequests =
    $documentRequests ?? [];

$canManageDocuments =
    $canManageDocuments ?? false;


$documentStatusMeta = [

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
    ]

];


$documentActionMeta = [

    "document" => "Document submission",

    "response" => "Written response",

    "confirmation" => "Confirmation required"

];


$resetStatusMeta = [

    "Pending" => [
        "label" => "Pending",
        "class" => "badge-gray"
    ],

    "Completed" => [
        "label" => "Completed",
        "class" => "badge-green"
    ],

    "Rejected" => [
        "label" => "Rejected",
        "class" => "badge-red"
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
| Absolute date / time formatter
|--------------------------------------------------------------------------
|
| Used by the onboarding document modal, which shows exact timestamps.
|
*/

$formatDateTime = static function (
    ?string $value,
    string $format = 'F j, Y'
): string {

    $value = trim((string) ($value ?? ''));

    if ($value === '') {
        return '—';
    }

    $timestamp = strtotime($value);

    return $timestamp ? date($format, $timestamp) : '—';
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
    "total"     => count($requests) + count($passwordResetRequests) + count($documentRequests),
    "pending"   => 0,
    "approved"  => 0,
    "completed" => 0,
    "rejected"  => 0,
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

    $resetStatus =
        $reset["status"] ?? "Pending";

    if (isset($employeeStatusKeys[$resetStatus])) {
        $summary[$employeeStatusKeys[$resetStatus]]++;
    }
}


/*
|--------------------------------------------------------------------------
| Onboarding document requests feed the same status buckets
|--------------------------------------------------------------------------
|
| Documents use Pending, Approved, and Rejected. Approval is the final
| accepted state. A Pending row is awaiting HR review once the employee has
| submitted it, and awaiting the employee otherwise.
|
*/

foreach ($documentRequests as $document) {

    $documentStatus =
        $document["status"] ?? "Pending";

    if ($documentStatus === "Approved") {
        $summary["approved"]++;
    } elseif ($documentStatus === "Rejected") {
        $summary["rejected"]++;
    } else {
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


$hasAnyRows =
    !empty($requests) ||
    !empty($passwordResetRequests) ||
    !empty($documentRequests);

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


            <?php if ($canViewPasswordResets || !empty($documentRequests)): ?>

                <select id="categoryFilter">

                    <option value="All">
                        All Requests
                    </option>

                    <option value="employee">
                        Employee Requests
                    </option>

                    <?php if ($canViewPasswordResets): ?>

                        <option value="reset">
                            Password Resets
                        </option>

                    <?php endif; ?>

                    <option value="document">
                        Onboarding Documents
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

                <option value="Completed">
                    Completed
                </option>

                <option value="Rejected">
                    Rejected
                </option>

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


                            <?php foreach ($documentRequests as $document): ?>

                                <?php

                                $documentId =
                                    (int) (
                                        $document['document_id']
                                        ?? 0
                                    );


                                $documentStatus =
                                    $document['status']
                                    ?? 'Pending';


                                $documentMeta =
                                    $documentStatusMeta[$documentStatus]
                                    ?? $documentStatusMeta['Pending'];


                                $documentFullName =
                                    trim(
                                        (string) (
                                            $document['fullname']
                                            ?? ''
                                        )
                                    );


                                if ($documentFullName === '') {

                                    $documentFullName =
                                        'Unknown Employee';
                                }


                                $documentInitial =
                                    strtoupper(
                                        substr(
                                            $documentFullName,
                                            0,
                                            1
                                        )
                                    );


                                $documentTitle =
                                    trim(
                                        (string) (
                                            $document['document_name']
                                            ?? ''
                                        )
                                    );


                                if ($documentTitle === '') {

                                    $documentTitle =
                                        'Requested Document';
                                }


                                $documentType =
                                    trim(
                                        (string) (
                                            $document['request_type']
                                            ?? ''
                                        )
                                    );


                                if ($documentType === '') {

                                    $documentType =
                                        'Submit Required Document';
                                }


                                $documentActionType =
                                    (string) (
                                        $document['action_type']
                                        ?? 'document'
                                    );


                                $documentActionLabel =
                                    $documentActionMeta[
                                        $documentActionType
                                    ]
                                    ?? 'Document submission';


                                $documentAssignedBy =
                                    trim(
                                        (string) (
                                            $document[
                                                'assigned_by_name'
                                            ] ?? ''
                                        )
                                    );


                                if ($documentAssignedBy === '') {

                                    $documentAssignedBy =
                                        'HR';
                                }


                                $documentPriority =
                                    (string) (
                                        $document['priority']
                                        ?? 'Normal'
                                    );


                                $documentHasFile =
                                    !empty(
                                        $document['has_file']
                                    );


                                $documentIsSubmitted =
                                    !empty(
                                        $document['submitted_at']
                                    );


                                /*
                                 * Approve / Reject are available once the
                                 * employee has submitted a still-Pending
                                 * document.
                                 */

                                $documentCanReview =
                                    $canManageDocuments
                                    && $documentStatus === 'Pending'
                                    && $documentIsSubmitted;


                                $documentPayload = [

                                    'id' => $documentId,

                                    'employeeName' =>
                                        $documentFullName,

                                    'employeeNumber' =>
                                        (string) (
                                            $document[
                                                'employee_number'
                                            ] ?? ''
                                        ),

                                    'department' =>
                                        (string) (
                                            $document[
                                                'department_name'
                                            ] ?? ''
                                        ),

                                    'title' =>
                                        $documentTitle,

                                    'type' =>
                                        $documentType,

                                    'actionType' =>
                                        $documentActionType,

                                    'actionLabel' =>
                                        $documentActionLabel,

                                    'priority' =>
                                        $documentPriority,

                                    'dueDate' =>
                                        $formatDateTime(
                                            $document['due_date']
                                            ?? null
                                        ),

                                    'hasDue' =>
                                        !empty(
                                            $document['due_date']
                                        ),

                                    'assignedBy' =>
                                        $documentAssignedBy,

                                    'assignedByRole' =>
                                        trim(
                                            (string) (
                                                $document[
                                                    'assigned_by_role'
                                                ] ?? ''
                                            )
                                        ),

                                    'assignedAt' =>
                                        $formatDateTime(
                                            $document['created_at']
                                            ?? null,
                                            'F j, Y — g:i A'
                                        ),

                                    'status' =>
                                        $documentStatus,

                                    'statusLabel' =>
                                        $documentMeta['label'],

                                    'statusClass' =>
                                        $documentMeta['class'],

                                    'instruction' =>
                                        (string) (
                                            $document['instruction']
                                            ?? ''
                                        ),

                                    'responseText' =>
                                        (string) (
                                            $document['response_text']
                                            ?? ''
                                        ),

                                    'hasFile' =>
                                        $documentHasFile,

                                    'fileUrl' =>
                                        $documentHasFile
                                            ? '?page=assigned-requests-download&request_id='
                                                . $documentId
                                            : '',

                                    'fileName' =>
                                        $documentHasFile
                                            ? $documentTitle
                                            : '',

                                    'fileSize' =>
                                        AssignedDocumentService::formatSize(
                                            isset(
                                                $document[
                                                    'submission_file_size'
                                                ]
                                            )
                                                ? (int) $document[
                                                    'submission_file_size'
                                                ]
                                                : null
                                        ),

                                    'submittedAt' =>
                                        $formatDateTime(
                                            $document['submitted_at']
                                            ?? null,
                                            'F j, Y — g:i A'
                                        ),

                                    'reviewRemarks' =>
                                        (string) (
                                            $document['remarks']
                                            ?? ''
                                        ),

                                    'canReview' =>
                                        $documentCanReview,

                                ];

                                ?>

                                <tr
                                    class="request-row document-row"
                                    data-kind="document"
                                    tabindex="0"
                                    role="button"

                                    data-id="<?= htmlspecialchars(
                                        (string) $documentId
                                    ) ?>"

                                    data-name="<?= htmlspecialchars(
                                        $documentFullName
                                    ) ?>"

                                    data-status="<?= htmlspecialchars(
                                        $documentStatus
                                    ) ?>"

                                    data-date="<?= htmlspecialchars(
                                        (string) (
                                            $document['created_at']
                                            ?? ''
                                        )
                                    ) ?>"

                                    data-document="<?= htmlspecialchars(
                                        json_encode($documentPayload),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                >


                                    <!-- Requester -->

                                    <td>

                                        <div class="request-cell">

                                            <div class="avatar-circle">

                                                <?= htmlspecialchars(
                                                    $documentInitial
                                                ) ?>

                                            </div>


                                            <div>

                                                <strong>

                                                    <?= htmlspecialchars(
                                                        $documentFullName
                                                    ) ?>

                                                </strong>


                                                <span class="sub-text">

                                                    <?= htmlspecialchars(
                                                        (string) (
                                                            $document[
                                                                'employee_number'
                                                            ] ?? ''
                                                        )
                                                    ) ?>

                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- Type -->

                                    <td>

                                        <span class="request-kind request-kind--document">

                                            <i class="fa-solid fa-file-arrow-up"></i>

                                            Onboarding Document

                                        </span>

                                    </td>


                                    <!-- Details -->

                                    <td>

                                        <button
                                            type="button"
                                            class="request-type request-view-btn"
                                            data-request-id="<?= htmlspecialchars(
                                                (string) $documentId
                                            ) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $documentTitle
                                            ) ?>

                                        </button>


                                        <span class="sub-text">

                                            <?= htmlspecialchars(
                                                (string) (
                                                    $document[
                                                        'department_name'
                                                    ] ?? '—'
                                                )
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- Status -->

                                    <td>

                                        <span
                                            class="badge <?= htmlspecialchars(
                                                $documentMeta['class']
                                            ) ?>"
                                        >

                                            <?= htmlspecialchars(
                                                $documentMeta['label']
                                            ) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <span class="sub-text application-time">
                                            <?= htmlspecialchars(
                                                $formatRelativeTime($document['created_at'] ?? null)
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

                    <span class="modal-eyebrow" id="requestModalEyebrow">
                        Request
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

                            <span id="modalPartyHeading">
                                Employee Information
                            </span>

                        </h3>


                        <div class="request-detail-grid">

                            <div class="request-detail-item">

                                <span class="detail-label" id="modalPartyLabelOne">
                                    Employee
                                </span>

                                <strong id="modalEmployeeName">
                                    —
                                </strong>

                            </div>


                            <div class="request-detail-item">

                                <span class="detail-label" id="modalPartyLabelTwo">
                                    Employee Number
                                </span>

                                <strong id="modalEmployeeNumber">
                                    —
                                </strong>

                            </div>


                            <div class="request-detail-item">

                                <span class="detail-label" id="modalPartyLabelThree">
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

                                <span class="detail-label" id="modalRequestTypeLabel">
                                    Request Type
                                </span>

                                <strong id="modalRequestType">
                                    —
                                </strong>

                            </div>


                            <div class="request-detail-item" id="modalRequestCategoryItem" hidden>

                                <span class="detail-label">
                                    Request Type
                                </span>

                                <strong id="modalRequestCategory">
                                    —
                                </strong>

                            </div>


                            <div class="request-detail-item">

                                <span class="detail-label" id="modalRequestDateLabel">
                                    Date Submitted
                                </span>

                                <strong id="modalRequestDate">
                                    —
                                </strong>

                            </div>


                            <div class="request-detail-item" id="modalRequestDueItem" hidden>

                                <span class="detail-label">
                                    Due Date
                                </span>

                                <strong id="modalRequestDue">
                                    —
                                </strong>

                            </div>


                            <div class="request-detail-item" id="modalRequestPriorityItem" hidden>

                                <span class="detail-label">
                                    Priority
                                </span>

                                <strong id="modalRequestPriority">
                                    —
                                </strong>

                            </div>


                            <div class="request-detail-item" id="modalRequestAssignedByItem" hidden>

                                <span class="detail-label">
                                    Assigned By
                                </span>

                                <strong id="modalRequestAssignedBy">
                                    —
                                </strong>

                            </div>


                            <div class="request-detail-item" id="modalRequestHandledByItem" hidden>

                                <span class="detail-label">
                                    Handled By
                                </span>

                                <strong id="modalRequestHandledBy">
                                    —
                                </strong>

                            </div>


                            <div class="request-detail-item">

                                <span class="detail-label">
                                    Status
                                </span>

                                <span id="modalRequestStatus" class="badge badge-gray">
                                    —
                                </span>

                            </div>

                        </div>

                    </div>


                    <!-- Subject -->

                    <div
                        class="request-detail-section"
                        id="modalSubjectSection"
                    >

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

                            <span id="modalDetailsHeading">
                                Request Details
                            </span>

                        </h3>


                        <div
                            class="request-detail-message"
                            id="modalRequestDescription"
                        >
                            —
                        </div>

                    </div>


                    <!-- Employee submission (documents) -->

                    <div
                        class="request-detail-section"
                        id="modalSubmissionSection"
                        hidden
                    >

                        <h3>

                            <i class="fa-solid fa-paper-plane"></i>

                            Employee Submission

                        </h3>


                        <div
                            class="request-detail-message"
                            id="modalSubmissionText"
                            hidden
                        ></div>


                        <a
                            class="doc-download"
                            id="modalSubmissionFile"
                            href="#"
                            hidden
                        >

                            <i class="fa-solid fa-file-arrow-down"></i>

                            <span id="modalSubmissionFileName">
                                document
                            </span>

                            <span
                                class="doc-download__size"
                                id="modalSubmissionFileSize"
                            ></span>

                        </a>


                        <p
                            class="doc-submitted-at"
                            id="modalSubmissionAt"
                        ></p>

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


                        <div
                            class="request-status-list"
                            id="requestStatusList"
                        ></div>

                    </div>


                    <!-- HR Remarks -->

                    <div
                        class="request-detail-section"
                        id="modalRemarksSection"
                    >

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




<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>
