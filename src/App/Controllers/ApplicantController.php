<?php

namespace App\Controllers;

use App\Models\Applicant;
use App\Models\JobOffer;
use App\Services\MailService;
use App\Services\Auth;
use App\Services\PipelineService;
use App\Services\ResumeEvaluationService;
use App\Services\ResumeInputService;
use App\Services\AcademicDocumentInputService;
use App\Services\AcademicDocumentEvaluationService;
use App\Models\ApplicationValidation;
use App\Services\ApplicationValidationService;
use App\Services\ApplicationRetentionService;

class ApplicantController
{

    private const MAX_INVALID_ATTEMPTS = 3;
    private const MAX_ATTEMPTS_PER_HOUR = 10;
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
        /*
         * Enforce the application retention policy before the list is read so
         * the figures reflect the cleaned-up state.
         */
        (new ApplicationRetentionService())->runIfDue();

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
            $_SESSION['application_error'] = 'Invalid application link.';

            header("Location: ?page=login");
            exit;
        }

        /*
         * Validation plus screening can take a while. Keep running (and
         * cleaning up) even if the browser is closed.
         */
        @set_time_limit(180);
        ignore_user_abort(true);

        $savedFilePaths = [];
        $stagingDirectory = null;
        $validationId = null;

        try {

            /*
            |--------------------------------------------------------------------------
            | 1. Job posting
            |--------------------------------------------------------------------------
            */

            $job = $this->applicant->getJobByApplicationToken($token);

            if (!$job) {
                throw new \Exception('Invalid application link.');
            }

            $closedReason = $this->jobClosedReason($job);

            if ($closedReason !== null) {
                throw new \Exception($closedReason);
            }


            /*
            |--------------------------------------------------------------------------
            | 2. Basic validation: applicant information
            |--------------------------------------------------------------------------
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
            |--------------------------------------------------------------------------
            | 2b. Data privacy consent
            |--------------------------------------------------------------------------
            |
            | The applicant must agree to the Privacy Notice before the
            | application is accepted. Enforced here because the consent
            | checkbox is only a client-side control.
            |
            */

            if (empty($_POST['data_privacy_consent'])) {
                throw new \Exception(
                    'Please read and agree to the Privacy Notice and consent to the collection and processing of your personal information before submitting your application.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 3. Basic validation: resume (PDF only, the AI cannot read DOC/DOCX)
            |--------------------------------------------------------------------------
            */

            if (
                !isset($_FILES['resume']) ||
                $_FILES['resume']['error'] !== UPLOAD_ERR_OK
            ) {
                throw new \Exception('Please upload your resume.');
            }

            $resume = $_FILES['resume'];
            $academicDocuments = $_FILES['academic_documents'] ?? null;
            $coverLetter = $_FILES['cover_letter'] ?? null;

            $resumeInput = new ResumeInputService();

            $resumeExtension = $resumeInput->validateUploadedFile($resume);

            if ($resumeExtension !== 'pdf') {
                throw new \Exception('Please upload your resume as a PDF file.');
            }


            /*
            |--------------------------------------------------------------------------
            | 4. Basic validation: academic documents (academic_documents[])
            |--------------------------------------------------------------------------
            */

            $academicDocumentRequired = !empty($job['academic_document_required']);
            $academicFiles = [];

            if (
                $academicDocuments !== null &&
                isset($academicDocuments['name']) &&
                is_array($academicDocuments['name'])
            ) {

                $academicInput = new AcademicDocumentInputService();

                foreach ($academicDocuments['name'] as $index => $originalName) {

                    $fileError = $academicDocuments['error'][$index] ?? UPLOAD_ERR_NO_FILE;

                    if ($fileError === UPLOAD_ERR_NO_FILE) {
                        continue;
                    }

                    if ($fileError !== UPLOAD_ERR_OK) {
                        throw new \Exception(
                            'There was a problem uploading one of your certificates or diplomas.'
                        );
                    }

                    $academicFile = [
                        'name' => $academicDocuments['name'][$index] ?? '',
                        'type' => $academicDocuments['type'][$index] ?? '',
                        'tmp_name' => $academicDocuments['tmp_name'][$index] ?? '',
                        'error' => $fileError,
                        'size' => $academicDocuments['size'][$index] ?? 0,
                    ];

                    $extension = $academicInput->validateUploadedFile($academicFile);

                    $academicFiles[] = [
                        'file' => $academicFile,
                        'extension' => $extension,
                        'original_file_name' => basename($academicFile['name']),
                    ];
                }
            }

            if ($academicDocumentRequired && empty($academicFiles)) {
                throw new \Exception('Please upload at least one certificate or diploma.');
            }


            /*
            |--------------------------------------------------------------------------
            | 5. Basic validation: optional cover letter
            |--------------------------------------------------------------------------
            */

            $coverExtension = null;

            if ($coverLetter && $coverLetter['error'] !== UPLOAD_ERR_NO_FILE) {

                if ($coverLetter['error'] !== UPLOAD_ERR_OK) {
                    throw new \Exception('There was a problem uploading your cover letter.');
                }

                $coverExtension = $resumeInput->validateUploadedFile(
                    $coverLetter,
                    'Cover letter'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 6. Duplicate checks (before any AI call is spent)
            |--------------------------------------------------------------------------
            */

            $existingApplicant = $this->applicant->getApplicantByEmail($email);

            if ($existingApplicant) {

                if (
                    $this->applicant->hasApplied(
                        (int) $existingApplicant['applicant_id'],
                        (int) $job['posting_id']
                    )
                ) {
                    throw new \Exception(
                        'You have already submitted an application for this position using this email address. You cannot submit another application for the same position.'
                    );
                }

            } elseif ($this->applicant->getApplicantByPhone($phone)) {

                throw new \Exception(
                    'This phone number is already registered with another applicant. Please use a different phone number or contact the recruitment team.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 7. Rate limit (this endpoint is public and calls the AI)
            |--------------------------------------------------------------------------
            */

            $validations = new ApplicationValidation();
            $ipAddress = $this->clientIp();

            $validations->purgeExpiredInvalid(
                max(1, (int) config('app.rejected_applicant_retention_days', 30))
            );
            $validations->purgeExpiredUnlinked();


            /*
            |--------------------------------------------------------------------------
            | 8. Stage the files (no applicant or application exists yet)
            |--------------------------------------------------------------------------
            |
            | Files wait in storage/uploads/pending/{reference}/ with real file
            | extensions so the AI service can read them.
            |
            */

            $projectRoot = dirname(__DIR__, 3);
            $pendingRoot = $projectRoot . '/storage/uploads/pending';

            $this->purgeStalePending($pendingRoot);

            $reference = 'VAL-' . strtoupper(bin2hex(random_bytes(5)));

            $applicantName = trim(implode(' ', array_filter([
                $firstName,
                $middleName,
                $lastName,
            ])));

            $validationId = $validations->reserveAttempt([
                'submission_reference' => $reference,
                'posting_id' => (int) $job['posting_id'],
                'applicant_name' => $applicantName,
                'applicant_email' => $email,
                'ip_address' => $ipAddress,
            ], self::MAX_INVALID_ATTEMPTS, self::MAX_ATTEMPTS_PER_HOUR);

            $stagingDirectory = $pendingRoot . '/' . $reference;

            if (
                !mkdir($stagingDirectory, 0755, true) &&
                !is_dir($stagingDirectory)
            ) {
                throw new \Exception('Unable to prepare your application for processing.');
            }

            $stagedResumePath = $stagingDirectory . '/resume.pdf';

            if (!move_uploaded_file($resume['tmp_name'], $stagedResumePath)) {
                throw new \Exception('Unable to save the uploaded resume.');
            }

            $stagedDocuments = [];

            foreach ($academicFiles as $position => $academicFile) {

                $stagedPath = $stagingDirectory
                    . '/document_' . $position . '.' . $academicFile['extension'];

                if (!move_uploaded_file($academicFile['file']['tmp_name'], $stagedPath)) {
                    throw new \Exception(
                        'Unable to save one of the uploaded certificates or diplomas.'
                    );
                }

                $stagedDocuments[] = [
                    'path' => $stagedPath,
                    'label' => $academicFile['original_file_name'],
                ];
            }

            $stagedCoverPath = null;

            if ($coverExtension !== null) {

                $stagedCoverPath = $stagingDirectory . '/cover.' . $coverExtension;

                if (!move_uploaded_file($coverLetter['tmp_name'], $stagedCoverPath)) {
                    throw new \Exception('Unable to save the uploaded cover letter.');
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 9. AI validation
            |--------------------------------------------------------------------------
            */

            $validation = (new ApplicationValidationService())->validate(
                $stagedResumePath,
                $stagedDocuments,
                [
                    'name' => $applicantName,
                    'email' => $email,
                    'phone' => $phone,
                ],
                $job
            );

            $validations->updateResult($validationId, [
                'validation_status' => $validation['status'],
                'identity_status' => $validation['identity_status'],
                'education_status' => $validation['education_status'],
                'experience_status' => $validation['experience_status'],
                'credential_status' => $validation['credential_status'],
                'consistency_score' => $validation['consistency_score'],
                'findings' => $validation['findings'],
                'validation_summary' => $validation['summary'],
                'ai_model' => $validation['ai_model'],
            ]);


            /*
            |--------------------------------------------------------------------------
            | 10. INVALID: stop here. No applicant, no application, no files kept.
            |--------------------------------------------------------------------------
            */

            if ($validation['status'] === 'INVALID') {

                $this->removeDirectory($stagingDirectory);
                $stagingDirectory = null;

                throw new \Exception(
                    'We could not verify that the information and documents you submitted are consistent with each other, so your application was not accepted. Please make sure your resume and certificates belong to you and match the details you entered, then try again. Reference: '
                    . $reference . '.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 11. VALID / REVIEW: find or create applicant
            |--------------------------------------------------------------------------
            */

            $database = \Core\Database::connection();
            $database->beginTransaction();

            if ($existingApplicant) {

                $applicantId = (int) $existingApplicant['applicant_id'];

            } else {

                $applicantId = $this->applicant->createApplicant([
                    'first_name' => $firstName,
                    'middle_name' => $middleName !== '' ? $middleName : null,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $phone,
                    'address' => $address !== '' ? $address : null,
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 12. Move staged files into permanent storage
            |--------------------------------------------------------------------------
            */

            $uploadDirectory = $projectRoot . '/storage/uploads/applications/';

            if (!is_dir($uploadDirectory)) {

                if (
                    !mkdir($uploadDirectory, 0755, true) &&
                    !is_dir($uploadDirectory)
                ) {
                    throw new \Exception('Unable to create the application upload directory.');
                }
            }

            $resumeFileName = 'resume_' . $applicantId . '_' . bin2hex(random_bytes(8)) . '.pdf';

            $this->moveStagedFile(
                $stagedResumePath,
                $uploadDirectory . $resumeFileName,
                'resume'
            );

            $savedFilePaths[] = $uploadDirectory . $resumeFileName;

            $academicDocumentsForDatabase = [];
            $finalAcademicFileNames = [];

            foreach ($academicFiles as $position => $academicFile) {

                $academicFileName = 'academic_' . $applicantId . '_'
                    . bin2hex(random_bytes(8)) . '.' . $academicFile['extension'];

                $this->moveStagedFile(
                    $stagedDocuments[$position]['path'],
                    $uploadDirectory . $academicFileName,
                    'certificate or diploma'
                );

                $savedFilePaths[] = $uploadDirectory . $academicFileName;

                $finalAcademicFileNames[$position] = $academicFileName;

                $academicDocumentsForDatabase[] = [
                    'file_name' => $academicFileName,
                    'original_file_name' => $academicFile['original_file_name'],
                ];
            }

            $coverLetterFileName = null;

            if ($stagedCoverPath !== null) {

                $coverLetterFileName = 'cover_' . $applicantId . '_'
                    . bin2hex(random_bytes(8)) . '.' . $coverExtension;

                $this->moveStagedFile(
                    $stagedCoverPath,
                    $uploadDirectory . $coverLetterFileName,
                    'cover letter'
                );

                $savedFilePaths[] = $uploadDirectory . $coverLetterFileName;
            }

            $this->removeDirectory($stagingDirectory);
            $stagingDirectory = null;


            /*
            |--------------------------------------------------------------------------
            | 13. Create application and link the validation record
            |--------------------------------------------------------------------------
            */

            $applicationId = $this->applicant->createApplication([
                'applicant_id' => $applicantId,
                'posting_id' => $job['posting_id'],
                'resume_file' => $resumeFileName,
                'cover_letter_file' => $coverLetterFileName,
                'academic_documents' => $academicDocumentsForDatabase,
            ]);

            $validations->linkToApplication($validationId, (int) $applicationId);


            /*
            |--------------------------------------------------------------------------
            | 14. Credential results
            |--------------------------------------------------------------------------
            |
            | The validation call already read every credential, so its results
            | are stored and the separate academic evaluation is skipped. If the
            | AI was unavailable, fall back to the existing evaluation service.
            |
            */

            $academicRows = [];

            if (!empty($academicDocumentsForDatabase)) {

                $academicRows = $validation['ai_available']
                    ? (new ApplicationValidationService())->academicRows(
                        $validation,
                        $finalAcademicFileNames
                    )
                    : [];

                if (!empty($academicRows)) {
                    $validations->applyToAcademicDocuments((int) $applicationId, $academicRows);
                }
            }

            $database->commit();

            if (!empty($academicDocumentsForDatabase)) {

                if (count($academicRows) < count($academicDocumentsForDatabase)) {

                    try {

                        (new AcademicDocumentEvaluationService())->evaluate((int) $applicationId);

                    } catch (\Throwable $e) {

                        error_log('Academic document evaluation failed: ' . $e->getMessage());

                        $_SESSION['academic_validation_error'] =
                            'Application submitted successfully, but certificate verification could not run right now.';
                    }
                }
            }


            /*
            |--------------------------------------------------------------------------
            | 15. AI screening (unchanged)
            |--------------------------------------------------------------------------
            */

            try {

                (new ResumeEvaluationService())->evaluate((int) $applicationId);

            } catch (\Throwable $e) {

                error_log('Resume AI evaluation failed: ' . $e->getMessage());

                $_SESSION['ai_screening_error'] =
                    'Application submitted successfully, but AI screening could not run right now.';
            }


            /*
            |--------------------------------------------------------------------------
            | 15b. Application-received email (G2)
            |
            | Sent after the commit so a mail failure never undoes the
            | submission; the applicant still sees the success page
            | and the failure is logged for HR to notice.
            |--------------------------------------------------------------------------
            */

            try {

                (new MailService())->sendApplicationReceived([
                    'email'    => $email,
                    'fullname' => trim(
                        $firstName . ' ' . $middleName . ' ' . $lastName
                    ),
                    'position' => (string) $job['title']
                ]);

            } catch (\Throwable $e) {

                error_log(
                    'Application-received email failed for application '
                    . (int) $applicationId . ': ' . $e->getMessage()
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 16. Success
            |--------------------------------------------------------------------------
            */

            header("Location: ?page=application-success");
            exit;

        } catch (\Throwable $e) {

            $database = \Core\Database::connection();

            if ($database->inTransaction()) {
                $database->rollBack();
            }

            if ($validationId !== null && isset($validations) && $validations instanceof ApplicationValidation) {
                try {
                    $validations->markProcessingAsReview(
                        $validationId,
                        'Submission processing did not complete. This attempt was not accepted.'
                    );
                } catch (\Throwable $updateError) {
                    error_log('Unable to finalize application validation attempt: ' . $updateError->getMessage());
                }
            }

            foreach ($savedFilePaths as $savedPath) {

                if (is_string($savedPath) && file_exists($savedPath)) {
                    @unlink($savedPath);
                }
            }

            if ($stagingDirectory !== null) {
                $this->removeDirectory($stagingDirectory);
            }

            /*
             * Never show raw database errors to applicants.
             */
            if ($e instanceof \PDOException) {

                error_log('Application submission database error: ' . $e->getMessage());

                $_SESSION['application_error'] =
                    'We could not process your application right now. Please try again later.';

            } else {

                $_SESSION['application_error'] = $e->getMessage();
            }

            header("Location: ?page=apply&token=" . urlencode($token));
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


        /* Pipeline data shown in the phased review UI. */
        $pipeline = new PipelineService();

        $evaluations =
            $pipeline->evaluationsFor(
                (int) $applicant['application_id']
            );

        $requirements =
            $pipeline->requirements()->forApplication(
                (int) $applicant['application_id']
            );

        $offer =
            (new JobOffer())->forApplication(
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

        if (($applicant['application_status'] ?? '') !== 'Interview') {
            $_SESSION['error'] =
                'Qualify the applicant before scheduling an interview.';

            header(
                'Location:?page=review&id=' . $applicantId
            );

            exit;
        }


        try {
            $this->applicant
                ->scheduleInterview(
                    $_POST
                );
        } catch (\Throwable $e) {
            $_SESSION['error'] = $e->getMessage();

            header(
                'Location:?page=review&id=' . $applicantId
            );

            exit;
        }


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


            /*
             * Hiring the last vacancy closes the posting. Remove that posting's
             * rejected applications straight away; never let a cleanup failure
             * break the hiring response.
             */
            try {
                (new ApplicationRetentionService())->run();
            } catch (\Throwable $retentionError) {
                error_log(
                    'Application retention cleanup failed after hire: ' .
                    $retentionError->getMessage()
                );
            }


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
            'HR',
            'MGR'
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


            /*
             * Every rejection carries a reason category (stored
             * HR-internal in the timeline) plus an optional note.
             */
            $context = $this->applicant->getApplicationForPipeline($applicationId);

            if (
                !$context
                || !in_array(
                    $context['application_status'],
                    ['Submitted', 'Under Review', 'Interview', 'Selection'],
                    true
                )
            ) {
                throw new \Exception(
                    'This application cannot be rejected at its current stage.'
                );
            }

            if (
                Auth::role() === 'MGR'
                && in_array(
                    $context['application_status'],
                    ['Submitted', 'Under Review'],
                    true
                )
            ) {
                throw new \Exception(
                    'Only HR can reject an application during application review.'
                );
            }

            $reasonCategory =
                trim((string) ($_POST['reason_category'] ?? ''));

            if (
                $reasonCategory !== ''
                && !in_array(
                    $reasonCategory,
                    PipelineService::REJECTION_REASONS,
                    true
                )
            ) {
                throw new \Exception(
                    'Select a rejection reason.'
                );
            }

            if ($reasonCategory === '') {
                $reasonCategory = 'Other';
            }


            $note =
                trim((string) ($_POST['reason_note'] ?? ''));


            /*
             * A withdrawal ends the process by the applicant's own
             * choice, so no rejection email is sent.
             */
            $notifyCandidate =
                $reasonCategory !== 'Withdrew';


            $success =
                $this->applicant
                    ->rejectApplication(
                        $applicationId,
                        $reasonCategory,
                        $note !== '' ? $note : null,
                        Auth::userId(),
                        $notifyCandidate
                    );


            if (!$success) {
                throw new \Exception(
                    'Unable to reject this application. It may already be hired or may not exist.'
                );
            }


            /*
             * The rejection is committed; the email is sent after the
             * fact and a failure is logged and reported rather than
             * undoing the rejection. When no notification is expected
             * (e.g. the applicant withdrew) the flag reports false.
             */
            $emailSent = false;


            if ($notifyCandidate) {

                $applicant =
                    $this->applicant
                        ->getApplicationForPipeline(
                            $applicationId
                        );


                try {

                    (new MailService())->sendRejectionEmail([
                        'email'    => $applicant['email'],
                        'fullname' => $applicant['fullname'],
                        'position' => $applicant['position']
                    ]);

                    $emailSent = true;

                } catch (\Throwable $mailError) {

                    error_log(
                        'Rejection email failed for application '
                        . $applicationId . ': '
                        . $mailError->getMessage()
                    );

                    $emailSent = false;
                }
            }


            echo json_encode([

                'success' =>
                    true,

                'message' =>
                    'Applicant has been rejected.',

                'email_sent' =>
                    $emailSent
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
    | Qualify Applicant
    |--------------------------------------------------------------------------
    */

    public function qualify()
    {
        Auth::requireRole([
            'ADMIN',
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
                    ->qualifyApplication(
                        $applicationId
                    );


            if (!$success) {
                throw new \Exception(
                    'Unable to qualify this application. It may already be in the interview stage or may not exist.'
                );
            }


            echo json_encode([

                'success' =>
                    true,

                'message' =>
                    'Applicant has been qualified for interview.'
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
     * Move a staged file into permanent storage.
     */
    private function moveStagedFile(string $from, string $to, string $what): void
    {
        if (!@rename($from, $to)) {
            throw new \Exception('Unable to save the uploaded ' . $what . '.');
        }
    }

    /**
     * Delete a directory and its files.
     */
    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }

        foreach (scandir($directory) ?: [] as $item) {

            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory . DIRECTORY_SEPARATOR . $item;

            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                @unlink($path);
            }
        }

        @rmdir($directory);
    }

    /**
     * Remove staging folders left behind by a crashed request.
     */
    private function purgeStalePending(string $pendingRoot): void
    {
        if (!is_dir($pendingRoot)) {
            return;
        }

        foreach (glob($pendingRoot . '/VAL-*', GLOB_ONLYDIR) ?: [] as $directory) {

            if (filemtime($directory) < time() - 86400) {
                $this->removeDirectory($directory);
            }
        }
    }

    /**
     * Client IP for rate limiting. REMOTE_ADDR cannot be spoofed by the
     * client; if the site sits behind a proxy or CDN this will be the
     * proxy's address and the per-IP limits will be shared.
     */
    private function clientIp(): ?string
    {
        $ip = trim((string) ($_SERVER['REMOTE_ADDR'] ?? ''));

        return $ip !== '' ? substr($ip, 0, 45) : null;
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