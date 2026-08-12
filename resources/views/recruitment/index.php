<?php

$pageTitle       = "Recruitment Management";
$pageCSS         = "recruitment.css";
$pageJS          = "recruitment.js";
$pageDescription = "Create and manage job postings";

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

    <section class="recruitment-toolbar">
        <div class="toolbar-filters">
            <select id="jobFilter">
                <option value="">Job Title</option>

                <?php if (!empty($jobs)): ?>
                    <?php foreach ($jobs as $job): ?>
                        <option value="<?= htmlspecialchars($job['title']) ?>">
                            <?= htmlspecialchars($job['title']) ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>

            </select>

            <select id="departmentFilter">

                <option value="">All Departments</option>

                <?php if (!empty($departments)): ?>
                    <?php foreach ($departments as $department): ?>

                        <option value="<?= htmlspecialchars($department['department_name']) ?>">
                            <?= htmlspecialchars($department['department_name']) ?>
                        </option>

                    <?php endforeach; ?>
                <?php endif; ?>

            </select>

            <select id="employmentFilter">
                <option value="">All Employment Types</option>
                <option value="Full-Time">Full-Time</option>
                <option value="Part-Time">Part-Time</option>
                <option value="Contract">Contract</option>
                <option value="Internship">Internship</option>
            </select>

            <select id="statusFilter">
                <option value="">All Status</option>
                <option value="Open">Open</option>
                <option value="Closed">Closed</option>
            </select>

        </div>

        <a href="?page=create" class="btn-primary">
            <i class="fa-solid fa-plus"></i>
            Add Post
        </a>

    </section>


    <!-- ==========================================
        JOB TABLE
    =========================================== -->

    <section class="table-card">
        <div class="table-content">
            <table class="data-table">

                <thead>

                    <tr>
                        <th>(<?= $total_jobs = isset($jobs) ? count($jobs) : 0; ?>) Job Titles</th>
                        <th>DEPARTMENTS</th>
                        <th>EMPLOYMENT TYPES</th>
                        <th>APPLICATIONS</th>
                        <th>DEADLINE</th>
                        <th>STATUS</th>
                        <th>APPLICATION LINK</th>
                        <th width="170">ACTIONS</th>
                    </tr>

                </thead>

                <tbody>

                <?php if (!empty($jobs)): ?>

                    <?php foreach ($jobs as $job): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($job['title']) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($job['department_name']) ?>
                            </td>

                            <td>
                                <span class="badge employment">
                                    <?= htmlspecialchars($job['employment_type']) ?>
                                </span>
                            </td>

                            <td>
                                <?= htmlspecialchars($job['applicants']) ?>
                            </td>

                            <td>
                                <i class="fa-regular fa-calendar"></i>
                                <?= htmlspecialchars($job['application_deadline']) ?>
                            </td>

                            <td>

                                <span class="badge status <?= strtolower($job['status']) ?>">
                                    <?= htmlspecialchars($job['status']) ?>
                                </span>

                            </td>

                            <td class="application-link-cell">
                                <?php if (
                                    $job['status'] === 'Open' &&
                                    !empty($job['application_token'])
                                ): ?>

                                    <?php
                                    $applicationUrl =
                                        '/hr1/public/index.php?page=apply&token=' .
                                        urlencode($job['application_token']);
                                    ?>

                                    <button
                                        type="button"
                                        class="action-btn link-btn"
                                        title="Copy application link"
                                        onclick="copyApplicationLink(
                                            <?= htmlspecialchars(
                                                json_encode($applicationUrl),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        )"
                                    >
                                        <i class="fa-solid fa-link"></i>
                                    </button>

                                <?php else: ?>

                                    <span class="no-link">
                                        —
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td class="actions">
                                <!-- View -->
                                <a href="?page=view&id=<?= $job['posting_id'] ?>" class="action-btn">
                                    <i class="fa-solid fa-eye"></i>
                                </a>

                                <!-- Edit -->
                                <a href="?page=edit&id=<?= $job['posting_id'] ?>" class="action-btn">
                                    <i class="fa-solid fa-pen"></i>
                                </a>

                                <!-- Close -->
                                <?php if ($job['status'] === 'Open'): ?>
                                    <form method="POST"
                                        action="?page=close"
                                        style="display:inline;"
                                        onsubmit="return confirm('Close this job posting?');">

                                        <input
                                            type="hidden"
                                            name="posting_id"
                                            value="<?= $job['posting_id'] ?>">

                                        <button type="submit" class="action-btn warning">
                                            <i class="fa-solid fa-lock"></i>
                                        </button>

                                    </form>
                                <?php endif; ?>

                                <!-- Archive -->
                                <form method="POST"
                                    action="?page=delete"
                                    style="display:inline;"
                                    onsubmit="return confirm('Archive this job posting?');">

                                    <input
                                        type="hidden"
                                        name="posting_id"
                                        value="<?= $job['posting_id'] ?>">

                                    <button type="submit" class="action-btn delete">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="8" class="empty-state">
                            No job postings found.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>
            </table>
        </div>


        <!-- ==========================================
            TABLE FOOTER
        =========================================== -->
        <div class="table-footer">
            <?php
            $totalJobs = !empty($jobs) ? count($jobs) : 0;
            ?>

            <span>
                Showing
                <?= $totalJobs ? 1 : 0 ?>
                to
                <?= $totalJobs ?>
                of
                <?= $totalJobs ?>
            </span>

            <div class="pagination">

                <button class="page-btn">
                    <i class="fa-solid fa-angle-left"></i>
                </button>

                <button class="page-btn active">
                    1
                </button>

                <button class="page-btn">
                    <i class="fa-solid fa-angle-right"></i>
                </button>

            </div>

        </div>

    </section>
    <?php require '../resources/views/includes/footer.php'; ?>
</div>

<?php require '../resources/views/includes/scripts.php'; ?>