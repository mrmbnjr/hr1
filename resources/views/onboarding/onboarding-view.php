<?php

use App\Models\Onboarding;

$pageTitle       = "Onboarding Details";
$pageCSS         = "onboarding.css";
$pageJS          = "onboarding.js";
$pageDescription = "Manage employee onboarding requirements.";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url("?page=login"));
    exit;
}


/*
|--------------------------------------------------------------------------
| DATA PROVIDED BY CONTROLLER
|--------------------------------------------------------------------------
|
| The controller provides:
|
| $employee
| $progress
| $progressSteps
| $documents
| $documentProgress
| $activities
|
*/

$employee = $employee ?? [];

$progress = (int) ($progress ?? 0);

$progressSteps = $progressSteps ?? [];

$documents = $documents ?? [];

$documentProgress = $documentProgress ?? [
    'total'     => 0,
    'verified'  => 0,
    'submitted' => 0,
    'pending'   => 0
];

$activities = $activities ?? [];


/*
|--------------------------------------------------------------------------
| EMPLOYEE DISPLAY DATA
|--------------------------------------------------------------------------
*/

$fullname = trim(
    ($employee['first_name'] ?? '') . ' ' .
    ($employee['middle_name'] ?? '') . ' ' .
    ($employee['last_name'] ?? '')
);

$fullname = preg_replace('/\s+/', ' ', $fullname);

if ($fullname === '') {
    $fullname = 'Unknown Employee';
}


$employeeId =
    $employee['employee_id'] ?? 'N/A';


$jobTitle =
    $employee['job_title'] ?? 'No Position';


$department =
    $employee['department_name'] ?? 'No Department';


$email =
    $employee['email'] ?? '';


$phone =
    $employee['phone'] ?? '';


$employmentType =
    $employee['employment_type']
    ?? $employee['employment_status']
    ?? '';


$status = trim(
    (string) ($employee['onboarding_status'] ?? '')
);

if ($status === '') {
    $status = 'Pending';
}


$statusSlug = strtolower(
    str_replace(' ', '-', $status)
);


/*
|--------------------------------------------------------------------------
| START DATE
|--------------------------------------------------------------------------
*/

$startDate = 'Not available';

if (!empty($employee['start_date'])) {

    $timestamp = strtotime(
        $employee['start_date']
    );

    if ($timestamp !== false) {

        $startDate = date(
            'F d, Y',
            $timestamp
        );
    }
}


$orientationDate = '';

if (!empty($employee['orientation_date'])) {

    $orientationTimestamp = strtotime(
        $employee['orientation_date']
    );

    if ($orientationTimestamp !== false) {

        $orientationDate = date(
            'M d, Y',
            $orientationTimestamp
        );
    }
}


/*
|--------------------------------------------------------------------------
| AVATAR INITIAL
|--------------------------------------------------------------------------
*/

$avatarInitial =
    strtoupper(
        substr(
            trim($fullname),
            0,
            1
        )
    );


/*
|--------------------------------------------------------------------------
| DOCUMENT COUNTS
|--------------------------------------------------------------------------
*/

$totalDocuments =
    (int) ($documentProgress['total'] ?? 0);

$approvedDocuments =
    (int) ($documentProgress['approved'] ?? 0);

$completedDocuments =
    (int) ($documentProgress['completed'] ?? 0);

$acceptedDocuments =
    (int) ($documentProgress['accepted'] ?? 0);

$submittedDocuments =
    (int) ($documentProgress['submitted'] ?? 0);

$pendingDocuments =
    (int) ($documentProgress['pending'] ?? 0);

/*
| Documents the employee has already sent back but HR has not yet
| confirmed — the actionable "awaiting review" count (submitted minus
| accepted). Canonically these rows are still Pending.
*/

$awaitingReviewDocuments =
    max(0, $submittedDocuments - $acceptedDocuments);


$documentPercent = 0;

if ($totalDocuments > 0) {

    $documentPercent = min(
        100,
        round(
            ($acceptedDocuments / $totalDocuments) * 100,
            1
        )
    );
}


$completedStepCount = 0;

foreach ($progressSteps as $step) {

    if (($step['status'] ?? '') === 'completed') {
        $completedStepCount++;
    }
}


/*
|--------------------------------------------------------------------------
| ONBOARDING ID
|--------------------------------------------------------------------------
*/

$onboardingId =
    (int) ($employee['onboarding_id'] ?? 0);

?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>

<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>


