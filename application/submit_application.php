<?php

include "db_connect.php";


// Get applicant information

$first_name = $_POST['first_name'];
$middle_name = $_POST['middle_name'];
$last_name = $_POST['last_name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$address = $_POST['address'];



// Insert applicant

$sql = "
INSERT INTO applicants
(
    first_name,
    middle_name,
    last_name,
    email,
    phone,
    address
)
VALUES
(
    ?,?,?,?,?,?
)
";


$stmt = $conn->prepare($sql);

$stmt->execute([
    $first_name,
    $middle_name,
    $last_name,
    $email,
    $phone,
    $address
]);



// Get generated applicant ID

$applicant_id = $conn->lastInsertId();



// Upload Resume

$resume_name = $_FILES['resume']['name'];

$resume_tmp = $_FILES['resume']['tmp_name'];


$upload_folder = "uploads/";

$resume_path = $upload_folder . $resume_name;


move_uploaded_file(
    $resume_tmp,
    $resume_path
);



// Success redirect

header("Location: Submit.html");

exit();

?>