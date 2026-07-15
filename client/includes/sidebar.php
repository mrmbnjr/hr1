<aside class="sidebar">

    <div class="sidebar-header">
        <h2>RAM-YUM</h2>
        <p>Recruitment System</p>
    </div>

    <nav class="sidebar-menu">

        <a href="<?= BASE_URL ?>/modules/dashboard/index.php">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>

        <?php if ($_SESSION['role'] === 'admin'): ?>
            <a href="<?= BASE_URL ?>/modules/users/index.php">
                <i class="fa-solid fa-users"></i>
                <span>Users</span>
            </a>
        <?php endif; ?>

        <?php if (in_array($_SESSION['role'], ['admin', 'hr'])): ?>
            <a href="<?= BASE_URL ?>/modules/applications/index.php">
                <i class="fa-solid fa-file-lines"></i>
                <span>Applications</span>
            </a>
        <?php endif; ?>

        <?php if (in_array($_SESSION['role'], ['admin', 'hr'])): ?>
            <a href="<?= BASE_URL ?>/modules/jobmanagement/index.php">
                <i class="fa-solid fa-briefcase"></i>
                <span>Job Management</span>
            </a>
        <?php endif; ?>

        <?php if (in_array($_SESSION['role'], ['admin', 'hr'])): ?>
            <a href="<?= BASE_URL ?>/modules/rolesandpermissions/index.php">
                <i class="fa-solid fa-user-shield"></i>
                <span>Roles & Permissions</span>
            </a>
        <?php endif; ?>

        <?php if (in_array($_SESSION['role'], ['admin', 'hr'])): ?>
            <a href="<?= BASE_URL ?>/modules/reports/index.php">
                <i class="fa-solid fa-chart-column"></i>
                <span>Reports</span>
            </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>

    </nav>

</aside>