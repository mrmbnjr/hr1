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


    public function employeeRecords()
    {
        // Get employee records
        $employees = $this->employeeRecords->getAllEmployees();

        // Get departments for the filter
        $departments = $this->employeeRecords->getDepartments();

        // Load the view
        require '../resources/views/employee-records/index.php';
    }
}