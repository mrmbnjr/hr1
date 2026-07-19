<?php
$pageTitle = "Onboarding";
$pageCSS = "onboarding.css";
$pageDescription = "Complete your onboarding process for your RAM-YUM Store account — Korean and Japanese Store.";

    if (!isset($_SESSION['user_id'])) {
        header("Location: /hr1/public/?page=login");
        exit;
    }

?>

<?php require '../resources/views/includes/header.php'; ?>
<?php require '../resources/views/includes/sidebar.php'; ?>
<div class="main-content">
    <?php require '../resources/views/includes/navbar.php'; ?>


</div>
<?php require '../resources/views/includes/footer.php'; ?>