<?php
/**
 * Position Details / Overview — single-position page.
 *
 * Reached from the "View" action in the Positions table:
 *     ?page=position-statistics&id=<position_id>
 *
 * Every figure and list is supplied by
 * HumanCapitalController::positionStatistics() from real database rows for
 * the selected position only. When the page is loaded without a position
 * there is nothing to show, so it redirects back to Human Capital instead of
 * rendering placeholder data.
 *
 * Expected input:
 *   $position       — positions row + department / role / derived status
 *   $positionStats  — employees / vacancies / postings / applicants figures
 *   $employees      — employees currently holding the position
 *   $recruitment    — ['summary' => [...], 'postings' => [...], 'interviews' => [...]]
 *   $onboarding     — employees still onboarding, with progress
 *   $recentActivity — newest position events first
 *   $departmentLookup / $roles — options for the Edit Position modal
 */

$pageTitle       = "Position Overview";
$pageCSS         = "position-statistics.css";
$pageJS          = "position-statistics.js";
$pageDescription = "Workforce, staffing, hiring and onboarding for a single position.";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url("?page=login"));
    exit;
}

if (empty($position) || !is_array($position)) {
    header("Location: " . url("?page=human-capital"));
    exit;
}

$positionStats    = $positionStats ?? [];
$employees        = $employees ?? [];
$recruitment      = $recruitment ?? [];
$onboarding       = $onboarding ?? [];
$recentActivity   = $recentActivity ?? [];
$departmentLookup = $departmentLookup ?? [];
$roles            = $roles ?? [];

$e       = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$pct     = fn($part, $total) => $total > 0 ? (int) round($part / $total * 100) : 0;
$fmtDate = fn($d) => $d ? date('M j, Y', strtotime((string) $d)) : '—';
$plural  = fn($n, $one, $many = null) => $n . ' ' . ($n === 1 ? $one : ($many ?? $one . 's'));

// ---- Header ----
$posId      = (int) $position['position_id'];
$posName    = (string) $position['position_name'];
$posStatus  = (string) ($position['status'] ?? 'Closed');
$deptId     = (int) ($position['department_id'] ?? 0);
$deptName   = (string) ($position['department_name'] ?? '—');
$deptStatus = (string) ($position['department_status'] ?? 'Active');
$roleName   = (string) ($position['role_name'] ?? '');
$roleCode   = (string) ($position['role_code'] ?? '');
$empType    = $position['employment_type'] ?? null;
$createdAt  = $position['created_at'] ?? null;

// ---- Figures ----
$emp          = $positionStats['employees'] ?? [];
$empTotal     = (int) ($emp['total'] ?? 0);
$empActive    = (int) ($emp['active'] ?? 0);
$empInactive  = (int) ($emp['inactive'] ?? 0);
$empNoAcct    = (int) ($emp['no_account'] ?? 0);
$newHires     = (int) ($emp['new_hires_30d'] ?? 0);

$vacancies    = (int) ($positionStats['vacancies'] ?? 0);
$openPostings = (int) ($positionStats['open_postings'] ?? 0);

$applicants        = (int) ($positionStats['applicants'] ?? 0);
$activeApplicants  = (int) ($positionStats['active_applicants'] ?? 0);
$hired             = (int) ($positionStats['hired'] ?? 0);
$upcomingInterview = (int) ($positionStats['upcoming_interviews'] ?? 0);

$obActive  = (int) ($positionStats['onboarding']['active'] ?? 0);
$obOverdue = (int) ($positionStats['onboarding']['overdue'] ?? 0);

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

// ---- Staffing capacity (real data only) ----
$filled   = $empTotal;
$vacant   = $vacancies;
$capacity = $filled + $vacant;
$fillPct  = $capacity > 0 ? (int) round($filled / $capacity * 100) : 0;
$vacantPct = $capacity > 0 ? 100 - $fillPct : 0;

