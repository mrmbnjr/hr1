<?php

session_start();

require_once __DIR__ . "/../server/config/database.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

$username = trim($_POST['username']);
$password = $_POST['password'];
$stmt = mysqli_prepare($conn, "SELECT id, username, password, role FROM users WHERE username = ?");

mysqli_stmt_bind_param($stmt, "s", $username);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

if ($user = mysqli_fetch_assoc($result)) {

    if (password_verify($password, $user['password'])) {

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header("Location: modules/dashboard/index.php");
        exit();

    } else {

        echo "<script>
            alert('Wrong Password');
            window.location='index.php';
        </script>";

    }

} else {

    echo "<script>
        alert('Username not found');
        window.location='index.php';
    </script>";

}

mysqli_stmt_close($stmt);
mysqli_close($conn);

?>