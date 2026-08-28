<?php

namespace App\Models;

use Core\Database;
use PDO;
use DateTime;

class Dashboard
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }


    /*
    |--------------------------------------------------------------------------
    | QUICK STATS
    |--------------------------------------------------------------------------
    */

    public function getQuickStats()
    {
        return [

            'applicants' => $this->db->query("
                SELECT COUNT(*)
                FROM applicants
            ")->fetchColumn(),


            'postings' => $this->db->query("
                SELECT COUNT(*)
                FROM job_postings
                WHERE status = 'Open'
            ")->fetchColumn(),


            'employees' => $this->db->query("
                SELECT COUNT(*)
                FROM employees
            ")->fetchColumn(),


            'requests' => $this->db->query("
                SELECT COUNT(*)
                FROM onboarding
                WHERE onboarding_status = 'Pending'
            ")->fetchColumn()

        ];
    }



    /*
    |--------------------------------------------------------------------------
    | APPLICATION SUBMISSION CHART
    |--------------------------------------------------------------------------
    */

    public function getApplicantChart(
        string $view,
        int $year,
        ?int $month = null,
        ?string $weekStart = null
    ) {

        switch ($view) {

            case 'month':

                return $this->getMonthChart(
                    $year,
                    $month ?? date('n')
                );


            case 'week':

                return $this->getWeekChart(
                    $weekStart ?? date('Y-m-d')
                );


            case 'year':

            default:

                return $this->getYearChart($year);

        }

    }



    /*
    |--------------------------------------------------------------------------
    | YEAR VIEW
    | January - December
    |--------------------------------------------------------------------------
    */

    private function getYearChart(int $year)
    {
        $sql = "
            SELECT
                MONTH(applied_at) AS month_number,
                COUNT(*) AS total

            FROM applications

            WHERE YEAR(applied_at) = :year

            GROUP BY MONTH(applied_at)

            ORDER BY month_number
        ";


        $stmt = $this->db->prepare($sql);

        $stmt->bindValue(
            ':year',
            $year,
            PDO::PARAM_INT
        );

        $stmt->execute();


        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);


        $data = array_fill(1, 12, 0);


        foreach ($results as $row) {

            $data[(int)$row['month_number']] =
                (int)$row['total'];

        }


        return [

            'labels' => [
                'Jan',
                'Feb',
                'Mar',
                'Apr',
                'May',
                'Jun',
                'Jul',
                'Aug',
                'Sep',
                'Oct',
                'Nov',
                'Dec'
            ],

            'data' => array_values($data),

            'period' => $year,

            'subtitle' =>
                "Applications submitted throughout the year"

        ];
    }




    /*
    |--------------------------------------------------------------------------
    | MONTH VIEW
    | 1 - Last day of month
    |--------------------------------------------------------------------------
    */

    private function getMonthChart(
        int $year,
        int $month
    ) {

        $sql = "
            SELECT
                DAY(applied_at) AS day_number,
                COUNT(*) AS total

            FROM applications

            WHERE YEAR(applied_at) = :year
            AND MONTH(applied_at) = :month

            GROUP BY DAY(applied_at)

            ORDER BY day_number
        ";


        $stmt = $this->db->prepare($sql);


        $stmt->bindValue(
            ':year',
            $year,
            PDO::PARAM_INT
        );


        $stmt->bindValue(
            ':month',
            $month,
            PDO::PARAM_INT
        );


        $stmt->execute();


        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);



        $daysInMonth = cal_days_in_month(
            CAL_GREGORIAN,
            $month,
            $year
        );


        $data = array_fill(
            1,
            $daysInMonth,
            0
        );



        foreach ($results as $row) {

            $data[(int)$row['day_number']] =
                (int)$row['total'];

        }



        $labels = [];

        for ($i = 1; $i <= $daysInMonth; $i++) {

            $labels[] = $i;

        }



        return [

            'labels' => $labels,

            'data' => array_values($data),

            'period' =>
                date(
                    'F Y',
                    strtotime("$year-$month-01")
                ),

            'subtitle' =>
                "Applications submitted this month"

        ];

    }




    /*
    |--------------------------------------------------------------------------
    | WEEK VIEW
    | Monday - Sunday
    |--------------------------------------------------------------------------
    */

    private function getWeekChart(
        string $weekStart
    ) {


        $start = new DateTime($weekStart);


        $start->modify('monday this week');


        $end = clone $start;


        $end->modify('+6 days');



        $sql = "
            SELECT

                DATE(applied_at) AS application_date,

                COUNT(*) AS total


            FROM applications


            WHERE applied_at BETWEEN :start AND :end


            GROUP BY DATE(applied_at)


            ORDER BY application_date

        ";



        $stmt = $this->db->prepare($sql);



        $stmt->bindValue(
            ':start',
            $start->format('Y-m-d')
        );


        $stmt->bindValue(
            ':end',
            $end->format('Y-m-d')
        );


        $stmt->execute();



        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);



        $data = [];


        $labels = [];



        for ($i = 0; $i < 7; $i++) {


            $current = clone $start;


            $current->modify("+$i days");



            $date = $current->format('Y-m-d');


            $labels[] =
                $current->format('D');



            $data[$date] = 0;


        }



        foreach ($results as $row) {

            $data[$row['application_date']] =
                (int)$row['total'];

        }



        return [

            'labels' => $labels,

            'data' => array_values($data),

            'period' =>
                $start->format('M d')
                ." - ".
                $end->format('M d Y'),


            'subtitle' =>
                "Applications submitted this week"

        ];

    }




    /*
    |--------------------------------------------------------------------------
    | RECENT HIRES
    |--------------------------------------------------------------------------
    */

    public function getRecentHires($limit = 5)
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

        $stmt->bindValue(
            1,
            (int)$limit,
            PDO::PARAM_INT
        );

        $stmt->execute();


        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }





    /*
    |--------------------------------------------------------------------------
    | RECENT ACTIVITIES
    |--------------------------------------------------------------------------
    */

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
                    'job',
                    'Job posting created',
                    CONCAT(jp.title, ' was posted.'),
                    jp.created_at

                FROM job_postings jp
            )


            UNION ALL


            (
                SELECT
                    'employee',
                    'New employee hired',
                    CONCAT(a.first_name, ' ', a.last_name, ' joined as ', jp.title),
                    e.hire_date

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
                    'onboarding',
                    'Onboarding updated',
                    CONCAT(a.first_name, ' ', a.last_name, ' onboarding is ', o.onboarding_status),
                    o.orientation_date

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

        $stmt->bindValue(
            1,
            (int)$limit,
            PDO::PARAM_INT
        );

        $stmt->execute();


        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getApplicantsPerJob()
    {
        $sql = "
            SELECT
                jp.title,
                COUNT(a.application_id) AS total_applicants
            FROM job_postings jp
            LEFT JOIN applications a
                ON jp.posting_id = a.posting_id
            GROUP BY jp.posting_id, jp.title
            ORDER BY total_applicants DESC
            LIMIT 5
        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentApplicants(
        string $view,
        int $year,
        int $month,
        string $weekStart,
        int $page = 1,
        int $limit = 3
    )
    {
        $bounds = $this->getApplicantPeriodBounds(
            $view,
            $year,
            $month,
            $weekStart
        );

        $page = max(1, $page);
        $offset = ($page - 1) * $limit;

        $countStmt = $this->db->prepare(
            "SELECT COUNT(*)
             FROM applications
             WHERE applied_at >= :start
             AND applied_at < :end"
        );
        $countStmt->bindValue(':start', $bounds['start']);
        $countStmt->bindValue(':end', $bounds['end']);
        $countStmt->execute();

        $total = (int) $countStmt->fetchColumn();

        $sql = "
            SELECT
                a.applicant_id,
                CONCAT(a.first_name, ' ', a.last_name) AS fullname,
                a.address,
                jp.title AS position,
                app.application_status,
                app.applied_at

            FROM applications app

            INNER JOIN applicants a
                ON app.applicant_id = a.applicant_id

            INNER JOIN job_postings jp
                ON app.posting_id = jp.posting_id

            WHERE app.applied_at >= :start
            AND app.applied_at < :end

            ORDER BY app.applied_at DESC

            LIMIT :limit OFFSET :offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':start', $bounds['start']);
        $stmt->bindValue(':end', $bounds['end']);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return [
            'items' => $stmt->fetchAll(PDO::FETCH_ASSOC),
            'page' => $page,
            'total' => $total,
            'totalPages' => max(1, (int) ceil($total / $limit))
        ];
    }

    private function getApplicantPeriodBounds(
        string $view,
        int $year,
        int $month,
        string $weekStart
    ): array
    {
        if ($view === 'month') {
            $start = new DateTime(sprintf('%04d-%02d-01', $year, $month));
            $end = clone $start;
            $end->modify('+1 month');
        } elseif ($view === 'week') {
            $start = new DateTime($weekStart);
            $start->modify('monday this week');
            $end = clone $start;
            $end->modify('+7 days');
        } else {
            $start = new DateTime(sprintf('%04d-01-01', $year));
            $end = clone $start;
            $end->modify('+1 year');
        }

        return [
            'start' => $start->format('Y-m-d H:i:s'),
            'end' => $end->format('Y-m-d H:i:s')
        ];
    }
}