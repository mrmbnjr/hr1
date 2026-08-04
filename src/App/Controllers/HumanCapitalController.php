<?php

namespace App\Controllers;

use App\Models\HumanCapital;

class HumanCapitalController
{
    private HumanCapital $humanCapital;

    public function __construct()
    {
        $this->humanCapital = new HumanCapital();
    }

    /*
    |--------------------------------------------------------------------------
    | PAGE
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $stats = $this->humanCapital->getOverviewStats();

        $departments = $this->humanCapital->getDepartments();

        $jobPostings = $this->humanCapital->getJobPostings();

        $organization = $this->humanCapital->getOrganizationTree();

        $departmentLookup = $this->humanCapital->getDepartmentLookup();

        require '../resources/views/human-capital/index.php';
    }

    /*
    |--------------------------------------------------------------------------
    | DEPARTMENTS
    |--------------------------------------------------------------------------
    */

    public function getDepartment()
    {
        $id = (int) ($_GET['id'] ?? 0);

        $this->json([
            'success' => true,
            'data' => $this->humanCapital->getDepartment($id)
        ]);
    }

    public function saveDepartment()
    {
        $id = (int) ($_POST['department_id'] ?? 0);

        $data = [

            'department_name' => trim($_POST['department_name'] ?? ''),
            'description'     => trim($_POST['description'] ?? '')

        ];

        if ($data['department_name'] === '') {

            $this->json([
                'success' => false,
                'message' => 'Department name is required.'
            ]);

        }

        if ($id) {

            $this->humanCapital->updateDepartment($id, $data);

        } else {

            $this->humanCapital->createDepartment($data);

        }

        $this->json([
            'success' => true
        ]);
    }

    public function deleteDepartment()
    {
        $id = (int) ($_POST['department_id'] ?? 0);

        $this->json(

            $this->humanCapital->deleteDepartment($id)

        );
    }

    /*
    |--------------------------------------------------------------------------
    | ORGANIZATION
    |--------------------------------------------------------------------------
    */

    public function organization()
    {
        $this->json([

            'success' => true,

            'data' => $this->humanCapital->getOrganizationTree()

        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | HELPERS
    |--------------------------------------------------------------------------
    */

    private function json(array $response)
    {
        header('Content-Type: application/json');

        echo json_encode($response);

        exit;
    }
}