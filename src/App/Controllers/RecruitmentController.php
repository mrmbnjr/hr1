<?php

namespace App\Controllers;

use App\Models\Recruitment;

class RecruitmentController
{
    public function recruitment()
    {
        $recruitment = new Recruitment();

        $jobs = $recruitment->getAllJobs();
        $departments = $recruitment->getDepartments();

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

        $data = $_POST;
        $data['created_by'] = $_SESSION['user_id'];

        $recruitment->createJob($data);

        header("Location: ?page=recruitment");
        exit;
    }

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

    public function update()
    {
        $recruitment = new Recruitment();

        $recruitment->update($_POST);

        header("Location:?page=recruitment");
        exit;
    }

    public function close()
    {
        $recruitment = new Recruitment();

        if (!isset($_POST['posting_id'])) {
            header("Location:?page=recruitment");
            exit;
        }

        $recruitment->close($_POST['posting_id']);

        header("Location:?page=recruitment");
        exit;
    }

        public function delete()
    {
        $recruitment = new Recruitment();

        if (!isset($_POST['posting_id'])) {
            header("Location:?page=recruitment");
            exit;
        }

        $recruitment->delete((int)$_POST['posting_id']);

        header("Location:?page=recruitment");
        exit;
    }
}