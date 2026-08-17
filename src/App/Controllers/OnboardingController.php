<?php

namespace App\Controllers;

use App\Models\Onboarding;

class OnboardingController
{
    /*
    |--------------------------------------------------------------------------
    | ONBOARDING LIST
    |--------------------------------------------------------------------------
    */

    public function onboarding()
    {
        $model = new Onboarding();

        $employees = $model->getAllOnboarding();

        $totalNewHires = $model->countAll();

        $pending = $model->countStatus("Pending");

        $ongoing = $model->countStatus("Ongoing");

        $completed = $model->countStatus("Completed");


        require '../resources/views/onboarding/index.php';
    }


    /*
    |--------------------------------------------------------------------------
    | ONBOARDING DETAILS
    |--------------------------------------------------------------------------
    */

    public function onboardingView()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: /hr1/public/?page=login");
            exit;
        }


        $model = new Onboarding();


        /*
         * Get employee ID from URL.
         *
         * Example:
         *
         * ?page=onboarding-view&employee_id=5
         */
        $employeeId = filter_input(
            INPUT_GET,
            'employee_id',
            FILTER_VALIDATE_INT
        );


        if (!$employeeId) {
            header("Location: /hr1/public/?page=onboarding");
            exit;
        }


        /*
         * Get employee + onboarding information.
         */
        $employee = $model->getOnboardingDetails(
            (int) $employeeId
        );


        if (!$employee) {
            header("Location: /hr1/public/?page=onboarding");
            exit;
        }


        /*
         * Get onboarding ID.
         */
        $onboardingId = (int) $employee['onboarding_id'];


        /*
         * Get requested/submitted/verified documents.
         */
        $documents = $model->getDocuments(
            $onboardingId
        );


        /*
         * Get document statistics.
         */
        $documentProgress = $model->getDocumentProgress(
            $onboardingId
        );


        /*
         |--------------------------------------------------------------------------
         | ONBOARDING PROGRESS
         |--------------------------------------------------------------------------
         |
         | 1. Employee Hired
         | 2. Employee Record Created
         | 3. Documents Requested
         | 4. Documents Verified
         | 5. Onboarding Completed
         |
         */

        $completedSteps = 2;

        $totalSteps = 5;


        /*
         * Documents requested.
         */
        if ($documentProgress['total'] > 0) {
            $completedSteps++;
        }


        /*
         * All documents verified.
         */
        if (
            $documentProgress['total'] > 0 &&
            $documentProgress['verified'] ===
            $documentProgress['total']
        ) {
            $completedSteps++;
        }


        /*
         * Fully completed onboarding.
         */
        if (
            $employee['onboarding_status'] === 'Completed'
        ) {
            $completedSteps = 5;
        }


        $progress = (int) round(
            ($completedSteps / $totalSteps) * 100
        );


        /*
         |--------------------------------------------------------------------------
         | PROGRESS TIMELINE
         |--------------------------------------------------------------------------
         */

        $documentsRequested =
            $documentProgress['total'] > 0;


        $documentsVerified =
            $documentProgress['total'] > 0 &&
            $documentProgress['verified'] ===
            $documentProgress['total'];


        $progressSteps = [

            [
                'title' =>
                    'Employee Hired',

                'description' =>
                    'Applicant was approved and marked as Hired.',

                'date' =>
                    !empty($employee['hire_date'])
                        ? date(
                            'M d, Y',
                            strtotime($employee['hire_date'])
                        )
                        : null,

                'status' =>
                    'completed'
            ],


            [
                'title' =>
                    'Employee Record Created',

                'description' =>
                    'Employee record was automatically created.',

                'date' =>
                    !empty($employee['hire_date'])
                        ? date(
                            'M d, Y',
                            strtotime($employee['hire_date'])
                        )
                        : null,

                'status' =>
                    'completed'
            ],


            [
                'title' =>
                    'Documents Requested',

                'description' =>
                    'Required onboarding documents have been requested.',

                'date' =>
                    $documentsRequested
                        ? 'Documents requested'
                        : null,

                'status' =>
                    $documentsRequested
                        ? 'completed'
                        : 'current'
            ],


            [
                'title' =>
                    'Documents Verified',

                'description' =>
                    'All required documents must be verified by HR Staff.',

                'date' =>
                    $documentsVerified
                        ? 'All documents verified'
                        : null,

                'status' =>
                    $documentsVerified
                        ? 'completed'
                        : (
                            $documentsRequested
                                ? 'current'
                                : 'pending'
                        )
            ],


            [
                'title' =>
                    'Onboarding Completed',

                'description' =>
                    'Employee has completed all onboarding requirements.',

                'date' =>
                    $employee['onboarding_status'] === 'Completed'
                        ? 'Completed'
                        : null,

                'status' =>
                    $employee['onboarding_status'] === 'Completed'
                        ? 'completed'
                        : 'pending'
            ]

        ];


        /*
         |--------------------------------------------------------------------------
         | ACTIVITY LOG
         |--------------------------------------------------------------------------
         |
         | Your current database does not have an activity-log table,
         | so we only show activities that can be derived safely
         | from the current onboarding data.
         |
         */

        $activities = [];


        /*
         * Employee hiring.
         */
        $activities[] = [

            'text' =>
                'Employee record was created after hiring.',

            'date' =>
                !empty($employee['hire_date'])
                    ? date(
                        'M d, Y',
                        strtotime($employee['hire_date'])
                    )
                    : 'Date unavailable'

        ];


        /*
         * Orientation.
         */
        if (!empty($employee['orientation_date'])) {

            $activities[] = [

                'text' =>
                    'Employee orientation was recorded.',

                'date' =>
                    date(
                        'M d, Y',
                        strtotime(
                            $employee['orientation_date']
                        )
                    )

            ];
        }


        /*
         * Documents.
         */
        foreach ($documents as $document) {

            $activities[] = [

                'text' =>
                    'Document "' .
                    $document['document_name'] .
                    '" is ' .
                    strtolower($document['status']) .
                    '.',

                'date' =>
                    'Onboarding document'

            ];
        }


        /*
         |--------------------------------------------------------------------------
         | VIEW
         |--------------------------------------------------------------------------
         */

        require '../resources/views/onboarding/onboarding-view.php';
    }


    /*
    |--------------------------------------------------------------------------
    | REQUEST DOCUMENT
    |--------------------------------------------------------------------------
    */

    public function requestDocument()
    {
        if (!isset($_SESSION['user_id'])) {

            header("Location: /hr1/public/?page=login");
            exit;
        }

        $onboardingId = filter_input(
            INPUT_POST,
            'onboarding_id',
            FILTER_VALIDATE_INT
        );

        $documentName = trim(
            $_POST['document_name'] ?? ''
        );

        if (!$onboardingId) {

            die('Invalid onboarding record.');
        }

        if ($documentName === '') {

            die('Please enter a document name.');
        }

        if (mb_strlen($documentName) > 150) {

            die('Document name must not exceed 150 characters.');
        }

        $model = new Onboarding();

        $result = $model->requestDocument(
            (int) $onboardingId,
            $documentName
        );

        if (!$result['success']) {

            die($result['message']);
        }

        /*
        * Return to the employee onboarding page.
        */
        header(
            "Location: ?page=onboarding-view&employee_id=" .
            $this->getEmployeeIdFromOnboarding(
                (int) $onboardingId
            )
        );

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY DOCUMENT
    |--------------------------------------------------------------------------
    */

    public function verifyDocument()
    {
        if (!isset($_SESSION['user_id'])) {

            $this->jsonResponse([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 401);

            return;
        }


        $documentId = filter_input(
            INPUT_POST,
            'document_id',
            FILTER_VALIDATE_INT
        );


        if (!$documentId) {

            $this->jsonResponse([
                'success' => false,
                'message' =>
                    'Invalid document.'
            ]);

            return;
        }


        $model = new Onboarding();


        $result = $model->verifyDocument(
            (int) $documentId
        );


        $this->jsonResponse($result);
    }


    /*
    |--------------------------------------------------------------------------
    | JSON RESPONSE
    |--------------------------------------------------------------------------
    */

    private function jsonResponse(
        array $data,
        int $statusCode = 200
    ): void {

        http_response_code($statusCode);

        header(
            'Content-Type: application/json; charset=utf-8'
        );

        echo json_encode($data);

        exit;
    }

    private function getEmployeeIdFromOnboarding(
        int $onboardingId
    ): int {

        $db = \Core\Database::connection();

        $stmt = $db->prepare("
            SELECT employee_id
            FROM employees
            WHERE application_id = (
                SELECT application_id
                FROM onboarding
                WHERE onboarding_id = ?
                LIMIT 1
            )
            LIMIT 1
        ");

        $stmt->execute([
            $onboardingId
        ]);

        return (int) $stmt->fetchColumn();
    }
}