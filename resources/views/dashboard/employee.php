<?php

/*
|--------------------------------------------------------------------------
| EMPLOYEE DASHBOARD
|--------------------------------------------------------------------------
|
| Task-first layout for the EMP role. Reading order mirrors what an employee
| actually comes here for:
|
|   1. Where do my requests stand?                     -> status tiles
|   2. What has HR asked me to do?                    -> assigned requests
|   3. What happened to the ones I already sent?      -> recent requests
|   4. What is my record / how does it break down?    -> side summary
|   5. Let me file a new one.                         -> request launcher
|
| The controller supplies $employee, the status counts, $recentRequests and
| the assigned-request counts. Everything below is presentation only.
|
*/

$pageTitle       = 'Dashboard';
$pageCSS         = 'dashboard.css';
$pageJS          = 'dashboard.js';
$pageDescription = 'Your requests, employment details, and quick actions.';

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . url('?page=login'));
    exit;
}

$employee          = $employee ?? [];
$recentRequests    = $recentRequests ?? [];
$totalRequests     = (int) ($totalRequests ?? 0);
$pendingRequests   = (int) ($pendingRequests ?? 0);
$approvedRequests  = (int) ($approvedRequests ?? 0);
$rejectedRequests  = (int) ($rejectedRequests ?? 0);
$completedRequests = (int) ($completedRequests ?? 0);

$recentAssignedRequests = $recentAssignedRequests ?? [];
$totalAssigned          = (int) ($totalAssigned ?? 0);
$assignedPending        = (int) ($assignedPending ?? 0);
$assignedInProgress     = (int) ($assignedInProgress ?? 0);
$assignedCompleted      = (int) ($assignedCompleted ?? 0);
$assignedOverdue        = (int) ($assignedOverdue ?? 0);
$assignedTypes          = $assignedTypes ?? [];

/*
|--------------------------------------------------------------------------
| View helpers
|--------------------------------------------------------------------------
|
| Closures rather than named functions: this view can be re-included, and a
| second `function` declaration would fatal on redeclare.
|
*/

$e = static fn($value): string => htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');

/** Formats a date, or an em dash when there is nothing to show. */
$fmt = static function ($value, string $format = 'M j, Y'): string {
    if ($value === null || trim((string) $value) === '') {
        return '—';
    }

    $timestamp = strtotime((string) $value);

    return $timestamp ? date($format, $timestamp) : '—';
};

/** Collapses empty / null values down to an em dash. */
$orDash = static function ($value): string {
    $value = trim((string) ($value ?? ''));

    return $value === '' ? '—' : $value;
};

/*
|--------------------------------------------------------------------------
| Identity
|--------------------------------------------------------------------------
*/

$fullName = trim(preg_replace('/\s+/', ' ', (string) ($employee['fullname'] ?? '')) ?? '');

$nameParts = $fullName === '' ? [] : (preg_split('/\s+/', $fullName) ?: []);
$firstName = $nameParts[0] ?? '';

if (count($nameParts) >= 2) {
    $initials = mb_substr($nameParts[0], 0, 1) . mb_substr($nameParts[count($nameParts) - 1], 0, 1);
} elseif (count($nameParts) === 1) {
    $initials = mb_substr($nameParts[0], 0, 2);
} else {
    $initials = mb_substr((string) ($employee['employee_number'] ?? 'E'), 0, 2);
}

$initials = mb_strtoupper(trim($initials));
$initials = $initials === '' ? '?' : $initials;

$hour     = (int) date('G');
$greeting = $hour < 12
    ? 'Good morning'
    : ($hour < 18 ? 'Good afternoon' : 'Good evening');

$employmentStatus = trim((string) ($employee['employment_status'] ?? ''));
$isActive         = $employmentStatus === '' || strcasecmp($employmentStatus, 'Active') === 0;

/*
|--------------------------------------------------------------------------
| Request status metadata
|--------------------------------------------------------------------------
|
| One source of truth for the label, tone class and glyph of every status.
| The same palette feeds the doughnut chart through window.employeeRequestStats
| so the legend dots and the chart slices can never drift apart.
|
*/

