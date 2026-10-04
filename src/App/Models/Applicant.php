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


    /*
    |--------------------------------------------------------------------------
    | Applicant Listing
    |--------------------------------------------------------------------------
    */

    public function getAllApplicants()
    {
        $retentionDays = max(
            1,
            (int) config('app.rejected_applicant_retention_days', 30)
        );

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

                /* Primary academic document */
                (
                    SELECT ade.file_name
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_document_file,

                (
                    SELECT COUNT(*)
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                ) AS academic_document_count,

                (
                    SELECT ade.evaluation_status
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_evaluation_status,

                ai.overall_score AS ai_score,
                ai.ai_summary AS screening_summary,
                av.validation_status,

                i.interview_date,

                ap.application_status AS hiring_decision

            FROM applicants a

            INNER JOIN applications ap
                ON a.applicant_id = ap.applicant_id

            INNER JOIN job_postings jp
                ON ap.posting_id = jp.posting_id

            LEFT JOIN ai_screening ai
                ON ap.application_id = ai.application_id

            LEFT JOIN application_validations av
                ON av.application_id = ap.application_id

            LEFT JOIN interviews i
                ON ap.application_id = i.application_id

            WHERE ap.application_status IS NULL
                OR ap.application_status <> 'Rejected'
                OR ap.rejected_at >= DATE_SUB(NOW(), INTERVAL {$retentionDays} DAY)

            ORDER BY
                ap.applied_at DESC
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | Single Applicant
    |--------------------------------------------------------------------------
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

                jp.posting_id,
                jp.title AS position,

                ap.application_id,
                ap.resume_file,
                ap.cover_letter_file,
                ap.application_status,
                ap.applied_at,

                /*
                 * Primary academic document
                 */
                (
                    SELECT ade.evaluation_id
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_evaluation_id,

                (
                    SELECT ade.file_name
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_document_file,

                (
                    SELECT ade.original_file_name
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_document_original_file_name,

                (
                    SELECT ade.is_primary
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_document_is_primary,

                (
                    SELECT ade.evaluation_status
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_evaluation_status,

                (
                    SELECT ade.document_valid
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_document_valid,

                (
                    SELECT ade.confidence_score
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_confidence_score,

                (
                    SELECT ade.document_type
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_document_type,

                (
                    SELECT ade.institution
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_institution,

                (
                    SELECT ade.degree
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_degree,

                (
                    SELECT ade.field_of_study
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_field_of_study,

                (
                    SELECT ade.graduation_year
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_graduation_year,

                (
                    SELECT ade.extracted_details
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_extracted_details,

                (
                    SELECT ade.concerns
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_concerns,

                (
                    SELECT ade.ai_summary
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_ai_summary,

                (
                    SELECT ade.processed_at
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                    ORDER BY
                        ade.is_primary DESC,
                        ade.evaluation_id ASC
                    LIMIT 1
                ) AS academic_processed_at,

                (
                    SELECT COUNT(*)
                    FROM academic_document_evaluations ade
                    WHERE ade.application_id = ap.application_id
                ) AS academic_document_count,

                /*
                 * Resume AI screening
                 */
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

                                /*
                 * AI validation
                 */
                av.validation_status,
                av.submission_reference AS validation_reference,
                av.identity_status,
                av.education_status,
                av.experience_status,
                av.credential_status,
                av.consistency_score AS validation_score,
                av.findings AS validation_findings,
                av.validation_summary,
                av.processed_at AS validation_processed_at,

                /*
                 * Interview
                 */
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
                
            LEFT JOIN application_validations av
                ON av.application_id = ap.application_id

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

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':id' => $id
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | Application Status
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | Job Postings
    |--------------------------------------------------------------------------
    */

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


    /*
    |--------------------------------------------------------------------------
    | Managers / Interviewers
    |--------------------------------------------------------------------------
    */

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

            LEFT JOIN applicants a
                ON e.applicant_id = a.applicant_id

            WHERE u.status = 'Active'

            AND r.role_code IN (
                'ADMIN',
                'HR',
                'MGR'
            )

            ORDER BY
                r.role_name,
                fullname
        ";

        return $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | Schedule Interview
    |--------------------------------------------------------------------------
    */

    public function scheduleInterview(array $data)
    {
        $this->db->beginTransaction();

        try {

            $interviewDateTime =
                $data['interview_date'] .
                ' ' .
                $data['interview_time'] .
                ':00';


            /*
             * Check if interview already exists
             */
            $check = $this->db->prepare("
                SELECT interview_id
                FROM interviews
                WHERE application_id = :application_id
            ");

            $check->execute([
                ':application_id' => $data['application_id']
            ]);


            if ($check->fetch()) {

                /*
                 * Update existing interview
                 */
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

                /*
                 * Create new interview
                 */
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
                ':remarks' => $data['notes'] ?? null
            ]);


            /*
             * Change application status
             */
            $status = $this->db->prepare("
                UPDATE applications
                SET application_status = 'Interview',
                    rejected_at = NULL
                WHERE application_id = :application_id
            ");

            $status->execute([
                ':application_id' => $data['application_id']
            ]);


            $this->db->commit();

        } catch (\Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Hiring
    |--------------------------------------------------------------------------
    */

    public function getApplicationForHiring(
        int $applicationId
    ): ?array {

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

            INNER JOIN roles r
                ON p.role_id = r.role_id

            WHERE ap.application_id = :application_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':application_id' => $applicationId
        ]);

        $result =
            $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | Employee Number
    |--------------------------------------------------------------------------
    */

    private function generateEmployeeNumber(): string
    {
        $sequenceStmt = $this->db->query("
            SELECT next_number
            FROM employee_number_sequence
            WHERE sequence_id = 1
            FOR UPDATE
        ");

        $nextNumber =
            $sequenceStmt->fetchColumn();


        if ($nextNumber === false) {
            throw new \Exception(
                'Employee number sequence is not initialized.'
            );
        }


        $updateStmt = $this->db->prepare("
            UPDATE employee_number_sequence
            SET next_number = next_number + 1
            WHERE sequence_id = 1
        ");

        $updateStmt->execute();


        return 'EMP-' . str_pad(
            (int) $nextNumber,
            6,
            '0',
            STR_PAD_LEFT
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Employment Status
    |--------------------------------------------------------------------------
    */

    private function getEmploymentStatus(
        string $employmentType
    ): string {

        return match ($employmentType) {

            'Contract' => 'Contract',

            'Full-Time',
            'Part-Time',
            'Internship' => 'Probationary',

            default => 'Probationary'
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Hire Application
    |--------------------------------------------------------------------------
    */

    public function hireApplication(
        int $applicationId
    ): array {

        $this->db->beginTransaction();

        try {

            /*
             * 1. Get application
             */
            $application =
                $this->getApplicationForHiring(
                    $applicationId
                );


            if (!$application) {
                throw new \Exception(
                    'Application not found.'
                );
            }


            /*
             * Lock posting
             */
            $postingLockStmt = $this->db->prepare("
                SELECT posting_id
                FROM job_postings
                WHERE posting_id = :posting_id
                FOR UPDATE
            ");

            $postingLockStmt->execute([
                ':posting_id' =>
                    $application['posting_id']
            ]);


            /*
             * Lock application
             */
            $applicationLockStmt = $this->db->prepare("
                SELECT application_id
                FROM applications
                WHERE application_id = :application_id
                FOR UPDATE
            ");

            $applicationLockStmt->execute([
                ':application_id' => $applicationId
            ]);


            /*
             * Refresh application data
             */
            $application =
                $this->getApplicationForHiring(
                    $applicationId
                );


            /*
             * 2. Validate current status
             */
            if (
                $application['application_status'] === 'Hired'
            ) {
                throw new \Exception(
                    'This applicant has already been hired.'
                );
            }


            if (
                $application['application_status'] === 'Rejected'
            ) {
                throw new \Exception(
                    'A rejected applicant cannot be hired.'
                );
            }


            /*
             * 3. Check vacancy
             */
            $vacancies =
                (int) $application['vacancies'];

            $hiredCount =
                (int) $application['hired_count'];


            if ($hiredCount >= $vacancies) {
                throw new \Exception(
                    'This job posting has already reached its vacancy limit.'
                );
            }


            /*
             * 4. Check existing employee
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
             * 5. Employee number
             */
            $employeeNumber =
                $this->generateEmployeeNumber();


            $employmentStatus =
                $this->getEmploymentStatus(
                    $application['employment_type']
                );


            /*
             * 6. Create employee
             */
            $employeeStmt = $this->db->prepare("
                INSERT INTO employees
                (
                    application_id,
                    applicant_id,
                    employee_number,
                    position_id,
                    hire_date,
                    employment_status
                )
                VALUES
                (
                    :application_id,
                    :applicant_id,
                    :employee_number,
                    :position_id,
                    CURDATE(),
                    :employment_status
                )
            ");

            $employeeStmt->execute([
                ':application_id' =>
                    $applicationId,

                ':applicant_id' =>
                    $application['applicant_id'],

                ':employee_number' =>
                    $employeeNumber,

                ':position_id' =>
                    $application['position_id'],

                ':employment_status' =>
                    $employmentStatus
            ]);


            $employeeId =
                (int) $this->db->lastInsertId();


            /*
             * 7. Get system role
             */
            $roleId =
                $application['role_id'] ?? null;


            if (!$roleId) {
                throw new \Exception(
                    'No system role is assigned to this position.'
                );
            }


            $roleId =
                (int) $roleId;


            /*
             * 8. Generate username
             */
            $baseUsername = strtolower(
                $application['first_name'] .
                '.' .
                $application['last_name']
            );


            $baseUsername = preg_replace(
                '/[^a-z0-9.]/',
                '',
                $baseUsername
            );


            $username =
                $baseUsername;

            $counter = 1;


            $usernameCheck = $this->db->prepare("
                SELECT user_id
                FROM users
                WHERE username = :username
                LIMIT 1
            ");


            while (true) {

                $usernameCheck->execute([
                    ':username' =>
                        $username
                ]);


                if (!$usernameCheck->fetch()) {
                    break;
                }


                $username =
                    $baseUsername .
                    $counter;

                $counter++;
            }


            /*
             * 9. Temporary password
             */
            $temporaryPassword =
                'HR1-' .
                strtoupper(
                    substr(
                        bin2hex(
                            random_bytes(4)
                        ),
                        0,
                        8
                    )
                );


            /*
             * 10. Create user
             */
            $passwordHash =
                password_hash(
                    $temporaryPassword,
                    PASSWORD_DEFAULT
                );


            $userStmt = $this->db->prepare("
                INSERT INTO users
                (
                    employee_id,
                    role_id,
                    username,
                    email,
                    password,
                    status,
                    must_change_password
                )
                VALUES
                (
                    :employee_id,
                    :role_id,
                    :username,
                    :email,
                    :password,
                    'Active',
                    TRUE
                )
            ");


            $userStmt->execute([
                ':employee_id' =>
                    $employeeId,

                ':role_id' =>
                    $roleId,

                ':username' =>
                    $username,

                ':email' =>
                    $application['email'],

                ':password' =>
                    $passwordHash
            ]);


            $userId =
                (int) $this->db->lastInsertId();


            /*
             * 11. Create onboarding
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
                ':application_id' =>
                    $applicationId
            ]);


            /*
             * 12. Mark hired
             */
            $statusStmt = $this->db->prepare("
                UPDATE applications
                SET application_status = 'Hired',
                    rejected_at = NULL
                WHERE application_id = :application_id
            ");


            $statusStmt->execute([
                ':application_id' =>
                    $applicationId
            ]);


            /*
             * 13. Close posting when full
             */
            $newHiredCountStmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM applications
                WHERE posting_id = :posting_id
                AND application_status = 'Hired'
            ");


            $newHiredCountStmt->execute([
                ':posting_id' =>
                    $application['posting_id']
            ]);


            $newHiredCount =
                (int) $newHiredCountStmt->fetchColumn();


            if ($newHiredCount >= $vacancies) {

                $closeStmt = $this->db->prepare("
                    UPDATE job_postings
                    SET status = 'Closed',
                        closed_at = CURRENT_TIMESTAMP
                    WHERE posting_id = :posting_id
                ");

                $closeStmt->execute([
                    ':posting_id' =>
                        $application['posting_id']
                ]);
            }


            /*
             * 14. Commit
             */
            $this->db->commit();


            return [

                'email' =>
                    $application['email'],

                'fullname' =>
                    trim(
                        $application['first_name'] .
                        ' ' .
                        ($application['middle_name'] ?? '') .
                        ' ' .
                        $application['last_name']
                    ),

                'position' =>
                    $application['title'],

                'employee_id' =>
                    $employeeId,

                'employee_number' =>
                    $employeeNumber,

                'user_id' =>
                    $userId,

                'username' =>
                    $username,

                'temporary_password' =>
                    $temporaryPassword
            ];

        } catch (\Throwable $e) {

            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Reject Application
    |--------------------------------------------------------------------------
    */

    public function rejectApplication(
        int $applicationId
    ): bool {

        $stmt = $this->db->prepare("
            UPDATE applications
            SET application_status = 'Rejected',
                rejected_at = CASE
                    WHEN application_status <> 'Rejected' THEN CURRENT_TIMESTAMP
                    ELSE rejected_at
                END
            WHERE application_id = :application_id
            AND application_status <> 'Hired'
        ");


        $stmt->execute([
            ':application_id' =>
                $applicationId
        ]);


        return $stmt->rowCount() > 0;
    }


    /*
    |--------------------------------------------------------------------------
    | Job By Application Token
    |--------------------------------------------------------------------------
    */

    public function getJobByApplicationToken(
        string $token
    ) {

        $sql = "
            SELECT
                jp.posting_id,
                jp.title,
                jp.description,
                jp.requirements,
                jp.academic_document_required,
                jp.employment_type,
                jp.vacancies,
                jp.max_applicants,
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
                ) AS hired_count,

                COUNT(
                    CASE
                        WHEN ap.application_id IS NOT NULL
                            AND (
                                ap.application_status IS NULL
                                OR ap.application_status <> 'Rejected'
                            )
                        THEN 1
                    END
                ) AS applicant_count

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
                jp.academic_document_required,
                jp.employment_type,
                jp.vacancies,
                jp.max_applicants,
                jp.status,
                jp.application_token,
                jp.application_deadline,
                p.position_name,
                d.department_name

            LIMIT 1
        ";


        $stmt = $this->db->prepare($sql);


        $stmt->execute([
            ':token' =>
                $token
        ]);


        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Find Applicant By Email
    |--------------------------------------------------------------------------
    */

    public function getApplicantByEmail(
        string $email
    ) {

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


        $stmt->execute([
            $email
        ]);


        return $stmt->fetch(
            PDO::FETCH_ASSOC
        );
    }

        /*
    |--------------------------------------------------------------------------
    | Find Applicant By Phone
    |--------------------------------------------------------------------------
    */

    public function getApplicantByPhone(string $phone)
    {
        $stmt = $this->db->prepare("
            SELECT applicant_id
            FROM applicants
            WHERE phone = ?
            LIMIT 1
        ");

        $stmt->execute([$phone]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /*
    |--------------------------------------------------------------------------
    | Check Existing Application
    |--------------------------------------------------------------------------
    */

    public function hasApplied(
        int $applicantId,
        int $postingId
    ): bool {

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


    /*
    |--------------------------------------------------------------------------
    | Create Applicant
    |--------------------------------------------------------------------------
    */

    public function createApplicant(
        array $data
    ): int {

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
            ':first_name' =>
                $data['first_name'],

            ':middle_name' =>
                $data['middle_name'] ?? null,

            ':last_name' =>
                $data['last_name'],

            ':email' =>
                $data['email'],

            ':phone' =>
                $data['phone'],

            ':address' =>
                $data['address'] ?? null
        ]);


        return (int) $this->db->lastInsertId();
    }


    /*
    |--------------------------------------------------------------------------
    | Create Application
    |--------------------------------------------------------------------------
    |
    | Creates the application and all uploaded academic documents.
    |
    | Supported input:
    |
    | 'academic_document_files' => [
    |     'academic_generated_1.pdf',
    |     'academic_generated_2.jpg'
    | ]
    |
    | Or:
    |
    | 'academic_documents' => [
    |     [
    |         'file_name' => 'academic_generated_1.pdf',
    |         'original_file_name' => 'Diploma.pdf'
    |     ],
    |     [
    |         'file_name' => 'academic_generated_2.jpg',
    |         'original_file_name' => 'Certificate.jpg'
    |     ]
    | ]
    |
    */

    public function createApplication(
        array $data
    ): int {

        $ownsTransaction = !$this->db->inTransaction();

        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {

            $postingStmt = $this->db->prepare("
                SELECT max_applicants
                FROM job_postings
                WHERE posting_id = :posting_id
                FOR UPDATE
            ");

            $postingStmt->execute([
                ':posting_id' => $data['posting_id']
            ]);

            $posting = $postingStmt->fetch(PDO::FETCH_ASSOC);

            if (!$posting) {
                throw new \Exception(
                    'This position is no longer accepting applications.'
                );
            }

            $countStmt = $this->db->prepare("
                SELECT COUNT(*)
                FROM applications
                WHERE posting_id = :posting_id
                AND (
                    application_status IS NULL
                    OR application_status <> 'Rejected'
                )
            ");

            $countStmt->execute([
                ':posting_id' => $data['posting_id']
            ]);

            $applicantCount = (int) $countStmt->fetchColumn();

            if ($applicantCount >= (int) $posting['max_applicants']) {
                throw new \Exception(
                    'This position has reached its maximum number of applicants.'
                );
            }

            /*
             * 1. Create application
             */
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
                ':applicant_id' =>
                    $data['applicant_id'],

                ':posting_id' =>
                    $data['posting_id'],

                ':resume_file' =>
                    $data['resume_file'],

                ':cover_letter_file' =>
                    $data['cover_letter_file'] ?? null
            ]);


            $applicationId =
                (int) $this->db->lastInsertId();

            /*
             * 2. Academic documents
             */
            $academicDocuments =
                $data['academic_documents']
                ?? $data['academic_document_files']
                ?? [];


            if (!is_array($academicDocuments)) {
                $academicDocuments = [];
            }


            if (!empty($academicDocuments)) {

                $documentStmt = $this->db->prepare("
                    INSERT INTO academic_document_evaluations
                    (
                        application_id,
                        file_name,
                        original_file_name,
                        is_primary,
                        evaluation_status
                    )
                    VALUES
                    (
                        :application_id,
                        :file_name,
                        :original_file_name,
                        :is_primary,
                        'Pending'
                    )
                ");


                $primaryAssigned = false;


                foreach (
                    $academicDocuments
                    as $document
                ) {

                    /*
                     * Allow simple filename strings
                     */
                    if (is_string($document)) {

                        $fileName =
                            trim($document);

                        $originalFileName =
                            $fileName;

                    /*
                     * Or structured document arrays
                     */
                    } elseif (is_array($document)) {

                        $fileName =
                            trim(
                                (string) (
                                    $document['file_name']
                                    ?? ''
                                )
                            );

                        $originalFileName =
                            trim(
                                (string) (
                                    $document['original_file_name']
                                    ?? $fileName
                                )
                            );

                    } else {

                        continue;
                    }


                    if ($fileName === '') {
                        continue;
                    }


                    /*
                     * The first valid academic document
                     * becomes the primary document.
                     */
                    $isPrimary =
                        !$primaryAssigned;


                    $documentStmt->execute([
                        ':application_id' =>
                            $applicationId,

                        ':file_name' =>
                            $fileName,

                        ':original_file_name' =>
                            $originalFileName,

                        ':is_primary' =>
                            $isPrimary ? 1 : 0
                    ]);


                    if ($isPrimary) {
                        $primaryAssigned = true;
                    }
                }
            }


            if ($ownsTransaction) {
                $this->db->commit();
            }


            return $applicationId;

        } catch (\Throwable $e) {

            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Application File
    |--------------------------------------------------------------------------
    */

    public function getApplicationFile(
        int $applicationId
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                application_id,
                resume_file
            FROM applications
            WHERE application_id = :application_id
            LIMIT 1
        ");


        $stmt->execute([
            ':application_id' =>
                $applicationId
        ]);


        $result =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $result ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | All Academic Documents For Application
    |--------------------------------------------------------------------------
    */

    public function getAcademicDocumentsForApplication(
        int $applicationId
    ): array {

        $stmt = $this->db->prepare("
            SELECT
                evaluation_id,
                application_id,
                file_name,
                original_file_name,
                is_primary,
                evaluation_status,
                document_valid,
                confidence_score,
                document_type,
                institution,
                degree,
                field_of_study,
                graduation_year,
                extracted_details,
                concerns,
                ai_summary,
                processed_at,
                created_at,
                updated_at

            FROM academic_document_evaluations

            WHERE application_id = :application_id

            ORDER BY
                is_primary DESC,
                evaluation_id ASC
        ");


        $stmt->execute([
            ':application_id' =>
                $applicationId
        ]);


        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Primary Academic Document
    |--------------------------------------------------------------------------
    */

    public function getAcademicDocumentFile(
        int $applicationId
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                evaluation_id,
                application_id,
                file_name AS academic_document_file,
                original_file_name,
                is_primary,
                evaluation_status

            FROM academic_document_evaluations

            WHERE application_id = :application_id

            ORDER BY
                is_primary DESC,
                evaluation_id ASC

            LIMIT 1
        ");


        $stmt->execute([
            ':application_id' =>
                $applicationId
        ]);


        $result =
            $stmt->fetch(
                PDO::FETCH_ASSOC
            );


        return $result ?: null;
    }
}