// ---- Flags shown under the header (this position only) ----
$alerts = [];
if ($empTotal === 0) {
    $alerts[] = ['class' => 'is-vacant', 'icon' => 'fa-users-slash', 'text' => 'No employees assigned to this position'];
}
if ($vacancies > 0) {
    $alerts[] = ['class' => 'is-hiring', 'icon' => 'fa-user-plus', 'text' => $plural($vacancies, 'open vacancy', 'open vacancies')];
}
if ((int) ($position['total_postings'] ?? 0) === 0) {
    $alerts[] = ['class' => 'is-headless', 'icon' => 'fa-bullhorn', 'text' => 'No job postings yet'];
}
if ($deptStatus === 'Inactive') {
    $alerts[] = ['class' => 'is-headless', 'icon' => 'fa-building', 'text' => 'Parent department is inactive'];
}
if ($obOverdue > 0) {
    $alerts[] = ['class' => 'is-vacant', 'icon' => 'fa-hourglass-end', 'text' => $plural($obOverdue, 'overdue onboarding', 'overdue onboardings')];
}

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
    <div class="pos-page">

        <!-- ============ Position header ============ -->
        <section class="pos-header">
            <div class="pos-header-main">
                <div class="pos-header-icon"><i class="fa-solid fa-briefcase"></i></div>

                <div class="pos-header-info">
                    <div class="pos-header-title">
                        <h1><?= $e($posName) ?></h1>
                        <span class="status-badge <?= $e(strtolower($posStatus)) ?>">
                            <span class="status-dot"></span>
                            <?= $e($posStatus) ?>
                        </span>
                    </div>

                    <div class="pos-header-meta">
                        <span>
                            <i class="fa-solid fa-building"></i>
                            <a class="pos-link" href="<?= $e(url('?page=department-statistics&id=' . $deptId)) ?>"><?= $e($deptName) ?></a>
                            <small>· <?= $e($deptStatus) ?></small>
                        </span>
                        <span>
                            <i class="fa-solid fa-user-tie"></i>
                            Role:
                            <strong><?= $roleName !== '' ? $e($roleName) : '—' ?></strong>
                            <?php if ($roleCode !== ''): ?>
                                <small>· <?= $e($roleCode) ?></small>
                            <?php endif; ?>
                        </span>
                        <span>
                            <i class="fa-solid fa-clock"></i>
                            Employment:
                            <strong><?= !empty($empType) ? $e($empType) : 'Not specified' ?></strong>
                        </span>
                        <span>
                            <i class="fa-solid fa-calendar-days"></i>
                            Created <?= $e($fmtDate($createdAt)) ?>
                        </span>
                    </div>
                </div>
            </div>

            <div class="pos-header-actions">
                <button type="button" class="pos-btn pos-btn-primary" id="posEditBtn">
                    <i class="fa-solid fa-pen"></i> Edit Position
                </button>
                <a class="pos-btn" href="<?= $e(url('?page=create&position_id=' . $posId)) ?>">
                    <i class="fa-solid fa-bullhorn"></i> Create Job Posting
                </a>
                <a class="pos-btn" href="#pos-employees">
                    <i class="fa-solid fa-users"></i> View Employees
                </a>
                <a class="pos-btn" href="<?= $e(url('?page=human-capital')) ?>">
                    <i class="fa-solid fa-arrow-left"></i> Human Capital
                </a>
            </div>
        </section>

        <?php if (!empty($alerts)): ?>
            <ul class="pos-alerts">
                <?php foreach ($alerts as $alert): ?>
                    <li class="pos-tag <?= $e($alert['class']) ?>">
                        <i class="fa-solid <?= $e($alert['icon']) ?>"></i> <?= $e($alert['text']) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <!-- ============ KPI cards ============ -->
        <div class="pos-stats">
            <div class="pos-stat-card">
                <div class="pos-stat-icon"><i class="fa-solid fa-users"></i></div>
                <div class="pos-stat-body">
                    <span class="pos-stat-value"><?= $empTotal ?></span>
                    <span class="pos-stat-label">Employees</span>
                    <span class="pos-stat-sub"><?= $newHires ?> hired in last 30 days</span>
                </div>
            </div>

            <div class="pos-stat-card <?= $vacancies > 0 ? 'is-warning' : '' ?>">
                <div class="pos-stat-icon"><i class="fa-solid fa-user-plus"></i></div>
                <div class="pos-stat-body">
                    <span class="pos-stat-value"><?= $vacancies ?></span>
                    <span class="pos-stat-label">Open Vacancies</span>
                    <span class="pos-stat-sub"><?= $plural($openPostings, 'open posting') ?></span>
                </div>
            </div>

            <div class="pos-stat-card">
                <div class="pos-stat-icon"><i class="fa-solid fa-bullhorn"></i></div>
                <div class="pos-stat-body">
                    <span class="pos-stat-value"><?= $openPostings ?></span>
                    <span class="pos-stat-label">Active Job Postings</span>
                    <span class="pos-stat-sub"><?= (int) ($position['total_postings'] ?? 0) ?> total postings</span>
                </div>
            </div>

            <div class="pos-stat-card">
                <div class="pos-stat-icon"><i class="fa-solid fa-file-lines"></i></div>
                <div class="pos-stat-body">
                    <span class="pos-stat-value"><?= $applicants ?></span>
                    <span class="pos-stat-label">Applicants</span>
                    <span class="pos-stat-sub"><?= $activeApplicants ?> active · <?= $hired ?> hired</span>
                </div>
            </div>
        </div>

        <!-- ============ Position information | Staffing overview ============ -->
        <div class="pos-panels pos-panels-even">

            <section class="pos-panel" id="pos-information">
                <header class="pos-panel-head">
                    <h3><i class="fa-solid fa-circle-info"></i> Position Information</h3>
                </header>

                <dl class="pos-info">
                    <div>
                        <dt>Position</dt>
                        <dd><?= $e($posName) ?></dd>
                    </div>
                    <div>
                        <dt>Department</dt>
                        <dd>
                            <?= $e($deptName) ?>
                            <span class="pos-tag <?= $deptStatus === 'Active' ? 'is-hiring' : 'is-headless' ?>"><?= $e($deptStatus) ?></span>
                        </dd>
                    </div>
                    <div>
                        <dt>Assigned role</dt>
                        <dd>
                            <?= $roleName !== '' ? $e($roleName) : '—' ?>
                            <?php if ($roleCode !== ''): ?>
                                <small class="pos-cell-sub"><?= $e($roleCode) ?></small>
                            <?php endif; ?>
                        </dd>
                    </div>
                    <div>
                        <dt>Employment type</dt>
                        <dd><?= !empty($empType) ? $e($empType) : 'Not specified' ?></dd>
                    </div>
                    <div>
                        <dt>Status</dt>
                        <dd>
                            <span class="status-badge <?= $e(strtolower($posStatus)) ?>">
                                <span class="status-dot"></span><?= $e($posStatus) ?>
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt>Position ID</dt>
                        <dd>#<?= $posId ?></dd>
                    </div>
                    <div>
                        <dt>Created</dt>
                        <dd><?= $e($fmtDate($createdAt)) ?></dd>
                    </div>
                    <div>
                        <dt>Last posting</dt>
                        <dd><?= $e($fmtDate($position['last_posting_at'] ?? null)) ?></dd>
                    </div>
                </dl>
            </section>

            <section class="pos-panel" id="pos-staffing">
                <header class="pos-panel-head">
                    <h3><i class="fa-solid fa-chart-simple"></i> Staffing Overview</h3>
                    <span class="pos-panel-meta"><?= $plural($capacity, 'headcount slot') ?></span>
                </header>

                <?php if ($capacity === 0): ?>
                    <p class="pos-empty">No employees and no open vacancies recorded for this position.</p>
                <?php else: ?>
                    <div class="pos-split" role="img"
                         aria-label="<?= $filled ?> filled, <?= $vacant ?> vacant of <?= $capacity ?> total">
                        <span class="seg seg-filled" style="width: <?= $pct($filled, $capacity) ?>%"></span>
                        <span class="seg seg-vacant" style="width: <?= $pct($vacant, $capacity) ?>%"></span>
                    </div>

                    <ul class="pos-legend pos-legend-wide">
                        <li><span class="dot dot-filled"></span> Filled <b><?= $filled ?></b></li>
                        <li><span class="dot dot-vacant"></span> Vacant <b><?= $vacant ?></b></li>
                    </ul>

                    <div class="pos-staffing-figures">
                        <div>
                            <b><?= $fillPct ?>%</b>
                            <span>Fill rate</span>
                        </div>
                        <div>
                            <b><?= $vacantPct ?>%</b>
                            <span>Vacancy rate</span>
                        </div>
                        <div>
                            <b><?= $capacity ?></b>
                            <span>Total capacity</span>
                        </div>
                    </div>

                    <p class="pos-note">
                        Filled counts employees currently holding this position; vacant counts
                        vacancies from its open job postings.
                    </p>
                <?php endif; ?>
            </section>
        </div>

        <!-- ============ Current employees ============ -->
        <section class="pos-panel pos-block" id="pos-employees">
            <header class="pos-panel-head">
                <h3><i class="fa-solid fa-users"></i> Current Employees</h3>
                <?php if (!empty($employees)): ?>
                    <label class="pos-search">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="search" id="posEmployeeSearch" placeholder="Search name or number"
                               aria-label="Search employees in this position" autocomplete="off">
                    </label>
                <?php endif; ?>
            </header>

            <?php if (empty($employees)): ?>
                <p class="pos-empty">No employees currently hold this position.</p>
            <?php else: ?>
                <div class="pos-table-wrap pos-scroll">
                    <table class="pos-mini-table" id="posEmployeeTable">
                        <thead>
                            <tr>
                                <th scope="col">Employee</th>
                                <th scope="col">Employment</th>
                                <th scope="col">Hired</th>
                                <th scope="col">Account</th>
                                <th scope="col">Record</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $emp_row): ?>
                                <?php
                                    $search = strtolower(
                                        $emp_row['employee_name'] . ' ' .
                                        ($emp_row['employee_number'] ?? '')
                                    );
                                    $acct = $emp_row['account_status'] ?? null;
                                ?>
                                <tr data-search="<?= $e($search) ?>">
                                    <td>
                                        <strong class="pos-cell-main"><?= $e($emp_row['employee_name'] !== '' ? $emp_row['employee_name'] : 'Unnamed') ?></strong>
                                        <small class="pos-cell-sub"><?= $e($emp_row['employee_number'] ?? '—') ?></small>
                                    </td>
                                    <td>
                                        <span class="pos-tag">
                                            <?= $e($emp_row['employment_status'] ?? '—') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= $e($fmtDate($emp_row['hire_date'])) ?>
                                        <small class="pos-cell-sub"><?= $e($tenure($emp_row['hire_date'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($acct): ?>
                                            <span class="status-badge <?= $e(strtolower($acct)) ?>">
                                                <span class="status-dot"></span><?= $e($acct) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="pos-muted">No account</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a class="pos-btn pos-btn-sm" href="<?= $e(url('?page=view&id=' . (int) $emp_row['employee_id'])) ?>">
                                            <i class="fa-solid fa-eye"></i> View
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p class="pos-empty" id="posEmployeeEmpty" hidden>No employees match your search.</p>
                </div>
            <?php endif; ?>
        </section>

        <!-- ============ Recruitment | Onboarding ============ -->
        <div class="pos-panels pos-panels-even">

            <section class="pos-panel" id="pos-recruitment">
                <header class="pos-panel-head">
                    <h3><i class="fa-solid fa-bullhorn"></i> Recruitment</h3>
                    <span class="pos-panel-meta"><?= $plural((int) ($recSummary['total_postings'] ?? 0), 'posting') ?></span>
                </header>

                <div class="pos-mini-stats">
                    <div><b><?= (int) ($recSummary['vacancies'] ?? 0) ?></b><span>Vacancies</span></div>
                    <div><b><?= (int) ($recSummary['active_applicants'] ?? 0) ?></b><span>Active applicants</span></div>
                    <div><b><?= (int) ($recSummary['upcoming_interviews'] ?? 0) ?></b><span>Upcoming interviews</span></div>
                    <div><b><?= (int) ($recSummary['hired'] ?? 0) ?></b><span>Hired</span></div>
                </div>

                <h4 class="pos-subhead">Job postings</h4>
                <?php if (empty($recPostings)): ?>
                    <p class="pos-empty">No job postings have been created for this position.</p>
                <?php else: ?>
                    <ul class="pos-postings">
                        <?php foreach ($recPostings as $posting): ?>
                            <?php
                                $isOpen       = ($posting['status'] ?? '') === 'Open';
                                $pastDeadline = strtotime((string) $posting['application_deadline']) < strtotime('today');
                            ?>
                            <li>
                                <div class="pos-posting-main">
                                    <strong><?= $e($posting['title']) ?></strong>
                                    <small>
                                        <?= $e($posting['employment_type']) ?>
                                        · Deadline <?= $e($fmtDate($posting['application_deadline'])) ?>
                                        <?php if ($isOpen && $pastDeadline): ?>
                                            <span class="pos-tag is-hiring">Past deadline</span>
                                        <?php elseif (!$isOpen): ?>
                                            <span class="pos-tag is-headless">Closed</span>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div class="pos-posting-stats">
                                    <span><b><?= (int) $posting['vacancies'] ?></b> vacancies</span>
                                    <span><b><?= (int) $posting['applicants'] ?></b>/<?= (int) $posting['max_applicants'] ?> applicants</span>
                                    <span><b><?= (int) $posting['upcoming_interviews'] ?></b> interviews</span>
                                    <a class="pos-posting-link" href="<?= $e(url('?page=edit&id=' . (int) $posting['posting_id'])) ?>">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>

                <h4 class="pos-subhead">Interviews awaiting a result</h4>
                <?php if (empty($recInterviews)): ?>
                    <p class="pos-empty pos-empty-tight">No interviews are waiting for a result.</p>
                <?php else: ?>
                    <ul class="pos-interviews">
                        <?php foreach ($recInterviews as $iv): ?>
                            <?php $isPast = strtotime((string) $iv['interview_date']) < time(); ?>
                            <li>
                                <div>
                                    <strong><?= $e($iv['applicant_name']) ?></strong>
                                    <small><?= $e($iv['posting_title']) ?> · <?= $e($iv['interview_type']) ?></small>
                                </div>
                                <div class="pos-interview-when">
                                    <span><?= $e(date('M j, g:i A', strtotime((string) $iv['interview_date']))) ?></span>
                                    <?php if ($isPast): ?>
                                        <span class="pos-tag is-hiring">Awaiting result</span>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>

            <section class="pos-panel" id="pos-onboarding">
                <header class="pos-panel-head">
                    <h3><i class="fa-solid fa-clipboard-list"></i> Current Onboarding</h3>
                    <span class="pos-panel-meta"><?= count($onboarding) ?> in progress</span>
                </header>

                <?php if (empty($onboarding)): ?>
                    <p class="pos-empty">Nobody in this position is currently onboarding.</p>
                <?php else: ?>
                    <ul class="pos-onboarding">
                        <?php foreach ($onboarding as $ob): ?>
                            <li>
                                <div class="pos-onboarding-top">
                                    <div>
                                        <strong><?= $e($ob['employee_name'] !== '' ? $ob['employee_name'] : 'Unnamed') ?></strong>
                                        <small><?= !empty($ob['started_at']) ? 'Started ' . $e($fmtDate($ob['started_at'])) : 'Not started' ?></small>
                                    </div>
                                    <span class="pos-tag <?= $e($onboardClass[$ob['onboarding_status']] ?? 'is-headless') ?>">
                                        <?= $e($ob['onboarding_status']) ?>
                                    </span>
                                </div>

                                <div class="pos-progress">
                                    <div class="pos-bar-track" role="progressbar" aria-valuemin="0" aria-valuemax="100"
                                         aria-valuenow="<?= (int) $ob['progress'] ?>">
                                        <div class="pos-bar-fill" style="width: <?= (int) $ob['progress'] ?>%"></div>
                                    </div>
                                    <span class="pos-progress-value"><?= (int) $ob['progress'] ?>%</span>
                                </div>

                                <small class="pos-onboarding-foot">
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
        </div>

        <!-- ============ Recent activity ============ -->
        <section class="pos-panel pos-block" id="pos-activity">
            <header class="pos-panel-head">
                <h3><i class="fa-solid fa-clock-rotate-left"></i> Recent Activity</h3>
                <span class="pos-panel-meta"><?= $plural(count($recentActivity), 'event') ?></span>
            </header>

            <?php if (empty($recentActivity)): ?>
                <p class="pos-empty">No activity recorded for this position yet.</p>
            <?php else: ?>
                <ul class="pos-timeline">
                    <?php foreach ($recentActivity as $event): ?>
                        <li>
                            <span class="pos-timeline-icon">
                                <i class="fa-solid <?= $e($eventIcon[$event['event_type']] ?? 'fa-circle') ?>"></i>
                            </span>
                            <div>
                                <span class="pos-timeline-text"><?= $e($event['description']) ?></span>
                                <small><?= $e($fmtDate($event['event_at'])) ?></small>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <!-- ============ Edit position modal ============ -->
        <div class="pos-modal" id="posEditModal" hidden>
            <div class="pos-modal-backdrop" data-close></div>

            <form class="pos-modal-card" id="posEditForm" method="post"
                  data-save-url="<?= $e(url('?page=save-position')) ?>"
                  role="dialog" aria-modal="true" aria-labelledby="posEditTitle">

                <header class="pos-modal-head">
                    <h3 id="posEditTitle">Edit Position</h3>
                    <button type="button" class="pos-modal-x" data-close aria-label="Close">&times;</button>
                </header>

                <input type="hidden" name="position_id" value="<?= $posId ?>">

                <label class="pos-field">
                    <span>Position name</span>
                    <input type="text" name="position_name" maxlength="150" required
                           value="<?= $e($posName) ?>">
                </label>

                <label class="pos-field">
                    <span>Department</span>
                    <select name="department_id" required>
                        <?php foreach ($departmentLookup as $dept): ?>
                            <option value="<?= (int) $dept['department_id'] ?>"
                                <?= (int) $dept['department_id'] === $deptId ? 'selected' : '' ?>>
                                <?= $e($dept['department_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="pos-field">
                    <span>System role</span>
                    <select name="role_id" required>
                        <?php foreach ($roles as $role): ?>
                            <option value="<?= (int) $role['role_id'] ?>"
                                <?= (int) $role['role_id'] === (int) ($position['role_id'] ?? 0) ? 'selected' : '' ?>>
                                <?= $e($role['role_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <p class="pos-modal-error" id="posEditError" hidden></p>

                <footer class="pos-modal-foot">
                    <button type="button" class="pos-btn" data-close>Cancel</button>
                    <button type="submit" class="pos-btn pos-btn-primary" id="posEditSave">Save changes</button>
                </footer>
            </form>
        </div>

    </div>
    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>
</div>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>
