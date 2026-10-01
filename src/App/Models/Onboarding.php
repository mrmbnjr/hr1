<?php

namespace App\Models;

use Core\Database;
use PDO;

class Onboarding
{
    /*
    |--------------------------------------------------------------------------
    | REQUEST CATALOGUE
    |--------------------------------------------------------------------------
    |
    | Canonical list for the "Request Document" form. Shared by three
    | consumers so they can never drift apart:
    |
    |   * the HR request form      -> the <option> list
    |   * requestDocument()        -> validation of what HR submitted
    |   * the Assigned Requests    -> the icon shown per request
    |     employee page
    |
    */

    public const REQUEST_TYPES = [
        'Submit Required Document' => 'fa-file-arrow-up',
        'Update Employee Information' => 'fa-user-pen',
        'Submit Additional Employment Document' => 'fa-folder-plus',
        'Complete Employee Form' => 'fa-file-signature',
        'Review and Confirm Information' => 'fa-circle-check',
        'Submit Missing Requirement' => 'fa-triangle-exclamation',
        'HR Compliance Requirement' => 'fa-scale-balanced',
        'Other' => 'fa-comment-dots',
    ];

    public const ACTION_TYPES = [
        'document',
        'response',
        'confirmation',
    ];

    public const PRIORITIES = [
        'Normal',
        'High',
        'Urgent',
    ];

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

