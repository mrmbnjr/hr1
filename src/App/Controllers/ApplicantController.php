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

    /**
     * Applicant Management Page
     */
    public function index()
    {
        $applicants = $this->applicant->getAllApplicants();
        $positions = $this->applicant->getAllPositions();

        require '../resources/views/applicants/index.php';
    }

    /**
     * View Single Applicant
     */
    public function view()
    {
        if (!isset($_GET['id'])) {
            header("Location: ?page=applicants");
            exit;
        }

        $applicant = $this->applicant->getApplicantById($_GET['id']);

        if (!$applicant) {
            header("Location: ?page=applicants");
            exit;
        }

        require '../resources/views/applicants/view.php';
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
        $positions = $this->applicant->getAllPositions();

        require '../resources/views/applicants/edit.php';
    }
}