<div class="main-content">

    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>


    <div class="onboarding-view-page">


        <section class="onboarding-view-header">

            <div class="header-left">

                <div class="title-row">

                    <div class="avatar-large">
                        <?= htmlspecialchars($avatarInitial) ?>
                    </div>

                    <div>

                        <span class="eyebrow">
                            Onboarding Details
                        </span>

                        <h1>
                            <?= htmlspecialchars($fullname) ?>
                        </h1>

                        <p class="employee-position">
                            <?= htmlspecialchars($jobTitle) ?>
                            <span>•</span>
                            <?= htmlspecialchars($department) ?>
                        </p>

                    </div>

                    <span class="onboarding-status-badge <?= htmlspecialchars($statusSlug) ?>">
                        <span class="status-dot" aria-hidden="true"></span>
                        <span class="status-label">
                            <?= htmlspecialchars($status) ?>
                        </span>
                    </span>

                </div>

            </div>

        </section>


        <section class="employee-header-card">

            <div class="employee-header-item">
                <span class="info-label">Employee ID</span>
                <strong>
                    <?= htmlspecialchars((string) $employeeId) ?>
                </strong>
            </div>

            <div class="employee-header-item">
                <span class="info-label">Email</span>
                <strong>
                    <?= htmlspecialchars($email !== '' ? $email : '—') ?>
                </strong>
            </div>

            <div class="employee-header-item">
                <span class="info-label">Phone</span>
                <strong>
                    <?= htmlspecialchars($phone !== '' ? $phone : '—') ?>
                </strong>
            </div>

            <div class="employee-header-item">
                <span class="info-label">Employment</span>
                <strong>
                    <?= htmlspecialchars($employmentType !== '' ? $employmentType : '—') ?>
                </strong>
            </div>

            <div class="employee-header-item">
                <span class="info-label">Start Date</span>
                <strong>
                    <?= htmlspecialchars($startDate) ?>
                </strong>
            </div>

            <div class="employee-header-item">
                <span class="info-label">Orientation</span>
                <strong>
                    <?= htmlspecialchars($orientationDate !== '' ? $orientationDate : 'Not scheduled') ?>
                </strong>
            </div>

        </section>


        <section class="onboarding-summary-grid">

            <div class="onboarding-stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-route"></i>
                </div>
                <div class="stat-content">
                    <span>Overall Progress</span>
                    <strong><?= $progress ?>%</strong>
                    <small>
                        <?= $completedStepCount ?>
                        of
                        <?= count($progressSteps) ?>
                        steps
                    </small>
                </div>
            </div>

            <div class="onboarding-stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-folder-open"></i>
                </div>
                <div class="stat-content">
                    <span>Documents</span>
                    <strong><?= $totalDocuments ?></strong>
                    <small>Requested from this hire</small>
                </div>
            </div>

            <div class="onboarding-stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-file-circle-check"></i>
                </div>
                <div class="stat-content">
                    <span>Approved</span>
                    <strong><?= $approvedDocuments ?></strong>
                    <small>Confirmed by HR</small>
                </div>
            </div>

            <div class="onboarding-stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="stat-content">
                    <span>Completed</span>
                    <strong><?= $completedDocuments ?></strong>
                    <small>Carried out</small>
                </div>
            </div>

            <div class="onboarding-stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-hourglass-half"></i>
                </div>
                <div class="stat-content">
                    <span>Pending</span>
                    <strong><?= $pendingDocuments ?></strong>
                    <small>
                        <?= $awaitingReviewDocuments > 0
                            ? $awaitingReviewDocuments . ' awaiting review'
                            : 'Not yet settled' ?>
                    </small>
                </div>
            </div>

        </section>


        <div class="onboarding-detail-grid">


            <section class="detail-card progress-card">

                <div class="card-header">

                    <div>
                        <div>
                            <h2>Onboarding Progress</h2>
                            <p>Track the employee's onboarding journey.</p>
                        </div>

                    </div>

                    <span class="progress-count">
                        <?= $completedStepCount ?>
                        of
                        <?= count($progressSteps) ?>
                    </span>

                </div>

                <div class="overall-progress">

                    <div class="progress-label">
                        <span>Overall Progress</span>
                        <strong><?= $progress ?>%</strong>
                    </div>

                    <div class="progress-track">
                        <div
                            class="progress-fill"
                            style="width: <?= $progress ?>%;">
                        </div>
                    </div>

                </div>

                <div class="progress-timeline">

                    <?php if (!empty($progressSteps)): ?>

                        <?php foreach ($progressSteps as $step): ?>

                            <div
                                class="progress-step <?= htmlspecialchars(
                                    $step['status'] ?? 'pending'
                                ) ?>">

                                <div class="step-marker">

                                    <?php if (($step['status'] ?? '') === 'completed'): ?>
                                        <i class="fa-solid fa-check"></i>
                                    <?php elseif (($step['status'] ?? '') === 'in-progress'): ?>
                                        <i class="fa-solid fa-circle"></i>
                                    <?php elseif (($step['status'] ?? '') === 'current'): ?>
                                        <i class="fa-solid fa-circle"></i>
                                    <?php else: ?>
                                        <i class="fa-regular fa-circle"></i>
                                    <?php endif; ?>

                                </div>

                                <div class="step-content">

                                    <div class="step-top">

                                        <h3>
                                            <?= htmlspecialchars($step['title'] ?? '') ?>
                                        </h3>

                                        <?php if (!empty($step['date'])): ?>
                                            <span><?= htmlspecialchars($step['date']) ?></span>
                                        <?php else: ?>
                                            <span>Pending</span>
                                        <?php endif; ?>

                                    </div>

                                    <p>
                                        <?= htmlspecialchars($step['description'] ?? '') ?>
                                    </p>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="empty-state">
                            <p>No onboarding progress available.</p>
                        </div>

                    <?php endif; ?>

                </div>

            </section>


            <section class="detail-card documents-card">

                <div class="card-header">

                    <div>
                        <div>
                            <h2>Document Checklist</h2>
                            <p>Documents requested from this employee.</p>
                        </div>
                    </div>

                    <span class="document-count">
                        <?= $acceptedDocuments ?>
                        /
                        <?= $totalDocuments ?>
                    </span>

                </div>

                <div class="overall-progress document-progress">

                    <div class="progress-label">
                        <span>Accepted</span>
                        <strong><?= $documentPercent ?>%</strong>
                    </div>

                    <div class="progress-track">
                        <div
                            class="progress-fill"
                            style="width: <?= $documentPercent ?>%;">
                        </div>
                    </div>

                </div>

                <button
                    type="button"
                    class="request-document-btn"
                    id="openDocumentModal">

                    <i class="fa-solid fa-plus"></i>
                    Request Document

                </button>

                <div class="document-list">

                    <?php if (!empty($documents)): ?>

                        <?php foreach ($documents as $document): ?>

                            <?php

                            $documentStatus =
                                $document['status'] ?? 'Pending';

                            switch ($documentStatus) {

                                case 'Approved':
                                    $statusClass = 'approved';
                                    break;

                                case 'Completed':
                                    $statusClass = 'completed';
                                    break;

                                case 'Rejected':
                                    $statusClass = 'rejected';
                                    break;

                                case 'Pending':
                                default:
                                    $statusClass = 'pending';
                                    break;
                            }

                            $filePath = trim((string) ($document['file_path'] ?? ''));
                            $fileHref = '';

                            if ($filePath !== '') {

                                if (
                                    str_starts_with($filePath, 'http://')
                                    || str_starts_with($filePath, 'https://')
                                    || str_starts_with($filePath, '/')
                                ) {
                                    $fileHref = $filePath;
                                } else {
                                    $fileHref = url(ltrim($filePath, '/'));
                                }
                            }


                            /*
                            |------------------------------------------------------------
                            | Payload for the "View Document" detail modal
                            |------------------------------------------------------------
                            */

                            $docRequestType =
                                $document['request_type'] ?? 'Submit Required Document';

                            $docActionType =
                                $document['action_type'] ?? 'document';

                            $docPriority =
                                $document['priority'] ?? 'Normal';

                            $docDueDate = '';

                            if (!empty($document['due_date'])) {
                                $dueTimestamp = strtotime($document['due_date']);
                                $docDueDate = $dueTimestamp !== false
                                    ? date('F j, Y', $dueTimestamp)
                                    : '';
                            }

                            $docReviewedAt = '';

                            if (!empty($document['reviewed_at'])) {
                                $reviewedTimestamp = strtotime($document['reviewed_at']);
                                $docReviewedAt = $reviewedTimestamp !== false
                                    ? date('F j, Y — g:i A', $reviewedTimestamp)
                                    : '';
                            }

                            $docCompletedAt = '';

                            if (!empty($document['completed_at'])) {
                                $completedTimestamp = strtotime($document['completed_at']);
                                $docCompletedAt = $completedTimestamp !== false
                                    ? date('F j, Y — g:i A', $completedTimestamp)
                                    : '';
                            }

                            $actionTypeLabels = [
                                'document' => 'Upload a document',
                                'response' => 'Provide a written response',
                                'confirmation' => 'Confirmation only',
                            ];


                            /*
                            |------------------------------------------------------------
                            | Activity timeline
                            |------------------------------------------------------------
                            | Derived from known columns only — same approach as the
                            | page-level Activity Log below, since there is no
                            | activity-log table. "Date unavailable" is used where no
                            | timestamp column exists (matches that section's pattern).
                            */

                            $docActivities = [];

                            $docActivities[] = [
                                'icon' => 'fa-plus',
                                'text' => 'Document was requested from the employee.',
                                'date' => 'Date unavailable',
                            ];

                            if ($filePath !== '') {
                                $docActivities[] = [
                                    'icon' => 'fa-paper-plane',
                                    'text' => 'Employee submitted the document.',
                                    'date' => 'Date unavailable',
                                ];
                            }

                            if (!empty($document['reviewed_at'])) {
                                $docActivities[] = [
                                    'icon' => $documentStatus === 'Rejected'
                                        ? 'fa-xmark'
                                        : 'fa-magnifying-glass',
                                    'text' => $documentStatus === 'Rejected'
                                        ? 'HR rejected this document.'
                                        : 'HR approved this document.',
                                    'date' => $docReviewedAt !== '' ? $docReviewedAt : 'Date unavailable',
                                ];
                            }

                            if (!empty($document['completed_at'])) {
                                $docActivities[] = [
                                    'icon' => 'fa-circle-check',
                                    'text' => 'Document marked as completed.',
                                    'date' => $docCompletedAt !== '' ? $docCompletedAt : 'Date unavailable',
                                ];
                            }

                            $documentPayload = [
                                'name' => $document['document_name'] ?? 'Unnamed Document',
                                'requestType' => $docRequestType,
                                'actionType' => $docActionType,
                                'actionLabel' => $actionTypeLabels[$docActionType] ?? 'Upload a document',
                                'priority' => $docPriority,
                                'dueDate' => $docDueDate,
                                'status' => $documentStatus,
                                'instruction' => $document['instruction'] ?? '',
                                'fileUrl' => $fileHref,
                                'fileName' => $filePath !== '' ? ($document['document_name'] ?? 'Document') : '',
                                'response' => trim((string) ($document['response_text'] ?? '')),
                                'remarks' => $document['remarks'] ?? '',
                                'reviewedAt' => $docReviewedAt,
                                'completedAt' => $docCompletedAt,
                                'activities' => $docActivities,
                            ];

                            ?>
                            <div class="document-item">

                                <div class="document-icon">
                                    <i class="fa-regular fa-file-lines"></i>
                                </div>

                                <div class="document-info">

                                    <h3>
                                        <?= htmlspecialchars(
                                            $document['document_name']
                                                ?? 'Unnamed Document'
                                        ) ?>
                                    </h3>

                                    <span>
                                        <?php if ($filePath !== ''): ?>
                                            Document uploaded
                                        <?php else: ?>
                                            Not yet uploaded
                                        <?php endif; ?>
                                    </span>

                                </div>

                                <span class="document-status <?= htmlspecialchars($statusClass) ?>">
                                    <?= htmlspecialchars($documentStatus) ?>
                                </span>

                                <div class="document-actions">

                                    <button
                                        type="button"
                                        class="document-action view-document"
                                        data-document="<?= htmlspecialchars(
                                            json_encode($documentPayload),
                                            ENT_QUOTES
                                        ) ?>"
                                        title="View document details">
                                        <i class="fa-solid fa-eye"></i>
                                    </button>

                                    <?php if ($documentStatus === 'Pending' && empty($document['submitted_at'])): ?>

                                        <button
                                            type="button"
                                            class="document-action edit-document"
                                            data-id="<?= (int) ($document['document_id'] ?? 0) ?>"
                                            data-name="<?= htmlspecialchars(
                                                $document['document_name'] ?? '',
                                                ENT_QUOTES
                                            ) ?>"
                                            data-description="<?= htmlspecialchars(
                                                $document['instruction'] ?? '',
                                                ENT_QUOTES
                                            ) ?>"
                                            data-request-type="<?= htmlspecialchars(
                                                $document['request_type'] ?? 'Submit Required Document',
                                                ENT_QUOTES
                                            ) ?>"
                                            data-action-type="<?= htmlspecialchars(
                                                $document['action_type'] ?? 'document',
                                                ENT_QUOTES
                                            ) ?>"
                                            data-priority="<?= htmlspecialchars(
                                                $document['priority'] ?? 'Normal',
                                                ENT_QUOTES
                                            ) ?>"
                                            data-due-date="<?= htmlspecialchars(
                                                $document['due_date'] ?? '',
                                                ENT_QUOTES
                                            ) ?>"
                                            title="Edit pending document">

                                            <i class="fa-solid fa-pen"></i>

                                        </button>

                                    <?php elseif ($documentStatus === 'Pending'): ?>

                                        <!-- Approve (confirm the submission) -->
                                        <button
                                            type="button"
                                            class="document-action verify-document"
                                            data-id="<?= (int) ($document['document_id'] ?? 0) ?>"
                                            data-name="<?= htmlspecialchars(
                                                $document['document_name'] ?? '',
                                                ENT_QUOTES
                                            ) ?>"
                                            title="Approve document">

                                            <i class="fa-solid fa-check"></i>

                                        </button>

                                        <!-- Reject -->
                                        <button
                                            type="button"
                                            class="document-action reject-document"
                                            data-id="<?= (int) ($document['document_id'] ?? 0) ?>"
                                            data-name="<?= htmlspecialchars(
                                                $document['document_name'] ?? '',
                                                ENT_QUOTES
                                            ) ?>"
                                            title="Reject document">

                                            <i class="fa-solid fa-xmark"></i>

                                        </button>

                                    <?php elseif ($documentStatus === 'Approved'): ?>

                                        <!-- Mark completed (carry the request out) -->
                                        <button
                                            type="button"
                                            class="document-action complete-document"
                                            data-id="<?= (int) ($document['document_id'] ?? 0) ?>"
                                            data-name="<?= htmlspecialchars(
                                                $document['document_name'] ?? '',
                                                ENT_QUOTES
                                            ) ?>"
                                            title="Mark as completed">

                                            <i class="fa-solid fa-flag-checkered"></i>

                                        </button>

                                    <?php endif; ?>
                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="empty-state">
                            <i class="fa-regular fa-folder-open"></i>
                            <p>No documents have been requested yet.</p>
                        </div>

                    <?php endif; ?>

                </div>

            </section>


            <section class="detail-card activity-card">

                <div class="card-header">

                    <div>
                        <div>
                            <h2>Activity Log</h2>
                            <p>Recent onboarding activities.</p>
                        </div>
                    </div>
                </div>

                <div class="activity-list">

                    <?php if (!empty($activities)): ?>

                        <?php foreach ($activities as $activity): ?>

                            <div class="activity-item">

                                <span class="activity-dot"></span>

                                <div class="activity-content">

                                    <p>
                                        <?= htmlspecialchars($activity['text'] ?? '') ?>
                                    </p>

                                    <span>
                                        <?= htmlspecialchars($activity['date'] ?? '') ?>
                                    </span>

                                </div>

                            </div>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <div class="empty-state">
                            <p>No onboarding activity yet.</p>
                        </div>

                    <?php endif; ?>

                </div>

            </section>


        </div>

    </div>


    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>


