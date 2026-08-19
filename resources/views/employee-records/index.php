<?php

$pageTitle       = "Employee Records";
$pageCSS         = "employee-records.css";
$pageJS          = "employee-records.js";
$pageDescription = "Manage and view employee information and employment records.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
| $employees and $departments should be provided by
| EmployeeRecordsController.
*/

$employees   = $employees ?? [];
$departments = $departments ?? [];

$statusMeta = [
    "Active" => [
        "label" => "Active",
        "class" => "badge-green"
    ],
    "Inactive" => [
        "label" => "Inactive",
        "class" => "badge-gray"
    ]
];

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>

    <div class="employee-records-page">
        <section class="filter-bar">

            <div class="filter-field">
                <select id="departmentFilter">
                    <option value="All">All Departments</option>

                    <?php foreach ($departments as $department): ?>
                        <option value="<?= htmlspecialchars($department['department_name']) ?>">
                            <?= htmlspecialchars($department['department_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filter-field">
                <select id="employmentTypeFilter">
                    <option value="All">All Employment Types</option>
                    <option value="Full-Time">Full-Time</option>
                    <option value="Part-Time">Part-Time</option>
                    <option value="Contract">Contract</option>
                    <option value="Internship">Internship</option>
                </select>
            </div>

            <div class="filter-field">
                <select id="statusFilter">
                    <option value="All">All Status</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <div class="filter-field">
                <select id="sortFilter">
                    <option value="newest">Newest</option>
                    <option value="oldest">Oldest</option>
                    <option value="name-az">Employee Name (A-Z)</option>
                    <option value="name-za">Employee Name (Z-A)</option>
                </select>
            </div>

        </section>

        <section class="table-card">

            <div class="table-header">
                <div>
                    <h2>Employees</h2>
                    <p>Employee information and current employment status.</p>
                </div>
            </div>

            <div class="table-scroll">

                <table class="employee-records-table" id="employeeRecordsTable">

                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Employee ID</th>
                            <th>Department</th>
                            <th>Position</th>
                            <th>Employment Type</th>
                            <th>Status</th>
                            <th>Date Hired</th>
                        </tr>
                    </thead>

                    <tbody>

                        <?php if (empty($employees)): ?>

                            <tr class="empty-row">
                                <td colspan="7">
                                    <div class="empty-state">
                                        <i class="fa-solid fa-address-book"></i>
                                        <h3>No Employee Records</h3>
                                        <p>No employee records are currently available.</p>
                                    </div>
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($employees as $employee): ?>

                                <?php
                                $status = $employee['employment_status'] ?? 'Active';
                                $meta = $statusMeta[$status] ?? $statusMeta['Active'];

                                $fullname = $employee['fullname'] ?? 'Unknown Employee';
                                $email = $employee['email'] ?? '';
                                $employeeId = $employee['employee_id'] ?? '';
                                $department = $employee['department_name'] ?? '';
                                $jobTitle = $employee['job_title'] ?? '';
                                $employmentType = $employee['employment_type'] ?? '';
                                $dateHired = $employee['hire_date'] ?? '';

                                $initial = strtoupper(substr(trim($fullname), 0, 1));
                                ?>

                                <tr
                                    class="employee-row"
                                    data-status="<?= htmlspecialchars($status) ?>"
                                    data-department="<?= htmlspecialchars($department) ?>"
                                    data-employment-type="<?= htmlspecialchars($employmentType) ?>"
                                    data-name="<?= htmlspecialchars(strtolower($fullname)) ?>"
                                    data-date="<?= htmlspecialchars($dateHired) ?>"
                                    data-id="<?= htmlspecialchars($employeeId) ?>"
                                >

                                    <td>
                                        <a
                                            href="?page=view&id=<?= urlencode($employeeId) ?>"
                                            class="employee-link"
                                        >
                                            <div class="employee-cell">

                                                <div class="avatar-circle">
                                                    <?= htmlspecialchars($initial) ?>
                                                </div>

                                                <div class="employee-details">
                                                    <strong>
                                                        <?= htmlspecialchars($fullname) ?>
                                                    </strong>

                                                    <?php if ($email): ?>
                                                        <span class="sub-text">
                                                            <?= htmlspecialchars($email) ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>

                                            </div>
                                        </a>
                                    </td>

                                    <td>
                                        <span class="employee-id">
                                            <?= htmlspecialchars($employeeId) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($department) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($jobTitle) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($employmentType) ?>
                                    </td>

                                    <td>
                                        <span class="badge <?= $meta['class'] ?>">
                                            <?= htmlspecialchars($meta['label']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($dateHired) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

            <div class="table-footer">

                <span class="results-count" id="resultsCount"></span>

                <div class="pagination" id="pagination">

                    <button
                        class="page-btn"
                        id="prevPage"
                        type="button"
                        aria-label="Previous page"
                    >
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    <div class="page-numbers" id="pageNumbers"></div>

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

    <?php require '../resources/views/includes/footer.php'; ?>

</div>

<?php require '../resources/views/includes/scripts.php'; ?>