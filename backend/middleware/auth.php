<?php

if (!isset($_SESSION['user_id'])) {

    header("Location: /client/index.php");
    exit();

}