</div>



<!-- ==============================================================
    REQUEST DOCUMENT MODAL
=============================================================== -->

<div
    class="document-modal-overlay"
    id="documentModal"
    aria-hidden="true">


    <div
        class="document-modal request-document-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="documentModalTitle">

        <div class="modal-header">

            <div>

                <span class="modal-icon">
                    <i class="fa-solid fa-file-circle-plus"></i>
                </span>

                <div>
                    <h2 id="documentModalTitle">
                        Request Document
                    </h2>
                    <p>
                        Request a document from this employee.
                    </p>
                </div>

            </div>

            <button
                type="button"
                class="modal-close"
                id="closeDocumentModal"
                aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <form
            method="POST"
            action="?page=onboarding-request-document"
            id="requestDocumentForm">

            <input
                type="hidden"
                name="onboarding_id"
                value="<?= (int) $onboardingId ?>">

            <div class="modal-body">

                <div class="form-group">

                    <label for="documentName">
                        Document Type
                    </label>

                    <input
                        type="text"
                        id="documentName"
                        name="document_name"
                        placeholder="e.g. Birth Certificate"
                        maxlength="150"
                        required>

                </div>

                <div class="form-group">

                    <label for="documentDescription">

                        Instructions

                    </label>

                    <textarea
                        id="documentDescription"
                        name="description"
                        rows="3"
                        placeholder="Enter instructions or additional information for the employee..."></textarea>

                </div>

                <div class="form-section-label">
                    Assignment Details
                </div>

                <div class="form-grid-2">

                    <div class="form-group">

                        <label for="documentRequestType">

                            Request Type

                        </label>

                        <select
                            id="documentRequestType"
                            name="request_type">

                            <?php foreach (Onboarding::REQUEST_TYPES as $type => $icon): ?>

                                <option
                                    value="<?= htmlspecialchars($type) ?>"
                                    <?= $type === 'Submit Required Document' ? 'selected' : '' ?>>

                                    <?= htmlspecialchars($type) ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="documentActionType">

                            Required From Employee

                        </label>

                        <select
                            id="documentActionType"
                            name="action_type">

                            <option value="document" selected>
                                Upload a document
                            </option>

                            <option value="response">
                                Provide a written response
                            </option>

                            <option value="confirmation">
                                Confirmation only
                            </option>

                        </select>

                    </div>

                </div>

                <div class="form-grid-2">

                    <div class="form-group">

                        <label for="documentPriority">

                            Priority


                        </label>

                        <select
                            id="documentPriority"
                            name="priority">

                            <option value="Normal" selected>
                                Normal
                            </option>

                            <option value="High">
                                High
                            </option>

                            <option value="Urgent">
                                Urgent
                            </option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="documentDueDate">

                            Due Date


                        </label>

                        <input
                            type="date"
                            id="documentDueDate"
                            name="due_date">

                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="modal-cancel"
                    id="cancelDocumentModal">
                    Cancel
                </button>

                <button
                    type="submit"
                    class="modal-submit">
                    <i class="fa-solid fa-paper-plane"></i>
                    Send Request
                </button>

            </div>

        </form>
    </div>
