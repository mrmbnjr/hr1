<?php

session_start();

if(!isset($_SESSION['username'])){

    header("Location:index.php");

    exit();

}

?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>
</head>

<body>

<h1>Welcome <?php echo $_SESSION['username']; ?></h1>

<a href="logout.php">Logout</a>

</body>
</html>