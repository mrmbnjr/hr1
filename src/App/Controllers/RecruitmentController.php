<?php

namespace App\Controllers;

use App\Models\Recruitment;

class RecruitmentController
{
    private Recruitment $recruitment;

    public function __construct()
    {
        $this->recruitment = new Recruitment();
    }

    public function recruitment()
    {
        $jobs = $this->recruitment->getAllJobs();
        $departments = $this->recruitment->getDepartments();

        $applicationBaseUrl = url('?page=apply&token=');

        require BASE_PATH . '/resources/views/recruitment/index.php';
    }

    public function create()
    {
        $departments = $this->recruitment->getDepartments();
        $positions   = $this->recruitment->getPositions();

        require BASE_PATH . '/resources/views/recruitment/create.php';
    }

    public function store()
    {
        $data = $_POST;
        $data['created_by'] = $_SESSION['user_id'];

        try {

            $this->recruitment->createJob($data);

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
        if (!isset($_GET['id'])) {
            header("Location:?page=recruitment");
            exit;
        }

        $postingId = (int) $_GET['id'];

        $job = $this->recruitment->getJobById($postingId);

        if (!$job) {
            header("Location:?page=recruitment");
            exit;
        }

        $departments = $this->recruitment->getDepartments();

        $positions = $this->recruitment->getPositionsForEdit($postingId);

        require BASE_PATH . '/resources/views/recruitment/edit.php';
    }

    public function update()
    {
        $this->recruitment->update($_POST);

        header("Location:?page=recruitment");
        exit;
    }

    public function close()
    {
        if (!isset($_POST['posting_id'])) {
            header("Location:?page=recruitment");
            exit;
        }

        $postingId = (int)$_POST['posting_id'];

        $job = $this->recruitment->getJobById($postingId);

        if (!$job || $job['status'] === 'Closed') {
            header("Location:?page=recruitment");
            exit;
        }

        try {
            $this->recruitment->close($postingId);

            header("Location:?page=recruitment");
            exit;
        } catch (\Exception $e) {
            $_SESSION['error'] = $e->getMessage();

            header("Location:?page=recruitment&action=edit&id=$postingId");
            exit;
        }
    }

    public function delete()
    {
        if (!isset($_POST['posting_id'])) {
            header("Location:?page=recruitment");
            exit;
        }

        $this->recruitment->delete((int)$_POST['posting_id']);

        header("Location:?page=recruitment");
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

            header("Location: ?page=login");

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Validate Posting ID
        |--------------------------------------------------------------------------
        */

        if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {

            header("Location: ?page=recruitment");

            exit;
        }


        $postingId = (int) $_GET['id'];


        if ($postingId <= 0) {

            header("Location: ?page=recruitment");

            exit;
        }


        /*
        |--------------------------------------------------------------------------
        | Load Statistics
        |--------------------------------------------------------------------------
        */

        $job = $this->recruitment->getJob($postingId);


        if (!$job) {

            header("Location: ?page=recruitment");

            exit;
        }


        $summary = $this->recruitment->getSummary($postingId);

        $interviewCount = $this->recruitment->getInterviewCount($postingId);

        $statusBreakdown = $this->recruitment->getStatusBreakdown($postingId);

        $applicationTrend = $this->recruitment->getApplicationTrend($postingId);

        $recentApplicants = $this->recruitment->getRecentApplicants($postingId);


        /*
        |--------------------------------------------------------------------------
        | Calculated Values
        |--------------------------------------------------------------------------
        */

        $vacancies = (int) ($job['vacancies'] ?? 0);

        $hired = (int) ($summary['hired'] ?? 0);

        $totalApplications = (int) ($summary['total_applications'] ?? 0);


        $remainingVacancies = max(0, $vacancies - $hired);


        $hiringRate = 0;

        if ($totalApplications > 0) {

            $hiringRate = round(($hired / $totalApplications) * 100, 1);
        }


        /*
        |--------------------------------------------------------------------------
        | Page Configuration
        |--------------------------------------------------------------------------
        */

        $pageTitle = "Job Statistics";

        $pageCSS = "statistics.css";

        $pageJS = "statistics.js";

        $pageDescription = "View recruitment statistics for this job posting.";


        /*
        |--------------------------------------------------------------------------
        | View
        |--------------------------------------------------------------------------
        */

        require BASE_PATH . '/resources/views/recruitment/statistics.php';
    }
}