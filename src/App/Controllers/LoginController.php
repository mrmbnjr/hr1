<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\Auth;

class LoginController
{
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: /hr1/public/?page=login");
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {

            $_SESSION['error'] = "Username and Password are required.";

            header("Location: /hr1/public/?page=login");
            exit;
        }

        $user = User::findByUsername($username);

        if (!$user) {

            $_SESSION['error'] = "Invalid username or password.";

            header("Location: /hr1/public/?page=login");
            exit;
        }

        if ($user['status'] != 'Active') {

            $_SESSION['error'] = "Account is inactive.";

            header("Location: /hr1/public/?page=login");
            exit;
        }

        if (!password_verify($password, $user['password'])) {

            $_SESSION['error'] = "Invalid username or password.";

            header("Location: /hr1/public/?page=login");
            exit;
        }

        Auth::login($user);

        header("Location: /hr1/public/?page=dashboard");
        exit;
    }

    public function logout()
    {
        Auth::logout();

        header("Location: /hr1/public/?page=login");
        exit;
    }
}