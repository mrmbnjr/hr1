<?php

namespace App\Controllers;

use App\Models\Applicant;
use App\Services\Auth;
use App\Services\ResumeEvaluationService;
use App\Services\ResumeInputService;

class ApplicantController
{
    private Applicant $applicant;

    public function __construct()
    {
        $this->applicant = new Applicant();
    }


    public function index()
    {
        $applicants = $this->applicant->getAllApplicants();
        $positions = $this->applicant->getAllJobPostings();

        require '../resources/views/applicants/index.php';
    }

    public function apply()
    {
        $token = trim($_GET['token'] ?? '');

        if ($token === '') {
            http_response_code(404);
            require '../resources/views/misc/soon.php';
            exit;
        }

        $job = $this->applicant->getJobByApplicationToken($token);

        if (!$job) {
            http_response_code(404);
            require '../resources/views/misc/soon.php';
            exit;
        }

        // Check if job is still open
        if ($job['status'] !== 'Open') {
            require '../resources/views/public/apply-closed.php';
            exit;
        }

        // Check application deadline
        if ($job['application_deadline'] < date('Y-m-d')) {
            require '../resources/views/public/apply-closed.php';
            exit;
        }

        // Check if all vacancies have already been filled
        if (
            isset($job['hired_count']) &&
            (int) $job['hired_count'] >= (int) $job['vacancies']
        ) {
            require '../resources/views/public/apply-closed.php';
            exit;
        }

        require '../resources/views/public/apply.php';
    }


