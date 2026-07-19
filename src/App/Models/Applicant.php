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

    /**
     * Get all applicants with application details
     */
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

                ai.match_score AS ai_score,
                ai.ai_summary AS screening_summary,

                i.interview_date,

                COALESCE(jo.status, 'Pending') AS hiring_decision

            FROM applicants a

            INNER JOIN applications ap
                ON a.applicant_id = ap.applicant_id

            INNER JOIN job_positions jp
                ON ap.position_id = jp.position_id

            LEFT JOIN ai_screening ai
                ON ap.application_id = ai.application_id

            LEFT JOIN interviews i
                ON ap.application_id = i.application_id

            LEFT JOIN job_offers jo
                ON ap.application_id = jo.application_id

            ORDER BY ap.applied_at DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get applicant by ID
     */
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

                jp.position_id,
                jp.title AS position,

                ap.application_id,
                ap.resume_file,
                ap.cover_letter_file,
                ap.application_status,
                ap.applied_at,

                ai.match_score AS ai_score,
                ai.recommendation,
                ai.extracted_skills,
                ai.strengths,
                ai.weaknesses,
                ai.ai_summary,

                i.interview_date,
                i.interview_type,
                i.remarks,
                i.result,

                jo.offered_salary,
                jo.start_date,
                jo.status AS hiring_decision

            FROM applicants a

            INNER JOIN applications ap
                ON a.applicant_id = ap.applicant_id

            INNER JOIN job_positions jp
                ON ap.position_id = jp.position_id

            LEFT JOIN ai_screening ai
                ON ap.application_id = ai.application_id

            LEFT JOIN interviews i
                ON ap.application_id = i.application_id

            LEFT JOIN job_offers jo
                ON ap.application_id = jo.application_id

            WHERE a.applicant_id = :id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get all job positions
     */
    public function getAllPositions()
    {
        $sql = "
            SELECT
                position_id,
                title
            FROM job_positions
            WHERE status = 'Open'
            ORDER BY title ASC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }
}