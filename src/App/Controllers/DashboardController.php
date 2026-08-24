<?php

namespace App\Controllers;

use App\Models\Dashboard;
use App\Services\Auth;

class DashboardController
{
    private Dashboard $dashboard;


    public function __construct()
    {
        $this->dashboard = new Dashboard();
    }


    /*
    |--------------------------------------------------------------------------
    | DASHBOARD
    |--------------------------------------------------------------------------
    */

    public function index(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Employee Dashboard
        |--------------------------------------------------------------------------
        |
        | Employees should not see the Admin / HR / Manager dashboard.
        |
        */

        if ($this->isEmployee()) {

            $this->employeeDashboard();

            return;
        }


        /*
        |--------------------------------------------------------------------------
        | EXISTING ADMIN / HR / MANAGER DASHBOARD
        |--------------------------------------------------------------------------
        */

        $stats =
            $this->dashboard->getQuickStats();


        $newEmployees =
            $this->dashboard->getNewEmployees(5);


        $recentActivities =
            $this->dashboard->getRecentActivities(5);


        $jobApplicants =
            $this->dashboard->getApplicantsPerJob();


        $jobLabels =
            array_column(
                $jobApplicants,
                'title'
            );


        $jobData =
            array_map(
                'intval',
                array_column(
                    $jobApplicants,
                    'total_applicants'
                )
            );


        $view =
            $_GET['view'] ?? 'year';


        $year =
            isset($_GET['year'])
                ? (int) $_GET['year']
                : date('Y');


        $month =
            isset($_GET['month'])
                ? (int) $_GET['month']
                : date('n');


        $weekStart =
            $_GET['weekStart']
            ?? date('Y-m-d');


        $chart =
            $this->dashboard->getApplicantChart(
                $view,
                $year,
                $month,
                $weekStart
            );


        $growthLabels =
            $chart['labels'];


        $growthData =
            $chart['data'];


        $chartPeriod =
            $chart['period'];


        $chartSubtitle =
            $chart['subtitle'];


        require '../resources/views/dashboard/index.php';
    }


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE DASHBOARD
    |--------------------------------------------------------------------------
    */

