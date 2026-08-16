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
                i.interviewer_id,
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

            r.role_code AS interviewer_role,
            r.role_name AS interviewer_role_name,

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

            LEFT JOIN roles r
                ON u.role_id = r.role_id

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

    public function markAsUnderReview(int $applicantId)
    {
        $stmt = $this->db->prepare("
            UPDATE applications
            SET application_status = 'Under Review'
            WHERE applicant_id = :applicant_id
            AND application_status = 'Submitted'
        ");

        return $stmt->execute([
            ':applicant_id' => $applicantId
        ]);
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

                r.role_code,
                r.role_name

            FROM users u

            INNER JOIN roles r
                ON u.role_id = r.role_id

            LEFT JOIN employees e
                ON u.employee_id = e.employee_id

            LEFT JOIN applications ap
                ON e.application_id = ap.application_id

            LEFT JOIN applicants a
                ON ap.applicant_id = a.applicant_id

            WHERE u.status = 'Active'
            AND r.role_code IN ('ADMIN', 'HR', 'MGR')

            ORDER BY
                r.role_name,
                fullname
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function scheduleInterview(array $data)
    {
        $this->db->beginTransaction();

        try {

            $interviewDateTime = $data['interview_date'] . ' ' . $data['interview_time'] . ':00';

            // Check if interview already exists
            $check = $this->db->prepare("
                SELECT interview_id
                FROM interviews
                WHERE application_id = :application_id
            ");

            $check->execute([
                ':application_id' => $data['application_id']
            ]);

            if ($check->fetch()) {

                // Update existing interview
                $stmt = $this->db->prepare("
                    UPDATE interviews
                    SET
                        interviewer_id = :interviewer_id,
                        interview_type = :interview_type,
                        interview_date = :interview_date,
                        location = :location,
                        remarks = :remarks
                    WHERE application_id = :application_id
                ");

            } else {

                // Create new interview
                $stmt = $this->db->prepare("
                    INSERT INTO interviews
                    (
                        application_id,
                        interviewer_id,
                        interview_type,
                        interview_date,
                        location,
                        remarks
                    )
                    VALUES
                    (
                        :application_id,
                        :interviewer_id,
                        :interview_type,
                        :interview_date,
                        :location,
                        :remarks
                    )
                ");

            }

            $stmt->execute([
                ':application_id' => $data['application_id'],
                ':interviewer_id' => $data['interviewer_id'],
                ':interview_type' => $data['interview_type'],
                ':interview_date' => $interviewDateTime,
                ':location' => $data['location'],
                ':remarks' => $data['notes']
            ]);

            // Change applicant status to Interview
            $status = $this->db->prepare("
                UPDATE applications
                SET application_status = 'Interview'
                WHERE application_id = :application_id
            ");

            $status->execute([
                ':application_id' => $data['application_id']
            ]);

            $this->db->commit();

        } catch (\Exception $e) {

            $this->db->rollBack();

            throw $e;
        }
    }

    public function updateApplicationStatus(int $applicationId, string $status)
    {
        $this->db->beginTransaction();

        try {

            $postingStmt = $this->db->prepare("
                SELECT
                    posting_id
                FROM applications
                WHERE application_id = :application_id
            ");

            $postingStmt->execute([
                ':application_id' => $applicationId
            ]);

            $application = $postingStmt->fetch(PDO::FETCH_ASSOC);

            if (!$application) {
                throw new \Exception('Application not found.');
            }

            $postingId = (int) $application['posting_id'];

            if ($status === 'Hired') {

                $capacityStmt = $this->db->prepare("
                    SELECT
                        jp.vacancies,
                        COUNT(ap.application_id) AS hired_count
                    FROM job_postings jp

                    LEFT JOIN applications ap
                        ON ap.posting_id = jp.posting_id
                        AND ap.application_status = 'Hired'

                    WHERE jp.posting_id = :posting_id

                    GROUP BY
                        jp.posting_id,
                        jp.vacancies
                ");

                $capacityStmt->execute([
                    ':posting_id' => $postingId
                ]);

                $capacity = $capacityStmt->fetch(PDO::FETCH_ASSOC);

                if (!$capacity) {
                    throw new \Exception('Job posting not found.');
                }

                $vacancies = (int) $capacity['vacancies'];
                $hiredCount = (int) $capacity['hired_count'];

                if ($hiredCount >= $vacancies) {
                    throw new \Exception(
                        'This job posting has already reached its vacancy limit.'
                    );
                }
            }


            $statusStmt = $this->db->prepare("
                UPDATE applications
                SET application_status = :status
                WHERE application_id = :application_id
            ");

            $statusStmt->execute([
                ':status' => $status,
                ':application_id' => $applicationId
            ]);


            if ($status === 'Hired') {

                $checkStmt = $this->db->prepare("
                    SELECT
                        jp.vacancies,
                        COUNT(ap.application_id) AS hired_count

                    FROM job_postings jp

                    LEFT JOIN applications ap
                        ON ap.posting_id = jp.posting_id
                        AND ap.application_status = 'Hired'

                    WHERE jp.posting_id = :posting_id

                    GROUP BY
                        jp.posting_id,
                        jp.vacancies
                ");

                $checkStmt->execute([
                    ':posting_id' => $postingId
                ]);

                $posting = $checkStmt->fetch(PDO::FETCH_ASSOC);

                if (
                    $posting &&
                    (int) $posting['hired_count'] >=
                    (int) $posting['vacancies']
                ) {

                    $closeStmt = $this->db->prepare("
                        UPDATE job_postings
                        SET status = 'Closed'
                        WHERE posting_id = :posting_id
                    ");

                    $closeStmt->execute([
                        ':posting_id' => $postingId
                    ]);
                }
            }


            $this->db->commit();

            return true;

        } catch (\Exception $e) {

            $this->db->rollBack();

            throw $e;
        }
    }

    public function getApplicationForHiring(int $applicationId): ?array
    {
        $sql = "
            SELECT
                ap.application_id,
                ap.applicant_id,
                ap.posting_id,
                ap.application_status,

                a.first_name,
                a.middle_name,
                a.last_name,
                a.email,

                jp.title,
                jp.employment_type,
                jp.vacancies,

                p.position_id,
                p.position_name,
                p.role_id,

                d.department_id,
                d.department_name,

                r.role_code,
                r.role_name,

                (
                    SELECT COUNT(*)
                    FROM applications hired_app
                    WHERE hired_app.posting_id = ap.posting_id
                    AND hired_app.application_status = 'Hired'
                ) AS hired_count

            FROM applications ap

            INNER JOIN applicants a
                ON ap.applicant_id = a.applicant_id

            INNER JOIN job_postings jp
                ON ap.posting_id = jp.posting_id

            INNER JOIN positions p
                ON jp.position_id = p.position_id

            INNER JOIN departments d
                ON p.department_id = d.department_id

            WHERE ap.application_id = :application_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':application_id' => $applicationId
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    /**
     * Generate the next employee number.
     */
    private function generateEmployeeNumber(): string
    {
        $stmt = $this->db->query("
            SELECT employee_number
            FROM employees
            ORDER BY employee_id DESC
            LIMIT 1
        ");

        $lastNumber = $stmt->fetchColumn();

        if (!$lastNumber) {
            return 'EMP-000001';
        }

        if (preg_match('/(\d+)$/', $lastNumber, $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;

            return 'EMP-' . str_pad(
                $nextNumber,
                6,
                '0',
                STR_PAD_LEFT
            );
        }

        return 'EMP-000001';
    }


    /**
     * Determine initial employee employment status.
     */
    private function getEmploymentStatus(string $employmentType): string
    {
        return match ($employmentType) {

            'Contract' => 'Contract',

            'Full-Time',
            'Part-Time',
            'Internship' => 'Probationary',

            default => 'Probationary'
        };
    }

    public function hireApplication(int $applicationId): array
    {
        $this->db->beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | 1. Get application
            |--------------------------------------------------------------------------
            */

            $application = $this->getApplicationForHiring($applicationId);

            if (!$application) {
                throw new \Exception('Application not found.');
            }


            /*
            |--------------------------------------------------------------------------
            | 2. Make sure application is not already hired/rejected
            |--------------------------------------------------------------------------
            */

            if ($application['application_status'] === 'Hired') {
                throw new \Exception(
                    'This applicant has already been hired.'
                );
            }

            if ($application['application_status'] === 'Rejected') {
                throw new \Exception(
                    'A rejected applicant cannot be hired.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 3. Check vacancy
            |--------------------------------------------------------------------------
            */

            $vacancies = (int) $application['vacancies'];
            $hiredCount = (int) $application['hired_count'];

            if ($hiredCount >= $vacancies) {
                throw new \Exception(
                    'This job posting has already reached its vacancy limit.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 4. Make sure employee does not already exist
            |--------------------------------------------------------------------------
            */

            $employeeCheck = $this->db->prepare("
                SELECT employee_id
                FROM employees
                WHERE application_id = :application_id
                LIMIT 1
            ");

            $employeeCheck->execute([
                ':application_id' => $applicationId
            ]);

            if ($employeeCheck->fetch()) {
                throw new \Exception(
                    'An employee record already exists for this application.'
                );
            }


            /*
            |--------------------------------------------------------------------------
            | 5. Generate employee number
            |--------------------------------------------------------------------------
            */

            $employeeNumber = $this->generateEmployeeNumber();

            $employmentStatus = $this->getEmploymentStatus(
                $application['employment_type']
            );


            /*
            |--------------------------------------------------------------------------
            | 6. Create employee
            |--------------------------------------------------------------------------
            */

            $employeeStmt = $this->db->prepare("
                INSERT INTO employees
                (
                    application_id,
                    employee_number,
                    position_id,
                    department_id,
                    hire_date,
                    employment_status
                )
                VALUES
                (
                    :application_id,
                    :employee_number,
                    :position_id,
                    :department_id,
                    CURDATE(),
                    :employment_status
                )
            ");

            $employeeStmt->execute([
                ':application_id' => $applicationId,
                ':employee_number' => $employeeNumber,
                ':position_id' => $application['position_id'],
                ':department_id' => $application['department_id'],
                ':employment_status' => $employmentStatus
            ]);

            $employeeId = (int) $this->db->lastInsertId();

            /*
            |--------------------------------------------------------------------------
            | 7. Get role from position
            |--------------------------------------------------------------------------
            */

            $roleId = $application['role_id'] ?? null;

            if (!$roleId) {
                throw new \Exception(
                    'No system role is assigned to this position.'
                );
            }

            $roleId = (int) $roleId;

            /*
            |--------------------------------------------------------------------------
            | 8. Generate username
            |--------------------------------------------------------------------------
            */

            $baseUsername = strtolower(
                $application['first_name'] . '.' .
                $application['last_name']
            );

            $baseUsername = preg_replace(
                '/[^a-z0-9.]/',
                '',
                $baseUsername
            );

            $username = $baseUsername;

            $counter = 1;

            $usernameCheck = $this->db->prepare("
                SELECT user_id
                FROM users
                WHERE username = :username
                LIMIT 1
            ");

            while (true) {

                $usernameCheck->execute([
                    ':username' => $username
                ]);

                if (!$usernameCheck->fetch()) {
                    break;
                }

                $username = $baseUsername . $counter;

                $counter++;
            }


            /*
            |--------------------------------------------------------------------------
            | 9. Generate temporary password
            |--------------------------------------------------------------------------
            */

            $temporaryPassword =
                'HR1-' .
                strtoupper(
                    substr(
                        bin2hex(random_bytes(4)),
                        0,
                        8
                    )
                );


            /*
            |--------------------------------------------------------------------------
            | 10. Create user account
            |--------------------------------------------------------------------------
            */

            $passwordHash = password_hash(
                $temporaryPassword,
                PASSWORD_DEFAULT
            );

            $userStmt = $this->db->prepare("
                INSERT INTO users
                (
                    employee_id,
                    role_id,
                    username,
                    password,
                    status,
                    must_change_password
                )
                VALUES
                (
                    :employee_id,
                    :role_id,
                    :username,
                    :password,
                    'Active',
                    TRUE
                )
            ");

            $userStmt->execute([
                ':employee_id' => $employeeId,
                ':role_id' => $roleId,
                ':username' => $username,
                ':password' => $passwordHash
            ]);

            $userId = (int) $this->db->lastInsertId();


            /*
            |--------------------------------------------------------------------------
            | 11. Create onboarding record
            |--------------------------------------------------------------------------
            */

            $onboardingStmt = $this->db->prepare("
                INSERT INTO onboarding
                (
                    application_id,
                    onboarding_status
                )
                VALUES
                (
                    :application_id,
                    'Pending'
                )
            ");

            $onboardingStmt->execute([
                ':application_id' => $applicationId
            ]);


            /*
            |--------------------------------------------------------------------------
            | 12. Mark application as Hired
            |--------------------------------------------------------------------------
            */

            $statusStmt = $this->db->prepare("
                UPDATE applications
                SET application_status = 'Hired'
                WHERE application_id = :application_id
            ");

            $statusStmt->execute([
                ':application_id' => $applicationId
            ]);


            /*
            |--------------------------------------------------------------------------
            | 13. Close job posting if vacancy is now full
            |--------------------------------------------------------------------------
            */

            $newHiredCountStmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM applications
                WHERE posting_id = :posting_id
                AND application_status = 'Hired'
            ");

            $newHiredCountStmt->execute([
                ':posting_id' => $application['posting_id']
            ]);

            $newHiredCount = (int) $newHiredCountStmt->fetchColumn();

            if ($newHiredCount >= $vacancies) {

                $closeStmt = $this->db->prepare("
                    UPDATE job_postings
                    SET status = 'Closed'
                    WHERE posting_id = :posting_id
                ");

                $closeStmt->execute([
                    ':posting_id' => $application['posting_id']
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | 14. Commit everything
            |--------------------------------------------------------------------------
            */

            $this->db->commit();


            return [
                'employee_id' => $employeeId,
                'employee_number' => $employeeNumber,
                'user_id' => $userId,
                'username' => $username,
                'temporary_password' => $temporaryPassword
            ];


        } catch (\Exception $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }


    /**
     * Reject an application.
     */
    public function rejectApplication(int $applicationId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE applications

            SET application_status = 'Rejected'

            WHERE application_id = :application_id

            AND application_status <> 'Hired'
        ");

        $stmt->execute([
            ':application_id' => $applicationId
        ]);

        return $stmt->rowCount() > 0;
    }

    public function getJobByApplicationToken(string $token)
    {
        $sql = "
            SELECT
                jp.posting_id,
                jp.title,
                jp.description,
                jp.requirements,
                jp.employment_type,
                jp.vacancies,
                jp.status,
                jp.application_token,
                jp.application_deadline,

                p.position_name,
                d.department_name,

                COUNT(
                    CASE
                        WHEN ap.application_status = 'Hired'
                        THEN 1
                    END
                ) AS hired_count

            FROM job_postings jp

            INNER JOIN positions p
                ON p.position_id = jp.position_id

            INNER JOIN departments d
                ON d.department_id = p.department_id

            LEFT JOIN applications ap
                ON ap.posting_id = jp.posting_id

            WHERE jp.application_token = :token

            GROUP BY
                jp.posting_id,
                jp.title,
                jp.description,
                jp.requirements,
                jp.employment_type,
                jp.vacancies,
                jp.status,
                jp.application_token,
                jp.application_deadline,
                p.position_name,
                d.department_name

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':token' => $token
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getApplicantByEmail(string $email)
    {
        $stmt = $this->db->prepare("
            SELECT
                applicant_id,
                first_name,
                middle_name,
                last_name,
                email,
                phone,
                address
            FROM applicants
            WHERE email = ?
            LIMIT 1
        ");

        $stmt->execute([$email]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function hasApplied(
        int $applicantId,
        int $postingId
    ): bool
    {
        $stmt = $this->db->prepare("
            SELECT application_id
            FROM applications
            WHERE applicant_id = ?
            AND posting_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $applicantId,
            $postingId
        ]);

        return (bool) $stmt->fetch();
    }

    public function createApplicant(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO applicants
            (
                first_name,
                middle_name,
                last_name,
                email,
                phone,
                address
            )
            VALUES
            (
                :first_name,
                :middle_name,
                :last_name,
                :email,
                :phone,
                :address
            )
        ");

        $stmt->execute([
            ':first_name' => $data['first_name'],
            ':middle_name' => $data['middle_name'],
            ':last_name' => $data['last_name'],
            ':email' => $data['email'],
            ':phone' => $data['phone'],
            ':address' => $data['address']
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function createApplication(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO applications
            (
                applicant_id,
                posting_id,
                resume_file,
                cover_letter_file,
                application_status
            )
            VALUES
            (
                :applicant_id,
                :posting_id,
                :resume_file,
                :cover_letter_file,
                'Submitted'
            )
        ");

        $stmt->execute([
            ':applicant_id' => $data['applicant_id'],
            ':posting_id' => $data['posting_id'],
            ':resume_file' => $data['resume_file'],
            ':cover_letter_file' => $data['cover_letter_file']
        ]);

        return (int) $this->db->lastInsertId();
    }
}