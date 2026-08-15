<?php

namespace App\Models;

use Core\Database;
use PDO;

class EmployeeRecords
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    /*
    |--------------------------------------------------------------------------
    | GET ALL EMPLOYEES
    |--------------------------------------------------------------------------
    */

    public function getAllEmployees(): array
    {
        $sql = "
            SELECT
                e.employee_id,
                e.employee_number,
                e.application_id,
                e.position_id,
                e.hire_date,
                e.employment_status,

                ap.applicant_id,

                CONCAT(
                    COALESCE(ap.first_name, ''),
                    ' ',
                    COALESCE(ap.last_name, '')
                ) AS fullname,

                ap.email,

                d.department_name,

                p.position_name AS job_title,

                jp.employment_type

            FROM employees e

            LEFT JOIN applications app
                ON e.application_id = app.application_id

            LEFT JOIN applicants ap
                ON app.applicant_id = ap.applicant_id

            LEFT JOIN job_postings jp
                ON app.posting_id = jp.posting_id

            LEFT JOIN positions p
                ON e.position_id = p.position_id

            LEFT JOIN departments d
                ON p.department_id = d.department_id

            ORDER BY e.hire_date DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | GET EMPLOYEE BY ID
    |--------------------------------------------------------------------------
    */

    public function getEmployeeById(int $employeeId): ?array
    {
        $sql = "
            SELECT
                e.employee_id,
                e.employee_number,
                e.application_id,
                e.position_id,
                e.hire_date,
                e.employment_status,

                ap.applicant_id,

                CONCAT(
                    COALESCE(ap.first_name, ''),
                    ' ',
                    COALESCE(ap.last_name, '')
                ) AS fullname,

                ap.email,

                d.department_name,

                p.position_name AS job_title,

                jp.employment_type

            FROM employees e

            LEFT JOIN applications app
                ON e.application_id = app.application_id

            LEFT JOIN applicants ap
                ON app.applicant_id = ap.applicant_id

            LEFT JOIN job_postings jp
                ON app.posting_id = jp.posting_id

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

        $employee = $stmt->fetch(PDO::FETCH_ASSOC);

        return $employee ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | GET DEPARTMENTS USED BY EMPLOYEES
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

            ORDER BY d.department_name ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}