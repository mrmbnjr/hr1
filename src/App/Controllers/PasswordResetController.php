<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Core\Database;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

/**
 * Self-service password reset.
 *
 * Backs the three options on resources/views/reset-password/index.php:
 *   1. Email code      → sendCode()  + verifyCode()
 *   2. Username        → resetByUsername()
 *   3. Contact HR      → requestHr()
 *
 * Every method is called from a fetch() POST and must answer with JSON of
 * the shape { success: bool, message: string } — the same contract the
 * view's JavaScript expects.
 */
class PasswordResetController
{
    private const CODE_LENGTH = 6;
    private const CODE_EXPIRY_MINUTES = 5;
    private const CODE_MAX_ATTEMPTS = 5;
    private const RESEND_COOLDOWN_SECONDS = 60;
    private const PASSWORD_MIN_LENGTH = 8;

    /** Session key remembering the last address a code was sent to. */
    private const SESSION_LAST_SENT = 'pwreset_last_sent';

    /**
     * Render the reset page.
     */
    public function show(): void
    {
        require BASE_PATH . '/resources/views/reset-password/index.php';
    }

    // -----------------------------------------------------------------
    // Option 1 — email verification code
    // -----------------------------------------------------------------

    /**
     * STEP 1: email a one-time reset code to the account's registered address.
     * Also serves the "Send a new code" button.
     */
    public function sendCode(): void
    {
        $this->requirePostOrFail();

        $email = trim((string) ($_POST['email'] ?? ''));

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->fail('Enter a valid email address.');
        }

        // Light anti-spam cooldown, tracked per browser session.
        $last = $_SESSION[self::SESSION_LAST_SENT] ?? null;

        if (is_array($last) && strcasecmp((string) ($last['email'] ?? ''), $email) === 0) {
            $remaining = self::RESEND_COOLDOWN_SECONDS - (time() - (int) ($last['at'] ?? 0));

            if ($remaining > 0) {
                $this->fail("Please wait {$remaining} seconds before requesting another code.");
            }
        }

        $user = $this->findUserByEmail($email);

        if (!$user) {
            $this->fail('We could not find an account registered with that email address.');
        }

        if ($user['status'] !== 'Active') {
            $this->fail('This account is inactive. Please contact HR.');
        }

