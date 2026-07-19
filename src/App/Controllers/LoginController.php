<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\Auth;

class LoginController
{
    public function login()
    {
        header("Content-Type: application/json");

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode([
                "success" => false,
                "message" => "Invalid request method."
            ]);
            exit;
        }

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($username === '' || $password === '') {

            echo json_encode([
                "success" => false,
                "message" => "Username and Password are required."
            ]);
            exit;
        }

        $user = User::findByUsername($username);

        if (!$user) {

            echo json_encode([
                "success" => false,
                "message" => "Invalid username or password."
            ]);
            exit;
        }

        if ($user['status'] != 'Active') {

            echo json_encode([
                "success" => false,
                "message" => "Account is inactive."
            ]);
            exit;
        }

        if (!password_verify($password, $user['password'])) {

            echo json_encode([
                "success" => false,
                "message" => "Invalid username or password."
            ]);
            exit;
        }

        // Create login session
        Auth::login($user);

        echo json_encode([
            "success" => true,
            "message" => "Login successful."
        ]);

        exit;
    }


    public function logout()
    {
        Auth::logout();

        header("Location: /hr1/public/?page=login");
        exit;
    }
}