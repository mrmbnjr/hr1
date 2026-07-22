<?php

$pageTitle = "Create Job Posting";
$pageCSS = "recruitment.css";
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
    HERO
========================================== -->

<section class="page-hero">

    <div>
        <span class="hero-tag">📢 Recruitment</span>
        <h1>Create Job Posting</h1>
        <p>Fill in the details below to publish a new job opening.</p>
    </div>

</section>

<!-- ==========================================
    FORM
========================================== -->

<form action="?page=store-job" method="POST" class="form-card">

    <h2>Basic Information</h2>

    <div class="form-grid">

        <div class="form-group">
            <label>Job Title</label>
            <input
                type="text"
                name="title"
                placeholder="e.g. Sales Associate"
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
                <?php endif; ?>
            </select>

        </div>

        <div class="form-group">
            <label>Employment Type</label>

            <select name="employment_type" required>
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
            <label>Salary (Optional)</label>

            <input
                type="number"
                name="salary"
                placeholder="0.00"
                step="0.01">
        </div>

        <div class="form-group">
            <label>Application Deadline</label>

            <input
                type="date"
                name="application_deadline"
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

    <h2>Publishing</h2>

    <div class="form-grid">

        <div class="form-group">

            <label>Status</label>

            <select name="status">

                <option value="Draft">
                    Save as Draft
                </option>

                <option value="Open">
                    Publish Immediately
                </option>

            </select>

        </div>

    </div>

    <div class="form-actions">

        <a href="?page=recruitment" class="btn-inline">
            Cancel
        </a>

        <button
            type="submit"
            class="btn-primary">

            <i class="fa-solid fa-floppy-disk"></i>
            Save Job Posting

        </button>

    </div>

</form>

    <?php require '../resources/views/includes/footer.php'; ?>
</div>

<?php require '../resources/views/includes/scripts.php'?>