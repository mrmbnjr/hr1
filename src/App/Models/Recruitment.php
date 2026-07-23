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
                jp.posting_id,
                jp.title,
                jp.employment_type,
                jp.vacancies,
                jp.status,
                jp.application_deadline,
                d.department_name,

                COUNT(a.application_id) AS applicants,

                SUM(
                    CASE
                        WHEN a.application_status = 'Under Review'
                        THEN 1 ELSE 0
                    END
                ) AS under_review,

                SUM(
                    CASE
                        WHEN a.application_status = 'Interview'
                        THEN 1 ELSE 0
                    END
                ) AS interview,

                SUM(
                    CASE
                        WHEN a.application_status = 'Hired'
                        THEN 1 ELSE 0
                    END
                ) AS hired

            FROM job_postings jp

            JOIN departments d
                ON d.department_id = jp.department_id

            LEFT JOIN applications a
                ON a.posting_id = jp.posting_id

            GROUP BY
                jp.posting_id,
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

    public function createJob(array $data)
    {
        $sql = "
            INSERT INTO job_postings
            (
                department_id,
                title,
                description,
                requirements,
                employment_type,
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
            ':vacancies' => $data['vacancies'],
            ':application_deadline' => $data['application_deadline'],
            ':created_by' => $_SESSION['user_id']
        ]);
    }

    public function close(int $id)
    {
        $stmt = $this->db->prepare("
            UPDATE job_postings
            SET status = 'Closed'
            WHERE posting_id = ?
        ");

        return $stmt->execute([$id]);
    }

    public function getJobById(int $id)
    {
        $sql = "
            SELECT *
            FROM job_postings
            WHERE posting_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function updateJob(array $data)
    {
        $sql = "
            UPDATE job_postings
            SET department_id = ?,
                title = ?,
                description = ?,
                requirements = ?,
                employment_type = ?,
                vacancies = ?,
                application_deadline = ?
            WHERE posting_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['department_id'],
            $data['title'],
            $data['description'],
            $data['requirements'],
            $data['employment_type'],
            $data['vacancies'],
            $data['application_deadline'],
            $data['posting_id']
        ]);
    }
}

