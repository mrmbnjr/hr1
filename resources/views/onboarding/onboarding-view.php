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
| TEMPORARY UI DATA
|--------------------------------------------------------------------------
| This is only for previewing the interface.
| We will replace these with controller/database data later.
*/

$employee = [
    'employee_id'     => 1001,
    'fullname'        => 'Juliana Dimaandal',
    'email'           => 'juliana.dimaandal@email.com',
    'department_name' => 'Product & Design',
    'job_title'       => 'UI/UX Designer',
    'employment_type' => 'Full-Time',
    'start_date'      => 'August 18, 2026',
    'status'          => 'In Progress'
];

$progress = 50;

$progressSteps = [

    [
        'title' => 'Employee Hired',
        'description' => 'Applicant was approved and marked as Hired.',
        'date' => 'Aug 10, 2026',
        'status' => 'completed'
    ],

    [
        'title' => 'Employee Record Created',
        'description' => 'Employee record was automatically created.',
        'date' => 'Aug 10, 2026',
        'status' => 'completed'
    ],

    [
        'title' => 'Documents Requested',
        'description' => 'Required onboarding documents have been requested.',
        'date' => 'Aug 11, 2026',
        'status' => 'current'
    ],

    [
        'title' => 'Documents Verified',
        'description' => 'All required documents must be verified by HR Staff.',
        'date' => null,
        'status' => 'pending'
    ],

    [
        'title' => 'Onboarding Completed',
        'description' => 'Employee has completed all onboarding requirements.',
        'date' => null,
        'status' => 'pending'
    ]

];

$documents = [

    [
        'name' => 'Signed Employment Contract',
        'status' => 'Verified',
        'status_class' => 'verified',
        'date' => 'Uploaded Aug 12, 2026'
    ],

    [
        'name' => 'SSS / PhilHealth / Pag-IBIG Numbers',
        'status' => 'Needs Review',
        'status_class' => 'review',
        'date' => 'Uploaded Aug 12, 2026'
    ],

    [
        'name' => 'Valid Government ID',
        'status' => 'Pending',
        'status_class' => 'pending',
        'date' => 'Not yet uploaded'
    ],

    [
        'name' => 'NBI Clearance',
        'status' => 'Pending',
        'status_class' => 'pending',
        'date' => 'Not yet uploaded'
    ]

];

