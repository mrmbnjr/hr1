<?php

$pageTitle = "Human Capital Management";
$pageCSS = "human-capital.css";
$pageJS = "human-capital.js";
$pageDescription = "Manage your company's organizational structure.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

$stats = $stats ?? [
    'departments' => 0,
    'positions' => 0,
    'vacancies' => 0,
    'hiring_departments' => 0
];

$departments = $departments ?? [];
$jobPostings = $jobPostings ?? [];
$organization = $organization ?? [];
$departmentLookup = $departmentLookup ?? [];

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

<?php require '../resources/views/includes/navbar.php'; ?>

<!-- =====================================================
PAGE HEADER
===================================================== -->

<section class="cs-page-head">

    <div>

        <h1>Human Capital Management</h1>

        <p>

            Manage your company's departments and organizational structure.

        </p>

    </div>

    <button
        class="btn-primary"
        id="csAddDepartmentBtn">

        <i class="fa-solid fa-plus"></i>

        Add Department

    </button>

</section>

<!-- =====================================================
STATISTICS
===================================================== -->

<section class="stats-grid">

    <article class="stat-card">

        <div class="stat-icon">

            <i class="fa-solid fa-building"></i>

        </div>

        <div>

            <small>Departments</small>

            <h2><?= $stats['departments']; ?></h2>

            <span>Registered departments</span>

        </div>

    </article>

    <article class="stat-card">

        <div class="stat-icon">

            <i class="fa-solid fa-briefcase"></i>

        </div>

        <div>

            <small>Active Job Postings</small>

            <h2><?= $stats['positions']; ?></h2>

            <span>Currently hiring</span>

        </div>

    </article>

    <article class="stat-card">

        <div class="stat-icon">

            <i class="fa-solid fa-user-plus"></i>

        </div>

        <div>

            <small>Open Vacancies</small>

            <h2><?= $stats['vacancies']; ?></h2>

            <span>Available positions</span>

        </div>

    </article>

    <article class="stat-card">

        <div class="stat-icon">

            <i class="fa-solid fa-sitemap"></i>

        </div>

        <div>

            <small>Hiring Departments</small>

            <h2><?= $stats['hiring_departments']; ?></h2>

            <span>Departments with open jobs</span>

        </div>

    </article>

</section>

<!-- =====================================================
TOOLBAR
===================================================== -->

<section class="cs-toolbar">

    <div class="cs-search-box">

        <i class="fa-solid fa-magnifying-glass"></i>

        <input
            type="text"
            id="csSearchInput"
            placeholder="Search department or job position">

    </div>

    <div class="cs-filter-chips">

        <select
            id="csDepartmentFilter"
            class="cs-chip-select">

            <option value="">All Departments</option>

            <?php foreach ($departmentLookup as $department): ?>

                <option value="<?= $department['department_id']; ?>">

                    <?= htmlspecialchars($department['department_name']); ?>

                </option>

            <?php endforeach; ?>

        </select>

        <select
            id="csEmploymentFilter"
            class="cs-chip-select">

            <option value="">Employment Type</option>

            <option>Full-Time</option>

            <option>Part-Time</option>

            <option>Contract</option>

            <option>Internship</option>

        </select>

        <select
            id="csStatusFilter"
            class="cs-chip-select">

            <option value="">Status</option>

            <option>Open</option>

            <option>Closed</option>

        </select>

    </div>

    <div class="cs-view-toggle">

        <button
            class="cs-view-tab active"
            data-view="organization">

            <i class="fa-solid fa-sitemap"></i>

            Organization

        </button>

        <button
            class="cs-view-tab"
            data-view="departments">

            <i class="fa-solid fa-building"></i>

            Departments

        </button>

        <button
            class="cs-view-tab"
            data-view="positions">

            <i class="fa-solid fa-briefcase"></i>

            Job Positions

        </button>

    </div>

</section>

<!-- =====================================================
ORGANIZATION VIEW
===================================================== -->