</div>

<!-- ==============================================================
    VERIFY DOCUMENT MODAL
=============================================================== -->

<div
    class="document-modal-overlay"
    id="verifyDocumentModal"
    aria-hidden="true">

    <div
        class="document-modal document-decision-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="verifyDocumentModalTitle">

        <div class="modal-header">

            <div>

                <span class="modal-icon decision-icon-verify">
                    <i class="fa-solid fa-circle-check"></i>
                </span>

                <div>

                    <h2 id="verifyDocumentModalTitle">
                        Verify Document
                    </h2>

                    <p>
                        Confirm that this submitted document is valid.
                    </p>

                </div>

            </div>

            <button
                type="button"
                class="modal-close"
                data-close-document-decision="verifyDocumentModal"
                aria-label="Close">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>

        <form
            method="POST"
            id="verifyDocumentForm">

            <input
                type="hidden"
                name="document_id"
                id="verifyDocumentId">

            <input
                type="hidden"
                name="document_action"
                value="verify">

            <div class="decision-message">

                <p>
                    Are you sure you want to approve this document?
                </p>

                <span
                    class="decision-document-name"
                    id="verifyDocumentName">
                </span>

            </div>

            <div class="form-group">

                <label for="verifyDocumentRemarks">

                    Remarks

                    <span>Optional</span>

                </label>

                <textarea
                    id="verifyDocumentRemarks"
                    name="remarks"
                    rows="4"
                    placeholder="Add remarks about the document verification, if needed..."></textarea>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="modal-cancel"
                    data-close-document-decision="verifyDocumentModal">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="modal-submit decision-approve-btn">

                    <i class="fa-solid fa-check"></i>
                    Approve Document

                </button>

            </div>

        </form>

    </div>

