<?php

require_once "../../../server/bootstrap.php";
require_once "../../../server/middleware/auth.php";
require_once "../../../server/middleware/role.php";

requireRole([
    'admin',
    'hr',
    'manager',
    'cashier',
    'warehouse',
    'accountant'
]);

$pageTitle = "Dashboard";

$pageStyles = [
    "layout.css",
    "dashboard.css"
];

include "../../includes/header.php";

?>

<div class="layout">

    <?php include "../../includes/sidebar.php"; ?>

    <main class="main-content">

        <?php include "../../includes/navbar.php"; ?>

        <h1>Welcome <?= htmlspecialchars($_SESSION['username']) ?></h1>

    </main>

</div>

<?php include "../../includes/footer.php"; ?>