<section
    class="cs-view-panel dashboard-card"
    id="organizationView">

    <div class="card-header">

        <div>

            <h2>Organization Structure</h2>

            <p>

                Departments and available job positions.

            </p>

        </div>

    </div>

    <div class="organization-tree">

        <div class="organization-root">

            <div class="organization-company">

                RAM-YUM

            </div>

            <?php foreach ($organization as $department): ?>

                <div class="organization-department">

                    <div class="department-node">

                        <i class="fa-solid fa-building"></i>

                        <?= htmlspecialchars($department['department_name']); ?>

                    </div>

                    <?php if (!empty($department['positions'])): ?>

                        <ul class="organization-position-list">

                            <?php foreach ($department['positions'] as $position): ?>

                                <li>

                                    <i class="fa-solid fa-briefcase"></i>

                                    <?= htmlspecialchars($position['title']); ?>

                                </li>

                            <?php endforeach; ?>

                        </ul>

                    <?php else: ?>

                        <p class="organization-empty">

                            No job positions available.

                        </p>

                    <?php endif; ?>

                </div>

            <?php endforeach; ?>

        </div>

    </div>

</section>

<!-- =====================================================
DEPARTMENTS VIEW
===================================================== -->

<section
    class="cs-view-panel"
    id="departmentsView"
    hidden>

    <?php if (!empty($departments)): ?>

        <div class="cs-department-grid">

            <?php foreach ($departments as $department): ?>

                <article
                    class="dashboard-card department-card"
                    data-id="<?= $department['department_id']; ?>">

                    <div class="department-card-header">

                        <div>

                            <h3>

                                <?= htmlspecialchars($department['department_name']); ?>

                            </h3>

                            <small>

                                Department

                            </small>

                        </div>

                        <div class="dropdown">

                            <button
                                class="dropdown-btn">

                                <i class="fa-solid fa-ellipsis"></i>

                            </button>

                            <div class="dropdown-menu">

                                <button
                                    class="viewDepartment"
                                    data-id="<?= $department['department_id']; ?>">

                                    <i class="fa-solid fa-eye"></i>

                                    View

                                </button>

                                <button
                                    class="editDepartment"
                                    data-id="<?= $department['department_id']; ?>">

                                    <i class="fa-solid fa-pen"></i>

                                    Edit

                                </button>

                                <button
                                    class="deleteDepartment"
                                    data-id="<?= $department['department_id']; ?>">

                                    <i class="fa-solid fa-trash"></i>

                                    Delete

                                </button>

                            </div>

                        </div>

                    </div>

                    <p class="department-description">

                        <?= !empty($department['description'])

                            ? nl2br(htmlspecialchars($department['description']))

                            : 'No description available.'; ?>

                    </p>

                    <div class="department-footer">

                        <div>

                            <span>

                                Job Positions

                            </span>

                            <strong>

                                <?= $department['job_postings']; ?>

                            </strong>

                        </div>

                        <div>

                            <span>

                                Vacancies

                            </span>

                            <strong>

                                <?= $department['vacancies']; ?>

                            </strong>

                        </div>

                    </div>

                </article>

            <?php endforeach; ?>

        </div>

    <?php else: ?>

        <div class="cs-empty-state">

            <i class="fa-solid fa-building"></i>

            <h3>

                No departments yet

            </h3>

            <p>

                Create your first department to begin organizing your company.

            </p>

            <button
                class="btn-primary"
                id="createFirstDepartment">

                <i class="fa-solid fa-plus"></i>

                Add Department

            </button>

        </div>

    <?php endif; ?>

</section>

<!-- =====================================================
DEPARTMENT MODAL
===================================================== -->

<div
    class="cs-modal"
    id="departmentModal">

    <div class="modal-backdrop"></div>

    <div class="modal-content">

        <div class="modal-header">

            <h2>

                Department

            </h2>

            <button
                class="closeModal">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>

        <form
            id="departmentForm">

            <input
                type="hidden"
                name="department_id"
                id="department_id">

            <div class="form-group">

                <label>

                    Department Name

                </label>

                <input
                    type="text"
                    name="department_name"
                    id="department_name"
                    required>

            </div>

            <div class="form-group">

                <label>

                    Description

                </label>

                <textarea
                    name="description"
                    id="description"
                    rows="5"></textarea>

            </div>

            <div class="modal-footer">

                <button
                    type="button"
                    class="btn-secondary closeModal">

                    Cancel

                </button>

                <button
                    type="submit"
                    class="btn-primary">

                    Save Department

                </button>

            </div>

        </form>

    </div>

