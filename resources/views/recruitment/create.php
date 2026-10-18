<?php

$pageTitle = "Create Job Posting";
$pageCSS = "recruitment.css";
$pageJS = "recruitment.js";
$pageDescription = "Create a new job posting.";

if (!isset($_SESSION['user_id'])) {
    header("Location: " . url("?page=login"));
    exit;
}

?>

<?php require BASE_PATH . '/resources/views/includes/header.php'; ?>
<?php require BASE_PATH . '/resources/views/includes/sidebar.php'; ?>

<div class="main-content">

    <?php require BASE_PATH . '/resources/views/includes/navbar.php'; ?>

    <!-- ==========================================
        FORM
    ========================================== -->

    <div class="posting-page">
        <div class="posting-layout">
            <form action="?page=store" method="POST" class="form-card">

        <div class="form-section-heading">
            <span class="section-icon"><i class="fa-solid fa-briefcase"></i></span>
            <div><h2>Basic information</h2><p>Give applicants the essentials at a glance.</p></div>
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
                    value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                    placeholder="e.g. Sales Associate"
                    minlength="3" maxlength="32"
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
                                value="<?= htmlspecialchars($department['department_id']) ?>"
                                <?= (int) ($preselectedDepartmentId ?? 0) === (int) $department['department_id'] ? 'selected' : '' ?>>

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
                                <?= (isset($job) && $position['position_id'] == $job['position_id'])
                                    || (int) ($preselectedPositionId ?? 0) === (int) $position['position_id']
                                    ? 'selected' : '' ?>>

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
                    <option value="Full-Time">Full-Time</option>
                    <option value="Part-Time">Part-Time</option>
                    <option value="Contract">Contract</option>
                    <option value="Internship">Internship</option>
                </select>
            </div>

            <div class="form-group">
                <label>Certificate Required</label>
                <div>
                    <label><input type="radio" name="academic_document_required" value="1" <?= ($_POST['academic_document_required'] ?? '0') === '1' ? 'checked' : '' ?>> Yes</label>
                    <label><input type="radio" name="academic_document_required" value="0" <?= ($_POST['academic_document_required'] ?? '0') !== '1' ? 'checked' : '' ?>> No</label>
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
                    value="<?= htmlspecialchars($_POST['vacancies'] ?? '1') ?>"
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
                    value="<?= htmlspecialchars($_POST['max_applicants'] ?? '100') ?>"
                    required>

            </div>

            <div class="form-group">
                <label>Application Deadline</label>

                <input id="deadline"
                    type="date"
                    name="application_deadline"
                    min="<?= date('Y-m-d') ?>"
                    required>
            </div>
        </div>

        <hr>

        <div class="form-section-heading">
            <span class="section-icon section-icon-gold"><i class="fa-solid fa-align-left"></i></span>
            <div><h2>Tell the story</h2><p>Help candidates understand the work and what success looks like.</p></div>
        </div>

        <div class="form-group">

            <label>Description</label>

            <textarea id="description"
                name="description"
                rows="6"
                placeholder="Describe the responsibilities of this position..."
                required
                minlength="3"
                maxlength="500"
            ></textarea>

        </div>

        <div class="form-group">

            <label>Qualifications / Requirements</label>

            <textarea id="requirements"
                name="requirements"
                rows="6"
                placeholder="List the qualifications, education, and experience required..."
                required
                minlength="3"
                maxlength="500"
            ></textarea>

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
            ><?= htmlspecialchars($_POST['benefits'] ?? '') ?></textarea>

            <small class="form-hint">Optional. Enter one benefit per line — they are shown to applicants on the listing and application pages.</small>

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

            <aside class="posting-preview" aria-live="polite">
                <div class="preview-card">
                    <div class="preview-mark"><i class="fa-solid fa-building"></i></div>
                    <span class="preview-status">OPEN POSITION</span>
                    <h2 data-preview="title">Untitled position</h2>
                    <p class="preview-department" data-preview="department">Select a department</p>
                    <div class="preview-meta"><span><i class="fa-solid fa-clock"></i><b data-preview="employment">Full-Time</b></span><span><i class="fa-solid fa-users"></i><b data-preview="vacancies">1 vacancy</b></span></div>
                    <div class="preview-divider"></div>
                    <p class="preview-deadline"><i class="fa-regular fa-calendar"></i> Apply by <b data-preview="deadline">Choose a deadline</b></p>
                    <div class="preview-tip"><i class="fa-solid fa-lightbulb"></i><span>Your description and requirements will appear here after publishing.</span></div>
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

<?php require BASE_PATH . '/resources/views/includes/scripts.php'?>