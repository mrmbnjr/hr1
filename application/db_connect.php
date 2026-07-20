<?php

$host = "localhost";
$user = "root";
$password = "";
$database = "hr1_db";


try {

    $conn = new PDO(
        "mysql:host=$host;dbname=$database",
        $user,
        $password
    );

    // Enable error reporting for database errors
    $conn->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

}

catch(PDOException $e){

    die("Connection Failed: " . $e->getMessage());

}

?>