        try {
            $code = $this->generateCode();

            $this->storeCode((int) $user['user_id'], $code);
            $this->sendCodeEmail($user['email'], $user['username'], $code);

            $_SESSION[self::SESSION_LAST_SENT] = ['email' => $email, 'at' => time()];

            $this->succeed(
                'A verification code has been sent to your email. It expires in '
                . self::CODE_EXPIRY_MINUTES . ' minutes.'
            );
        } catch (Throwable $e) {
            error_log('Password reset code error for ' . $email . ': ' . $e->getMessage());
            $this->fail('Unable to send the verification code. Please try again.');
        }
    }

    /**
     * STEP 2: verify the emailed code and set the new password.
     */
    public function verifyCode(): void
    {
        $this->requirePostOrFail();

        $email = trim((string) ($_POST['email'] ?? ''));
        $code = trim((string) ($_POST['code'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->fail('Your reset session is invalid. Please start again.');
        }

        if (!preg_match('/^\d{' . self::CODE_LENGTH . '}$/', $code)) {
            $this->fail('Enter the ' . self::CODE_LENGTH . '-digit verification code.');
        }

        $this->assertPassword($password, $confirm);

        $user = $this->findUserByEmail($email);

        if (!$user) {
            $this->fail('We could not find an account registered with that email address.');
        }

        $record = $this->findActiveCode((int) $user['user_id']);

        if (!$record) {
            $this->fail('The verification code is invalid or has expired. Please request a new one.');
        }

        if (strtotime((string) $record['expires_at']) <= time()) {
            $this->fail('The verification code has expired. Please request a new one.');
        }

        if ((int) $record['attempts'] >= self::CODE_MAX_ATTEMPTS) {
            $this->fail('Too many incorrect attempts. Please request a new code.');
        }

        if (!password_verify($code, (string) $record['code_hash'])) {
            $this->incrementAttempts((int) $record['reset_id']);
            $this->fail('Incorrect verification code.');
        }

        $this->markCodeUsed((int) $record['reset_id']);

        // Verifying the emailed code proves ownership of the address.
        $this->applyNewPassword((int) $user['user_id'], $password, markEmailVerified: true);

        unset($_SESSION[self::SESSION_LAST_SENT]);

        ActivityLogger::log('Reset password via email code', 'reset-password');

        $this->succeed('Your password has been reset. You can now sign in with your new password.');
    }

    // -----------------------------------------------------------------
    // Option 2 — username + current password
    // -----------------------------------------------------------------

    /**
     * Reset a password for a signed-out user who still knows the current one.
     */
    public function resetByUsername(): void
    {
        $this->requirePostOrFail();

        $username = trim((string) ($_POST['username'] ?? ''));
        $current = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $confirm = (string) ($_POST['password_confirm'] ?? '');

        if ($username === '' || $current === '') {
            $this->fail('Username and current password are required.');
        }

        $this->assertPassword($password, $confirm);

        $user = User::findByUsername($username);

        if (!$user || !password_verify($current, (string) $user['password'])) {
            $this->fail('Invalid username or current password.');
        }

        if ($user['status'] !== 'Active') {
            $this->fail('This account is inactive. Please contact HR.');
        }

        if (password_verify($password, (string) $user['password'])) {
            $this->fail('Choose a new password that is different from your current one.');
        }

        $this->applyNewPassword((int) $user['user_id'], $password);

        ActivityLogger::log('Reset password with current password', 'reset-password');

        $this->succeed('Your password has been reset. You can now sign in with your new password.');
    }

    // -----------------------------------------------------------------
    // Option 3 — ask HR to reset
    // -----------------------------------------------------------------

    /**
     * Record a manual reset request so HR can verify the person and act.
     */
    public function requestHr(): void
    {
        $this->requirePostOrFail();

        $fullName = trim((string) ($_POST['full_name'] ?? ''));
        $identifier = trim((string) ($_POST['identifier'] ?? ''));
        $contact = trim((string) ($_POST['contact'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));

        if ($fullName === '' || $identifier === '' || $contact === '') {
            $this->fail('Please complete your name, username / employee ID, and contact details.');
        }

        if (mb_strlen($fullName) > 150 || mb_strlen($identifier) > 150 || mb_strlen($contact) > 150) {
            $this->fail('One of the fields is too long.');
        }

        if (mb_strlen($message) > 2000) {
            $this->fail('Your message is too long (maximum 2000 characters).');
        }

        try {
            $db = Database::connection();
            $stmt = $db->prepare('
                INSERT INTO password_reset_requests (full_name, identifier, contact, message)
                VALUES (?, ?, ?, ?)
            ');

            $stmt->execute([
                $fullName,
                $identifier,
                $contact,
                $message !== '' ? $message : null,
            ]);

            ActivityLogger::log('Requested HR password reset', 'reset-password');
        } catch (Throwable $e) {
            error_log('HR reset request error: ' . $e->getMessage());
            $this->fail('Unable to send your request right now. Please try again.');
        }

        // Best-effort heads-up to HR; a mail failure must not lose the request.
        $this->notifyHr($fullName, $identifier, $contact, $message);

        $this->succeed('Your request has been sent to HR. They will verify your identity and reset your account.');
    }

    // -----------------------------------------------------------------
    // Password + code persistence
    // -----------------------------------------------------------------

    /**
     * Validate and persist a new password, clearing the first-login flag.
     */
    private function applyNewPassword(int $userId, string $password, bool $markEmailVerified = false): void
    {
        $db = Database::connection();

        $sql = 'UPDATE users
                SET password = :password, must_change_password = 0';

        if ($markEmailVerified) {
            $sql .= ', email_verified_at = NOW()';
        }

        $sql .= ' WHERE user_id = :user_id';

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':password' => password_hash($password, PASSWORD_DEFAULT),
            ':user_id'  => $userId,
        ]);
    }

    private function assertPassword(string $password, string $confirm): void
    {
        if (strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $this->fail('The new password must be at least ' . self::PASSWORD_MIN_LENGTH . ' characters long.');
        }

        if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
            $this->fail('The new password must contain at least one letter and one number.');
        }

        if ($password !== $confirm) {
            $this->fail('The new password and its confirmation do not match.');
        }
    }

    private function generateCode(): string
    {
        $max = (10 ** self::CODE_LENGTH) - 1;

        return str_pad((string) random_int(0, $max), self::CODE_LENGTH, '0', STR_PAD_LEFT);
    }

    private function storeCode(int $userId, string $code): void
    {
        $this->invalidateActiveCodes($userId);

        $db = Database::connection();
        $stmt = $db->prepare('
            INSERT INTO password_reset_codes (user_id, code_hash, attempts, expires_at)
            VALUES (?, ?, 0, DATE_ADD(NOW(), INTERVAL ' . self::CODE_EXPIRY_MINUTES . ' MINUTE))
        ');

        $stmt->execute([
            $userId,
            password_hash($code, PASSWORD_DEFAULT),
        ]);
    }

    private function findActiveCode(int $userId): array|false
    {
        $db = Database::connection();
        $stmt = $db->prepare('
            SELECT reset_id, user_id, code_hash, attempts, expires_at
            FROM password_reset_codes
            WHERE user_id = ? AND used_at IS NULL
            ORDER BY created_at DESC
            LIMIT 1
        ');

        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function incrementAttempts(int $resetId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE password_reset_codes SET attempts = attempts + 1 WHERE reset_id = ?');
        $stmt->execute([$resetId]);
    }

    private function markCodeUsed(int $resetId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE password_reset_codes SET used_at = NOW() WHERE reset_id = ?');
        $stmt->execute([$resetId]);
    }

    private function invalidateActiveCodes(int $userId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('
            UPDATE password_reset_codes
            SET used_at = NOW()
            WHERE user_id = ? AND used_at IS NULL
        ');
        $stmt->execute([$userId]);
    }

    /**
     * Look up an active account by its sign-in email (the column is UNIQUE).
     */
    private function findUserByEmail(string $email): array|false
    {
        $db = Database::connection();
        $stmt = $db->prepare('
            SELECT user_id, username, email, status
            FROM users
            WHERE email = ?
            LIMIT 1
        ');

        $stmt->execute([$email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // -----------------------------------------------------------------
    // Email
    // -----------------------------------------------------------------

    private function sendCodeEmail(string $email, string $username, string $code): void
    {
        $mail = $this->createMailer();
        $mail->addAddress($email, $username);

        $mail->Subject = 'RAM-YUM HRMS Password Reset Code';
        $mail->Body = $this->buildCodeEmailBody($username, $code);
        $mail->AltBody = "Your RAM-YUM HRMS password reset code is {$code}. This code expires in "
            . self::CODE_EXPIRY_MINUTES . ' minutes. If you did not request this, ignore this email.';

        $mail->send();
    }

    private function notifyHr(string $fullName, string $identifier, string $contact, string $message): void
    {
        $hrAddress = (string) ($_ENV['HR_NOTIFICATION_EMAIL'] ?? $_ENV['MAIL_FROM_ADDRESS'] ?? '');

        if ($hrAddress === '' || !filter_var($hrAddress, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        try {
            $mail = $this->createMailer();
            $mail->addAddress($hrAddress);

            $mail->Subject = 'Password reset request - ' . $fullName;
            $mail->Body = $this->buildHrEmailBody($fullName, $identifier, $contact, $message);
            $mail->AltBody = "Password reset request\n\nName: {$fullName}\nIdentifier: {$identifier}\n"
                . "Contact: {$contact}\n\nMessage:\n" . ($message !== '' ? $message : '(none)');

            $mail->send();
        } catch (Throwable $e) {
            // The request is already stored; a mail hiccup should stay silent.
            error_log('HR reset notification failed: ' . $e->getMessage());
        }
    }

    private function createMailer(): PHPMailer
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();

        $mail->Host = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USERNAME'];
        $mail->Password = preg_replace('/\s+/', '', (string) ($_ENV['MAIL_PASSWORD'] ?? ''));
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) ($_ENV['MAIL_PORT'] ?? 587);

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME'] ?? 'RAM-YUM HRMS');
        $mail->isHTML(true);

        return $mail;
    }

    private function buildCodeEmailBody(string $username, string $code): string
    {
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $expiryMinutes = self::CODE_EXPIRY_MINUTES;

        return <<<HTML
            <div style="font-family: Arial, sans-serif; line-height: 1.6; max-width: 560px; margin: auto;">
                <h2>RAM-YUM HRMS</h2>
                <p>Hello <strong>{$safeUsername}</strong>,</p>
                <p>Use the verification code below to reset your password:</p>
                <div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; margin: 25px 0;">
                    {$code}
                </div>
                <p>This code will expire in <strong>{$expiryMinutes} minutes</strong>.</p>
                <p>If you did not request a password reset, you can safely ignore this message.</p>
            </div>
            HTML;
    }

    private function buildHrEmailBody(string $fullName, string $identifier, string $contact, string $message): string
    {
        $name = htmlspecialchars($fullName, ENT_QUOTES, 'UTF-8');
        $id = htmlspecialchars($identifier, ENT_QUOTES, 'UTF-8');
        $contact = htmlspecialchars($contact, ENT_QUOTES, 'UTF-8');
        $note = htmlspecialchars($message !== '' ? $message : '(none)', ENT_QUOTES, 'UTF-8');

        return <<<HTML
            <div style="font-family: Arial, sans-serif; line-height: 1.6; max-width: 560px; margin: auto;">
                <h2>RAM-YUM HRMS — Password reset request</h2>
                <p>A user has asked HR to reset their password.</p>
                <ul>
                    <li><strong>Name:</strong> {$name}</li>
                    <li><strong>Username / employee ID:</strong> {$id}</li>
                    <li><strong>Contact:</strong> {$contact}</li>
                </ul>
                <p><strong>Message:</strong><br>{$note}</p>
            </div>
            HTML;
    }

    // -----------------------------------------------------------------
    // Request / response helpers
    // -----------------------------------------------------------------

    private function requirePostOrFail(): void
    {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->fail('Invalid request method.');
        }
    }

    private function fail(string $message): never
    {
        echo json_encode([
            'success' => false,
            'message' => $message,
        ]);

        exit;
    }

    private function succeed(string $message, array $extra = []): never
    {
        echo json_encode(array_merge([
            'success' => true,
            'message' => $message,
        ], $extra));

        exit;
    }
}