</div>

<!-- ==============================================================
    REJECT DOCUMENT MODAL
=============================================================== -->

<div
    class="document-modal-overlay"
    id="rejectDocumentModal"
    aria-hidden="true">

    <div
        class="document-modal document-decision-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="rejectDocumentModalTitle">

        <div class="modal-header">

            <div>

                <span class="modal-icon decision-icon-reject">
                    <i class="fa-solid fa-file-circle-xmark"></i>
                </span>

                <div>

                    <h2 id="rejectDocumentModalTitle">
                        Reject Document
                    </h2>

                    <p>
                        Provide a reason for rejecting this document.
                    </p>

                </div>

            </div>

            <button
                type="button"
                class="modal-close"
                data-close-document-decision="rejectDocumentModal"
                aria-label="Close">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>

        <form
            method="POST"
            id="rejectDocumentForm">

            <input
                type="hidden"
                name="document_id"
                id="rejectDocumentId">

            <input
                type="hidden"
                name="document_action"
                value="reject">

            <div class="decision-message">

                <p>
                    Are you sure you want to reject this document?
                </p>

                <span
                    class="decision-document-name"
                    id="rejectDocumentName">
                </span>

            </div>

            <div class="form-group">

                <label for="rejectDocumentRemarks">

                    Remarks

                    <span class="required-label">Required</span>

                </label>

                <textarea
                    id="rejectDocumentRemarks"
                    name="remarks"
                    rows="4"
                    placeholder="Explain why this document is being rejected..."
                    required></textarea>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="modal-cancel"
                    data-close-document-decision="rejectDocumentModal">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="modal-submit decision-reject-btn">

                    <i class="fa-solid fa-xmark"></i>
                    Reject Document

                </button>

            </div>

        </form>

    </div>

