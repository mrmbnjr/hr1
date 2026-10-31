<?php

use App\Services\Auth;

/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/
$pageTitle       = "Dashboard";
$pageCSS         = "dashboard.css";
$pageJS          = "dashboard.js";
$pageDescription = "Welcome to RAM-YUM Store — Korean and Japanese Store.";

/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/
if (!isset($_SESSION['user_id'])) {
    header("Location: " . url("?page=login"));
    exit;
}

/*
|--------------------------------------------------------------------------
| Prevent Employee From Using Admin Dashboard
|--------------------------------------------------------------------------
*/
if (method_exists(Auth::class, 'role') && Auth::role() === 'EMP') {
    header("Location: " . url("?page=dashboard"));
    exit;
}

/*
|--------------------------------------------------------------------------
| Initialize Stats
|--------------------------------------------------------------------------
*/
if (!isset($stats) || !is_array($stats)) {
    $stats = [
        'applicants' => 0,
        'postings'   => 0,
        'employees'  => 0,
        'requests'   => 0,
    ];
}

if (!isset($recentApplicants) || !is_array($recentApplicants)) {
    $recentApplicants = [
        'items'      => [],
        'page'       => 1,
        'total'      => 0,
        'totalPages' => 1,
    ];
}

function activity_icon_class(?string $type): string
{
    return match ($type) {
        'applicant' => 'applicant',
        'employee'  => 'employee',
        'request'   => 'request',
        'system'    => 'system',
        default     => 'default',
    };
}

function activity_icon_glyph(?string $type): string
{
    return match ($type) {
        'applicant' => 'fa-solid fa-user-plus',
        'employee'  => 'fa-solid fa-user-tie',
        'request'   => 'fa-solid fa-file-circle-check',
        'system'    => 'fa-solid fa-gear',
        default     => 'fa-solid fa-circle-info',
    };
}

/*
|--------------------------------------------------------------------------
| Quick Stat Cards
|--------------------------------------------------------------------------
*/
$statCards = [
    [
        'href'  => url('?page=applicants'),
        'icon'  => 'fa-users',
        'label' => 'Total Applicants',
        'value' => $stats['applicants'],
        'note'  => '<span class="increase"><i class="fa-solid fa-arrow-trend-up"></i> +18 this week</span>',
    ],
    [
        'href'  => url('?page=recruitment'),
        'icon'  => 'fa-briefcase',
        'label' => 'Open Positions',
        'value' => $stats['postings'],
        'note'  => '<span>Across all branches</span>',
    ],
    [
        'href'  => url('?page=employee-records'),
        'icon'  => 'fa-user-tie',
        'label' => 'Employees',
        'value' => $stats['employees'],
        'note'  => '<span class="warning">Active employees only</span>',
    ],
    [
        'href'  => url('?page=employee-requests'),
        'icon'  => 'fa-file-circle-check',
        'label' => 'Requests',
        'value' => $stats['requests'],
        'note'  => '<span>Recent employee requests</span>',
    ],
];

