<?php

$pageTitle       = "Employee Record";
$pageCSS         = "employee-records.css";
$pageJS          = "employee-records.js";
$pageDescription = "View employee information and employment records.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
| $employee should be provided by EmployeeRecordsController.
*/

$employee = $employee ?? [];

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

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>

    <div class="employee-record-page">

        <div class="page-back">
            <a href="?page=employee-records">
                <i class="fa-solid fa-arrow-left"></i>
                Employee Records
            </a>
        </div>

        <?php if (empty($employee)): ?>

            <section class="table-card">

                <div class="empty-state">
                    <i class="fa-solid fa-user-slash"></i>

                    <h3>Employee Record Not Found</h3>

                    <p>
                        The employee record you're looking for could not be found.
                    </p>

                    <a
                        href="?page=employee-records"
                        class="btn-review"
                    >
                        <i class="fa-solid fa-arrow-left"></i>
                        Back to Employee Records
                    </a>
                </div>

            </section>

        <?php else: ?>

            <section class="employee-view-navigation">

                <button
                    type="button"
                    class="employee-view-tab active"
                    data-view="overview"
                    role="tab"
                    aria-selected="true"
                >
                    <i class="fa-solid fa-user"></i>
                    <span>Overview</span>
                </button>

                <button
                    type="button"
                    class="employee-view-tab"
                    data-view="employment"
                    role="tab"
                    aria-selected="false"
                >
                    <i class="fa-solid fa-briefcase"></i>
                    <span>Employment</span>
                </button>

                <button
                    type="button"
                    class="employee-view-tab"
                    data-view="documents"
                    role="tab"
                    aria-selected="false"
                >
                    <i class="fa-solid fa-file-lines"></i>
                    <span>Documents</span>
                </button>

                <button
                    type="button"
                    class="employee-view-tab"
                    data-view="notes"
                    role="tab"
                    aria-selected="false"
                >
                    <i class="fa-solid fa-note-sticky"></i>
                    <span>Notes</span>
                </button>

                <button
                    type="button"
                    class="employee-view-tab"
                    data-view="activity"
                    role="tab"
                    aria-selected="false"
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

                        <section class="record-card">

                            <div class="record-card-header">
                                <div>
                                    <h2>Record References</h2>
                                    <p>
                                        Internal references connecting this employee
                                        to the hiring process.
                                    </p>
                                </div>
                            </div>

                            <div class="record-grid">

                                <div class="record-item">
                                    <span class="record-label">Employee ID</span>
                                    <strong>
                                        <?= htmlspecialchars($employeeId) ?>
                                    </strong>
                                </div>

                                <div class="record-item">
                                    <span class="record-label">Application ID</span>
                                    <strong>
                                        <?= htmlspecialchars($applicationId) ?>
                                    </strong>
                                </div>

                                <div class="record-item">
                                    <span class="record-label">Applicant ID</span>
                                    <strong>
                                        <?= htmlspecialchars($applicantId) ?>
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
                    >

                        <section class="record-card">

                            <div class="record-card-header">

                                <div>
                                    <h2>Employee Documents</h2>
                                    <p>
                                        Manage documents associated with this employee.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="btn-primary"
                                    id="requestEmployeeDocument"
                                >
                                    <i class="fa-solid fa-plus"></i>
                                    Request Document
                                </button>

                            </div>

                            <div class="empty-state">
                                <i class="fa-solid fa-file-circle-check"></i>

                                <h3>No Documents Available</h3>

                                <p>
                                    Employee documents and document requests will appear here.
                                </p>
                            </div>

                        </section>

                    </section>

                    <!-- ==================================================
                         NOTES
                    =================================================== -->

                    <section
                        class="employee-view-panel"
                        id="notesView"
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

                            <div class="empty-state">
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

                            <div class="empty-state">
                                <i class="fa-solid fa-clock-rotate-left"></i>

                                <h3>No Activity Available</h3>

                                <p>
                                    Employee activity and record history will appear here.
                                </p>
                            </div>

                        </section>

                    </section>

                </div>

            </div>

        <?php endif; ?>

    </div>

    <?php require '../resources/views/includes/footer.php'; ?>

</div>

<?php require '../resources/views/includes/scripts.php'; ?>