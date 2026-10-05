<?php

namespace App\Models;

use Core\Database;
use PDO;

class EmployeeRequests
{
    private PDO $db;


    public function __construct()
    {
        $this->db = Database::connection();
    }


    /*
    |--------------------------------------------------------------------------
    | GET ALL EMPLOYEE REQUESTS
    |--------------------------------------------------------------------------
    */

    public function getAllRequests(): array
    {
        $sql = "
            SELECT
                er.request_id,
                er.employee_id,

                e.employee_number,
                e.application_id,
                e.position_id,
                e.hire_date,
                e.employment_status,

                ap.applicant_id,

                CONCAT_WS(
                    ' ',
                    NULLIF(ap.first_name, ''),
                    NULLIF(ap.middle_name, ''),
                    NULLIF(ap.last_name, '')
                ) AS fullname,

                ap.email,
                ap.phone,

                d.department_id,
                d.department_name,

                p.position_name AS job_title,

                er.request_type,
                er.subject,
                er.description,

                er.status,
                er.hr_remarks,

                er.requested_at,
                er.resolved_at,
                er.resolved_by

            FROM employee_requests er

            LEFT JOIN employees e
                ON er.employee_id = e.employee_id

            LEFT JOIN applications app
                ON e.application_id = app.application_id

            LEFT JOIN applicants ap
                ON app.applicant_id = ap.applicant_id

            LEFT JOIN positions p
                ON e.position_id = p.position_id

            LEFT JOIN departments d
                ON p.department_id = d.department_id

            ORDER BY er.requested_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | GET EMPLOYEE REQUEST BY ID
    |--------------------------------------------------------------------------
    */

    public function getRequestById(
        int $requestId
    ): ?array {

        $sql = "
            SELECT
                er.request_id,
                er.employee_id,

                e.employee_number,
                e.application_id,
                e.position_id,
                e.hire_date,
                e.employment_status,

                ap.applicant_id,

                CONCAT_WS(
                    ' ',
                    NULLIF(ap.first_name, ''),
                    NULLIF(ap.middle_name, ''),
                    NULLIF(ap.last_name, '')
                ) AS fullname,

                ap.email,
                ap.phone,
                ap.address,

                d.department_id,
                d.department_name,

                p.position_name AS job_title,

                er.request_type,
                er.subject,
                er.description,

                er.status,
                er.hr_remarks,

                er.requested_at,
                er.resolved_at,
                er.resolved_by

            FROM employee_requests er

            LEFT JOIN employees e
                ON er.employee_id = e.employee_id

            LEFT JOIN applications app
                ON e.application_id = app.application_id

            LEFT JOIN applicants ap
                ON app.applicant_id = ap.applicant_id

            LEFT JOIN positions p
                ON e.position_id = p.position_id

            LEFT JOIN departments d
                ON p.department_id = d.department_id

            WHERE er.request_id = :request_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':request_id',
            $requestId,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $request = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $request ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | GET DEPARTMENTS USED BY EMPLOYEE REQUESTS
    |--------------------------------------------------------------------------
    */

    public function getDepartments(): array
    {
        $sql = "
            SELECT DISTINCT
                d.department_id,
                d.department_name

            FROM departments d

            INNER JOIN positions p
                ON p.department_id = d.department_id

            INNER JOIN employees e
                ON e.position_id = p.position_id

            INNER JOIN employee_requests er
                ON er.employee_id = e.employee_id

            ORDER BY d.department_name ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GET ONBOARDING DOCUMENT REQUESTS (HR / Admin requested files)
    |--------------------------------------------------------------------------
    |
    | The files HR or Administration request during onboarding live in
    | onboarding_documents. They are surfaced in the same review queue as
    | employee requests so HR has one place to see every outstanding item,
    | including the ones an employee has already submitted back.
    |
    | status vocabulary (canonical):
    |   Pending   -> not yet settled; submitted_at is set once the employee
    |                submits, so "submitted, awaiting HR" is Pending +
    |                submitted_at IS NOT NULL
    |   Approved  -> HR confirmed the submission
    |   Rejected  -> not accepted; returned to the employee
    |   Completed -> the document requirement has been carried out
    |
    */

    public function getOnboardingDocumentRequests(): array
    {
        $sql = "
            SELECT
                od.document_id,
                od.onboarding_id,
                od.employee_id,

                od.document_name,

                COALESCE(
                    NULLIF(od.request_type, ''),
                    'Submit Required Document'
                ) AS request_type,

                COALESCE(od.instruction, '') AS instruction,
                od.action_type,
                od.priority,
                od.due_date,

                od.status,

                od.response_text,
                od.file_path,
                od.submission_file_size,
                od.submission_mime,
                od.submitted_at,

                od.remarks,
                od.reviewed_at,
                od.completed_at,
                od.created_at,

                e.employee_number,

                CONCAT_WS(
                    ' ',
                    NULLIF(ap.first_name, ''),
                    NULLIF(ap.middle_name, ''),
                    NULLIF(ap.last_name, '')
                ) AS fullname,

                d.department_name,
                p.position_name AS job_title,

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

                hr_role.role_name AS assigned_by_role

            FROM onboarding_documents od

            LEFT JOIN employees e
                ON od.employee_id = e.employee_id

            LEFT JOIN applications emp_app
                ON e.application_id = emp_app.application_id

            LEFT JOIN applicants ap
                ON emp_app.applicant_id = ap.applicant_id

            LEFT JOIN positions p
                ON e.position_id = p.position_id

            LEFT JOIN departments d
                ON p.department_id = d.department_id

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

            ORDER BY
                (od.submitted_at IS NOT NULL AND od.status = 'Pending') DESC,
                od.created_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | GET REQUESTS BY EMPLOYEE
    |--------------------------------------------------------------------------
    */

    public function getRequestsByEmployee(
        int $employeeId
    ): array {

        $sql = "
            SELECT
                request_id,
                employee_id,

                request_type,
                subject,
                description,

                status,
                hr_remarks,

                requested_at,
                resolved_at,
                resolved_by

            FROM employee_requests

            WHERE employee_id = :employee_id

            ORDER BY requested_at DESC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':employee_id',
            $employeeId,
            PDO::PARAM_INT
        );

        $stmt->execute();

        return $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );
    }


    /*
    |--------------------------------------------------------------------------
    | GET EMPLOYEE ID FROM USER ID
    |--------------------------------------------------------------------------
    |
    | users.employee_id connects the logged-in account to employees.
    |
    */

    public function getEmployeeIdByUserId(
        int $userId
    ): ?int {

        $sql = "
            SELECT employee_id

            FROM users

            WHERE user_id = :user_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':user_id',
            $userId,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $employeeId = $stmt->fetchColumn();

        if ($employeeId === false || $employeeId === null) {
            return null;
        }

        return (int) $employeeId;
    }


    /*
    |--------------------------------------------------------------------------
    | GET EMPLOYEE INFORMATION
    |--------------------------------------------------------------------------
    */

    public function getEmployeeInfo(
        int $employeeId
    ): ?array {

        $sql = "
            SELECT
                e.employee_id,
                e.employee_number,
                e.application_id,
                e.position_id,
                e.hire_date,
                e.employment_status,

                CONCAT_WS(
                    ' ',
                    NULLIF(ap.first_name, ''),
                    NULLIF(ap.middle_name, ''),
                    NULLIF(ap.last_name, '')
                ) AS fullname,

                ap.email,
                ap.phone,
                ap.address,

                d.department_name,

                p.position_name AS job_title

            FROM employees e

            LEFT JOIN applications app
                ON e.application_id = app.application_id

            LEFT JOIN applicants ap
                ON app.applicant_id = ap.applicant_id

            LEFT JOIN positions p
                ON e.position_id = p.position_id

            LEFT JOIN departments d
                ON p.department_id = d.department_id

            WHERE e.employee_id = :employee_id

            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':employee_id',
            $employeeId,
            PDO::PARAM_INT
        );

        $stmt->execute();

        $employee = $stmt->fetch(
            PDO::FETCH_ASSOC
        );

        return $employee ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE EMPLOYEE REQUEST
    |--------------------------------------------------------------------------
    */

    public function createRequest(
        int $employeeId,
        string $requestType,
        string $subject,
        string $description
    ): bool {

        $sql = "
            INSERT INTO employee_requests (
                employee_id,
                request_type,
                subject,
                description
            )

            VALUES (
                :employee_id,
                :request_type,
                :subject,
                :description
            )
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':employee_id' =>
                $employeeId,

            ':request_type' =>
                $requestType,

            ':subject' =>
                $subject,

            ':description' =>
                $description
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE REQUEST DETAILS (Employee Side)
    |--------------------------------------------------------------------------
    */

    public function updateRequestDetails(
        int $requestId,
        int $employeeId,
        string $subject,
        string $description
    ): bool {
        $sql = "
            UPDATE employee_requests
            SET
                subject = :subject,
                description = :description
            WHERE
                request_id = :request_id
                AND employee_id = :employee_id
                AND status = 'Pending'
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':subject' => $subject,
            ':description' => $description,
            ':request_id' => $requestId,
            ':employee_id' => $employeeId
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE REQUEST STATUS + HR REMARKS (HR Side)
    |--------------------------------------------------------------------------
    */

    public function updateStatus(
        int $requestId,
        string $status,
        ?string $hrRemarks,
        ?int $resolvedBy
    ): bool {

        $allowedStatuses = [
            'Approved',
            'Rejected',
            'Completed'
        ];

        if (!in_array(
            $status,
            $allowedStatuses,
            true
        )) {
            return false;
        }

        $resolvedAt = date(
            'Y-m-d H:i:s'
        );

        $sql = "
            UPDATE employee_requests

            SET
                status = :status,
                hr_remarks = :hr_remarks,
                resolved_at = :resolved_at,
                resolved_by = :resolved_by

            WHERE
                request_id = :request_id

                AND status = 'Pending'
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':status' =>
                $status,

            ':hr_remarks' =>
                $hrRemarks,

            ':resolved_at' =>
                $resolvedAt,

            ':resolved_by' =>
                $resolvedBy,

            ':request_id' =>
                $requestId
        ]);

        return $stmt->rowCount() > 0;
    }
}