</div>

<div
    class="cs-modal"
    id="departmentDetailsModal">

    <div class="modal-backdrop"></div>

    <div class="modal-content">

        <div class="modal-header">

            <h2>

                Department Details

            </h2>

            <button
                class="closeModal">

                <i class="fa-solid fa-xmark"></i>

            </button>

        </div>

        <div
            id="departmentDetailsBody">

        </div>

    </div>

</div>
<!-- =====================================================
JOB POSITIONS VIEW
===================================================== -->

<section
    class="cs-view-panel dashboard-card"
    id="positionsView"
    hidden>

    <div class="card-header">

        <div>

            <h2>Job Positions</h2>

            <p>

                Available positions from Recruitment Management.

            </p>

        </div>

        <a
            href="?page=recruitment"
            class="btn-primary">

            <i class="fa-solid fa-plus"></i>

            New Job Posting

        </a>

    </div>

    <?php if (!empty($jobPostings)): ?>

    <div class="table-responsive">

        <table class="cs-table">

            <thead>

                <tr>

                    <th>Position</th>

                    <th>Department</th>

                    <th>Employment Type</th>

                    <th>Vacancies</th>

                    <th>Status</th>

                    <th>Deadline</th>

                    <th>Applicants</th>

                    <th></th>

                </tr>

            </thead>

            <tbody>

            <?php foreach ($jobPostings as $job): ?>

                <tr>

                    <td>

                        <strong>

                            <?= htmlspecialchars($job['title']); ?>

                        </strong>

                    </td>

                    <td>

                        <?= htmlspecialchars($job['department_name']); ?>

                    </td>

                    <td>

                        <?= htmlspecialchars($job['employment_type']); ?>

                    </td>

                    <td>

                        <?= (int)$job['vacancies']; ?>

                    </td>

                    <td>

                        <?php

                        $badge = strtolower($job['status']);

                        ?>

                        <span class="status-badge <?= $badge; ?>">

                            <?= htmlspecialchars($job['status']); ?>

                        </span>

                    </td>

                    <td>

                        <?= date('M d, Y', strtotime($job['application_deadline'])); ?>

                    </td>

                    <td>

                        <?= (int)$job['total_applicants']; ?>

                    </td>

                    <td>

                        <div class="dropdown">

                            <button class="dropdown-btn">

                                <i class="fa-solid fa-ellipsis"></i>

                            </button>

                            <div class="dropdown-menu">

                                <a
                                    href="?page=recruitment">

                                    <i class="fa-solid fa-eye"></i>

                                    View

                                </a>

                                <a
                                    href="?page=recruitment">

                                    <i class="fa-solid fa-pen"></i>

                                    Edit

                                </a>

                            </div>

                        </div>

                    </td>

                </tr>

            <?php endforeach; ?>

            </tbody>

        </table>

    </div>

    <?php else: ?>

        <div class="cs-empty-state">

            <i class="fa-solid fa-briefcase"></i>

            <h3>

                No job postings available

            </h3>

            <p>

                Create your first recruitment posting to start hiring.

            </p>

            <a
                href="?page=recruitment"
                class="btn-primary">

                <i class="fa-solid fa-plus"></i>

                Create Job Posting

            </a>

        </div>

    <?php endif; ?>

</section>

<!-- =====================================================
CONFIRMATION MODAL
===================================================== -->

<div
    class="cs-modal"
    id="confirmModal">

    <div class="modal-backdrop"></div>

    <div class="modal-content modal-sm">

        <div class="modal-header">

            <h3>

                Confirm Action

            </h3>

        </div>

        <div class="modal-body">

            <p id="confirmMessage">

                Are you sure you want to continue?

            </p>

        </div>

        <div class="modal-footer">

            <button
                class="btn-secondary"
                id="cancelConfirm">

                Cancel

            </button>

            <button
                class="btn-danger"
                id="confirmAction">

                Confirm

            </button>

        </div>

    </div>

</div>

<!-- =====================================================
TOAST CONTAINER
===================================================== -->

<div
    id="toastContainer"
    class="toast-container">

</div>

<?php require '../resources/views/includes/footer.php'; ?>

</div>

<?php require '../resources/views/includes/scripts.php'; ?>