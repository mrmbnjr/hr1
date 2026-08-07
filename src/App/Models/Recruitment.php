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

    public function getAllJobs(): array
    {
        $sql = "
            SELECT
                jp.posting_id,
                jp.title,
                p.position_name,
                d.department_name,
                jp.employment_type,
                jp.vacancies,
                jp.status,
                jp.application_deadline,

                COALESCE(COUNT(a.application_id),0) AS applicants,

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

            LEFT JOIN positions p
                ON p.position_id = jp.position_id

            LEFT JOIN departments d
                ON d.department_id = p.department_id
                
            LEFT JOIN applications a
                ON a.posting_id = jp.posting_id

            WHERE jp.status <> 'Archived'
            
            GROUP BY
                jp.posting_id,
                jp.title,
                jp.employment_type,
                jp.vacancies,
                jp.status,
                jp.application_deadline,
                p.position_name,
                d.department_name

            ORDER BY jp.created_at DESC
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

        public function getPositions()
    {
        $stmt = $this->db->query("

            SELECT

                p.position_id,
                p.position_name,
                d.department_name

            FROM positions p

            INNER JOIN departments d

                ON d.department_id = p.department_id

            ORDER BY

                d.department_name,
                p.position_name

        ");

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
                position_id,
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
                :position_id,
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
            ':position_id' => $data['position_id'],
            ':title' => $data['title'],
            ':description' => $data['description'],
            ':requirements' => $data['requirements'],
            ':employment_type' => $data['employment_type'],
            ':vacancies' => $data['vacancies'],
            ':application_deadline' => $data['application_deadline'],
            ':created_by' => $data['created_by']
        ]);
    }

    public function close(int $id)
    {
        $stmt = $this->db->prepare("
            UPDATE job_postings
            SET status = 'Closed'
            WHERE posting_id = ?
            AND status = 'Open'
        ");

        return $stmt->execute([$id]);
    }

    public function getJobById(int $id)
    {
        $sql = "
            SELECT
                jp.*,
                d.department_name
            FROM job_postings jp
            LEFT JOIN positions p
                ON p.position_id = jp.position_id
            LEFT JOIN departments d
                ON d.department_id = p.department_id
                WHERE jp.posting_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update(array $data)
    {
        $sql = "
            UPDATE job_postings
            SET position_id = ?,
                title = ?,
                description = ?,
                requirements = ?,
                employment_type = ?,
                vacancies = ?,
                status = ?,
                application_deadline = ?
            WHERE posting_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            $data['position_id'],
            $data['title'],
            $data['description'],
            $data['requirements'],
            $data['employment_type'],
            $data['vacancies'],
            $data['status'],
            $data['application_deadline'],
            $data['posting_id']
        ]);
    }
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM job_postings
            WHERE posting_id = ?
        ");

        return $stmt->execute([$id]);
    }

    public function changeStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare("
            UPDATE job_postings
            SET status = ?
            WHERE posting_id = ?
        ");

        return $stmt->execute([$status, $id]);
    }
}

