<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
	public function sendHiringCredentials(array $account): void
	{
		$mailer = new PHPMailer(true);

		$mailer->isSMTP();
		$mailer->Host = (string) getenv('MAIL_HOST');
		$mailer->SMTPAuth = true;
		$mailer->Username = (string) getenv('MAIL_USERNAME');
		$mailer->Password = preg_replace('/\s+/', '', (string) getenv('MAIL_PASSWORD'));
		$mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		$mailer->Port = (int) (getenv('MAIL_PORT') ?: 587);

		$fromAddress = (string) getenv('MAIL_FROM_ADDRESS');
		$fromName = trim((string) getenv('MAIL_FROM_NAME'), " \\\"'");

		$mailer->setFrom($fromAddress, $fromName);
		$mailer->addAddress((string) $account['email'], (string) $account['fullname']);
		$mailer->isHTML(true);
		$mailer->Subject = 'You have been hired - your HR1 account';

		$name = htmlspecialchars((string) $account['fullname'], ENT_QUOTES, 'UTF-8');
		$position = htmlspecialchars((string) $account['position'], ENT_QUOTES, 'UTF-8');
		$username = htmlspecialchars((string) $account['username'], ENT_QUOTES, 'UTF-8');
		$temporaryPassword = htmlspecialchars((string) $account['temporary_password'], ENT_QUOTES, 'UTF-8');

		$mailer->Body = <<<HTML
			<p>Dear {$name},</p>
			<p>We are pleased to inform you that you have been hired for the <strong>{$position}</strong> position.</p>
			<p>Your HR1 account has been created:</p>
			<ul>
				<li><strong>Username:</strong> {$username}</li>
				<li><strong>Temporary password:</strong> {$temporaryPassword}</li>
			</ul>
			<p>Please sign in and change your password when prompted. Keep these credentials confidential.</p>
			<p>Regards,<br>RAM-YUM Recruitment</p>
		HTML;

		$mailer->AltBody = "Dear {$account['fullname']},\n\n"
			. "You have been hired for the {$account['position']} position.\n\n"
			. "Username: {$account['username']}\n"
			. "Temporary password: {$account['temporary_password']}\n\n"
			. "Please sign in and change your password when prompted.";

		$mailer->send();
	}
}
