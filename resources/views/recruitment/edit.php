<?php

$pageTitle = "Edit Job Posting";
$pageCSS = "recruitment.css";
$pageJS = "recruitment.js";
$pageDescription = "Edit an existing job posting.";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url("?page=login"));
    exit;
}

$job = isset($job) && is_array($job) ? $job : [];

?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>

    <div class="posting-page">
        <div class="posting-layout">
            <form method="POST" action="?page=update" class="form-card">

        <input
            type="hidden"
            name="posting_id"
            value="<?= htmlspecialchars($job['posting_id']) ?>">

        <div class="form-section-heading">
            <span class="section-icon"><i class="fa-solid fa-briefcase"></i></span>
            <div><h2>Basic information</h2><p>Keep the public details accurate and easy to scan.</p></div>
        </div>

        <div class="form-grid">
            <div class="form-group">
                <label>
                    Job Title
                    <span class="required">*</span>
                </label>

                <input id="postingTitle"
                    type="text"
                    name="title"
                    value="<?= htmlspecialchars($job['title']) ?>"
                    minlength="3" maxlength="32"
                    required>

            </div>

            <div class="form-group">

                <label>Department</label>

                <select id="department" name="department_id" required>

                    <?php if (!empty($departments)): ?>

                        <?php foreach ($departments as $department): ?>

                            <option
                                value="<?= htmlspecialchars($department['department_id']) ?>"
                                <?= $department['department_id'] == $job['department_id'] ? 'selected' : '' ?>>

                                <?= htmlspecialchars($department['department_name']) ?>

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

                    <?php if (!empty($positions)): ?>

                        <?php foreach ($positions as $position): ?>

                            <option
                                value="<?= htmlspecialchars($position['position_id']) ?>"
                                data-department="<?= htmlspecialchars($position['department_id']) ?>"
                                <?= $position['position_id'] == $job['position_id'] ? 'selected' : '' ?>>

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
                <label>Branch</label>

                <select id="branch" name="branch" required>
                    <option value="" hidden selected>
                        Select Branch
                    </option>

                    <!-- Sample branch data -->
                    <option value="Quezon City">
                        Quezon City
                    </option>
                </select>
            </div>

            <div class="form-group">

                <label>Employment Type</label>

                <select id="employmentType" name="employment_type" required>

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
                <label>Certificate Required</label>
                <div>
                    <label><input type="radio" name="academic_document_required" value="1" <?= !empty($job['academic_document_required']) ? 'checked' : '' ?>> Yes</label>
                    <label><input type="radio" name="academic_document_required" value="0" <?= empty($job['academic_document_required']) ? 'checked' : '' ?>> No</label>
                </div>
            </div>

            <div class="form-group">

                <label>
                    Number of Vacancies
                    <span class="required">*</span>
                </label>

                <input id="vacancies"
                    type="number"
                    name="vacancies"
                    min="1"
                    value="<?= htmlspecialchars($job['vacancies']) ?>"
                    required>

            </div>

            <div class="form-group">
                <label>
                    Maximum Applicants
                    <span class="required">*</span>
                </label>

                <input id="maxApplicants"
                    type="number"
                    name="max_applicants"
                    min="1"
                    value="<?= htmlspecialchars((string) ($job['max_applicants'] ?? 100)) ?>"
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

                <input id="deadline"
                    type="date"
                    name="application_deadline"
                    value="<?= htmlspecialchars($job['application_deadline']) ?>"
                    min="<?= date('Y-m-d') ?>"
                    required>
                    
            </div>
        </div>

        <hr>

        <div class="form-section-heading">
            <span class="section-icon section-icon-gold"><i class="fa-solid fa-align-left"></i></span>
            <div><h2>Tell the story</h2><p>Make the responsibilities and requirements feel concrete.</p></div>
        </div>

        <div class="form-group">

            <label>Description</label>

            <textarea id="description"
                name="description"
                rows="6"
                minlength="3" maxlength="500"
                required><?= htmlspecialchars($job['description']) ?>
            </textarea>
        </div>

        <div class="form-group">

            <label>Qualifications / Requirements</label>

            <textarea id="requirements"
                name="requirements"
                rows="6"
                minlength="3" maxlength="500"
                required><?= htmlspecialchars($job['requirements']) ?>
            </textarea>
        </div>

        <div class="form-group">

            <label>Salary Range</label>

            <input type="text"
                id="salary_range"
                name="salary_range"
                maxlength="100"
                placeholder="e.g. PHP 20,000 - 25,000 per month"
                value="<?= htmlspecialchars((string) ($job['salary_range'] ?? '')) ?>">
        </div>

        <div class="form-group">

            <label>Benefits</label>

            <textarea id="benefits"
                name="benefits"
                rows="4"
                maxlength="1000"
                placeholder="One benefit per line, e.g.
Meal allowance
Flexible scheduling
Career growth opportunities"
            ><?= htmlspecialchars($job['benefits'] ?? '') ?></textarea>

            <small class="form-hint">Optional. Enter one benefit per line — they are shown to applicants on the listing and application pages. Leave empty to hide benefits.</small>

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

            <aside class="posting-preview" aria-live="polite">
                <div class="preview-card">
                    <div class="preview-mark"><i class="fa-solid fa-building"></i></div>
                    <span class="preview-status">OPEN POSITION</span>
                    <h2 data-preview="title">Untitled position</h2>
                    <p class="preview-department" data-preview="department">Select a department</p>
                    <div class="preview-meta"><span><i class="fa-solid fa-clock"></i><b data-preview="employment">Full-Time</b></span><span><i class="fa-solid fa-users"></i><b data-preview="vacancies">1 vacancy</b></span></div>
                    <div class="preview-divider"></div>
                    <p class="preview-deadline"><i class="fa-regular fa-calendar"></i> Apply by <b data-preview="deadline">Choose a deadline</b></p>
                    <div class="preview-tip"><i class="fa-solid fa-lightbulb"></i><span>Your saved details are reflected in this preview.</span></div>
                </div>
                <div class="posting-checklist">
                    <h3><i class="fa-solid fa-list-check"></i> Before publishing</h3>
                    <p class="checklist-item" data-check="title"><i class="fa-regular fa-circle"></i> Add a clear job title</p>
                    <p class="checklist-item" data-check="description"><i class="fa-regular fa-circle"></i> Describe the role</p>
                    <p class="checklist-item" data-check="requirements"><i class="fa-regular fa-circle"></i> Add requirements</p>
                    <p class="checklist-item" data-check="deadline"><i class="fa-regular fa-circle"></i> Set an application deadline</p>
                </div>
            </aside>
        </div>
    </div>

    <?php require BASE_PATH . '/resources/views/includes/footer.php'; ?>

</div>

<?php require BASE_PATH . '/resources/views/includes/scripts.php'; ?>