</div>

<!-- ==============================================================
    MARK DOCUMENT COMPLETED MODAL
=============================================================== -->

<div
    class="document-modal-overlay"
    id="completeDocumentModal"
    aria-hidden="true">

    <div
        class="document-modal document-decision-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="completeDocumentModalTitle">

        <div class="modal-header">

            <div>

                <span class="modal-icon decision-icon-complete">
                    <i class="fa-solid fa-flag-checkered"></i>
                </span>

                <div>

                    <h2 id="completeDocumentModalTitle">
                        Mark Document Completed
                    </h2>

                    <p>
                        Record that this approved requirement has been carried out.
                    </p>

                </div>

            </div>

            <button
                type="button"
                class="modal-close"
                data-close-document-decision="completeDocumentModal"
                aria-label="Close">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>

        <form
            method="POST"
            id="completeDocumentForm">

            <input
                type="hidden"
                name="document_id"
                id="completeDocumentId">

            <input
                type="hidden"
                name="document_action"
                value="complete">

            <div class="decision-message">

                <p>
                    Mark this document as completed? This confirms the request
                    has actually been carried out.
                </p>

                <span
                    class="decision-document-name"
                    id="completeDocumentName">
                </span>

            </div>

            <div class="form-group">

                <label for="completeDocumentRemarks">

                    Remarks

                    <span>Optional</span>

                </label>

                <textarea
                    id="completeDocumentRemarks"
                    name="remarks"
                    rows="4"
                    placeholder="Add remarks about the completion, if needed..."></textarea>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="modal-cancel"
                    data-close-document-decision="completeDocumentModal">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="modal-submit decision-complete-btn">

                    <i class="fa-solid fa-flag-checkered"></i>
                    Mark Completed

                </button>

            </div>

        </form>

    </div>