$statusPalette = [
    'pending'   => '#d97706',
    'approved'  => '#2e8b57',
    'completed' => '#2563eb',
    'rejected'  => '#c2413b',
];

$statusMeta = [
    'Pending'   => ['label' => 'Pending',   'tone' => 'pending',   'icon' => 'fa-hourglass-half'],
    'Approved'  => ['label' => 'Approved',  'tone' => 'approved',  'icon' => 'fa-circle-check'],
    'Completed' => ['label' => 'Completed', 'tone' => 'completed', 'icon' => 'fa-flag-checkered'],
    'Rejected'  => ['label' => 'Rejected',  'tone' => 'rejected',  'icon' => 'fa-circle-xmark'],
];

/** Percentage of all requests, used by the overview legend. */
$share = static function (int $count) use ($totalRequests): int {
    return $totalRequests > 0 ? (int) round($count / $totalRequests * 100) : 0;
};

/*
|--------------------------------------------------------------------------
| Assigned request metadata
|--------------------------------------------------------------------------
|
| Assigned requests use their own vocabulary (Pending Action / In Progress /
| Completed). They reuse the dashboard's existing status tones so the card
| needs no new colours: blue reads as "in flight", green as "done".
|
*/

$assignedStatusMeta = [
    'Pending Action' => ['label' => 'Pending Action', 'tone' => 'pending',  'icon' => 'fa-hourglass-half'],
    'In Progress'    => ['label' => 'In Progress',    'tone' => 'progress', 'icon' => 'fa-rotate'],
    'Completed'      => ['label' => 'Completed',      'tone' => 'done',     'icon' => 'fa-circle-check'],
];

$hasMoreAssigned = $totalAssigned > count($recentAssignedRequests);

/*
|--------------------------------------------------------------------------
| Status tiles
|--------------------------------------------------------------------------
|
| Five tiles instead of four: every status the chart can show is represented,
| so the summary and the breakdown tell the same story.
|
*/

$tiles = [
    [
        'tone'    => 'brand',
        'icon'    => 'fa-layer-group',
        'label'   => 'Total requests',
        'value'   => $totalRequests,
        'caption' => 'All time',
    ],
    [
        'tone'    => 'pending',
        'icon'    => 'fa-hourglass-half',
        'label'   => 'Pending',
        'value'   => $pendingRequests,
        'caption' => 'Waiting on HR',
        'urgent'  => $pendingRequests > 0,
    ],
    [
        'tone'    => 'approved',
        'icon'    => 'fa-circle-check',
        'label'   => 'Approved',
        'value'   => $approvedRequests,
        'caption' => 'Cleared by HR',
    ],
    [
        'tone'    => 'completed',
        'icon'    => 'fa-flag-checkered',
        'label'   => 'Completed',
        'value'   => $completedRequests,
        'caption' => 'Closed out',
    ],
    [
        'tone'    => 'rejected',
        'icon'    => 'fa-circle-xmark',
        'label'   => 'Rejected',
        'value'   => $rejectedRequests,
        'caption' => 'Not approved',
    ],
];

/*
|--------------------------------------------------------------------------
| Request type launcher
|--------------------------------------------------------------------------
|
| Mirrors the canonical list in EmployeeRequestsController::createMyRequest()
| and resources/views/employee-requests/my-requests.php. Each tile deep-links
| into the My Requests page with that type preselected.
|
*/

$requestTypes = [
    'Certificate of Employment' => ['icon' => 'fa-certificate',        'hint' => 'An official employment certificate issued by HR.'],
    'Document Request'          => ['icon' => 'fa-file-lines',         'hint' => 'Copies of employment records or company documents.'],
    'Profile Update'            => ['icon' => 'fa-id-card',            'hint' => 'Changes to details you cannot edit yourself.'],
    'Payroll Concern'           => ['icon' => 'fa-money-check-dollar', 'hint' => 'A wrong amount, a missing payment, or similar.'],
    'Employment Concern'        => ['icon' => 'fa-briefcase',          'hint' => 'A question about your work status or record.'],
    'Other'                     => ['icon' => 'fa-comment-dots',       'hint' => 'Anything else that needs HR attention.'],
];

