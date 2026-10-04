<?php
/**
 * Department Statistics — standalone page.
 *
 * Reached from the "View" action in the Departments table
 * (?page=department-statistics). Every figure is supplied by
 * HumanCapitalController::statistics() from real database records; the
 * fallbacks below are neutral zeros / empty arrays (never demo data) so the
 * page still renders safely if it is ever loaded without a controller.
 *
 * Expected input:
 *   $deptStats   — assoc array of summary counts
 *   $departments — one row per department (getDepartments() projection)
 *   $deptRecent  — newest departments first
 */

$pageTitle       = "Department Statistics";
$pageCSS         = "department-statistics.css";
$pageJS          = null;
$pageDescription = "Staffing distribution and departments that may need attention.";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url("?page=login"));
    exit;
}

$deptStats = $deptStats ?? [
    'total_departments'    => 0,
    'active_departments'   => 0,
    'inactive_departments' => 0,
    'total_employees'      => 0,
    'total_positions'      => 0,
    'staffed_positions'    => 0,
    'open_positions'       => 0,
    'without_head'         => 0,
];

$departments = $departments ?? [];
$deptRecent  = $deptRecent ?? [];

// ---- Derived figures (all guarded against division by zero) ----
$totalDepts    = max(0, (int) $deptStats['total_departments']);
$activeDepts   = (int) $deptStats['active_departments'];
$inactiveDepts = (int) $deptStats['inactive_departments'];
$totalEmp      = (int) $deptStats['total_employees'];
$totalPos      = (int) $deptStats['total_positions'];
$staffedPos    = (int) $deptStats['staffed_positions'];
$openPos       = (int) $deptStats['open_positions'];
$withoutHead   = (int) $deptStats['without_head'];

$activePct   = $totalDepts > 0 ? round($activeDepts / $totalDepts * 100) : 0;
$avgTeam     = $totalDepts > 0 ? round($totalEmp / $totalDepts, 1) : 0;
$vacancyRate = $totalPos > 0 ? round($openPos / $totalPos * 100) : 0;
$donutDeg    = round($activePct * 3.6);

/*
 * Headcount distribution, the largest/smallest department, and the set of
 * departments that need attention (empty, hiring, or without a head).
 */
$maxHeadcount   = 0;
$largest        = null;
$smallest       = null;
$needsAttention = [];

foreach ($departments as $department) {

    $headcount = (int) $department['employee_count'];
    $openRoles = (int) $department['vacancies'];

    $maxHeadcount = max($maxHeadcount, $headcount);

    if ($largest === null || $headcount > (int) $largest['employee_count']) {
        $largest = $department;
    }

    if ($smallest === null || $headcount < (int) $smallest['employee_count']) {
        $smallest = $department;
    }

    $reasons = [];

    if ($headcount === 0) {
        $reasons[] = ['label' => 'No employees', 'class' => 'is-vacant'];
    }

    if ($openRoles > 0) {
        $reasons[] = [
            'label' => $openRoles . ' open role' . ($openRoles === 1 ? '' : 's'),
            'class' => 'is-hiring',
        ];
    }

    if (empty($department['has_head'])) {
        $reasons[] = ['label' => 'No head', 'class' => 'is-headless'];
    }

    if (!empty($reasons)) {
        $needsAttention[] = [
            'name'    => $department['department_name'],
            'reasons' => $reasons,
        ];
    }
}

$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">

