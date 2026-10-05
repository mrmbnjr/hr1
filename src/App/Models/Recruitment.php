<?php

namespace App\Models;

use Core\Database;
use PDO;

class Recruitment
{
    /**
     * Shared projection for the position pickers. Callers append their own
     * WHERE / ORDER BY clause.
     */
    private const SELECT_POSITION = "
        SELECT
            p.position_id,
            p.position_name,
            p.department_id,
            p.role_id,
            d.department_name,
            r.role_code,
            r.role_name

        FROM positions p

        INNER JOIN departments d
            ON d.department_id = p.department_id

        LEFT JOIN roles r
            ON r.role_id = p.role_id
    ";

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
                jp.application_token,
                p.position_name,
                d.department_name,
                jp.employment_type,
                jp.vacancies,
                jp.max_applicants,
                jp.status,
                jp.application_deadline,

                COALESCE(
                    SUM(
                        CASE
                            WHEN a.application_id IS NOT NULL
                                AND (
                                    a.application_status IS NULL
                                    OR a.application_status <> 'Rejected'
                                )
                            THEN 1 ELSE 0
                        END
                    ),
                    0
                ) AS applicants,

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
                jp.application_token,
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
        $sql = self::SELECT_POSITION . "
            WHERE NOT EXISTS (
                SELECT 1
                FROM job_postings jp
                WHERE jp.position_id = p.position_id
            )

            ORDER BY
                d.department_name,
                p.position_name
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
        $maxApplicants = (int) ($data['max_applicants'] ?? 0);

        if ($maxApplicants < 1) {
            throw new \InvalidArgumentException(
                'Maximum applicants must be at least 1.'
            );
        }

