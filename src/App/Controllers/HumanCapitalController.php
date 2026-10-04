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
        $stats = $this->humanCapital->getOverviewStats();

        $departments = $this->humanCapital->getDepartments();

        /*
         * Department overview figures.
         *
         * Derived from the rows already loaded above, so no additional
         * queries are needed. Every employee maps to exactly one position
         * and therefore one department, which is why the per-department
         * counts can simply be summed.
         */
        $totalEmployees = array_sum(
            array_column($departments, 'employee_count')
        );

        $staffedPositions = array_sum(
            array_column($departments, 'staffed_position_count')
        );

        $activeDepartments = count(
            array_filter(
                $departments,
                fn($department) => ($department['status'] ?? 'Active') === 'Active'
            )
        );

        $withoutHead = count(
            array_filter(
                $departments,
                fn($department) => empty($department['has_head'])
            )
        );

        $deptStats = [

            'total_departments'    => $stats['departments'],
            'active_departments'   => $activeDepartments,
            'inactive_departments' => $stats['departments'] - $activeDepartments,
            'total_employees'      => $totalEmployees,
            'total_positions'      => $stats['positions'],
            'staffed_positions'    => $staffedPositions,
            'open_positions'       => $stats['vacancies'],
            'without_head'         => $withoutHead,

        ];

        /*
         * Most recently created departments (newest first).
         */
        $deptRecent = $departments;

        usort(
            $deptRecent,
            fn($a, $b) => strcmp($b['created_at'], $a['created_at'])
        );

        $deptRecent = array_slice($deptRecent, 0, 5);

        require BASE_PATH . '/resources/views/human-capital/department-statistics.php';
    }

    /*
    |--------------------------------------------------------------------------
    | POSITION STATISTICS PAGE
    |--------------------------------------------------------------------------
    */

    public function positionStatistics()
    {
        $stats = $this->humanCapital->getOverviewStats();

        $departments = $this->humanCapital->getDepartments();

        $positions = $this->humanCapital->getPositions();

        /*
         * A "slot" is a position's planned headcount: the people who
         * currently hold it (filled) plus the open vacancies still to fill.
         * There is no slots column, so slots are derived from real records.
         */
        $filledSlots = array_sum(
            array_column($departments, 'employee_count')
        );

        $openSlots = $stats['vacancies'];

        $totalSlots = $filledSlots + $openSlots;

        $departmentsCovered = count(
            array_filter(
                $departments,
                fn($department) => (int) $department['position_count'] > 0
            )
        );

        $unassignedPositions = count(
            array_filter(
                $positions,
                fn($position) => (int) $position['employee_count'] === 0
            )
        );

        $posStats = [

            'total_positions'      => $stats['positions'],
            'total_slots'          => $totalSlots,
            'filled_slots'         => $filledSlots,
            'open_slots'           => $openSlots,
            'departments_covered'  => $departmentsCovered,
            'total_departments'    => $stats['departments'],
            'unassigned_positions' => $unassignedPositions,

        ];

        /*
         * Most recently created positions (newest first).
         */
        $posRecent = $positions;

        usort(
            $posRecent,
            fn($a, $b) => strcmp($b['created_at'], $a['created_at'])
        );

        $posRecent = array_slice($posRecent, 0, 5);

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