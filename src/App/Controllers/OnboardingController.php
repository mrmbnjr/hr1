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

        $ongoing = $model->countStatus("In Progress");

        $completed = $model->countStatus("Completed");


        require BASE_PATH . '/resources/views/onboarding/index.php';
    }


    /*
    |--------------------------------------------------------------------------
    | ONBOARDING DETAILS
    |--------------------------------------------------------------------------
    */

    public function onboardingView()
    {
        if (!isset($_SESSION['user_id'])) {
            header("Location: " . url("?page=login"));
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
            header("Location: " . url("?page=onboarding"));
            exit;
        }


        /*
         * Get employee + onboarding information.
         */
        $employee = $model->getOnboardingDetails(
            (int) $employeeId
        );


        if (!$employee) {
            header("Location: " . url("?page=onboarding"));
            exit;
        }


        /*
         * Get onboarding ID.
         */
        $onboardingId = (int) $employee['onboarding_id'];


        /*
         * Get requested/submitted/accepted documents.
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
         | ONBOARDING PROGRESS (NEW 5-PHASE FLOW)
         |--------------------------------------------------------------------------
         |
         | Phase 1: System & Access Setup
         |    - System automatically checks that the employee account and access
         |      are properly created
         |
         | Phase 2: Orientation & Setup
         |    - System checks the employee's orientation/setup information
         |
         | Phase 3: Document Submission
         |    - System automatically checks whether all required documents have
         |      been submitted
         |
         | Phase 4: Document Verification
         |    - HR/Admin verifies the submitted documents
         |
         | Phase 5: Final Onboarding Completion
         |    - System automatically completes onboarding when everything
         |      required is finished
         |
         */

        // Initialize phases if they don't exist
        $model->initializePhases($onboardingId);

        // Get all phases for this onboarding
        $phases = $model->getOnboardingPhases($onboardingId);

        // Auto-update phase statuses based on onboarding data
        // Phase 1: System & Access Setup - Mark as completed if employee record exists
        if ($employee['employee_id']) {
            $model->updatePhaseStatus($onboardingId, 1, 'completed');
        }

        // Phase 2: Orientation & Setup - Mark as in-progress if orientation date is set
        if (!empty($employee['orientation_date'])) {
            $model->updatePhaseStatus($onboardingId, 2, 'completed');
        } elseif ($employee['employee_id']) {
            $model->updatePhaseStatus($onboardingId, 2, 'in-progress');
        }

        // Phase 3: Document Submission - Check if documents are submitted
        if ($documentProgress['total'] > 0 && $documentProgress['submitted'] > 0) {
            $model->updatePhaseStatus($onboardingId, 3, 'in-progress');
        }
        if ($documentProgress['total'] > 0 && $documentProgress['submitted'] >= $documentProgress['total']) {
            $model->updatePhaseStatus($onboardingId, 3, 'completed');
        } elseif ($documentProgress['total'] > 0) {
            // Documents are pending
            $model->updatePhaseStatus($onboardingId, 3, 'in-progress');
        }

        // Phase 4: Document Verification - Check if all documents are accepted
        if ($documentProgress['total'] > 0 && $documentProgress['accepted'] > 0) {
            $model->updatePhaseStatus($onboardingId, 4, 'in-progress');
        }
        if ($documentProgress['total'] > 0 && $documentProgress['accepted'] >= $documentProgress['total']) {
            $model->updatePhaseStatus($onboardingId, 4, 'completed');
        }

        // Phase 5: Final Onboarding Completion - Only complete once all prior phases are done.
        $phaseRows = $model->getOnboardingPhases($onboardingId);
        $allPriorPhasesComplete = true;

        foreach ($phaseRows as $phaseRow) {
            if ((int) $phaseRow['phase_number'] < 5 && strtolower((string) $phaseRow['status']) !== 'completed') {
                $allPriorPhasesComplete = false;
                break;
            }
        }

        if ($employee['onboarding_status'] === 'Completed' && $allPriorPhasesComplete) {
            $model->updatePhaseStatus($onboardingId, 5, 'completed');
        } elseif ($phaseRows) {
            $model->updatePhaseStatus($onboardingId, 5, 'in-progress');
        }

        // Refresh phases after auto-updates
        $phases = $model->getOnboardingPhases($onboardingId);
        $progressSteps = [];
        foreach ($phases as $phase) {
            $phaseStatus = strtolower($phase['status']);
            
            $progressSteps[] = [
                'title' => $phase['phase_name'],
                'description' => $phase['phase_description'],
                'date' => $phase['completed_at'] 
                    ? date('M d, Y', strtotime($phase['completed_at']))
                    : null,
                'status' => $phaseStatus,
                'phase_number' => $phase['phase_number']
            ];
        }

        // Calculate overall progress from phases
        $progress = $model->calculateProgressFromPhases($onboardingId);


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

        require BASE_PATH . '/resources/views/onboarding/onboarding-view.php';
    }


    /*
    |--------------------------------------------------------------------------
    | REQUEST DOCUMENT
    |--------------------------------------------------------------------------
    */

    public function requestDocument()
    {
        if (!isset($_SESSION['user_id'])) {

            header("Location: " . url("?page=login"));
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

        $description = trim(
            $_POST['description'] ?? ''
        );

        /*
        |--------------------------------------------------------------------------
        | Optional scheduling / categorisation
        |--------------------------------------------------------------------------
        |
        | These feed the employee Assigned Requests page. Every one of them
        | is optional, so the existing "document name + instructions" flow
        | still works exactly as before.
        |
        */

        $requestType = trim(
            $_POST['request_type'] ?? ''
        );

        $actionType = trim(
            $_POST['action_type'] ?? ''
        );

        $priority = trim(
            $_POST['priority'] ?? ''
        );

        $dueDate = trim(
            $_POST['due_date'] ?? ''
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

        if (
            $requestType !== ''
            && !array_key_exists(
                $requestType,
                Onboarding::REQUEST_TYPES
            )
        ) {

            die('Please select a valid request type.');
        }

        if (!in_array($actionType, Onboarding::ACTION_TYPES, true)) {

            $actionType = 'document';
        }

        if (!in_array($priority, Onboarding::PRIORITIES, true)) {

            $priority = 'Normal';
        }

        if ($dueDate !== '') {

            $parsedDate = \DateTime::createFromFormat(
                'Y-m-d',
                $dueDate
            );

            if (
                $parsedDate === false
                || $parsedDate->format('Y-m-d') !== $dueDate
            ) {

                die('Please enter a valid due date.');
            }
        }

        $model = new Onboarding();

        $result = $model->requestDocument(
            (int) $onboardingId,
            $documentName,
            $description,
            $requestType !== '' ? $requestType : null,
            $actionType,
            $priority,
            $dueDate !== '' ? $dueDate : null,
            (int) $_SESSION['user_id']
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

    public function updateDocument()
    {
        $this->requireLogin();

        $documentId = filter_input(
            INPUT_POST,
            'document_id',
            FILTER_VALIDATE_INT
        );

        $documentName = trim(
            $_POST['document_name'] ?? ''
        );

        $description = trim(
            $_POST['description'] ?? ''
        );

        $requestType = trim(
            $_POST['request_type'] ?? ''
        );

        $actionType = trim(
            $_POST['action_type'] ?? ''
        );

        $priority = trim(
            $_POST['priority'] ?? ''
        );

        $dueDate = trim(
            $_POST['due_date'] ?? ''
        );

        if (!$documentId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid document.'
            ]);
            return;
        }

        if ($documentName === '') {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Document name cannot be empty.'
            ]);
            return;
        }

        if (mb_strlen($documentName) > 150) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Document name must not exceed 150 characters.'
            ]);
            return;
        }

        if (mb_strlen($description) > 500) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Instructions must not exceed 500 characters.'
            ]);
            return;
        }

        if (
            $requestType !== ''
            && !array_key_exists(
                $requestType,
                Onboarding::REQUEST_TYPES
            )
        ) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Please select a valid request type.'
            ]);
            return;
        }

        if (!in_array($actionType, Onboarding::ACTION_TYPES, true)) {
            $actionType = 'document';
        }

        if (!in_array($priority, Onboarding::PRIORITIES, true)) {
            $priority = 'Normal';
        }

        if ($dueDate !== '') {
            $parsedDate = \DateTime::createFromFormat(
                'Y-m-d',
                $dueDate
            );

            if (
                $parsedDate === false
                || $parsedDate->format('Y-m-d') !== $dueDate
            ) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'Please enter a valid due date.'
                ]);
                return;
            }
        }

        $model = new Onboarding();
        $result = $model->updateDocumentName(
            (int) $documentId,
            $documentName,
            $description,
            $requestType !== '' ? $requestType : null,
            $actionType,
            $priority,
            $dueDate !== '' ? $dueDate : null
        );

        $this->jsonResponse($result);
    }
    
    public function deleteDocument()
    {
        $this->requireLogin();

        $documentId = filter_input(
            INPUT_POST,
            'document_id',
            FILTER_VALIDATE_INT
        );

        if (!$documentId) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid document.'
            ]);
            return;
        }

        $model = new Onboarding();
        $result = $model->deleteDocument((int) $documentId);

        $this->jsonResponse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY DOCUMENT
    |--------------------------------------------------------------------------
    */

    public function verifyDocument()
    {
        $this->requireLogin();

        $documentId = filter_input(
            INPUT_POST,
            'document_id',
            FILTER_VALIDATE_INT
        );

        $remarks = trim(
            $_POST['remarks'] ?? ''
        );

        if (!$documentId) {

            $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid document.'
            ]);

            return;
        }

        if (mb_strlen($remarks) > 1000) {

            $this->jsonResponse([
                'success' => false,
                'message' => 'Remarks must not exceed 1000 characters.'
            ]);

            return;
        }

        $model = new Onboarding();

        $result = $model->verifyDocument(
            (int) $documentId,
            $remarks,
            (int) $_SESSION['user_id']
        );

        $this->jsonResponse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT DOCUMENT
    |--------------------------------------------------------------------------
    */

    public function rejectDocument()
    {
        $this->requireLogin();

        $documentId = filter_input(
            INPUT_POST,
            'document_id',
            FILTER_VALIDATE_INT
        );

        $remarks = trim(
            $_POST['remarks'] ?? ''
        );

        if (!$documentId) {

            $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid document.'
            ]);

            return;
        }

        /*
        * Rejection remarks are required.
        */
        if ($remarks === '') {

            $this->jsonResponse([
                'success' => false,
                'message' => 'Rejection remarks are required.'
            ]);

            return;
        }

        if (mb_strlen($remarks) > 1000) {

            $this->jsonResponse([
                'success' => false,
                'message' => 'Remarks must not exceed 1000 characters.'
            ]);

            return;
        }

        $model = new Onboarding();

        $result = $model->rejectDocument(
            (int) $documentId,
            $remarks,
            (int) $_SESSION['user_id']
        );

        $this->jsonResponse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | MARK DOCUMENT COMPLETED
    |--------------------------------------------------------------------------
    |
    | An approved document is finalised here. The request moves from the
    | confirmation stage (Approved) to Completed, meaning the requirement has
    | actually been carried out — the final transition in the shared status
    | model. Only documents currently in the Approved state may be completed.
    |
    */

    public function markCompleted()
    {
        $this->requireLogin();

        $documentId = filter_input(
            INPUT_POST,
            'document_id',
            FILTER_VALIDATE_INT
        );

        $remarks = trim(
            $_POST['remarks'] ?? ''
        );

        if (!$documentId) {

            $this->jsonResponse([
                'success' => false,
                'message' => 'Invalid document.'
            ]);

            return;
        }

        if (mb_strlen($remarks) > 1000) {

            $this->jsonResponse([
                'success' => false,
                'message' => 'Remarks must not exceed 1000 characters.'
            ]);

            return;
        }

        $model = new Onboarding();

        $result = $model->markCompleted(
            (int) $documentId,
            $remarks,
            (int) $_SESSION['user_id']
        );

        $this->jsonResponse($result);
    }

    /*
    |--------------------------------------------------------------------------
    | AUTH GUARD
    |--------------------------------------------------------------------------
    */

    private function requireLogin(): void
    {
        if (!isset($_SESSION['user_id'])) {
            $this->jsonResponse([
                'success' => false,
                'message' => 'Unauthorized.'
            ], 401);
        }
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