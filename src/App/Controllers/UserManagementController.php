<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Profile;
use App\Services\Auth;
use PDOException;
use RuntimeException;

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

    public function editProfile(): void
    {
        Auth::requireRole(['ADMIN']);

        $userId = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$userId || $userId < 1) {
            $this->flash('error', 'Select a valid user profile to edit.');
            header('Location: ' . url('?page=user-management'));
            exit;
        }

        $profile = (new Profile())->getProfile((int) $userId);
        if ($profile === null || empty($profile['applicant_id'])) {
            $this->flash('error', 'This user is not linked to an employee profile.');
            header('Location: ' . url('?page=user-management'));
            exit;
        }

        $user = [
            'id' => (int) $profile['user_id'],
            'badge_id' => $profile['employee_number'] ?? '',
            'first_name' => $profile['first_name'] ?? '',
            'last_name' => $profile['last_name'] ?? '',
            'email' => $profile['personal_email'] ?? '',
            'phone' => $profile['phone'] ?? '',
            'address' => $profile['address'] ?? '',
        ];

        require BASE_PATH . '/resources/views/employee-records/edit-profile.php';
    }

    public function updateProfile(): void
    {
        Auth::requireRole(['ADMIN']);

        $userId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$userId || $userId < 1) {
            $this->jsonResponse(422, ['success' => false, 'message' => 'Select a valid user profile.']);
        }

        try {
            $profiles = new Profile();
            $profile = $profiles->getProfile((int) $userId);
            if ($profile === null || empty($profile['applicant_id'])) {
                $this->jsonResponse(404, ['success' => false, 'message' => 'This user is not linked to an employee profile.']);
            }

            $firstName = trim((string) ($_POST['first_name'] ?? ''));
            $lastName = trim((string) ($_POST['last_name'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $phone = trim((string) ($_POST['phone'] ?? ''));
            $address = trim((string) ($_POST['address'] ?? ''));
            $applicantId = (int) $profile['applicant_id'];

            if ($firstName === '' || mb_strlen($firstName) > 100) {
                $this->jsonResponse(422, ['success' => false, 'message' => 'Enter a first name of no more than 100 characters.', 'errors' => ['first_name' => 'Enter a valid first name.']]);
            }
            if ($lastName === '' || mb_strlen($lastName) > 100) {
                $this->jsonResponse(422, ['success' => false, 'message' => 'Enter a last name of no more than 100 characters.', 'errors' => ['last_name' => 'Enter a valid last name.']]);
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
                $this->jsonResponse(422, ['success' => false, 'message' => 'Enter a valid contact email address.', 'errors' => ['email' => 'Enter a valid email address.']]);
            }
            if (!preg_match('/^[0-9+()\\-\\s]{7,30}$/', $phone)) {
                $this->jsonResponse(422, ['success' => false, 'message' => 'Enter a valid phone number.', 'errors' => ['phone' => 'Enter a valid phone number.']]);
            }
            if (mb_strlen($address) > 500) {
                $this->jsonResponse(422, ['success' => false, 'message' => 'The address is too long (maximum 500 characters).']);
            }
            if ($profiles->personalEmailExists($email, $applicantId)) {
                $this->jsonResponse(422, ['success' => false, 'message' => 'That contact email is already used by another record.', 'errors' => ['email' => 'That email is already in use.']]);
            }
            if ($profiles->phoneExists($phone, $applicantId)) {
                $this->jsonResponse(422, ['success' => false, 'message' => 'That phone number is already used by another record.', 'errors' => ['phone' => 'That phone number is already in use.']]);
            }

            if (!$profiles->updatePersonalInfo($applicantId, [
                'first_name' => $firstName,
                'middle_name' => $profile['middle_name'] ?? null,
                'last_name' => $lastName,
                'email' => $email,
                'phone' => $phone,
                'address' => $address !== '' ? $address : null,
            ])) {
                throw new RuntimeException('The profile update did not complete.');
            }
        } catch (PDOException $exception) {
            error_log('User profile update failed: ' . $exception->getMessage());
            $this->jsonResponse(500, ['success' => false, 'message' => 'The profile could not be saved. Please try again.']);
        } catch (RuntimeException $exception) {
            $this->jsonResponse(500, ['success' => false, 'message' => $exception->getMessage()]);
        }

        $this->jsonResponse(200, [
            'success' => true,
            'message' => 'Profile updated successfully.',
            'redirect' => url('?page=user-management'),
        ]);
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

    private function jsonResponse(int $status, array $payload): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
        exit;
    }
}
