<?php

namespace App\Controllers;

use App\Services\Auth;

class LogoutController
{
    public function logout()
    {
        Auth::logout();

        header("Location: index.php?page=login");
        exit;
    }
}