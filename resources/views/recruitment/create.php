<?php
$pageTitle = "Create Job Posting";
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
    <section class="page-header">

        <div>
            <h1>Create Job Posting</h1>
            <p>Create a new vacancy for recruitment.</p>
        </div>

        <a href="?page=recruitment" class="btn-outline">
            <i class="fa-solid fa-arrow-left"></i>
            Back
        </a>

    </section>

    <form action="?page=store-job" method="POST" class="form-card">

        <div class="form-grid">

            <div class="form-group">
                <label>Job Title</label>

                <input
                    type="text"
                    name="title"
                    placeholder="Software Engineer"
                    required>
            </div>

            <div class="form-group">
                <label>Department</label>

                <select name="department_id" required>

                    <option value="">Select Department</option>

                    <?php if (!empty($departments)): ?>
                        <?php foreach ($departments as $department): ?>
                        <option value="<?= $department['department_id']; ?>">
                            <?= htmlspecialchars($department['department_name']); ?>
                        </option>
                    <?php endforeach; ?>

                    <?php else: ?>
                        <div class="empty-state">
                            No job postings found.
                        </div>
                    <?php endif; ?>
                </select>
            </div>

            <div class="form-group">
                <label>Employment Type</label>

                <select name="employment_type" required>

                    <option value="">Select Type</option>

                    <option>Full-Time</option>
                    <option>Part-Time</option>
                    <option>Contract</option>
                    <option>Internship</option>

                </select>
            </div>

            <div class="form-group">
                <label>Vacancies</label>

                <input
                    type="number"
                    name="vacancies"
                    min="1"
                    value="1"
                    required>
            </div>

            <div class="form-group">
                <label>Salary</label>

                <input
                    type="number"
                    name="salary"
                    placeholder="25000">
            </div>

            <div class="form-group">
                <label>Application Deadline</label>

                <input
                    type="date"
                    name="application_deadline"
                    required>
            </div>

        </div>

        <div class="form-group">

            <label>Job Description</label>

            <textarea
                name="description"
                rows="6"
                placeholder="Describe the job responsibilities..."
                required></textarea>

        </div>

        <div class="form-group">
            <label>Requirements</label>
            <textarea
                name="requirements"
                rows="6"
                placeholder="Required skills, education, experience..."
                required></textarea>
        </div>

        <div class="form-actions">

            <button type="reset" class="btn-outline">
                Reset
            </button>

            <button type="submit" class="btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>Create Job Posting
            </button>
        </div>
    </form>
</div>

<?php require '../resources/views/includes/footer.php'; ?>