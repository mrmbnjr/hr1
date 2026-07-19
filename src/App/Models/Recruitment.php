<?php

namespace App\Models;

use Core\Database;
use PDO;

class Recruitment
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getAllJobs()
    {
        $sql = "
            SELECT
                jp.position_id,
                jp.title,
                jp.employment_type,
                jp.vacancies,
                jp.status,
                jp.application_deadline,
                d.department_name,

                COUNT(a.application_id) AS applicants,

                SUM(CASE
                        WHEN a.application_status = 'Shortlisted'
                        THEN 1 ELSE 0
                    END) AS shortlisted,

                SUM(CASE
                        WHEN a.application_status = 'Interview'
                        THEN 1 ELSE 0
                    END) AS interview,

                SUM(CASE
                        WHEN a.application_status = 'Hired'
                        THEN 1 ELSE 0
                    END) AS hired

            FROM job_positions jp

            JOIN departments d
                ON d.department_id = jp.department_id

            LEFT JOIN applications a
                ON a.position_id = jp.position_id

            GROUP BY
                jp.position_id,
                jp.title,
                jp.employment_type,
                jp.vacancies,
                jp.status,
                jp.application_deadline,
                d.department_name

            ORDER BY jp.created_at DESC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getDepartments()
    {
        $stmt = $this->db->query("
            SELECT department_id, department_name
            FROM departments
            ORDER BY department_name
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // NEW: Save a new job posting
    public function createJob(array $data)
    {
        $sql = "
            INSERT INTO job_positions
            (
                department_id,
                title,
                description,
                requirements,
                employment_type,
                salary,
                vacancies,
                status,
                application_deadline,
                created_by
            )
            VALUES
            (
                :department_id,
                :title,
                :description,
                :requirements,
                :employment_type,
                :salary,
                :vacancies,
                'Open',
                :application_deadline,
                :created_by
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':department_id' => $data['department_id'],
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':requirements' => $data['requirements'],
            ':employment_type' => $data['employment_type'],
            ':salary' => $data['salary'],
            ':vacancies' => $data['vacancies'],
            ':application_deadline' => $data['application_deadline'],
            ':created_by' => $_SESSION['user_id']
        ]);
    }

    public function close(int $id)
    {
        $stmt = $this->db->prepare("
            UPDATE job_positions
            SET status = 'Closed'
            WHERE position_id = ?
        ");

        return $stmt->execute([$id]);
    }
}

