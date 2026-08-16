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

            <button type="button"
                    class="modal-close"
                    data-close-modal="addDepartmentModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="addDepartmentForm" method="POST" action="?page=save-department">   
            <div class="modal-body">

                <div class="form-group">
                    <label for="departmentName">Department Name</label>

                    <input
                        type="text"
                        id="departmentName"
                        name="department_name"
                        placeholder="e.g. Marketing"
                        minlength="3" maxlength="32"
                        required>
                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn-secondary"
                        data-close-modal="addDepartmentModal">
                    Cancel
                </button>

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

            <button type="button"
                    class="modal-close"
                    data-close-modal="addPositionModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form id="addPositionForm" method="POST" action="?page=create-position">

            <div class="modal-body">

                <div class="form-group">
                    <label for="positionTitle">Position Title</label>

                    <input
                        type="text"
                        id="positionTitle"
                        name="position_name"
                        placeholder="e.g. Software Engineer"
                        minlength="3" maxlength="32"
                        required>
                </div>

                <div class="form-group">
                    <label for="positionRole">Default System Role</label>

                    <select
                        id="positionRole"
                        name="role_id"
                        required>

                        <option value="" hidden selected>
                            Select system role
                        </option>

                        <?php foreach ($roles as $role): ?>
                            <option value="<?= $role['role_id']; ?>">
                                <?= htmlspecialchars($role['role_name']); ?>
                            </option>
                        <?php endforeach; ?>

                    </select>

                    <small class="form-help">
                        This role will be assigned automatically when an employee is hired for this position.
                    </small>
                </div>

                <div class="form-group">
                    <label for="positionDepartment">Department</label>

                    <?php if (empty($departmentLookup)): ?>

                        <select id="positionDepartment"
                                name="department_id"
                                disabled>

                            <option value="">
                                No departments available
                            </option>

                        </select>

                        <small class="form-help">
                            Create a department first before adding a position.
                        </small>

                    <?php else: ?>

                        <select id="positionDepartment"
                                name="department_id"
                                required>

                            <option value="" hidden selected>
                                Select department
                            </option>

                            <?php foreach ($departmentLookup as $dept): ?>
                                <option value="<?= $dept['department_id']; ?>">
                                    <?= htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>

                        </select>

                    <?php endif; ?>
                </div>

            </div>

            <div class="modal-footer">

                <button type="button"
                        class="btn-secondary"
                        data-close-modal="addPositionModal">
                    Cancel
                </button>

                <button type="submit" class="btn-primary">
                    <i class="fa-solid fa-check"></i>
                    Create Position
                </button>

            </div>

        </form>

    </div>
</div>