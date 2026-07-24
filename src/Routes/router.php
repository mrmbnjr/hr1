<?php

use App\Controllers\LoginController;
use App\Controllers\DashboardController;

return [

    'GET' => [

        '/login' => [
            LoginController::class,
            'showLogin'
        ],


        '/dashboard' => [
            DashboardController::class,
            'index'
        ],


        '/dashboard-growth' => [
            DashboardController::class,
            'growthData'
        ]

    ],


    'POST' => [

        '/login' => [
            LoginController::class,
            'login'
        ]

    ]

];