</div>

<!-- ==============================================================
    EDIT DOCUMENT MODAL
=============================================================== -->

<div
    class="document-modal-overlay"
    id="editDocumentModal"
    aria-hidden="true">

    <div
        class="document-modal request-document-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="editDocumentModalTitle">

        <div class="modal-header">

            <div>

                <span class="modal-icon">
                    <i class="fa-solid fa-pen-to-square"></i>
                </span>

                <div>
                    <h2 id="editDocumentModalTitle">
                        Edit Pending Document
                    </h2>
                    <p>
                        Update the document title for this employee.
                    </p>
                </div>

            </div>

            <button
                type="button"
                class="modal-close"
                id="closeEditDocumentModal"
                aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <form id="editDocumentForm">

            <input type="hidden" name="document_id" id="editDocumentId">

            <div class="modal-body">

                <div class="form-group">

                    <label for="editDocumentName">
                        Document Type
                    </label>

                    <input
                        type="text"
                        id="editDocumentName"
                        name="document_name"
                        maxlength="150"
                        required>

                </div>

                <div class="form-group">

                    <label for="editDocumentDescription">
                        Instructions
                    </label>

                    <textarea
                        id="editDocumentDescription"
                        name="description"
                        rows="3"
                        placeholder="Update instructions or additional details for this employee..."></textarea>

                </div>

                <div class="form-section-label">
                    Assignment Details
                </div>

                <div class="form-grid-2">

                    <div class="form-group">

                        <label for="editDocumentRequestType">
                            Request Type
                        </label>

                        <select
                            id="editDocumentRequestType"
                            name="request_type">

                            <?php foreach (Onboarding::REQUEST_TYPES as $type => $icon): ?>

                                <option value="<?= htmlspecialchars($type) ?>">
                                    <?= htmlspecialchars($type) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="editDocumentActionType">
                            Required From Employee
                        </label>

                        <select
                            id="editDocumentActionType"
                            name="action_type">

                            <option value="document">
                                Upload a document
                            </option>

                            <option value="response">
                                Provide a written response
                            </option>

                            <option value="confirmation">
                                Confirmation only
                            </option>

                        </select>

                    </div>

                </div>

                <div class="form-grid-2">

                    <div class="form-group">

                        <label for="editDocumentPriority">
                            Priority
                        </label>

                        <select
                            id="editDocumentPriority"
                            name="priority">

                            <option value="Normal">
                                Normal
                            </option>

                            <option value="High">
                                High
                            </option>

                            <option value="Urgent">
                                Urgent
                            </option>

                        </select>

                    </div>

                    <div class="form-group">

                        <label for="editDocumentDueDate">
                            Due Date
                        </label>

                        <input
                            type="date"
                            id="editDocumentDueDate"
                            name="due_date">

                    </div>

                </div>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="modal-cancel"
                    id="cancelEditDocumentModal">
                    Delete Request
                </button>

                <button
                    type="submit"
                    class="modal-submit">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                </button>

            </div>

        </form>
    </div>
