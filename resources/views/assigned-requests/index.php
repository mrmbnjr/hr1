<?php

use App\Services\AssignedDocumentService;
use App\Services\Auth;


/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$pageTitle =
    "Assigned Requests";

$pageCSS =
    "assigned-requests.css";

$pageJS =
    "assigned-requests.js";

$pageDescription =
    "View and complete requests assigned to you by HR or Administration.";


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
| Data supplied by the controller
|--------------------------------------------------------------------------
*/

$requests =
    $requests ?? [];

$activitiesByRequest =
    $activitiesByRequest ?? [];

$requestTypes =
    $requestTypes ?? [];


/*
|--------------------------------------------------------------------------
| View helpers
|--------------------------------------------------------------------------
| Closures rather than named functions — this view can be re-included.
*/

$e = static fn($value): string =>
    htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

/** Formats a date, or an em dash when there is nothing to show. */
$fmt = static function ($value, string $format = 'F j, Y'): string {

    $value = trim((string) ($value ?? ''));

    if ($value === '') {
        return '—';
    }

    $timestamp = strtotime($value);

    return $timestamp ? date($format, $timestamp) : '—';
};

/** Formats a timestamp for the activity timeline. */
$fmtMoment = static fn($value): array => [
    'date' => $fmt($value, 'F j, Y'),
    'time' => $fmt($value, 'g:i A'),
];


/*
|--------------------------------------------------------------------------
| Presentation metadata
|--------------------------------------------------------------------------
| One source of truth for labels, tones and glyphs, shared by the list
| and the detail modal.
*/

$statusMeta = [

    'Pending Action' => [
        'label' => 'Pending Action',
        'class' => 'badge-orange',
        'tone' => 'pending'
    ],

    'In Progress' => [
        'label' => 'In Progress',
        'class' => 'badge-blue',
        'tone' => 'progress'
    ],

    'Approved' => [
        'label' => 'Approved',
        'class' => 'badge-green',
        'tone' => 'approved'
    ],

];

$priorityMeta = [

    'Normal' => [
        'label' => 'Normal',
        'tone' => 'normal'
    ],

    'High' => [
        'label' => 'High',
        'tone' => 'high'
    ],

    'Urgent' => [
        'label' => 'Urgent',
        'tone' => 'urgent'
    ]

];

$actionMeta = [

    'document' => [
        'label' => 'Document submission',
        'icon' => 'fa-file-arrow-up',
        'fallback' => 'Please upload the requested document.'
    ],

    'response' => [
        'label' => 'Written response',
        'icon' => 'fa-pen-to-square',
        'fallback' => 'Please provide the requested information.'
    ],

    'confirmation' => [
        'label' => 'Confirmation required',
        'icon' => 'fa-circle-check',
        'fallback' => 'Please review the details and confirm.'
    ]

];

$eventIcon = [
    'created' => 'fa-plus',
    'assigned' => 'fa-user-check',
    'submitted' => 'fa-paper-plane',
    'uploaded' => 'fa-file-arrow-up',
    'reviewed' => 'fa-magnifying-glass'
];


/*
|--------------------------------------------------------------------------
| Filter options
|--------------------------------------------------------------------------
| Built from the requests actually on the page, so the "Request Type"
| filter never offers a type the employee has nothing for.
*/

$requestTypeOptions = [];

foreach ($requests as $request) {

    $requestType = trim(
        (string) ($request['request_type'] ?? '')
    );

    if ($requestType !== '') {
        $requestTypeOptions[$requestType] = true;
    }
}

$requestTypeOptions = array_keys($requestTypeOptions);

sort($requestTypeOptions);


/*
|--------------------------------------------------------------------------
| Feedback messages
|--------------------------------------------------------------------------
*/

$success =
    $_GET['success'] ?? '';

$error =
    $_GET['error'] ?? '';

$errorMessage =
    $_GET['message'] ?? '';

?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>

<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>


