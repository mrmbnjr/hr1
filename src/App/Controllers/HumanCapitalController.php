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

        require BASE_PATH . '/resources/views/human-capital/index.php';
    }

    /*
    |--------------------------------------------------------------------------
    | DEPARTMENT STATISTICS PAGE
    |--------------------------------------------------------------------------
    */

    public function statistics()
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: ?page=human-capital');
            exit;
        }

        $department = $this->humanCapital->getDepartmentDetails($id);

        if ($department === null) {
            header('Location: ?page=human-capital');
            exit;
        }

        $departmentStats = $this->humanCapital->getDepartmentStats($id);
        $positions       = $this->humanCapital->getDepartmentPositions($id);
        $employees       = $this->humanCapital->getDepartmentEmployees($id);
        $recruitment     = $this->humanCapital->getDepartmentRecruitment($id);
        $onboarding      = $this->humanCapital->getDepartmentOnboarding($id);
        $recentActivity  = $this->humanCapital->getDepartmentActivity($id);

        require BASE_PATH . '/resources/views/human-capital/department-statistics.php';
    }

    /*
    |--------------------------------------------------------------------------
    | POSITION DETAILS PAGE
    |--------------------------------------------------------------------------
    |
    | Opened from the "View" action in the Positions table:
    |     ?page=position-statistics&id=<position_id>
    |
    | Only the selected position is loaded. When the id is missing or the
    | position does not exist the page redirects back to Human Capital rather
    | than rendering placeholder data.
    |
    */

    public function positionStatistics()
    {
        $id = (int) ($_GET['id'] ?? 0);

        if ($id <= 0) {
            header('Location: ?page=human-capital');
            exit;
        }

        $position = $this->humanCapital->getPositionDetails($id);

        if ($position === null) {
            header('Location: ?page=human-capital');
            exit;
        }

        $positionStats  = $this->humanCapital->getPositionStats($id);
        $employees      = $this->humanCapital->getPositionEmployees($id);
        $recruitment    = $this->humanCapital->getPositionRecruitment($id);
        $onboarding     = $this->humanCapital->getPositionOnboarding($id);
        $recentActivity = $this->humanCapital->getPositionActivity($id);

        /*
         * Lookups that feed the Edit Position modal.
         */
        $departmentLookup = $this->humanCapital->getDepartmentLookup();
        $roles            = $this->humanCapital->getRoles();

        require BASE_PATH . '/resources/views/human-capital/position-statistics.php';
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

        $status = $_POST['status'] ?? 'Active';

        if (!in_array($status, ['Active', 'Inactive'], true)) {
            $status = 'Active';
        }

        $data = [

            'department_name' => trim($_POST['department_name'] ?? ''),
            'status'          => $status

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

    public function deletePosition()
    {
        $id = (int) ($_POST['position_id'] ?? 0);

        $this->json(
            $this->humanCapital->deletePosition($id)
        );
    }

    /*
     * Create or update a position from the Edit Position modal.
     */
    public function savePosition()
    {
        $id = (int) ($_POST['position_id'] ?? 0);

        $positionName = trim($_POST['position_name'] ?? '');
        $departmentId = (int) ($_POST['department_id'] ?? 0);
        $roleId       = (int) ($_POST['role_id'] ?? 0);

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

        $data = [
            'position_name' => $positionName,
            'department_id' => $departmentId,
            'role_id'       => $roleId,
        ];

        try {

            if ($id > 0) {
                $success = $this->humanCapital->updatePosition($id, $data);
            } else {
                $success = $this->humanCapital->createPosition($data);
            }

        } catch (\Exception $e) {

            $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ]);

        }

        $this->json([
            'success' => $success,
            'message' => $id > 0
                ? 'Position updated successfully.'
                : 'Position created successfully.'
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
