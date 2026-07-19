<?php
$pageTitle = "Recruitment";
$pageCSS = "recruitment.css";
$pageDescription = "Manage your recruitment processes at RAM-YUM Store.";

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

        <section class="recruitment-hero">
            <div>
                <span class="hero-tag">💼 Recruitment Module</span>
                <h1>Recruitment Management</h1>
                <p>Manage job postings, monitor recruitment pipelines and track hiring performance.</p>
            </div>

            <a href="?page=create" class="btn-primary">
                <i class="fa-solid fa-plus"></i>New Job Posting
            </a>
        </section>

        <!-- ==========================================
            QUICK STATS
        =========================================== -->

        <section class="recruitment-stats">
            <article class="mini-card">
                <i class="fa-solid fa-briefcase"></i>
                <div>
                    <h2>12</h2>
                    <span>Open Jobs</span>
                </div>
            </article>

            <article class="mini-card">
                <i class="fa-solid fa-users"></i>
                <div>
                    <h2>245</h2>
                    <span>Total Applicants</span>
                </div>
            </article>

            <article class="mini-card">
                <i class="fa-solid fa-user-check"></i>
                <div>
                    <h2>38</h2>
                    <span>Shortlisted</span>
                </div>
            </article>

            <article class="mini-card">
                <i class="fa-solid fa-handshake"></i>
                <div>
                    <h2>14</h2>
                    <span>Hired</span>
                </div>
            </article>
        </section>

        <!-- ==========================================
            FILTERS
        =========================================== -->

        <section class="filter-card">
            <div class="filter-group">
                <select>
                    <option>All Departments</option>
                    <option>Human Resources</option>
                    <option>Finance</option>
                    <option>IT</option>
                    <option>Warehouse</option>
                </select>

                <select>
                    <option>All Employment Types</option>
                    <option>Full-Time</option>
                    <option>Part-Time</option>
                    <option>Contract</option>
                    <option>Internship</option>
                </select>

                <select>
                    <option>All Status</option>
                    <option>Open</option>
                    <option>Closed</option>
                </select>

                <select>
                    <option>Newest First</option>
                    <option>Oldest First</option>
                    <option>Application Deadline</option>
                </select>
            </div>
        </section>

        <!-- ==========================================
            JOBS
        =========================================== -->

        <section class="job-grid">
            <?php if (!empty($jobs)): ?>
                <?php foreach($jobs as $job): ?>
                    <article class="job-card">
                        <div class="job-header">
                            <div>
                                <h2><?= htmlspecialchars($job['title']) ?></h2>
                                <p><?= htmlspecialchars($job['department_name']) ?></p>
                            </div>

                            <span class="status <?= strtolower($job['status']) ?>">
                                <?= htmlspecialchars($job['status']) ?>
                            </span>
                        </div>

                        <div class="job-meta">
                            <div>
                                <strong>Employment</strong>
                                <span><?= htmlspecialchars($job['employment_type']) ?></span>
                            </div>

                            <div>
                                <strong>Vacancies</strong>
                                <span><?= htmlspecialchars($job['vacancies']) ?></span>
                            </div>

                            <div>
                                <strong>Deadline</strong>
                                <span><?= htmlspecialchars($job['application_deadline']) ?></span>
                            </div>
                        </div>

                        <div class="pipeline">
                            <div>
                                <h3><?= $job['applicants'] ?></h3>
                                <span>Applicants</span>
                            </div>

                            <div>
                                <h3><?= $job['shortlisted'] ?></h3>
                                <span>Shortlisted</span>
                            </div>

                            <div>
                                <h3><?= $job['interview'] ?></h3>
                                <span>Interview</span>
                            </div>

                            <div>
                                <h3><?= $job['hired'] ?></h3>
                                <span>Hired</span>
                            </div>
                        </div>

                        <div class="job-actions">
                            <a href="?page=view-job&id=<?= $job['position_id']; ?>"class="btn-outline">
                                <i class="fa-solid fa-eye"></i>View
                            </a>

                            <a href="?page=edit-job&id=<?= $job['position_id']; ?>"class="btn-outline">
                                <i class="fa-solid fa-pen"></i>Edit
                            </a>

                            <form method="POST" action="?page=close"
                                onsubmit="return confirm('Close this vacancy?');">

                                <input type="hidden"
                                    name="position_id"
                                    value="<?= $job['position_id']; ?>">

                                <button type="submit" class="btn-danger">
                                    <i class="fa-solid fa-lock"></i>
                                    Close Vacancy
                                </button>

                            </form>                        
                        </div>
                    </article>
                <?php endforeach; ?>

                <?php else: ?>
                    <div class="empty-state">
                        No job postings found.
                    </div>
            <?php endif; ?>
        </section>
    </main>
</div>
<?php require '../resources/views/includes/footer.php'; ?>