<div class="main-content">

    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>


    <div class="assigned-requests-page">


        <!-- ======================================================
             SUCCESS / ERROR MESSAGES
        ======================================================= -->

        <?php if ($success === 'submitted'): ?>

            <div class="alert alert-success">

                <i class="fa-solid fa-circle-check"></i>

                Your submission has been sent to HR.

            </div>

        <?php endif; ?>


        <?php if ($error !== ''): ?>

            <?php

            $resolvedError = match ($error) {

                'response-required' =>
                    'Please enter your response before submitting.',

                'document-required' =>
                    'Please attach the required document before submitting.',

                'upload' =>
                    $errorMessage !== ''
                        ? $errorMessage
                        : 'The document could not be uploaded. Please try again.',

                'response-length' =>
                    'Your response is too long. Please keep it under 2000 characters.',

                'already-approved' =>
                    'This request has already been approved.',

                'not-found' =>
                    'The request could not be found.',

                'invalid' =>
                    'Invalid request.',

                default =>
                    'Your submission could not be saved. Please try again.'

            };

            ?>

            <div class="alert alert-error">

                <i class="fa-solid fa-circle-exclamation"></i>

                <?= $e($resolvedError) ?>

            </div>

        <?php endif; ?>


<!-- ======================================================
             FILTER BAR
        ======================================================= -->

        <section class="filter-bar">

            <div class="filter-controls">

                <select id="assignedTypeFilter">

                    <option value="All">
                        All Request Types
                    </option>

                    <?php foreach ($requestTypeOptions as $type): ?>

                        <option value="<?= $e($type) ?>">
                            <?= $e($type) ?>
                        </option>

                    <?php endforeach; ?>

                </select>


                <select id="assignedStatusFilter">

                    <option value="All">
                        All Status
                    </option>

                    <option value="pending">
                        Pending Action
                    </option>

                    <option value="progress">
                        In Progress
                    </option>

                    <option value="approved">
                        Approved
                    </option>

                </select>


                <select id="assignedSortFilter">

                    <option value="newest">
                        Newest
                    </option>

                    <option value="oldest">
                        Oldest
                    </option>

                    <option value="due">
                        Due Date
                    </option>

                    <option value="priority">
                        Priority
                    </option>

                    <option value="type">
                        Request Type
                    </option>

                </select>

            </div>

        </section>


        <!-- ======================================================
             ASSIGNED REQUESTS TABLE
        ======================================================= -->

        <section class="table-card">

            <div class="table-scroll">

                <table
                    class="employee-request-table"
                    id="assignedRequestTable"
                >

                    <thead>

                        <tr>
                            <th>Request</th>
                            <th>Request Type</th>
                            <th>Assigned By</th>
                            <th>Date Assigned</th>
                            <th>Due Date</th>
                            <th>Priority</th>
                            <th>Status</th>
                        </tr>

                    </thead>


                    <tbody>

                        <?php if (empty($requests)): ?>

                            <tr class="empty-row">

                                <td colspan="7">

                                    <div class="empty-state">

                                        <i class="fa-solid fa-clipboard-check"></i>

                                        <h3>
                                            No Assigned Requests
                                        </h3>

                                        <p>
                                            You currently have no requests requiring your action.
                                        </p>

                                    </div>

                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($requests as $request): ?>

                                <?php

                                $requestId =
                                    (int) $request['request_id'];

                                $status =
                                    $request['status'] ?? 'Pending Action';

                                $statusInfo =
                                    $statusMeta[$status] ?? $statusMeta['Pending Action'];

                                $priority =
                                    $request['priority'] ?? 'Normal';

                                $priorityInfo =
                                    $priorityMeta[$priority] ?? $priorityMeta['Normal'];

                                $actionType =
                                    $request['action_type'] ?? 'document';

                                $actionInfo =
                                    $actionMeta[$actionType] ?? $actionMeta['document'];

                                $type =
                                    $request['request_type'] ?? 'Submit Required Document';

                                $typeIcon =
                                    $requestTypes[$type] ?? 'fa-circle-question';

                                $title =
                                    trim((string) ($request['title'] ?? '')) ?: $type;


                                /*
                                 * HR's instruction is the "required action". When HR
                                 * did not write one, fall back to a sentence that
                                 * matches what the employee is being asked to do.
                                 */
                                $description =
                                    trim((string) ($request['description'] ?? ''));

                                if ($description === '') {
                                    $description = $actionInfo['fallback'];
                                }

                                $isOverdue =
                                    !empty($request['is_overdue']);

                                $assignedBy =
                                    trim((string) ($request['assigned_by_name'] ?? ''))
                                    ?: 'HR';

                                $assignedByRole =
                                    trim((string) ($request['assigned_by_role'] ?? ''));

                                $dueDate =
                                    $request['due_date'] ?? null;


                                /*
                                |--------------------------------------------------------------------------
                                | Payload handed to the detail modal
                                |--------------------------------------------------------------------------
                                */

                                $payload = [

                                    'id' => $requestId,
                                    'title' => $title,
                                    'type' => $type,
                                    'typeIcon' => $typeIcon,
                                    'actionType' => $actionType,
                                    'actionLabel' => $actionInfo['label'],
                                    'priority' => $priority,
                                    'priorityTone' => $priorityInfo['tone'],
                                    'status' => $status,
                                    'statusLabel' => $statusInfo['label'],
                                    'statusClass' => $statusInfo['class'],
                                    'overdue' => $isOverdue,
                                    'assignedBy' => $assignedBy,
                                    'assignedByRole' => $assignedByRole,
                                    'assignedAt' => $fmt($request['created_at'] ?? null),
                                    'due' => $fmt($dueDate),
                                    'hasDue' => !empty($dueDate),
                                    'description' => $description,
                                    'response' => (string) ($request['response_text'] ?? ''),


                                    /*
                                     * Only advertise a download when the file is
                                     * actually on disk, so the page never renders a
                                     * dead link. The label is the request title
                                     * because the stored name is opaque.
                                     */

                                    'fileUrl' => !empty($request['has_file'])
                                        ? '?page=assigned-requests-download&request_id=' . $requestId
                                        : '',

                                    'fileOriginal' => !empty($request['has_file'])
                                        ? $title
                                        : '',

                                    'fileSize' => AssignedDocumentService::formatSize(
                                        isset($request['submission_file_size'])
                                            ? (int) $request['submission_file_size']
                                            : null
                                    ),
                                    'submittedAt' => $fmt($request['submitted_at'] ?? null, 'F j, Y — g:i A'),
                                    'reviewRemarks' => trim((string) ($request['review_remarks'] ?? '')),

                                ];


                                /*
                                |--------------------------------------------------------------------------
                                | Activity timeline
                                |--------------------------------------------------------------------------
                                */

                                $payload['activities'] = [];

                                foreach (
                                    $activitiesByRequest[$requestId] ?? []
                                    as $activity
                                ) {

                                    $moment = $fmtMoment($activity['created_at'] ?? null);

                                    $payload['activities'][] = [

                                        'date' => $moment['date'],
                                        'time' => $moment['time'],
                                        'description' => $activity['description'] ?? '',
                                        'icon' => $eventIcon[$activity['event'] ?? ''] ?? 'fa-circle'

                                    ];
                                }

                                ?>

                                <tr
                                    class="request-row"
                                    tabindex="0"
                                    role="button"
                                    aria-label="View request: <?= $e($title) ?>"

                                    data-ar-status="<?= $e($statusInfo['tone']) ?>"
                                    data-ar-type="<?= $e($type) ?>"
                                    data-ar-priority="<?= $e($priority) ?>"
                                    data-ar-overdue="<?= $isOverdue ? '1' : '0' ?>"
                                    data-ar-created="<?= $e($request['created_at'] ?? '') ?>"
                                    data-ar-due="<?= $e($dueDate ?? '') ?>"
                                    data-ar-request="<?= $e(json_encode($payload)) ?>"
                                >

                                    <!-- Request -->

                                    <td>

                                        <div class="request-cell">

                                            <div>

                                                <strong>
                                                    <?= $e($title) ?>
                                                </strong>

                                                <span class="sub-text">
                                                    <?= $e(mb_strimwidth($description, 0, 90, '…')) ?>
                                                </span>

                                            </div>

                                        </div>

                                    </td>


                                    <!-- Request Type -->

                                    <td>

                                        <button
                                            type="button"
                                            class="request-type request-view-btn"
                                            data-request-id="<?= $requestId ?>"
                                        >

                                            <?= $e($type) ?>

                                        </button>

                                    </td>


                                    <!-- Assigned By -->

                                    <td>

                                        <strong>
                                            <?= $e($assignedBy) ?>
                                        </strong>

                                        <?php if ($assignedByRole !== ''): ?>

                                            <span class="sub-text">
                                                <?= $e($assignedByRole) ?>
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Date Assigned -->

                                    <td>
                                        <?= $e($fmt($request['created_at'] ?? null)) ?>
                                    </td>


                                    <!-- Due Date -->

                                    <td class="due-date <?= $isOverdue ? 'is-overdue' : '' ?>">

                                        <?= $e($fmt($dueDate)) ?>

                                        <?php if ($isOverdue): ?>

                                            <span class="ar-overdue-chip">

                                                <i class="fa-solid fa-triangle-exclamation"></i>

                                                Overdue

                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- Priority -->

                                    <td>

                                        <span class="ar-priority ar-priority--<?= $e($priorityInfo['tone']) ?>">
                                            <?= $e($priorityInfo['label']) ?>
                                        </span>

                                    </td>


                                    <!-- Status -->

                                    <td>

                                        <span class="badge <?= $e($statusInfo['class']) ?>">
                                            <?= $e($statusInfo['label']) ?>
                                        </span>

                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>


                        <!-- Revealed by JS when the filters match nothing -->

                        <tr class="empty-row" id="assignedFilteredEmpty" hidden>

                            <td colspan="7">

                                <div class="empty-state">

                                    <i class="fa-solid fa-filter-circle-xmark"></i>

                                    <h3>
                                        Nothing Here
                                    </h3>

                                    <p>
                                        No assigned requests match the selected filters.
                                    </p>

                                </div>

                            </td>

                        </tr>

                    </tbody>

                </table>

            </div>

        </section>

    </div>


    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>