        foreach ($employees as &$employee) {
            $onboardingId = (int) ($employee['onboarding_id'] ?? 0);

            $phases = $onboardingId > 0
                ? $this->getOnboardingPhases($onboardingId)
                : [];

            $documentProgress = $onboardingId > 0
                ? $this->getDocumentProgress($onboardingId)
                : [
                    'total' => 0,
                    'submitted' => 0,
                    'verified' => 0,
                    'pending' => 0,
                ];

            $employee['current_phase'] = $this->resolveCurrentPhase($phases);
            $employee['documents_total'] = (int) ($documentProgress['total'] ?? 0);
            $employee['documents_submitted'] = (int) ($documentProgress['submitted'] ?? 0);
            $employee['progress'] = $onboardingId > 0
                ? $this->calculateProgressFromPhases($onboardingId)
                : 0;

            if ($employee['progress'] === 0 && ($employee['onboarding_status'] ?? '') === 'Completed') {
                $employee['progress'] = 100;
            }

            $employee['start_date'] = $employee['hire_date'] ?? null;
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
                instruction,
                request_type,
                action_type,
                priority,
                due_date,
                remarks,
                status,
                reviewed_at,
                completed_at,
                response_text
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
                            WHEN status IN ('Submitted', 'Verified')
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
        string $documentName,
        string $instruction = '',
        ?string $requestType = null,
        string $actionType = 'document',
        string $priority = 'Normal',
        ?string $dueDate = null,
        ?int $assignedBy = null
    ): array {

        $documentName = trim($documentName);
        $instruction = trim($instruction);

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
        * Optional categorisation. Falls back to the generic type so the
        * Assigned Requests page always has something to show.
        */
        if (
            $requestType === null
            || !array_key_exists($requestType, self::REQUEST_TYPES)
        ) {
            $requestType = 'Submit Required Document';
        }

        if (!in_array($actionType, self::ACTION_TYPES, true)) {
            $actionType = 'document';
        }

        if (!in_array($priority, self::PRIORITIES, true)) {
            $priority = 'Normal';
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
        *
        * employee_id is stored directly so the Assigned Requests page can
        * scope rows to the signed-in employee with a single predicate.
        */
        try {

            $stmt = $this->db->prepare("
                INSERT INTO onboarding_documents (
                    onboarding_id,
                    employee_id,
                    document_name,
                    request_type,
                    file_path,
                    instruction,
                    action_type,
                    priority,
                    due_date,
                    assigned_by,
                    status
                )
                VALUES (
                    :onboarding_id,
                    :employee_id,
                    :document_name,
                    :request_type,
                    NULL,
                    :instruction,
                    :action_type,
                    :priority,
                    :due_date,
                    :assigned_by,
                    'Pending'
                )
            ");

            $stmt->execute([
                ':onboarding_id' =>
                    $onboardingId,

                ':employee_id' =>
                    $this->getEmployeeIdForOnboarding(
                        $onboardingId
                    ),

                ':document_name' =>
                    $documentName,

                ':request_type' =>
                    $requestType,

                ':instruction' =>
                    $instruction !== '' ? $instruction : null,

                ':action_type' =>
                    $actionType,

                ':priority' =>
                    $priority,

                ':due_date' =>
                    $dueDate,

                ':assigned_by' =>
                    $assignedBy
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
    | EMPLOYEE FOR AN ONBOARDING RECORD
    |--------------------------------------------------------------------------
    |
    | onboarding.application_id and employees.application_id are both
    | unique, so this resolves to exactly one employee.
    |
    */

    public function getEmployeeIdForOnboarding(
        int $onboardingId
    ): ?int {

        $stmt = $this->db->prepare("
            SELECT e.employee_id

            FROM onboarding o

            INNER JOIN employees e
                ON o.application_id = e.application_id

            WHERE o.onboarding_id = ?

            LIMIT 1
        ");

        $stmt->execute([
            $onboardingId
        ]);

        $employeeId = $stmt->fetchColumn();

        if ($employeeId === false || $employeeId === null) {
            return null;
        }

        return (int) $employeeId;
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY DOCUMENT
    |--------------------------------------------------------------------------
    */

    public function updateDocumentName(
        int $documentId,
        string $documentName,
        string $instruction = '',
        ?string $requestType = null,
        string $actionType = 'document',
        string $priority = 'Normal',
        ?string $dueDate = null
    ): array {
        $documentName = trim($documentName);
        $instruction = trim($instruction);

        if ($documentId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid document.'
            ];
        }

        if ($documentName === '') {
            return [
                'success' => false,
                'message' => 'Document name cannot be empty.'
            ];
        }

        if (mb_strlen($documentName) > 150) {
            return [
                'success' => false,
                'message' => 'Document name must not exceed 150 characters.'
            ];
        }

        if (mb_strlen($instruction) > 500) {
            return [
                'success' => false,
                'message' => 'Instructions must not exceed 500 characters.'
            ];
        }

        /*
        * Optional categorisation — same fallback rules as requestDocument().
        */
        if (
            $requestType === null
            || !array_key_exists($requestType, self::REQUEST_TYPES)
        ) {
            $requestType = 'Submit Required Document';
        }

        if (!in_array($actionType, self::ACTION_TYPES, true)) {
            $actionType = 'document';
        }

        if (!in_array($priority, self::PRIORITIES, true)) {
            $priority = 'Normal';
        }

        $stmt = $this->db->prepare("
            SELECT document_id, status
            FROM onboarding_documents
            WHERE document_id = ?
            LIMIT 1
        ");

        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            return [
                'success' => false,
                'message' => 'Document not found.'
            ];
        }

        if (($document['status'] ?? '') !== 'Pending') {
            return [
                'success' => false,
                'message' => 'Only pending documents can be edited.'
            ];
        }

        $stmt = $this->db->prepare("
            UPDATE onboarding_documents
            SET
                document_name = ?,
                instruction = ?,
                request_type = ?,
                action_type = ?,
                priority = ?,
                due_date = ?
            WHERE document_id = ?
        ");

        $stmt->execute([
            $documentName,
            $instruction !== '' ? $instruction : null,
            $requestType,
            $actionType,
            $priority,
            $dueDate !== '' ? $dueDate : null,
            $documentId
        ]);

        return [
            'success' => true,
            'message' => 'Document updated successfully.'
        ];
    }
    
    public function deleteDocument(int $documentId): array
    {
        if ($documentId <= 0) {
            return [
                'success' => false,
                'message' => 'Invalid document.'
            ];
        }

        $stmt = $this->db->prepare("
            SELECT document_id, onboarding_id, status
            FROM onboarding_documents
            WHERE document_id = ?
            LIMIT 1
        ");

        $stmt->execute([$documentId]);
        $document = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$document) {
            return [
                'success' => false,
                'message' => 'Document not found.'
            ];
        }

        if (($document['status'] ?? '') !== 'Pending') {
            return [
                'success' => false,
                'message' => 'Only pending document requests can be deleted.'
            ];
        }

        $stmt = $this->db->prepare("
            DELETE FROM onboarding_documents
            WHERE document_id = ?
        ");

        $stmt->execute([$documentId]);

        $this->updateOnboardingStatus((int) $document['onboarding_id']);

        return [
            'success' => true,
            'message' => 'Document request deleted successfully.'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | VERIFY DOCUMENT
    |--------------------------------------------------------------------------
    */

    public function verifyDocument(
        int $documentId,
        string $remarks = '',
        ?int $reviewedBy = null
    ): array {

        $remarks = trim($remarks);

        if ($documentId <= 0) {

            return [
                'success' => false,
                'message' => 'Invalid document.'
            ];
        }

        if (mb_strlen($remarks) > 1000) {

            return [
                'success' => false,
                'message' => 'Remarks must not exceed 1000 characters.'
            ];
        }

        /*
        * Get the document.
        */
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

        /*
        * Only submitted documents can be verified.
        */
        if (($document['status'] ?? '') !== 'Submitted') {

            return [
                'success' => false,
                'message' => 'Only submitted documents can be verified.'
            ];
        }

        /*
        * Approve document and save optional remarks.
        */
        $now = date('Y-m-d H:i:s');

        $stmt = $this->db->prepare("
            UPDATE onboarding_documents
            SET
                status = 'Verified',
                remarks = ?,
                reviewed_by = ?,
                reviewed_at = ?,
                completed_at = ?
            WHERE document_id = ?
        ");

        $stmt->execute([
            $remarks !== '' ? $remarks : null,
            $reviewedBy,
            $now,
            $now,
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
            'message' => 'Document approved successfully.'
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | REJECT DOCUMENT
    |--------------------------------------------------------------------------
    */

    public function rejectDocument(
        int $documentId,
        string $remarks,
        ?int $reviewedBy = null
    ): array {

        $remarks = trim($remarks);

        if ($documentId <= 0) {

            return [
                'success' => false,
                'message' => 'Invalid document.'
            ];
        }

        /*
        * Rejection remarks are mandatory.
        */
        if ($remarks === '') {

            return [
                'success' => false,
                'message' => 'Rejection remarks are required.'
            ];
        }

        if (mb_strlen($remarks) > 1000) {

            return [
                'success' => false,
                'message' => 'Remarks must not exceed 1000 characters.'
            ];
        }

        /*
        * Get the document.
        */
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

        /*
        * Only submitted documents can be rejected.
        */
        if (($document['status'] ?? '') !== 'Submitted') {

            return [
                'success' => false,
                'message' => 'Only submitted documents can be rejected.'
            ];
        }

        /*
        * Reject document and save remarks.
        */
        $stmt = $this->db->prepare("
            UPDATE onboarding_documents
            SET
                status = 'Rejected',
                remarks = ?,
                reviewed_by = ?,
                reviewed_at = ?
            WHERE document_id = ?
        ");

        $stmt->execute([
            $remarks,
            $reviewedBy,
            date('Y-m-d H:i:s'),
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
            'message' => 'Document rejected successfully.'
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
         * In Progress:
         *
         * - Documents exist
         * OR
         * - Orientation has been scheduled/recorded
         */
        elseif (
            $progress['total'] > 0 ||
            !empty($onboarding['orientation_date'])
        ) {

            $status = 'In Progress';

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


    /*
    |--------------------------------------------------------------------------
    | GET ONBOARDING PHASES
    |--------------------------------------------------------------------------
    */

    public function getOnboardingPhases(int $onboardingId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                phase_id,
                onboarding_id,
                phase_number,
                phase_name,
                phase_description,
                status,
                completed_at

            FROM onboarding_phases

            WHERE onboarding_id = ?

            ORDER BY phase_number ASC
        ");

        $stmt->execute([
            $onboardingId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?? [];
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE PHASE STATUS
    |--------------------------------------------------------------------------
    */

    public function updatePhaseStatus(
        int $onboardingId,
        int $phaseNumber,
        string $status
    ): bool {

        $validStatuses = ['pending', 'in-progress', 'completed'];

        if (!in_array(strtolower($status), $validStatuses)) {
            return false;
        }

        $completedAt = (strtolower($status) === 'completed')
            ? date('Y-m-d H:i:s')
            : null;

        $stmt = $this->db->prepare("
            UPDATE onboarding_phases

            SET status = ?,
                completed_at = ?

            WHERE onboarding_id = ?
            AND phase_number = ?
        ");

        return $stmt->execute([
            strtolower($status),
            $completedAt,
            $onboardingId,
            $phaseNumber
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | CALCULATE ONBOARDING PROGRESS (NEW 5-PHASE)
    |--------------------------------------------------------------------------
    */

    public function calculateProgressFromPhases(int $onboardingId): int
    {
        $phases = $this->getOnboardingPhases($onboardingId);

        if (empty($phases)) {
            return 0;
        }

        $completedCount = 0;

        foreach ($phases as $phase) {
            if (strtolower($phase['status']) === 'completed') {
                $completedCount++;
            }
        }

        return (int) round(
            ($completedCount / count($phases)) * 100
        );
    }

    private function resolveCurrentPhase(array $phases): string
    {
        if (empty($phases)) {
            return 'Not Started';
        }

        foreach ($phases as $phase) {
            $status = strtolower((string) ($phase['status'] ?? 'pending'));

            if ($status === 'in-progress') {
                return (string) ($phase['phase_name'] ?? 'In Progress');
            }
        }

        foreach ($phases as $phase) {
            $status = strtolower((string) ($phase['status'] ?? 'pending'));

            if ($status !== 'completed') {
                return (string) ($phase['phase_name'] ?? 'Pending');
            }
        }

        $lastPhase = end($phases);

        if ($lastPhase !== false) {
            return (string) ($lastPhase['phase_name'] ?? 'Completed');
        }

        return 'Completed';
    }


    /*
    |--------------------------------------------------------------------------
    | INITIALIZE ONBOARDING PHASES
    |--------------------------------------------------------------------------
    */

    public function initializePhases(int $onboardingId): bool
    {
        // Check if phases already exist
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM onboarding_phases
            WHERE onboarding_id = ?
        ");

        $stmt->execute([$onboardingId]);

        if ($stmt->fetchColumn() > 0) {
            return false; // Phases already initialized
        }

        $phases = [
            [
                'number' => 1,
                'name' => 'System & Access Setup',
                'description' => 'System automatically checks that the employee account and access are properly created'
            ],
            [
                'number' => 2,
                'name' => 'Orientation & Setup',
                'description' => "System checks the employee's orientation/setup information"
            ],
            [
                'number' => 3,
                'name' => 'Document Submission',
                'description' => 'System automatically checks whether all required documents have been submitted'
            ],
            [
                'number' => 4,
                'name' => 'Document Verification',
                'description' => 'HR/Admin verifies the submitted documents'
            ],
            [
                'number' => 5,
                'name' => 'Final Onboarding Completion',
                'description' => 'System automatically completes onboarding when everything required is finished'
            ]
        ];

        foreach ($phases as $phase) {
            $stmt = $this->db->prepare("
                INSERT INTO onboarding_phases (
                    onboarding_id,
                    phase_number,
                    phase_name,
                    phase_description,
                    status
                ) VALUES (?, ?, ?, ?, 'pending')
            ");

            $stmt->execute([
                $onboardingId,
                $phase['number'],
                $phase['name'],
                $phase['description']
            ]);
        }

        return true;
    }
}