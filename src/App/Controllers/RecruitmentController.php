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

        /*
        |----------------------------------------------------------------------
        | Overview metrics
        |----------------------------------------------------------------------
        | Feeds the KPI cards at the top of the page.
        */
        $overview = $this->buildOverview($jobs);

        require BASE_PATH . '/resources/views/recruitment/index.php';
    }

    /**
     * Aggregate the job posting rows into the KPI figures shown above the
     * recruitment table.
     *
     * @param array<int, array<string, mixed>> $jobs
     * @return array<string, int>
     */
    private function buildOverview(array $jobs): array
    {
        $today      = strtotime('today');
        $soonCutoff = strtotime('+7 days', $today);

        $overview = [
            'total'              => 0,
            'open'               => 0,
            'closed'             => 0,
            'vacancies'          => 0,
            'applications'       => 0,
            'departments_hiring' => 0,
            'closing_soon'       => 0,
            'hired'              => 0,
        ];

        $hiringDepartments = [];

        foreach ($jobs as $job) {
            $status   = (string) ($job['status'] ?? '');
            $deadline = strtotime((string) ($job['application_deadline'] ?? ''));

            $overview['total']++;
            $overview['applications'] += (int) ($job['applicants'] ?? 0);
            $overview['hired']        += (int) ($job['hired'] ?? 0);

            if ($status === 'Open') {
                $overview['open']++;
                $overview['vacancies'] += (int) ($job['vacancies'] ?? 0);

                $department = (string) ($job['department_name'] ?? '');

                if ($department !== '') {
                    $hiringDepartments[$department] = true;
                }

                if ($deadline !== false && $deadline >= $today && $deadline <= $soonCutoff) {
                    $overview['closing_soon']++;
                }
            } elseif ($status === 'Closed') {
                $overview['closed']++;
            }
        }

        $overview['departments_hiring'] = count($hiringDepartments);

        return $overview;
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
        try {
            $this->recruitment->update($_POST);
        } catch (\InvalidArgumentException $e) {
            $_SESSION['error'] = $e->getMessage();
            $postingId = (int) ($_POST['posting_id'] ?? 0);

            header("Location:?page=recruitment&action=edit&id=$postingId");
            exit;
        }

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