?>
<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>

    <!-- Quick Stats -->
    <section class="stats-grid">
        <?php foreach ($statCards as $card): ?>
            <a href="<?= htmlspecialchars($card['href']) ?>" class="stat-card-link">
                <article class="stat-card">
                    <div class="stat-icon">
                        <i class="fa-solid <?= htmlspecialchars($card['icon']) ?>"></i>
                    </div>
                    <div>
                        <small><?= htmlspecialchars($card['label']) ?></small>
                        <h2><?= (int) $card['value'] ?></h2>
                        <?= $card['note'] ?>
                    </div>
                </article>
            </a>
        <?php endforeach; ?>
    </section>

    <!-- Main Grid -->
    <section class="dashboard-grid">

        <!-- Application Submissions -->
        <article id="applicationSubmissionsCard" class="dashboard-card">
            <div class="card-header">
                <div>
                    <h2>Application Submissions</h2>
                    <p id="growthSubtitle">Applications submitted throughout the year</p>
                </div>
                <div class="chart-controls">
                    <div class="chart-navigation">
                        <button id="prevPeriod" class="nav-btn" type="button">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <span id="currentPeriod"><?= date('Y') ?></span>
                        <button id="nextPeriod" class="nav-btn" type="button">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                    <select id="growthFilter">
                        <option value="year" <?= ($view ?? 'year') === 'year' ? 'selected' : '' ?>>Year</option>
                        <option value="month" <?= ($view ?? '') === 'month' ? 'selected' : '' ?>>Month</option>
                        <option value="week" <?= ($view ?? '') === 'week' ? 'selected' : '' ?>>Week</option>
                    </select>
                </div>
            </div>

            <canvas id="applicantGrowthChart" height="120"></canvas>

            <section class="recent-submissions">
                <div class="submissions-header">
                    <h3>Recent Application Submissions</h3>
                    <a href="?page=applicants">View All</a>
                </div>

                <div class="table-scroll">
                    <table class="submissions-table">
                        <thead>
                            <tr>
                                <th>Applicant</th>
                                <th>Position</th>
                                <th>Location</th>
                                <th>Submitted</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="submissionsTableBody">
                            <?php if (empty($recentApplicants['items'])): ?>
                                <tr>
                                    <td colspan="5" class="submissions-empty">No application submissions yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentApplicants['items'] as $recentApplicant): ?>
                                    <?php
                                    $status      = $recentApplicant['application_status'] ?? 'Submitted';
                                    $statusClass = strtolower(str_replace(' ', '-', $status));
                                    ?>
                                    <tr>
                                        <td>
                                            <a class="submission-applicant" href="?page=review&id=<?= (int) $recentApplicant['applicant_id'] ?>">
                                                <span class="submission-avatar">
                                                    <?= strtoupper(substr($recentApplicant['fullname'] ?? '?', 0, 1)) ?>
                                                </span>
                                                <strong><?= htmlspecialchars($recentApplicant['fullname'] ?? '') ?></strong>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($recentApplicant['position'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($recentApplicant['address'] ?: 'Not provided') ?></td>
                                        <td class="submission-date"><?= date('M j, Y', strtotime($recentApplicant['applied_at'])) ?></td>
                                        <td>
                                            <span class="submission-status <?= htmlspecialchars($statusClass) ?>">
                                                <?= htmlspecialchars($status) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="submissions-pagination" id="submissionsPagination">
                    <span id="submissionsPageInfo">
                        Page <?= (int) $recentApplicants['page'] ?> of <?= (int) $recentApplicants['totalPages'] ?>
                    </span>
                    <div>
                        <button id="submissionsPrev" type="button" aria-label="Previous page">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>
                        <button id="submissionsNext" type="button" aria-label="Next page">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
            </section>

            <script>
                window.scheduledInterviews = <?= json_encode(
                    $scheduledInterviews ?? [],
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ); ?>;
                window.applicantGrowthData = <?= json_encode([
                    'labels'     => $growthLabels ?? [],
                    'data'       => $growthData ?? [],
                    'view'       => $view ?? 'year',
                    'year'       => $year ?? date('Y'),
                    'month'      => $month ?? date('n'),
                    'weekStart'  => $weekStart ?? date('Y-m-d'),
                    'period'     => $chartPeriod ?? date('Y'),
                    'subtitle'   => $chartSubtitle ?? 'Applications submitted throughout the year',
                    'applicants' => $recentApplicants['items'] ?? [],
                    'page'       => $recentApplicants['page'] ?? 1,
                    'total'      => $recentApplicants['total'] ?? 0,
                    'totalPages' => $recentApplicants['totalPages'] ?? 1,
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
            </script>
        </article>

        <!-- Calendar -->
        <article class="dashboard-card">
            <div class="card-header calendar-header">
                <div class="calendar-title">
                    <h2 id="calendarTitle"></h2>
                    <p class="calendar-hint">Select a date to add a note or event</p>
                </div>
                <button class="calendar-view-toggle" id="calendarViewToggle" type="button"
                        aria-expanded="false" aria-controls="calendarSlides">
                    <i class="fa-solid fa-note-sticky"></i>
                    <span>Notes</span>
                </button>
            </div>
            <div class="calendar-viewport">
                <div class="calendar-slides" id="calendarSlides">
                    <section class="calendar-slide calendar-slide--month" aria-label="Monthly calendar">
                        <div class="calendar-month-navigation">
                            <button class="calendar-nav" id="prevMonth" type="button" aria-label="Previous month">
                                <i class="fa-solid fa-chevron-left"></i>
                            </button>
                            <table class="calendar">
                                <thead>
                                    <tr>
                                        <th>Mon</th>
                                        <th>Tue</th>
                                        <th>Wed</th>
                                        <th>Thu</th>
                                        <th>Fri</th>
                                        <th>Sat</th>
                                        <th>Sun</th>
                                    </tr>
                                </thead>
                                <tbody id="calendarBody"></tbody>
                            </table>
                            <button class="calendar-nav" id="nextMonth" type="button" aria-label="Next month">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </div>
                    </section>
                    <section class="calendar-slide calendar-slide--notes" aria-label="Calendar notes and events">
                        <div class="calendar-events" aria-live="polite">
                            <div class="calendar-events__header">
                                <div>
                                    <h3><i class="fa-solid fa-list-check"></i> Notes &amp; events</h3>
                                    <p>Keep track of your upcoming reminders</p>
                                </div>
                                <button type="button" class="calendar-add-event" id="addCalendarEvent">
                                    <i class="fa-solid fa-plus"></i> Add event
                                </button>
                            </div>
                            <div id="calendarEventList" class="calendar-event-list"></div>
                            <div class="calendar-pagination" id="calendarPagination" hidden>
                                <button type="button" id="calendarPrevPage" aria-label="Previous notes page">
                                    <i class="fa-solid fa-chevron-left"></i>
                                </button>
                                <span id="calendarPageInfo"></span>
                                <button type="button" id="calendarNextPage" aria-label="Next notes page">
                                    <i class="fa-solid fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </article>

    </section>

    <div class="calendar-modal" id="calendarModal" hidden>
        <div class="calendar-modal__backdrop" data-calendar-close></div>
        <section class="calendar-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="calendarModalTitle">
            <div class="calendar-modal__header">
                <div>
                    <p class="calendar-modal__eyebrow">Personal calendar</p>
                    <h2 id="calendarModalTitle">Add note or event</h2>
                </div>
                <button type="button" class="calendar-modal__close" data-calendar-close aria-label="Close calendar form">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
            <form id="calendarEventForm">
                <input type="hidden" id="calendarEventId">
                <div class="calendar-form-field">
                    <label for="calendarEventTitle">Title</label>
                    <input id="calendarEventTitle" name="title" type="text" maxlength="80" required placeholder="e.g. Team meeting">
                </div>
                <div class="calendar-form-row">
                    <div class="calendar-form-field">
                        <label for="calendarEventDate">Date</label>
                        <input id="calendarEventDate" name="date" type="date" required>
                    </div>
                    <div class="calendar-form-field">
                        <label for="calendarEventTime">Time <span>(optional)</span></label>
                        <input id="calendarEventTime" name="time" type="time">
                    </div>
                </div>
                <div class="calendar-form-field">
                    <label for="calendarEventNotes">Notes <span>(optional)</span></label>
                    <textarea id="calendarEventNotes" name="notes" rows="3" maxlength="240" placeholder="Add helpful details or a reminder"></textarea>
                </div>
                <div class="calendar-modal__actions">
                    <button type="button" class="calendar-delete-event" id="deleteCalendarEvent" hidden>Delete</button>
                    <button type="button" class="calendar-cancel-event" data-calendar-close>Cancel</button>
                    <button type="submit" class="calendar-save-event">Save event</button>
                </div>
            </form>
        </section>
    </div>

    <!-- Second Grid -->
    <section class="dashboard-grid">

        <!-- Applicants Per Job -->
        <article id="topAppliedJobsCard" class="dashboard-card">
            <div class="card-header">
                <div>
                    <h2>Top 5 Most Applied Jobs</h2>
                    <p>Open positions with the highest number of applicants</p>
                </div>
                <a href="<?= url('?page=recruitment') ?>" class="view-all-btn">
                    View All <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
            <div class="job-chart-wrapper">
                <canvas id="jobApplicantsChart"></canvas>
            </div>
            <script>
                window.jobApplicantsChart = {
                    labels: <?= json_encode($jobLabels ?? []) ?>,
                    data: <?= json_encode($jobData ?? []) ?>
                };
            </script>
        </article>

        <!-- Onboarding Progress -->
        <article class="dashboard-card">
            <div class="card-header">
                <div>
                    <h2>Onboarding Progress</h2>
                    <p>Employee onboarding status</p>
                </div>
                <a href="<?= url('?page=onboarding') ?>">View All</a>
            </div>
            <div class="onboarding-list">
                <?php if (!empty($onboardingProgress)): ?>
                    <?php foreach ($onboardingProgress as $employee): ?>
                        <?php
                        $onboardingStatus = strtolower(str_replace(' ', '-', $employee['onboarding_status'] ?? 'pending'));
                        $progressPercent  = (int) ($employee['progress'] ?? 0);
                        $pendingDocuments = (int) ($employee['pending_documents'] ?? 0);
                        $currentDocument  = $employee['current_document'] ?? null;
                        ?>
                        <div class="onboarding-item">
                            <div class="onboarding-identity">
                                <strong class="onboarding-name"><?= htmlspecialchars($employee['employee_name']) ?></strong>
                                <span class="onboarding-role">
                                    <?= htmlspecialchars($employee['position_name']) ?>
                                    <span aria-hidden="true">&middot;</span>
                                    <?= htmlspecialchars($employee['department_name']) ?>
                                </span>
                            </div>
                            <div class="onboarding-bar-container">
                                <div class="onboarding-bar-background">
                                    <div class="onboarding-bar-fill <?= $onboardingStatus ?>" style="width: <?= $progressPercent ?>%"></div>
                                </div>
                            </div>
                            <div class="onboarding-progress-label">
                                <strong><?= $progressPercent ?>%</strong>
                            </div>
                            <div class="onboarding-info">
                                <span>
                                    <?php if ($onboardingStatus === 'completed'): ?>
                                        Completed
                                    <?php elseif ($currentDocument): ?>
                                        <?= htmlspecialchars($currentDocument) ?>
                                    <?php else: ?>
                                        Documents &amp; Requirements
                                    <?php endif; ?>
                                </span>
                                <span>
                                    <?php if ($onboardingStatus === 'completed' && !empty($employee['completed_at'])): ?>
                                        <?= date('M j, Y', strtotime($employee['completed_at'])) ?>
                                    <?php elseif ($pendingDocuments > 0): ?>
                                        <?= $pendingDocuments ?> document<?= $pendingDocuments === 1 ? '' : 's' ?> left
                                    <?php else: ?>
                                        In progress
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="no-onboarding">No employees in onboarding.</p>
                <?php endif; ?>
            </div>
        </article>

    </section>

    <!-- Total Activities -->
    <section class="dashboard-card activity-card">
        <div class="card-header">
            <div>
                <h2>Total Activities</h2>
                <p>Recent HR system activities</p>
            </div>
        </div>
        <div class="activity-list">
            <?php if (!empty($recentActivities)): ?>
                <?php foreach ($recentActivities as $activity): ?>
                    <?php $activityType = $activity['activity_type'] ?? null; ?>
                    <div class="activity-item">
                        <div class="activity-type <?= htmlspecialchars(activity_icon_class($activityType)) ?>">
                            <i class="<?= htmlspecialchars(activity_icon_glyph($activityType)) ?>" aria-hidden="true"></i>
                        </div>
                        <div>
                            <h3><?= htmlspecialchars($activity['activity_title']) ?></h3>
                            <p><?= htmlspecialchars($activity['activity_description']) ?></p>
                        </div>
                        <span><?= date('M d, Y', strtotime($activity['activity_date'])) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No recent activities found.</p>
            <?php endif; ?>
        </div>
    </section>

    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>
</div>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>