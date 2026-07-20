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

    // Display Edit Form
    public function edit()
    {
        $recruitment = new Recruitment();

        if (!isset($_GET['id'])) {
            header("Location:?page=recruitment");
            exit;
        }

        $job = $recruitment->getJobById($_GET['id']);

        if (!$job) {
            header("Location:?page=recruitment");
            exit;
        }

        $departments = $recruitment->getDepartments();

        require '../resources/views/recruitment/edit.php';
    }

    // Update Job Posting
    public function update()
    {
        $recruitment = new Recruitment();

        $recruitment->updateJob($_POST);

        header("Location:?page=recruitment");
        exit;
    }

    public function close()
    {
        $recruitment = new Recruitment();

        $recruitment->close($_POST['position_id']);

        header("Location:?page=recruitment");
        exit;
    }
}