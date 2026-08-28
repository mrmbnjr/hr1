<?php

use App\Services\Auth;


/*
|--------------------------------------------------------------------------
| Page Configuration
|--------------------------------------------------------------------------
*/

$pageTitle =
    "Dashboard";

$pageCSS =
    "dashboard.css";

$pageJS =
    "dashboard.js";

$pageDescription =
    "Welcome to RAM-YUM Store — Korean and Japanese Store.";


/*
|--------------------------------------------------------------------------
| Authentication
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {

    header(
        "Location: /hr1/public/?page=login"
    );

    exit;
}


/*
|--------------------------------------------------------------------------
| Prevent Employee From Using Admin Dashboard
|--------------------------------------------------------------------------
*/

if (
    method_exists(
        Auth::class,
        'role'
    )
) {

    if (Auth::role() === 'EMP') {

        header(
            "Location: /hr1/public/?page=dashboard"
        );

        exit;
    }
}


/*
|--------------------------------------------------------------------------
| Initialize Stats
|--------------------------------------------------------------------------
*/

if (
    !isset($stats) ||
    !is_array($stats)
) {

    $stats = [

        'applicants' => 0,

        'postings' => 0,

        'employees' => 0,

        'requests' => 0

    ];
}


if (
    !isset($recentApplicants) ||
    !is_array($recentApplicants)
) {
    $recentApplicants = [
        'items' => [],
        'page' => 1,
        'total' => 0,
        'totalPages' => 1
    ];
}

?>


<?php require '../resources/views/includes/header.php'; ?>

<?php require '../resources/views/includes/sidebar.php'; ?>