        // A position can have only one posting, regardless of its status.
        $check = $this->db->prepare("
            SELECT posting_id
            FROM job_postings
            WHERE position_id = :position_id
            LIMIT 1
        ");

        $check->execute([
            ':position_id' => $data['position_id']
        ]);

        if ($check->fetch()) {
            throw new \InvalidArgumentException(
                'This position already has a job posting.'
            );
        }

        // Generate a unique token for the public application URL
        do {
            $applicationToken = bin2hex(random_bytes(16));

            $tokenCheck = $this->db->prepare("
                SELECT posting_id
                FROM job_postings
                WHERE application_token = ?
                LIMIT 1
            ");

            $tokenCheck->execute([$applicationToken]);

        } while ($tokenCheck->fetch());


        $sql = "
            INSERT INTO job_postings
            (
                position_id,
                title,
                description,
                requirements,
                academic_document_required,
                employment_type,
                vacancies,
                max_applicants,
                status,
                application_deadline,
                created_by,
                application_token
            )
            VALUES
            (
                :position_id,
                :title,
                :description,
                :requirements,
                :academic_document_required,
                :employment_type,
                :vacancies,
                :max_applicants,
                'Open',
                :application_deadline,
                :created_by,
                :application_token
            )
        ";


        $stmt = $this->db->prepare($sql);


        try {
            return $stmt->execute([
                ':position_id' => $data['position_id'],
                ':title' => $data['title'],
                ':description' => $data['description'],
                ':requirements' => $data['requirements'],
                ':academic_document_required' => !empty($data['academic_document_required']) ? 1 : 0,
                ':employment_type' => $data['employment_type'],
                ':vacancies' => $data['vacancies'],
                ':max_applicants' => $maxApplicants,
                ':application_deadline' => $data['application_deadline'],
                ':created_by' => $data['created_by'],
                ':application_token' => $applicationToken
            ]);
        } catch (\PDOException $e) {
            if ($this->isPositionDuplicate($e)) {
                throw new \InvalidArgumentException(
                    'This position already has a job posting.',
                    0,
                    $e
                );
            }

            throw $e;
        }
    }

    public function close(int $id)
    {
        $stmt = $this->db->prepare("
            UPDATE job_postings
            SET status = 'Closed',
                closed_at = CURRENT_TIMESTAMP
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

                p.position_id,
                p.position_name,
                p.department_id,
                p.role_id,

                d.department_name,

                r.role_code,
                r.role_name

            FROM job_postings jp

            LEFT JOIN positions p
                ON p.position_id = jp.position_id

            LEFT JOIN departments d
                ON d.department_id = p.department_id

            LEFT JOIN roles r
                ON r.role_id = p.role_id

            WHERE jp.posting_id = ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update(array $data)
    {
        $maxApplicants = (int) ($data['max_applicants'] ?? 0);

        if ($maxApplicants < 1) {
            throw new \InvalidArgumentException(
                'Maximum applicants must be at least 1.'
            );
        }

        $check = $this->db->prepare("
            SELECT posting_id
            FROM job_postings
            WHERE position_id = :position_id
              AND posting_id <> :posting_id
            LIMIT 1
        ");
        $check->execute([
            ':position_id' => $data['position_id'],
            ':posting_id' => $data['posting_id']
        ]);

        if ($check->fetch()) {
            throw new \InvalidArgumentException(
                'This position already has a job posting.'
            );
        }

        $newStatus = (string) ($data['status'] ?? 'Open');

        /*
         * Keep closed_at aligned with the status transition so the retention
         * window is measured from the moment the posting was actually closed:
         *   - first move to Closed -> stamp now
         *   - stays Closed         -> keep the original stamp
         *   - reopened             -> clear it
         */
        $current = $this->db->prepare("
            SELECT status, closed_at
            FROM job_postings
            WHERE posting_id = ?
            LIMIT 1
        ");
        $current->execute([$data['posting_id']]);
        $existing = $current->fetch(PDO::FETCH_ASSOC);

        $closedAt = null;

        if ($newStatus === 'Closed') {

            $closedAt = $existing['closed_at'] ?? null;

            if (!$existing || $existing['status'] !== 'Closed' || $closedAt === null) {
                $closedAt = date('Y-m-d H:i:s');
            }
        }

        $sql = "
            UPDATE job_postings
            SET position_id = ?,
                title = ?,
                description = ?,
                requirements = ?,
                academic_document_required = ?,
                employment_type = ?,
                vacancies = ?,
                max_applicants = ?,
                status = ?,
                closed_at = ?,
                application_deadline = ?
            WHERE posting_id = ?
        ";

        $stmt = $this->db->prepare($sql);

        try {
            return $stmt->execute([
                $data['position_id'],
                $data['title'],
                $data['description'],
                $data['requirements'],
                !empty($data['academic_document_required']) ? 1 : 0,
                $data['employment_type'],
                $data['vacancies'],
                $maxApplicants,
                $newStatus,
                $closedAt,
                $data['application_deadline'],
                $data['posting_id']
            ]);
        } catch (\PDOException $e) {
            if ($this->isPositionDuplicate($e)) {
                throw new \InvalidArgumentException(
                    'This position already has a job posting.',
                    0,
                    $e
                );
            }

            throw $e;
        }
    }

    private function isPositionDuplicate(\PDOException $exception): bool
    {
        return (int) ($exception->errorInfo[1] ?? 0) === 1062
            && strpos(
                (string) ($exception->errorInfo[2] ?? ''),
                'uq_job_postings_position'
            ) !== false;
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("
            DELETE FROM job_postings
            WHERE posting_id = ?
        ");

        return $stmt->execute([$id]);
    }

    public function getPositionsForEdit(int $postingId)
    {
        $sql = self::SELECT_POSITION . "
            WHERE
                NOT EXISTS (
                    SELECT 1
                    FROM job_postings jp
                    WHERE jp.position_id = p.position_id
                    AND jp.posting_id <> :posting_id
                )

            ORDER BY
                d.department_name,
                p.position_name
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':posting_id' => $postingId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getJob(int $postingId): ?array
    {
        $sql = "
            SELECT
                jp.posting_id,
                jp.title,
                jp.description,
                jp.requirements,
                jp.employment_type,
                jp.vacancies,
                jp.academic_document_required,
                jp.status,
                jp.application_deadline,
                jp.created_at,

                p.position_name,

                d.department_name

            FROM job_postings jp

            LEFT JOIN positions p
                ON p.position_id = jp.position_id

            LEFT JOIN departments d
                ON d.department_id = p.department_id

            WHERE jp.posting_id = :posting_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':posting_id' => $postingId
        ]);

        $job = $stmt->fetch(PDO::FETCH_ASSOC);

        return $job ?: null;
    }


    /**
     * Get overall statistics for a job posting.
     */
    public function getSummary(int $postingId): array
    {
        $sql = "
            SELECT

                COUNT(ap.application_id) AS total_applications,

                SUM(
                    CASE
                        WHEN ap.application_status = 'Submitted'
                        THEN 1
                        ELSE 0
                    END
                ) AS submitted,

                SUM(
                    CASE
                        WHEN ap.application_status = 'Under Review'
                        THEN 1
                        ELSE 0
                    END
                ) AS under_review,

                SUM(
                    CASE
                        WHEN ap.application_status = 'Interview'
                        THEN 1
                        ELSE 0
                    END
                ) AS interview,

                SUM(
                    CASE
                        WHEN ap.application_status = 'Hired'
                        THEN 1
                        ELSE 0
                    END
                ) AS hired,

                SUM(
                    CASE
                        WHEN ap.application_status = 'Rejected'
                        THEN 1
                        ELSE 0
                    END
                ) AS rejected

            FROM applications ap

            WHERE ap.posting_id = :posting_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':posting_id' => $postingId
        ]);

        $summary = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$summary) {
            return [
                'total_applications' => 0,
                'submitted' => 0,
                'under_review' => 0,
                'interview' => 0,
                'hired' => 0,
                'rejected' => 0
            ];
        }

        return [
            'total_applications' => (int) ($summary['total_applications'] ?? 0),
            'submitted' => (int) ($summary['submitted'] ?? 0),
            'under_review' => (int) ($summary['under_review'] ?? 0),
            'interview' => (int) ($summary['interview'] ?? 0),
            'hired' => (int) ($summary['hired'] ?? 0),
            'rejected' => (int) ($summary['rejected'] ?? 0)
        ];
    }


    /**
     * Get the number of scheduled interviews.
     */
    public function getInterviewCount(int $postingId): int
    {
        $sql = "
            SELECT COUNT(i.interview_id)

            FROM interviews i

            INNER JOIN applications ap
                ON ap.application_id = i.application_id

            WHERE ap.posting_id = :posting_id
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':posting_id' => $postingId
        ]);

        return (int) $stmt->fetchColumn();
    }


