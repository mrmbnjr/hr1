<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\Auth;
use Core\Database;
use PDO;
use PHPMailer\PHPMailer\PHPMailer;
use Throwable;

/**
 * Handles the full authentication lifecycle:
 * username/password login, OTP issuance and verification,
 * OTP resend, and logout.
 */
class AuthController
{
    private const OTP_LENGTH = 6;
    private const OTP_EXPIRY_MINUTES = 5;
    private const OTP_MAX_ATTEMPTS = 5;
    private const OTP_RESEND_COOLDOWN_SECONDS = 60;

    private const SESSION_PENDING_USER_ID = 'pending_otp_user_id';
    private const SESSION_PENDING_EMAIL = 'pending_otp_email';
    private const SESSION_OTP_SENT_AT = 'otp_sent_at';

    private const REDIRECT_VERIFY_OTP = '?page=verify-otp';
    private const REDIRECT_DASHBOARD = '?page=dashboard';
    private const REDIRECT_CHANGE_PASSWORD = '?page=change-password';
    private const REDIRECT_LOGIN = '?page=login';

    /**
     * Render the login page.
     */
    public function showLogin(): void
    {
        require BASE_PATH . '/resources/views/auth/login.php';
    }

    /**
     * Render the OTP verification page.
     */
    public function showVerifyOtp(): void
    {
        require BASE_PATH . '/resources/views/auth/login.php';
    }

    /**
     * STEP 1: Validate credentials and dispatch an OTP by email.
     * Does not establish a session; that only happens after verifyOtp().
     */
    public function login(): void
    {
        $this->requirePostOrFail();

        $username = trim($_POST['username'] ?? '');
        $password = (string) ($_POST['password'] ?? '');

        if ($username === '' || $password === '') {
            $this->fail('Username and Password are required.');
        }

        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            $this->fail('Invalid username or password.');
        }

        if ($user['status'] !== 'Active') {
            $this->fail('Account is inactive.');
        }

        if (empty($user['email'])) {
            $this->fail('This account does not have a registered email address. Please contact an administrator.');
        }