<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>


    <!-- ======================================================
         QUICK STATS
    ======================================================= -->

    <section class="stats-grid">


        <!-- Applicants -->

        <a
            href="/hr1/public/?page=applicants"
            class="stat-card-link"
        >

            <article class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-users"
                    ></i>

                </div>


                <div>

                    <small>
                        Total Applicants
                    </small>

                    <h2>
                        <?= $stats['applicants']; ?>
                    </h2>

                    <span class="increase">

                        <i
                            class="fa-solid fa-arrow-trend-up"
                        ></i>

                        +18 this week

                    </span>

                </div>

            </article>

        </a>


        <!-- Open Positions -->

        <a
            href="/hr1/public/?page=recruitment"
            class="stat-card-link"
        >

            <article class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-briefcase"
                    ></i>

                </div>


                <div>

                    <small>
                        Open Positions
                    </small>

                    <h2>
                        <?= $stats['postings']; ?>
                    </h2>

                    <span>
                        Across all branches
                    </span>

                </div>

            </article>

        </a>


        <!-- Employees -->

        <a
            href="/hr1/public/?page=employee-records"
            class="stat-card-link"
        >

            <article class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-user-tie"
                    ></i>

                </div>


                <div>

                    <small>
                        Employees
                    </small>

                    <h2>
                        <?= $stats['employees']; ?>
                    </h2>

                    <span class="warning">
                        Active employees only
                    </span>

                </div>

            </article>

        </a>


        <!-- Requests -->

        <a
            href="/hr1/public/?page=employee-requests"
            class="stat-card-link"
        >

            <article class="stat-card">

                <div class="stat-icon">

                    <i
                        class="fa-solid fa-file-circle-check"
                    ></i>

                </div>


                <div>

                    <small>
                        Requests
                    </small>

                    <h2>
                        <?= $stats['requests']; ?>
                    </h2>

                    <span>
                        Recent employee requests
                    </span>

                </div>

            </article>

        </a>

    </section>


    <!-- ======================================================
         MAIN GRID
    ======================================================= -->

    <section class="dashboard-grid">


        <!-- ==================================================
             APPLICATION SUBMISSIONS
        =================================================== -->

        <article
            id="applicationSubmissionsCard"
            class="dashboard-card"
        >

            <div class="card-header">

                <div>

                    <h2>
                        Application Submissions
                    </h2>

                    <p id="growthSubtitle">
                        Applications submitted
                        throughout the year
                    </p>

                </div>


                <div class="chart-controls">


                    <div class="chart-navigation">

                        <button
                            id="prevPeriod"
                            class="nav-btn"
                            type="button"
                        >

                            <i
                                class="fa-solid fa-chevron-left"
                            ></i>

                        </button>


                        <span id="currentPeriod">

                            <?= date('Y') ?>

                        </span>


                        <button
                            id="nextPeriod"
                            class="nav-btn"
                            type="button"
                        >

                            <i
                                class="fa-solid fa-chevron-right"
                            ></i>

                        </button>

                    </div>


                    <select id="growthFilter">

                        <option
                            value="year"
                            <?= ($view ?? 'year') === 'year'
                                ? 'selected'
                                : '' ?>
                        >
                            Year
                        </option>


                        <option
                            value="month"
                            <?= ($view ?? '') === 'month'
                                ? 'selected'
                                : '' ?>
                        >
                            Month
                        </option>


                        <option
                            value="week"
                            <?= ($view ?? '') === 'week'
                                ? 'selected'
                                : '' ?>
                        >
                            Week
                        </option>

                    </select>

                </div>

            </div>


            <canvas
                id="applicantGrowthChart"
                height="120"
            ></canvas>


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
                                <td colspan="5" class="submissions-empty">
                                    No application submissions yet.
                                </td>
                            </tr>

                        <?php else: ?>

                            <?php foreach ($recentApplicants['items'] as $recentApplicant):
                                $status = $recentApplicant['application_status']
                                    ?? 'Submitted';
                                $statusClass = strtolower(
                                    str_replace(' ', '-', $status)
                                );
                            ?>

                                <tr>

                                    <td>
                                        <a
                                            class="submission-applicant"
                                            href="?page=review&id=<?= (int) $recentApplicant['applicant_id'] ?>"
                                        >
                                            <span class="submission-avatar">
                                                <?= strtoupper(substr($recentApplicant['fullname'] ?? '?', 0, 1)) ?>
                                            </span>
                                            <strong><?= htmlspecialchars($recentApplicant['fullname'] ?? '') ?></strong>
                                        </a>
                                    </td>

                                    <td><?= htmlspecialchars($recentApplicant['position'] ?? '') ?></td>

                                    <td><?= htmlspecialchars($recentApplicant['address'] ?: 'Not provided') ?></td>

                                    <td class="submission-date">
                                        <?= date('M j, Y', strtotime($recentApplicant['applied_at'])) ?>
                                    </td>

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
                        Page <?= (int) $recentApplicants['page'] ?>
                        of <?= (int) $recentApplicants['totalPages'] ?>
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

    window.applicantGrowthData = <?= json_encode([
        'labels'    => $growthLabels ?? [],
        'data'      => $growthData ?? [],
        'view'      => $view ?? 'year',
        'year'      => $year ?? date('Y'),
        'month'     => $month ?? date('n'),
        'weekStart' => $weekStart ?? date('Y-m-d'),
        'period'    => $chartPeriod ?? date('Y'),
        'subtitle'  => $chartSubtitle
            ?? 'Applications submitted throughout the year',
        'applicants' => $recentApplicants['items'] ?? [],
        'page'      => $recentApplicants['page'] ?? 1,
        'total'     => $recentApplicants['total'] ?? 0,
        'totalPages' => $recentApplicants['totalPages'] ?? 1
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;

</script>
        </article>


        <!-- ==================================================
             CALENDAR
        =================================================== -->

        <article class="dashboard-card">

            <div
                class="
                    card-header
                    calendar-header
                "
            >

                <button
                    class="calendar-nav"
                    id="prevMonth"
                    type="button"
                >

                    <i
                        class="fa-solid fa-chevron-left"
                    ></i>

                </button>


                <div class="calendar-title">

                    <h2 id="calendarTitle"></h2>

                </div>


                <button
                    class="calendar-nav"
                    id="nextMonth"
                    type="button"
                >

                    <i
                        class="fa-solid fa-chevron-right"
                    ></i>

                </button>

            </div>


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


                <tbody
                    id="calendarBody"
                ></tbody>

            </table>

        </article>

    </section>


    <!-- ======================================================
         SECOND GRID
    ======================================================= -->

    <section class="dashboard-grid">


        <!-- Applicants Per Job -->

        <article
            id="topAppliedJobsCard"
            class="dashboard-card"
        >

            <div class="card-header">

                <div>

                    <h2>
                        Top 5 Most Applied Jobs
                    </h2>

                    <p>
                        Open positions with the
                        highest number of applicants
                    </p>

                </div>


                <a
                    href="/hr1/public/?page=recruitment"
                    class="view-all-btn"
                >

                    View All

                    <i
                        class="fa-solid fa-arrow-right"
                    ></i>

                </a>

            </div>


            <div
                class="job-chart-wrapper"
            >

                <canvas
                    id="jobApplicantsChart"
                ></canvas>

            </div>


            <script>

                window.jobApplicantsChart = {

                    labels:
                        <?= json_encode(
                            $jobLabels ?? []
                        ) ?>,

                    data:
                        <?= json_encode(
                            $jobData ?? []
                        ) ?>

                };

            </script>

        </article>


        <!-- New Employees -->

        <article class="dashboard-card">

            <div class="card-header">

                <div>

                    <h2>
                        New Employees
                    </h2>

                    <p>
                        Recently onboarded employees
                    </p>

                </div>


                <a
                    href="/hr1/public/?page=employee-records"
                >
                    View All
                </a>

            </div>


            <div class="employee-list">


                <?php if (
                    !empty($newEmployees)
                ): ?>


                    <?php foreach (
                        $newEmployees
                        as $employee
                    ): ?>

                        <div
                            class="employee-item"
                        >

                            <div
                                class="employee-info"
                            >

                                <h3>

                                    <?= htmlspecialchars(
                                        $employee[
                                            'employee_name'
                                        ]
                                    ) ?>

                                </h3>


                                <p>

                                    <?= htmlspecialchars(
                                        $employee[
                                            'title'
                                        ]
                                    ) ?>

                                </p>


                                <div
                                    class="
                                        employee-meta
                                    "
                                >

                                    <span
                                        class="
                                            employment
                                            <?= strtolower(
                                                $employee[
                                                    'employment_status'
                                                ]
                                            ) ?>
                                        "
                                    >

                                        <?= htmlspecialchars(
                                            $employee[
                                                'employment_status'
                                            ]
                                        ) ?>

                                    </span>


                                    <span
                                        class="
                                            status
                                            <?= strtolower(
                                                $employee[
                                                    'onboarding_status'
                                                ]
                                            ) ?>
                                        "
                                    >

                                        <?= htmlspecialchars(
                                            $employee[
                                                'onboarding_status'
                                            ]
                                        ) ?>

                                    </span>

                                </div>

                            </div>


                            <span
                                class="employee-date"
                            >

                                <?= date(
                                    'M d',
                                    strtotime(
                                        $employee[
                                            'hire_date'
                                        ]
                                    )
                                ) ?>

                            </span>

                        </div>

                    <?php endforeach; ?>


                <?php else: ?>


                    <p>
                        No new employees found.
                    </p>


                <?php endif; ?>

            </div>

        </article>

    </section>


    <!-- ======================================================
         TOTAL ACTIVITIES
    ======================================================= -->

    <section
        class="
            dashboard-card
            activity-card
        "
    >

        <div class="card-header">

            <div>

                <h2>
                    Total Activities
                </h2>

                <p>
                    Recent HR system activities
                </p>

            </div>

        </div>


        <div class="activity-list">


            <?php if (
                !empty($recentActivities)
            ): ?>


                <?php foreach (
                    $recentActivities
                    as $activity
                ): ?>

                    <div
                        class="activity-item"
                    >

                        <div>

                            <h3>

                                <?= htmlspecialchars(
                                    $activity[
                                        'activity_title'
                                    ]
                                ) ?>

                            </h3>


                            <p>

                                <?= htmlspecialchars(
                                    $activity[
                                        'activity_description'
                                    ]
                                ) ?>

                            </p>

                        </div>


                        <span>

                            <?= date(
                                'M d, Y',
                                strtotime(
                                    $activity[
                                        'activity_date'
                                    ]
                                )
                            ) ?>

                        </span>

                    </div>

                <?php endforeach; ?>


            <?php else: ?>


                <p>
                    No recent activities found.
                </p>


            <?php endif; ?>

        </div>

    </section>


    <?php require '../resources/views/includes/footer.php'; ?>

</div>


<?php require '../resources/views/includes/scripts.php'; ?>