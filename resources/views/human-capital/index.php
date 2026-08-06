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
    <div class="page-content">

        <!-- =====================================================
        VIEW NAVIGATION
        ===================================================== -->
        <section class="cs-view-navigation">
            <button type="button" class="cs-view-tab active" data-view="organization">

                <i class="fa-solid fa-sitemap"></i>
                <span>Organization</span>
            </button>

            <button type="button" class="cs-view-tab" data-view="departments">

                <i class="fa-solid fa-building"></i>
                <span>Departments</span>
            </button>

            <button type="button" class="cs-view-tab" data-view="positions">

                <i class="fa-solid fa-briefcase"></i>
                <span>Positions</span>
            </button>
        </section>

        <div class="cs-view-wrapper">
            <div class="cs-view-slider">
                <!-- =====================================================
                ORGANIZATION
                ===================================================== -->
                <section class="cs-view-panel" id="organizationView">
                    <div class="dashboard-card">
                        <div class="card-header">
                            <div>
                                <h2>Organization Structure</h2>
                                <p>View your company's organizational hierarchy.</p>
                            </div>
                        </div>

                        <div class="organization-tree">
                            <div class="organization-root">
                                <div class="organization-company">
                                    <i class="fa-solid fa-building"></i>
                                    <span>RAM-YUM</span>
                                </div>

                                <div class="organization-departments">
                                    <?php if (!empty($organization)): ?>
                                        <?php foreach ($organization as $department): ?>
                                            <div class="organization-department">
                                                <div class="department-node">
                                                    <i class="fa-solid fa-building"></i>

                                                    <span><?= htmlspecialchars($department['department_name']); ?></span>
                                                </div>

                                                <?php if (!empty($department['positions'])): ?>
                                                <div class="organization-positions">

                                                    <?php foreach ($department['positions'] as $position): ?>

                                                        <div class="organization-position">

                                                            <div class="position-node">

                                                                <i class="fa-solid fa-briefcase"></i>

                                                                <span><?= htmlspecialchars($position['position_name']); ?></span>

                                                            </div>

                                                            <?php if (!empty($position['employees'])): ?>

                                                                <div class="organization-employees">

                                                                    <?php foreach ($position['employees'] as $employee): ?>

                                                                        <div class="employee-node">

                                                                            <i class="fa-solid fa-user"></i>

                                                                            <span>
                                                                                <?= htmlspecialchars($employee['employee_name']); ?>
                                                                            </span>

                                                                        </div>

                                                                    <?php endforeach; ?>

                                                                </div>

                                                            <?php endif; ?>

                                                        </div>

                                                    <?php endforeach; ?>

                                                </div>

                                                <?php else: ?>
                                                    <div class="organization-empty">No positions assigned.</div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>

                                    <?php else: ?>
                                        <div class="cs-empty-state">
                                            <i class="fa-solid fa-sitemap"></i>
                                            <h3>No organizational structure available</h3>

                                            <p>Create departments and positions to build your organization.</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- =====================================================
                    DEPARTMENTS
                ===================================================== -->
                <section class="cs-view-panel" id="departmentsView">

                    <div class="dashboard-card">

                        <div class="card-header">
                            <div>
                                <h2>Departments</h2>
                                <p>Manage your organization's departments.</p>
                            </div>

                            <button type="button" class="btn-primary" id="addDepartment">
                                <i class="fa-solid fa-plus"></i>
                                New Department
                            </button>
                        </div>

                        <?php if (!empty($departments)): ?>

                            <div class="table-responsive">
                                <table class="cs-table">

                                    <thead>
                                        <tr>
                                            <th>Department</th>
                                            <th>Description</th>
                                            <th>Positions</th>
                                            <th>Vacancies</th>
                                            <th width="80"></th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                        <?php foreach ($departments as $department): ?>
                                            <tr>
                                                <td>
                                                    <strong><?= htmlspecialchars($department['department_name']); ?></strong>
                                                </td>

                                                <td><?= (int) $department['position_count']; ?></td>
                                                <td><?= (int) $department['vacancies']; ?></td>

                                                <td>
                                                    <div class="dropdown">
                                                        <button class="dropdown-btn">
                                                            <i class="fa-solid fa-ellipsis"></i>
                                                        </button>

                                                        <div class="dropdown-menu">
                                                            <button class="viewDepartment" data-id="<?= $department['department_id']; ?>">
                                                                <i class="fa-solid fa-eye"></i> View
                                                            </button>

                                                            <button class="editDepartment" data-id="<?= $department['department_id']; ?>">
                                                                <i class="fa-solid fa-pen"></i> Edit
                                                            </button>

                                                            <button class="deleteDepartment" data-id="<?= $department['department_id']; ?>">
                                                                <i class="fa-solid fa-trash"></i> Delete
                                                            </button>
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
                                <i class="fa-solid fa-building"></i>
                                <h3>No departments found</h3>
                                <p>Create your first department to organize your company.</p>

                                <button type="button" class="btn-primary" id="createFirstDepartment">
                                    <i class="fa-solid fa-plus"></i>
                                    New Department
                                </button>
                            </div>

                        <?php endif; ?>

                    </div>

                </section>

                <!-- =====================================================
                    POSITIONS
                ===================================================== -->
                <section class="cs-view-panel" id="positionsView">

                    <div class="dashboard-card">

                        <div class="card-header">
                            <div>
                                <h2>Positions</h2>
                                <p>Manage job positions within your organization.</p>
                            </div>

                            <a href="?page=recruitment" class="btn-primary">
                                <i class="fa-solid fa-plus"></i>
                                New Position
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
                                            <th>Applicants</th>
                                            <th width="80"></th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php foreach ($jobPostings as $job): ?>

                                            <tr>

                                                <td>
                                                    <strong><?= htmlspecialchars($job['title']); ?></strong>
                                                </td>

                                                <td><?= htmlspecialchars($job['department_name']); ?></td>

                                                <td><?= htmlspecialchars($job['employment_type']); ?></td>

                                                <td><?= (int) $job['vacancies']; ?></td>

                                                <td>
                                                    <span class="status-badge <?= strtolower($job['status']); ?>">
                                                        <span class="status-dot"></span>
                                                        <?= htmlspecialchars($job['status']); ?>
                                                    </span>
                                                </td>

                                                <td><?= (int) $job['applicants']; ?></td>

                                                <td>
                                                    <div class="dropdown">

                                                        <button class="dropdown-btn">
                                                            <i class="fa-solid fa-ellipsis"></i>
                                                        </button>

                                                        <div class="dropdown-menu">

                                                            <a href="?page=recruitment">
                                                                <i class="fa-solid fa-eye"></i>
                                                                View
                                                            </a>

                                                            <a href="?page=recruitment">
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
                                <h3>No positions found</h3>
                                <p>Create your first position to start recruiting employees.</p>

                                <a href="?page=recruitment" class="btn-primary">
                                    <i class="fa-solid fa-plus"></i>
                                    New Position
                                </a>
                            </div>

                        <?php endif; ?>

                    </div>

                </section>
            </div>
        </div>

        <!-- =====================================================
            CONFIRMATION MODAL
        ===================================================== -->
        <div class="cs-modal" id="confirmModal">

            <div class="modal-backdrop"></div>

            <div class="modal-content modal-sm">

                <div class="modal-header">
                    <h3 id="confirmTitle">Confirm Action</h3>
                </div>

                <div class="modal-body">
                    <p id="confirmMessage">
                        Are you sure you want to continue?
                    </p>
                </div>

                <div class="modal-footer">
                    <button class="btn-secondary" id="cancelConfirm">Cancel</button>
                    <button class="btn-danger" id="confirmAction">Confirm</button>
                </div>

            </div>

        </div>

        <!-- =====================================================
            TOAST CONTAINER
        ===================================================== -->
        <div id="toastContainer" class="toast-container"></div>
    </div>
    <?php require '../resources/views/includes/footer.php'; ?>

</div>

<?php require '../resources/views/includes/scripts.php'; ?>