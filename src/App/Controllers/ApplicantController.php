<?php

namespace App\Controllers;

use App\Models\Applicant;

class ApplicantController
{
    private Applicant $applicant;

    public function __construct()
    {
        $this->applicant = new Applicant();
    }

    public function index()
    {
        $applicants = $this->applicant->getAllApplicants();
        $positions = $this->applicant->getAllJobPostings();

        require '../resources/views/applicants/index.php';
    }

    /**
     * View Single Applicant
     */
    public function review()
    {
        if (!isset($_GET['id'])) {
            header("Location:?page=applicants");
            exit;
        }

        $applicant = $this->applicant->getApplicantById((int)$_GET['id']);

        if (!$applicant) {
            header("Location:?page=applicants");
            exit;
        }

        $managers = $this->applicant->getManagers();

        require '../resources/views/applicants/review.php';
    }

    /**
     * Edit Applicant
     */
    public function edit()
    {
        if (!isset($_GET['id'])) {
            header("Location: ?page=applicants");
            exit;
        }

        $applicant = $this->applicant->getApplicantById($_GET['id']);
        $positions = $this->applicant->getAllJobPostings();

        require '../resources/views/applicants/edit.php';
    }
}