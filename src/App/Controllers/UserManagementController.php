<?php

namespace App\Controllers;

use App\Models\User;
use App\Services\Auth;
use PDOException;

class UserManagementController
{
    private User $users;

    public function __construct()
    {
        $this->users = new User();
    }

    public function index(): void
    {
        Auth::requireRole(['ADMIN']);

        $users = $this->users->getManagementData();
        $roles = $this->users->getRoles();
        $departments = $this->users->getDepartments();

        require BASE_PATH . '/resources/views/user-management/index.php';
    }

    public function action(): void
    {
        Auth::requireRole(['ADMIN']);

        $action = $_POST['action'] ?? '';
        $users = $this->users->getManagementData();

        try {
            if ($action === 'update') {
                $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
                $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);
                $username = trim($_POST['username'] ?? '');
                $status = $_POST['status'] ?? '';

                if (!$userId || !$roleId || $username === '' || !in_array($status, ['Active', 'Inactive'], true)) {
                    throw new \InvalidArgumentException('Enter a valid username, role, and account status.');
                }

                foreach ($users as $user) {
                    if ((int) $user['user_id'] !== $userId && strcasecmp($user['username'], $username) === 0) {
                        throw new \InvalidArgumentException('That username is already in use.');
                    }
                }

                $this->users->updateAccount($userId, $username, $roleId, $status);
                $this->flash('success', 'User account updated.');
            } elseif ($action === 'toggle-status') {
                $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
                $status = $_POST['status'] ?? '';

                if (!$userId || !in_array($status, ['Active', 'Inactive'], true)) {
                    throw new \InvalidArgumentException('Invalid account status request.');
                }

                if ((int) $userId === (int) Auth::userId()) {
                    throw new \InvalidArgumentException('You cannot disable your own account.');
                }

                $this->users->setStatus($userId, $status);
                $this->flash('success', $status === 'Active' ? 'Account enabled.' : 'Account disabled.');
            } else {
                throw new \InvalidArgumentException('Unknown user-management action.');
            }
        } catch (PDOException $exception) {
            $this->flash('error', 'The account could not be saved. Check for duplicate values and try again.');
        } catch (\Throwable $exception) {
            $this->flash('error', $exception->getMessage());
        }

        header('Location: ' . url('?page=user-management'));
        exit;
    }

    private function flash(string $type, string $message): void
    {
        $_SESSION['user_management_flash'] = [
            'type' => $type,
            'message' => $message,
        ];
    }
}
