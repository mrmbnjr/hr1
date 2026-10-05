<?php
/**
 * Department Details / Overview — single-department page.
 *
 * Reached from the "View" action in the Departments table:
 *     ?page=department-statistics&id=<department_id>
 *
 * Every figure and list is supplied by HumanCapitalController::statistics()
 * from real database rows for the selected department only. When the page is
 * loaded without a department there is nothing to show, so it redirects back
 * to Human Capital instead of rendering placeholder data.
 *
 * Expected input:
 *   $department      — departments row (SELECT d.*) + heads / head / has_head
 *   $departmentStats — employees / positions / vacancies / onboarding figures
 *   $positions       — positions in the department
 *   $employees       — employees in the department
 *   $recruitment     — ['summary' => [...], 'postings' => [...], 'interviews' => [...]]
 *   $onboarding      — employees still onboarding, with progress
 *   $recentActivity  — newest department events first
 */

$pageTitle       = "Department Overview";
$pageCSS         = "department-statistics.css";
$pageJS          = "department-statistics.js";
$pageDescription = "Workforce, positions, hiring and onboarding for a single department.";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url("?page=login"));
    exit;
}

if (empty($department) || !is_array($department)) {
    header("Location: " . url("?page=human-capital"));
    exit;
}

$departmentStats = $departmentStats ?? [];
$positions       = $positions ?? [];
$employees       = $employees ?? [];
$recruitment     = $recruitment ?? [];
$onboarding      = $onboarding ?? [];
$recentActivity  = $recentActivity ?? [];

$e       = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$pct     = fn($part, $total) => $total > 0 ? (int) round($part / $total * 100) : 0;
$fmtDate = fn($d) => $d ? date('M j, Y', strtotime((string) $d)) : '—';
$plural  = fn($n, $one, $many = null) => $n . ' ' . ($n === 1 ? $one : ($many ?? $one . 's'));

// ---- Header ----
$deptId     = (int) $department['department_id'];
$deptName   = (string) $department['department_name'];
$deptStatus = (string) ($department['status'] ?? 'Active');
$deptCode   = $department['department_code'] ?? $department['code'] ?? null;  // only if the column exists
$deptDesc   = $department['description'] ?? null;                              // only if the column exists
$head       = $department['head'] ?? null;
$headCount  = count($department['heads'] ?? []);

// ---- Figures ----
$emp = $departmentStats['employees']  ?? [];
$pos = $departmentStats['positions']  ?? [];
$onb = $departmentStats['onboarding'] ?? [];

$empTotal    = (int) ($emp['total'] ?? 0);
$empActive   = (int) ($emp['active'] ?? 0);
$empInactive = (int) ($emp['inactive'] ?? 0);
$empNoAcct   = (int) ($emp['no_account'] ?? 0);
$newHires    = (int) ($emp['new_hires_30d'] ?? 0);

$posTotal    = (int) ($pos['total'] ?? 0);
$posStaffed  = (int) ($pos['staffed'] ?? 0);
$posUnfilled = (int) ($pos['unfilled'] ?? 0);

$vacancies    = (int) ($departmentStats['vacancies'] ?? 0);
$openPostings = (int) ($departmentStats['open_postings'] ?? 0);

$obActive  = (int) ($onb['active'] ?? 0);
$obOverdue = (int) ($onb['overdue'] ?? 0);

$typeRows = [
    ['label' => 'Regular',      'count' => (int) ($emp['regular'] ?? 0)],
    ['label' => 'Probationary', 'count' => (int) ($emp['probationary'] ?? 0)],
    ['label' => 'Contract',     'count' => (int) ($emp['contract'] ?? 0)],
];
if ((int) ($emp['other'] ?? 0) > 0) {
    $typeRows[] = ['label' => 'Unspecified', 'count' => (int) $emp['other']];
}

$recSummary    = $recruitment['summary'] ?? [];
$recPostings   = $recruitment['postings'] ?? [];
$recInterviews = $recruitment['interviews'] ?? [];

