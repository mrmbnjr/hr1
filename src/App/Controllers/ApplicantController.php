<?php

namespace App\Controllers;

use App\Models\Applicant;
use App\Services\MailService;
use App\Services\Auth;
use App\Services\ResumeEvaluationService;
use App\Services\ResumeInputService;
use App\Services\AcademicDocumentInputService;
use App\Services\AcademicDocumentEvaluationService;

class ApplicantController
{
    private Applicant $applicant;

    public function __construct()
    {
        $this->applicant = new Applicant();
    }


    /*
    |--------------------------------------------------------------------------
    | Applicant List
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $applicants = $this->applicant->getAllApplicants();
        $positions = $this->applicant->getAllJobPostings();

        require BASE_PATH . '/resources/views/applicants/index.php';
    }


    /*
    |--------------------------------------------------------------------------
    | Public Application Form
    |--------------------------------------------------------------------------
    */

    public function apply()
    {
        $token = trim($_GET['token'] ?? '');

        if ($token === '') {
            http_response_code(404);
            require BASE_PATH . '/resources/views/misc/soon.php';
            exit;
        }

        $job = $this->applicant->getJobByApplicationToken($token);

        if (!$job) {
            http_response_code(404);
            require BASE_PATH . '/resources/views/misc/soon.php';
            exit;
        }

        /*
         * The job must still be accepting applications.
         */
        $closedReason = $this->jobClosedReason($job);

        if ($closedReason !== null) {
            require BASE_PATH . '/resources/views/public/apply-closed.php';
            exit;
        }

        require BASE_PATH . '/resources/views/public/apply.php';
    }


    /*
    |--------------------------------------------------------------------------
    | Public Application Submission
    |--------------------------------------------------------------------------
    */

