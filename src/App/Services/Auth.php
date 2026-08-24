<?php

namespace App\Services;

class Auth
{
    /**
     * Log the user in.
     */
    public static function login(array $user): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        /*
         * Regenerate the session ID after login.
         */
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role_id'] = $user['role_id'];
        $_SESSION['role_code'] = $user['role_code'] ?? null;
        $_SESSION['role_name'] = $user['role_name'] ?? null;
    }


    /**
     * Log the user out.
     */
    public static function logout(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }


    /**
     * Check whether the user is logged in.
     */
    public static function check(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['user_id']);
    }


    /**
     * Get the logged-in user's ID.
     */
    public static function userId()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['user_id'] ?? null;
    }


    /**
     * Get the logged-in user's username.
     */
    public static function username(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['username'] ?? null;
    }


    /**
     * Get the logged-in user's role ID.
     */
    public static function roleId()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['role_id'] ?? null;
    }


    /**
     * Get the logged-in user's role code.
     *
     * ADMIN
     * HR
     * MGR
     * EMP
     */
    public static function role(): ?string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $_SESSION['role_code'] ?? null;
    }


    /**
     * Check for one exact role.
     */
    public static function hasRole(string $role): bool
    {
        return self::role() === $role;
    }


    /**
     * Check whether the user has one of several roles.
     */
    public static function hasAnyRole(array $roles): bool
    {
        return in_array(
            self::role(),
            $roles,
            true
        );
    }


    /**
     * Require the user to be logged in.
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {

            header(
                "Location: /hr1/public/?page=login"
            );

            exit;
        }
    }


    /**
     * Require one of the specified roles.
     */
    public static function requireRole(array $roles): void
    {
        self::requireLogin();

        /*
         * ADMIN always has full access.
         */
        if (self::hasRole('ADMIN')) {
            return;
        }

        /*
         * Check whether current role is allowed.
         */
        if (!self::hasAnyRole($roles)) {

            http_response_code(403);

            exit('Access denied.');
        }
    }
}