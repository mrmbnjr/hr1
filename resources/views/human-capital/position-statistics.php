<?php
/**
 * Position Statistics — standalone page.
 *
 * Reached from the "View" action in the Positions table
 * (?page=position-statistics). Every figure is supplied by
 * HumanCapitalController::positionStatistics() from real database records; the
 * fallbacks below are neutral zeros / empty arrays (never demo data) so the
 * page still renders safely if it is ever loaded without a controller.
 *
 * A "slot" is a position's planned headcount: filled (people who hold it)
 * plus open (vacancies still to fill). There is no slots column, so slots
 * are derived from current records.
 *
 * Expected input:
 *   $posStats    — assoc array of summary counts
 *   $departments — one row per department (getDepartments() projection)
 *   $positions   — one row per position (getPositions() projection)
 *   $posRecent   — newest positions first
 */

$pageTitle       = "Position Statistics";
$pageCSS         = "position-statistics.css";
$pageJS          = null;
$pageDescription = "Staffing availability and positions that need filling.";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url("?page=login"));
    exit;
}

$posStats = $posStats ?? [
    'total_positions'      => 0,
    'total_slots'          => 0,
    'filled_slots'         => 0,
    'open_slots'           => 0,
    'departments_covered'  => 0,
    'total_departments'    => 0,
    'unassigned_positions' => 0,
];

$departments = $departments ?? [];
$positions   = $positions ?? [];
$posRecent   = $posRecent ?? [];

// ---- Derived figures (all guarded against division by zero) ----
$totalPos    = (int) $posStats['total_positions'];
$totalSlots  = (int) $posStats['total_slots'];
$filledSlots = (int) $posStats['filled_slots'];
$openSlots   = (int) $posStats['open_slots'];
$deptCovered = (int) $posStats['departments_covered'];
$deptTotal   = (int) $posStats['total_departments'];
$unassigned  = (int) $posStats['unassigned_positions'];

$fillPct    = $totalSlots > 0 ? round($filledSlots / $totalSlots * 100) : 0;
$vacancyPct = $totalSlots > 0 ? 100 - $fillPct : 0;
$deptsEmpty = max(0, $deptTotal - $deptCovered);
$donutDeg   = round($fillPct * 3.6);

/*
 * Slots per department (stacked filled / open) and the department with the
 * most positions.
 */
$maxSlots = 0;
$topDept  = null;

foreach ($departments as $department) {

    $slots = (int) $department['employee_count'] + (int) $department['vacancies'];

    $maxSlots = max($maxSlots, $slots);

    $positionCount = (int) $department['position_count'];

    if ($positionCount > 0 && ($topDept === null || $positionCount > $topDept['positions'])) {
        $topDept = [
            'name'      => $department['department_name'],
            'positions' => $positionCount,
        ];
    }
}

/*
 * Positions still to fill (open vacancies > 0), biggest staffing gap first.
 */
$positionsToFill = array_values(
    array_filter(
        $positions,
        fn($position) => (int) $position['vacancies'] > 0
    )
);

usort(
    $positionsToFill,
    fn($a, $b) => (int) $b['vacancies'] <=> (int) $a['vacancies']
);

$largestGap = $positionsToFill[0] ?? null;

$e = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">

