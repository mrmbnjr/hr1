<?php
$pageTitle = "Dashboard";
$pageCSS = "dashboard.css";
$pageDescription = "Welcome to RAM-YUM Store — Korean and Japanese Store.";

    if (!isset($_SESSION['user_id'])) {
        header("Location: /hr1/public/?page=login");
        exit;
    }

    // Initialize stats array with default values
    if (!isset($stats) || !is_array($stats)) {
        $stats = [
            'applicants' => 0,
            'postings' => 0,
            'employees' => 0,
            'requests' => 0
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
        <a href="/hr1/public/?page=applicants" class="stat-card-link">
            <article class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-users"></i>
                </div>
                <div>
                    <small>Total Applicants</small>
                    <h2><?= $stats['applicants']; ?></h2>
                    <span class="increase">
                        <i class="fa-solid fa-arrow-trend-up"></i>+18 this week
                    </span>
                </div>
            </article>
        </a>

        <a href="/hr1/public/?page=recruitment" class="stat-card-link">
            <article class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-briefcase"></i>
                </div>

                <div>
                    <small>Open Positions</small>
                    <h2><?= $stats['postings']; ?></h2>
                    <span>Across all branches</span>
                </div>
            </article>
        </a>

        <a href="/hr1/public/?page=employee-records" class="stat-card-link">
            <article class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-user-tie"></i>
                </div>

                <div>
                    <small>Employees</small>
                    <h2><?= $stats['employees']; ?></h2>
                    <span class="warning">Active employees only</span>
                </div>
            </article>
        </a>

        <a href="/hr1/public/?page=employee-self-service" class="stat-card-link">
            <article class="stat-card">
                <div class="stat-icon">
                    <i class="fa-solid fa-file-circle-check"></i>
                </div>
                <div>
                    <small>Requests</small>
                    <h2><?= $stats['requests']; ?></h2>
                    <span>Recent employee requests</span>
                </div>
            </article>
        </a>
    </section>

    <!-- ======================================================
        GRID
    ======================================================= -->

    <section class="dashboard-grid">
        <!-- Applicant Submissions -->
        <article class="dashboard-card">
            <div class="card-header">
                <div>
                    <h2>Application Submissions</h2>
                    <p id="growthSubtitle">
                        Applications submitted throughout the year
                    </p>
                </div>

                <div class="chart-controls">

                    <div class="chart-navigation">
                        <button id="prevPeriod" class="nav-btn" type="button">
                            <i class="fa-solid fa-chevron-left"></i>
                        </button>

                        <span id="currentPeriod">
                            <?= date('Y') ?>
                        </span>

                        <button id="nextPeriod" class="nav-btn" type="button">
                            <i class="fa-solid fa-chevron-right"></i>
                        </button>
                    </div>

                    <select id="growthFilter">
                        <option value="year"
                            <?= ($view ?? 'year') === 'year' ? 'selected' : '' ?>>
                            Year
                        </option>

                        <option value="month"
                            <?= ($view ?? '') === 'month' ? 'selected' : '' ?>>
                            Month
                        </option>

                        <option value="week"
                            <?= ($view ?? '') === 'week' ? 'selected' : '' ?>>
                            Week
                        </option>
                    </select>
                </div>
            </div>

            <canvas id="applicantGrowthChart" height="120"></canvas>

            <script>
                window.applicantGrowthData = {
                    labels: <?= json_encode($growthLabels ?? []) ?>,
                    data: <?= json_encode($growthData ?? []) ?>,

                    view: "<?= $view ?? 'year' ?>",
                    year: <?= $year ?? date('Y') ?>,
                    month: <?= $month ?? date('n') ?>,
                    weekStart: "<?= $weekStart ?? date('Y-m-d') ?>",

                    period: "<?= $chartPeriod ?? date('Y') ?>",
                    subtitle: "<?= $chartSubtitle ?? 'Applications submitted throughout the year' ?>"
                };
            </script>
        </article>

        <!-- Calendar -->
        <article class="dashboard-card">
            <div class="card-header">
                <button class="calendar-nav" id="prevMonth">
                    <i class="fa-solid fa-chevron-left"></i>
                </button>

                <div class="calendar-title">
                    <h2 id="calendarTitle"></h2>
                </div>

                <button class="calendar-nav" id="nextMonth">
                    <i class="fa-solid fa-chevron-right"></i>
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
                <tbody id="calendarBody"></tbody>
            </table>
        </article>
    </section>

    <!-- ======================================================
        SECOND GRID
    ======================================================= -->

    <section class="dashboard-grid">

        <!-- New Employees -->
        <article class="dashboard-card">

            <div class="card-header">
                <div>
                    <h2>New Employees</h2>
                    <p>Recently onboarded employees</p>
                </div>

                <a href="/hr1/public/?page=employee-records">
                    View All
                </a>
            </div>


            <div class="employee-list">

            <?php if (!empty($newEmployees)): ?>

                <?php foreach ($newEmployees as $employee): ?>

                    <div class="employee-item">

                        <div class="employee-info">

                            <h3>
                                <?= htmlspecialchars($employee['employee_name']) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars($employee['title']) ?>
                            </p>

                            <div class="employee-meta">

                                <span class="employment <?= strtolower($employee['employment_status']) ?>">
                                    <?= htmlspecialchars($employee['employment_status']) ?>
                                </span>

                                <span class="status <?= strtolower($employee['onboarding_status']) ?>">
                                    <?= htmlspecialchars($employee['onboarding_status']) ?>
                                </span>

                            </div>

                        </div>

                        <span class="employee-date">
                            <?= date('M d', strtotime($employee['hire_date'])) ?>
                        </span>

                    </div>

                <?php endforeach; ?>

            <?php else: ?>

                <p>No new employees found.</p>

            <?php endif; ?>

            </div>
        </article>

        <!-- Quick Actions -->
        <article class="dashboard-card">

            <div class="card-header">
                <div>
                    <h2>Quick Actions</h2>
                    <p>Frequently used functions</p>
                </div>
            </div>


            <div class="shortcut-grid">

                <a href="/hr1/public/?page=recruitment">
                    <i class="fa-solid fa-plus"></i>
                    <span>Create Job Post</span>
                </a>


                <a href="/hr1/public/?page=applicants">
                    <i class="fa-solid fa-user-check"></i>
                    <span>Review Applicants</span>
                </a>


                <a href="/hr1/public/?page=onboarding">
                    <i class="fa-solid fa-file-signature"></i>
                    <span>Onboarding</span>
                </a>


                <a href="/hr1/public/?page=employee-records">
                    <i class="fa-solid fa-users"></i>
                    <span>Employees</span>
                </a>
            </div>
        </article>
    </section>

    <!-- ======================================================
        TOTAL ACTIVITIES
    ======================================================= -->

    <section class="dashboard-card">

        <div class="card-header">
            <div>
                <h2>Total Activities</h2>
                <p>Recent HR system activities</p>
            </div>

            <a href="#">
                View History
            </a>
        </div>


        <div class="activity-list">

        <?php if (!empty($recentActivities)): ?>

            <?php foreach ($recentActivities as $activity): ?>

                <div class="activity-item">
                    <div>

                        <h3>
                            <?= htmlspecialchars($activity['activity_title']) ?>
                        </h3>

                        <p>
                            <?= htmlspecialchars($activity['activity_description']) ?>
                        </p>

                    </div>

                    <span>
                        <?= date('M d, Y', strtotime($activity['activity_date'])) ?>
                    </span>

                </div>

            <?php endforeach; ?>

        <?php else: ?>

            <p>No recent activities found.</p>

        <?php endif; ?>

        </div>
    </section>
    <?php require '../resources/views/includes/footer.php'; ?>
</div>

<?php require '../resources/views/includes/scripts.php'?>