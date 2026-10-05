<?php

namespace App\Models;

use Core\Database;
use PDO;

/**
 * Assigned Requests
 *
 * Requests / required actions that HR or Administration assigned to an
 * employee. These live in HR's existing onboarding document requests —
 * `onboarding_documents` — rather than a table of their own:
 *
 *   HR  requests a document during onboarding  -> onboarding_documents
 *   employee fulfils it on this page           -> stays Pending, submitted_at set
 *   HR  verifies or rejects it in onboarding   -> Approved / Rejected
 *   HR  finalises an approved document         -> Completed
 *
 * The page therefore has its own vocabulary derived from the canonical
 * request statuses:
 *
 *   Pending (not submitted)  -> Pending Action
 *   Pending (submitted)      -> In Progress
 *   Approved                 -> In Progress
 *   Completed                -> Completed
 *   Rejected                 -> Pending Action (returned to the employee)
 *
 * "Submitted" is derived from submitted_at, since the canonical enum has no
 * separate Submitted state. "Overdue" is derived from due_date on read, so
 * it can never disagree with the real status.
 */
class AssignedRequests
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }


    /*
    |--------------------------------------------------------------------------
    | SHARED SELECT
    |--------------------------------------------------------------------------
    |
    | One column list shared by the employee and HR read queries so the two
    | can never drift apart. Column names are aliased to the vocabulary the
    | Assigned Requests page speaks (title, description, submission_file).
    |
    */

    private const REQUEST_COLUMNS = "
        od.document_id AS request_id,
        od.employee_id,

        od.document_name AS title,

        COALESCE(
            NULLIF(od.request_type, ''),
            'Submit Required Document'
        ) AS request_type,

        COALESCE(od.instruction, '') AS description,

        od.action_type,
        od.priority,
        od.due_date,

        od.status AS source_status,

        CASE
            WHEN od.status = 'Completed' THEN 'Completed'
            WHEN od.status = 'Approved' THEN 'In Progress'
            WHEN od.status = 'Pending' AND od.submitted_at IS NOT NULL
                THEN 'In Progress'
            ELSE 'Pending Action'
        END AS status,

        od.response_text,
        od.file_path AS submission_file,
        od.submission_file_size,
        od.submission_mime,
        od.submitted_at,

        od.reviewed_by,
        od.remarks AS review_remarks,
        od.reviewed_at,
        od.completed_at,

        od.created_at,
        od.updated_at,

        e.employee_number,

        CONCAT_WS(
            ' ',
            NULLIF(ap.first_name, ''),
            NULLIF(ap.middle_name, ''),
            NULLIF(ap.last_name, '')
        ) AS employee_name,

        COALESCE(
            NULLIF(
                TRIM(
                    CONCAT_WS(
                        ' ',
                        NULLIF(hr_ap.first_name, ''),
                        NULLIF(hr_ap.last_name, '')
                    )
                ),
                ''
            ),
            hr_user.username
        ) AS assigned_by_name,

        hr_role.role_name AS assigned_by_role,

        (
            od.due_date IS NOT NULL
            AND od.due_date < CURDATE()
            AND (
                (od.status = 'Pending' AND od.submitted_at IS NULL)
                OR od.status = 'Rejected'
            )
        ) AS is_overdue
    ";

    private const REQUEST_JOINS = "
        LEFT JOIN employees e
            ON od.employee_id = e.employee_id

        LEFT JOIN applications emp_app
            ON e.application_id = emp_app.application_id

        LEFT JOIN applicants ap
            ON emp_app.applicant_id = ap.applicant_id

        LEFT JOIN users hr_user
            ON od.assigned_by = hr_user.user_id

        LEFT JOIN roles hr_role
            ON hr_user.role_id = hr_role.role_id

        LEFT JOIN employees hr_emp
            ON hr_user.employee_id = hr_emp.employee_id

        LEFT JOIN applications hr_app
            ON hr_emp.application_id = hr_app.application_id

        LEFT JOIN applicants hr_ap
            ON hr_app.applicant_id = hr_ap.applicant_id
    ";


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE — ALL ASSIGNED REQUESTS
    |--------------------------------------------------------------------------
    |
    | Actionable items first: anything still waiting on the employee
    | (overdue, then urgent, then soonest due) rises to the top; completed
    | items sink to the bottom.
    |
    */

    public function getRequestsForEmployee(
        int $employeeId
    ): array {

        $sql = "
            SELECT
                " . self::REQUEST_COLUMNS . "

            FROM onboarding_documents od

            " . self::REQUEST_JOINS . "

            WHERE od.employee_id = :employee_id

            ORDER BY
                (od.status = 'Completed') ASC,

                (
                    od.due_date IS NOT NULL
                    AND od.due_date < CURDATE()
                    AND (
                        (od.status = 'Pending' AND od.submitted_at IS NULL)
                        OR od.status = 'Rejected'
                    )
                ) DESC,

                FIELD(od.priority, 'Urgent', 'High', 'Normal'),

                (od.due_date IS NULL) ASC,
                od.due_date ASC,

                od.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':employee_id',
            $employeeId,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE — ONE REQUEST (OWNERSHIP ENFORCED)
    |--------------------------------------------------------------------------
    |
    | The employee_id predicate is part of the query, not a later check,
    | so an employee can never read another employee's request even by
    | guessing a document_id.
    |
    */

    public function getRequestForEmployee(
        int $requestId,
        int $employeeId
    ): ?array {

        $sql = "
            SELECT
                " . self::REQUEST_COLUMNS . "

            FROM onboarding_documents od

            " . self::REQUEST_JOINS . "

            WHERE
                od.document_id = :request_id
                AND od.employee_id = :employee_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':request_id',
            $requestId,
            PDO::PARAM_INT
        );

        $stmt->bindValue(
            ':employee_id',
            $employeeId,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        return $request ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | HR / ADMIN — ONE REQUEST
    |--------------------------------------------------------------------------
    */

    public function getRequestById(
        int $requestId
    ): ?array {

        $sql = "
            SELECT
                " . self::REQUEST_COLUMNS . "

            FROM onboarding_documents od

            " . self::REQUEST_JOINS . "

            WHERE od.document_id = :request_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':request_id',
            $requestId,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $request = $stmt->fetch(PDO::FETCH_ASSOC);

        return $request ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | REQUEST TIMELINE
    |--------------------------------------------------------------------------
    |
    | Built from the row's own timestamps instead of a separate activity
    | table. Returns the same shape the page already renders: a timestamp,
    | an event key (for the icon) and a description.
    |
    */

    public function buildTimeline(
        array $request
    ): array {

        $events = [];

        if (!empty($request['created_at'])) {
            $events[] = [
                'created_at' => $request['created_at'],
                'event' => 'created',
                'description' => 'HR requested this item.'
            ];
        }

        if (!empty($request['submitted_at'])) {

            $events[] = [
                'created_at' => $request['submitted_at'],
                'event' => 'submitted',
                'description' =>
                    ($request['action_type'] ?? '') === 'confirmation'
                        ? 'You confirmed this request.'
                        : 'You submitted the requested information.'
            ];
        }

        if (!empty($request['reviewed_at'])) {

            $events[] = [
                'created_at' => $request['reviewed_at'],
                'event' => 'reviewed',
                'description' =>
                    ($request['source_status'] ?? '') === 'Rejected'
                        ? 'HR returned this item for revision.'
                        : 'HR reviewed your submission.'
            ];
        }

        if (!empty($request['completed_at'])) {

            $events[] = [
                'created_at' => $request['completed_at'],
                'event' => 'completed',
                'description' => 'This request was completed.'
            ];
        }

        return $events;
    }


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEE — SUBMIT RESPONSE / DOCUMENT / CONFIRMATION
    |--------------------------------------------------------------------------
    |
    | The request's own action_type decides what is required, never the
    | client. Every action type funnels into the same state — the row stays
    | "Pending" with submitted_at stamped — so there is exactly one
    | completion mechanism: HR's review in Onboarding.
    |
    | $file is already validated and stored by the controller and arrives
    | as metadata only, so this method never touches the filesystem.
    |
    */

    public function submit(
        int $requestId,
        int $employeeId,
        int $actorUserId,
        ?string $responseText,
        ?array $file
    ): array {

        $request = $this->getRequestForEmployee(
            $requestId,
            $employeeId
        );

        if (!$request) {

            return [
                'success' => false,
                'message' => 'Assigned request not found.'
            ];
        }

        if (
            in_array(
                $request['source_status'] ?? '',
                ['Approved', 'Completed'],
                true
            )
        ) {

            return [
                'success' => false,
                'message' => 'This request has already been accepted and cannot be resubmitted.'
            ];
        }

        $actionType =
            $request['action_type'] ?? 'document';

        $hasText =
            $responseText !== null
            && trim($responseText) !== '';

        $existingFile =
            trim((string) ($request['submission_file'] ?? ''));


        /*
        |--------------------------------------------------------------------------
        | Required input per action type
        |--------------------------------------------------------------------------
        */

        if (
            $actionType === 'document'
            && $file === null
            && $existingFile === ''
        ) {

            return [
                'success' => false,
                'message' => 'Please attach the requested document.'
            ];
        }

        if ($actionType === 'response' && !$hasText) {

            return [
                'success' => false,
                'message' => 'Please provide the requested response.'
            ];
        }


        /*
        |--------------------------------------------------------------------------
        | Build the update
        |--------------------------------------------------------------------------
        */

        $now = date('Y-m-d H:i:s');

        $fields = [
            'status = :status',
            'submitted_at = :submitted_at'
        ];

        $params = [
            ':status' => 'Pending',
            ':submitted_at' => $now,
            ':request_id' => $requestId,
            ':employee_id' => $employeeId
        ];

        if ($hasText) {
            $fields[] = 'response_text = :response_text';
            $params[':response_text'] = trim((string) $responseText);
        }

        if ($file !== null) {

            $fields[] = 'file_path = :file_path';
            $fields[] = 'submission_file_size = :submission_file_size';
            $fields[] = 'submission_mime = :submission_mime';

            $params[':file_path'] = $file['stored_name'];
            $params[':submission_file_size'] = $file['size'];
            $params[':submission_mime'] = $file['mime'];
        }


        $sql = "
            UPDATE onboarding_documents

            SET " . implode(', ', $fields) . "

            WHERE
                document_id = :request_id
                AND employee_id = :employee_id
                AND status NOT IN ('Approved', 'Completed')
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute($params);

        if ($stmt->rowCount() === 0) {

            return [
                'success' => false,
                'message' =>
                    'This request could not be updated. It may have been completed already.'
            ];
        }

        return [
            'success' => true,
            'message' => $actionType === 'confirmation'
                ? 'Thank you. Your confirmation has been sent to HR.'
                : 'Your submission was sent. HR will review it shortly.'
        ];
    }
}
