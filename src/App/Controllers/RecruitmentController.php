<?php

namespace App\Controllers;

use App\Models\Recruitment;

class RecruitmentController
{
    public function recruitment()
    {
        $recruitment = new Recruitment();

        $jobs = $recruitment->getAllJobs();

        require '../resources/views/recruitment/index.php';
    }

    public function create()
    {
        $recruitment = new Recruitment();

        // Load all departments for the dropdown
        $departments = $recruitment->getDepartments();

        require '../resources/views/recruitment/create.php';
    }

    public function store()
    {
        $recruitment = new Recruitment();

        $recruitment->createJob($_POST);

        header("Location: ?page=recruitment");
        exit;
    }

    public function close()
    {
        $recruitment = new Recruitment();

        $recruitment->close($_POST['position_id']);

        header("Location:?page=recruitment");
        exit;
    }
    
    public function applicants()
    {
        require '../resources/views/applicants/index.php';
    }

    public function onboarding()
    {
        require '../resources/views/onboarding/index.php';
    }
}