    public function submitApplication()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location: ?page=login");
            exit;
        }

        $token = trim($_POST['application_token'] ?? '');

        if ($token === '') {
            $_SESSION['application_error'] =
                'Invalid application link.';

            header("Location: ?page=login");
            exit;
        }


        /*
         * Keep track of saved files so they can be removed
         * if something fails after the upload succeeds.
         */
        $savedFilePaths = [];


        try {

            /*
            |--------------------------------------------------------------------------
            | 1. Find job posting
            |--------------------------------------------------------------------------
            */

            $job =
                $this->applicant
                    ->getJobByApplicationToken($token);


            if (!$job) {
                throw new \Exception(
                    'Invalid application link.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 2. Make sure job is accepting applications
            |--------------------------------------------------------------------------
            */

            $closedReason = $this->jobClosedReason($job);

            if ($closedReason !== null) {
                throw new \Exception($closedReason);
            }


            /*
            |--------------------------------------------------------------------------
            | 3. Applicant information
            |--------------------------------------------------------------------------
            */

            $firstName =
                trim($_POST['first_name'] ?? '');

            $middleName =
                trim($_POST['middle_name'] ?? '');

            $lastName =
                trim($_POST['last_name'] ?? '');

            $email =
                trim($_POST['email'] ?? '');

            $phone =
                trim($_POST['phone'] ?? '');

            $address =
                trim($_POST['address'] ?? '');


            if ($firstName === '') {
                throw new \Exception(
                    'First name is required.'
                );
            }

            if ($lastName === '') {
                throw new \Exception(
                    'Last name is required.'
                );
            }

            if (
                $email === '' ||
                !filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                )
            ) {
                throw new \Exception(
                    'Please provide a valid email address.'
                );
            }

            if ($phone === '') {
                throw new \Exception(
                    'Phone number is required.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 6. Resume
            |--------------------------------------------------------------------------
            */

            if (
                !isset($_FILES['resume']) ||
                $_FILES['resume']['error'] !== UPLOAD_ERR_OK
            ) {
                throw new \Exception(
                    'Please upload your resume.'
                );
            }


            $resume =
                $_FILES['resume'];

            $academicDocuments =
                $_FILES['academic_documents'] ?? null;

            $coverLetter =
                $_FILES['cover_letter'] ?? null;


            /*
            |--------------------------------------------------------------------------
            | 7. Validate resume
            |--------------------------------------------------------------------------
            */

            $resumeInput =
                new ResumeInputService();

            $resumeExtension =
                $resumeInput->validateUploadedFile(
                    $resume
                );


            /*
            |--------------------------------------------------------------------------
            | 8. Validate academic documents
            |--------------------------------------------------------------------------
            |
            | The form uses:
            |
            | academic_documents[]
            |
            | Multiple files are allowed.
            |
            */

            $academicDocumentRequired =
                !empty(
                    $job['academic_document_required']
                );

            $academicFiles = [];


            if (
                $academicDocuments !== null &&
                isset($academicDocuments['name']) &&
                is_array($academicDocuments['name'])
            ) {

                $academicInput =
                    new AcademicDocumentInputService();


                foreach (
                    $academicDocuments['name']
                    as $index => $originalName
                ) {

                    $fileError =
                        $academicDocuments['error'][$index]
                        ?? UPLOAD_ERR_NO_FILE;


                    /*
                     * Ignore empty file slots.
                     */
                    if (
                        $fileError ===
                        UPLOAD_ERR_NO_FILE
                    ) {
                        continue;
                    }


                    /*
                     * Handle upload errors.
                     */
                    if (
                        $fileError !==
                        UPLOAD_ERR_OK
                    ) {
                        throw new \Exception(
                            'There was a problem uploading one of your certificates or diplomas.'
                        );
                    }


                    /*
                     * Rebuild a normal $_FILES-style array.
                     */
                    $academicFile = [

                        'name' =>
                            $academicDocuments['name'][$index]
                            ?? '',

                        'type' =>
                            $academicDocuments['type'][$index]
                            ?? '',

                        'tmp_name' =>
                            $academicDocuments['tmp_name'][$index]
                            ?? '',

                        'error' =>
                            $fileError,

                        'size' =>
                            $academicDocuments['size'][$index]
                            ?? 0
                    ];


                    /*
                     * Validate individual document.
                     */
                    $extension =
                        $academicInput
                            ->validateUploadedFile(
                                $academicFile
                            );


                    /*
                     * Keep the validated document
                     * until it is moved into storage.
                     */
                    $academicFiles[] = [

                        'file' =>
                            $academicFile,

                        'extension' =>
                            $extension,

                        'original_file_name' =>
                            basename(
                                $academicFile['name']
                            )
                    ];
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 9. Required academic document check
            |--------------------------------------------------------------------------
            */

            if (
                $academicDocumentRequired &&
                empty($academicFiles)
            ) {
                throw new \Exception(
                    'Please upload at least one certificate or diploma.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 10. Validate optional cover letter
            |--------------------------------------------------------------------------
            */

            $coverExtension = null;


            if (
                $coverLetter &&
                $coverLetter['error'] !==
                UPLOAD_ERR_NO_FILE
            ) {

                if (
                    $coverLetter['error'] !==
                    UPLOAD_ERR_OK
                ) {
                    throw new \Exception(
                        'There was a problem uploading your cover letter.'
                    );
                }


                $coverExtension =
                    $resumeInput
                        ->validateUploadedFile(
                            $coverLetter,
                            'Cover letter'
                        );
            }


            /*
            |--------------------------------------------------------------------------
            | 11. Find or create applicant
            |--------------------------------------------------------------------------
            */

            $existingApplicant =
                $this->applicant
                    ->getApplicantByEmail(
                        $email
                    );


            if ($existingApplicant) {

                /*
                 * Prevent duplicate application
                 * for the same position.
                 */
                if (
                    $this->applicant->hasApplied(
                        (int)
                        $existingApplicant['applicant_id'],

                        (int)
                        $job['posting_id']
                    )
                ) {
                    throw new \Exception(
                        'You have already submitted an application for this position using this email address. You cannot submit another application for the same position.'
                    );
                }


                $applicantId =
                    (int)
                    $existingApplicant['applicant_id'];

            } else {

                $applicantId =
                    $this->applicant
                        ->createApplicant([
                            'first_name' =>
                                $firstName,

                            'middle_name' =>
                                $middleName !== ''
                                    ? $middleName
                                    : null,

                            'last_name' =>
                                $lastName,

                            'email' =>
                                $email,

                            'phone' =>
                                $phone,

                            'address' =>
                                $address !== ''
                                    ? $address
                                    : null
                        ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 12. Create upload directory
            |--------------------------------------------------------------------------
            */

            $projectRoot =
                dirname(__DIR__, 3);

            $uploadDirectory =
                $projectRoot .
                '/storage/uploads/applications/';


            if (!is_dir($uploadDirectory)) {

                if (
                    !mkdir(
                        $uploadDirectory,
                        0755,
                        true
                    ) &&
                    !is_dir($uploadDirectory)
                ) {
                    throw new \Exception(
                        'Unable to create the application upload directory.'
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 13. Save resume
            |--------------------------------------------------------------------------
            */

            $resumeFileName =
                'resume_' .
                $applicantId .
                '_' .
                bin2hex(
                    random_bytes(8)
                ) .
                '.' .
                $resumeExtension;


            $resumePath =
                $uploadDirectory .
                $resumeFileName;


            if (
                !move_uploaded_file(
                    $resume['tmp_name'],
                    $resumePath
                )
            ) {
                throw new \Exception(
                    'Unable to save the uploaded resume.'
                );
            }


            $savedFilePaths[] =
                $resumePath;


            /*
            |--------------------------------------------------------------------------
            | 14. Save academic documents
            |--------------------------------------------------------------------------
            |
            | Every uploaded certificate/diploma gets:
            |
            | - generated storage filename
            | - original filename
            |
            | The first document becomes primary.
            |
            */

            $academicDocumentsForDatabase = [];


            foreach (
                $academicFiles
                as $academicFile
            ) {

                $academicFileName =
                    'academic_' .
                    $applicantId .
                    '_' .
                    bin2hex(
                        random_bytes(8)
                    ) .
                    '.' .
                    $academicFile['extension'];


                $academicFilePath =
                    $uploadDirectory .
                    $academicFileName;


                if (
                    !move_uploaded_file(
                        $academicFile['file']['tmp_name'],
                        $academicFilePath
                    )
                ) {

                    throw new \Exception(
                        'Unable to save one of the uploaded certificates or diplomas.'
                    );
                }


                $savedFilePaths[] =
                    $academicFilePath;


                $academicDocumentsForDatabase[] = [

                    'file_name' =>
                        $academicFileName,

                    'original_file_name' =>
                        $academicFile['original_file_name']
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | 15. Save optional cover letter
            |--------------------------------------------------------------------------
            */

            $coverLetterFileName =
                null;


            if (
                $coverLetter &&
                $coverLetter['error'] ===
                UPLOAD_ERR_OK
            ) {

                $coverLetterFileName =
                    'cover_' .
                    $applicantId .
                    '_' .
                    bin2hex(
                        random_bytes(8)
                    ) .
                    '.' .
                    $coverExtension;


                $coverLetterPath =
                    $uploadDirectory .
                    $coverLetterFileName;


                if (
                    !move_uploaded_file(
                        $coverLetter['tmp_name'],
                        $coverLetterPath
                    )
                ) {
                    throw new \Exception(
                        'Unable to save the uploaded cover letter.'
                    );
                }


                $savedFilePaths[] =
                    $coverLetterPath;
            }


            /*
            |--------------------------------------------------------------------------
            | 16. Create application
            |--------------------------------------------------------------------------
            |
            | IMPORTANT:
            |
            | We no longer send:
            |
            | academic_document_file
            |
            | Instead, all academic documents are passed
            | to the Applicant model.
            |
            */

            $applicationId =
                $this->applicant
                    ->createApplication([

                        'applicant_id' =>
                            $applicantId,

                        'posting_id' =>
                            $job['posting_id'],

                        'resume_file' =>
                            $resumeFileName,

                        'cover_letter_file' =>
                            $coverLetterFileName,

                        'academic_documents' =>
                            $academicDocumentsForDatabase
                    ]);


            /*
            |--------------------------------------------------------------------------
            | 17. Resume AI screening
            |--------------------------------------------------------------------------
            */

            try {

                $evaluationService =
                    new ResumeEvaluationService();

                $evaluationService->evaluate(
                    (int)
                    $applicationId
                );

            } catch (\Throwable $e) {

                error_log(
                    'Resume AI evaluation failed: ' .
                    $e->getMessage()
                );

                $_SESSION['ai_screening_error'] =
                    'Application submitted successfully, but AI screening could not run right now.';
            }


            /*
            |--------------------------------------------------------------------------
            | 18. Academic document evaluation
            |--------------------------------------------------------------------------
            |
            | The service will later be updated to process
            | each academic_document_evaluations record.
            |
            | For now, pass the application ID because
            | that is the existing service interface.
            |
            */

            if (
                !empty(
                    $academicDocumentsForDatabase
                )
            ) {

                try {

                    $academicService =
                        new AcademicDocumentEvaluationService();

                    $academicService->evaluate(
                        (int)
                        $applicationId
                    );

                } catch (\Throwable $e) {

                    error_log(
                        'Academic document evaluation failed: ' .
                        $e->getMessage()
                    );

                    $_SESSION['academic_validation_error'] =
                        'Application submitted successfully, but certificate verification could not run right now.';
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 19. Success
            |--------------------------------------------------------------------------
            */

            header(
                "Location: ?page=application-success"
            );

            exit;


        } catch (\Throwable $e) {

            /*
             * Remove uploaded files when
             * submission fails.
             */
            foreach (
                $savedFilePaths
                as $savedPath
            ) {

                if (
                    is_string($savedPath) &&
                    file_exists($savedPath)
                ) {
                    @unlink($savedPath);
                }
            }


            $_SESSION['application_error'] =
                $e->getMessage();


            header(
                "Location: ?page=apply&token=" .
                urlencode($token)
            );

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Review Applicant
    |--------------------------------------------------------------------------
    */

    public function review()
    {
        Auth::requireRole([
            'ADMIN',
            'HR',
            'MGR'
        ]);


        if (!isset($_GET['id'])) {
            header(
                "Location:?page=applicants"
            );
            exit;
        }


        $applicantId =
            (int) $_GET['id'];


        $this->applicant
            ->markAsUnderReview(
                $applicantId
            );


        $applicant =
            $this->applicant
                ->getApplicantById(
                    $applicantId
                );


        if (!$applicant) {
            header(
                "Location:?page=applicants"
            );
            exit;
        }


        $managers =
            $this->applicant
                ->getManagers();


        /*
         * All academic documents can be used
         * by the review UI later.
         */
        $academicDocuments =
            $this->applicant
                ->getAcademicDocumentsForApplication(
                    (int) $applicant['application_id']
                );


        require BASE_PATH . '/resources/views/applicants/review.php';
    }


    /*
    |--------------------------------------------------------------------------
    | Download Resume
    |--------------------------------------------------------------------------
    */

    public function downloadResume(): void
    {
        Auth::requireRole([
            'HR',
            'MGR'
        ]);


        $applicationId =
            (int) (
                $_GET['id'] ?? 0
            );


        if ($applicationId <= 0) {
            http_response_code(404);
            exit('File not found.');
        }


        $application =
            $this->applicant
                ->getApplicationFile(
                    $applicationId
                );


        if (!$application) {
            http_response_code(404);
            exit('File not found.');
        }


        try {

            $resume =
                (new ResumeInputService())
                    ->getResume(
                        $application
                    );

        } catch (\Throwable $e) {

            http_response_code(404);
            exit('File not found.');
        }


        $this->streamFile($resume);
    }


    /*
    |--------------------------------------------------------------------------
    | Download Primary Academic Document
    |--------------------------------------------------------------------------
    */

    public function downloadAcademicDocument(): void
    {
        Auth::requireRole([
            'HR',
            'MGR'
        ]);


        $applicationId =
            (int) (
                $_GET['id'] ?? 0
            );


        if ($applicationId <= 0) {
            http_response_code(404);
            exit('File not found.');
        }


        /*
         * For now this returns the primary document.
         *
         * Later the review page can pass evaluation_id
         * to download any specific academic document.
         */
        $application =
            $this->applicant
                ->getAcademicDocumentFile(
                    $applicationId
                );


        if (!$application) {
            http_response_code(404);
            exit('File not found.');
        }


        try {

            $document =
                (new AcademicDocumentInputService())
                    ->getDocument(
                        $application
                    );

        } catch (\Throwable $e) {

            http_response_code(404);
            exit('File not found.');
        }


        $this->streamFile($document);
    }


    /*
    |--------------------------------------------------------------------------
    | Schedule Interview
    |--------------------------------------------------------------------------
    */

    public function scheduleInterview()
    {
        Auth::requireRole([
            'ADMIN',
            'HR',
            'MGR'
        ]);


        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header(
                "Location:?page=applicants"
            );
            exit;
        }


        $applicationId =
            (int) (
                $_POST['application_id'] ?? 0
            );


        $applicantId =
            (int) (
                $_POST['applicant_id'] ?? 0
            );


        $applicant =
            $this->applicant
                ->getApplicantById(
                    $applicantId
                );


        if (
            !$applicant ||
            (int)
            $applicant['application_id'] !==
            $applicationId
        ) {

            $_SESSION['error'] =
                'Applicant application could not be found.';

            header(
                'Location:?page=applicants'
            );

            exit;
        }


        $this->applicant
            ->scheduleInterview(
                $_POST
            );


        $emailSent = true;

        $interviewerName =
            'HR Recruitment Team';


        foreach (
            $this->applicant
                ->getManagers()
            as $manager
        ) {

            if (
                (int)
                $manager['user_id'] ===
                (int)
                (
                    $_POST['interviewer_id'] ?? 0
                )
            ) {

                $interviewerName =
                    $manager['fullname'];

                break;
            }
        }


        try {

            (new MailService())
                ->sendInterviewSchedule([

                    'email' =>
                        $applicant['email'],

                    'fullname' =>
                        $applicant['fullname'],

                    'position' =>
                        $applicant['position'],

                    'interview_date' =>
                        date(
                            'F d, Y',
                            strtotime(
                                $_POST['interview_date']
                            )
                        ),

                    'interview_time' =>
                        date(
                            'g:i A',
                            strtotime(
                                $_POST['interview_time']
                            )
                        ),

                    'interview_type' =>
                        $_POST['interview_type']
                        ?? '',

                    'location' =>
                        $_POST['location']
                        ?? '',

                    'interviewer' =>
                        $interviewerName,

                    'notes' =>
                        $_POST['notes']
                        ?? ''
                ]);

        } catch (\Throwable $mailError) {

            error_log(
                'Interview schedule email failed: ' .
                $mailError->getMessage()
            );

            $emailSent = false;
        }


        $_SESSION['success'] =
            $emailSent
                ? 'Interview scheduled and notification emailed to the applicant.'
                : 'Interview scheduled, but the notification email could not be sent.';


        header(
            "Location:?page=review&id=" .
            $applicantId
        );

        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Evaluate Resume
    |--------------------------------------------------------------------------
    */

    public function evaluateResume()
    {
        Auth::requireRole([
            'HR',
            'MGR'
        ]);


        header(
            'Content-Type: application/json'
        );


        try {

            $applicationId =
                (int) (
                    $_POST['application_id']
                    ??
                    $_GET['id']
                    ??
                    0
                );


            if ($applicationId <= 0) {
                throw new \Exception(
                    'Invalid application ID.'
                );
            }


            $service =
                new ResumeEvaluationService();


            $result =
                $service->evaluate(
                    $applicationId
                );


            echo json_encode([
                'success' =>
                    true,

                'message' =>
                    'Resume screening completed.',

                'data' =>
                    $result
            ]);

        } catch (\Throwable $e) {

            http_response_code(400);


            echo json_encode([
                'success' =>
                    false,

                'message' =>
                    $e->getMessage()
            ]);
        }


        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Evaluate Academic Documents
    |--------------------------------------------------------------------------
    */

    public function evaluateAcademicDocument(): void
    {
        Auth::requireRole([
            'HR',
            'MGR'
        ]);


        header(
            'Content-Type: application/json'
        );


        try {

            $applicationId =
                (int) (
                    $_POST['application_id']
                    ??
                    $_GET['id']
                    ??
                    0
                );


            if ($applicationId <= 0) {
                throw new \Exception(
                    'Invalid application ID.'
                );
            }


            /*
             * Every credential document belonging to the application is
             * assessed together in a single AI pass. The response carries
             * the per-document breakdown plus an aggregate summary.
             */
            $service =
                new AcademicDocumentEvaluationService();


            $documents =
                $service->evaluate(
                    $applicationId
                );


            $summary =
                $service->summarize(
                    $documents
                );


            $message =
                'All ' .
                $summary['total'] .
                ' credential document(s) were assessed.';


            if ($summary['failed'] > 0) {

                $message =
                    'Assessment finished, but ' .
                    $summary['failed'] .
                    ' document(s) could not be assessed.';

            } elseif ($summary['flagged'] > 0) {

                $message .=
                    ' ' .
                    $summary['flagged'] .
                    ' need manual review.';
            }


            echo json_encode([

                'success' =>
                    true,

                'message' =>
                    $message,

                'data' => [

                    'summary' =>
                        $summary,

                    'documents' =>
                        $documents
                ]
            ]);

        } catch (\Throwable $e) {

            http_response_code(400);


            echo json_encode([

                'success' =>
                    false,

                'message' =>
                    $e->getMessage()
            ]);
        }


        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Hire Applicant
    |--------------------------------------------------------------------------
    */

    public function hire()
    {
        Auth::requireRole([
            'HR'
        ]);


        header(
            'Content-Type: application/json'
        );


        try {

            $applicationId =
                (int) (
                    $_POST['application_id'] ?? 0
                );


            if ($applicationId <= 0) {
                throw new \Exception(
                    'Invalid application ID.'
                );
            }


            $account =
                $this->applicant
                    ->hireApplication(
                        $applicationId
                    );


            $emailSent = true;


            try {

                (new MailService())
                    ->sendHiringCredentials(
                        $account
                    );

            } catch (\Throwable $mailError) {

                error_log(
                    'Hiring credentials email failed: ' .
                    $mailError->getMessage()
                );

                $emailSent = false;
            }


            echo json_encode([

                'success' =>
                    true,

                'message' =>
                    $emailSent
                        ? 'Applicant successfully hired and credentials emailed.'
                        : 'Applicant successfully hired, but the credentials email could not be sent.',

                'email_sent' =>
                    $emailSent,

                'data' =>
                    $account
            ]);

        } catch (\Throwable $e) {

            http_response_code(400);


            echo json_encode([

                'success' =>
                    false,

                'message' =>
                    $e->getMessage()
            ]);
        }


        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Reject Applicant
    |--------------------------------------------------------------------------
    */

    public function reject()
    {
        Auth::requireRole([
            'HR'
        ]);


        header(
            'Content-Type: application/json'
        );


        try {

            $applicationId =
                (int) (
                    $_POST['application_id'] ?? 0
                );


            if ($applicationId <= 0) {
                throw new \Exception(
                    'Invalid application ID.'
                );
            }


            $success =
                $this->applicant
                    ->rejectApplication(
                        $applicationId
                    );


            if (!$success) {
                throw new \Exception(
                    'Unable to reject this application. It may already be hired or may not exist.'
                );
            }


            echo json_encode([

                'success' =>
                    true,

                'message' =>
                    'Applicant has been rejected.'
            ]);

        } catch (\Throwable $e) {

            http_response_code(400);


            echo json_encode([

                'success' =>
                    false,

                'message' =>
                    $e->getMessage()
            ]);
        }


        exit;
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Returns the reason the job posting is closed to new applications, or
     * null when it is still accepting them.
     *
     * Shared by the public form (renders the "closed" page) and the
     * submission handler (rejects the request with the same reason).
     */
    private function jobClosedReason(array $job): ?string
    {
        if (($job['status'] ?? '') !== 'Open') {
            return 'This position is no longer accepting applications.';
        }

        if (
            !empty($job['application_deadline']) &&
            $job['application_deadline'] < date('Y-m-d')
        ) {
            return 'The application deadline for this position has passed.';
        }

        if (
            isset($job['hired_count']) &&
            (int) $job['hired_count'] >= (int) ($job['vacancies'] ?? 0)
        ) {
            return 'This position is no longer accepting applications because all vacancies have been filled.';
        }

        if (
            isset($job['applicant_count'], $job['max_applicants']) &&
            (int) $job['applicant_count'] >= (int) $job['max_applicants']
        ) {
            return 'This position has reached its maximum number of applicants.';
        }

        return null;
    }

    /**
     * Stream a resolved file to the browser — inline by default, or as a
     * download when the request carries ?download.
     */
    private function streamFile(array $file): void
    {
        $disposition = isset($_GET['download']) ? 'attachment' : 'inline';

        header('Content-Type: ' . $file['mime_type']);
        header('Content-Length: ' . (string) filesize($file['path']));
        header('Content-Disposition: ' . $disposition . '; filename="' . basename($file['filename']) . '"');
        header('X-Content-Type-Options: nosniff');

        readfile($file['path']);

        exit;
    }
}