<?php

session_start();

require_once __DIR__ . "/../server/config/database.php";

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit();
}

// Get form data
$username = trim($_POST['username']);
$password = $_POST['password'];

// SQL Query
$sql = "
SELECT
    u.user_id,
    u.username,
    u.password,
    u.account_status,

    e.employee_id,
    e.first_name,
    e.last_name,

    r.role_code,
    r.role_name

FROM users u
INNER JOIN employees e
    ON u.employee_id = e.employee_id
INNER JOIN roles r
    ON u.role_id = r.role_id

WHERE u.username = ?
LIMIT 1
";

// Prepare statement
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    die("Database error: " . mysqli_error($conn));
}

// Bind parameter
mysqli_stmt_bind_param($stmt, "s", $username);

// Execute
mysqli_stmt_execute($stmt);

// Get result
$result = mysqli_stmt_get_result($stmt);

// Check if user exists
if ($user = mysqli_fetch_assoc($result)) {

    // Check account status
    if ($user['account_status'] !== 'Active') {

        echo "<script>
                alert('Your account has been disabled.');
                window.location='index.php';
              </script>";

        exit();
    }

    // Verify password
    if (password_verify($password, $user['password'])) {

        // Regenerate session ID for security
        session_regenerate_id(true);

        // Store session
        $_SESSION['user_id']      = $user['user_id'];
        $_SESSION['employee_id']  = $user['employee_id'];

        $_SESSION['username']     = $user['username'];

        $_SESSION['name']         =
            $user['first_name'] . " " . $user['last_name'];

        // Use role_code for middleware
        $_SESSION['role']         = $user['role_code'];

        // Use role_name for display
        $_SESSION['role_name']    = $user['role_name'];

        header("Location: modules/dashboard/index.php");
        exit();

    } else {

        echo "<script>
                alert('Incorrect password.');
                window.location='index.php';
              </script>";

        exit();
    }

} else {

    echo "<script>
            alert('Username not found.');
            window.location='index.php';
          </script>";

    exit();
}

// Close resources
mysqli_stmt_close($stmt);
mysqli_close($conn);

?>