<?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>
    <div class="pos-page">

        <!-- ============ Row 1: headline stat cards ============ -->
        <div class="pos-stats">
            <div class="pos-stat-card">
                <div class="pos-stat-icon"><i class="fa-solid fa-id-badge"></i></div>
                <div class="pos-stat-body">
                    <span class="pos-stat-value"><?= $totalPos ?></span>
                    <span class="pos-stat-label">Total Positions</span>
                    <span class="pos-stat-sub"><?= $totalSlots ?> headcount slots</span>
                </div>
            </div>

            <div class="pos-stat-card">
                <div class="pos-stat-icon"><i class="fa-solid fa-user-check"></i></div>
                <div class="pos-stat-body">
                    <span class="pos-stat-value"><?= $filledSlots ?></span>
                    <span class="pos-stat-label">Filled Slots</span>
                    <span class="pos-stat-sub"><?= $fillPct ?>% fill rate</span>
                </div>
            </div>

            <div class="pos-stat-card">
                <div class="pos-stat-icon"><i class="fa-solid fa-user-plus"></i></div>
                <div class="pos-stat-body">
                    <span class="pos-stat-value"><?= $openSlots ?></span>
                    <span class="pos-stat-label">Open Slots</span>
                    <span class="pos-stat-sub"><?= $vacancyPct ?>% vacancy rate</span>
                </div>
            </div>

            <div class="pos-stat-card">
                <div class="pos-stat-icon"><i class="fa-solid fa-building"></i></div>
                <div class="pos-stat-body">
                    <span class="pos-stat-value"><?= $deptCovered ?><small>/<?= $deptTotal ?></small></span>
                    <span class="pos-stat-label">Departments Covered</span>
                    <span class="pos-stat-sub"><?= $deptsEmpty ?> without positions</span>
                </div>
            </div>
        </div>

        <!-- ============ Row 2: highlight strip ============ -->
        <div class="pos-highlights">
            <div class="pos-highlight">
                <i class="fa-solid fa-arrow-trend-up"></i>
                <div>
                    <span class="pos-highlight-label">Most Positions</span>
                    <strong><?= $topDept ? $e($topDept['name']) : '—' ?></strong>
                    <small><?= $topDept ? (int) $topDept['positions'] : 0 ?> positions</small>
                </div>
            </div>

            <div class="pos-highlight <?= $largestGap ? 'is-warning' : '' ?>">
                <i class="fa-solid fa-bullseye"></i>
                <div>
                    <span class="pos-highlight-label">Largest Staffing Gap</span>
                    <strong><?= $largestGap ? $e($largestGap['position_name']) : '—' ?></strong>
                    <small><?= $largestGap ? (int) $largestGap['vacancies'] : 0 ?> open</small>
                </div>
            </div>

            <div class="pos-highlight <?= $deptsEmpty > 0 ? 'is-warning' : '' ?>">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <div>
                    <span class="pos-highlight-label">Departments w/o Positions</span>
                    <strong><?= $deptsEmpty ?></strong>
                    <small><?= $deptsEmpty > 0 ? 'Needs setup' : 'All covered' ?></small>
                </div>
            </div>

            <div class="pos-highlight <?= $unassigned > 0 ? 'is-warning' : '' ?>">
                <i class="fa-solid fa-link-slash"></i>
                <div>
                    <span class="pos-highlight-label">Unassigned Positions</span>
                    <strong><?= $unassigned ?></strong>
                    <small><?= $unassigned > 0 ? 'No employees yet' : 'All staffed' ?></small>
                </div>
            </div>
        </div>

        <!-- ============ Row 3: charts ============ -->
        <div class="pos-panels">
            <section class="pos-panel">
                <header class="pos-panel-head">
                    <h3><i class="fa-solid fa-chart-simple"></i> Slots by Department</h3>
                    <span class="pos-legend-inline">
                        <span class="dot dot-filled"></span> Filled
                        <span class="dot dot-open"></span> Open
                    </span>
                </header>

                <?php if (empty($departments)): ?>
                    <p class="pos-empty">No position data yet.</p>
                <?php else: ?>
                    <ul class="pos-bars">
                        <?php foreach ($departments as $department):
                            $filled  = (int) $department['employee_count'];
                            $open    = (int) $department['vacancies'];
                            $filledW = $maxSlots > 0 ? round($filled / $maxSlots * 100) : 0;
                            $openW   = $maxSlots > 0 ? round($open / $maxSlots * 100) : 0;
                        ?>
                            <li class="pos-bar-row">
                                <span class="pos-bar-name"><?= $e($department['department_name']) ?></span>
                                <div class="pos-bar-track">
                                    <div class="pos-bar-filled" style="width: <?= $filledW ?>%"></div>
                                    <div class="pos-bar-open" style="width: <?= $openW ?>%"></div>
                                </div>
                                <span class="pos-bar-value"><?= $filled ?>/<?= $filled + $open ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section class="pos-panel">
                <header class="pos-panel-head">
                    <h3><i class="fa-solid fa-chart-pie"></i> Fill Status</h3>
                </header>

                <div class="pos-donut-wrap">
                    <div class="pos-donut" style="--filled-deg: <?= $donutDeg ?>deg">
                        <div class="pos-donut-center">
                            <strong><?= $fillPct ?>%</strong>
                            <small>filled</small>
                        </div>
                    </div>
                    <ul class="pos-legend">
                        <li><span class="dot dot-filled"></span> Filled <b><?= $filledSlots ?></b></li>
                        <li><span class="dot dot-open"></span> Open <b><?= $openSlots ?></b></li>
                    </ul>
                </div>
            </section>
        </div>

        <!-- ============ Row 4: detail lists ============ -->
        <div class="pos-panels pos-panels-even">
            <section class="pos-panel">
                <header class="pos-panel-head">
                    <h3><i class="fa-solid fa-user-plus"></i> Positions to Fill</h3>
                    <span class="pos-panel-meta"><?= count($positionsToFill) ?> open</span>
                </header>

                <?php if (empty($positionsToFill)): ?>
                    <p class="pos-empty">All positions are fully staffed.</p>
                <?php else: ?>
                    <table class="pos-mini-table">
                        <thead>
                            <tr><th>Position</th><th>Department</th><th>Filled</th><th>Open</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($positionsToFill as $position):
                                $filled = (int) $position['employee_count'];
                                $open   = (int) $position['vacancies'];
                            ?>
                                <tr>
                                    <td><?= $e($position['position_name']) ?></td>
                                    <td><?= $e($position['department_name']) ?></td>
                                    <td><?= $filled ?>/<?= $filled + $open ?></td>
                                    <td><span class="pos-badge is-open"><?= $open ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

            <section class="pos-panel">
                <header class="pos-panel-head">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> Recently Added</h3>
                </header>

                <?php if (empty($posRecent)): ?>
                    <p class="pos-empty">No recent positions.</p>
                <?php else: ?>
                    <ul class="pos-recent">
                        <?php foreach ($posRecent as $r): ?>
                            <li>
                                <div>
                                    <strong><?= $e($r['position_name']) ?></strong>
                                    <small><?= $e($r['department_name']) ?> · Added <?= $e(date('M d, Y', strtotime($r['created_at']))) ?></small>
                                </div>
                                <span class="status-badge <?= strtolower($r['status'] ?? 'Closed') ?>">
                                    <span class="status-dot"></span>
                                    <?= $e($r['status'] ?? 'Closed') ?>
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
