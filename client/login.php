<?php

session_start();

include __DIR__ . "/../server/config/database.php";

$username=$_POST['username'];
$password=$_POST['password'];

$sql="SELECT * FROM users WHERE username='$username'";

$result=mysqli_query($conn,$sql);

if (mysqli_num_rows($result)>0) {

    $user=mysqli_fetch_assoc($result);
    if(password_verify($password,$user['password'])){
        $_SESSION['username']=$username;
        header("Location: dashboard.php");
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

?>