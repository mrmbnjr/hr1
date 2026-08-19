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

    public function getRequestById(int $requestId): ?array
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

        $request = $stmt->fetch(PDO::FETCH_ASSOC);

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

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | GET REQUESTS BY EMPLOYEE
    |--------------------------------------------------------------------------
    */

    public function getRequestsByEmployee(int $employeeId): array
    {
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

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
            ':employee_id'  => $employeeId,
            ':request_type' => $requestType,
            ':subject'      => $subject,
            ':description'  => $description
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE REQUEST STATUS
    |--------------------------------------------------------------------------
    */

    public function updateStatus(
        int $requestId,
        string $status,
        ?string $hrRemarks,
        ?int $resolvedBy
    ): bool {

        $resolvedAt = null;

        if (
            in_array(
                $status,
                [
                    'Approved',
                    'Rejected',
                    'Completed'
                ],
                true
            )
        ) {
            $resolvedAt = date('Y-m-d H:i:s');
        }

        $sql = "
            UPDATE employee_requests

            SET
                status = :status,
                hr_remarks = :hr_remarks,
                resolved_at = :resolved_at,
                resolved_by = :resolved_by

            WHERE request_id = :request_id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':status'      => $status,
            ':hr_remarks'  => $hrRemarks,
            ':resolved_at' => $resolvedAt,
            ':resolved_by' => $resolvedBy,
            ':request_id'  => $requestId
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE HR REMARKS
    |--------------------------------------------------------------------------
    */

    public function updateRemarks(
        int $requestId,
        ?string $hrRemarks
    ): bool {

        $sql = "
            UPDATE employee_requests

            SET
                hr_remarks = :hr_remarks

            WHERE request_id = :request_id
        ";

        $stmt = $this->db->prepare($sql);

        return $stmt->execute([
            ':hr_remarks' => $hrRemarks,
            ':request_id' => $requestId
        ]);
    }
}