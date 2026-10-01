<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Profile;
use App\Models\User;
use App\Services\Auth;
use PDOException;
use RuntimeException;
use Throwable;

/**
 * The signed-in user's own profile.
 *
 * Scope: every authenticated role (ADMIN, HR, MGR, EMP) manages their *own*
 * profile here — there is no cross-user editing, so no role gate beyond
 * "must be logged in" is needed.
 *
 * The page is fully functional, but every write targets columns that already
 * exist — no schema changes are required or performed.
 */
class ProfileController
{
    private const TAB_PERSONAL   = 'personal';
    private const TAB_EMPLOYMENT = 'employment';
    private const TAB_ACCOUNT    = 'account';
    private const TAB_ACTIVITY   = 'activity';

    private const PASSWORD_MIN_LENGTH = 8;

    private Profile $profile;

    public function __construct()
    {
        $this->profile = new Profile();
    }

    /**
     * Render the profile page for the current user.
     */
    public function index(): void
    {
        $this->render();
    }

    /**
     * Landing target for the first-login "change your password" flow.
     *
     * AuthController redirects here (?page=change-password) after a user with
     * `must_change_password = 1` signs in. We reuse the profile page and open
     * it straight on the Account & Security tab instead of duplicating a form.
     */
    public function changePassword(): void
    {
        $this->render(self::TAB_ACCOUNT);
    }

    /**
     * Shared renderer. `$forcedTab` wins over the query string / flash tab.
     */
    private function render(?string $forcedTab = null): void
    {
        Auth::requireLogin();

        $userId = (int) Auth::userId();
        $profile = $this->profile->getProfile($userId);

        if ($profile === null) {
            http_response_code(404);
            require BASE_PATH . '/resources/views/misc/soon.php';
            return;
        }

        $activity = $this->profile->getRecentActivity($userId, 12);
        $activitySummary = $this->profile->getActivitySummary($userId);

        require BASE_PATH . '/resources/views/profile/index.php';
    }

    /**
     * Handle every POST from the profile page, then redirect (PRG).
     */
    public function update(): void
    {
        Auth::requireLogin();

        $userId = (int) Auth::userId();
        $action = (string) ($_POST['action'] ?? '');

        try {
            match ($action) {
                'personal' => $this->updatePersonal($userId),
                'account'  => $this->updateAccount($userId),
                'password' => $this->updatePassword($userId),
                default    => throw new RuntimeException('Unknown profile action.'),
            };
        } catch (PDOException $exception) {
            error_log('Profile update failed: ' . $exception->getMessage());
            $this->flash('error', 'The change could not be saved. Please check the values and try again.', $this->tabForAction($action));
        } catch (Throwable $exception) {
            $this->flash('error', $exception->getMessage(), $this->tabForAction($action));
        }

        header('Location: ' . url('?page=profile&tab=') . $this->tabForAction($action));
        exit;
    }

    // -----------------------------------------------------------------
    // Actions
    // -----------------------------------------------------------------

    private function updatePersonal(int $userId): void
    {
        $profile = $this->profile->getProfile($userId);
        $applicantId = (int) ($profile['applicant_id'] ?? 0);

        if ($applicantId <= 0) {
            throw new RuntimeException('This account is not linked to an employee record, so personal details cannot be edited here.');
        }

        $firstName  = trim((string) ($_POST['first_name'] ?? ''));
        $middleName = trim((string) ($_POST['middle_name'] ?? ''));
        $lastName   = trim((string) ($_POST['last_name'] ?? ''));
        $email      = trim((string) ($_POST['personal_email'] ?? ''));
        $phone      = trim((string) ($_POST['phone'] ?? ''));
        $address    = trim((string) ($_POST['address'] ?? ''));

        $this->assertName($firstName, 'First name');
        $this->assertName($middleName, 'Middle name', required: false);
        $this->assertName($lastName, 'Last name');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            throw new RuntimeException('Enter a valid contact email address.');
        }
        if ($this->profile->personalEmailExists($email, $applicantId)) {
            throw new RuntimeException('That contact email is already used by another record.');
        }

