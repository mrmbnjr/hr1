<?php

namespace App\Controllers;

use App\Models\EmployeeRequests;
use App\Models\PasswordResetRequest;
use App\Services\AssignedDocumentService;
use App\Services\Auth;

class EmployeeRequestsController
{
    private EmployeeRequests $employeeRequests;

    private PasswordResetRequest $passwordResetRequest;

    private AssignedDocumentService $documents;


    public function __construct()
    {
        $this->employeeRequests =
            new EmployeeRequests();

        $this->passwordResetRequest =
            new PasswordResetRequest();

        $this->documents =
            new AssignedDocumentService();
    }


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE REQUESTS PAGE
    |--------------------------------------------------------------------------
    */

    public function employeeRequests(): void
    {
        Auth::requireRole([
            'ADMIN',
            'HR',
            'MGR'
        ]);

        $requests =
            $this->employeeRequests->getAllRequests();

        $departments =
            $this->employeeRequests->getDepartments();


        /*
        |--------------------------------------------------------------------------
        | Password reset requests ("Contact HR")
        |--------------------------------------------------------------------------
        |
        | Shown as a second tab, but only to HR and Admin — managers can see
        | employee requests without gaining access to account recovery data.
        |
        */

        $canViewPasswordResets =
            Auth::hasAnyRole([
                'ADMIN',
                'HR'
            ]);

        $passwordResetRequests = [];
        $passwordResetPendingCount = 0;

        if ($canViewPasswordResets) {

            $passwordResetRequests =
                $this->passwordResetRequest->getAll();

            $passwordResetPendingCount =
                $this->passwordResetRequest->getPendingCount();
        }


        /*
        |--------------------------------------------------------------------------
        | Onboarding document requests (files requested by HR / Admin)
        |--------------------------------------------------------------------------
        |
        | Every file HR or Administration requested during onboarding is shown
        | in the same queue, so submitted documents land in HR's review list
        | instead of only living inside the Onboarding module.
        |
        | has_file tells the view whether the submitted file is actually on
        | disk, so it never renders a dead download link.
        |
        */

        $documentRequests =
            $this->employeeRequests
                ->getOnboardingDocumentRequests();

        foreach ($documentRequests as &$document) {

            $storedName = trim(
                (string) ($document['file_path'] ?? '')
            );

            $document['has_file'] =
                $storedName !== ''
                && $this->documents->resolve($storedName) !== null;
        }

        unset($document);


        /*
         * Managers may review the queue but only HR and Admin may verify or
         * reject a submitted document.
         */

        $canManageDocuments =
            Auth::hasAnyRole([
                'ADMIN',
                'HR'
            ]);


        require BASE_PATH . '/resources/views/employee-requests/index.php';
    }


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE — MY REQUESTS PAGE
    |--------------------------------------------------------------------------
    */

    public function myRequests(): void
    {
        Auth::requireRole([
            'EMP'
        ]);

        $userId =
            (int) Auth::userId();

        $employeeId =
            $this->employeeRequests
                ->getEmployeeIdByUserId(
                    $userId
                );


        /*
        |--------------------------------------------------------------------------
        | Account is not connected to an employee
        |--------------------------------------------------------------------------
        */

        if (!$employeeId) {

            http_response_code(403);

            exit(
                'Your account is not connected to an employee record.'
            );
        }


        $employee =
            $this->employeeRequests
                ->getEmployeeInfo(
                    $employeeId
                );


        if (!$employee) {

            http_response_code(404);

            exit(
                'Employee record not found.'
            );
        }


        $requests =
            $this->employeeRequests
                ->getRequestsByEmployee(
                    $employeeId
                );


        require BASE_PATH . '/resources/views/employee-requests/my-requests.php';
    }


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE — CREATE REQUEST
    |--------------------------------------------------------------------------
    */

    public function createMyRequest(): void
    {
        Auth::requireRole([
            'EMP'
        ]);


        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            header(
                "Location: " . url("?page=my-requests")
            );

            exit;
        }


        $userId =
            (int) Auth::userId();


        $employeeId =
            $this->employeeRequests
                ->getEmployeeIdByUserId(
                    $userId
                );


