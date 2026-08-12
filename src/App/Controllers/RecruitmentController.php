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

        $applicationBaseUrl = '/hr1/public/apply/';

        require '../resources/views/recruitment/index.php';
    }

    public function create()
    {
        $recruitment = new Recruitment();

        $departments = $recruitment->getDepartments();
        $positions   = $recruitment->getPositions();

        require '../resources/views/recruitment/create.php';
    }

    public function store()
    {
        $recruitment = new Recruitment();

        $data = $_POST;
        $data['created_by'] = $_SESSION['user_id'];

        try {

            $recruitment->createJob($data);

            header("Location: ?page=recruitment");
            exit;

        } catch (\Exception $e) {

            $_SESSION['error'] = $e->getMessage();

            header("Location: ?page=recruitment&action=create");
            exit;
        }
    }

    public function edit()
    {
        $recruitment = new Recruitment();

        if (!isset($_GET['id'])) {
            header("Location:?page=recruitment");
            exit;
        }

        $postingId = (int) $_GET['id'];

        $job = $recruitment->getJobById($postingId);

        if (!$job) {
            header("Location:?page=recruitment");
            exit;
        }

        $departments = $recruitment->getDepartments();

        $positions = $recruitment->getPositionsForEdit($postingId);

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

    public function getPositions()
    {
        $recruitment = new Recruitment();

        $departmentId = (int)($_GET['department_id'] ?? 0);

        header('Content-Type: application/json');

        echo json_encode(
            $recruitment->getPositionsByDepartment($departmentId)
        );

        exit;
    }
}