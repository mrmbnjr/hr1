<?php

namespace App\Models;

use Core\Database;
use PDO;

class Dashboard {

    private PDO $db;
    
    public function __construct()
    {
        $this->db = Database::connection();
    }

    public function getEmployeeGrowth()
    {
        $sql = "
            SELECT
                DATE_FORMAT(hire_date,'%b %y') AS month,
                COUNT(*) AS total
            FROM employees
            GROUP BY YEAR(hire_date), MONTH(hire_date)
            ORDER BY hire_date
        ";

        return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getNewEmployees($limit = 5)
    {
        $sql = "
            SELECT
                CONCAT(ap.first_name, ' ', ap.last_name) AS employee_name,
                jp.title,
                e.employment_status,
                o.onboarding_status,
                e.hire_date

            FROM employees e

            INNER JOIN applications app
                ON e.application_id = app.application_id

            INNER JOIN applicants ap
                ON app.applicant_id = ap.applicant_id

            INNER JOIN job_postings jp
                ON app.posting_id = jp.posting_id

            LEFT JOIN onboarding o
                ON app.application_id = o.application_id

            ORDER BY e.hire_date DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, (int) $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentActivities($limit = 10)
    {
        $sql = "
            (
                SELECT
                    'applicant' AS activity_type,
                    'New applicant submitted' AS activity_title,
                    CONCAT(a.first_name, ' ', a.last_name, ' applied for ', jp.title) AS activity_description,
                    app.applied_at AS activity_date

                FROM applications app
                INNER JOIN applicants a
                    ON app.applicant_id = a.applicant_id
                INNER JOIN job_postings jp
                    ON app.posting_id = jp.posting_id
            )

            UNION ALL

            (
                SELECT
                    'job' AS activity_type,
                    'Job posting created' AS activity_title,
                    CONCAT(jp.title, ' was posted.') AS activity_description,
                    jp.created_at AS activity_date

                FROM job_postings jp
            )

            UNION ALL

            (
                SELECT
                    'employee' AS activity_type,
                    'New employee hired' AS activity_title,
                    CONCAT(a.first_name, ' ', a.last_name, ' joined as ', jp.title) AS activity_description,
                    e.hire_date AS activity_date

                FROM employees e
                INNER JOIN applications app
                    ON e.application_id = app.application_id
                INNER JOIN applicants a
                    ON app.applicant_id = a.applicant_id
                INNER JOIN job_postings jp
                    ON app.posting_id = jp.posting_id
            )

            UNION ALL

            (
                SELECT
                    'onboarding' AS activity_type,
                    'Onboarding updated' AS activity_title,
                    CONCAT(a.first_name, ' ', a.last_name, ' onboarding is ', o.onboarding_status) AS activity_description,
                    o.orientation_date AS activity_date

                FROM onboarding o
                INNER JOIN applications app
                    ON o.application_id = app.application_id
                INNER JOIN applicants a
                    ON app.applicant_id = a.applicant_id
            )

            ORDER BY activity_date DESC
            LIMIT ?
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}