        try {
            $otp = $this->generateOtp();

            $this->storeOtp((int) $user['user_id'], $otp);
            $this->sendOtpEmail($user['email'], $user['username'], $otp);

            $this->startPendingOtpSession((int) $user['user_id'], $user['email']);

            $this->succeed('A verification code has been sent to your registered email.', [
                'redirect' => url(self::REDIRECT_VERIFY_OTP),
            ]);
        } catch (Throwable $e) {
            error_log("OTP login error for username {$username}: {$e->getMessage()}");
            $this->fail('Unable to send the verification code. Please try again.');
        }
    }

    /**
     * STEP 2: Verify the submitted OTP and, on success, establish the
     * authenticated session.
     */
    public function verifyOtp(): void
    {
        $this->requirePostOrFail();

        $userId = $_SESSION[self::SESSION_PENDING_USER_ID] ?? null;
        $otp = trim($_POST['otp'] ?? '');

        if (!$userId) {
            $this->fail('Your login session has expired. Please log in again.');
        }

        if (!preg_match('/^\d{' . self::OTP_LENGTH . '}$/', $otp)) {
            $this->fail('Please enter a valid ' . self::OTP_LENGTH . '-digit verification code.');
        }

        $otpRecord = $this->findActiveOtp((int) $userId);

        if (!$otpRecord) {
            $this->fail('The verification code is invalid or has expired.');
        }

        if (strtotime($otpRecord['expires_at']) <= time()) {
            $this->fail('The verification code has expired. Please request a new code.');
        }

        if ((int) $otpRecord['attempts'] >= self::OTP_MAX_ATTEMPTS) {
            $this->fail('Too many incorrect attempts. Please request a new code.');
        }

        if (!password_verify($otp, $otpRecord['otp_hash'])) {
            $this->incrementOtpAttempts((int) $otpRecord['otp_id']);
            $this->fail('Incorrect verification code.');
        }

        $this->markOtpUsed((int) $otpRecord['otp_id']);

        $user = User::findById((int) $userId);

        if (!$user || $user['status'] !== 'Active') {
            $this->clearPendingOtpSession();
            $this->fail($user ? 'Account is inactive.' : 'User account could not be found.');
        }

        session_regenerate_id(true);
        Auth::login($user);
        ActivityLogger::log('Logged in with OTP', 'login');

        $this->clearPendingOtpSession();

        $redirect = empty($user['must_change_password'])
            ? self::REDIRECT_DASHBOARD
            : self::REDIRECT_CHANGE_PASSWORD;

        $this->succeed('Login successful.', ['redirect' => url($redirect)]);
    }

    /**
     * STEP 3: Invalidate any outstanding OTP and send a new one,
     * subject to a resend cooldown.
     */
    public function resendOtp(): void
    {
        $this->requirePostOrFail();

        $userId = $_SESSION[self::SESSION_PENDING_USER_ID] ?? null;

        if (!$userId) {
            $this->fail('Your login session has expired. Please log in again.');
        }

        $secondsRemaining = self::OTP_RESEND_COOLDOWN_SECONDS
            - (time() - (int) ($_SESSION[self::SESSION_OTP_SENT_AT] ?? 0));

        if ($secondsRemaining > 0) {
            $this->fail("Please wait {$secondsRemaining} seconds before requesting a new code.");
        }

        $user = User::findById((int) $userId);

        if (!$user || $user['status'] !== 'Active') {
            $this->clearPendingOtpSession();
            $this->fail('Unable to process the request.');
        }

        if (empty($user['email'])) {
            $this->fail('This account does not have a registered email address.');
        }

        try {
            $otp = $this->generateOtp();

            $this->invalidateActiveOtps((int) $userId);
            $this->storeOtp((int) $userId, $otp);
            $this->sendOtpEmail($user['email'], $user['username'], $otp);

            $_SESSION[self::SESSION_OTP_SENT_AT] = time();

            $this->succeed('A new verification code has been sent.');
        } catch (Throwable $e) {
            error_log("OTP resend error for user {$userId}: {$e->getMessage()}");
            $this->fail('Unable to send a new verification code.');
        }
    }

    /**
     * STEP 4: Terminate the authenticated session.
     */
    public function logout(): void
    {
        ActivityLogger::log('Logged out', 'logout');
        Auth::logout();

        header('Location: ' . url(self::REDIRECT_LOGIN));
        exit;
    }

    // -----------------------------------------------------------------
    // OTP persistence
    // -----------------------------------------------------------------

    private function generateOtp(): string
    {
        $max = (10 ** self::OTP_LENGTH) - 1;

        return str_pad((string) random_int(0, $max), self::OTP_LENGTH, '0', STR_PAD_LEFT);
    }

    private function storeOtp(int $userId, string $otp): void
    {
        $this->invalidateActiveOtps($userId);

        $db = Database::connection();
        $stmt = $db->prepare('
            INSERT INTO login_otps (user_id, otp_hash, attempts, expires_at)
            VALUES (?, ?, 0, DATE_ADD(NOW(), INTERVAL ' . self::OTP_EXPIRY_MINUTES . ' MINUTE))
        ');

        $stmt->execute([
            $userId,
            password_hash($otp, PASSWORD_DEFAULT),
        ]);
    }

    private function findActiveOtp(int $userId): array|false
    {
        $db = Database::connection();
        $stmt = $db->prepare('
            SELECT otp_id, user_id, otp_hash, attempts, expires_at
            FROM login_otps
            WHERE user_id = ? AND used_at IS NULL
            ORDER BY created_at DESC
            LIMIT 1
        ');

        $stmt->execute([$userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function incrementOtpAttempts(int $otpId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE login_otps SET attempts = attempts + 1 WHERE otp_id = ?');
        $stmt->execute([$otpId]);
    }

    private function markOtpUsed(int $otpId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE login_otps SET used_at = NOW() WHERE otp_id = ?');
        $stmt->execute([$otpId]);
    }

    private function invalidateActiveOtps(int $userId): void
    {
        $db = Database::connection();
        $stmt = $db->prepare('UPDATE login_otps SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL');
        $stmt->execute([$userId]);
    }

    // -----------------------------------------------------------------
    // Session helpers
    // -----------------------------------------------------------------

    private function startPendingOtpSession(int $userId, string $email): void
    {
        $_SESSION[self::SESSION_PENDING_USER_ID] = $userId;
        $_SESSION[self::SESSION_PENDING_EMAIL] = $email;
        $_SESSION[self::SESSION_OTP_SENT_AT] = time();
    }

    private function clearPendingOtpSession(): void
    {
        unset(
            $_SESSION[self::SESSION_PENDING_USER_ID],
            $_SESSION[self::SESSION_PENDING_EMAIL],
            $_SESSION[self::SESSION_OTP_SENT_AT]
        );
    }

    // -----------------------------------------------------------------
    // Email
    // -----------------------------------------------------------------

    private function sendOtpEmail(string $email, string $username, string $otp): void
    {
        $mail = new PHPMailer(true);
        $mail->isSMTP();

        $mail->Host = $_ENV['MAIL_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['MAIL_USERNAME'];
        $mail->Password = $_ENV['MAIL_PASSWORD'];
        $mail->SMTPSecure = $_ENV['MAIL_ENCRYPTION'] ?? PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = (int) ($_ENV['MAIL_PORT'] ?? 587);

        $mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME'] ?? 'RAM-YUM HRMS');
        $mail->addAddress($email, $username);

        $mail->isHTML(true);
        $mail->Subject = 'RAM-YUM HRMS Login Verification Code';
        $mail->Body = $this->buildOtpEmailBody($username, $otp);
        $mail->AltBody = "Your RAM-YUM HRMS login verification code is {$otp}. This code expires in "
            . self::OTP_EXPIRY_MINUTES . ' minutes.';

        $mail->send();
    }

    private function buildOtpEmailBody(string $username, string $otp): string
    {
        $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
        $expiryMinutes = self::OTP_EXPIRY_MINUTES;

        return <<<HTML
            <div style="font-family: Arial, sans-serif; line-height: 1.6; max-width: 560px; margin: auto;">
                <h2>RAM-YUM HRMS</h2>
                <p>Hello <strong>{$safeUsername}</strong>,</p>
                <p>Use the verification code below to complete your login:</p>
                <div style="font-size: 32px; font-weight: bold; letter-spacing: 8px; margin: 25px 0;">
                    {$otp}
                </div>
                <p>This code will expire in <strong>{$expiryMinutes} minutes</strong>.</p>
                <p>If you did not attempt to log in, please ignore this message.</p>
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

    /**
     * Emit a failure JSON response and terminate the request.
     */
    private function fail(string $message): never
    {
        echo json_encode([
            'success' => false,
            'message' => $message,
        ]);

        exit;
    }

    /**
     * Emit a success JSON response and terminate the request.
     *
     * @param array<string, mixed> $extra Additional response fields (e.g. redirect).
     */
    private function succeed(string $message, array $extra = []): never
    {
        echo json_encode(array_merge([
            'success' => true,
            'message' => $message,
        ], $extra));

        exit;
    }
}