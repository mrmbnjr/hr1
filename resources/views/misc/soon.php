<?php

$pageTitle       = "Coming Soon!";
$pageDescription = "This feature is currently under development. We're working hard to bring it to you soon.";

if (!isset($_SESSION['user_id'])) {
    header("Location: /hr1/public/?page=login");
    exit;
}
?>
<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>
<style>
    :root {
    --maroon: #6e1423;
    --gold: #d4af37;
    --cream: #f8f1e7;
    --bg: #f8f1e7;
    --card-bg: #ffffff;
    --ink: #2b2b2b;
}
.coming-soon-wrapper {
    min-height: 85.2vh;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    padding: 20px;
}

.coming-soon-card {
    background: var(--card-bg);
    border-top: 6px solid var(--maroon);
    border-radius: 12px;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
    padding: 48px 40px;
    text-align: center;
    max-width: 420px;
    width: 100%;
}

.coming-soon-icon {
    font-size: 48px;
    margin-bottom: 16px;
}

.coming-soon-card h1 {
    font-family: 'Baloo 2', sans-serif;
    color: var(--maroon);
    font-size: 28px;
    margin-bottom: 12px;
}

.coming-soon-card p {
    color: #555;
    font-size: 15px;
    line-height: 1.5;
    margin-bottom: 24px;
}

</style>
<div class="main-content">

    <?php require '../resources/views/includes/navbar.php'; ?>

    <div class="coming-soon-wrapper">
        <div class="coming-soon-card">
            <div class="coming-soon-icon">🚧</div>
            <h1>Coming Soon</h1>
            <p>This feature is currently under development. We're working hard to bring it to you soon.</p>
        </div>
    </div>
    <?php require '../resources/views/includes/footer.php'; ?>
</div>

<?php require '../resources/views/includes/scripts.php'?>