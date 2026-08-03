<?php

$pageTitle       = "New Hire Onboarding";
$pageCSS         = "onboarding.css";
$pageJS          = "onboarding.js";
$pageDescription = "Manage newly hired employees and monitor their onboarding progress.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

/*
|--------------------------------------------------------------------------
| DATA
|--------------------------------------------------------------------------
| $employees should be provided by OnboardingController.
| Only employees with active onboarding records should be shown.
*/

$employees   = $employees ?? [];
$departments = $departments ?? [];

$statusMeta = [
    "Pending" => [
        "label" => "Pending",
        "class" => "badge-gray"
    ],

    "In Progress" => [
        "label" => "In Progress",
        "class" => "badge-blue"
    ],

    "Completed" => [
        "label" => "Completed",
        "class" => "badge-green"
    ],

    "Overdue" => [
        "label" => "Overdue",
        "class" => "badge-red"
    ],
];

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>

    <div class="onboarding-page">
        <!-- ==========================================================
            FILTER BAR
        ========================================================== -->

        <section class="filter-bar">

            <select id="departmentFilter">
                <option value="All">All Departments</option>

                <?php foreach ($departments as $department): ?>

                    <option value="<?= htmlspecialchars($department['department_name']) ?>">
                        <?= htmlspecialchars($department['department_name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <select id="statusFilter">

                <option value="All">All Status</option>

                <option value="Pending">Pending</option>

                <option value="In Progress">In Progress</option>

                <option value="Completed">Completed</option>

                <option value="Overdue">Overdue</option>

            </select>

            <select id="sortFilter">

                <option value="newest">Newest</option>

                <option value="oldest">Oldest</option>

                <option value="progress-high">Highest Progress</option>

                <option value="progress-low">Lowest Progress</option>

                <option value="name-az">Employee Name (A-Z)</option>

            </select>

        </section>

        <!-- ==========================================================
            ONBOARDING TABLE
        ========================================================== -->

        <section class="table-card">

            <div class="table-scroll">
                <table class="onboarding-table" id="onboardingTable">
                    <thead>

                    <tr>

                    <th>Employee</th>

                    <th>Department</th>

                    <th>Job Title</th>

                    <th>Progress</th>

                    <th>Status</th>

                    <th>Start Date</th>

                    <th class="col-actions">Actions</th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($employees)): ?>

                    <tr class="empty-row">

                        <td colspan="7">

                            <div class="empty-state">

                                <i class="fa-solid fa-user-check"></i>

                                <p>No employees currently in onboarding.</p>

                            </div>

                        </td>

                    </tr>

                    <?php else: ?>

                    <?php foreach ($employees as $employee):

                        $status = $employee['onboarding_status'] ?? 'Pending';
                        $meta   = $statusMeta[$status] ?? $statusMeta['Pending'];

                        $progress = (int)($employee['progress'] ?? 0);

                    ?>

                    <tr class="employee-row"

                        data-status="<?= htmlspecialchars($status) ?>"
                        data-department="<?= htmlspecialchars($employee['department_name'] ?? '') ?>"
                        data-progress="<?= $progress ?>"
                        data-id="<?= $employee['employee_id'] ?>">

                        <!-- Employee -->

                        <td>

                            <div class="onboarding-cell">

                                <div class="avatar-circle">

                                    <?= strtoupper(substr($employee['fullname'],0,1)) ?>

                                </div>

                                <div>

                                    <strong><?= htmlspecialchars($employee['fullname']) ?></strong>

                                    <span class="sub-text">

                                        <?= htmlspecialchars($employee['email']) ?>

                                    </span>

                                </div>

                            </div>

                        </td>

                        <!-- Department -->

                        <td>

                            <?= htmlspecialchars($employee['department_name']) ?>

                        </td>

                        <!-- Position -->

                        <td>

                            <?= htmlspecialchars($employee['job_title']) ?>

                        </td>

                        <!-- Progress -->

                        <td>

                            <div class="score-pill">

                                <span
                                    class="score-dot"
                                    style="--score:<?= $progress ?>">
                                </span>

                                <?= $progress ?>%

                            </div>

                        </td>

                        <!-- Status -->

                        <td>

                            <span class="badge <?= $meta['class'] ?>">

                                <?= $meta['label'] ?>

                            </span>

                        </td>

                        <!-- Start Date -->

                        <td>

                            <?= htmlspecialchars($employee['start_date']) ?>

                        </td>

                        <!-- Actions -->

                        <td class="col-actions">

                            <a
                                href="?page=onboarding-view&id=<?= $employee['employee_id'] ?>"
                                class="btn-review">

                                <i class="fa-solid fa-eye"></i>

                                View

                            </a>

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
                    <button class="page-btn" id="prevPage" type="button"><i class="fa-solid fa-chevron-left"></i></button>
                    <div class="page-numbers" id="pageNumbers"></div>
                    <button class="page-btn" id="nextPage" type="button"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>

        </section>

    </div>
    <?php require '../resources/views/includes/footer.php'; ?>
</div>

<?php require '../resources/views/includes/scripts.php'?>