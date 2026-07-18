<?php

namespace App\Middleware;

use App\Services\Auth;

class AuthMiddleware
{
    public static function handle()
    {
        if (!Auth::check()) {
            header("Location: /login");
            exit;
        }
    }
}