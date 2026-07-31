<?php

namespace App\Models;

use Core\Database;
use PDO;

class Applicant
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getAllApplicants()
    {
        $sql = "
            SELECT
                a.applicant_id,

                CONCAT(
                    a.first_name,
                    ' ',
                    COALESCE(a.middle_name, ''),
                    ' ',
                    a.last_name
                ) AS fullname,

                a.email,
                a.phone,

                jp.title AS position,

                ap.application_id,
                ap.resume_file,
                ap.application_status,
                ap.applied_at,

                ai.overall_score AS ai_score,
                ai.ai_summary AS screening_summary,

                i.interview_date,

                ap.application_status AS hiring_decision

            FROM applicants a

            INNER JOIN applications ap
                ON a.applicant_id = ap.applicant_id

            INNER JOIN job_postings jp
                ON ap.posting_id = jp.posting_id

            LEFT JOIN ai_screening ai
                ON ap.application_id = ai.application_id

            LEFT JOIN interviews i
                ON ap.application_id = i.application_id

            ORDER BY ap.applied_at DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getApplicantById(int $id)
    {
        $sql = "
            SELECT
                a.applicant_id,

                CONCAT(
                    a.first_name,
                    ' ',
                    COALESCE(a.middle_name, ''),
                    ' ',
                    a.last_name
                ) AS fullname,

                a.email,
                a.phone,
                a.address,

                jp.posting_id,
                jp.title AS position,

                ap.application_id,
                ap.resume_file,
                ap.cover_letter_file,
                ap.application_status,
                ap.applied_at,

                ai.overall_score AS ai_score,
                ai.skills_score,
                ai.experience_score,
                ai.education_score,
                ai.keyword_score,
                ai.recommendation,
                ai.extracted_skills,
                ai.strengths,
                ai.concerns,
                ai.ai_summary,

                i.interview_id,
                i.interview_date,
                i.location,
                i.interview_type,
                i.remarks,
                i.result,

                CONCAT(
                    interviewer.first_name,
                    ' ',
                    COALESCE(interviewer.middle_name, ''),
                    ' ',
                    interviewer.last_name
                ) AS interviewer_name,

                ap.application_status AS hiring_decision

            FROM applicants a

            INNER JOIN applications ap
                ON a.applicant_id = ap.applicant_id

            INNER JOIN job_postings jp
                ON ap.posting_id = jp.posting_id

            LEFT JOIN ai_screening ai
                ON ap.application_id = ai.application_id

            LEFT JOIN interviews i
                ON ap.application_id = i.application_id

            LEFT JOIN users u
                ON i.interviewer_id = u.user_id

            LEFT JOIN employees e
                ON u.employee_id = e.employee_id

            LEFT JOIN applications ia
                ON e.application_id = ia.application_id

            LEFT JOIN applicants interviewer
                ON ia.applicant_id = interviewer.applicant_id

            WHERE a.applicant_id = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllJobPostings()
    {
        $sql = "
            SELECT
                posting_id,
                title
            FROM job_postings
            WHERE status = 'Open'
            ORDER BY title ASC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getManagers()
    {
        $sql = "
            SELECT
                u.user_id,

                CONCAT(
                    a.first_name,
                    ' ',
                    COALESCE(a.middle_name, ''),
                    ' ',
                    a.last_name
                ) AS fullname,

                r.role_name

            FROM users u

            INNER JOIN roles r
                ON u.role_id = r.role_id

            INNER JOIN employees e
                ON u.employee_id = e.employee_id

            INNER JOIN applications ap
                ON e.application_id = ap.application_id

            INNER JOIN applicants a
                ON ap.applicant_id = a.applicant_id

            WHERE r.role_code IN ('ADMIN', 'HR', 'MANAGER')

            ORDER BY
                r.role_name,
                fullname
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}