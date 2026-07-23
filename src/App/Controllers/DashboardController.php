<?php

namespace App\Controllers;

use App\Models\Applicant;

class DashboardController
{
    public function index()
    {
        $applicantModel = new Applicant();

        $chart = $applicantModel->getApplicantsPerPosition();

        $labels = $chart['labels'];
        $data = $chart['data'];

        require '../resources/views/dashboard/index.php';
    }
}