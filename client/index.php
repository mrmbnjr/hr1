<?php

require_once "../server/bootstrap.php";

$pageTitle = "RAM-YUM Login";

$pageStyles = [
    "login.css"
];

include "includes/header.php";

?>

<div class="login-page">

    <div class="login-container">

        <img src="<?= BASE_URL ?>/assets/images/logo.png" class="logo">

        <h1>RAM-YUM Store</h1>

        <h2>LOGIN</h2>

        <form action="<?= BASE_URL ?>/login.php" method="POST">

            <label>Username</label>

            <input
                type="text"
                name="username"
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

</div>

<script src="<?= BASE_URL ?>/assets/js/script.js"></script>

<?php include "includes/footer.php"; ?>