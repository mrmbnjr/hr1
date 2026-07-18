<?php

use App\Controllers\LoginController;
use App\Controllers\DashboardController;

return [

    'GET' => [
        '/login' => [LoginController::class, 'showLogin'],
        '/dashboard' => [DashboardController::class, 'index'],
    ],

    'POST' => [
        '/login' => [LoginController::class, 'login'],
    ]

];