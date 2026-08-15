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

        $positions = $this->humanCapital->getPositions();

        $organization = $this->humanCapital->getOrganizationTree();

        $departmentLookup =
            $this->humanCapital->getDepartmentLookup();

        $roles =
            $this->humanCapital->getRoles();

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
    | POSITIONS
    |--------------------------------------------------------------------------
    */

    public function createPosition()
    {
        $positionName = trim(
            $_POST['position_name'] ?? ''
        );

        $departmentId = (int) (
            $_POST['department_id'] ?? 0
        );

        $roleId = (int) (
            $_POST['role_id'] ?? 0
        );


        if ($positionName === '') {

            $this->json([
                'success' => false,
                'message' => 'Position name is required.'
            ]);

        }


        if ($departmentId <= 0) {

            $this->json([
                'success' => false,
                'message' => 'Department is required.'
            ]);

        }


        if ($roleId <= 0) {

            $this->json([
                'success' => false,
                'message' => 'System role is required.'
            ]);

        }


        try {

            $success = $this->humanCapital->createPosition([

                'position_name' => $positionName,

                'department_id' => $departmentId,

                'role_id' => $roleId

            ]);


            $this->json([
                'success' => $success,
                'message' => 'Position created successfully.'
            ]);


        } catch (\Exception $e) {

            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);

        }
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