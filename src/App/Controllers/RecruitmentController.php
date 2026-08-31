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

    public function statistics()
    {
        /*
        |--------------------------------------------------------------------------
        | Authentication
        |--------------------------------------------------------------------------
        */

        if (!isset($_SESSION['user_id'])) {

            header(
                "Location: /hr1/public/?page=login"
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Posting ID
        |--------------------------------------------------------------------------
        */

        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

            header(
                "Location: /hr1/public/?page=recruitment"
            );

            exit;
        }


        $postingId = (int) $_GET['id'];


        if ($postingId <= 0) {

            header(
                "Location: /hr1/public/?page=recruitment"
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Load Model
        |--------------------------------------------------------------------------
        */

        $statistics = new Recruitment();


        /*
        |--------------------------------------------------------------------------
        | Get Job
        |--------------------------------------------------------------------------
        */

        $job = $statistics->getJob($postingId);


        if (!$job) {

            header(
                "Location: /hr1/public/?page=recruitment"
            );

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Get Statistics
        |--------------------------------------------------------------------------
        */

        $summary = $statistics->getSummary(
            $postingId
        );


        $interviewCount =
            $statistics->getInterviewCount(
                $postingId
            );


        $statusBreakdown =
            $statistics->getStatusBreakdown(
                $postingId
            );


        $applicationTrend =
            $statistics->getApplicationTrend(
                $postingId
            );


        $recentApplicants =
            $statistics->getRecentApplicants(
                $postingId
            );


        /*
        |--------------------------------------------------------------------------
        | Calculated Values
        |--------------------------------------------------------------------------
        */

        $vacancies =
            (int) ($job['vacancies'] ?? 0);

        $hired =
            (int) ($summary['hired'] ?? 0);

        $totalApplications =
            (int) ($summary['total_applications'] ?? 0);


        $remainingVacancies = max(
            0,
            $vacancies - $hired
        );


        $hiringRate = 0;

        if ($totalApplications > 0) {

            $hiringRate = round(
                ($hired / $totalApplications) * 100,
                1
            );
        }


        /*
        |--------------------------------------------------------------------------
        | Page Configuration
        |--------------------------------------------------------------------------
        */

        $pageTitle =
            "Job Statistics";

        $pageCSS =
            "statistics.css";

        $pageJS =
            "statistics.js";

        $pageDescription =
            "View recruitment statistics for this job posting.";


        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        require '../resources/views/recruitment/statistics.php';
    }

}