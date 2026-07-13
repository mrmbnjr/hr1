<?php

$host="localhost";
$user="root";
$pass="";
$db="ramyum";
$conn = mysqli_connect($host,$user,$pass,$db);

    if(!$conn){
        die("Connection Failed");
    }

?>