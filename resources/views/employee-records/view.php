<?php

$pageTitle       = "Employee Record";
$pageCSS         = "employee-records.css";
$pageJS          = "employee-records.js";
$pageDescription = "View employee information and employment records.";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url("?page=login"));
    exit;
}

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
| $employee should be provided by EmployeeRecordsController.
*/

$employee = $employee ?? [];
$activities = $activities ?? [];

$activityDeviceLabel = static function (?string $userAgent): string {
    $userAgent = $userAgent ?? '';

    $device = preg_match('/Mobile|Android|iPhone|iPad/i', $userAgent)
        ? 'Mobile device'
        : 'Desktop device';

    $operatingSystem = match (true) {
        preg_match('/Windows/i', $userAgent) === 1 => 'Windows',
        preg_match('/Mac OS|Macintosh/i', $userAgent) === 1 => 'macOS',
        preg_match('/Android/i', $userAgent) === 1 => 'Android',
        preg_match('/iPhone|iPad|iPod/i', $userAgent) === 1 => 'iOS',
        preg_match('/Linux/i', $userAgent) === 1 => 'Linux',
        default => 'Unknown OS'
    };

    $browser = match (true) {
        preg_match('/Edg/i', $userAgent) === 1 => 'Edge',
        preg_match('/Chrome/i', $userAgent) === 1 => 'Chrome',
        preg_match('/Firefox/i', $userAgent) === 1 => 'Firefox',
        preg_match('/Safari/i', $userAgent) === 1 => 'Safari',
        default => 'Unknown browser'
    };

    return $device . ' · ' . $operatingSystem . ' · ' . $browser;
};

/*
|--------------------------------------------------------------------------
| EMPLOYEE DATA
|--------------------------------------------------------------------------
*/

$fullname = $employee['fullname'] ?? 'Unknown Employee';
$email = $employee['email'] ?? '—';
$employeeNumber = $employee['employee_number'] ?? '—';
$department = $employee['department_name'] ?? '—';
$jobTitle = $employee['job_title'] ?? '—';
$employmentType = $employee['employment_type'] ?? '—';
$employmentStatus = $employee['employment_status'] ?? '—';
$hireDate = $employee['hire_date'] ?? '—';
$applicationId = $employee['application_id'] ?? '—';
$applicantId = $employee['applicant_id'] ?? '—';
$employeeId = $employee['employee_id'] ?? '—';
$applicationStatus = $employee['application_status'] ?? '—';
$applicationDate = $employee['applied_at'] ?? '—';
$postingTitle = $employee['posting_title'] ?? '—';

if ($applicationDate !== '' && $applicationDate !== '—') {
    try {
        $applicationDate = (new DateTimeImmutable($applicationDate))->format('F j, Y');
    } catch (Throwable $exception) {
        $applicationDate = '—';
    }
}

$documents = [];

if (!empty($employee['resume_file'])) {
    $documents[] = [
        'label' => 'Resume',
        'file' => $employee['resume_file'],
        'icon' => 'fa-file-lines',
        'viewUrl' => '?page=download-resume&id=' . (int) $applicationId,
        'downloadUrl' => '?page=download-resume&id=' . (int) $applicationId . '&download=1'
    ];
}

if (!empty($employee['academic_document_file'])) {
    $documents[] = [
        'label' => 'Certificate / Academic Document',
        'file' => $employee['academic_document_file'],
        'icon' => 'fa-graduation-cap',
        'viewUrl' => '?page=download-academic-document&id=' . (int) $applicationId,
        'downloadUrl' => '?page=download-academic-document&id=' . (int) $applicationId . '&download=1'
    ];
}

$profileFields = [
    $email,
    $employeeNumber,
    $department,
    $jobTitle,
    $employmentType,
    $employmentStatus,
    $hireDate
];
$profileCompletion = (int) round(
    count(array_filter($profileFields, static fn ($value) => $value !== '' && $value !== '—'))
    / count($profileFields)
    * 100
);

$tenure = '—';

if ($hireDate !== '' && $hireDate !== '—') {
    try {
        $hireDateObject = new DateTimeImmutable($hireDate);
        $tenurePeriod = $hireDateObject->diff(new DateTimeImmutable());
        $tenureParts = [];

        if ($tenurePeriod->y > 0) {
            $tenureParts[] = $tenurePeriod->y . ' yr' . ($tenurePeriod->y === 1 ? '' : 's');
        }

        if ($tenurePeriod->m > 0 || empty($tenureParts)) {
            $tenureParts[] = $tenurePeriod->m . ' mo' . ($tenurePeriod->m === 1 ? '' : 's');
        }

        $tenure = implode(' ', $tenureParts);
    } catch (Throwable $exception) {
        $tenure = '—';
    }
}

