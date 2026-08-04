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
                FROM job_postings
                WHERE status = 'Open'
            ")->fetchColumn(),

            'vacancies' => (int) $this->db->query("
                SELECT COALESCE(SUM(vacancies),0)
                FROM job_postings
                WHERE status='Open'
            ")->fetchColumn(),

            'hiring_departments' => (int) $this->db->query("
                SELECT COUNT(DISTINCT department_id)
                FROM job_postings
                WHERE status='Open'
            ")->fetchColumn(),

        ];
    }

    public function getDepartments()
    {
        $stmt = $this->db->query("

            SELECT

                d.department_id,
                d.department_name,
                d.description,

                COUNT(j.posting_id) AS positions,

                COALESCE(SUM(
                    CASE
                        WHEN j.status='Open'
                        THEN j.vacancies
                        ELSE 0
                    END
                ),0) AS vacancies

            FROM departments d

            LEFT JOIN job_postings j

                ON j.department_id=d.department_id

            GROUP BY d.department_id

            ORDER BY d.department_name

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
            (

                department_name,
                description

            )

            VALUES

            (?,?)

        ");

        return $stmt->execute([

            $data['department_name'],
            $data['description']

        ]);
    }
    public function updateDepartment($id,$data)
    {
        $stmt=$this->db->prepare("

            UPDATE departments

            SET

                department_name=?,
                description=?

            WHERE department_id=?

        ");

        return $stmt->execute([

            $data['department_name'],
            $data['description'],
            $id

        ]);
    }
    public function deleteDepartment($id)
    {
        $stmt=$this->db->prepare("

            SELECT COUNT(*)

            FROM job_postings

            WHERE department_id=?

        ");

        $stmt->execute([$id]);

        if($stmt->fetchColumn()>0){

            return [

                'success'=>false,

                'message'=>'This department still has job postings.'

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

                d.department_name,

                (

                    SELECT COUNT(*)

                    FROM applications a

                    WHERE a.posting_id=j.posting_id

                ) applicants

            FROM job_postings j

            JOIN departments d

                ON d.department_id=j.department_id

            ORDER BY

                d.department_name,

                j.title

        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    public function getOrganizationTree()
    {
        $departments=$this->getDepartments();

        foreach($departments as &$department){

            $stmt=$this->db->prepare("

                SELECT

                    posting_id,

                    title,

                    employment_type,

                    vacancies,

                    status

                FROM job_postings

                WHERE department_id=?

                ORDER BY title

            ");

            $stmt->execute([

                $department['department_id']

            ]);

            $department['positions']=$stmt->fetchAll(PDO::FETCH_ASSOC);

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