        if (!$employeeId) {

            http_response_code(403);

            exit(
                'Your account is not connected to an employee record.'
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Form values
        |--------------------------------------------------------------------------
        */

        $requestType =
            trim(
                $_POST['request_type'] ?? ''
            );

        $subject =
            trim(
                $_POST['subject'] ?? ''
            );

        $description =
            trim(
                $_POST['description'] ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | Allowed request types
        |--------------------------------------------------------------------------
        */

        $allowedRequestTypes = [

            'Certificate of Employment',

            'Document Request',

            'Profile Update',

            'Payroll Concern',

            'Employment Concern',

            'Other'

        ];


        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        if (
            !in_array(
                $requestType,
                $allowedRequestTypes,
                true
            )
        ) {

            header(
                "Location: " . url("?page=my-requests&error=invalid-type")
            );

            exit;
        }


        if ($subject === '') {

            header(
                "Location: " . url("?page=my-requests&error=subject")
            );

            exit;
        }


        if ($description === '') {

            header(
                "Location: " . url("?page=my-requests&error=description")
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Create request
        |--------------------------------------------------------------------------
        */

        try {

            $created =
                $this->employeeRequests
                    ->createRequest(
                        $employeeId,
                        $requestType,
                        $subject,
                        $description
                    );


            if (!$created) {

                header(
                    "Location: " . url("?page=my-requests&error=create")
                );

                exit;
            }


            header(
                "Location: " . url("?page=my-requests&success=created")
            );

            exit;

        } catch (\Throwable $e) {

            header(
                "Location: " . url("?page=my-requests&error=create")
            );

            exit;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE MY REQUEST (Employee Side)
    |--------------------------------------------------------------------------
    */

    public function updateMyRequest(): void
    {
        try {
            Auth::requireRole([
                'EMP'
            ]);

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header("Location: " . url("?page=my-requests"));
                exit;
            }

            $userId = (int) Auth::userId();
            $employeeId = $this->employeeRequests->getEmployeeIdByUserId($userId);

            if (!$employeeId) {
                header("Location: " . url("?page=my-requests&error=invalid-id"));
                exit;
            }

            $requestId = filter_input(INPUT_POST, 'request_id', FILTER_VALIDATE_INT);
            $subject = trim($_POST['subject'] ?? '');
            $description = trim($_POST['description'] ?? '');

            if (!$requestId) {
                header("Location: " . url("?page=my-requests&error=invalid-id"));
                exit;
            }

            $updated = $this->employeeRequests->updateRequestDetails(
                $requestId,
                $employeeId,
                $subject,
                $description
            );

            if (!$updated) {
                header("Location: " . url("?page=my-requests&error=update"));
                exit;
            }

            header("Location: " . url("?page=my-requests&success=updated"));
            exit;
        } catch (\Throwable $e) {
            error_log("Update My Request Error: " . $e->getMessage());
            header("Location: " . url("?page=my-requests&error=update"));
            exit;
        }
    }

    public function update(): void
    {
        header(
            'Content-Type: application/json; charset=utf-8'
        );


        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        Auth::requireRole([
            'ADMIN',
            'HR'
        ]);


        /*
        |--------------------------------------------------------------------------
        | Request method
        |--------------------------------------------------------------------------
        */

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            http_response_code(405);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Invalid request method.'
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Request ID
        |--------------------------------------------------------------------------
        */

        $requestId =
            filter_input(
                INPUT_POST,
                'request_id',
                FILTER_VALIDATE_INT
            );


        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        $status =
            trim(
                $_POST['status'] ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | HR Remarks
        |--------------------------------------------------------------------------
        */

        $hrRemarks =
            trim(
                $_POST['hr_remarks'] ?? ''
            );


        /*
        |--------------------------------------------------------------------------
        | Validate request ID
        |--------------------------------------------------------------------------
        */

        if (!$requestId) {

            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Invalid request ID.'
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Only final statuses may be saved
        |--------------------------------------------------------------------------
        */

        $allowedStatuses = [
            'Approved',
            'Rejected',
            'Completed'
        ];


        if (!in_array(
            $status,
            $allowedStatuses,
            true
        )) {

            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Please select Approved, Rejected, or Completed before saving.'
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Get current request
        |--------------------------------------------------------------------------
        */

        $existingRequest =
            $this->employeeRequests
                ->getRequestById(
                    $requestId
                );


        if (!$existingRequest) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Employee request not found.'
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Prevent changes to finalized requests
        |--------------------------------------------------------------------------
        */

        if (
            ($existingRequest['status'] ?? 'Pending')
            !== 'Pending'
        ) {

            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' =>
                    'This request has already been finalized and cannot be changed.'
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Empty remarks become NULL
        |--------------------------------------------------------------------------
        */

        $hrRemarks =
            $hrRemarks === ''
                ? null
                : $hrRemarks;


        /*
        |--------------------------------------------------------------------------
        | Current logged-in user
        |--------------------------------------------------------------------------
        */

        $resolvedBy =
            (int) Auth::userId();


        try {

            /*
            |--------------------------------------------------------------------------
            | Update request
            |--------------------------------------------------------------------------
            */

            $updated =
                $this->employeeRequests
                    ->updateStatus(
                        $requestId,
                        $status,
                        $hrRemarks,
                        $resolvedBy
                    );


            if (!$updated) {

                http_response_code(500);

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'Failed to update the employee request.'
                ]);

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Get updated request
            |--------------------------------------------------------------------------
            */

            $request =
                $this->employeeRequests
                    ->getRequestById(
                        $requestId
                    );


            if (!$request) {

                http_response_code(404);

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'Updated request could not be found.'
                ]);

                return;
            }


            /*
            |--------------------------------------------------------------------------
            | Success
            |--------------------------------------------------------------------------
            */

            echo json_encode([
                'success' => true,
                'message' =>
                    'Employee request finalized successfully.',
                'request' =>
                    $request
            ]);

        } catch (\Throwable $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' =>
                    'An error occurred while updating the request.'
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PASSWORD RESET REQUEST (HR / Admin)
    |--------------------------------------------------------------------------
    |
    | Completes or rejects a pending "Contact HR" password reset request.
    | Settled requests cannot be edited or reopened.
    |
    */

    public function updatePasswordResetRequest(): void
    {
        header(
            'Content-Type: application/json; charset=utf-8'
        );


        /*
        |--------------------------------------------------------------------------
        | Authentication — HR and Admin only
        |--------------------------------------------------------------------------
        */

        Auth::requireRole([
            'ADMIN',
            'HR'
        ]);


        /*
        |--------------------------------------------------------------------------
        | Request method
        |--------------------------------------------------------------------------
        */

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            http_response_code(405);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Invalid request method.'
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Request ID
        |--------------------------------------------------------------------------
        */

        $requestId =
            filter_input(
                INPUT_POST,
                'request_id',
                FILTER_VALIDATE_INT
            );


        if (!$requestId) {

            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Invalid request ID.'
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Status — Completed / Rejected for this queue
        |--------------------------------------------------------------------------
        |
        | Password resets can only be actioned once while Pending.
        |
        */

        $status =
            trim(
                $_POST['status'] ?? ''
            );


        if (!in_array(
            $status,
            [
                'Completed',
                'Rejected'
            ],
            true
        )) {

            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Please choose Completed or Rejected before saving.'
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Confirm the request exists
        |--------------------------------------------------------------------------
        */

        $existing =
            $this->passwordResetRequest
                ->getById(
                    $requestId
                );


        if (!$existing) {

            http_response_code(404);

            echo json_encode([
                'success' => false,
                'message' =>
                    'Password reset request not found.'
            ]);

            return;
        }

        if (($existing['status'] ?? '') !== 'Pending') {
            http_response_code(409);

            echo json_encode([
                'success' => false,
                'message' =>
                    'This password reset request has already been actioned and cannot be changed.'
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Apply the change
        |--------------------------------------------------------------------------
        */

        $handledBy = (int) Auth::userId();

        try {

            $updated =
                $this->passwordResetRequest
                    ->setStatus(
                        $requestId,
                        $status,
                        $handledBy
                    );


            if (!$updated) {

                http_response_code(409);

                echo json_encode([
                    'success' => false,
                    'message' =>
                        'This password reset request has already been actioned and cannot be changed.'
                ]);

                return;
            }


            $request =
                $this->passwordResetRequest
                    ->getById(
                        $requestId
                    );


            echo json_encode([
                'success' => true,
                'message' =>
                    match ($status) {
                        'Completed' => 'Password reset request marked as completed.',
                        'Rejected'  => 'Password reset request rejected.',
                    },
                'request' =>
                    $request
            ]);

        } catch (\Throwable $e) {

            error_log(
                'Password reset request update failed: '
                . $e->getMessage()
            );

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' =>
                    'An error occurred while updating the password reset request.'
            ]);
        }
    }

}