$statusClass = match ($employmentStatus) {
    'Regular' => 'badge-green',
    'Probationary' => 'badge-orange',
    'Contract' => 'badge-blue',
    default => 'badge-gray'
};

$initial = strtoupper(
    substr(trim($fullname), 0, 1)
);

?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>

    <div class="employee-record-page">

        <?php if (empty($employee)): ?>

            <section class="table-card">

                <div class="empty-state">
                    <i class="fa-solid fa-user-slash"></i>

                    <h3>Employee Record Not Found</h3>

                    <p>
                        The employee record you're looking for could not be found.
                    </p>

                </div>

            </section>

        <?php else: ?>

            <section class="employee-view-navigation">

                <button
                    type="button"
                    class="employee-view-tab active"
                    id="overviewTab"
                    data-view="overview"
                    role="tab"
                    aria-selected="true"
                    aria-controls="overviewView"
                    tabindex="0"
                >
                    <i class="fa-solid fa-user"></i>
                    <span>Overview</span>
                </button>

                <button
                    type="button"
                    class="employee-view-tab"
                    id="employmentTab"
                    data-view="employment"
                    role="tab"
                    aria-selected="false"
                    aria-controls="employmentView"
                    tabindex="-1"
                >
                    <i class="fa-solid fa-briefcase"></i>
                    <span>Employment</span>
                </button>

                <button
                    type="button"
                    class="employee-view-tab"
                    id="documentsTab"
                    data-view="documents"
                    role="tab"
                    aria-selected="false"
                    aria-controls="documentsView"
                    tabindex="-1"
                >
                    <i class="fa-solid fa-file-lines"></i>
                    <span>Documents</span>
                </button>

                <button
                    type="button"
                    class="employee-view-tab"
                    id="notesTab"
                    data-view="notes"
                    role="tab"
                    aria-selected="false"
                    aria-controls="notesView"
                    tabindex="-1"
                >
                    <i class="fa-solid fa-note-sticky"></i>
                    <span>Notes</span>
                </button>

                <button
                    type="button"
                    class="employee-view-tab"
                    id="activityTab"
                    data-view="activity"
                    role="tab"
                    aria-selected="false"
                    aria-controls="activityView"
                    tabindex="-1"
                >
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Activity</span>
                </button>

            </section>

            <div class="employee-view-wrapper">

                <div class="employee-view-slider">

                    <!-- ==================================================
                         OVERVIEW
                    =================================================== -->

                    <section
                        class="employee-view-panel"
                        id="overviewView"
                        role="tabpanel"
                        aria-labelledby="overviewTab"
                    >

                        <section class="employee-profile-card">

                            <div class="employee-profile-main">

                                <div class="avatar-large">
                                    <?= htmlspecialchars($initial) ?>
                                </div>

                                <div class="employee-profile-info">

                                    <h1>
                                        <?= htmlspecialchars($fullname) ?>
                                    </h1>

                                    <p class="employee-position">
                                        <?= htmlspecialchars($jobTitle) ?>

                                        <span class="profile-separator">•</span>

                                        <?= htmlspecialchars($department) ?>
                                    </p>

                                    <div class="employee-profile-meta">

                                        <span>
                                            <?= htmlspecialchars($employeeNumber) ?>
                                        </span>

                                        <span class="profile-separator">•</span>

                                        <span>
                                            <?= htmlspecialchars($employmentStatus) ?>
                                        </span>

                                        <span class="profile-separator">•</span>

                                        <span>
                                            Joined <?= htmlspecialchars($hireDate) ?>
                                        </span>

                                    </div>

                                </div>

                            </div>

                        </section>

                        <section class="overview-stat-grid" aria-label="Employee overview statistics">

                            <div class="overview-stat-card overview-stat-card-status">
                                <span class="overview-stat-icon" aria-hidden="true">
                                    <i class="fa-solid fa-circle-check"></i>
                                </span>
                                <div>
                                    <span class="overview-stat-label">Status</span>
                                    <strong><?= htmlspecialchars($employmentStatus) ?></strong>
                                    <small>Current employment</small>
                                </div>
                            </div>

                            <div class="overview-stat-card">
                                <span class="overview-stat-icon" aria-hidden="true">
                                    <i class="fa-solid fa-hourglass-half"></i>
                                </span>
                                <div>
                                    <span class="overview-stat-label">Tenure</span>
                                    <strong><?= htmlspecialchars($tenure) ?></strong>
                                    <small>Since <?= htmlspecialchars($hireDate) ?></small>
                                </div>
                            </div>

                            <div class="overview-stat-card">
                                <span class="overview-stat-icon" aria-hidden="true">
                                    <i class="fa-solid fa-file-circle-check"></i>
                                </span>
                                <div>
                                    <span class="overview-stat-label">Documents</span>
                                    <strong><?= count($documents) ?></strong>
                                    <small>Submitted files</small>
                                </div>
                            </div>

                            <div class="overview-stat-card overview-stat-card-meter">
                                <div class="overview-meter" style="--completion: <?= $profileCompletion ?>%;" aria-label="Profile <?= $profileCompletion ?> percent complete">
                                    <span><?= $profileCompletion ?>%</span>
                                </div>
                                <div>
                                    <span class="overview-stat-label">Profile</span>
                                    <strong><?= $profileCompletion === 100 ? 'Complete' : 'In progress' ?></strong>
                                    <small>Information available</small>
                                </div>
                            </div>

                        </section>

                        <section class="record-card overview-completeness-card">

                            <div class="record-card-header">
                                <div>
                                    <h2>Record Completeness</h2>
                                    <p>Quick view of the information currently available in this employee record.</p>
                                </div>
                                <strong class="overview-completeness-value"><?= $profileCompletion ?>%</strong>
                            </div>

                            <div class="overview-progress-track" role="progressbar" aria-valuenow="<?= $profileCompletion ?>" aria-valuemin="0" aria-valuemax="100" aria-label="Record completeness">
                                <span style="width: <?= $profileCompletion ?>%;"></span>
                            </div>

                            <div class="overview-progress-meta">
                                <span>Identity and employment details</span>
                                <span><?= count(array_filter($profileFields, static fn ($value) => $value !== '' && $value !== '—')) ?>/<?= count($profileFields) ?> fields populated</span>
                            </div>

                        </section>

                        <div class="overview-grid">

                            <section class="record-card">

                                <div class="record-card-header">
                                    <div>
                                        <h2>Personal Information</h2>
                                        <p>
                                            Basic information associated with the employee.
                                        </p>
                                    </div>
                                </div>

                                <div class="record-grid">

                                    <div class="record-item">
                                        <span class="record-label">Full Name</span>
                                        <strong>
                                            <?= htmlspecialchars($fullname) ?>
                                        </strong>
                                    </div>

                                    <div class="record-item">
                                        <span class="record-label">Email</span>
                                        <strong>
                                            <?= htmlspecialchars($email) ?>
                                        </strong>
                                    </div>

                                    <div class="record-item">
                                        <span class="record-label">Employee Number</span>
                                        <strong>
                                            <?= htmlspecialchars($employeeNumber) ?>
                                        </strong>
                                    </div>

                                    <div class="record-item">
                                        <span class="record-label">Employee ID</span>
                                        <strong>
                                            <?= htmlspecialchars($employeeId) ?>
                                        </strong>
                                    </div>

                                </div>

                            </section>

                            <section class="record-card">

                                <div class="record-card-header">
                                    <div>
                                        <h2>Employment Summary</h2>
                                        <p>
                                            Current employment and organizational assignment.
                                        </p>
                                    </div>
                                </div>

                                <div class="record-grid">

                                    <div class="record-item">
                                        <span class="record-label">Department</span>
                                        <strong>
                                            <?= htmlspecialchars($department) ?>
                                        </strong>
                                    </div>

                                    <div class="record-item">
                                        <span class="record-label">Position</span>
                                        <strong>
                                            <?= htmlspecialchars($jobTitle) ?>
                                        </strong>
                                    </div>

                                    <div class="record-item">
                                        <span class="record-label">Employment Type</span>
                                        <strong>
                                            <?= htmlspecialchars($employmentType) ?>
                                        </strong>
                                    </div>

                                    <div class="record-item">
                                        <span class="record-label">Employment Status</span>

                                        <span class="badge <?= $statusClass ?>">
                                            <?= htmlspecialchars($employmentStatus) ?>
                                        </span>
                                    </div>

                                    <div class="record-item">
                                        <span class="record-label">Date Hired</span>
                                        <strong>
                                            <?= htmlspecialchars($hireDate) ?>
                                        </strong>
                                    </div>

                                </div>

                            </section>

                        </div>

                        <section class="record-card overview-hiring-context">

                            <div class="record-card-header">
                                <div>
                                    <h2>Hiring Context</h2>
                                    <p>
                                        The application details that led to this employee record.
                                    </p>
                                </div>
                            </div>

                            <div class="record-grid">

                                <div class="record-item">
                                    <span class="record-label">Applied Position</span>
                                    <strong>
                                        <?= htmlspecialchars($postingTitle) ?>
                                    </strong>
                                </div>

                                <div class="record-item">
                                    <span class="record-label">Application Status</span>
                                    <span class="badge <?= $applicationStatus === 'Hired' ? 'badge-green' : 'badge-gray' ?>">
                                        <?= htmlspecialchars($applicationStatus) ?>
                                    </span>
                                </div>

                                <div class="record-item">
                                    <span class="record-label">Date Applied</span>
                                    <strong>
                                        <?= htmlspecialchars($applicationDate) ?>
                                    </strong>
                                </div>

                                <div class="record-item">
                                    <span class="record-label">Date Hired</span>
                                    <strong>
                                        <?= htmlspecialchars($hireDate) ?>
                                    </strong>
                                </div>

                            </div>

                        </section>

                    </section>

                    <!-- ==================================================
                         EMPLOYMENT
                    =================================================== -->

                    <section
                        class="employee-view-panel"
                        id="employmentView"
                        role="tabpanel"
                        aria-labelledby="employmentTab"
                    >

                        <section class="record-card">

                            <div class="record-card-header">
                                <div>
                                    <h2>Employment Information</h2>
                                    <p>
                                        Current employment details and organizational assignment.
                                    </p>
                                </div>
                            </div>

                            <div class="record-grid">

                                <div class="record-item">
                                    <span class="record-label">Employee Number</span>
                                    <strong>
                                        <?= htmlspecialchars($employeeNumber) ?>
                                    </strong>
                                </div>

                                <div class="record-item">
                                    <span class="record-label">Department</span>
                                    <strong>
                                        <?= htmlspecialchars($department) ?>
                                    </strong>
                                </div>

                                <div class="record-item">
                                    <span class="record-label">Position</span>
                                    <strong>
                                        <?= htmlspecialchars($jobTitle) ?>
                                    </strong>
                                </div>

                                <div class="record-item">
                                    <span class="record-label">Employment Type</span>
                                    <strong>
                                        <?= htmlspecialchars($employmentType) ?>
                                    </strong>
                                </div>

                                <div class="record-item">
                                    <span class="record-label">Employment Status</span>

                                    <span class="badge <?= $statusClass ?>">
                                        <?= htmlspecialchars($employmentStatus) ?>
                                    </span>
                                </div>

                                <div class="record-item">
                                    <span class="record-label">Date Hired</span>
                                    <strong>
                                        <?= htmlspecialchars($hireDate) ?>
                                    </strong>
                                </div>

                            </div>

                        </section>

                        <section class="record-card">

                            <div class="record-card-header">
                                <div>
                                    <h2>Work Schedule</h2>
                                    <p>
                                        Work schedule and assignment information.
                                    </p>
                                </div>
                            </div>

                            <div class="empty-state compact">
                                <i class="fa-solid fa-calendar-days"></i>

                                <h3>Work Schedule Information</h3>

                                <p>
                                    Work schedule and shift information can be displayed here
                                    when available.
                                </p>
                            </div>

                        </section>

                    </section>

                    <!-- ==================================================
                         DOCUMENTS
                    =================================================== -->

                    <section
                        class="employee-view-panel"
                        id="documentsView"
                        role="tabpanel"
                        aria-labelledby="documentsTab"
                    >

                        <section class="record-card">

                            <div class="record-card-header">

                                <div>
                                    <h2>Employee Documents</h2>
                                    <p>
                                        Submitted documents associated with this employee.
                                    </p>
                                </div>

                            </div>

                            <?php if (empty($documents)): ?>
                                <div class="empty-state" id="employeeDocumentsEmptyState">
                                    <i class="fa-solid fa-file-circle-check"></i>

                                    <h3>No Documents Available</h3>

                                    <p>
                                        No submitted documents are available for this employee.
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="employee-document-list">
                                    <?php foreach ($documents as $document): ?>
                                        <article class="employee-document-item">
                                            <div class="employee-document-icon" aria-hidden="true">
                                                <i class="fa-solid <?= htmlspecialchars($document['icon']) ?>"></i>
                                            </div>

                                            <div class="employee-document-info">
                                                <strong><?= htmlspecialchars($document['label']) ?></strong>
                                                <span><?= htmlspecialchars(basename($document['file'])) ?></span>
                                            </div>

                                            <div class="employee-document-actions">
                                                <a
                                                    href="<?= htmlspecialchars($document['viewUrl']) ?>"
                                                    target="_blank"
                                                    rel="noopener"
                                                    class="btn-outline"
                                                >
                                                    <i class="fa-solid fa-eye"></i>
                                                    View
                                                </a>
                                                <a
                                                    href="<?= htmlspecialchars($document['downloadUrl']) ?>"
                                                    class="btn-outline"
                                                >
                                                    <i class="fa-solid fa-download"></i>
                                                    Download
                                                </a>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        </section>

                    </section>

                    <!-- ==================================================
                         NOTES
                    =================================================== -->

                    <section
                        class="employee-view-panel"
                        id="notesView"
                        role="tabpanel"
                        aria-labelledby="notesTab"
                    >

                        <section class="record-card">

                            <div class="record-card-header">

                                <div>
                                    <h2>Employee Notes</h2>
                                    <p>
                                        Internal notes and remarks related to this employee.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="btn-primary"
                                    id="addEmployeeNote"
                                >
                                    <i class="fa-solid fa-plus"></i>
                                    Add Note
                                </button>

                            </div>

                            <div class="empty-state" id="employeeNotesEmptyState">
                                <i class="fa-solid fa-note-sticky"></i>

                                <h3>No Notes Available</h3>

                                <p>
                                    Internal HR notes and remarks will appear here.
                                </p>
                            </div>

                        </section>

                    </section>

                    <!-- ==================================================
                         ACTIVITY
                    =================================================== -->

                    <section
                        class="employee-view-panel"
                        id="activityView"
                        role="tabpanel"
                        aria-labelledby="activityTab"
                    >

                        <section class="record-card">

                            <div class="record-card-header">
                                <div>
                                    <h2>Activity</h2>
                                    <p>
                                        Recent activity and changes related to this employee.
                                    </p>
                                </div>
                            </div>

                            <?php if (empty($activities)): ?>
                                <div class="empty-state">
                                    <i class="fa-solid fa-clock-rotate-left"></i>

                                    <h3>No Activity Available</h3>

                                    <p>
                                        Login and account activity will appear here when recorded.
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="employee-activity-list">
                                    <?php foreach ($activities as $activity): ?>
                                        <?php
                                        $activityDate = 'Unknown time';

                                        try {
                                            $activityDate = (new DateTimeImmutable($activity['created_at']))->format('M j, Y g:i A');
                                        } catch (Throwable $exception) {
                                        }
                                        ?>
                                        <article class="employee-activity-item">
                                            <span class="employee-activity-marker" aria-hidden="true">
                                                <i class="fa-solid <?= ($activity['action'] ?? '') === 'Logged in' ? 'fa-right-to-bracket' : 'fa-bolt' ?>"></i>
                                            </span>

                                            <div class="employee-activity-content">
                                                <div class="employee-activity-heading">
                                                    <strong><?= htmlspecialchars($activity['action'] ?? 'Activity recorded') ?></strong>
                                                    <time datetime="<?= htmlspecialchars($activity['created_at'] ?? '') ?>">
                                                        <?= htmlspecialchars($activityDate) ?>
                                                    </time>
                                                </div>

                                                <div class="employee-activity-meta">
                                                    <span>
                                                        <i class="fa-solid fa-desktop"></i>
                                                        <?= htmlspecialchars($activityDeviceLabel($activity['user_agent'] ?? null)) ?>
                                                    </span>
                                                    <span>
                                                        <i class="fa-solid fa-globe"></i>
                                                        <?= htmlspecialchars($activity['ip_address'] ?? 'Unknown IP') ?>
                                                    </span>
                                                    <span class="activity-method">
                                                        <?= htmlspecialchars($activity['request_method'] ?? 'GET') ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                        </section>

                    </section>

                </div>

            </div>

        <?php endif; ?>

    </div>

    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>

</div>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>