// ---- Flags shown under the header (this department only) ----
$alerts = [];
if (!$department['has_head']) {
    $alerts[] = ['class' => 'is-headless', 'icon' => 'fa-user-tie', 'text' => 'No department head assigned'];
}
if ($empTotal === 0) {
    $alerts[] = ['class' => 'is-vacant', 'icon' => 'fa-users-slash', 'text' => 'No employees in this department'];
}
if ($obOverdue > 0) {
    $alerts[] = ['class' => 'is-vacant', 'icon' => 'fa-hourglass-end', 'text' => $plural($obOverdue, 'overdue onboarding', 'overdue onboardings')];
}
if ($deptStatus === 'Inactive' && $openPostings > 0) {
    $alerts[] = ['class' => 'is-hiring', 'icon' => 'fa-bullhorn', 'text' => 'Inactive department still has open postings'];
}

$employmentClass = ['Regular' => 'is-regular', 'Probationary' => 'is-probationary', 'Contract' => 'is-contract'];
$onboardClass    = ['Overdue' => 'is-vacant', 'In Progress' => 'is-hiring', 'Pending' => 'is-headless'];
$eventIcon       = [
    'hire'           => 'fa-user-plus',
    'position'       => 'fa-sitemap',
    'posting'        => 'fa-bullhorn',
    'posting_closed' => 'fa-circle-xmark',
    'application'    => 'fa-file-lines',
    'interview'      => 'fa-calendar-check',
    'onboarding'     => 'fa-circle-check',
];

$tenure = function ($date) {
    if (!$date) {
        return '';
    }
    try {
        $diff = (new DateTime((string) $date))->diff(new DateTime('today'));
    } catch (Exception $ex) {
        return '';
    }
    if ($diff->invert) {
        return 'Starts ' . date('M j', strtotime((string) $date));
    }
    if ($diff->y > 0) {
        return $diff->y . ' yr' . ($diff->y > 1 ? 's' : '') . ($diff->m ? ' ' . $diff->m . ' mo' : '');
    }
    if ($diff->m > 0) {
        return $diff->m . ' mo';
    }
    return $diff->days . ' day' . ($diff->days === 1 ? '' : 's');
};
?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">

