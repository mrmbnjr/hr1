<?php

require_once "../../../server/bootstrap.php";
require_once "../../../server/middleware/auth.php";
require_once "../../../server/middleware/role.php";

requireRole([
    'admin',
    'hr',
    'manager',
    'cashier',
    'warehouse',
    'accountant'
]);

$pageTitle = "Dashboard";

$pageStyles = [
    "layout.css",
    "dashboard.css"
];

include "../../includes/header.php";

$role = $_SESSION['role'];
?>

<div class="layout">

    <?php include "../../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include "../../includes/navbar.php"; ?>

        <!-- Welcome -->
        <section class="welcome-card">

            <div>
                <h1>Welcome, <?= htmlspecialchars($_SESSION['username']) ?>!</h1>
                <p>
                    <?= ucfirst(htmlspecialchars($role)) ?> Dashboard
                </p>
            </div>

        </section>

        <?php if (in_array($role, ['admin', 'hr', 'manager'])) : ?>

            <!-- Statistics -->
            <section class="stats-grid">

                <div class="stat-card">
                    <h3>Total Applicants</h3>
                    <span>245</span>
                </div>

                <div class="stat-card">
                    <h3>Job Posts</h3>
                    <span>12</span>
                </div>

                <div class="stat-card">
                    <h3>Pending Review</h3>
                    <span>18</span>
                </div>

                <div class="stat-card">
                    <h3>Scheduled Interviews</h3>
                    <span>7</span>
                </div>

            </section>

            <!-- Two Columns -->
            <section class="dashboard-grid">

                <!-- Recent Applications -->
                <div class="dashboard-card">

                    <div class="card-header">
                        <h2>Recent Applications</h2>
                        <a href="../applications/index.php">View All</a>
                    </div>

                    <table class="dashboard-table">

                        <thead>

                            <tr>
                                <th>Applicant</th>
                                <th>Position</th>
                                <th>AI Score</th>
                                <th>Status</th>
                            </tr>

                        </thead>

                        <tbody>

                            <tr>
                                <td>Juan Dela Cruz</td>
                                <td>Cashier</td>
                                <td>92%</td>
                                <td><span class="status pending">Pending</span></td>
                            </tr>

                            <tr>
                                <td>Maria Santos</td>
                                <td>Warehouse Staff</td>
                                <td>88%</td>
                                <td><span class="status interview">Interview</span></td>
                            </tr>

                            <tr>
                                <td>John Cruz</td>
                                <td>Accountant</td>
                                <td>95%</td>
                                <td><span class="status hired">Hired</span></td>
                            </tr>

                        </tbody>

                    </table>

                </div>

                <!-- AI Summary -->
                <div class="dashboard-card">

                    <div class="card-header">
                        <h2>AI Screening Summary</h2>
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
                            <span>Needs Review</span>
                            <strong>6</strong>
                        </div>

                    </div>

                </div>

            </section>

            <!-- Bottom Grid -->
            <section class="dashboard-grid">

                <!-- Interviews -->
                <div class="dashboard-card">

                    <div class="card-header">
                        <h2>Upcoming Interviews</h2>
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

                        </tbody>

                    </table>

                </div>

                <!-- Activity -->
                <div class="dashboard-card">

                    <div class="card-header">
                        <h2>Recent Activity</h2>
                    </div>

                    <ul class="activity-list">

                        <li>✓ Maria Santos submitted an application.</li>

                        <li>✓ AI screened Juan Dela Cruz.</li>

                        <li>✓ HR scheduled an interview.</li>

                        <li>✓ Manager approved a hiring request.</li>

                    </ul>

                </div>

            </section>

        <?php else : ?>

            <!-- Basic Dashboard -->
            <section class="dashboard-card">

                <h2>Dashboard</h2>

                <p>
                    Welcome to the Recruitment Management System.
                </p>

                <p>
                    Your assigned modules will appear here once they become available.
                </p>

            </section>

        <?php endif; ?>

    </main>

</div>

<?php include "../../includes/footer.php"; ?>