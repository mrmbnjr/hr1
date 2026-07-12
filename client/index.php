<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RAM-YUM Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Luckiest+Guy&display=swap" rel="stylesheet">
</head>

<body>

<div class="login-page">
    <div class="login-container">

        <img src="images/logo.png" class="logo">
        <h1>RAM-YUM Store</h1>
        <h2>LOGIN</h2>

        <form action="login.php" method="POST">

            <label>Username</label>
                <input
                type="text"
                name="username"
                placeholder=""
                required>
            <label>Password</label>

            <div class="password-box">
                <input
                type="password"
                id="password"
                name="password"
                required>
                <span id="togglePassword">👁</span>
            </div>

            <button type="submit">
                LOGIN
            </button>

        </form>
    </div>
    <footer></footer>
</div>

<script src="assets/js/script.js"></script>

</body>
</html>