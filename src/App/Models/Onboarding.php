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

            /*
             * Onboarding → Application
             */
            LEFT JOIN applications app
                ON o.application_id = app.application_id

            /*
             * Application → Applicant
             */
            LEFT JOIN applicants a
                ON app.applicant_id = a.applicant_id

            /*
             * Application → Employee
             *
             * Employee is the main HR record.
             */
            LEFT JOIN employees e
                ON o.application_id = e.application_id

            /*
             * Employee → Position
             *
             * IMPORTANT:
             * Do not use job_postings here.
             */
            LEFT JOIN positions p
                ON e.position_id = p.position_id

            /*
             * Position → Department
             */
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

        $stmt->execute([$status]);

        return (int) $stmt->fetchColumn();
    }
}