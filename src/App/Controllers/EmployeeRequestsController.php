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
}