    /**
     * Get applications grouped by status.
     */
    public function getStatusBreakdown(int $postingId): array
    {
        $sql = "
            SELECT
                ap.application_status,
                COUNT(*) AS total

            FROM applications ap

            WHERE ap.posting_id = :posting_id

            GROUP BY ap.application_status
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':posting_id' => $postingId
        ]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $statuses = [
            'Submitted' => 0,
            'Under Review' => 0,
            'Interview' => 0,
            'Hired' => 0,
            'Rejected' => 0
        ];

        foreach ($results as $row) {

            $status = $row['application_status'];

            if (array_key_exists($status, $statuses)) {
                $statuses[$status] = (int) $row['total'];
            }
        }

        return $statuses;
    }


    /**
     * Get daily application totals for the last 14 days.
     */
    public function getApplicationTrend(int $postingId): array
    {
        $sql = "
            SELECT
                DATE(ap.applied_at) AS application_date,
                COUNT(*) AS total

            FROM applications ap

            WHERE ap.posting_id = :posting_id

            AND ap.applied_at >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)

            GROUP BY DATE(ap.applied_at)

            ORDER BY application_date ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':posting_id' => $postingId
        ]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        /*
        |----------------------------------------------------------------------
        | Build all 14 dates.
        |----------------------------------------------------------------------
        */

        $trend = [];

        for ($i = 13; $i >= 0; $i--) {

            $date = date(
                'Y-m-d',
                strtotime("-{$i} days")
            );

            $trend[$date] = 0;
        }

        foreach ($rows as $row) {

            $date = $row['application_date'];

            if (array_key_exists($date, $trend)) {
                $trend[$date] = (int) $row['total'];
            }
        }

        return $trend;
    }


    /**
     * Get recent applicants for this job posting.
     */
    public function getRecentApplicants(
        int $postingId,
        int $limit = 8
    ): array {

        $limit = max(1, min($limit, 50));

        $sql = "
            SELECT
                ap.application_id,
                ap.applicant_id,
                ap.application_status,
                ap.applied_at,

                CONCAT(
                    a.first_name,
                    ' ',
                    COALESCE(a.middle_name, ''),
                    CASE
                        WHEN a.middle_name IS NOT NULL
                        AND a.middle_name <> ''
                        THEN ' '
                        ELSE ''
                    END,
                    a.last_name
                ) AS fullname,

                a.email,

                ai.overall_score AS ai_score,
                ai.recommendation

            FROM applications ap

            INNER JOIN applicants a
                ON a.applicant_id = ap.applicant_id

            LEFT JOIN ai_screening ai
                ON ai.application_id = ap.application_id

            WHERE ap.posting_id = :posting_id

            ORDER BY ap.applied_at DESC

            LIMIT {$limit}
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':posting_id' => $postingId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Get every applicant and the evaluation data used by job statistics.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getStatisticsCandidates(int $postingId): array
    {
        $sql = "
            SELECT
                ap.application_id,
                ap.applicant_id,
                ap.application_status,
                ap.applied_at,
                ap.resume_file,
                ap.academic_document_file,
                jp.academic_document_required,

                CONCAT(
                    a.first_name,
                    ' ',
                    COALESCE(a.middle_name, ''),
                    CASE
                        WHEN a.middle_name IS NOT NULL
                        AND a.middle_name <> ''
                        THEN ' '
                        ELSE ''
                    END,
                    a.last_name
                ) AS fullname,

                a.email,

                ai.overall_score AS ai_score,
                ai.keyword_score AS requirement_match,
                ai.skills_score,
                ai.experience_score,
                ai.education_score,
                ai.recommendation,
                ai.strengths,
                ai.concerns,

                i.interview_date,
                i.result AS interview_result,

                (
                    SELECT COUNT(*)
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                ) AS academic_document_count

            FROM applications ap

            INNER JOIN applicants a
                ON a.applicant_id = ap.applicant_id

            INNER JOIN job_postings jp
                ON jp.posting_id = ap.posting_id

            LEFT JOIN ai_screening ai
                ON ai.application_id = ap.application_id

            LEFT JOIN interviews i
                ON i.application_id = ap.application_id

            WHERE ap.posting_id = :posting_id

            ORDER BY ap.applied_at DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':posting_id' => $postingId,
        ]);

        $candidates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($candidates as &$candidate) {
            $missingDocuments = [];

            if (trim((string) ($candidate['resume_file'] ?? '')) === '') {
                $missingDocuments[] = 'Resume';
            }

            $hasAcademicDocument = (int) ($candidate['academic_document_count'] ?? 0) > 0
                || trim((string) ($candidate['academic_document_file'] ?? '')) !== '';

            if (!empty($candidate['academic_document_required']) && !$hasAcademicDocument) {
                $missingDocuments[] = 'Academic document';
            }

            $candidate['missing_documents'] = $missingDocuments;
        }
        unset($candidate);

        return $candidates;
    }

}