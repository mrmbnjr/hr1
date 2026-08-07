<?php
// Expects $departmentLookup (or $departments) to be available for the position select.
// Falls back gracefully if not set.
$departmentOptions = $departmentLookup ?? $departments ?? [];
?>

<!-- =====================================================
    ADD DEPARTMENT MODAL
===================================================== -->
<div class="cs-modal" id="addDepartmentModal">

    <div class="modal-backdrop"></div>

    <div class="modal-content">

        <div class="modal-header">
            <h3>New Department</h3>
            <button type="button" class="modal-close" data-close-modal="addDepartmentModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="addDepartmentForm" method="POST" action="?page=human-capital&action=createDepartment">

            <div class="modal-body">

                <div class="form-group">
                    <label for="departmentName">Department Name</label>
                    <input
                        type="text"
                        id="departmentName"
                        name="department_name"
                        placeholder="e.g. Marketing"
                        required>
                </div>

                <div class="form-group">
                    <label for="departmentCode">Department Code</label>
                    <input
                        type="text"
                        id="departmentCode"
                        name="department_code"
                        placeholder="e.g. MKT">
                </div>

                <div class="form-group">
                    <label for="departmentDescription">Description</label>
                    <textarea
                        id="departmentDescription"
                        name="description"
                        rows="3"
                        placeholder="Brief description of this department's function"></textarea>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" data-close-modal="addDepartmentModal">Cancel</button>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-check"></i>
                    Create Department
                </button>
            </div>

        </form>

    </div>

</div>

<!-- =====================================================
    ADD POSITION MODAL
===================================================== -->
<div class="cs-modal" id="addPositionModal">

    <div class="modal-backdrop"></div>

    <div class="modal-content">

        <div class="modal-header">
            <h3>New Position</h3>
            <button type="button" class="modal-close" data-close-modal="addPositionModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="addPositionForm" method="POST" action="?page=human-capital&action=createPosition">

            <div class="modal-body">

                <div class="form-group">
                    <label for="positionTitle">Position Title</label>
                    <input
                        type="text"
                        id="positionTitle"
                        name="position_name"
                        placeholder="e.g. Software Engineer"
                        required>
                </div>

                <div class="form-group">
                    <label for="positionDepartment">Department</label>
                    <select id="positionDepartment" name="department_id" required>
                        <option value="" disabled selected>Select department</option>
                        <?php foreach ($departmentOptions as $dept): ?>
                            <option value="<?= $dept['department_id']; ?>">
                                <?= htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-row">

                    <div class="form-group">
                        <label for="employmentType">Employment Type</label>
                        <select id="employmentType" name="employment_type" required>
                            <option value="Full-time">Full-time</option>
                            <option value="Part-time">Part-time</option>
                            <option value="Contract">Contract</option>
                            <option value="Internship">Internship</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="vacancies">Vacancies</label>
                        <input
                            type="number"
                            id="vacancies"
                            name="vacancies"
                            min="1"
                            value="1"
                            required>
                    </div>

                </div>

                <div class="form-group">
                    <label for="positionStatus">Status</label>
                    <select id="positionStatus" name="status">
                        <option value="Open">Open</option>
                        <option value="Draft">Draft</option>
                        <option value="Closed">Closed</option>
                    </select>
                </div>

            </div>

            <div class="modal-footer">
                <button type="button" class="btn-secondary" data-close-modal="addPositionModal">Cancel</button>
                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-check"></i>
                    Create Position
                </button>
            </div>

        </form>

    </div>

</div>