<?php

namespace App\Models;

use Core\Database;
use PDO;

class HumanCapital
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }


    public function getOverviewStats()
    {
        return [

            'departments' => (int) $this->db->query("
                SELECT COUNT(*)
                FROM departments
            ")->fetchColumn(),

            'positions' => (int) $this->db->query("
                SELECT COUNT(*)
                FROM positions
            ")->fetchColumn(),

            'vacancies' => (int) $this->db->query("
                SELECT COALESCE(SUM(vacancies),0)
                FROM job_postings
                WHERE status='Open'
            ")->fetchColumn(),

            'hiring_departments' => (int) $this->db->query("
                SELECT COUNT(DISTINCT p.department_id)
                FROM job_postings j
                INNER JOIN positions p
                    ON p.position_id = j.position_id
                WHERE j.status = 'Open'
            ")->fetchColumn(),

        ];
    }

    public function getDepartments()
    {
        $stmt = $this->db->query("

            SELECT

                d.department_id,
                d.department_name,
                d.created_at,

                COUNT(DISTINCT p.position_id) AS position_count,

                COUNT(DISTINCT e.employee_id) AS employee_count,

                COALESCE(
                    SUM(
                        CASE
                            WHEN jp.status = 'Open'
                            THEN jp.vacancies
                            ELSE 0
                        END
                    ),
                    0
                ) AS vacancies

            FROM departments d

            LEFT JOIN positions p
                ON p.department_id = d.department_id

            LEFT JOIN job_postings jp
                ON jp.position_id = p.position_id

            LEFT JOIN applications ap
                ON ap.posting_id = jp.posting_id

            LEFT JOIN employees e
                ON e.application_id = ap.application_id

            GROUP BY

                d.department_id,
                d.department_name,
                d.created_at

            ORDER BY

                d.department_name

        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartment(int $id)
    {
        $stmt = $this->db->prepare("

            SELECT
                d.department_id,
                d.department_name,
                d.created_at,

                COUNT(DISTINCT p.position_id) AS position_count,

                COUNT(DISTINCT e.employee_id) AS employee_count,

                COALESCE(
                    SUM(
                        CASE
                            WHEN j.status = 'Open'
                            THEN j.vacancies
                            ELSE 0
                        END
                    ),
                    0
                ) AS vacancies

            FROM departments d

            LEFT JOIN positions p
                ON p.department_id = d.department_id

            LEFT JOIN job_postings j
                ON j.position_id = p.position_id

            LEFT JOIN applications ap
                ON ap.posting_id = j.posting_id

            LEFT JOIN employees e
                ON e.application_id = ap.application_id

            WHERE d.department_id = ?

            GROUP BY
                d.department_id,
                d.department_name,
                d.created_at;
        ");

        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createDepartment(array $data)
    {
        $stmt=$this->db->prepare("

            INSERT INTO departments
            (department_name)
            VALUES
            (?)
        ");

        return $stmt->execute([
            $data['department_name']
        ]);
    }
    public function updateDepartment(int $id, array $data)
    {
        $stmt=$this->db->prepare("

            UPDATE departments

            SET
                department_name=?
            WHERE department_id=?
        ");

        return $stmt->execute([

            $data['department_name'],
            $id

        ]);
    }
    public function deleteDepartment(int $id)
    {
        $stmt=$this->db->prepare("
            SELECT COUNT(*)
            FROM positions
            WHERE department_id=?
        ");

        $stmt->execute([$id]);

        if($stmt->fetchColumn()>0){

            return [

                'success'=>false,

                'message'=>'This department still has positions.'

            ];

        }

        $stmt=$this->db->prepare("

            DELETE FROM departments

            WHERE department_id=?

        ");

        $stmt->execute([$id]);

        return [

            'success'=>true

        ];
    }

    public function getPositions()
    {
        $stmt = $this->db->query("

            SELECT

                p.position_id,
                p.position_name,

                d.department_name,

                COUNT(DISTINCT e.employee_id) AS employee_count,

                COALESCE(
                    SUM(
                        CASE
                            WHEN jp.status = 'Open'
                            THEN jp.vacancies
                            ELSE 0
                        END
                    ),
                    0
                ) AS vacancies,

                CASE

                    WHEN COALESCE(
                        SUM(
                            CASE
                                WHEN jp.status = 'Open'
                                THEN jp.vacancies
                                ELSE 0
                            END
                        ),
                        0
                    ) > 0

                    THEN 'Open'

                    ELSE 'Closed'

                END AS status

            FROM positions p

            INNER JOIN departments d
                ON d.department_id = p.department_id

            LEFT JOIN job_postings jp
                ON jp.position_id = p.position_id

            LEFT JOIN applications ap
                ON ap.posting_id = jp.posting_id

            LEFT JOIN employees e
                ON e.application_id = ap.application_id

            GROUP BY

                p.position_id,
                p.position_name,
                d.department_name

            ORDER BY

                d.department_name,
                p.position_name

        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getOrganizationTree()
    {
        $departments = $this->getDepartments();

        foreach ($departments as &$department) {

            // Get all positions in this department
            $stmt = $this->db->prepare("

            SELECT
                position_id,
                position_name
            FROM positions
            WHERE department_id = ?
            ORDER BY position_name

            ");

            $stmt->execute([
                $department['department_id']
            ]);

            $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Get employees for every position
            foreach ($positions as &$position) {

            $employeeStmt = $this->db->prepare("

            SELECT
                e.employee_id,
                e.employee_number,
                e.hire_date,
                e.employment_status,

                CONCAT(
                    a.first_name,
                    IF(
                        a.middle_name IS NULL OR a.middle_name = '',
                        '',
                        CONCAT(' ', a.middle_name)
                    ),
                    ' ',
                    a.last_name
                ) AS employee_name

            FROM employees e

            INNER JOIN applications ap
                ON ap.application_id = e.application_id

            INNER JOIN applicants a
                ON a.applicant_id = ap.applicant_id

            INNER JOIN job_postings jp
                ON jp.posting_id = ap.posting_id

            WHERE jp.position_id = ?

            ORDER BY
                a.last_name,
                a.first_name;

            ");

            $employeeStmt->execute([
                $position['position_id']
            ]);

            $position['employees'] = $employeeStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            $department['positions'] = $positions;
        }

        return $departments;
    }

    public function getDepartmentLookup()
    {
        return $this->db->query("

            SELECT

                department_id,
                department_name

            FROM departments

            ORDER BY department_name

        ")->fetchAll(PDO::FETCH_ASSOC);
    }
}