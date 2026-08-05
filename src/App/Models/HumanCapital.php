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

            COUNT(DISTINCT p.position_id) AS positions,

            COALESCE(SUM(
                CASE
                    WHEN j.status = 'Open'
                    THEN j.vacancies
                    ELSE 0
                END
            ),0) AS vacancies

        FROM departments d

        LEFT JOIN positions p
            ON p.department_id = d.department_id

        LEFT JOIN job_postings j
            ON j.position_id = p.position_id

        GROUP BY
            d.department_id,
            d.department_name,
            d.created_at

        ORDER BY
            d.department_name

        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartment($id)
    {
        $stmt=$this->db->prepare("

            SELECT *

            FROM departments

            WHERE department_id=?

        ");

        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createDepartment($data)
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
    public function updateDepartment($id,$data)
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
    public function deleteDepartment($id)
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

    public function getJobPostings()
    {
        $stmt=$this->db->query("

            SELECT

                j.*,
                p.position_name,
                d.department_name,

                (

                    SELECT COUNT(*)

                    FROM applications a

                    WHERE a.posting_id=j.posting_id

                ) applicants

            FROM job_postings j

            INNER JOIN positions p

                ON p.position_id = j.position_id

            INNER JOIN departments d

                ON d.department_id = p.department_id

            ORDER BY

                d.department_name,
                p.position_name,
                j.title

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

                ORDER BY title

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
                        a.middle_name IS NULL OR a.middle_name='',
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

                WHERE EXISTS (

                    SELECT 1

                    FROM job_postings jp

                    WHERE jp.posting_id = ap.posting_id
                    AND jp.position_id = ?

                )

                ORDER BY

                    a.last_name,
                    a.first_name

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