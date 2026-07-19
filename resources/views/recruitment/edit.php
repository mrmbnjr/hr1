<?php
$pageTitle = "Edit Job Posting";
$pageCSS = "recruitment.css";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}

// Sample data
$job = [
    'title' => 'Cashier',
    'department_id' => 1,
    'employment_type' => 'Full-Time',
    'salary' => '18000',
    'vacancies' => 3,
    'status' => 'Open',
    'deadline' => '2026-08-15',
    'description' => 'Responsible for handling customer payments.',
    'requirements' => 'Customer Service Skills'
];
?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <div class="page-header">
        <h1>Edit Job Posting</h1>
    </div>

    <form method="POST" action="?page=recruitment-update&id=1" class="card">

        <div class="form-grid">

            <div class="form-group">
                <label>Job Title</label>

                <input type="text"
                       name="title"
                       value="<?= htmlspecialchars($job['title']) ?>"
                       required>
            </div>

            <div class="form-group">
                <label>Department</label>

                <select name="department_id">

                    <option value="1" selected>Sales</option>
                    <option value="2">Kitchen</option>
                    <option value="3">Accounting</option>

                </select>

            </div>

            <div class="form-group">
                <label>Employment Type</label>

                <select name="employment_type">

                    <option <?= $job['employment_type']=="Full-Time"?"selected":"" ?>>Full-Time</option>
                    <option <?= $job['employment_type']=="Part-Time"?"selected":"" ?>>Part-Time</option>
                    <option <?= $job['employment_type']=="Contract"?"selected":"" ?>>Contract</option>
                    <option <?= $job['employment_type']=="Internship"?"selected":"" ?>>Internship</option>

                </select>

            </div>

            <div class="form-group">
                <label>Salary</label>

                <input type="number"
                       name="salary"
                       value="<?= $job['salary'] ?>">
            </div>

            <div class="form-group">
                <label>Vacancies</label>

                <input type="number"
                       name="vacancies"
                       value="<?= $job['vacancies'] ?>">
            </div>

            <div class="form-group">
                <label>Status</label>

                <select name="status">

                    <option <?= $job['status']=="Open"?"selected":"" ?>>Open</option>
                    <option <?= $job['status']=="Closed"?"selected":"" ?>>Closed</option>

                </select>

            </div>

            <div class="form-group">
                <label>Application Deadline</label>

                <input type="date"
                       name="application_deadline"
                       value="<?= $job['deadline'] ?>">
            </div>

        </div>

        <div class="form-group">

            <label>Job Description</label>

            <textarea
                name="description"
                rows="6"><?= htmlspecialchars($job['description']) ?></textarea>

        </div>

        <div class="form-group">

            <label>Requirements</label>

            <textarea
                name="requirements"
                rows="6"><?= htmlspecialchars($job['requirements']) ?></textarea>

        </div>

        <div class="form-actions">

            <a href="?page=recruitment" class="btn-secondary">
                Cancel
            </a>

            <button type="submit" class="btn-primary">
                Save Changes
            </button>

        </div>

    </form>

</div>

<?php require '../resources/views/includes/footer.php'; ?>