    /**
     * PUBLIC APPLICATION SUBMISSION
     */
    public function submitApplication()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?page=login");
            exit;
        }

        $token = trim($_POST['application_token'] ?? '');

        if ($token === '') {
            $_SESSION['application_error'] = 'Invalid application link.';
            header("Location: ?page=login");
            exit;
        }

        try {

            /*
             * Find the job posting using the token.
             */
            $job = $this->applicant->getJobByApplicationToken($token);

            if (!$job) {
                throw new \Exception('Invalid application link.');
            }

            /*
             * Make sure the job is still accepting applications.
             */
            if ($job['status'] !== 'Open') {
                throw new \Exception(
                    'This position is no longer accepting applications.'
                );
            }

            /*
             * Check application deadline.
             */
            if ($job['application_deadline'] < date('Y-m-d')) {
                throw new \Exception(
                    'The application deadline for this position has passed.'
                );
            }

            /*
             * Check vacancy limit.
             */
            if (
                isset($job['hired_count']) &&
                (int) $job['hired_count'] >= (int) $job['vacancies']
            ) {
                throw new \Exception(
                    'This position is no longer accepting applications because all vacancies have been filled.'
                );
            }


            /*
             * Validate applicant information.
             */
            $firstName = trim($_POST['first_name'] ?? '');
            $middleName = trim($_POST['middle_name'] ?? '');
            $lastName = trim($_POST['last_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if ($firstName === '') {
                throw new \Exception('First name is required.');
            }

            if ($lastName === '') {
                throw new \Exception('Last name is required.');
            }

            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception('Please provide a valid email address.');
            }

            if ($phone === '') {
                throw new \Exception('Phone number is required.');
            }


            /*
             * Resume is required by the database.
             */
            if (
                !isset($_FILES['resume']) ||
                $_FILES['resume']['error'] !== UPLOAD_ERR_OK
            ) {
                throw new \Exception('Please upload your resume.');
            }


            /*
             * Cover letter is optional.
             */
            $resume = $_FILES['resume'];
            $coverLetter = $_FILES['cover_letter'] ?? null;

            $resumeInput = new ResumeInputService();
            $resumeExtension = $resumeInput->validateUploadedFile($resume);


            /*
             * Optional cover letter validation.
             */
            if (
                $coverLetter &&
                $coverLetter['error'] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($coverLetter['error'] !== UPLOAD_ERR_OK) {
                    throw new \Exception(
                        'There was a problem uploading your cover letter.'
                    );
                }

                $coverExtension = $resumeInput->validateUploadedFile($coverLetter, 'Cover letter');
            }


            /*
             * Check if this applicant already exists.
             */
            $existingApplicant =
                $this->applicant->getApplicantByEmail($email);


            if ($existingApplicant) {

                /*
                 * Because applications has:
                 *
                 * UNIQUE(applicant_id, posting_id)
                 *
                 * we must check whether this person
                 * already applied to this specific job.
                 */
                if (
                    $this->applicant->hasApplied(
                        (int) $existingApplicant['applicant_id'],
                        (int) $job['posting_id']
                    )
                ) {
                    throw new \Exception(
                        'You have already submitted an application for this position.'
                    );
                }

                $applicantId =
                    (int) $existingApplicant['applicant_id'];

            } else {

                /*
                 * Create a new applicant.
                 */
                $applicantId = $this->applicant->createApplicant([
                    'first_name' => $firstName,
                    'middle_name' => $middleName !== ''
                        ? $middleName
                        : null,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address !== ''
                        ? $address
                        : null
                ]);
            }


            /*
             * Create safe unique filenames.
             */
            $projectRoot = dirname(__DIR__, 3);

            $uploadDirectory =
                $projectRoot .
                '/storage/uploads/applications/';


            if (!is_dir($uploadDirectory)) {
                mkdir(
                    $uploadDirectory,
                    0755,
                    true
                );
            }

            $resumeFileName =
                'resume_' .
                $applicantId .
                '_' .
                bin2hex(random_bytes(8)) .
                '.' .
                $resumeExtension;


            $resumePath =
                $uploadDirectory .
                $resumeFileName;


            if (!move_uploaded_file(
                $resume['tmp_name'],
                $resumePath
            )) {
                throw new \Exception(
                    'Unable to save the uploaded resume.'
                );
            }

            /*
             * Cover letter.
             */
            $coverLetterFileName = null;

            if (
                $coverLetter &&
                $coverLetter['error'] === UPLOAD_ERR_OK
            ) {

                $coverExtension = strtolower(
                    pathinfo(
                        $coverLetter['name'],
                        PATHINFO_EXTENSION
                    )
                );

                $coverLetterFileName =
                    'cover_' .
                    $applicantId .
                    '_' .
                    bin2hex(random_bytes(8)) .
                    '.' .
                    $coverExtension;


                $coverLetterPath =
                    $uploadDirectory .
                    $coverLetterFileName;

                if (!move_uploaded_file(
                    $coverLetter['tmp_name'],
                    $coverLetterPath
                )) {

                    /*
                     * Remove resume if cover letter
                     * upload fails.
                     */
                    if (file_exists($resumePath)) {
                        unlink($resumePath);
                    }

                    throw new \Exception(
                        'Unable to save the uploaded cover letter.'
                    );
                }

            }


            /*
             * Create the application.
             */
            $applicationId =
                $this->applicant->createApplication([
                    'applicant_id' => $applicantId,
                    'posting_id' => $job['posting_id'],
                    'resume_file' => $resumeFileName,
                    'cover_letter_file' => $coverLetterFileName
                ]);

            try {
                $evaluationService = new \App\Services\ResumeEvaluationService();
                $evaluationService->evaluate((int) $applicationId);
            } catch (\Throwable $e) {
                $_SESSION['ai_screening_error'] =
                    'Application submitted successfully, but AI screening could not run right now.';
            }

            /*
             * Redirect to success page.
             */
            header(
                "Location: ?page=application-success"
            );
            exit;


        } catch (\Exception $e) {

            $_SESSION['application_error'] =
                $e->getMessage();

            header(
                "Location: ?page=apply&token=" .
                urlencode($token)
            );

            exit;
        }
    }


    /**
     * View Single Applicant
     */
    public function review()
    {
        Auth::requireRole(['HR', 'MGR']);

        if (!isset($_GET['id'])) {
            header("Location:?page=applicants");
            exit;
        }

        $applicantId = (int)$_GET['id'];

        $this->applicant->markAsUnderReview($applicantId);

        $applicant =
            $this->applicant->getApplicantById($applicantId);

        if (!$applicant) {
            header("Location:?page=applicants");
            exit;
        }

        $managers =
            $this->applicant->getManagers();

        require '../resources/views/applicants/review.php';
    }

    public function downloadResume(): void
    {
        Auth::requireRole(['HR', 'MGR']);

        $applicationId = (int) ($_GET['id'] ?? 0);

        if ($applicationId <= 0) {
            http_response_code(404);
            exit('File not found.');
        }

        $application = $this->applicant->getApplicationFile($applicationId);

        if (!$application) {
            http_response_code(404);
            exit('File not found.');
        }

        try {
            $resume = (new ResumeInputService())->getResume($application);
        } catch (\Throwable $e) {
            http_response_code(404);
            exit('File not found.');
        }

        $disposition = isset($_GET['download'])
            ? 'attachment'
            : 'inline';

        header('Content-Type: ' . $resume['mime_type']);
        header('Content-Length: ' . (string) filesize($resume['path']));
        header('Content-Disposition: ' . $disposition . '; filename="' . basename($resume['filename']) . '"');
        header('X-Content-Type-Options: nosniff');

        readfile($resume['path']);
        exit;
    }


    public function scheduleInterview()
    {
        Auth::requireRole(['HR', 'MGR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location:?page=applicants");
            exit;
        }

        $this->applicant->scheduleInterview($_POST);

        header(
            "Location:?page=review&id=" .
            $_POST['applicant_id']
        );

        exit;
    }

    public function updateStatus()
    {
        Auth::requireRole(['HR', 'MGR']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location:?page=applicants");
            exit;
        }

        $applicationId =
            (int) ($_POST['application_id'] ?? 0);

        $status =
            $_POST['status'] ?? '';

        if (!$applicationId || !$status) {
            header("Location:?page=applicants");
            exit;
        }

        try {

            $this->applicant->updateApplicationStatus(
                $applicationId,
                $status
            );

            header(
                "Location:?page=review&id=" .
                (int) ($_POST['applicant_id'] ?? 0)
            );

            exit;

        } catch (\Exception $e) {

            $_SESSION['error'] =
                $e->getMessage();

            header(
                "Location:?page=review&id=" .
                (int) ($_POST['applicant_id'] ?? 0)
            );

            exit;
        }
    }

    public function evaluateResume()
    {
        header('Content-Type: application/json');

        try {
            $applicationId = (int) ($_POST['application_id'] ?? $_GET['id'] ?? 0);

            if ($applicationId <= 0) {
                throw new \Exception('Invalid application ID.');
            }

            $service = new ResumeEvaluationService();
            $result = $service->evaluate($applicationId);

            echo json_encode([
                'success' => true,
                'message' => 'Resume screening completed.',
                'data' => $result,
            ]);

        } catch (\Throwable $e) {
            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }

        exit;
    }

    public function hire()
    {
        Auth::requireRole(['HR']);

        header('Content-Type: application/json');

        try {

            $applicationId = (int) ($_POST['application_id'] ?? 0);

            if ($applicationId <= 0) {
                throw new \Exception(
                    'Invalid application ID.'
                );
            }

            $model = new \App\Models\Applicant();

            $account = $model->hireApplication(
                $applicationId
            );

            echo json_encode([
                'success' => true,
                'message' => 'Applicant successfully hired.',
                'data' => $account
            ]);

        } catch (\Exception $e) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        exit;
    }


    public function reject()
    {
        Auth::requireRole(['HR']);

        header('Content-Type: application/json');

        try {

            $applicationId = (int) ($_POST['application_id'] ?? 0);

            if ($applicationId <= 0) {
                throw new \Exception(
                    'Invalid application ID.'
                );
            }

            $model = new \App\Models\Applicant();

            $success = $model->rejectApplication(
                $applicationId
            );

            if (!$success) {
                throw new \Exception(
                    'Unable to reject this application. It may already be hired or may not exist.'
                );
            }

            echo json_encode([
                'success' => true,
                'message' => 'Applicant has been rejected.'
            ]);

        } catch (\Exception $e) {

            http_response_code(400);

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }

        exit;
    }
}