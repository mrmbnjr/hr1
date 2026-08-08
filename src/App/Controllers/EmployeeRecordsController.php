<?php

namespace App\Controllers;

use App\Models\EmployeeRecords;

class EmployeeRecordsController
{
    private EmployeeRecords $employeeRecords;

    public function __construct()
    {
        $this->employeeRecords = new EmployeeRecords();
    }


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE RECORDS — LIST
    |--------------------------------------------------------------------------
    */

    public function employeeRecords()
    {
        // Get employee records
        $employees = $this->employeeRecords->getAllEmployees();

        // Get departments for the filter
        $departments = $this->employeeRecords->getDepartments();

        // Load employee records list
        require '../resources/views/employee-records/index.php';
    }


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE RECORD — VIEW
    |--------------------------------------------------------------------------
    */

    public function view()
    {
        $employeeId = $_GET['id'] ?? null;

        if (!$employeeId) {
            header("Location: /hr1/public/?page=employee-records");
            exit;
        }

        // Get selected employee
        $employee = $this->employeeRecords->getEmployeeById($employeeId);

        // Load employee record view
        require '../resources/views/employee-records/view.php';
    }
}