<?php

namespace App\Models;

use Core\Database;
use PDO;

class InterviewEvaluation
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }


    /*
    |--------------------------------------------------------------------------
    | Interview Evaluations
    |--------------------------------------------------------------------------
    */

    /**
     * Record one interviewer evaluation. The four ratings and the
     * recommendation are expected to be validated by the caller.
     */
    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO interview_evaluations
            (
                interview_id,
                evaluator_id,
                evaluator_role,
                technical_skills,
                communication,
                problem_solving,
                culture_fit,
                strengths,
                weaknesses,
                remarks,
                recommendation
            )
            VALUES
            (
                :interview_id,
                :evaluator_id,
                :evaluator_role,
                :technical_skills,
                :communication,
                :problem_solving,
                :culture_fit,
                :strengths,
                :weaknesses,
                :remarks,
                :recommendation
            )
        ");

        $stmt->execute([
            ':interview_id'     => (int) $data['interview_id'],
            ':evaluator_id'     => (int) $data['evaluator_id'],
            ':evaluator_role'   => $data['evaluator_role'] ?? null,
            ':technical_skills' => (int) $data['technical_skills'],
            ':communication'    => (int) $data['communication'],
            ':problem_solving'  => (int) $data['problem_solving'],
            ':culture_fit'      => (int) $data['culture_fit'],
            ':strengths'        => $data['strengths'] ?? null,
            ':weaknesses'       => $data['weaknesses'] ?? null,
            ':remarks'          => $data['remarks'] ?? null,
            ':recommendation'   => $data['recommendation']
        ]);

        return (int) $this->db->lastInsertId();
    }


    /**
     * Every evaluation for one application, newest first, with the
     * evaluator's display name. All evaluations are returned - the
     * latest row is the current one but earlier evaluators are never
     * hidden.
     */
    public function forApplication(int $applicationId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                ev.evaluation_id,
                ev.interview_id,
                ev.evaluator_id,
                ev.evaluator_role,
                ev.technical_skills,
                ev.communication,
                ev.problem_solving,
                ev.culture_fit,
                ev.strengths,
                ev.weaknesses,
                ev.remarks,
                ev.recommendation,
                ev.created_at,

                CONCAT(
                    evaluator.first_name,
                    ' ',
                    COALESCE(evaluator.middle_name, ''),
                    ' ',
                    evaluator.last_name
                ) AS evaluator_name,

                r.role_code AS evaluator_role_code

            FROM interview_evaluations ev

            INNER JOIN interviews i
                ON ev.interview_id = i.interview_id

            INNER JOIN users u
                ON ev.evaluator_id = u.user_id

            LEFT JOIN employees e
                ON u.employee_id = e.employee_id

            LEFT JOIN applicants evaluator
                ON e.applicant_id = evaluator.applicant_id

            LEFT JOIN roles r
                ON u.role_id = r.role_id

            WHERE i.application_id = :application_id

            ORDER BY
                ev.created_at DESC,
                ev.evaluation_id DESC
        ");

        $stmt->execute([
            ':application_id' => $applicationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function countForApplication(int $applicationId): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM interview_evaluations ev
            INNER JOIN interviews i
                ON ev.interview_id = i.interview_id
            WHERE i.application_id = :application_id
        ");

        $stmt->execute([
            ':application_id' => $applicationId
        ]);

        return (int) $stmt->fetchColumn();
    }
}
