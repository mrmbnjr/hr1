<?php

namespace App\Controllers;

use App\Models\EmployeeRequests;

class EmployeeRequestsController
{
    private EmployeeRequests $employeeRequests;


    public function __construct()
    {
        $this->employeeRequests = new EmployeeRequests();
    }


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE REQUESTS PAGE
    |--------------------------------------------------------------------------
    */

    public function employeeRequests(): void
    {
        if (!isset($_SESSION['user_id'])) {

            header(
                "Location: /hr1/public/?page=login"
            );

            exit;
        }


        $requests =
            $this->employeeRequests->getAllRequests();


        $departments =
            $this->employeeRequests->getDepartments();


        require '../resources/views/employee-requests/index.php';
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE EMPLOYEE REQUEST
    |--------------------------------------------------------------------------
    */

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

        if (!isset($_SESSION['user_id'])) {

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized.'
            ]);

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | Request method
        |--------------------------------------------------------------------------
        */

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            http_response_code(405);

            echo json_encode([
                'success' => false,
                'message' => 'Invalid request method.'
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
                'message' => 'Invalid request ID.'
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
            $this->employeeRequests->getRequestById(
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
            (int) $_SESSION['user_id'];


        try {

            /*
            |--------------------------------------------------------------------------
            | Update request
            |--------------------------------------------------------------------------
            */

            $updated =
                $this->employeeRequests->updateStatus(
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
                $this->employeeRequests->getRequestById(
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
                'request' => $request
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
}