<?php

$pageTitle       = "New Hire Onboarding";
$pageCSS         = "onboarding.css";
$pageJS          = "onboarding.js";
$pageDescription = "Manage newly hired employees and monitor their onboarding progress.";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url("?page=login"));
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

/*
|--------------------------------------------------------------------------
| SUMMARY METRICS
|--------------------------------------------------------------------------
| The controller already counts each status. The array-derived counts below
| only act as a fallback, so the summary stays correct if the view is ever
| rendered without those variables.
*/

$totalOnboarding = (int) ($totalNewHires ?? count($employees));

$statusCounts = [
    'Pending'     => 0,
    'In Progress' => 0,
    'Completed'   => 0,
    'Overdue'     => 0,
];

foreach ($employees as $onboardingRow) {
    $rowStatus = (string) ($onboardingRow['onboarding_status'] ?? 'Pending');

    $statusCounts[$rowStatus] = ($statusCounts[$rowStatus] ?? 0) + 1;
}

$pendingCount   = (int) ($pending   ?? $statusCounts['Pending']);
$ongoingCount   = (int) ($ongoing   ?? $statusCounts['In Progress']);
$completedCount = (int) ($completed ?? $statusCounts['Completed']);
$overdueCount   = $statusCounts['Overdue'];

$statTiles = [
    [
        'icon'  => 'fa-users',
        'value' => $totalOnboarding,
        'label' => 'In Onboarding',
        'note'  => 'Active onboarding records',
    ],
    [
        'icon'  => 'fa-hourglass-half',
        'value' => $pendingCount,
        'label' => 'Pending',
        'note'  => 'Not yet started',
    ],
    [
        'icon'  => 'fa-arrows-rotate',
        'value' => $ongoingCount,
        'label' => 'In Progress',
        'note'  => 'Currently onboarding',
    ],
    [
        'icon'  => 'fa-circle-check',
        'value' => $completedCount,
        'label' => 'Completed',
        'note'  => 'Finished onboarding',
    ],
    [
        'icon'  => 'fa-triangle-exclamation',
        'value' => $overdueCount,
        'label' => 'Overdue',
        'note'  => 'Needs follow-up',
    ],
];

?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>

    <div class="onboarding-page">
        <!-- ==========================================================
            SUMMARY
        ========================================================== -->

        <section class="onboarding-summary-grid" aria-label="Onboarding summary">
            <?php foreach ($statTiles as $tile): ?>

                <div class="onboarding-stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid <?= htmlspecialchars($tile['icon']) ?>"></i>
                    </div>

                    <div class="stat-content">
                        <span><?= htmlspecialchars($tile['label']) ?></span>
                        <strong><?= (int) $tile['value'] ?></strong>
                        <small><?= htmlspecialchars($tile['note']) ?></small>
                    </div>
                </div>

            <?php endforeach; ?>
        </section>

        <!-- ==========================================================
            FILTER BAR
        ========================================================== -->

        <section class="filter-bar">

            <div class="filter-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" id="employeeSearch" placeholder="Search employee or email..." aria-label="Search employees">
            </div>

            <select id="departmentFilter" aria-label="Filter by department">
                <option value="All">All Departments</option>

                <?php foreach ($departments as $department): ?>

                    <option value="<?= htmlspecialchars($department['department_name']) ?>">
                        <?= htmlspecialchars($department['department_name']) ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <select id="statusFilter" aria-label="Filter by status">

                <option value="All">All Status</option>

                <option value="Pending">Pending</option>

                <option value="In Progress">In Progress</option>

                <option value="Completed">Completed</option>

                <option value="Overdue">Overdue</option>

            </select>

            <select id="sortFilter" aria-label="Sort employees">

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

                    <th>Onboarding Phase</th>

                    <th>Documents Submitted</th>

                    <th>Progress</th>

                    <th>Status</th>

                    <th>Start Date</th>

                    </tr>

                    </thead>

                    <tbody>

                    <?php if (empty($employees)): ?>

                    <tr class="empty-row">

                        <td colspan="6">

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

                        $totalDocs = (int)($employee['documents_total'] ?? 0);
                        $submittedDocs = (int)($employee['documents_submitted'] ?? 0);
                        $pendingDocs = max(0, $totalDocs - $submittedDocs);

                        $documentsClass = 'document-neutral';
                        if ($pendingDocs === 0 && $totalDocs > 0) {
                            $documentsClass = 'document-success';
                        } elseif ($submittedDocs > 0 && $pendingDocs > 0) {
                            $documentsClass = 'document-progress';
                        }

                    ?>

                    <tr class="employee-row"
                        data-row-href="?page=onboarding-view&employee_id=<?= (int)$employee['employee_id'] ?>"
                        tabindex="0"
                        role="link"

                        data-status="<?= htmlspecialchars($status) ?>"
                        data-department="<?= htmlspecialchars($employee['department_name'] ?? '') ?>"
                        data-progress="<?= $progress ?>"
                        data-start-date="<?= htmlspecialchars($employee['start_date'] ?? '') ?>"
                        data-fullname="<?= htmlspecialchars($employee['fullname'] ?? '') ?>"
                        data-id="<?= $employee['employee_id'] ?>">

                        <!-- Employee -->

                        <td>
                            <a href="?page=onboarding-view&employee_id=<?= (int)$employee['employee_id'] ?>" class="employee-link">

                                <div class="onboarding-cell">

                                    <div class="avatar-circle">
                                        <?= strtoupper(substr($employee['fullname'], 0, 1)) ?>
                                    </div>

                                    <div>
                                        <strong><?= htmlspecialchars($employee['fullname']) ?></strong>

                                        <span class="sub-text">
                                            <?= htmlspecialchars($employee['email']) ?>
                                        </span>
                                    </div>

                                </div>

                            </a>
                        </td>

                        <!-- Onboarding Phase -->

                        <td>

                            <?= htmlspecialchars($employee['current_phase'] ?? 'Not Started') ?>

                        </td>

                        <!-- Documents Submitted -->

                        <td>
                            <div class="document-summary <?= htmlspecialchars($documentsClass) ?>">
                                <strong><?= $submittedDocs ?> / <?= $totalDocs ?></strong>
                                <span><?= $pendingDocs > 0 ? $pendingDocs . ' pending' : 'All received' ?></span>
                            </div>
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
                    </tr>

                    <?php endforeach; ?>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

            <!-- ==========================================
                TABLE FOOTER
            =========================================== -->
            <div class="table-footer">
                <span class="results-count" id="resultsCount">0 employees</span>

                <div class="pagination" aria-label="Pagination">
                    <button class="page-btn" id="prevPage" type="button" aria-label="Previous page">
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>
                    <div class="page-numbers" id="pageNumbers"></div>
                    <button class="page-btn" id="nextPage" type="button" aria-label="Next page">
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </section>

    </div>
    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>
</div>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'?>