<?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>
    <div class="dept-page">

        <!-- ============ Department header ============ -->
        <section class="dept-header">
            <div class="dept-header-main">
                <div class="dept-header-icon"><i class="fa-solid fa-building"></i></div>

                <div class="dept-header-info">
                    <div class="dept-header-title">
                        <h1><?= $e($deptName) ?></h1>
                        <span class="status-badge <?= $e(strtolower($deptStatus)) ?>">
                            <span class="status-dot"></span>
                            <?= $e($deptStatus) ?>
                        </span>
                        <?php if (!empty($deptCode)): ?>
                            <span class="dept-code"><?= $e($deptCode) ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($deptDesc)): ?>
                        <p class="dept-header-desc"><?= $e($deptDesc) ?></p>
                    <?php endif; ?>

                    <div class="dept-header-meta">
                        <span>
                            <i class="fa-solid fa-user-tie"></i>
                            Head:
                            <?php if ($head): ?>
                                <strong><?= $e($head['employee_name']) ?></strong>
                                <small>· <?= $e($head['position_name']) ?><?= $headCount > 1 ? ' · +' . ($headCount - 1) . ' more' : '' ?></small>
                            <?php else: ?>
                                <strong class="dept-muted">Not assigned</strong>
                            <?php endif; ?>
                        </span>
                        <span>
                            <i class="fa-solid fa-calendar-days"></i>
                            Created <?= $e($fmtDate($department['created_at'] ?? null)) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="dept-header-actions">
                <button type="button" class="dept-btn dept-btn-primary" id="deptEditBtn">
                    <i class="fa-solid fa-pen"></i> Edit Department
                </button>
                <a class="dept-btn" href="#dept-employees">
                    <i class="fa-solid fa-users"></i> View Employees
                </a>
                <a class="dept-btn" href="<?= $e(url('?page=human-capital')) ?>">
                    <i class="fa-solid fa-sitemap"></i> Manage Positions
                </a>
            </div>
        </section>

        <?php if (!empty($alerts)): ?>
            <ul class="dept-alerts">
                <?php foreach ($alerts as $alert): ?>
                    <li class="dept-tag <?= $e($alert['class']) ?>">
                        <i class="fa-solid <?= $e($alert['icon']) ?>"></i> <?= $e($alert['text']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- ============ KPI cards ============ -->
        <div class="dept-stats">
            <div class="dept-stat-card">
                <div class="dept-stat-icon"><i class="fa-solid fa-users"></i></div>
                <div class="dept-stat-body">
                    <span class="dept-stat-value"><?= $empTotal ?></span>
                    <span class="dept-stat-label">Employees</span>
                    <span class="dept-stat-sub"><?= $newHires ?> hired in last 30 days</span>
                </div>
            </div>

            <div class="dept-stat-card">
                <div class="dept-stat-icon"><i class="fa-solid fa-sitemap"></i></div>
                <div class="dept-stat-body">
                    <span class="dept-stat-value"><?= $posTotal ?></span>
                    <span class="dept-stat-label">Positions</span>
                    <span class="dept-stat-sub"><?= $posStaffed ?> staffed · <?= $posUnfilled ?> unfilled</span>
                </div>
            </div>

            <div class="dept-stat-card">
                <div class="dept-stat-icon"><i class="fa-solid fa-briefcase"></i></div>
                <div class="dept-stat-body">
                    <span class="dept-stat-value"><?= $vacancies ?></span>
                    <span class="dept-stat-label">Open Vacancies</span>
                    <span class="dept-stat-sub"><?= $plural($openPostings, 'open posting') ?></span>
                </div>
            </div>

            <div class="dept-stat-card <?= $obOverdue > 0 ? 'is-warning' : '' ?>">
                <div class="dept-stat-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                <div class="dept-stat-body">
                    <span class="dept-stat-value"><?= $obActive ?></span>
                    <span class="dept-stat-label">Onboarding</span>
                    <span class="dept-stat-sub"><?= $obOverdue > 0 ? $obOverdue . ' overdue' : 'None overdue' ?></span>
                </div>
            </div>
        </div>

        <!-- ============ Workforce overview ============ -->
        <section class="dept-panel dept-block">
            <header class="dept-panel-head">
                <h3><i class="fa-solid fa-chart-simple"></i> Workforce Overview</h3>
                <span class="dept-panel-meta"><?= $plural($empTotal, 'employee') ?></span>
            </header>

            <?php if ($empTotal === 0): ?>
                <p class="dept-empty">No employees are assigned to this department yet.</p>
            <?php else: ?>
                <div class="dept-workforce">
                    <div>
                        <h4 class="dept-subhead">Account status</h4>
                        <div class="dept-split" role="img"
                             aria-label="<?= $empActive ?> active, <?= $empInactive ?> inactive, <?= $empNoAcct ?> without an account">
                            <span class="seg seg-active"   style="width: <?= $pct($empActive, $empTotal) ?>%"></span>
                            <span class="seg seg-inactive" style="width: <?= $pct($empInactive, $empTotal) ?>%"></span>
                            <span class="seg seg-none"     style="width: <?= $pct($empNoAcct, $empTotal) ?>%"></span>
                        </div>
                        <ul class="dept-legend dept-legend-wide">
                            <li><span class="dot dot-active"></span> Active <b><?= $empActive ?></b></li>
                            <li><span class="dot dot-inactive"></span> Inactive <b><?= $empInactive ?></b></li>
                            <li><span class="dot dot-none"></span> No account <b><?= $empNoAcct ?></b></li>
                        </ul>
                        <p class="dept-note">Based on each employee's linked system account.</p>
                    </div>

                    <div>
                        <h4 class="dept-subhead">Employment status</h4>
                        <ul class="dept-bars">
                            <?php foreach ($typeRows as $row): ?>
                                <li class="dept-bar-row">
                                    <span class="dept-bar-name"><?= $e($row['label']) ?></span>
                                    <div class="dept-bar-track">
                                        <div class="dept-bar-fill" style="width: <?= $pct($row['count'], $empTotal) ?>%"></div>
                                    </div>
                                    <span class="dept-bar-value"><?= $row['count'] ?> <small>(<?= $pct($row['count'], $empTotal) ?>%)</small></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- ============ Positions | Hiring & Staffing ============ -->
        <div class="dept-panels dept-panels-even">

            <section class="dept-panel" id="dept-positions">
                <header class="dept-panel-head">
                    <h3><i class="fa-solid fa-sitemap"></i> Positions</h3>
                    <span class="dept-panel-meta"><?= $plural($posTotal, 'position') ?></span>
                </header>

                <?php if (empty($positions)): ?>
                    <p class="dept-empty">No positions have been created for this department.</p>
                <?php else: ?>
                    <div class="dept-table-wrap">
                        <table class="dept-mini-table">
                            <thead>
                                <tr>
                                    <th scope="col">Position</th>
                                    <th scope="col">Staff</th>
                                    <th scope="col">Open</th>
                                    <th scope="col">Flags</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($positions as $position): ?>
                                    <?php
                                        $staff   = (int) $position['employee_count'];
                                        $openFor = (int) $position['vacancies'];
                                    ?>
                                    <tr>
                                        <td>
                                            <strong class="dept-cell-main"><?= $e($position['position_name']) ?></strong>
                                            <small class="dept-cell-sub"><?= $e($position['role_name'] ?? '—') ?></small>
                                        </td>
                                        <td><?= $staff ?></td>
                                        <td><?= $openFor ?></td>
                                        <td>
                                            <span class="dept-flags">
                                                <?php if (($position['role_code'] ?? '') === 'MGR'): ?>
                                                    <span class="dept-tag is-role">Head role</span>
                                                <?php endif; ?>
                                                <?php if ($staff === 0): ?>
                                                    <span class="dept-tag is-vacant">Unfilled</span>
                                                <?php endif; ?>
                                                <?php if ($position['status'] === 'Open'): ?>
                                                    <span class="dept-tag is-hiring">Hiring</span>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

            <section class="dept-panel" id="dept-hiring">
                <header class="dept-panel-head">
                    <h3><i class="fa-solid fa-bullhorn"></i> Hiring &amp; Staffing</h3>
                    <span class="dept-panel-meta"><?= $plural((int) ($recSummary['open_postings'] ?? 0), 'open posting') ?></span>
                </header>

                <div class="dept-mini-stats">
                    <div><b><?= (int) ($recSummary['vacancies'] ?? 0) ?></b><span>Vacancies</span></div>
                    <div><b><?= (int) ($recSummary['active_applicants'] ?? 0) ?></b><span>Active applicants</span></div>
                    <div><b><?= (int) ($recSummary['upcoming_interviews'] ?? 0) ?></b><span>Upcoming interviews</span></div>
                    <div><b><?= (int) ($recSummary['hired'] ?? 0) ?></b><span>Hired</span></div>
                </div>

                <h4 class="dept-subhead">Open job postings</h4>
                <?php if (empty($recPostings)): ?>
                    <p class="dept-empty">No open job postings for this department.</p>
                <?php else: ?>
                    <ul class="dept-postings">
                        <?php foreach ($recPostings as $posting): ?>
                            <?php $pastDeadline = strtotime((string) $posting['application_deadline']) < strtotime('today'); ?>
                            <li>
                                <div class="dept-posting-main">
                                    <strong><?= $e($posting['title']) ?></strong>
                                    <small>
                                        <?= $e($posting['position_name']) ?> · <?= $e($posting['employment_type']) ?>
                                        · Deadline <?= $e($fmtDate($posting['application_deadline'])) ?>
                                        <?php if ($pastDeadline): ?>
                                            <span class="dept-tag is-hiring">Past deadline</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="dept-posting-stats">
                                    <span><b><?= (int) $posting['vacancies'] ?></b> vacancies</span>
                                    <span><b><?= (int) $posting['applicants'] ?></b>/<?= (int) $posting['max_applicants'] ?> applicants</span>
                                    <span><b><?= (int) $posting['upcoming_interviews'] ?></b> interviews</span>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h4 class="dept-subhead">Interviews awaiting a result</h4>
                <?php if (empty($recInterviews)): ?>
                    <p class="dept-empty dept-empty-tight">No interviews are waiting for a result.</p>
                <?php else: ?>
                    <ul class="dept-interviews">
                        <?php foreach ($recInterviews as $iv): ?>
                            <?php $isPast = strtotime((string) $iv['interview_date']) < time(); ?>
                            <li>
                                <div>
                                    <strong><?= $e($iv['applicant_name']) ?></strong>
                                    <small><?= $e($iv['posting_title']) ?> · <?= $e($iv['interview_type']) ?></small>
                                </div>
                                <div class="dept-interview-when">
                                    <span><?= $e(date('M j, g:i A', strtotime((string) $iv['interview_date']))) ?></span>
                                    <?php if ($isPast): ?>
                                        <span class="dept-tag is-hiring">Awaiting result</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>

        <!-- ============ Employees ============ -->
        <section class="dept-panel dept-block" id="dept-employees">
            <header class="dept-panel-head">
                <h3><i class="fa-solid fa-users"></i> Employees</h3>
                <?php if (!empty($employees)): ?>
                    <label class="dept-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" id="deptEmployeeSearch" placeholder="Search name, number or position"
                               aria-label="Search employees in this department" autocomplete="off">
                    </label>
                <?php endif; ?>
            </header>

            <?php if (empty($employees)): ?>
                <p class="dept-empty">No employees belong to this department yet.</p>
            <?php else: ?>
                <div class="dept-table-wrap dept-scroll">
                    <table class="dept-mini-table" id="deptEmployeeTable">
                        <thead>
                            <tr>
                                <th scope="col">Employee</th>
                                <th scope="col">Position</th>
                                <th scope="col">Employment</th>
                                <th scope="col">Hired</th>
                                <th scope="col">Account</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp_row): ?>
                                <?php
                                    $search = strtolower(
                                        $emp_row['employee_name'] . ' ' .
                                        ($emp_row['employee_number'] ?? '') . ' ' .
                                        $emp_row['position_name']
                                    );
                                    $acct = $emp_row['account_status'] ?? null;
                                ?>
                                <tr data-search="<?= $e($search) ?>">
                                    <td>
                                        <strong class="dept-cell-main"><?= $e($emp_row['employee_name'] !== '' ? $emp_row['employee_name'] : 'Unnamed') ?></strong>
                                        <small class="dept-cell-sub"><?= $e($emp_row['employee_number'] ?? '—') ?></small>
                                    </td>
                                    <td>
                                        <?= $e($emp_row['position_name']) ?>
                                        <?php if (!empty($emp_row['is_head'])): ?>
                                            <span class="dept-tag is-role">Head</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="dept-tag <?= $e($employmentClass[$emp_row['employment_status']] ?? 'is-contract') ?>">
                                            <?= $e($emp_row['employment_status'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $e($fmtDate($emp_row['hire_date'])) ?>
                                        <small class="dept-cell-sub"><?= $e($tenure($emp_row['hire_date'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($acct): ?>
                                            <span class="status-badge <?= $e(strtolower($acct)) ?>">
                                                <span class="status-dot"></span><?= $e($acct) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="dept-muted">No account</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="dept-empty" id="deptEmployeeEmpty" hidden>No employees match your search.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- ============ Onboarding | Recent activity ============ -->
        <div class="dept-panels dept-panels-even">

            <section class="dept-panel" id="dept-onboarding">
                <header class="dept-panel-head">
                    <h3><i class="fa-solid fa-clipboard-list"></i> Onboarding</h3>
                    <span class="dept-panel-meta"><?= count($onboarding) ?> in progress</span>
                </header>

                <?php if (empty($onboarding)): ?>
                    <p class="dept-empty">Nobody in this department is currently onboarding.</p>
                <?php else: ?>
                    <ul class="dept-onboarding">
                        <?php foreach ($onboarding as $ob): ?>
                            <li>
                                <div class="dept-onboarding-top">
                                    <div>
                                        <strong><?= $e($ob['employee_name'] !== '' ? $ob['employee_name'] : 'Unnamed') ?></strong>
                                        <small><?= $e($ob['position_name']) ?><?= !empty($ob['started_at']) ? ' · Started ' . $e($fmtDate($ob['started_at'])) : '' ?></small>
                                    </div>
                                    <span class="dept-tag <?= $e($onboardClass[$ob['onboarding_status']] ?? 'is-headless') ?>">
                                        <?= $e($ob['onboarding_status']) ?>
                                    </span>
                                </div>

                                <div class="dept-progress">
                                    <div class="dept-bar-track" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                         aria-valuenow="<?= (int) $ob['progress'] ?>">
                                        <div class="dept-bar-fill" style="width: <?= (int) $ob['progress'] ?>%"></div>
                                    </div>
                                    <span class="dept-progress-value"><?= (int) $ob['progress'] ?>%</span>
                                </div>

                                <small class="dept-onboarding-foot">
                                    <?= (int) $ob['steps_done'] ?>/<?= (int) $ob['steps_total'] ?> steps
                                    <?= $ob['next_step'] ? '· Next: ' . $e($ob['next_step']) : '· All steps done' ?>
                                    <?php if ((int) $ob['docs_total'] > 0): ?>
                                        · Docs <?= (int) $ob['docs_verified'] ?>/<?= (int) $ob['docs_total'] ?> verified
                                    <?php endif; ?>
                                    <?php if (!empty($ob['orientation_date'])): ?>
                                        · Orientation <?= $e($fmtDate($ob['orientation_date'])) ?>
                                    <?php endif; ?>
                                </small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section class="dept-panel" id="dept-activity">
                <header class="dept-panel-head">
                    <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Department Activity</h3>
                </header>

                <?php if (empty($recentActivity)): ?>
                    <p class="dept-empty">No activity recorded for this department yet.</p>
                <?php else: ?>
                    <ul class="dept-timeline">
                        <?php foreach ($recentActivity as $event): ?>
                            <li>
                                <span class="dept-timeline-icon">
                                    <i class="fa-solid <?= $e($eventIcon[$event['event_type']] ?? 'fa-circle') ?>"></i>
                                </span>
                                <div>
                                    <span class="dept-timeline-text"><?= $e($event['description']) ?></span>
                                    <small><?= $e($fmtDate($event['event_at'])) ?></small>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        </div>

        <!-- ============ Edit department modal ============ -->
        <div class="dept-modal" id="deptEditModal" hidden>
            <div class="dept-modal-backdrop" data-close></div>

            <form class="dept-modal-card" id="deptEditForm" method="post"
                  data-save-url="<?= $e(url('?page=save-department')) ?>"
                  role="dialog" aria-modal="true" aria-labelledby="deptEditTitle">

                <header class="dept-modal-head">
                    <h3 id="deptEditTitle">Edit Department</h3>
                    <button type="button" class="dept-modal-x" data-close aria-label="Close">&times;</button>
                </header>

                <input type="hidden" name="department_id" value="<?= $deptId ?>">

                <label class="dept-field">
                    <span>Department name</span>
                    <input type="text" name="department_name" maxlength="100" required
                           value="<?= $e($deptName) ?>">
                </label>

                <label class="dept-field">
                    <span>Status</span>
                    <select name="status">
                        <option value="Active"   <?= $deptStatus === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $deptStatus === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </label>

                <p class="dept-modal-error" id="deptEditError" hidden></p>

                <footer class="dept-modal-foot">
                    <button type="button" class="dept-btn" data-close>Cancel</button>
                    <button type="submit" class="dept-btn dept-btn-primary" id="deptEditSave">Save changes</button>
                </footer>
            </form>
        </div>

    </div>
    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>
</div>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>