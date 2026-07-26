<?php

$pageTitle = "Edit Job Posting";
$pageCSS = "recruitment.css";
$pageDescription = "Edit an existing job posting.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>

    <form method="POST" action="?page=update" class="form-card">

        <input
            type="hidden"
            name="posting_id"
            value="<?= htmlspecialchars($job['posting_id']) ?>">

        <h2>Basic Information</h2>

        <div class="form-grid">

            <div class="form-group">

                <label>
                    Job Title
                    <span class="required">*</span>
                </label>

                <input
                    type="text"
                    name="title"
                    value="<?= htmlspecialchars($job['title']) ?>"
                    required>

            </div>

            <div class="form-group">

                <label>Department</label>

                <select name="department_id" required>

                    <?php foreach ($departments as $department): ?>

                        <option
                            value="<?= htmlspecialchars($department['department_id']) ?>"
                            <?= $department['department_id'] == $job['department_id'] ? 'selected' : '' ?>>

                            <?= htmlspecialchars($department['department_name']) ?>

                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <div class="form-group">

                <label>Employment Type</label>

                <select name="employment_type" required>

                    <option value="Full-Time"
                        <?= $job['employment_type'] == 'Full-Time' ? 'selected' : '' ?>>
                        Full-Time
                    </option>

                    <option value="Part-Time"
                        <?= $job['employment_type'] == 'Part-Time' ? 'selected' : '' ?>>
                        Part-Time
                    </option>

                    <option value="Contract"
                        <?= $job['employment_type'] == 'Contract' ? 'selected' : '' ?>>
                        Contract
                    </option>

                    <option value="Internship"
                        <?= $job['employment_type'] == 'Internship' ? 'selected' : '' ?>>
                        Internship
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label>Vacancies</label>

                <input
                    type="number"
                    name="vacancies"
                    min="1"
                    value="<?= htmlspecialchars($job['vacancies']) ?>"
                    required>

            </div>

            <div class="form-group">

                <label>Status</label>

                <select name="status" required>

                    <option
                        value="Open"
                        <?= $job['status'] == 'Open' ? 'selected' : '' ?>>
                        Open
                    </option>

                    <option
                        value="Closed"
                        <?= $job['status'] == 'Closed' ? 'selected' : '' ?>>
                        Closed
                    </option>

                </select>

            </div>

            <div class="form-group">

                <label>Application Deadline</label>

                <input
                    type="date"
                    name="application_deadline"
                    value="<?= htmlspecialchars($job['application_deadline']) ?>"
                    min="<?= date('Y-m-d') ?>"
                    required>

            </div>

        </div>

        <hr>

        <h2>Job Description</h2>

        <div class="form-group">

            <label>Description</label>

            <textarea
                name="description"
                rows="6"
                required><?= htmlspecialchars($job['description']) ?></textarea>

        </div>

        <div class="form-group">

            <label>Qualifications / Requirements</label>

            <textarea
                name="requirements"
                rows="6"
                required><?= htmlspecialchars($job['requirements']) ?></textarea>

        </div>

        <hr>

        <div class="form-actions">

            <a href="?page=recruitment" class="btn-inline">
                Cancel
            </a>

            <button
                type="submit"
                class="btn-primary">

                <i class="fa-solid fa-floppy-disk"></i>
                Save Changes

            </button>

        </div>

    </form>

    <?php require '../resources/views/includes/footer.php'; ?>

</div>

<?php require '../resources/views/includes/scripts.php'; ?>