$activities = [

    [
        'text' => 'HR Staff requested Valid Government ID.',
        'date' => 'Aug 14, 2026 • 9:42 AM'
    ],

    [
        'text' => 'HR Staff requested NBI Clearance.',
        'date' => 'Aug 14, 2026 • 9:40 AM'
    ],

    [
        'text' => 'Employee uploaded Signed Employment Contract.',
        'date' => 'Aug 12, 2026 • 2:15 PM'
    ],

    [
        'text' => 'Employment Contract was verified by HR Staff.',
        'date' => 'Aug 13, 2026 • 4:20 PM'
    ],

    [
        'text' => 'Onboarding record was created.',
        'date' => 'Aug 11, 2026 • 10:05 AM'
    ]

];

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>

    <div class="onboarding-view-page">

        <!-- ==========================================================
            BACK
        =========================================================== -->

        <a href="?page=onboarding" class="back-link">
            <i class="fa-solid fa-arrow-left"></i>
            Back to New Hire Onboarding
        </a>


        <!-- ==========================================================
            EMPLOYEE HEADER
        =========================================================== -->

        <section class="employee-header-card">

            <div class="employee-header-left">

                <div class="employee-avatar">
                    <?= strtoupper(substr($employee['fullname'], 0, 1)) ?>
                </div>

                <div class="employee-heading">

                    <h1>
                        <?= htmlspecialchars($employee['fullname']) ?>
                    </h1>

                    <p class="employee-position">
                        <?= htmlspecialchars($employee['job_title']) ?>
                        <span>•</span>
                        <?= htmlspecialchars($employee['department_name']) ?>
                    </p>

                    <div class="employee-meta">

                        <span>
                            <i class="fa-solid fa-id-card"></i>
                            Employee ID: <?= htmlspecialchars($employee['employee_id']) ?>
                        </span>

                        <span>
                            <i class="fa-solid fa-briefcase"></i>
                            <?= htmlspecialchars($employee['employment_type']) ?>
                        </span>

                        <span>
                            <i class="fa-solid fa-calendar"></i>
                            Start Date: <?= htmlspecialchars($employee['start_date']) ?>
                        </span>

                    </div>

                </div>

            </div>

            <span class="onboarding-status-badge">
                <span></span>
                <?= htmlspecialchars($employee['status']) ?>
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
                            <h2>Onboarding Progress</h2>
                            <p>Track the employee's onboarding journey.</p>
                        </div>
                    </div>

                    <span class="progress-count">
                        2 of 5
                    </span>

                </div>


                <!-- Progress bar -->

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


                <!-- Timeline -->

                <div class="progress-timeline">

                    <?php foreach ($progressSteps as $step): ?>

                        <div class="progress-step <?= $step['status'] ?>">

                            <div class="step-marker">

                                <?php if ($step['status'] === 'completed'): ?>

                                    <i class="fa-solid fa-check"></i>

                                <?php elseif ($step['status'] === 'current'): ?>

                                    <i class="fa-solid fa-circle"></i>

                                <?php else: ?>

                                    <i class="fa-regular fa-circle"></i>

                                <?php endif; ?>

                            </div>


                            <div class="step-content">

                                <div class="step-top">

                                    <h3>
                                        <?= htmlspecialchars($step['title']) ?>
                                    </h3>

                                    <?php if ($step['date']): ?>

                                        <span>
                                            <?= htmlspecialchars($step['date']) ?>
                                        </span>

                                    <?php else: ?>

                                        <span>Pending</span>

                                    <?php endif; ?>

                                </div>

                                <p>
                                    <?= htmlspecialchars($step['description']) ?>
                                </p>

                            </div>

                        </div>

                    <?php endforeach; ?>

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
                            <h2>Document Checklist</h2>
                            <p>Documents requested from this employee.</p>
                        </div>

                    </div>

                    <span class="document-count">
                        2 / <?= count($documents) ?>
                    </span>

                </div>


                <!-- Request Button -->

                <button
                    type="button"
                    class="request-document-btn"
                    id="openDocumentModal">

                    <i class="fa-solid fa-plus"></i>

                    Request Document

                </button>


                <!-- Documents -->

                <div class="document-list">

                    <?php foreach ($documents as $document): ?>

                        <div class="document-item">

                            <div class="document-icon">

                                <i class="fa-regular fa-file-lines"></i>

                            </div>

                            <div class="document-info">

                                <h3>
                                    <?= htmlspecialchars($document['name']) ?>
                                </h3>

                                <span>
                                    <?= htmlspecialchars($document['date']) ?>
                                </span>

                            </div>

                            <span
                                class="document-status <?= htmlspecialchars($document['status_class']) ?>">

                                <?= htmlspecialchars($document['status']) ?>

                            </span>

                            <button
                                type="button"
                                class="document-action"
                                title="Document options">

                                <i class="fa-solid fa-ellipsis-vertical"></i>

                            </button>

                        </div>

                    <?php endforeach; ?>

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
                            <h2>Activity Log</h2>
                            <p>Recent onboarding activities.</p>
                        </div>

                    </div>

                </div>


                <div class="activity-list">

                    <?php foreach ($activities as $activity): ?>

                        <div class="activity-item">

                            <span class="activity-dot"></span>

                            <div class="activity-content">

                                <p>
                                    <?= htmlspecialchars($activity['text']) ?>
                                </p>

                                <span>
                                    <?= htmlspecialchars($activity['date']) ?>
                                </span>

                            </div>

                        </div>

                    <?php endforeach; ?>

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


        <form id="requestDocumentForm">

            <div class="form-group">

                <label for="documentType">
                    Document Type
                </label>

                <select id="documentType" name="document_type">

                    <option value="">
                        Select document
                    </option>

                    <option value="government_id">
                        Valid Government ID
                    </option>

                    <option value="nbi_clearance">
                        NBI Clearance
                    </option>

                    <option value="sss">
                        SSS Number
                    </option>

                    <option value="philhealth">
                        PhilHealth Number
                    </option>

                    <option value="pagibig">
                        Pag-IBIG Number
                    </option>

                    <option value="medical_certificate">
                        Medical Certificate
                    </option>

                    <option value="other">
                        Other
                    </option>

                </select>

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