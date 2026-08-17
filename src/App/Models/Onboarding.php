<?php

namespace App\Models;

use Core\Database;
use PDO;

class Onboarding
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }


    /*
    |--------------------------------------------------------------------------
    | GET ALL ONBOARDING
    |--------------------------------------------------------------------------
    */

    public function getAllOnboarding(): array
    {
        $sql = "
            SELECT

                /* Onboarding */
                o.onboarding_id,
                o.application_id,
                o.orientation_date,
                o.onboarding_status,
                o.remarks,

                /* Employee */
                e.employee_id,
                e.employee_number,
                e.hire_date,
                e.employment_status,
                e.position_id,

                /* Applicant */
                a.applicant_id,
                a.first_name,
                a.middle_name,
                a.last_name,
                a.email,

                CONCAT(
                    COALESCE(a.first_name, ''),
                    ' ',
                    IF(
                        a.middle_name IS NULL
                        OR a.middle_name = '',
                        '',
                        CONCAT(a.middle_name, ' ')
                    ),
                    COALESCE(a.last_name, '')
                ) AS fullname,

                /* Organization */
                d.department_name,
                p.position_name AS job_title

            FROM onboarding o

            LEFT JOIN applications app
                ON o.application_id = app.application_id

            LEFT JOIN applicants a
                ON app.applicant_id = a.applicant_id

            LEFT JOIN employees e
                ON o.application_id = e.application_id

            LEFT JOIN positions p
                ON e.position_id = p.position_id

            LEFT JOIN departments d
                ON p.department_id = d.department_id

            ORDER BY
                o.onboarding_id DESC
        ";

        $employees = $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);


        /*
         * Calculate onboarding progress.
         */
        foreach ($employees as &$employee) {

            switch ($employee['onboarding_status']) {

                case 'Completed':
                    $employee['progress'] = 100;
                    break;

                case 'Ongoing':
                    $employee['progress'] = 50;
                    break;

                case 'Pending':
                default:
                    $employee['progress'] = 0;
                    break;
            }


            /*
             * Start date is the employee's hire date.
             */
            $employee['start_date'] =
                $employee['hire_date'] ?? null;
        }

        unset($employee);

        return $employees;
    }


    /*
    |--------------------------------------------------------------------------
    | GET ONBOARDING DETAILS
    |--------------------------------------------------------------------------
    */

    public function getOnboardingDetails(int $employeeId): ?array
    {
        $sql = "
            SELECT

                /* Onboarding */
                o.onboarding_id,
                o.application_id,
                o.orientation_date,
                o.onboarding_status,
                o.remarks,

                /* Employee */
                e.employee_id,
                e.employee_number,
                e.hire_date,
                e.employment_status,
                e.position_id,

                /* Applicant */
                a.applicant_id,
                a.first_name,
                a.middle_name,
                a.last_name,
                a.email,
                a.phone,

                CONCAT(
                    COALESCE(a.first_name, ''),
                    ' ',
                    IF(
                        a.middle_name IS NULL
                        OR a.middle_name = '',
                        '',
                        CONCAT(a.middle_name, ' ')
                    ),
                    COALESCE(a.last_name, '')
                ) AS fullname,

                /* Organization */
                d.department_name,
                p.position_name AS job_title,

                /* Application */
                app.application_status,
                app.applied_at

            FROM onboarding o

            LEFT JOIN applications app
                ON o.application_id = app.application_id

            LEFT JOIN applicants a
                ON app.applicant_id = a.applicant_id

            LEFT JOIN employees e
                ON o.application_id = e.application_id

            LEFT JOIN positions p
                ON e.position_id = p.position_id

            LEFT JOIN departments d
                ON p.department_id = d.department_id

            WHERE e.employee_id = ?

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            $employeeId
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            return null;
        }


        /*
         * Start date is the employee's hire date.
         */
        $result['start_date'] =
            $result['hire_date'] ?? null;


        return $result;
    }


    /*
    |--------------------------------------------------------------------------
    | GET DOCUMENTS
    |--------------------------------------------------------------------------
    */

    public function getDocuments(int $onboardingId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                document_id,
                onboarding_id,
                document_name,
                file_path,
                status
            FROM onboarding_documents
            WHERE onboarding_id = ?
            ORDER BY document_id ASC
        ");

        $stmt->execute([
            $onboardingId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | GET DOCUMENT PROGRESS
    |--------------------------------------------------------------------------
    */

    public function getDocumentProgress(int $onboardingId): array
    {
        $stmt = $this->db->prepare("
            SELECT

                COUNT(*) AS total,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'Verified'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS verified,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'Submitted'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS submitted,

                COALESCE(
                    SUM(
                        CASE
                            WHEN status = 'Pending'
                            THEN 1
                            ELSE 0
                        END
                    ),
                    0
                ) AS pending

            FROM onboarding_documents

            WHERE onboarding_id = ?
        ");

        $stmt->execute([
            $onboardingId
        ]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return [
            'total' => (int) ($result['total'] ?? 0),
            'verified' => (int) ($result['verified'] ?? 0),
            'submitted' => (int) ($result['submitted'] ?? 0),
            'pending' => (int) ($result['pending'] ?? 0)
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | REQUEST DOCUMENT
    |--------------------------------------------------------------------------
    */

    public function requestDocument(
        int $onboardingId,
        string $documentName
    ): array {

        $documentName = trim($documentName);

        if ($onboardingId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid onboarding record.'
            ];
        }

        if ($documentName === '') {
            return [
                'success' => false,
                'message' => 'Document name cannot be empty.'
            ];
        }

        /*
        * Check if the onboarding record exists.
        */
        $stmt = $this->db->prepare("
            SELECT onboarding_id
            FROM onboarding
            WHERE onboarding_id = ?
            LIMIT 1
        ");

        $stmt->execute([
            $onboardingId
        ]);

        if (!$stmt->fetchColumn()) {
            return [
                'success' => false,
                'message' => 'Onboarding record not found.'
            ];
        }


        /*
        * Prevent duplicate pending/submitted requests.
        */
        $stmt = $this->db->prepare("
            SELECT document_id
            FROM onboarding_documents
            WHERE onboarding_id = ?
            AND document_name = ?
            AND status IN ('Pending', 'Submitted')
            LIMIT 1
        ");

        $stmt->execute([
            $onboardingId,
            $documentName
        ]);

        if ($stmt->fetch()) {
            return [
                'success' => false,
                'message' => 'This document has already been requested.'
            ];
        }


        /*
        * Create document request.
        */
        try {

            $stmt = $this->db->prepare("
                INSERT INTO onboarding_documents (
                    onboarding_id,
                    document_name,
                    file_path,
                    status
                )
                VALUES (
                    ?,
                    ?,
                    NULL,
                    'Pending'
                )
            ");

            $stmt->execute([
                $onboardingId,
                $documentName
            ]);

        } catch (\PDOException $e) {

            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }


        /*
        * Update onboarding status.
        */
        $this->updateOnboardingStatus(
            $onboardingId
        );


        return [
            'success' => true,
            'message' => 'Document request sent successfully.'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY DOCUMENT
    |--------------------------------------------------------------------------
    */

    public function verifyDocument(int $documentId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                document_id,
                onboarding_id,
                status

            FROM onboarding_documents

            WHERE document_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            $documentId
        ]);

        $document = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$document) {

            return [
                'success' => false,
                'message' => 'Document not found.'
            ];
        }


        if ($document['status'] !== 'Submitted') {

            return [
                'success' => false,
                'message' =>
                    'Only submitted documents can be verified.'
            ];
        }


        $stmt = $this->db->prepare("
            UPDATE onboarding_documents

            SET status = 'Verified'

            WHERE document_id = ?
        ");

        $stmt->execute([
            $documentId
        ]);


        /*
         * Recalculate onboarding status.
         */
        $this->updateOnboardingStatus(
            (int) $document['onboarding_id']
        );


        return [
            'success' => true,
            'message' =>
                'Document verified successfully.'
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE ONBOARDING STATUS
    |--------------------------------------------------------------------------
    */

    public function updateOnboardingStatus(
        int $onboardingId
    ): void {

        /*
         * Get current onboarding information.
         */
        $stmt = $this->db->prepare("
            SELECT
                orientation_date

            FROM onboarding

            WHERE onboarding_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            $onboardingId
        ]);

        $onboarding = $stmt->fetch(PDO::FETCH_ASSOC);


        if (!$onboarding) {
            return;
        }


        /*
         * Get document statistics.
         */
        $progress =
            $this->getDocumentProgress(
                $onboardingId
            );


        /*
         * Completed:
         *
         * - Orientation recorded
         * - At least one document exists
         * - Every document is verified
         */
        if (
            !empty($onboarding['orientation_date']) &&
            $progress['total'] > 0 &&
            $progress['verified'] === $progress['total']
        ) {

            $status = 'Completed';

        }

        /*
         * Ongoing:
         *
         * - Documents exist
         * OR
         * - Orientation has been scheduled/recorded
         */
        elseif (
            $progress['total'] > 0 ||
            !empty($onboarding['orientation_date'])
        ) {

            $status = 'Ongoing';

        }

        /*
         * Nothing has started yet.
         */
        else {

            $status = 'Pending';
        }


        $stmt = $this->db->prepare("
            UPDATE onboarding

            SET onboarding_status = ?

            WHERE onboarding_id = ?
        ");

        $stmt->execute([
            $status,
            $onboardingId
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | ENSURE ONBOARDING EXISTS
    |--------------------------------------------------------------------------
    */

    public function ensureOnboarding(
        int $applicationId
    ): int {

        $stmt = $this->db->prepare("
            SELECT onboarding_id

            FROM onboarding

            WHERE application_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            $applicationId
        ]);

        $onboardingId =
            $stmt->fetchColumn();


        if ($onboardingId) {
            return (int) $onboardingId;
        }


        $stmt = $this->db->prepare("
            INSERT INTO onboarding (
                application_id,
                onboarding_status
            )

            VALUES (
                ?,
                'Pending'
            )
        ");

        $stmt->execute([
            $applicationId
        ]);


        return (int) $this->db->lastInsertId();
    }


    /*
    |--------------------------------------------------------------------------
    | COUNT ALL ONBOARDING
    |--------------------------------------------------------------------------
    */

    public function countAll(): int
    {
        return (int) $this->db
            ->query("
                SELECT COUNT(*)
                FROM onboarding
            ")
            ->fetchColumn();
    }


    /*
    |--------------------------------------------------------------------------
    | COUNT ONBOARDING BY STATUS
    |--------------------------------------------------------------------------
    */

    public function countStatus(string $status): int
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)

            FROM onboarding

            WHERE onboarding_status = ?
        ");

        $stmt->execute([
            $status
        ]);

        return (int) $stmt->fetchColumn();
    }
}