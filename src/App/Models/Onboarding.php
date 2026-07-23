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
                o.orientation_date,
                o.onboarding_status,

                a.first_name,
                a.last_name,

                jp.title AS position,

                e.employee_number,
                e.hire_date

            FROM onboarding o

            JOIN applications app
                ON o.application_id = app.application_id

            JOIN applicants a
                ON app.applicant_id = a.applicant_id

            JOIN job_postings jp
                ON app.posting_id = jp.posting_id

            LEFT JOIN employees e
                ON app.application_id = e.application_id

            ORDER BY o.onboarding_id DESC

        ";

    return $this->db
        ->query($sql)
        ->fetchAll(PDO::FETCH_ASSOC);
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