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

                /* Employee information */
                e.employee_id,
                e.employee_number,
                e.application_id,
                e.hire_date,
                e.employment_status,

                /* Applicant information */
                ap.applicant_id,

                CONCAT(
                    ap.first_name,
                    ' ',
                    ap.last_name
                ) AS fullname,

                ap.email,

                /* Organization information */
                d.department_name,

                p.position_name AS job_title,

                /* Job posting information */
                jp.employment_type

            FROM employees e

            INNER JOIN applications app
                ON e.application_id = app.application_id

            INNER JOIN applicants ap
                ON app.applicant_id = ap.applicant_id

            INNER JOIN job_postings jp
                ON app.posting_id = jp.posting_id

            INNER JOIN positions p
                ON jp.position_id = p.position_id

            INNER JOIN departments d
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

                /* Employee information */
                e.employee_id,
                e.employee_number,
                e.application_id,
                e.hire_date,
                e.employment_status,

                /* Applicant information */
                ap.applicant_id,

                CONCAT(
                    ap.first_name,
                    ' ',
                    ap.last_name
                ) AS fullname,

                ap.email,

                /* Organization information */
                d.department_name,

                p.position_name AS job_title,

                /* Job posting information */
                jp.employment_type

            FROM employees e

            INNER JOIN applications app
                ON e.application_id = app.application_id

            INNER JOIN applicants ap
                ON app.applicant_id = ap.applicant_id

            INNER JOIN job_postings jp
                ON app.posting_id = jp.posting_id

            INNER JOIN positions p
                ON jp.position_id = p.position_id

            INNER JOIN departments d
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

            INNER JOIN job_postings jp
                ON jp.position_id = p.position_id

            INNER JOIN applications app
                ON app.posting_id = jp.posting_id

            INNER JOIN employees e
                ON e.application_id = app.application_id

            ORDER BY d.department_name ASC
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}