</div>


<!-- ==========================================================
     REQUEST DETAIL MODAL
=========================================================== -->

<div class="ar-modal" id="arModal" aria-hidden="true">

    <div class="ar-modal__backdrop" data-ar-close></div>


    <div
        class="ar-modal__dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="arModalTitle"
    >

        <header class="ar-modal__head">

            <div class="ar-modal__head-text">

                <span class="ar-modal__eyebrow">
                    Assigned Request
                </span>

                <h2 id="arModalTitle">
                    Request Details
                </h2>

                <div class="ar-modal__tags">

                    <span class="ar-modal__type" id="arModalType">
                        —
                    </span>

                    <span class="ar-priority ar-priority--normal" id="arModalPriority">
                        Normal
                    </span>

                </div>

            </div>


            <button
                type="button"
                class="ar-modal__close"
                data-ar-close
                aria-label="Close request details"
            >

                <i class="fa-solid fa-xmark"></i>

            </button>

        </header>


        <div class="ar-modal__body">


            <!-- ==================================================
                 REQUEST INFORMATION
            =================================================== -->

            <section class="ar-section">

                <h3>
                    <i class="fa-solid fa-circle-info"></i>
                    Request Information
                </h3>

                <div class="ar-info-grid">

                    <div class="ar-info">
                        <span class="ar-info__label">Request Type</span>
                        <strong class="ar-info__value" id="arInfoType">—</strong>
                    </div>

                    <div class="ar-info">
                        <span class="ar-info__label">Assigned By</span>
                        <strong class="ar-info__value" id="arInfoAssignedBy">—</strong>
                    </div>

                    <div class="ar-info">
                        <span class="ar-info__label">Date Assigned</span>
                        <strong class="ar-info__value" id="arInfoAssigned">—</strong>
                    </div>

                    <div class="ar-info">
                        <span class="ar-info__label">Due Date</span>
                        <strong class="ar-info__value" id="arInfoDue">—</strong>
                    </div>

                    <div class="ar-info">
                        <span class="ar-info__label">Priority</span>
                        <strong class="ar-info__value" id="arInfoPriority">—</strong>
                    </div>

                    <div class="ar-info">
                        <span class="ar-info__label">Status</span>
                        <span class="badge badge-orange" id="arInfoStatus">—</span>
                    </div>

                </div>

            </section>


            <!-- ==================================================
                 REQUIRED ACTION
            =================================================== -->

            <section class="ar-section">

                <h3>
                    <i class="fa-solid fa-list-check"></i>
                    Required Action
                </h3>

                <p class="ar-instruction" id="arInstruction">
                    —
                </p>


                <form
                    id="arForm"
                    class="ar-form"
                    method="POST"
                    action="?page=assigned-requests-submit"
                    enctype="multipart/form-data"
                >

                    <input type="hidden" name="request_id" id="arRequestId" value="">


                    <!-- Written response -->

                    <div class="ar-field" data-ar-block="response" hidden>

                        <label for="arResponse" class="ar-field__label">
                            Response / Remarks
                        </label>

                        <textarea
                            id="arResponse"
                            name="response"
                            rows="5"
                            maxlength="2000"
                            placeholder="Type your response here..."
                        ></textarea>

                        <p class="ar-field__hint" id="arResponseHint"></p>

                    </div>


                    <!-- Document upload -->

                    <div class="ar-field" data-ar-block="document" hidden>

                        <span class="ar-field__label">
                            Upload Document
                        </span>

                        <input
                            type="file"
                            id="arFile"
                            name="document"
                            accept=".pdf,.doc,.docx"
                            hidden
                        >

                        <label for="arFile" class="ar-upload" id="arUpload">

                            <i class="fa-solid fa-cloud-arrow-up"></i>

                            <span class="ar-upload__title">
                                Choose a file to upload
                            </span>

                            <span class="ar-upload__hint">
                                PDF, DOC, or DOCX · up to 5 MB
                            </span>

                        </label>

                        <div class="ar-file" id="arFileChip" hidden>

                            <span class="ar-file__icon">
                                <i class="fa-solid fa-file"></i>
                            </span>

                            <div class="ar-file__info">

                                <strong id="arFileName">—</strong>

                                <span id="arFileMeta">—</span>

                            </div>

                            <button
                                type="button"
                                class="ar-file__remove"
                                id="arFileRemove"
                            >

                                <i class="fa-solid fa-xmark"></i>
                                Remove

                            </button>

                        </div>

                    </div>


                    <!-- Confirmation only -->

                    <div class="ar-field ar-field--confirm" data-ar-block="confirmation" hidden>

                        <i class="fa-solid fa-circle-check"></i>

                        <p>
                            This request only needs your confirmation. Review the
                            details above, then confirm to complete it.
                        </p>

                    </div>

                </form>


                <!-- Submitted response / document (read-only) -->

                <div class="ar-submission" data-ar-block="submitted" hidden>

                    <h4>
                        Your Submission
                    </h4>

                    <p class="ar-submission__text" id="arSubmittedText" hidden></p>

                    <a
                        class="ar-submission__file"
                        id="arSubmittedFile"
                        href="#"
                        hidden
                    >

                        <i class="fa-solid fa-file-arrow-down"></i>

                        <span id="arSubmittedFileName">document</span>

                        <span class="ar-submission__size" id="arSubmittedFileSize"></span>

                    </a>

                    <p class="ar-submission__meta" id="arSubmittedAt"></p>

                </div>


                <!-- HR remarks -->

                <div class="ar-remarks" data-ar-block="remarks" hidden>

                    <h4>
                        <i class="fa-solid fa-comment-dots"></i>
                        HR Remarks
                    </h4>

                    <p id="arRemarks">—</p>

                </div>

            </section>


            <!-- ==================================================
                 ACTIVITY
            =================================================== -->

            <section class="ar-section">

                <h3>
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    Activity
                </h3>

                <ul class="ar-timeline" id="arTimeline"></ul>

            </section>

        </div>


        <footer class="ar-modal__foot">

            <button type="button" class="btn-secondary" data-ar-close>
                Close
            </button>

            <button
                type="submit"
                class="btn-primary"
                id="arSubmitBtn"
                form="arForm"
            >

                <i class="fa-solid fa-paper-plane"></i>
                Submit

            </button>

        </footer>

    </div>

</div>


<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>
