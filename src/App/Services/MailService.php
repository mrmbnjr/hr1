<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;

class MailService
{
	private function createMailer(): PHPMailer
	{
		$mailer = new PHPMailer(true);

		$mailer->isSMTP();
		$mailer->Host = (string) getenv('MAIL_HOST');
		$mailer->SMTPAuth = true;
		$mailer->Username = (string) getenv('MAIL_USERNAME');
		$mailer->Password = preg_replace('/\s+/', '', (string) getenv('MAIL_PASSWORD'));
		$mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
		$mailer->Port = (int) (getenv('MAIL_PORT') ?: 587);
		$mailer->setFrom(
			(string) getenv('MAIL_FROM_ADDRESS'),
			trim((string) getenv('MAIL_FROM_NAME'), " \\\"'")
		);
		$mailer->isHTML(true);

		return $mailer;
	}

	public function sendHiringCredentials(array $account): void
	{
		$mailer = $this->createMailer();
		$mailer->addAddress((string) $account['email'], (string) $account['fullname']);
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

	public function sendInterviewSchedule(array $schedule): void
	{
		$mailer = $this->createMailer();
		$mailer->addAddress((string) $schedule['email'], (string) $schedule['fullname']);
		$mailer->Subject = 'Interview scheduled - ' . $schedule['position'];

		$name = htmlspecialchars((string) $schedule['fullname'], ENT_QUOTES, 'UTF-8');
		$position = htmlspecialchars((string) $schedule['position'], ENT_QUOTES, 'UTF-8');
		$date = htmlspecialchars((string) $schedule['interview_date'], ENT_QUOTES, 'UTF-8');
		$time = htmlspecialchars((string) $schedule['interview_time'], ENT_QUOTES, 'UTF-8');
		$type = htmlspecialchars((string) $schedule['interview_type'], ENT_QUOTES, 'UTF-8');
		$location = htmlspecialchars((string) $schedule['location'], ENT_QUOTES, 'UTF-8');
		$interviewer = htmlspecialchars((string) $schedule['interviewer'], ENT_QUOTES, 'UTF-8');
		$notes = htmlspecialchars((string) ($schedule['notes'] ?? ''), ENT_QUOTES, 'UTF-8');

		$mailer->Body = <<<HTML
			<p>Dear {$name},</p>
			<p>Your interview for the <strong>{$position}</strong> position has been scheduled.</p>
			<ul>
				<li><strong>Date:</strong> {$date}</li>
				<li><strong>Time:</strong> {$time}</li>
				<li><strong>Type:</strong> {$type}</li>
				<li><strong>Location / link:</strong> {$location}</li>
				<li><strong>Interviewer:</strong> {$interviewer}</li>
			</ul>
			<p><strong>Notes:</strong> {$notes}</p>
			<p>Please be available a few minutes before the scheduled time.</p>
			<p>Regards,<br>RAM-YUM Recruitment</p>
		HTML;

		$mailer->AltBody = "Dear {$schedule['fullname']},\n\n"
			. "Your interview for the {$schedule['position']} position has been scheduled.\n\n"
			. "Date: {$schedule['interview_date']}\n"
			. "Time: {$schedule['interview_time']}\n"
			. "Type: {$schedule['interview_type']}\n"
			. "Location / link: {$schedule['location']}\n"
			. "Interviewer: {$schedule['interviewer']}\n"
			. "Notes: " . ($schedule['notes'] ?? '') . "\n\n"
			. "Please be available a few minutes before the scheduled time.";

		$mailer->send();
	}
}
