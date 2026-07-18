<?php

$pageTitle = "Dashboard";

$pageDescription = "RAM-YUM Recruitment Management System Dashboard";

$pageStyles = [
    "dashboard.css"
];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$user = [
    'username' => $_SESSION['username'] ?? 'Administrator',
    'role'     => $_SESSION['role'] ?? 'Admin',
];

$JobRoles = [
    'Admin',
    'HR Staff',
    'Manager'
];

$canViewDashboard = in_array($user['role'], $JobRoles);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="layout">

    <?php require_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="main-content">

        <?php require_once __DIR__ . '/../../includes/navbar.php'; ?>

        <!-- Welcome -->
        <section class="welcome-card">

            <div>

                <h1>
                    Welcome,
                    <?= htmlspecialchars($user['username']) ?>!
                </h1>

                <p>
                    <?= htmlspecialchars($user['role']) ?> Dashboard
                </p>

            </div>

        </section>

        <?php if ($canViewDashboard): ?>

            <!-- Statistics -->
            <section class="stats-grid">

                <div class="stat-card">
                    <h3>Total Applicants</h3>
                    <span>245</span>
                </div>

                <div class="stat-card">
                    <h3>Open Job Positions</h3>
                    <span>12</span>
                </div>

                <div class="stat-card">
                    <h3>AI Pending Review</h3>
                    <span>18</span>
                </div>

                <div class="stat-card">
                    <h3>Interviews Today</h3>
                    <span>7</span>
                </div>

                <div class="stat-card">
                    <h3>Job Offers</h3>
                    <span>4</span>
                </div>

                <div class="stat-card">
                    <h3>Employees Hired</h3>
                    <span>35</span>
                </div>

            </section>

            <!-- Top Grid -->
            <section class="dashboard-grid">

                <!-- Recent Applications -->
                <div class="dashboard-card">

                    <div class="card-header">

                        <h2>Recent Applications</h2>

                        <a href="#">View All</a>

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
                            <span>Average Match Score</span>
                            <strong>86%</strong>
                        </div>

                        <div class="summary-item">
                            <span>Highly Recommended</span>
                            <strong>10</strong>
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

                <!-- Upcoming Interviews -->
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

                <!-- Recent Activity -->
                <div class="dashboard-card">

                    <div class="card-header">
                        <h2>Recent Activity</h2>
                    </div>

                    <ul class="activity-list">

                        <li>✓ Maria Santos submitted a new application.</li>

                        <li>✓ AI screening completed for Juan Dela Cruz.</li>

                        <li>✓ Interview scheduled for John Cruz.</li>

                        <li>✓ Job Offer sent to Maria Santos.</li>

                        <li>✓ New Job Position created by HR.</li>

                    </ul>

                </div>

            </section>

        <?php else: ?>

            <section class="dashboard-card">

                <h2>Dashboard</h2>

                <p>
                    Welcome to the RAM-YUM Recruitment Management System.
                </p>

                <p>
                    Your assigned modules will appear here based on your account permissions.
                </p>

            </section>

        <?php endif; ?>

    </main>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>