<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}
?>

<?php require '../resources/views/includes/header.php'; ?>

<h1>HR1 Dashboard</h1>

<div class="dashboard-card">
    <h3>Welcome</h3>

    <p>
        Logged in as:
        <?= $_SESSION['username'] ?? 'User'; ?>
    </p>
</div>

<?php require '../resources/views/includes/footer.php'; ?>