</div>

<div
    class="document-modal-overlay"
    id="viewDocumentModal"
    aria-hidden="true">

    <div
        class="document-modal view-document-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="dvModalTitle">

        <div class="dv-head">

            <div class="dv-head__text">

                <span class="dv-eyebrow">
                    Document Request
                </span>

                <h2 id="dvModalTitle">
                    —
                </h2>

                <div class="dv-tags">
                    <span class="dv-type" id="dvModalType">—</span>
                    <span class="ar-priority ar-priority--normal" id="dvModalPriority">Normal</span>
                </div>

            </div>

            <button
                type="button"
                class="modal-close"
                id="closeViewDocumentModal"
                aria-label="Close">
                <i class="fa-solid fa-xmark"></i>
            </button>

        </div>

        <div class="dv-body">

            <section class="ar-section">

                <h3>
                    <i class="fa-solid fa-circle-info"></i>
                    Request Information
                </h3>

                <div class="ar-info-grid">

                    <div class="ar-info">
                        <span class="ar-info__label">Request Type</span>
                        <strong class="ar-info__value" id="dvInfoType">—</strong>
                    </div>

                    <div class="ar-info">
                        <span class="ar-info__label">Required From Employee</span>
                        <strong class="ar-info__value" id="dvInfoAction">—</strong>
                    </div>

                    <div class="ar-info">
                        <span class="ar-info__label">Due Date</span>
                        <strong class="ar-info__value" id="dvInfoDue">—</strong>
                    </div>

                    <div class="ar-info">
                        <span class="ar-info__label">Priority</span>
                        <strong class="ar-info__value" id="dvInfoPriority">—</strong>
                    </div>

                    <div class="ar-info">
                        <span class="ar-info__label">Status</span>
                        <span class="badge badge-gray" id="dvInfoStatus">—</span>
                    </div>

                </div>

            </section>

            <section class="ar-section" id="dvInstructionSection" hidden>

                <h3>
                    <i class="fa-solid fa-list-check"></i>
                    Instructions Given
                </h3>

                <p class="ar-instruction" id="dvInstruction">—</p>

            </section>

            <section class="ar-section" id="dvSubmissionSection" hidden>
                <h3>
                    <i class="fa-solid fa-paperclip"></i>
                    Employee Submission
                </h3>

                <div class="ar-submission">
                    <p
                        class="ar-submission__text"
                        id="dvSubmittedText"
                        hidden>
                    </p>

                    <a
                        class="ar-submission__file"
                        id="dvSubmittedFile"
                        href="#"
                        target="_blank"
                        rel="noopener noreferrer"
                        hidden>
                        <i class="fa-solid fa-file-arrow-down"></i>
                        <span id="dvSubmittedFileName">document</span>
                    </a>
                </div>
            </section>

            <section class="ar-section" id="dvRemarksSection" hidden>

                <h3>
                    <i class="fa-solid fa-comment-dots"></i>
                    Review Remarks
                </h3>

                <p id="dvRemarks">—</p>

                <p class="ar-submission__meta" id="dvReviewMeta"></p>

            </section>

            <section class="ar-section">

                <h3>
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Activity
                </h3>

                <ul class="ar-timeline" id="dvTimeline"></ul>

            </section>

        </div>
        
        <div class="modal-footer dv-foot">
            <button
                type="button"
                class="modal-cancel"
                id="cancelViewDocumentModal">
                Close
            </button>
        </div>

    </div>
</div>


<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>