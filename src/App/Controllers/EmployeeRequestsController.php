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
        $requests = $this->employeeRequests->getAllRequests();

        $departments = $this->employeeRequests->getDepartments();

        require '../resources/views/employee-requests/index.php';
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE EMPLOYEE REQUEST
    |--------------------------------------------------------------------------
    */

    public function update(): void
    {
        header('Content-Type: application/json; charset=utf-8');


        // ----------------------------------------------------------
        // Require logged-in user
        // ----------------------------------------------------------

        if (!isset($_SESSION['user_id'])) {

            http_response_code(401);

            echo json_encode([
                'success' => false,
                'message' => 'Unauthorized.'
            ]);

            return;
        }


        // ----------------------------------------------------------
        // Only allow POST
        // ----------------------------------------------------------

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

            http_response_code(405);

            echo json_encode([
                'success' => false,
                'message' => 'Invalid request method.'
            ]);

            return;
        }


        // ----------------------------------------------------------
        // Get submitted values
        // ----------------------------------------------------------

        $requestId =
            filter_input(
                INPUT_POST,
                'request_id',
                FILTER_VALIDATE_INT
            );

        $status =
            trim(
                $_POST['status'] ?? ''
            );

        $hrRemarks =
            trim(
                $_POST['hr_remarks'] ?? ''
            );


        // ----------------------------------------------------------
        // Validate request ID
        // ----------------------------------------------------------

        if (!$requestId) {

            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Invalid request ID.'
            ]);

            return;
        }


        // ----------------------------------------------------------
        // Validate status
        // ----------------------------------------------------------

        $allowedStatuses = [
            'Pending',
            'Approved',
            'Rejected',
            'Completed'
        ];


        if (!in_array($status, $allowedStatuses, true)) {

            http_response_code(422);

            echo json_encode([
                'success' => false,
                'message' => 'Invalid request status.'
            ]);

            return;
        }


        // ----------------------------------------------------------
        // Convert empty remarks to NULL
        // ----------------------------------------------------------

        $hrRemarks =
            $hrRemarks === ''
                ? null
                : $hrRemarks;


        // ----------------------------------------------------------
        // Logged-in HR/Admin user
        // ----------------------------------------------------------

        $resolvedBy =
            in_array(
                $status,
                [
                    'Approved',
                    'Rejected',
                    'Completed'
                ],
                true
            )
                ? (int) $_SESSION['user_id']
                : null;


        // ----------------------------------------------------------
        // Update database
        // ----------------------------------------------------------

        try {

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
                    'message' => 'Failed to update the employee request.'
                ]);

                return;
            }


            // ------------------------------------------------------
            // Get updated request
            // ------------------------------------------------------

            $request =
                $this->employeeRequests->getRequestById(
                    $requestId
                );


            if (!$request) {

                http_response_code(404);

                echo json_encode([
                    'success' => false,
                    'message' => 'Updated request could not be found.'
                ]);

                return;
            }


            // ------------------------------------------------------
            // Success
            // ------------------------------------------------------

            echo json_encode([
                'success' => true,
                'message' => 'Employee request updated successfully.',
                'request' => $request
            ]);

        } catch (\Throwable $e) {

            http_response_code(500);

            echo json_encode([
                'success' => false,
                'message' => 'An error occurred while updating the request.'
            ]);
        }
    }
}