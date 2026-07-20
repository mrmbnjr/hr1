<?php

$pageTitle = "Job Postings";
$pageCSS = "recruitment.css";
$pageDescription = "Create and manage job postings.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>

    <!-- ==========================================
        HERO
    =========================================== -->

    <section class="page-hero">

        <div>
            <span class="hero-tag">📢 Recruitment</span>
            <h1>Job Postings</h1>
            <p>Create, publish, and manage job openings across all departments.</p>
        </div>

        <a href="?page=create" class="btn-primary">
            <i class="fa-solid fa-plus"></i>
            Create Job Posting
        </a>

    </section>

    <!-- ==========================================
        QUICK STATS
    =========================================== -->

    <section class="stats-grid">

        <article class="stat-card">
            <i class="fa-solid fa-briefcase"></i>
            <div>
                <h2>12</h2>
                <span>Open Jobs</span>
            </div>
        </article>

        <article class="stat-card">
            <i class="fa-solid fa-file"></i>
            <div>
                <h2>3</h2>
                <span>Draft Jobs</span>
            </div>
        </article>

        <article class="stat-card">
            <i class="fa-solid fa-lock"></i>
            <div>
                <h2>18</h2>
                <span>Closed Jobs</span>
            </div>
        </article>

        <article class="stat-card">
            <i class="fa-solid fa-building"></i>
            <div>
                <h2>6</h2>
                <span>Departments Hiring</span>
            </div>
        </article>

    </section>

    <!-- ==========================================
        FILTERS
    =========================================== -->

    <section class="filter-card">

        <div class="filter-group">

            <input
                type="text"
                placeholder="Search job title...">

            <select>
                <option>All Departments</option>
                <option>Human Resources</option>
                <option>Finance</option>
                <option>IT</option>
                <option>Warehouse</option>
            </select>

            <select>
                <option>All Status</option>
                <option>Open</option>
                <option>Draft</option>
                <option>Closed</option>
            </select>

            <select>
                <option>All Employment Types</option>
                <option>Full-Time</option>
                <option>Part-Time</option>
                <option>Contract</option>
                <option>Internship</option>
            </select>

        </div>

    </section>

    <!-- ==========================================
        JOB TABLE
    =========================================== -->

    <section class="table-card">

        <?php if (!empty($jobs)): ?>
            <div class="table-header">
                <h2>Job Listings</h2>
                <span><?= count($jobs); ?> Job(s) Found</span>
            </div>
        <?php endif; ?>

        <table class="data-table">

            <thead>

                <tr>
                    <th>Job Title</th>
                    <th>Department</th>
                    <th>Employment</th>
                    <th>Vacancies</th>
                    <th>Deadline</th>
                    <th>Status</th>
                    <th width="260">Actions</th>
                </tr>

            </thead>

            <tbody>

            <?php if(!empty($jobs)): ?>

                <?php foreach($jobs as $job): ?>

                    <tr>

                        <td>
                            <strong><?= htmlspecialchars($job['title']); ?></strong>
                        </td>

                        <td><?= htmlspecialchars($job['department_name']); ?></td>

                        <td><?= htmlspecialchars($job['employment_type']); ?></td>

                        <td><?= htmlspecialchars($job['vacancies']); ?></td>

                        <td><?= htmlspecialchars($job['application_deadline']); ?></td>

                        <td>
                            <span class="status <?= strtolower($job['status']); ?>">
                                <?= htmlspecialchars($job['status']); ?>
                            </span>
                        </td>

                        <td>

                            <a href="?page=view&id=<?= $job['position_id']; ?>" class="btn-outline">
                                <i class="fa-solid fa-eye"></i>
                            </a>

                            <a href="?page=edit&id=<?= $job['position_id']; ?>" class="btn-outline">
                                <i class="fa-solid fa-pen"></i>
                            </a>

                            <?php if($job['status'] == 'Draft'): ?>

                                <form method="POST" action="?page=publish-job">

                                    <input
                                        type="hidden"
                                        name="position_id"
                                        value="<?= $job['position_id']; ?>">

                                    <button class="btn-success">
                                        <i class="fa-solid fa-bullhorn"></i>
                                    </button>

                                </form>

                            <?php endif; ?>

                            <?php if($job['status'] == 'Open'): ?>

                                <form
                                    method="POST"
                                    action="?page=close"
                                    onsubmit="return confirm('Close this job posting?');">

                                    <input
                                        type="hidden"
                                        name="position_id"
                                        value="<?= $job['position_id']; ?>">

                                    <button class="btn-danger">
                                        <i class="fa-solid fa-lock"></i>
                                    </button>

                                </form>

                            <?php endif; ?>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>

                    <td colspan="7" class="empty-state">
                        No job postings available.
                    </td>

                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </section>

</div>

<?php require '../resources/views/includes/footer.php'; ?>