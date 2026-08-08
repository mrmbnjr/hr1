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

        $applicantId = (int)$_GET['id'];

        // Change Submitted -> Under Review
        $this->applicant->markAsUnderReview($applicantId);

        $applicant = $this->applicant->getApplicantById($applicantId);

        if (!$applicant) {
            header("Location:?page=applicants");
            exit;
        }

        $managers = $this->applicant->getManagers();

        require '../resources/views/applicants/review.php';
    }

    public function scheduleInterview()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location:?page=applicants");
            exit;
        }

        $this->applicant->scheduleInterview($_POST);

        header("Location:?page=review&id=" . $_POST['applicant_id']);
        exit;
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

    public function updateStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header("Location:?page=applicants");
            exit;
        }

        $applicationId = (int) ($_POST['application_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (!$applicationId || !$status) {
            header("Location:?page=applicants");
            exit;
        }

        try {

            $this->applicant->updateApplicationStatus(
                $applicationId,
                $status
            );

            header(
                "Location:?page=review&id=" .
                (int) ($_POST['applicant_id'] ?? 0)
            );
            exit;

        } catch (\Exception $e) {

            /*
            * You can later replace this with
            * a proper session flash message.
            */
            $_SESSION['error'] = $e->getMessage();

            header(
                "Location:?page=review&id=" .
                (int) ($_POST['applicant_id'] ?? 0)
            );
            exit;
        }
    }
}