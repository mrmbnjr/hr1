<?php
$pageTitle = "Dashboard";
$pageCSS = "dashboard.css";
$pageDescription = "Welcome to RAM-YUM Store — Korean and Japanese Store.";

    if (!isset($_SESSION['user_id'])) {
        header("Location: /hr1/public/?page=login");
        exit;
    }

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>
<div class="main-content">
    <?php require '../resources/views/includes/navbar.php'; ?>

    <!-- ======================================================
        HERO
    ======================================================= -->

    <section class="dashboard-hero">
        <div class="hero-content">
            <span class="hero-tag">
                🍜 RAM-YUM Administration
            </span>
            <h1>Welcome back, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
            <p>Here's a quick overview of today's recruitment and employee activities.</p>
        </div>

        <div class="hero-badge">
            <div class="hero-circle">👋</div>
        </div>
    </section>

    <!-- ======================================================
        QUICK STATS
    ======================================================= -->

    <section class="stats-grid">
        <article class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-users"></i>
            </div>

            <div>
                <small>Total Applicants</small>
                <h2>245</h2>

                <span class="increase">
                    <i class="fa-solid fa-arrow-trend-up"></i>+18 this week
                </span>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-briefcase"></i>
            </div>

            <div>
                <small>Open Positions</small>
                <h2>12</h2>
                <span>Across all branches</span>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-user-clock"></i>
            </div>

            <div>
                <small>Pending Review</small>
                <h2>18</h2>
                <span class="warning">Needs HR review</span>
            </div>
        </article>

        <article class="stat-card">
            <div class="stat-icon">
                <i class="fa-solid fa-calendar-days"></i>
            </div>

            <div>
                <small>Interviews Today</small>
                <h2>7</h2>
                <span>Scheduled</span>
            </div>
        </article>
    </section>

    <!-- ======================================================
        GRID
    ======================================================= -->

    <section class="dashboard-grid">

        <!-- Recent Applicants -->
        <article class="dashboard-card">

            <div class="card-header">
                <div>
                    <h2>Recent Applications</h2>
                    <p>Latest submitted applicants</p>
                </div>
                <a href="../applications/index.php">View All</a>
            </div>

            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Position</th>
                        <th>AI Match</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>Juan Dela Cruz</td>
                        <td>Cashier</td>
                        <td>92%</td>
                        <td>
                            <span class="status pending">Pending</span>
                        </td>
                    </tr>

                    <tr>
                        <td>Maria Santos</td>
                        <td>Warehouse Staff</td>
                        <td>88%</td>
                        <td>
                            <span class="status interview">Interview</span>
                        </td>
                    </tr>

                    <tr>
                        <td>John Cruz</td>
                        <td>Accountant</td>
                        <td>95%</td>
                        <td>
                            <span class="status hired">Hired</span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </article>

        <!-- AI -->

        <article class="dashboard-card">
            <div class="card-header">
                <div>
                    <h2>AI Screening</h2>
                    <p>Today's recruitment analytics</p>
                </div>
            </div>

            <div class="summary-list">
                <div class="summary-item">
                    <span>Processed Today</span>
                    <strong>18</strong>
                </div>

                <div class="summary-item">
                    <span>Average Match</span>
                    <strong>86%</strong>
                </div>

                <div class="summary-item">
                    <span>Shortlisted</span>
                    <strong>12</strong>
                </div>

                <div class="summary-item">
                    <span>Needs Review</span>
                    <strong>6</strong>
                </div>
            </div>
        </article>
    </section>

    <!-- ======================================================
        SECOND GRID
    ======================================================= -->

    <section class="dashboard-grid">
        <article class="dashboard-card">
            <div class="card-header">
                <div>
                    <h2>Upcoming Interviews</h2>

                    <p>Next scheduled interviews</p>
                </div>
            </div>

            <table class="dashboard-table">

                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Position</th>
                        <th>Date</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>John Cruz</td>

                        <td>Cashier</td>

                        <td>July 20</td>
                    </tr>

                    <tr>
                        <td>Maria Santos</td>
                        <td>Warehouse Staff</td>
                        <td>July 21</td>
                    </tr>

                    <tr>
                        <td>Kevin Reyes</td>
                        <td>Cook</td>
                        <td>July 22</td>
                    </tr>
                </tbody>
            </table>
        </article>

        <article class="dashboard-card">
            <div class="card-header">
                <div>
                    <h2>Recent Activity</h2>

                    <p>Latest HR actions</p>
                </div>
            </div>

            <ul class="activity-list">
                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    Maria Santos submitted an application.
                </li>
                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    AI screened Juan Dela Cruz.
                </li>
                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    HR scheduled an interview.
                </li>
                <li>
                    <i class="fa-solid fa-circle-check"></i>
                    Manager approved a hiring request.
                </li>
            </ul>
        </article>
    </section>

    <section class="dashboard-card">
        <div class="card-header">
            <h2>Dashboard</h2>
        </div>
        <p>
            Welcome to the RAM-YUM HR Management System.
        </p>
        <p>
            Your assigned modules will appear here as they become available.
        </p>
    </section>
</div>
<?php require '../resources/views/includes/footer.php'; ?>