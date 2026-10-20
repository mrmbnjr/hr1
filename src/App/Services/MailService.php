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
		$appUrl = rtrim((string) config('app.url', ''), '/');
		if ($appUrl === '') {
			$scheme = !empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off'
				? 'https'
				: 'http';
			$appUrl = $scheme . '://' . (string) ($_SERVER['HTTP_HOST'] ?? 'localhost') . base_url();
		}
		$resetUrl = $appUrl . '/?page=reset-password&username=' . rawurlencode((string) $account['username']);
		$safeResetUrl = htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8');

		$mailer->Body = <<<HTML
			<p>Dear {$name},</p>
			<p>We are pleased to inform you that you have been hired for the <strong>{$position}</strong> position.</p>
			<p>Your HR1 account has been created:</p>
			<ul>
				<li><strong>Username:</strong> {$username}</li>
				<li><strong>Temporary password:</strong> {$temporaryPassword}</li>
			</ul>
			<p><a href="{$safeResetUrl}">Reset your password</a></p>
			<p>Use the username and temporary password above if you choose the username option. Keep these credentials confidential.</p>
			<p>Regards,<br>RAM-YUM Recruitment</p>
		HTML;

		$mailer->AltBody = "Dear {$account['fullname']},\n\n"
			. "You have been hired for the {$account['position']} position.\n\n"
			. "Username: {$account['username']}\n"
			. "Temporary password: {$account['temporary_password']}\n\n"
			. "Reset your password: {$resetUrl}\n\n"
			. "Use the username and temporary password above if you choose the username option.";

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

	/**
	 * Job offer letter. Position, salary, type, start date, terms and
	 * the expiry date come from the job_offers row.
	 */
	public function sendOfferLetter(array $offer): void
	{
		$mailer = $this->createMailer();
		$mailer->addAddress((string) $offer['email'], (string) $offer['fullname']);
		$mailer->Subject = 'Job offer - ' . $offer['position'];

		$name = htmlspecialchars((string) $offer['fullname'], ENT_QUOTES, 'UTF-8');
		$position = htmlspecialchars((string) $offer['position'], ENT_QUOTES, 'UTF-8');
		$salary = htmlspecialchars((string) $offer['salary'], ENT_QUOTES, 'UTF-8');
		$employmentType = htmlspecialchars((string) $offer['employment_type'], ENT_QUOTES, 'UTF-8');
		$startDate = htmlspecialchars((string) $offer['start_date'], ENT_QUOTES, 'UTF-8');
		$terms = nl2br(htmlspecialchars((string) ($offer['terms'] ?? ''), ENT_QUOTES, 'UTF-8'));
		$expiresOn = !empty($offer['expires_on'])
			? htmlspecialchars((string) $offer['expires_on'], ENT_QUOTES, 'UTF-8')
			: null;

		$mailer->Body = <<<HTML
			<p>Dear {$name},</p>
			<p>We are pleased to extend an offer for the <strong>{$position}</strong> position.</p>
			<ul>
				<li><strong>Position:</strong> {$position}</li>
				<li><strong>Employment type:</strong> {$employmentType}</li>
				<li><strong>Offered salary:</strong> {$salary}</li>
				<li><strong>Proposed start date:</strong> {$startDate}</li>
			</ul>
			<p><strong>Terms:</strong><br>{$terms}</p>
HTML;

		if ($expiresOn !== null) {
			$mailer->Body .= <<<HTML
			<p>This offer is valid until <strong>{$expiresOn}</strong>. Please let us know your decision before that date.</p>
HTML;
		}

		$mailer->Body .= <<<HTML
			<p>Regards,<br>RAM-YUM Recruitment</p>
HTML;

		$mailer->AltBody = "Dear {$offer['fullname']},\n\n"
			. "We are pleased to extend an offer for the {$offer['position']} position.\n\n"
			. "Position: {$offer['position']}\n"
			. "Employment type: {$offer['employment_type']}\n"
			. "Offered salary: {$offer['salary']}\n"
			. "Proposed start date: {$offer['start_date']}\n"
			. "Terms: " . ($offer['terms'] ?? '') . "\n"
			. ($expiresOn !== null
				? "This offer is valid until {$expiresOn}.\n\n"
				: "\n")
			. "Regards,\nRAM-YUM Recruitment";

		$mailer->send();
	}

	/**
	 * Generic, polite rejection notice. The internal reason category
	 * and any HR note are deliberately kept out of the email - they
	 * stay HR-internal in the application timeline.
	 */
	public function sendRejectionEmail(array $applicant): void
	{
		$mailer = $this->createMailer();
		$mailer->addAddress((string) $applicant['email'], (string) $applicant['fullname']);
		$mailer->Subject = 'Update on your application';

		$name = htmlspecialchars((string) $applicant['fullname'], ENT_QUOTES, 'UTF-8');
		$position = htmlspecialchars((string) $applicant['position'], ENT_QUOTES, 'UTF-8');

		$mailer->Body = <<<HTML
			<p>Dear {$name},</p>
			<p>Thank you for your interest in the <strong>{$position}</strong> position and for the time you invested in the selection process.</p>
			<p>After careful consideration, we will not be moving forward with your application at this time. We genuinely appreciate the effort you put into applying and encourage you to apply for future openings that match your qualifications.</p>
			<p>We wish you every success in your career.</p>
			<p>Regards,<br>RAM-YUM Recruitment</p>
		HTML;

		$mailer->AltBody = "Dear {$applicant['fullname']},\n\n"
			. "Thank you for your interest in the {$applicant['position']} position and for the time you invested in the selection process.\n\n"
			. "After careful consideration, we will not be moving forward with your application at this time. We genuinely appreciate the effort you put into applying and encourage you to apply for future openings that match your qualifications.\n\n"
			. "We wish you every success in your career.\n\n"
			. "Regards,\nRAM-YUM Recruitment";

		$mailer->send();
	}

	/**
	 * Confirmation that the application was received, sent on public
	 * submit.
	 */
	public function sendApplicationReceived(array $applicant): void
	{
		$mailer = $this->createMailer();
		$mailer->addAddress((string) $applicant['email'], (string) $applicant['fullname']);
		$mailer->Subject = 'Application received - ' . $applicant['position'];

		$name = htmlspecialchars((string) $applicant['fullname'], ENT_QUOTES, 'UTF-8');
		$position = htmlspecialchars((string) $applicant['position'], ENT_QUOTES, 'UTF-8');

		$mailer->Body = <<<HTML
			<p>Dear {$name},</p>
			<p>Thank you for applying for the <strong>{$position}</strong> position. We have received your application and it is now in our review queue.</p>
			<p>Our team reviews every application carefully, and we will be in touch with an update on the next steps.</p>
			<p>Regards,<br>RAM-YUM Recruitment</p>
		HTML;

		$mailer->AltBody = "Dear {$applicant['fullname']},\n\n"
			. "Thank you for applying for the {$applicant['position']} position. We have received your application and it is now in our review queue.\n\n"
			. "Our team reviews every application carefully, and we will be in touch with an update on the next steps.\n\n"
			. "Regards,\nRAM-YUM Recruitment";

		$mailer->send();
	}

	/**
	 * Generic progress update when an application moves to the next
	 * stage. Deliberately not a decision message - the candidate is
	 * told the process is progressing, nothing more.
	 */
	public function sendStatusUpdate(array $applicant): void
	{
		$mailer = $this->createMailer();
		$mailer->addAddress((string) $applicant['email'], (string) $applicant['fullname']);
		$mailer->Subject = 'Update on your application';

		$name = htmlspecialchars((string) $applicant['fullname'], ENT_QUOTES, 'UTF-8');
		$position = htmlspecialchars((string) $applicant['position'], ENT_QUOTES, 'UTF-8');

		$mailer->Body = <<<HTML
			<p>Dear {$name},</p>
			<p>Thank you for your patience. Your application for the <strong>{$position}</strong> position has moved to the next stage of our selection process.</p>
			<p>We will be in touch with any further updates as the process continues.</p>
			<p>Regards,<br>RAM-YUM Recruitment</p>
		HTML;

		$mailer->AltBody = "Dear {$applicant['fullname']},\n\n"
			. "Thank you for your patience. Your application for the {$applicant['position']} position has moved to the next stage of our selection process.\n\n"
			. "We will be in touch with any further updates as the process continues.\n\n"
			. "Regards,\nRAM-YUM Recruitment";

		$mailer->send();
	}
}
