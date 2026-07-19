<?php

use App\Controllers\LoginController;
use App\Controllers\DashboardController;
use App\Controllers\LogoutController;
use App\Controllers\RecruitmentController;
use App\Controllers\EmployeeController;
use App\Controllers\AdminController;
use App\Controllers\ProfileController;

session_start();

require '../vendor/autoload.php';

$page = $_GET['page'] ?? 'login';
$uri = $_SERVER['REQUEST_URI'];

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
    case 'applicants':
        (new RecruitmentController())->applicants();
        break;
    case 'recruitment':
        (new RecruitmentController())->recruitment();
        break;
    case 'onboarding':
        (new RecruitmentController())->onboarding();
        break;
    case 'logout':
        (new LogoutController())->logout();
        break;

    default:
        require '../resources/views/auth/login.php';
        break;
}