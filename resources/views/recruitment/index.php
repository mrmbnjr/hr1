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
        <table class="data-table">
            <thead>
                <tr>
                    <th>(<?= !empty($jobs) ? count($jobs) : 0 ?>) Job Title</th>
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

    <?php require '../resources/views/includes/footer.php'; ?>
</div>

<?php require '../resources/views/includes/scripts.php'?>