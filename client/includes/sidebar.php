<aside class="sidebar">

    <div class="sidebar-header">
        <h2>RAM-YUM</h2>
        <p>Recruitment System</p>
    </div>

    <nav class="sidebar-menu">

        <a href="<?= BASE_URL ?>/modules/dashboard/index.php">
            Dashboard
        </a>

        <?php if ($_SESSION['role'] === 'admin'): ?>

            <a href="<?= BASE_URL ?>/modules/users/index.php">
                Users
            </a>

        <?php endif; ?>

        <?php if (in_array($_SESSION['role'], ['admin', 'hr'])): ?>

            <a href="<?= BASE_URL ?>/modules/applicants/index.php">
                Applicants
            </a>

        <?php endif; ?>

        <a href="<?= BASE_URL ?>/logout.php">
            Logout
        </a>

    </nav>

</aside>