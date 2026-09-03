<?php

$pageTitle       = "Employee Dashboard";
$pageCSS         = "dashboard.css";
$pageDescription = "Your employee dashboard.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

$employee           = $employee ?? [];
$requests           = $requests ?? [];
$totalRequests      = $totalRequests ?? 0;
$pendingRequests     = $pendingRequests ?? 0;
$approvedRequests    = $approvedRequests ?? 0;
$rejectedRequests    = $rejectedRequests ?? 0;
$completedRequests   = $completedRequests ?? 0;
$recentRequests      = $recentRequests ?? [];

/** Renders a "small label" + "strong value" pair for the info grid below. */
function infoField(string $label, string $value): void
{
    echo '<div>';
    echo '<small>' . htmlspecialchars($label) . '</small>';
    echo '<strong>' . htmlspecialchars($value) . '</strong>';
    echo '</div>';
}

function requestStatusClass(string $status): string
{
    return match ($status) {
        'Approved', 'Completed' => 'badge-green',
        'Rejected'              => 'badge-red',
        default                 => 'badge-gray',
    };
}

$infoFields = [
    'Employee Number'   => $employee['employee_number'] ?? '—',
    'Department'        => $employee['department_name'] ?? '—',
    'Position'          => $employee['job_title'] ?? '—',
    'Employment Status' => $employee['employment_status'] ?? '—',
    'Hire Date'         => !empty($employee['hire_date'])
        ? date('M d, Y', strtotime($employee['hire_date']))
        : '—',
    'Email'             => $employee['email'] ?? '—',
];

$statCards = [
    ['icon' => 'fa-file-lines',    'label' => 'My Requests', 'value' => $totalRequests,     'desc' => 'Total submitted requests'],
    ['icon' => 'fa-clock',         'label' => 'Pending',     'value' => $pendingRequests,   'desc' => 'Requests awaiting review'],
    ['icon' => 'fa-circle-check',  'label' => 'Approved',    'value' => $approvedRequests,  'desc' => 'Approved requests'],
    ['icon' => 'fa-check-double',  'label' => 'Completed',   'value' => $completedRequests, 'desc' => 'Completed requests'],
];

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<style>
    .page-intro { margin-bottom: 24px; padding: 28px; }
    .page-intro .eyebrow { margin: 0 0 6px; color: #888; font-size: 14px; }
    .page-intro h1 { margin: 0 0 8px; font-size: 28px; }
    .page-intro .subtext { margin: 0; color: #777; }

    .info-card { margin-bottom: 24px; padding: 24px; }
    .info-card .card-header { margin-bottom: 20px; }
    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 20px;
    }
    .info-grid small { display: block; color: #888; margin-bottom: 6px; }

    .recent-requests { margin-bottom: 24px; }
    .recent-requests .activity-list { padding: 0 24px 20px; }
    .activity-meta { display: flex; align-items: center; gap: 12px; }

    .empty-state { padding: 40px 0; text-align: center; color: #888; }
    .empty-state i { font-size: 30px; margin-bottom: 12px; }

    .quick-action { padding: 24px; }
    .quick-action-row {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
    }
    .quick-action-row h2 { margin: 0 0 6px; }
    .quick-action-row p { margin: 0; color: #888; }
</style>

<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>

    <!-- Welcome -->
    <section class="dashboard-card page-intro">
        <p class="eyebrow">Employee Dashboard</p>
        <h1>Welcome, <?= htmlspecialchars($employee['fullname'] ?? 'Employee') ?>!</h1>
        <p class="subtext">Here's a quick overview of your employee information and requests.</p>
    </section>

    <!-- Employee Information -->
    <section class="dashboard-card info-card">
        <div class="card-header">
            <div>
                <h2>My Information</h2>
                <p>Your current employee information.</p>
            </div>
        </div>

        <div class="info-grid">
            <?php foreach ($infoFields as $label => $value): ?>
                <?php infoField($label, $value); ?>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Request Statistics -->
    <section class="stats-grid" style="margin-bottom:24px;">
        <?php foreach ($statCards as $card): ?>
            <a href="/hr1/public/?page=my-requests" class="stat-card-link">
                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid <?= $card['icon'] ?>"></i>
                    </div>
                    <div>
                        <small><?= htmlspecialchars($card['label']) ?></small>
                        <h2><?= (int) $card['value'] ?></h2>
                        <span><?= htmlspecialchars($card['desc']) ?></span>
                    </div>
                </article>
            </a>
        <?php endforeach; ?>
    </section>

    <!-- Recent Requests -->
    <section class="dashboard-card recent-requests">
        <div class="card-header">
            <div>
                <h2>Recent Requests</h2>
                <p>Your latest employee requests.</p>
            </div>
            <a href="/hr1/public/?page=my-requests" class="view-all-btn">
                View All <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>

        <div class="activity-list">
            <?php if (!empty($recentRequests)): ?>
                <?php foreach ($recentRequests as $request): ?>
                    <?php
                    $status      = $request['status'] ?? 'Pending';
                    $statusClass = requestStatusClass($status);
                    ?>
                    <div class="activity-item">
                        <div>
                            <h3><?= htmlspecialchars($request['request_type'] ?? 'Request') ?></h3>
                            <p><?= htmlspecialchars($request['subject'] ?? '—') ?></p>
                        </div>
                        <div class="activity-meta">
                            <span class="badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
                            <?php if (!empty($request['requested_at'])): ?>
                                <span><?= htmlspecialchars(date('M d', strtotime($request['requested_at']))) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fa-solid fa-inbox"></i>
                    <p>You have not submitted any requests yet.</p>
                    <a href="/hr1/public/?page=my-requests" class="btn-primary">
                        <i class="fa-solid fa-plus"></i> Submit a Request
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Quick Action -->
    <section class="dashboard-card quick-action">
        <div class="quick-action-row">
            <div>
                <h2>Need something from HR?</h2>
                <p>Submit a new employee request and track its progress.</p>
            </div>
            <a href="/hr1/public/?page=my-requests" class="btn-primary">
                <i class="fa-solid fa-file-circle-plus"></i> My Requests
            </a>
        </div>
    </section>

    <?php require '../resources/views/includes/footer.php'; ?>

</div>

<?php require '../resources/views/includes/scripts.php'; ?>