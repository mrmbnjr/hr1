<?php

use App\Controllers\LoginController;
use App\Controllers\DashboardController;

session_start();

require '../vendor/autoload.php';

$page = $_GET['page'] ?? 'login';

switch ($page) {

    case 'login':

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            (new LoginController())->login();
        } else {
            require '../resources/views/auth/login.php';
        }

        break;

    case 'dashboard':
        (new DashboardController())->index();
        break;

    default:
        require '../resources/views/auth/login.php';
        break;
}