/*
|--------------------------------------------------------------------------
| Employment record
|--------------------------------------------------------------------------
*/

$infoRows = [
    'Employee number'   => $employee['employee_number'] ?? '',
    'Department'        => $employee['department_name'] ?? '',
    'Position'          => $employee['job_title'] ?? '',
    'Employment status' => $employmentStatus,
    'Hire date'         => $fmt($employee['hire_date'] ?? null),
    'Email'             => $employee['email'] ?? '',
];

$hasMoreRequests = $totalRequests > count($recentRequests);

?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>

    <main class="emp-dash">

        <!-- =====================================================
             STATUS TILES
        ====================================================== -->
        <section class="emp-tiles" aria-label="Request statistics">
            <?php foreach ($tiles as $tile): ?>
                <a class="emp-tile is-<?= $e($tile['tone']) ?><?= !empty($tile['urgent']) ? ' is-urgent' : '' ?>"
                   href="?page=my-requests">
                    <span class="emp-tile-icon" aria-hidden="true">
                        <i class="fa-solid <?= $e($tile['icon']) ?>"></i>
                    </span>

                    <span class="emp-tile-body">
                        <span class="emp-tile-label"><?= $e($tile['label']) ?></span>
                        <strong class="emp-tile-value" data-count="<?= (int) $tile['value'] ?>"><?= (int) $tile['value'] ?></strong>
                        <span class="emp-tile-caption"><?= $e($tile['caption']) ?></span>
                    </span>

                    <i class="emp-tile-go fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            <?php endforeach; ?>
        </section>

        <!-- =====================================================
             CONTENT GRID
        ====================================================== -->
        <div class="emp-grid">

            <div class="emp-main">

                <!-- =============================================
                     ASSIGNED REQUESTS
                ============================================== -->
                <section class="emp-card" aria-labelledby="assignedTitle">
                    <header class="emp-card-head">
                        <div>
                            <h2 id="assignedTitle">Assigned to you</h2>
                            <p>Requests HR or Admin assigned to you.</p>
                        </div>
                        <a class="emp-link" href="?page=assigned-requests">
                            View all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </header>

                    <?php if ($totalAssigned > 0): ?>

                        <ul class="emp-assigned-stats">
                            <li class="is-pending">
                                <strong><?= $assignedPending ?></strong>
                                <span>Need action</span>
                            </li>
                            <li class="is-progress">
                                <strong><?= $assignedInProgress ?></strong>
                                <span>In progress</span>
                            </li>
                            <li class="is-done">
                                <strong><?= $assignedCompleted ?></strong>
                                <span>Completed</span>
                            </li>
                            <li class="is-overdue">
                                <strong><?= $assignedOverdue ?></strong>
                                <span>Overdue</span>
                            </li>
                        </ul>

                        <ul class="emp-rows">
                            <?php foreach ($recentAssignedRequests as $assigned): ?>
                                <?php
                                $assignedStatus = (string) ($assigned['status'] ?? 'Pending Action');
                                $assignedMeta   = $assignedStatusMeta[$assignedStatus] ?? $assignedStatusMeta['Pending Action'];
                                $assignedType   = (string) ($assigned['request_type'] ?? 'Submit Required Document');
                                $assignedIcon   = $assignedTypes[$assignedType] ?? 'fa-circle-question';
                                $assignedLabel  = trim((string) ($assigned['title'] ?? '')) !== ''
                                    ? (string) $assigned['title']
                                    : $assignedType;
                                $assignedDue    = $assigned['due_date'] ?? null;
                                $assignedLate   = !empty($assigned['is_overdue']);
                                ?>
                                <li class="emp-row is-link">
                                    <span class="emp-row-icon" aria-hidden="true">
                                        <i class="fa-solid <?= $e($assignedIcon) ?>"></i>
                                    </span>

                                    <div class="emp-row-body">
                                        <p class="emp-row-title"><?= $e($assignedLabel) ?></p>
                                        <p class="emp-row-meta">
                                            <span><i class="fa-solid fa-tag" aria-hidden="true"></i><?= $e($assignedType) ?></span>
                                            <span>
                                                <i class="fa-regular fa-clock" aria-hidden="true"></i>
                                                <?= $assignedDue
                                                    ? 'Due ' . $e($fmt($assignedDue))
                                                    : 'No due date' ?>
                                            </span>
                                            <?php if ($assignedLate): ?>
                                                <span class="is-overdue">
                                                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>Overdue
                                                </span>
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <span class="emp-pill is-<?= $e($assignedMeta['tone']) ?>">
                                        <i class="fa-solid <?= $e($assignedMeta['icon']) ?>" aria-hidden="true"></i>
                                        <?= $e($assignedMeta['label']) ?>
                                    </span>

                                    <a class="emp-row-hit"
                                       href="?page=assigned-requests"
                                       aria-label="Open assigned requests"></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php if ($hasMoreAssigned): ?>
                            <footer class="emp-card-foot">
                                <a class="emp-link" href="?page=assigned-requests">
                                    See all <?= $totalAssigned ?> assigned requests
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </footer>
                        <?php endif; ?>

                    <?php else: ?>

                        <div class="emp-empty is-compact">
                            <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i>
                            <p>Nothing has been assigned to you. When HR asks you for something, it will appear here.</p>
                        </div>

                    <?php endif; ?>
                </section>

                <!-- Recent requests -->
                <section class="emp-card" aria-labelledby="recentTitle">
                    <header class="emp-card-head">
                        <div>
                            <h2 id="recentTitle">Recent requests</h2>
                            <p>Your latest submissions and where they stand.</p>
                        </div>
                        <a class="emp-link" href="?page=my-requests">
                            View all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </header>

                    <?php if (!empty($recentRequests)): ?>

                        <ul class="emp-rows">
                            <?php foreach ($recentRequests as $request): ?>
                                <?php
                                $status   = (string) ($request['status'] ?? 'Pending');
                                $meta     = $statusMeta[$status] ?? $statusMeta['Pending'];
                                $type     = (string) ($request['request_type'] ?? 'Other');
                                $typeMeta = $requestTypes[$type] ?? ['icon' => 'fa-circle-question'];
                                $subject  = trim((string) ($request['subject'] ?? '')) !== ''
                                    ? (string) $request['subject']
                                    : $type;
                                ?>
                                <li class="emp-row">
                                    <span class="emp-row-icon" aria-hidden="true">
                                        <i class="fa-solid <?= $e($typeMeta['icon']) ?>"></i>
                                    </span>

                                    <div class="emp-row-body">
                                        <p class="emp-row-title"><?= $e($subject) ?></p>
                                        <p class="emp-row-meta">
                                            <span><i class="fa-solid fa-tag" aria-hidden="true"></i><?= $e($type) ?></span>
                                            <span>
                                                <i class="fa-regular fa-calendar" aria-hidden="true"></i>
                                                <?= $e($fmt($request['requested_at'] ?? null)) ?>
                                            </span>
                                        </p>
                                    </div>

                                    <span class="emp-pill is-<?= $e($meta['tone']) ?>">
                                        <i class="fa-solid <?= $e($meta['icon']) ?>" aria-hidden="true"></i>
                                        <?= $e($meta['label']) ?>
                                    </span>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <?php if ($hasMoreRequests): ?>
                            <footer class="emp-card-foot">
                                <a class="emp-link" href="?page=my-requests">
                                    See all <?= $totalRequests ?> requests
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                </a>
                            </footer>
                        <?php endif; ?>

                    <?php else: ?>

                        <div class="emp-empty">
                            <i class="fa-solid fa-inbox" aria-hidden="true"></i>
                            <h3>No requests yet</h3>
                            <p>Once you file a request it will appear here with its current status.</p>
                            <a class="btn-primary" href="?page=my-requests&amp;compose=1">
                                <i class="fa-solid fa-plus" aria-hidden="true"></i> Submit a request
                            </a>
                        </div>

                    <?php endif; ?>
                </section>

                <!-- =============================================
                     REQUEST LAUNCHER
                ============================================== -->
                <section class="emp-card" aria-labelledby="launcherTitle">
                    <header class="emp-card-head">
                        <div>
                            <h2 id="launcherTitle">Start a request</h2>
                            <p>Pick a type and the form opens with it already selected.</p>
                        </div>
                    </header>

                    <div class="emp-launcher-grid">
                        <?php foreach ($requestTypes as $type => $meta): ?>
                            <a class="emp-launch" href="?page=my-requests&amp;compose=<?= urlencode($type) ?>">
                                <span class="emp-launch-icon" aria-hidden="true">
                                    <i class="fa-solid <?= $e($meta['icon']) ?>"></i>
                                </span>
                                <span class="emp-launch-body">
                                    <strong><?= $e($type) ?></strong>
                                    <small><?= $e($meta['hint']) ?></small>
                                </span>
                                <i class="emp-launch-go fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </section>

            </div>

            <!-- Side column -->
            <aside class="emp-side">

                <!-- Request overview -->
                <section class="emp-card" aria-labelledby="overviewTitle">
                    <header class="emp-card-head">
                        <div>
                            <h2 id="overviewTitle">Request overview</h2>
                            <p>How your requests break down.</p>
                        </div>
                    </header>

                    <?php if ($totalRequests > 0): ?>

                        <div class="emp-chart">
                            <canvas id="employeeRequestChart" aria-label="Requests by status" role="img"></canvas>
                            <div class="emp-chart-center" aria-hidden="true">
                                <strong><?= $totalRequests ?></strong>
                                <span>Total</span>
                            </div>
                        </div>

                        <ul class="emp-legend">
                            <?php
                            $legendRows = [
                                ['key' => 'pending',   'label' => 'Pending',   'value' => $pendingRequests],
                                ['key' => 'approved',  'label' => 'Approved',  'value' => $approvedRequests],
                                ['key' => 'completed', 'label' => 'Completed', 'value' => $completedRequests],
                                ['key' => 'rejected',  'label' => 'Rejected',  'value' => $rejectedRequests],
                            ];
                            ?>
                            <?php foreach ($legendRows as $row): ?>
                                <li>
                                    <span class="emp-dot" style="background: <?= $e($statusPalette[$row['key']]) ?>"></span>
                                    <span class="emp-legend-label"><?= $e($row['label']) ?></span>
                                    <strong><?= (int) $row['value'] ?></strong>
                                    <em><?= $share((int) $row['value']) ?>%</em>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                    <?php else: ?>

                        <div class="emp-empty is-compact">
                            <i class="fa-solid fa-chart-pie" aria-hidden="true"></i>
                            <p>Nothing to chart yet — submit a request to see the breakdown.</p>
                        </div>

                    <?php endif; ?>
                </section>

                <!-- Employment record -->
                <section class="emp-card" aria-labelledby="infoTitle">
                    <header class="emp-card-head">
                        <div>
                            <h2 id="infoTitle">My information</h2>
                            <p>Your current employment record.</p>
                        </div>
                        <a class="emp-link" href="?page=profile">
                            Profile <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    </header>

                    <dl class="emp-info">
                        <?php foreach ($infoRows as $label => $value): ?>
                            <div>
                                <dt><?= $e($label) ?></dt>
                                <dd><?= $e($orDash($value)) ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>

                    <p class="emp-note">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        Employment details are maintained by HR. To change one, file a
                        <a class="emp-note-link" href="?page=my-requests&amp;compose=<?= urlencode('Profile Update') ?>">Profile Update</a> request.
                    </p>
                </section>

            </aside>
        </div>

        <script>
            window.employeeRequestStats = <?= json_encode([
                'pending'   => $pendingRequests,
                'approved'  => $approvedRequests,
                'completed' => $completedRequests,
                'rejected'  => $rejectedRequests,
                'palette'   => $statusPalette,
            ], JSON_UNESCAPED_SLASHES) ?>;
        </script>

    </main>

    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>

</div>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>
