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

    public function getAllOnboarding()
    {
        $sql = "
            SELECT
                o.onboarding_id,
                o.application_id,
                o.orientation_date,
                o.onboarding_status,
                o.remarks,

                e.employee_id,
                e.employee_number,
                e.hire_date,
                e.employment_status,

                a.first_name,
                a.middle_name,
                a.last_name,
                a.email,

                CONCAT(
                    a.first_name,
                    ' ',
                    COALESCE(CONCAT(a.middle_name, ' '), ''),
                    a.last_name
                ) AS fullname,

                d.department_name,

                p.position_name AS job_title

            FROM onboarding o

            INNER JOIN applications app
                ON o.application_id = app.application_id

            INNER JOIN applicants a
                ON app.applicant_id = a.applicant_id

            INNER JOIN job_postings jp
                ON app.posting_id = jp.posting_id

            INNER JOIN positions p
                ON jp.position_id = p.position_id

            INNER JOIN departments d
                ON p.department_id = d.department_id

            INNER JOIN employees e
                ON app.application_id = e.application_id

            ORDER BY o.onboarding_id DESC
        ";

        $employees = $this->db
            ->query($sql)
            ->fetchAll(PDO::FETCH_ASSOC);

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

            $employee['start_date'] = $employee['hire_date'];
        }

        return $employees;
    }

    public function countAll()
    {
        return $this->db
                    ->query("SELECT COUNT(*) FROM onboarding")
                    ->fetchColumn();
    }

    public function countStatus(string $status)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM onboarding
            WHERE onboarding_status = ?
        ");

        $stmt->execute([$status]);

        return $stmt->fetchColumn();
    }
}