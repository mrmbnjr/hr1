<?php

$pageTitle       = "Onboarding Details";
$pageCSS         = "onboarding.css";
$pageJS          = "onboarding.js";
$pageDescription = "Manage employee onboarding requirements.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
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


$employmentType =
    $employee['employment_type'] ?? '';


$status =
    $employee['onboarding_status'] ?? 'Pending';


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

$verifiedDocuments =
    (int) ($documentProgress['verified'] ?? 0);

$submittedDocuments =
    (int) ($documentProgress['submitted'] ?? 0);

$pendingDocuments =
    (int) ($documentProgress['pending'] ?? 0);


/*
|--------------------------------------------------------------------------
| ONBOARDING ID
|--------------------------------------------------------------------------
*/

$onboardingId =
    (int) ($employee['onboarding_id'] ?? 0);

?>

<?php require '../resources/views/includes/header.php'; ?>

<?php require '../resources/views/includes/sidebar.php'; ?>


<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>


    <div class="onboarding-view-page">


        <!-- ==========================================================
            BACK
        =========================================================== -->

        <a
            href="?page=onboarding"
            class="back-link">

            <i class="fa-solid fa-arrow-left"></i>

            Back to New Hire Onboarding

        </a>


        <!-- ==========================================================
            EMPLOYEE HEADER
        =========================================================== -->

        <section class="employee-header-card">

            <div class="employee-header-left">


                <div class="employee-avatar">

                    <?= htmlspecialchars(
                        $avatarInitial
                    ) ?>

                </div>


                <div class="employee-heading">

                    <h1>
                        <?= htmlspecialchars(
                            $fullname
                        ) ?>
                    </h1>


                    <p class="employee-position">

                        <?= htmlspecialchars(
                            $jobTitle
                        ) ?>

                        <span>•</span>

                        <?= htmlspecialchars(
                            $department
                        ) ?>

                    </p>


                    <div class="employee-meta">


                        <span>

                            <i class="fa-solid fa-id-card"></i>

                            Employee ID:
                            <?= htmlspecialchars(
                                $employeeId
                            ) ?>

                        </span>


                        <?php if ($employmentType !== ''): ?>

                            <span>

                                <i class="fa-solid fa-briefcase"></i>

                                <?= htmlspecialchars(
                                    $employmentType
                                ) ?>

                            </span>

                        <?php endif; ?>


                        <span>

                            <i class="fa-solid fa-calendar"></i>

                            Start Date:
                            <?= htmlspecialchars(
                                $startDate
                            ) ?>

                        </span>


                    </div>

                </div>

            </div>


            <span class="onboarding-status-badge">

                <span></span>

                <?= htmlspecialchars(
                    $status
                ) ?>

            </span>

        </section>


        <!-- ==========================================================
            MAIN GRID
        =========================================================== -->

        <div class="onboarding-detail-grid">


            <!-- ======================================================
                ONBOARDING PROGRESS
            ======================================================= -->

            <section class="detail-card progress-card">


                <div class="card-header">


                    <div>

                        <span class="card-icon">

                            <i class="fa-solid fa-route"></i>

                        </span>


                        <div>

                            <h2>
                                Onboarding Progress
                            </h2>

                            <p>
                                Track the employee's onboarding journey.
                            </p>

                        </div>

                    </div>


                    <span class="progress-count">

                        <?php

                        $completedStepCount = 0;

                        foreach ($progressSteps as $step) {

                            if (
                                ($step['status'] ?? '')
                                === 'completed'
                            ) {
                                $completedStepCount++;
                            }
                        }

                        ?>

                        <?= $completedStepCount ?>
                        of
                        <?= count($progressSteps) ?>

                    </span>

                </div>


                <!-- ==================================================
                    PROGRESS BAR
                =================================================== -->

                <div class="overall-progress">


                    <div class="progress-label">

                        <span>
                            Overall Progress
                        </span>

                        <strong>
                            <?= $progress ?>%
                        </strong>

                    </div>


                    <div class="progress-track">

                        <div
                            class="progress-fill"
                            style="width: <?= $progress ?>%;">
                        </div>

                    </div>

                </div>


                <!-- ==================================================
                    TIMELINE
                =================================================== -->

                <div class="progress-timeline">


                    <?php if (!empty($progressSteps)): ?>


                        <?php foreach ($progressSteps as $step): ?>


                            <div
                                class="progress-step <?= htmlspecialchars(
                                    $step['status'] ?? 'pending'
                                ) ?>">


                                <div class="step-marker">


                                    <?php if (
                                        ($step['status'] ?? '')
                                        === 'completed'
                                    ): ?>

                                        <i class="fa-solid fa-check"></i>


                                    <?php elseif (
                                        ($step['status'] ?? '')
                                        === 'current'
                                    ): ?>

                                        <i class="fa-solid fa-circle"></i>


                                    <?php else: ?>

                                        <i class="fa-regular fa-circle"></i>

                                    <?php endif; ?>


                                </div>


                                <div class="step-content">


                                    <div class="step-top">


                                        <h3>
                                            <?= htmlspecialchars(
                                                $step['title'] ?? ''
                                            ) ?>
                                        </h3>


                                        <?php if (
                                            !empty($step['date'])
                                        ): ?>

                                            <span>
                                                <?= htmlspecialchars(
                                                    $step['date']
                                                ) ?>
                                            </span>

                                        <?php else: ?>

                                            <span>
                                                Pending
                                            </span>

                                        <?php endif; ?>


                                    </div>


                                    <p>

                                        <?= htmlspecialchars(
                                            $step['description'] ?? ''
                                        ) ?>

                                    </p>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="empty-state">

                            <p>
                                No onboarding progress available.
                            </p>

                        </div>


                    <?php endif; ?>


                </div>


            </section>


            <!-- ======================================================
                DOCUMENT CHECKLIST
            ======================================================= -->

            <section class="detail-card documents-card">


                <div class="card-header">


                    <div>


                        <span class="card-icon">

                            <i class="fa-solid fa-file-circle-check"></i>

                        </span>


                        <div>

                            <h2>
                                Document Checklist
                            </h2>

                            <p>
                                Documents requested from this employee.
                            </p>

                        </div>


                    </div>


                    <span class="document-count">

                        <?= $verifiedDocuments ?>
                        /
                        <?= $totalDocuments ?>

                    </span>


                </div>


                <!-- ==================================================
                    REQUEST BUTTON
                =================================================== -->

                <button
                    type="button"
                    class="request-document-btn"
                    id="openDocumentModal">

                    <i class="fa-solid fa-plus"></i>

                    Request Document

                </button>


                <!-- ==================================================
                    DOCUMENTS
                =================================================== -->

                <div class="document-list">


                    <?php if (!empty($documents)): ?>


                        <?php foreach ($documents as $document): ?>


                            <?php

                            $documentStatus =
                                $document['status'] ?? 'Pending';


                            switch ($documentStatus) {

                                case 'Verified':

                                    $statusClass =
                                        'verified';

                                    break;


                                case 'Submitted':

                                    $statusClass =
                                        'review';

                                    break;


                                case 'Pending':

                                default:

                                    $statusClass =
                                        'pending';

                                    break;
                            }


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

                                        <?php if (
                                            !empty(
                                                $document['file_path']
                                            )
                                        ): ?>

                                            Document uploaded

                                        <?php else: ?>

                                            Not yet uploaded

                                        <?php endif; ?>

                                    </span>


                                </div>


                                <span
                                    class="document-status <?= htmlspecialchars(
                                        $statusClass
                                    ) ?>">

                                    <?= htmlspecialchars(
                                        $documentStatus
                                    ) ?>

                                </span>


                                <button
                                    type="button"
                                    class="document-action"
                                    title="Document options">

                                    <i class="fa-solid fa-ellipsis-vertical"></i>

                                </button>


                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="empty-state">

                            <i class="fa-regular fa-folder-open"></i>

                            <p>
                                No documents have been requested yet.
                            </p>

                        </div>


                    <?php endif; ?>


                </div>


            </section>


            <!-- ======================================================
                ACTIVITY LOG
            ======================================================= -->

            <section class="detail-card activity-card">


                <div class="card-header">


                    <div>


                        <span class="card-icon">

                            <i class="fa-solid fa-clock-rotate-left"></i>

                        </span>


                        <div>

                            <h2>
                                Activity Log
                            </h2>

                            <p>
                                Recent onboarding activities.
                            </p>

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

                                        <?= htmlspecialchars(
                                            $activity['text'] ?? ''
                                        ) ?>

                                    </p>


                                    <span>

                                        <?= htmlspecialchars(
                                            $activity['date'] ?? ''
                                        ) ?>

                                    </span>


                                </div>


                            </div>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <div class="empty-state">

                            <p>
                                No onboarding activity yet.
                            </p>

                        </div>


                    <?php endif; ?>


                </div>


            </section>


        </div>

    </div>


    <?php require '../resources/views/includes/footer.php'; ?>


</div>



<!-- ==============================================================
    REQUEST DOCUMENT MODAL
=============================================================== -->

<div
    class="document-modal-overlay"
    id="documentModal"
    aria-hidden="true">


    <div
        class="document-modal"
        role="dialog"
        aria-modal="true"
        aria-labelledby="documentModalTitle">


        <!-- ======================================================
            MODAL HEADER
        ======================================================= -->

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



        <!-- ======================================================
            REQUEST FORM
        ======================================================= -->
        <form
            method="POST"
            action="?page=onboarding-request-document"
            id="requestDocumentForm">

            <input
                type="hidden"
                name="onboarding_id"
                value="<?= (int) $onboardingId ?>">

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

                    <span>Optional</span>

                </label>

                <textarea
                    id="documentDescription"
                    name="description"
                    rows="4"
                    placeholder="Enter instructions or additional information for the employee..."></textarea>

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


<?php require '../resources/views/includes/scripts.php'; ?>