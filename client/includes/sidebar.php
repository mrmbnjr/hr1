<aside class="sidebar">

    <div class="sidebar-header">
        <h2>RAM-YUM</h2>
        <p>Human Resource Management</p>
    </div>

    <nav class="sidebar-menu">

        <!-- Dashboard -->
        <a href="<?= BASE_URL ?>/modules/dashboard/index.php">
            <i class="fa-solid fa-house"></i>
            <span>Dashboard</span>
        </a>

        <!-- ========================= -->
        <!-- Recruitment & Onboarding -->
        <!-- ========================= -->

        <p class="sidebar-title">Recruitment & Onboarding</p>

        <a href="<?= BASE_URL ?>/modules/applicant-management/index.php">
            <i class="fa-solid fa-file-lines"></i>
            <span>Applicant Management</span>
        </a>

        <a href="<?= BASE_URL ?>/modules/recruitment-management/index.php">
            <i class="fa-solid fa-briefcase"></i>
            <span>Recruitment Management</span>
        </a>

        <?php if (in_array($_SESSION['role'], ['admin', 'hr'])): ?>
            <a href="<?= BASE_URL ?>/modules/newhire-onboarding/index.php">
                <i class="fa-solid fa-user-check"></i>
                <span>New Hire Onboarding</span>
            </a>
        <?php endif; ?>

        <!-- ======== -->
        <!-- Core HR -->
        <!-- ======== -->

        <p class="sidebar-title">Core HR</p>

        <a href="<?= BASE_URL ?>/modules/hcm/index.php">
            <i class="fa-solid fa-id-card"></i>
            <span>Human Capital Management</span>
        </a>

        <?php if (in_array($_SESSION['role'], ['admin', 'hr'])): ?>
            <a href="<?= BASE_URL ?>/modules/employee-records/index.php">
                <i class="fa-solid fa-address-book"></i>
                <span>Employee Records</span>
            </a>
        <?php endif; ?>

        <a href="<?= BASE_URL ?>/modules/employee-self-service/index.php">
            <i class="fa-solid fa-user"></i>
            <span>Employee Self-Service</span>
        </a>

        <!-- ================= -->
        <!-- Administrator Only -->
        <!-- ================= -->

        <?php if ($_SESSION['role'] === 'admin'): ?>

            <p class="sidebar-title">Administration</p>

            <a href="<?= BASE_URL ?>/modules/users/index.php">
                <i class="fa-solid fa-users"></i>
                <span>User Management</span>
            </a>

            <a href="<?= BASE_URL ?>/modules/settings/index.php">
                <i class="fa-solid fa-gear"></i>
                <span>System Settings</span>
            </a>

        <?php endif; ?>

        <!-- ======= -->
        <!-- Reports -->
        <!-- ======= -->

        <a href="<?= BASE_URL ?>/modules/reports/index.php">
            <i class="fa-solid fa-chart-column"></i>
            <span>Reports</span>
        </a>

        <!-- ======= -->
        <!-- Profile -->
        <!-- ======= -->

        <a href="<?= BASE_URL ?>/modules/profile/index.php">
            <i class="fa-solid fa-circle-user"></i>
            <span>Profile</span>
        </a>

        <!-- Logout -->

        <a href="<?= BASE_URL ?>/logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>
            <span>Logout</span>
        </a>

    </nav>

</aside>