<?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>
    <div class="dept-page">

        <!-- ============ Row 1: headline stat cards ============ -->
        <div class="dept-stats">
            <div class="dept-stat-card">
                <div class="dept-stat-icon"><i class="fa-solid fa-building"></i></div>
                <div class="dept-stat-body">
                    <span class="dept-stat-value"><?= $totalDepts ?></span>
                    <span class="dept-stat-label">Total Departments</span>
                    <span class="dept-stat-sub"><?= $inactiveDepts ?> inactive</span>
                </div>
            </div>

            <div class="dept-stat-card">
                <div class="dept-stat-icon"><i class="fa-solid fa-circle-check"></i></div>
                <div class="dept-stat-body">
                    <span class="dept-stat-value"><?= $activeDepts ?></span>
                    <span class="dept-stat-label">Active Departments</span>
                    <span class="dept-stat-sub"><?= $activePct ?>% of all departments</span>
                </div>
            </div>

            <div class="dept-stat-card">
                <div class="dept-stat-icon"><i class="fa-solid fa-users"></i></div>
                <div class="dept-stat-body">
                    <span class="dept-stat-value"><?= $totalEmp ?></span>
                    <span class="dept-stat-label">Total Employees</span>
                    <span class="dept-stat-sub">Avg <?= $avgTeam ?> per department</span>
                </div>
            </div>

            <div class="dept-stat-card">
                <div class="dept-stat-icon"><i class="fa-solid fa-briefcase"></i></div>
                <div class="dept-stat-body">
                    <span class="dept-stat-value"><?= $openPos ?></span>
                    <span class="dept-stat-label">Open Roles</span>
                    <span class="dept-stat-sub"><?= $vacancyRate ?>% vacancy rate</span>
                </div>
            </div>
        </div>

        <!-- ============ Row 2: highlight strip ============ -->
        <div class="dept-highlights">
            <div class="dept-highlight">
                <i class="fa-solid fa-arrow-trend-up"></i>
                <div>
                    <span class="dept-highlight-label">Largest Department</span>
                    <strong><?= $largest ? $e($largest['department_name']) : '—' ?></strong>
                    <small><?= $largest ? (int) $largest['employee_count'] : 0 ?> employees</small>
                </div>
            </div>

            <div class="dept-highlight">
                <i class="fa-solid fa-arrow-trend-down"></i>
                <div>
                    <span class="dept-highlight-label">Smallest Department</span>
                    <strong><?= $smallest ? $e($smallest['department_name']) : '—' ?></strong>
                    <small><?= $smallest ? (int) $smallest['employee_count'] : 0 ?> employees</small>
                </div>
            </div>

            <div class="dept-highlight <?= $withoutHead > 0 ? 'is-warning' : '' ?>">
                <i class="fa-solid fa-user-tie"></i>
                <div>
                    <span class="dept-highlight-label">Without a Department Head</span>
                    <strong><?= $withoutHead ?></strong>
                    <small><?= $withoutHead > 0 ? 'Needs assignment' : 'All departments covered' ?></small>
                </div>
            </div>

            <div class="dept-highlight">
                <i class="fa-solid fa-sitemap"></i>
                <div>
                    <span class="dept-highlight-label">Total Positions</span>
                    <strong><?= $totalPos ?></strong>
                    <small><?= $staffedPos ?> staffed · <?= $openPos ?> open</small>
                </div>
            </div>
        </div>

        <!-- ============ Row 3: charts ============ -->
        <div class="dept-panels">
            <section class="dept-panel dept-panel-wide">
                <header class="dept-panel-head">
                    <h3><i class="fa-solid fa-chart-simple"></i> Headcount by Department</h3>
                    <span class="dept-panel-meta"><?= $totalEmp ?> employees</span>
                </header>

                <?php if (empty($departments)): ?>
                    <p class="dept-empty">No department data yet.</p>
                <?php else: ?>
                    <ul class="dept-bars">
                        <?php foreach ($departments as $department):
                            $count = (int) $department['employee_count'];
                            $barW  = $maxHeadcount > 0 ? round($count / $maxHeadcount * 100) : 0;
                            $share = $totalEmp > 0 ? round($count / $totalEmp * 100) : 0;
                        ?>
                            <li class="dept-bar-row">
                                <span class="dept-bar-name"><?= $e($department['department_name']) ?></span>
                                <div class="dept-bar-track">
                                    <div class="dept-bar-fill" style="width: <?= $barW ?>%"></div>
                                </div>
                                <span class="dept-bar-value"><?= $count ?> <small>(<?= $share ?>%)</small></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section class="dept-panel">
                <header class="dept-panel-head">
                    <h3><i class="fa-solid fa-chart-pie"></i> Department Status</h3>
                </header>

                <div class="dept-donut-wrap">
                    <div class="dept-donut" style="--active-deg: <?= $donutDeg ?>deg">
                        <div class="dept-donut-center">
                            <strong><?= $activePct ?>%</strong>
                            <small>active</small>
                        </div>
                    </div>
                    <ul class="dept-legend">
                        <li><span class="dot dot-active"></span> Active <b><?= $activeDepts ?></b></li>
                        <li><span class="dot dot-inactive"></span> Inactive <b><?= $inactiveDepts ?></b></li>
                    </ul>
                </div>
            </section>
        </div>

        <!-- ============ Row 4: departments needing attention ============ -->
        <div class="dept-panels">
            <section class="dept-panel dept-panel-full">
                <header class="dept-panel-head">
                    <h3><i class="fa-solid fa-triangle-exclamation"></i> Departments Needing Attention</h3>
                    <span class="dept-panel-meta"><?= count($needsAttention) ?> flagged</span>
                </header>

                <?php if (empty($needsAttention)): ?>
                    <p class="dept-empty">All departments are staffed and headed.</p>
                <?php else: ?>
                    <ul class="dept-attention">
                        <?php foreach ($needsAttention as $item): ?>
                            <li class="dept-attention-item">
                                <span class="dept-attention-name"><?= $e($item['name']) ?></span>
                                <span class="dept-attention-reasons">
                                    <?php foreach ($item['reasons'] as $reason): ?>
                                        <span class="dept-tag <?= $reason['class'] ?>"><?= $e($reason['label']) ?></span>
                                    <?php endforeach; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>

        <!-- ============ Row 5: detail lists ============ -->
        <div class="dept-panels dept-panels-even">
            <section class="dept-panel">
                <header class="dept-panel-head">
                    <h3><i class="fa-solid fa-briefcase"></i> Position Distribution</h3>
                </header>

                <?php if (empty($departments)): ?>
                    <p class="dept-empty">No department data yet.</p>
                <?php else: ?>
                    <table class="dept-mini-table">
                        <thead>
                            <tr><th>Department</th><th>Positions</th><th>Staffed</th><th>Open</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($departments as $department): ?>
                                <tr>
                                    <td><?= $e($department['department_name']) ?></td>
                                    <td><?= (int) $department['position_count'] ?></td>
                                    <td><?= (int) $department['staffed_position_count'] ?></td>
                                    <td><?= (int) $department['vacancies'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <section class="dept-panel">
                <header class="dept-panel-head">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> Recently Added</h3>
                </header>

                <?php if (empty($deptRecent)): ?>
                    <p class="dept-empty">No recent departments.</p>
                <?php else: ?>
                    <ul class="dept-recent">
                        <?php foreach ($deptRecent as $r): ?>
                            <li>
                                <div>
                                    <strong><?= $e($r['department_name']) ?></strong>
                                    <small>Added <?= $e(date('M d, Y', strtotime($r['created_at']))) ?></small>
                                </div>
                                <span class="status-badge <?= strtolower($r['status'] ?? 'Active') ?>">
                                    <span class="status-dot"></span>
                                    <?= $e($r['status'] ?? 'Active') ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>

    </div>
    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>
</div>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>