    private function employeeDashboard(): void
    {
        $userId =
            (int) Auth::userId();


        /*
        |--------------------------------------------------------------------------
        | Get employee information
        |--------------------------------------------------------------------------
        |
        | We use users.employee_id instead of allowing the employee
        | to provide an employee ID manually.
        |
        */

        $employeeId =
            $this->getEmployeeId(
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
        | Employee information
        |--------------------------------------------------------------------------
        */

        $employee =
            $this->getEmployeeInfo(
                $employeeId
            );


        /*
        |--------------------------------------------------------------------------
        | Employee requests
        |--------------------------------------------------------------------------
        */

        $requests =
            $this->getEmployeeRequests(
                $employeeId
            );


        /*
        |--------------------------------------------------------------------------
        | Request statistics
        |--------------------------------------------------------------------------
        */

        $totalRequests =
            count($requests);


        $pendingRequests = 0;

        $approvedRequests = 0;

        $rejectedRequests = 0;

        $completedRequests = 0;


        foreach ($requests as $request) {

            $status =
                $request['status']
                ?? 'Pending';


            switch ($status) {

                case 'Pending':

                    $pendingRequests++;

                    break;


                case 'Approved':

                    $approvedRequests++;

                    break;


                case 'Rejected':

                    $rejectedRequests++;

                    break;


                case 'Completed':

                    $completedRequests++;

                    break;

            }
        }


        /*
        |--------------------------------------------------------------------------
        | Recent requests
        |--------------------------------------------------------------------------
        */

        $recentRequests =
            array_slice(
                $requests,
                0,
                5
            );


        /*
        |--------------------------------------------------------------------------
        | Employee dashboard
        |--------------------------------------------------------------------------
        */

        require '../resources/views/dashboard/employee.php';
    }


    /*
    |--------------------------------------------------------------------------
    | CHECK EMPLOYEE ROLE
    |--------------------------------------------------------------------------
    */

    private function isEmployee(): bool
    {
        /*
        |--------------------------------------------------------------------------
        | Preferred role method
        |--------------------------------------------------------------------------
        */

        if (method_exists(
            Auth::class,
            'role'
        )) {

            return Auth::role() === 'EMP';
        }


        /*
        |--------------------------------------------------------------------------
        | Fallback for current GitHub Auth implementation
        |--------------------------------------------------------------------------
        |
        | Current Auth.php stores role_id in the session.
        |
        */

        if (
            isset($_SESSION['role']) &&
            $_SESSION['role'] === 'EMP'
        ) {

            return true;
        }


        return false;
    }


    /*
    |--------------------------------------------------------------------------
    | GET EMPLOYEE ID
    |--------------------------------------------------------------------------
    */

    private function getEmployeeId(
        int $userId
    ): ?int {

        $pdo =
            \Core\Database::connection();


        $sql = "
            SELECT employee_id

            FROM users

            WHERE user_id = :user_id

            LIMIT 1
        ";


        $stmt =
            $pdo->prepare($sql);


        $stmt->bindValue(
            ':user_id',
            $userId,
            \PDO::PARAM_INT
        );


        $stmt->execute();


        $employeeId =
            $stmt->fetchColumn();


        if (
            $employeeId === false ||
            $employeeId === null
        ) {

            return null;
        }


        return (int) $employeeId;
    }


    /*
    |--------------------------------------------------------------------------
    | GET EMPLOYEE INFORMATION
    |--------------------------------------------------------------------------
    */

    private function getEmployeeInfo(
        int $employeeId
    ): ?array {

        $pdo =
            \Core\Database::connection();


        $sql = "
            SELECT

                e.employee_id,

                e.employee_number,

                e.application_id,

                e.position_id,

                e.hire_date,

                e.employment_status,

                CONCAT_WS(
                    ' ',
                    NULLIF(ap.first_name, ''),
                    NULLIF(ap.middle_name, ''),
                    NULLIF(ap.last_name, '')
                ) AS fullname,

                ap.email,

                ap.phone,

                d.department_name,

                p.position_name AS job_title

            FROM employees e

            LEFT JOIN applications app
                ON e.application_id =
                   app.application_id

            LEFT JOIN applicants ap
                ON app.applicant_id =
                   ap.applicant_id

            LEFT JOIN positions p
                ON e.position_id =
                   p.position_id

            LEFT JOIN departments d
                ON p.department_id =
                   d.department_id

            WHERE e.employee_id =
                  :employee_id

            LIMIT 1
        ";


        $stmt =
            $pdo->prepare($sql);


        $stmt->bindValue(
            ':employee_id',
            $employeeId,
            \PDO::PARAM_INT
        );


        $stmt->execute();


        $employee =
            $stmt->fetch(
                \PDO::FETCH_ASSOC
            );


        return $employee ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | GET EMPLOYEE REQUESTS
    |--------------------------------------------------------------------------
    */

    private function getEmployeeRequests(
        int $employeeId
    ): array {

        $pdo =
            \Core\Database::connection();


        $sql = "
            SELECT

                request_id,

                employee_id,

                request_type,

                subject,

                description,

                status,

                hr_remarks,

                requested_at,

                resolved_at

            FROM employee_requests

            WHERE employee_id =
                  :employee_id

            ORDER BY requested_at DESC
        ";


        $stmt =
            $pdo->prepare($sql);


        $stmt->bindValue(
            ':employee_id',
            $employeeId,
            \PDO::PARAM_INT
        );


        $stmt->execute();


        return $stmt->fetchAll(
            \PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | APPLICANT GROWTH DATA
    |--------------------------------------------------------------------------
    */

    public function growthData(): void
    {
        $view =
            $_GET['view'] ?? 'year';


        $year =
            isset($_GET['year'])
                ? (int) $_GET['year']
                : date('Y');


        $month =
            isset($_GET['month'])
                ? (int) $_GET['month']
                : date('n');


        $weekStart =
            $_GET['weekStart']
            ?? date('Y-m-d');


        $chart =
            $this->dashboard->getApplicantChart(
                $view,
                $year,
                $month,
                $weekStart
            );


        header(
            'Content-Type: application/json'
        );


        echo json_encode([

            'labels' =>
                $chart['labels'],

            'data' =>
                $chart['data'],

            'period' =>
                $chart['period'],

            'subtitle' =>
                $chart['subtitle']

        ]);


        exit;
    }
}