        if (!preg_match('/^[0-9+()\-\s]{7,20}$/', $phone)) {
            throw new RuntimeException('Enter a valid phone number (7–20 digits, spaces, +, - or parentheses).');
        }
        if ($this->profile->phoneExists($phone, $applicantId)) {
            throw new RuntimeException('That phone number is already used by another record.');
        }

        if (mb_strlen($address) > 500) {
            throw new RuntimeException('The address is too long (maximum 500 characters).');
        }

        $this->profile->updatePersonalInfo($applicantId, [
            'first_name'  => $firstName,
            'middle_name' => $middleName !== '' ? $middleName : null,
            'last_name'   => $lastName,
            'email'       => $email,
            'phone'       => $phone,
            'address'     => $address !== '' ? $address : null,
        ]);

        $this->flash('success', 'Personal information updated.', self::TAB_PERSONAL);
    }

    private function updateAccount(int $userId): void
    {
        $profile = $this->profile->getProfile($userId);

        $username = trim((string) ($_POST['username'] ?? ''));
        $email    = trim((string) ($_POST['account_email'] ?? ''));

        if (!preg_match('/^[A-Za-z0-9._-]{3,50}$/', $username)) {
            throw new RuntimeException('Username must be 3–50 characters: letters, numbers, dots, dashes, or underscores.');
        }
        if ($this->profile->usernameExists($username, $userId)) {
            throw new RuntimeException('That username is already in use.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) {
            throw new RuntimeException('Enter a valid sign-in email address.');
        }
        if ($this->profile->accountEmailExists($email, $userId)) {
            throw new RuntimeException('That email address is already in use.');
        }

        $emailChanged = strcasecmp($email, (string) ($profile['account_email'] ?? '')) !== 0;

        $this->profile->updateAccount($userId, $username, $email, $emailChanged);

        // Keep the live session in step with the new identity.
        $_SESSION['username'] = $username;

        $message = 'Account details updated.';
        if ($emailChanged) {
            $message .= ' Your new email must be verified before the next sign-in code is trusted.';
        }

        $this->flash('success', $message, self::TAB_ACCOUNT);
    }

    private function updatePassword(int $userId): void
    {
        $current = (string) ($_POST['current_password'] ?? '');
        $new     = (string) ($_POST['new_password'] ?? '');
        $confirm = (string) ($_POST['confirm_password'] ?? '');

        $user = User::findById($userId);

        if ($user === null || !password_verify($current, (string) $user['password'])) {
            throw new RuntimeException('Your current password is incorrect.');
        }

        if (strlen($new) < self::PASSWORD_MIN_LENGTH) {
            throw new RuntimeException('The new password must be at least ' . self::PASSWORD_MIN_LENGTH . ' characters long.');
        }
        if (!preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            throw new RuntimeException('The new password must contain at least one letter and one number.');
        }
        if ($new !== $confirm) {
            throw new RuntimeException('The new password and its confirmation do not match.');
        }
        if ($new === $current) {
            throw new RuntimeException('Choose a new password that is different from your current one.');
        }

        $this->profile->updatePassword($userId, password_hash($new, PASSWORD_DEFAULT));

        $this->flash('success', 'Your password has been changed.', self::TAB_ACCOUNT);
    }

    // -----------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------

    private function assertName(string $value, string $label, bool $required = true): void
    {
        if ($required && $value === '') {
            throw new RuntimeException($label . ' is required.');
        }
        if (mb_strlen($value) > 100) {
            throw new RuntimeException($label . ' is too long (maximum 100 characters).');
        }
    }

    private function tabForAction(string $action): string
    {
        return match ($action) {
            'personal' => self::TAB_PERSONAL,
            'account', 'password' => self::TAB_ACCOUNT,
            default => self::TAB_PERSONAL,
        };
    }

    private function flash(string $type, string $message, string $tab): void
    {
        $_SESSION['profile_flash'] = [
            'type'    => $type,
            'message' => $message,
            'tab'     => $tab,
        ];
    }
}
