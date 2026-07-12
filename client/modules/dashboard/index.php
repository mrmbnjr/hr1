<?php

require_once "../../../server/bootstrap.php";
require_once "../../../server/middleware/auth.php";
require_once "../../../server/middleware/role.php";

requireRole(['admin', 'hr', 'manager', 'cashier', 'warehouse', 'accountant']);

?>

<!DOCTYPE html>
<html>
    <head>
        <title>Dashboard</title>
    </head>

    <body>

    <h1>Welcome <?php echo $_SESSION['username']; ?></h1>

    <a href="../../logout.php">Logout</a>

    </body>
</html>