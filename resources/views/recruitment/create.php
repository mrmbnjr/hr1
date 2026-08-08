<?php

$pageTitle = "Create Job Posting";
$pageCSS = "recruitment.css";
$pageJS = "recruitment.js";
$pageDescription = "Create a new job posting.";

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
        FORM
    ========================================== -->

    <form action="?page=store" method="POST" class="form-card">

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
                    value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                    placeholder="e.g. Sales Associate"
                    required>
            </div>

            <div class="form-group">
                <label>Department</label>

                <select id="department" name="department_id" required>
                    <option value="" hidden selected>
                        Select Department
                    </option>

                    <?php if (!empty($departments)): ?>

                        <?php foreach ($departments as $department): ?>
                            <option
                                value="<?= htmlspecialchars($department['department_id']) ?>">

                                <?= htmlspecialchars($department['department_name']); ?>

                            </option>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <option value="" disabled>
                            No departments available
                        </option>

                    <?php endif; ?>

                </select>
            </div>

            <div class="form-group">
                <label>
                    Position
                    <span class="required">*</span>
                </label>

                <select id="position" name="position_id" required>
                    <option value="" hidden selected>
                        Select Position
                    </option>

                    <?php if (!empty($positions)): ?>

                        <?php foreach ($positions as $position): ?>

                            <option
                                value="<?= htmlspecialchars($position['position_id']) ?>"
                                data-department="<?= htmlspecialchars($position['department_id']) ?>"
                                <?= isset($job) && $position['position_id'] == $job['position_id'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars($position['position_name']) ?>

                            </option>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <option value="" disabled>
                            No positions available
                        </option>

                    <?php endif; ?>

                </select>
            </div>

            <div class="form-group">
                <label>Employment Type</label>

                <select name="employment_type" required>
                    <option value="Full-Time">Full-Time</option>
                    <option value="Part-Time">Part-Time</option>
                    <option value="Contract">Contract</option>
                    <option value="Internship">Internship</option>
                </select>
            </div>

            <div class="form-group">
                <label>
                    Number of Vacancies
                    <span class="required">*</span>
                </label>

                <input
                    type="number"
                    name="vacancies"
                    min="1"
                    value="<?= htmlspecialchars($_POST['vacancies'] ?? '1') ?>"
                    required>

            </div>

            <div class="form-group">
                <label>Application Deadline</label>

                <input
                    type="date"
                    name="application_deadline"
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
                placeholder="Describe the responsibilities of this position..."
                required></textarea>

        </div>

        <div class="form-group">

            <label>Qualifications / Requirements</label>

            <textarea
                name="requirements"
                rows="6"
                placeholder="List the qualifications, education, and experience required..."
                required></textarea>

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
                Save Post

            </button>

        </div>

    </form>

    <?php require '../resources/views/includes/footer.php'; ?>
</div>

<?php require '../resources/views/includes/scripts.php'?>