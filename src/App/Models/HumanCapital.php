<?php

namespace App\Models;

use Core\Database;
use PDO;

class HumanCapital
{
    /**
     * Shared projection for the department list and the single-department
     * lookup. Callers append their own WHERE / GROUP BY / ORDER BY clause.
     */
    private const SELECT_DEPARTMENT = "
        SELECT

            d.department_id,
            d.department_name,
            d.created_at,
            d.status,

            COUNT(DISTINCT p.position_id) AS position_count,

            /*
             * Positions that actually have at least one employee.
             * Always a subset of position_count.
             */
            COUNT(
                DISTINCT CASE
                    WHEN e.employee_id IS NOT NULL
                    THEN p.position_id
                END
            ) AS staffed_position_count,

            COUNT(DISTINCT e.employee_id) AS employee_count,

            /*
             * Open vacancies for this department.
             *
             * Computed with a correlated subquery instead of joining
             * job_postings, so the employee fan-out cannot multiply the
             * vacancy count (a posting with 3 vacancies and 2 employees
             * must still count as 3, not 6).
             */
            COALESCE((
                SELECT SUM(jp.vacancies)
                FROM job_postings jp
                INNER JOIN positions vp
                    ON vp.position_id = jp.position_id
                WHERE vp.department_id = d.department_id
                  AND jp.status = 'Open'
            ), 0) AS vacancies,

            /*
             * A department is considered to have a head when one of its
             * positions is a Manager role (roles.role_code = 'MGR') and is
             * filled by an employee.
             *
             * Correlated subquery: it only references the grouped
             * d.department_id, so it does not need its own GROUP BY entry
             * and cannot multiply the outer row count.
             */
            EXISTS (
                SELECT 1
                FROM positions hp
                INNER JOIN employees he
                    ON he.position_id = hp.position_id
                INNER JOIN roles hr
                    ON hr.role_id = hp.role_id
                WHERE hp.department_id = d.department_id
                  AND hr.role_code = 'MGR'
            ) AS has_head

        FROM departments d

        /* Department -> Position */
        LEFT JOIN positions p
            ON p.department_id = d.department_id

        /* Position -> Employee (employees belong to positions directly) */
        LEFT JOIN employees e
            ON e.position_id = p.position_id
    ";

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }


    /*
    |--------------------------------------------------------------------------
    | OVERVIEW STATISTICS
    |--------------------------------------------------------------------------
    */

    public function getOverviewStats(): array
    {
        return [

            'departments' => (int) $this->db->query("
                SELECT COUNT(*)
                FROM departments
            ")->fetchColumn(),

            'positions' => (int) $this->db->query("
                SELECT COUNT(*)
                FROM positions
            ")->fetchColumn(),

            'vacancies' => (int) $this->db->query("
                SELECT COALESCE(SUM(vacancies), 0)
                FROM job_postings
                WHERE status = 'Open'
            ")->fetchColumn(),

            'hiring_departments' => (int) $this->db->query("
                SELECT COUNT(DISTINCT p.department_id)
                FROM job_postings jp
                INNER JOIN positions p
                    ON p.position_id = jp.position_id
                WHERE jp.status = 'Open'
            ")->fetchColumn(),

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | GET ALL DEPARTMENTS
    |--------------------------------------------------------------------------
    */

    public function getDepartments(): array
    {
        $sql = self::SELECT_DEPARTMENT . "

            GROUP BY
                d.department_id,
                d.department_name,
                d.created_at,
                d.status

            ORDER BY
                d.department_name

        ";

        $stmt = $this->db->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | GET SINGLE DEPARTMENT
    |--------------------------------------------------------------------------
    */

    public function getDepartment(int $id): ?array
    {
        $sql = self::SELECT_DEPARTMENT . "

            WHERE d.department_id = ?

            GROUP BY
                d.department_id,
                d.department_name,
                d.created_at,
                d.status

            LIMIT 1

        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);

        $department = $stmt->fetch(PDO::FETCH_ASSOC);

        return $department ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE DEPARTMENT
    |--------------------------------------------------------------------------
    */

    public function createDepartment(array $data): bool
    {
        $stmt = $this->db->prepare("

            INSERT INTO departments
                (department_name, status)

            VALUES
                (?, ?)

        ");

        return $stmt->execute([
            $data['department_name'],
            $data['status'] ?? 'Active'
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE DEPARTMENT
    |--------------------------------------------------------------------------
    */

    public function updateDepartment(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("

            UPDATE departments

            SET
                department_name = ?,
                status = ?

            WHERE department_id = ?

        ");

        return $stmt->execute([
            $data['department_name'],
            $data['status'] ?? 'Active',
            $id
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE DEPARTMENT
    |--------------------------------------------------------------------------
    */

    public function deleteDepartment(int $id): array
    {
        /*
         * Do not allow deletion when positions
         * still belong to this department.
         */
        $stmt = $this->db->prepare("

            SELECT COUNT(*)
            FROM positions
            WHERE department_id = ?

        ");

        $stmt->execute([$id]);

        if ((int) $stmt->fetchColumn() > 0) {

            return [

                'success' => false,

                'message' => 'This department still has positions.'

            ];
        }


        $stmt = $this->db->prepare("

            DELETE FROM departments

            WHERE department_id = ?

        ");

        $stmt->execute([$id]);

        return [

            'success' => true

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | GET ALL POSITIONS
    |--------------------------------------------------------------------------
    */

    public function getPositions(): array
    {
        $stmt = $this->db->query("

            SELECT

                p.position_id,
                p.position_name,
                p.created_at,

                d.department_name,

                /*
                 * Employees belong directly to positions.
                 */
                COUNT(DISTINCT e.employee_id) AS employee_count,

                /*
                 * Open vacancies come from open job postings.
                 *
                 * Correlated subquery so the employee fan-out cannot
                 * multiply the vacancy count (a posting with 3 vacancies
                 * and 2 employees must still count as 3, not 6).
                 */
                COALESCE((
                    SELECT SUM(jp.vacancies)
                    FROM job_postings jp
                    WHERE jp.position_id = p.position_id
                      AND jp.status = 'Open'
                ), 0) AS vacancies,

                /*
                 * Position is Open when it has
                 * at least one open job posting.
                 */
                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM job_postings active_jp
                        WHERE active_jp.position_id = p.position_id
                          AND active_jp.status = 'Open'
                    )
                    THEN 'Open'
                    ELSE 'Closed'
                END AS status

            FROM positions p

            INNER JOIN departments d
                ON d.department_id = p.department_id

            /*
             * Position → Employee
             */
            LEFT JOIN employees e
                ON e.position_id = p.position_id

            GROUP BY
                p.position_id,
                p.position_name,
                p.created_at,
                d.department_name

            ORDER BY
                d.department_name,
                p.position_name

        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | GET ORGANIZATION TREE
    |--------------------------------------------------------------------------
    */

    public function getOrganizationTree(): array
    {
        $departments = $this->getDepartments();

        foreach ($departments as &$department) {

            /*
             * Get positions belonging to this department.
             */
            $stmt = $this->db->prepare("

                SELECT

                    p.position_id,
                    p.position_name,

                    r.role_id,
                    r.role_code,
                    r.role_name

                FROM positions p

                LEFT JOIN roles r
                    ON r.role_id = p.role_id

                WHERE p.department_id = ?

                ORDER BY p.position_name

            ");

            $stmt->execute([
                $department['department_id']
            ]);

            $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);


            /*
             * Get employees for every position.
             */
            foreach ($positions as &$position) {

                $employeeStmt = $this->db->prepare("

                    SELECT

                        e.employee_id,
                        e.employee_number,
                        e.hire_date,
                        e.employment_status,

                        CONCAT(
                            COALESCE(a.first_name, ''),
                            IF(
                                a.middle_name IS NULL
                                OR a.middle_name = '',
                                '',
                                CONCAT(' ', a.middle_name)
                            ),
                            ' ',
                            COALESCE(a.last_name, '')
                        ) AS employee_name

                    FROM employees e

                    /*
                     * Employee → Application
                     */
                    LEFT JOIN applications ap
                        ON ap.application_id = e.application_id

                    /*
                     * Application → Applicant
                     */
                    LEFT JOIN applicants a
                        ON a.applicant_id = ap.applicant_id

                    /*
                     * IMPORTANT:
                     *
                     * Do NOT join job_postings here.
                     *
                     * The employee already has position_id.
                     */
                    WHERE e.position_id = ?

                    ORDER BY
                        a.last_name,
                        a.first_name

                ");

                $employeeStmt->execute([
                    $position['position_id']
                ]);

                $position['employees'] =
                    $employeeStmt->fetchAll(PDO::FETCH_ASSOC);
            }

            unset($position);

            $department['positions'] = $positions;
        }

        unset($department);

        return $departments;
    }


    /*
    |--------------------------------------------------------------------------
    | DEPARTMENT LOOKUP
    |--------------------------------------------------------------------------
    */

    public function getDepartmentLookup(): array
    {
        return $this->db->query("

            SELECT

                department_id,
                department_name

            FROM departments

            ORDER BY department_name

        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRoles(): array
    {
        $stmt = $this->db->query("
            SELECT
                role_id,
                role_code,
                role_name
            FROM roles
            ORDER BY role_name
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createPosition(array $data): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO positions
            (
                department_id,
                role_id,
                position_name
            )
            VALUES
            (
                :department_id,
                :role_id,
                :position_name
            )
        ");

        return $stmt->execute([
            ':department_id' => $data['department_id'],
            ':role_id' => $data['role_id'],
            ':position_name' => $data['position_name']
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE POSITION
    |--------------------------------------------------------------------------
    */

    public function deletePosition(int $id): array
    {
        /*
        * Do not allow deletion when employees
        * are still assigned to this position.
        */
        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM employees
            WHERE position_id = ?
        ");

        $stmt->execute([$id]);

        if ((int) $stmt->fetchColumn() > 0) {

            return [
                'success' => false,
                'message' => 'This position still has employees assigned.'
            ];
        }

        $stmt = $this->db->prepare("
            DELETE FROM positions
            WHERE position_id = ?
        ");

        $stmt->execute([$id]);

        return [
            'success' => true
        ];
    }


    /*
    |==========================================================================
    | DEPARTMENT DETAILS PAGE  (?page=department-statistics&id=<department_id>)
    |==========================================================================
    |
    | Everything below is scoped to ONE department_id and reads real rows only.
    |
    | How the schema is traversed:
    |   - employees.position_id      -> positions.department_id   (headcount)
    |   - employees.applicant_id     -> applicants                (names)
    |   - users.employee_id          -> employees                 (account status)
    |   - job_postings.position_id   -> positions.department_id   (hiring)
    |   - applications.posting_id    -> job_postings              (applicants)
    |   - interviews.application_id  -> applications              (interviews)
    |   - onboarding.application_id  -> employees.application_id  (onboarding)
    |
    | Counts that could be multiplied by a join fan-out use correlated
    | subqueries, the same approach SELECT_DEPARTMENT already takes.
    |
    */

    /**
     * Onboarding rows that belong to a department.
     *
     * The department is resolved from the employee's position when the
     * employee record exists, and from the job posting's position otherwise.
     * Callers append WHERE / ORDER BY. Aliases: o, ap, a, jp, e, p.
     */
    private const ONBOARDING_FROM = "
        FROM onboarding o

        INNER JOIN applications ap
            ON ap.application_id = o.application_id

        INNER JOIN applicants a
            ON a.applicant_id = ap.applicant_id

        INNER JOIN job_postings jp
            ON jp.posting_id = ap.posting_id

        LEFT JOIN employees e
            ON e.application_id = o.application_id

        INNER JOIN positions p
            ON p.position_id = COALESCE(e.position_id, jp.position_id)
    ";

    /**
     * SQL expression for "First Middle Last" from an applicants alias.
     */
    private function personName(string $alias = 'a'): string
    {
        return "TRIM(CONCAT(
            COALESCE({$alias}.first_name, ''),
            IF({$alias}.middle_name IS NULL OR {$alias}.middle_name = '',
               '',
               CONCAT(' ', {$alias}.middle_name)),
            ' ',
            COALESCE({$alias}.last_name, '')
        ))";
    }


    /*
    |--------------------------------------------------------------------------
    | DEPARTMENT HEADER  ($department)
    |--------------------------------------------------------------------------
    |
    | SELECT d.* on purpose: if the departments table has department_code /
    | description columns they come through automatically, and the view only
    | shows them when present.
    |
    | Head = an employee filling a position whose role_code is 'MGR' (the same
    | rule SELECT_DEPARTMENT uses for has_head).
    |
    */

    public function getDepartmentDetails(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT d.*
            FROM departments d
            WHERE d.department_id = ?
            LIMIT 1
        ");

        $stmt->execute([$id]);

        $department = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$department) {
            return null;
        }

        $stmt = $this->db->prepare("
            SELECT
                e.employee_id,
                e.employee_number,
                p.position_name,
                " . $this->personName('a') . " AS employee_name
            FROM employees e
            INNER JOIN positions p
                ON p.position_id = e.position_id
            INNER JOIN roles r
                ON r.role_id = p.role_id
            LEFT JOIN applicants a
                ON a.applicant_id = e.applicant_id
            WHERE p.department_id = ?
              AND r.role_code = 'MGR'
            ORDER BY e.hire_date ASC, e.employee_id ASC
        ");

        $stmt->execute([$id]);

        $heads = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $department['heads']    = $heads;
        $department['head']     = $heads[0] ?? null;
        $department['has_head'] = !empty($heads);

        return $department;
    }


    /*
    |--------------------------------------------------------------------------
    | KPI + WORKFORCE FIGURES  ($departmentStats)
    |--------------------------------------------------------------------------
    |
    | employees.employment_status is the only employment-type field on an
    | employee (Probationary / Regular / Contract). The employees table has no
    | active/inactive column, so "active vs inactive" comes from the linked
    | users.status; employees with no user account are counted separately.
    |
    */

    public function getDepartmentStats(int $id): array
    {
        $row = function (string $sql) use ($id): array {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        };

        $emp = $row("
            SELECT
                COUNT(*)                                              AS total,
                COALESCE(SUM(u.status = 'Active'), 0)                 AS active_accounts,
                COALESCE(SUM(u.status = 'Inactive'), 0)               AS inactive_accounts,
                COALESCE(SUM(u.user_id IS NULL), 0)                   AS no_account,
                COALESCE(SUM(e.employment_status = 'Regular'), 0)     AS regular,
                COALESCE(SUM(e.employment_status = 'Probationary'), 0) AS probationary,
                COALESCE(SUM(e.employment_status = 'Contract'), 0)    AS contract,
                COALESCE(SUM(e.hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)), 0) AS new_hires_30d
            FROM employees e
            INNER JOIN positions p
                ON p.position_id = e.position_id
            LEFT JOIN users u
                ON u.employee_id = e.employee_id
            WHERE p.department_id = ?
        ");

        $pos = $row("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(EXISTS (
                    SELECT 1 FROM employees se
                    WHERE se.position_id = p.position_id
                )), 0) AS staffed,
                COALESCE(SUM(EXISTS (
                    SELECT 1 FROM job_postings sj
                    WHERE sj.position_id = p.position_id
                      AND sj.status = 'Open'
                )), 0) AS hiring
            FROM positions p
            WHERE p.department_id = ?
        ");

        $hire = $row("
            SELECT
                COUNT(*)                          AS open_postings,
                COALESCE(SUM(jp.vacancies), 0)    AS vacancies
            FROM job_postings jp
            INNER JOIN positions p
                ON p.position_id = jp.position_id
            WHERE p.department_id = ?
              AND jp.status = 'Open'
        ");

        $onb = $row("
            SELECT
                COUNT(*)                                         AS active,
                COALESCE(SUM(o.onboarding_status = 'Overdue'), 0) AS overdue
            " . self::ONBOARDING_FROM . "
            WHERE p.department_id = ?
              AND o.onboarding_status <> 'Completed'
        ");

        $total        = (int) ($emp['total'] ?? 0);
        $regular      = (int) ($emp['regular'] ?? 0);
        $probationary = (int) ($emp['probationary'] ?? 0);
        $contract     = (int) ($emp['contract'] ?? 0);

        $posTotal  = (int) ($pos['total'] ?? 0);
        $posStaffed = (int) ($pos['staffed'] ?? 0);

        return [

            'employees' => [
                'total'         => $total,
                'active'        => (int) ($emp['active_accounts'] ?? 0),
                'inactive'      => (int) ($emp['inactive_accounts'] ?? 0),
                'no_account'    => (int) ($emp['no_account'] ?? 0),
                'regular'       => $regular,
                'probationary'  => $probationary,
                'contract'      => $contract,
                'other'         => max(0, $total - $regular - $probationary - $contract),
                'new_hires_30d' => (int) ($emp['new_hires_30d'] ?? 0),
            ],

            'positions' => [
                'total'    => $posTotal,
                'staffed'  => $posStaffed,
                'unfilled' => max(0, $posTotal - $posStaffed),
                'hiring'   => (int) ($pos['hiring'] ?? 0),
            ],

            'vacancies'     => (int) ($hire['vacancies'] ?? 0),
            'open_postings' => (int) ($hire['open_postings'] ?? 0),

            'onboarding' => [
                'active'  => (int) ($onb['active'] ?? 0),
                'overdue' => (int) ($onb['overdue'] ?? 0),
            ],

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | POSITIONS IN THE DEPARTMENT  ($positions)
    |--------------------------------------------------------------------------
    */

    public function getDepartmentPositions(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.position_id,
                p.position_name,
                p.created_at,

                r.role_name,
                r.role_code,

                (
                    SELECT COUNT(*)
                    FROM employees e
                    WHERE e.position_id = p.position_id
                ) AS employee_count,

                COALESCE((
                    SELECT SUM(jp.vacancies)
                    FROM job_postings jp
                    WHERE jp.position_id = p.position_id
                      AND jp.status = 'Open'
                ), 0) AS vacancies,

                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM job_postings ojp
                        WHERE ojp.position_id = p.position_id
                          AND ojp.status = 'Open'
                    )
                    THEN 'Open'
                    ELSE 'Closed'
                END AS status

            FROM positions p

            LEFT JOIN roles r
                ON r.role_id = p.role_id

            WHERE p.department_id = ?

            ORDER BY
                (r.role_code = 'MGR') DESC,
                p.position_name
        ");

        $stmt->execute([$id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEES IN THE DEPARTMENT  ($employees)
    |--------------------------------------------------------------------------
    */

    public function getDepartmentEmployees(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.employee_id,
                e.employee_number,
                e.hire_date,
                e.employment_status,

                p.position_id,
                p.position_name,

                r.role_name,
                (r.role_code = 'MGR') AS is_head,

                u.status AS account_status,

                " . $this->personName('a') . " AS employee_name

            FROM employees e

            INNER JOIN positions p
                ON p.position_id = e.position_id

            LEFT JOIN roles r
                ON r.role_id = p.role_id

            LEFT JOIN applicants a
                ON a.applicant_id = e.applicant_id

            LEFT JOIN users u
                ON u.employee_id = e.employee_id

            WHERE p.department_id = ?

            ORDER BY
                a.last_name,
                a.first_name
        ");

        $stmt->execute([$id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | HIRING & STAFFING  ($recruitment)
    |--------------------------------------------------------------------------
    |
    | Returns:
    |   summary    — roll-up across the department's OPEN postings
    |   postings   — open job postings with applicant / interview counts
    |   interviews — interviews still waiting for a result (soonest first)
    |
    */

    public function getDepartmentRecruitment(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT
                jp.posting_id,
                jp.title,
                jp.employment_type,
                jp.vacancies,
                jp.max_applicants,
                jp.application_deadline,

                p.position_name,

                (
                    SELECT COUNT(*)
                    FROM applications ap
                    WHERE ap.posting_id = jp.posting_id
                ) AS applicants,

                (
                    SELECT COUNT(*)
                    FROM applications ap
                    WHERE ap.posting_id = jp.posting_id
                      AND ap.application_status IN ('Submitted', 'Under Review', 'Interview')
                ) AS active_applicants,

                (
                    SELECT COUNT(*)
                    FROM applications ap
                    WHERE ap.posting_id = jp.posting_id
                      AND ap.application_status = 'Hired'
                ) AS hired,

                (
                    SELECT COUNT(*)
                    FROM interviews i
                    INNER JOIN applications ap
                        ON ap.application_id = i.application_id
                    WHERE ap.posting_id = jp.posting_id
                      AND i.result = 'Pending'
                      AND i.interview_date >= NOW()
                ) AS upcoming_interviews

            FROM job_postings jp

            INNER JOIN positions p
                ON p.position_id = jp.position_id

            WHERE p.department_id = ?
              AND jp.status = 'Open'

            ORDER BY
                jp.application_deadline ASC,
                jp.created_at DESC
        ");

        $stmt->execute([$id]);

        $postings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary = [
            'open_postings'       => count($postings),
            'vacancies'           => 0,
            'active_applicants'   => 0,
            'upcoming_interviews' => 0,
            'hired'               => 0,
        ];

        foreach ($postings as $posting) {
            $summary['vacancies']           += (int) $posting['vacancies'];
            $summary['active_applicants']   += (int) $posting['active_applicants'];
            $summary['upcoming_interviews'] += (int) $posting['upcoming_interviews'];
            $summary['hired']               += (int) $posting['hired'];
        }

        $stmt = $this->db->prepare("
            SELECT
                i.interview_id,
                i.interview_type,
                i.interview_date,
                i.location,
                i.result,

                jp.title AS posting_title,
                u.username AS interviewer,

                " . $this->personName('a') . " AS applicant_name

            FROM interviews i

            INNER JOIN applications ap
                ON ap.application_id = i.application_id

            INNER JOIN applicants a
                ON a.applicant_id = ap.applicant_id

            INNER JOIN job_postings jp
                ON jp.posting_id = ap.posting_id

            INNER JOIN positions p
                ON p.position_id = jp.position_id

            LEFT JOIN users u
                ON u.user_id = i.interviewer_id

            WHERE p.department_id = ?
              AND i.result = 'Pending'

            ORDER BY i.interview_date ASC

            LIMIT 5
        ");

        $stmt->execute([$id]);

        return [
            'summary'    => $summary,
            'postings'   => $postings,
            'interviews' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | ONBOARDING IN THE DEPARTMENT  ($onboarding)
    |--------------------------------------------------------------------------
    |
    | Employees whose onboarding is not Completed. Progress is the share of the
    | four onboarding checklist flags that are done.
    |
    */

    public function getDepartmentOnboarding(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT
                o.onboarding_id,
                o.onboarding_status,
                o.orientation_date,
                o.started_at,

                o.system_access_setup_completed,
                o.orientation_setup_completed,
                o.document_submission_completed,
                o.document_verification_completed,

                e.employee_id,
                e.employee_number,
                e.hire_date,

                p.position_name,

                " . $this->personName('a') . " AS employee_name,

                (
                    SELECT COUNT(*)
                    FROM onboarding_documents od
                    WHERE od.onboarding_id = o.onboarding_id
                ) AS docs_total,

                (
                    SELECT COUNT(*)
                    FROM onboarding_documents od
                    WHERE od.onboarding_id = o.onboarding_id
                      AND od.status = 'Approved'
                ) AS docs_accepted

            " . self::ONBOARDING_FROM . "

            WHERE p.department_id = ?
              AND o.onboarding_status <> 'Completed'

            ORDER BY
                FIELD(o.onboarding_status, 'Overdue', 'In Progress', 'Pending'),
                o.started_at ASC,
                o.onboarding_id ASC
        ");

        $stmt->execute([$id]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $steps = [
            'system_access_setup_completed'   => 'System access setup',
            'orientation_setup_completed'     => 'Orientation setup',
            'document_submission_completed'   => 'Document submission',
            'document_verification_completed' => 'Document verification',
        ];

        foreach ($rows as &$row) {

            $done     = 0;
            $nextStep = null;

            foreach ($steps as $column => $label) {
                if (!empty($row[$column])) {
                    $done++;
                } elseif ($nextStep === null) {
                    $nextStep = $label;
                }
            }

            $row['steps_done']  = $done;
            $row['steps_total'] = count($steps);
            $row['progress']    = (int) round($done / count($steps) * 100);
            $row['next_step']   = $nextStep;
        }

        unset($row);

        return $rows;
    }


    /*
    |--------------------------------------------------------------------------
    | RECENT DEPARTMENT ACTIVITY  ($recentActivity)
    |--------------------------------------------------------------------------
    |
    | Built from real department events, newest first:
    | hires, positions created, postings opened / closed, applications
    | received, interviews scheduled and onboarding completed.
    |
    */

    public function getDepartmentActivity(int $id, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $name  = $this->personName('a');

        $sql = "
            SELECT event_at, event_type, description
            FROM (

                (
                    SELECT
                        CAST(e.hire_date AS DATETIME) AS event_at,
                        'hire' AS event_type,
                        CONCAT($name, ' joined as ', p.position_name) AS description
                    FROM employees e
                    INNER JOIN positions p
                        ON p.position_id = e.position_id
                    LEFT JOIN applicants a
                        ON a.applicant_id = e.applicant_id
                    WHERE p.department_id = ?
                    ORDER BY e.hire_date DESC
                    LIMIT $limit
                )

                UNION ALL

                (
                    SELECT
                        p.created_at,
                        'position',
                        CONCAT('Position created: ', p.position_name)
                    FROM positions p
                    WHERE p.department_id = ?
                    ORDER BY p.created_at DESC
                    LIMIT $limit
                )

                UNION ALL

                (
                    SELECT
                        jp.created_at,
                        'posting',
                        CONCAT('Job posting opened: ', jp.title)
                    FROM job_postings jp
                    INNER JOIN positions p
                        ON p.position_id = jp.position_id
                    WHERE p.department_id = ?
                    ORDER BY jp.created_at DESC
                    LIMIT $limit
                )

                UNION ALL

                (
                    SELECT
                        jp.closed_at,
                        'posting_closed',
                        CONCAT('Job posting closed: ', jp.title)
                    FROM job_postings jp
                    INNER JOIN positions p
                        ON p.position_id = jp.position_id
                    WHERE p.department_id = ?
                      AND jp.closed_at IS NOT NULL
                    ORDER BY jp.closed_at DESC
                    LIMIT $limit
                )

                UNION ALL

                (
                    SELECT
                        ap.applied_at,
                        'application',
                        CONCAT($name, ' applied for ', jp.title)
                    FROM applications ap
                    INNER JOIN applicants a
                        ON a.applicant_id = ap.applicant_id
                    INNER JOIN job_postings jp
                        ON jp.posting_id = ap.posting_id
                    INNER JOIN positions p
                        ON p.position_id = jp.position_id
                    WHERE p.department_id = ?
                    ORDER BY ap.applied_at DESC
                    LIMIT $limit
                )

                UNION ALL

                (
                    SELECT
                        i.created_at,
                        'interview',
                        CONCAT('Interview scheduled for ', $name, ' (', jp.title, ')')
                    FROM interviews i
                    INNER JOIN applications ap
                        ON ap.application_id = i.application_id
                    INNER JOIN applicants a
                        ON a.applicant_id = ap.applicant_id
                    INNER JOIN job_postings jp
                        ON jp.posting_id = ap.posting_id
                    INNER JOIN positions p
                        ON p.position_id = jp.position_id
                    WHERE p.department_id = ?
                    ORDER BY i.created_at DESC
                    LIMIT $limit
                )

                UNION ALL

                (
                    SELECT
                        CAST(o.completed_at AS DATETIME),
                        'onboarding',
                        CONCAT('Onboarding completed: ', $name)
                    " . self::ONBOARDING_FROM . "
                    WHERE p.department_id = ?
                      AND o.completed_at IS NOT NULL
                    ORDER BY o.completed_at DESC
                    LIMIT $limit
                )

            ) AS dept_events

            WHERE event_at IS NOT NULL

            ORDER BY event_at DESC

            LIMIT $limit
        ";

        $stmt = $this->db->prepare($sql);

        // One department_id placeholder per UNION branch (7).
        $stmt->execute(array_fill(0, 7, $id));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |==========================================================================
    | POSITION DETAILS PAGE  (?page=position-statistics&id=<position_id>)
    |==========================================================================
    |
    | Everything below is scoped to ONE position_id and reads real rows only.
    |
    | How the schema is traversed:
    |   - positions.department_id    -> departments                (header)
    |   - positions.role_id          -> roles                      (assigned role)
    |   - employees.position_id      -> employees                  (headcount)
    |   - employees.applicant_id     -> applicants                 (names)
    |   - users.employee_id          -> employees                  (account status)
    |   - job_postings.position_id   -> positions                  (hiring)
    |   - applications.posting_id    -> job_postings               (applicants)
    |   - interviews.application_id  -> applications               (interviews)
    |   - onboarding.application_id  -> employees.application_id   (onboarding)
    |
    | Counts that could be multiplied by a join fan-out use correlated
    | subqueries, the same approach the department section takes.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | POSITION HEADER  ($position)
    |--------------------------------------------------------------------------
    |
    | The positions table has no status or employment_type column, so both are
    | derived from the position's real job postings: status is Open when an
    | open posting exists, and employment_type is taken from the most relevant
    | posting (an open one first, then the newest).
    |
    */

    public function getPositionDetails(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                p.position_id,
                p.position_name,
                p.created_at,

                d.department_id,
                d.department_name,
                d.status AS department_status,

                r.role_id,
                r.role_code,
                r.role_name,

                (
                    SELECT COUNT(*)
                    FROM employees e
                    WHERE e.position_id = p.position_id
                ) AS employee_count,

                COALESCE((
                    SELECT SUM(jp.vacancies)
                    FROM job_postings jp
                    WHERE jp.position_id = p.position_id
                      AND jp.status = 'Open'
                ), 0) AS vacancies,

                (
                    SELECT COUNT(*)
                    FROM job_postings jp
                    WHERE jp.position_id = p.position_id
                      AND jp.status = 'Open'
                ) AS open_postings,

                (
                    SELECT COUNT(*)
                    FROM job_postings jp
                    WHERE jp.position_id = p.position_id
                ) AS total_postings,

                CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM job_postings ojp
                        WHERE ojp.position_id = p.position_id
                          AND ojp.status = 'Open'
                    )
                    THEN 'Open'
                    ELSE 'Closed'
                END AS status,

                (
                    SELECT jp.employment_type
                    FROM job_postings jp
                    WHERE jp.position_id = p.position_id
                    ORDER BY (jp.status = 'Open') DESC, jp.created_at DESC
                    LIMIT 1
                ) AS employment_type,

                (
                    SELECT jp.created_at
                    FROM job_postings jp
                    WHERE jp.position_id = p.position_id
                    ORDER BY jp.created_at DESC
                    LIMIT 1
                ) AS last_posting_at

            FROM positions p

            INNER JOIN departments d
                ON d.department_id = p.department_id

            LEFT JOIN roles r
                ON r.role_id = p.role_id

            WHERE p.position_id = ?

            LIMIT 1
        ");

        $stmt->execute([$id]);

        $position = $stmt->fetch(PDO::FETCH_ASSOC);

        return $position ?: null;
    }


    /*
    |--------------------------------------------------------------------------
    | KPI FIGURES  ($positionStats)
    |--------------------------------------------------------------------------
    |
    | employees.employment_status is the only employment-type field on an
    | employee (Probationary / Regular / Contract). The employees table has no
    | active/inactive column, so "active vs inactive" comes from the linked
    | users.status; employees with no user account are counted separately.
    |
    | Applicant figures count every application submitted to this position's
    | postings; vacancies count only the OPEN postings.
    |
    */

    public function getPositionStats(int $id): array
    {
        $row = function (string $sql) use ($id): array {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        };

        $emp = $row("
            SELECT
                COUNT(*)                                               AS total,
                COALESCE(SUM(u.status = 'Active'), 0)                  AS active_accounts,
                COALESCE(SUM(u.status = 'Inactive'), 0)                AS inactive_accounts,
                COALESCE(SUM(u.user_id IS NULL), 0)                    AS no_account,
                COALESCE(SUM(e.employment_status = 'Regular'), 0)      AS regular,
                COALESCE(SUM(e.employment_status = 'Probationary'), 0) AS probationary,
                COALESCE(SUM(e.employment_status = 'Contract'), 0)     AS contract,
                COALESCE(SUM(e.hire_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)), 0) AS new_hires_30d
            FROM employees e
            LEFT JOIN users u
                ON u.employee_id = e.employee_id
            WHERE e.position_id = ?
        ");

        $hire = $row("
            SELECT
                COUNT(*)                       AS open_postings,
                COALESCE(SUM(jp.vacancies), 0) AS vacancies
            FROM job_postings jp
            WHERE jp.position_id = ?
              AND jp.status = 'Open'
        ");

        $apps = $row("
            SELECT
                COUNT(*) AS total,
                COALESCE(SUM(ap.application_status IN ('Submitted', 'Under Review', 'Interview')), 0) AS active,
                COALESCE(SUM(ap.application_status = 'Hired'), 0) AS hired
            FROM applications ap
            INNER JOIN job_postings jp
                ON jp.posting_id = ap.posting_id
            WHERE jp.position_id = ?
        ");

        $iv = $row("
            SELECT
                COUNT(*) AS upcoming
            FROM interviews i
            INNER JOIN applications ap
                ON ap.application_id = i.application_id
            INNER JOIN job_postings jp
                ON jp.posting_id = ap.posting_id
            WHERE jp.position_id = ?
              AND i.result = 'Pending'
              AND i.interview_date >= NOW()
        ");

        $onb = $row("
            SELECT
                COUNT(*)                                         AS active,
                COALESCE(SUM(o.onboarding_status = 'Overdue'), 0) AS overdue
            " . self::ONBOARDING_FROM . "
            WHERE p.position_id = ?
              AND o.onboarding_status <> 'Completed'
        ");

        $total        = (int) ($emp['total'] ?? 0);
        $regular      = (int) ($emp['regular'] ?? 0);
        $probationary = (int) ($emp['probationary'] ?? 0);
        $contract     = (int) ($emp['contract'] ?? 0);

        return [

            'employees' => [
                'total'         => $total,
                'active'        => (int) ($emp['active_accounts'] ?? 0),
                'inactive'      => (int) ($emp['inactive_accounts'] ?? 0),
                'no_account'    => (int) ($emp['no_account'] ?? 0),
                'regular'       => $regular,
                'probationary'  => $probationary,
                'contract'      => $contract,
                'other'         => max(0, $total - $regular - $probationary - $contract),
                'new_hires_30d' => (int) ($emp['new_hires_30d'] ?? 0),
            ],

            'open_postings'       => (int) ($hire['open_postings'] ?? 0),
            'vacancies'           => (int) ($hire['vacancies'] ?? 0),

            'applicants'          => (int) ($apps['total'] ?? 0),
            'active_applicants'   => (int) ($apps['active'] ?? 0),
            'hired'               => (int) ($apps['hired'] ?? 0),

            'upcoming_interviews' => (int) ($iv['upcoming'] ?? 0),

            'onboarding' => [
                'active'  => (int) ($onb['active'] ?? 0),
                'overdue' => (int) ($onb['overdue'] ?? 0),
            ],

        ];
    }


    /*
    |--------------------------------------------------------------------------
    | EMPLOYEES IN THE POSITION  ($employees)
    |--------------------------------------------------------------------------
    */

    public function getPositionEmployees(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT
                e.employee_id,
                e.employee_number,
                e.hire_date,
                e.employment_status,

                p.position_name,

                r.role_name,
                (r.role_code = 'MGR') AS is_head,

                u.status AS account_status,

                " . $this->personName('a') . " AS employee_name

            FROM employees e

            INNER JOIN positions p
                ON p.position_id = e.position_id

            LEFT JOIN roles r
                ON r.role_id = p.role_id

            LEFT JOIN applicants a
                ON a.applicant_id = e.applicant_id

            LEFT JOIN users u
                ON u.employee_id = e.employee_id

            WHERE e.position_id = ?

            ORDER BY
                a.last_name,
                a.first_name
        ");

        $stmt->execute([$id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | RECRUITMENT  ($recruitment)
    |--------------------------------------------------------------------------
    |
    | Returns:
    |   summary    — roll-up across the position's postings
    |   postings   — every posting for the position (open first)
    |   interviews — interviews still waiting for a result (soonest first)
    |
    */

    public function getPositionRecruitment(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT
                jp.posting_id,
                jp.title,
                jp.employment_type,
                jp.vacancies,
                jp.max_applicants,
                jp.application_deadline,
                jp.status,
                jp.created_at,

                p.position_name,

                (
                    SELECT COUNT(*)
                    FROM applications ap
                    WHERE ap.posting_id = jp.posting_id
                ) AS applicants,

                (
                    SELECT COUNT(*)
                    FROM applications ap
                    WHERE ap.posting_id = jp.posting_id
                      AND ap.application_status IN ('Submitted', 'Under Review', 'Interview')
                ) AS active_applicants,

                (
                    SELECT COUNT(*)
                    FROM applications ap
                    WHERE ap.posting_id = jp.posting_id
                      AND ap.application_status = 'Hired'
                ) AS hired,

                (
                    SELECT COUNT(*)
                    FROM interviews i
                    INNER JOIN applications ap
                        ON ap.application_id = i.application_id
                    WHERE ap.posting_id = jp.posting_id
                      AND i.result = 'Pending'
                      AND i.interview_date >= NOW()
                ) AS upcoming_interviews

            FROM job_postings jp

            INNER JOIN positions p
                ON p.position_id = jp.position_id

            WHERE jp.position_id = ?

            ORDER BY
                (jp.status = 'Open') DESC,
                jp.application_deadline ASC,
                jp.created_at DESC
        ");

        $stmt->execute([$id]);

        $postings = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $summary = [
            'total_postings'      => count($postings),
            'open_postings'       => 0,
            'vacancies'           => 0,
            'applicants'          => 0,
            'active_applicants'   => 0,
            'upcoming_interviews' => 0,
            'hired'               => 0,
        ];

        foreach ($postings as $posting) {

            if (($posting['status'] ?? '') === 'Open') {
                $summary['open_postings']++;
                $summary['vacancies'] += (int) $posting['vacancies'];
            }

            $summary['applicants']          += (int) $posting['applicants'];
            $summary['active_applicants']   += (int) $posting['active_applicants'];
            $summary['upcoming_interviews'] += (int) $posting['upcoming_interviews'];
            $summary['hired']               += (int) $posting['hired'];
        }

        $stmt = $this->db->prepare("
            SELECT
                i.interview_id,
                i.interview_type,
                i.interview_date,
                i.location,
                i.result,

                jp.title AS posting_title,
                u.username AS interviewer,

                " . $this->personName('a') . " AS applicant_name

            FROM interviews i

            INNER JOIN applications ap
                ON ap.application_id = i.application_id

            INNER JOIN applicants a
                ON a.applicant_id = ap.applicant_id

            INNER JOIN job_postings jp
                ON jp.posting_id = ap.posting_id

            LEFT JOIN users u
                ON u.user_id = i.interviewer_id

            WHERE jp.position_id = ?
              AND i.result = 'Pending'

            ORDER BY i.interview_date ASC

            LIMIT 5
        ");

        $stmt->execute([$id]);

        return [
            'summary'    => $summary,
            'postings'   => $postings,
            'interviews' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | ONBOARDING FOR THE POSITION  ($onboarding)
    |--------------------------------------------------------------------------
    |
    | Employees whose onboarding is not Completed. Progress is the share of the
    | four onboarding checklist flags that are done.
    |
    */

    public function getPositionOnboarding(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT
                o.onboarding_id,
                o.onboarding_status,
                o.orientation_date,
                o.started_at,

                o.system_access_setup_completed,
                o.orientation_setup_completed,
                o.document_submission_completed,
                o.document_verification_completed,

                e.employee_id,
                e.employee_number,
                e.hire_date,

                p.position_name,

                " . $this->personName('a') . " AS employee_name,

                (
                    SELECT COUNT(*)
                    FROM onboarding_documents od
                    WHERE od.onboarding_id = o.onboarding_id
                ) AS docs_total,

                (
                    SELECT COUNT(*)
                    FROM onboarding_documents od
                    WHERE od.onboarding_id = o.onboarding_id
                      AND od.status = 'Approved'
                ) AS docs_accepted

            " . self::ONBOARDING_FROM . "

            WHERE p.position_id = ?
              AND o.onboarding_status <> 'Completed'

            ORDER BY
                FIELD(o.onboarding_status, 'Overdue', 'In Progress', 'Pending'),
                o.started_at ASC,
                o.onboarding_id ASC
        ");

        $stmt->execute([$id]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $steps = [
            'system_access_setup_completed'   => 'System access setup',
            'orientation_setup_completed'     => 'Orientation setup',
            'document_submission_completed'   => 'Document submission',
            'document_verification_completed' => 'Document verification',
        ];

        foreach ($rows as &$row) {

            $done     = 0;
            $nextStep = null;

            foreach ($steps as $column => $label) {
                if (!empty($row[$column])) {
                    $done++;
                } elseif ($nextStep === null) {
                    $nextStep = $label;
                }
            }

            $row['steps_done']  = $done;
            $row['steps_total'] = count($steps);
            $row['progress']    = (int) round($done / count($steps) * 100);
            $row['next_step']   = $nextStep;
        }

        unset($row);

        return $rows;
    }


    /*
    |--------------------------------------------------------------------------
    | RECENT POSITION ACTIVITY  ($recentActivity)
    |--------------------------------------------------------------------------
    |
    | Built from real events tied to this position, newest first: hires,
    | the position being created, postings opened / closed, applications
    | received, interviews scheduled and onboarding completed.
    |
    */

    public function getPositionActivity(int $id, int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        $name  = $this->personName('a');

        $sql = "
            SELECT event_at, event_type, description
            FROM (

                (
                    SELECT
                        CAST(e.hire_date AS DATETIME) AS event_at,
                        'hire' AS event_type,
                        CONCAT($name, ' joined as ', p.position_name) AS description
                    FROM employees e
                    INNER JOIN positions p
                        ON p.position_id = e.position_id
                    LEFT JOIN applicants a
                        ON a.applicant_id = e.applicant_id
                    WHERE e.position_id = ?
                    ORDER BY e.hire_date DESC
                    LIMIT $limit
                )

                UNION ALL

                (
                    SELECT
                        p.created_at,
                        'position',
                        CONCAT('Position created: ', p.position_name)
                    FROM positions p
                    WHERE p.position_id = ?
                    ORDER BY p.created_at DESC
                    LIMIT $limit
                )

                UNION ALL

                (
                    SELECT
                        jp.created_at,
                        'posting',
                        CONCAT('Job posting opened: ', jp.title)
                    FROM job_postings jp
                    WHERE jp.position_id = ?
                    ORDER BY jp.created_at DESC
                    LIMIT $limit
                )

                UNION ALL

                (
                    SELECT
                        jp.closed_at,
                        'posting_closed',
                        CONCAT('Job posting closed: ', jp.title)
                    FROM job_postings jp
                    WHERE jp.position_id = ?
                      AND jp.closed_at IS NOT NULL
                    ORDER BY jp.closed_at DESC
                    LIMIT $limit
                )

                UNION ALL

                (
                    SELECT
                        ap.applied_at,
                        'application',
                        CONCAT($name, ' applied for ', jp.title)
                    FROM applications ap
                    INNER JOIN applicants a
                        ON a.applicant_id = ap.applicant_id
                    INNER JOIN job_postings jp
                        ON jp.posting_id = ap.posting_id
                    WHERE jp.position_id = ?
                    ORDER BY ap.applied_at DESC
                    LIMIT $limit
                )

                UNION ALL

                (
                    SELECT
                        i.created_at,
                        'interview',
                        CONCAT('Interview scheduled for ', $name, ' (', jp.title, ')')
                    FROM interviews i
                    INNER JOIN applications ap
                        ON ap.application_id = i.application_id
                    INNER JOIN applicants a
                        ON a.applicant_id = ap.applicant_id
                    INNER JOIN job_postings jp
                        ON jp.posting_id = ap.posting_id
                    WHERE jp.position_id = ?
                    ORDER BY i.created_at DESC
                    LIMIT $limit
                )

                UNION ALL

                (
                    SELECT
                        CAST(o.completed_at AS DATETIME),
                        'onboarding',
                        CONCAT('Onboarding completed: ', $name)
                    " . self::ONBOARDING_FROM . "
                    WHERE p.position_id = ?
                      AND o.completed_at IS NOT NULL
                    ORDER BY o.completed_at DESC
                    LIMIT $limit
                )

            ) AS position_events

            WHERE event_at IS NOT NULL

            ORDER BY event_at DESC

            LIMIT $limit
        ";

        $stmt = $this->db->prepare($sql);

        // One position_id placeholder per UNION branch (7).
        $stmt->execute(array_fill(0, 7, $id));

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE POSITION (Edit Position modal)
    |--------------------------------------------------------------------------
    */

    public function updatePosition(int $id, array $data): bool
    {
        $stmt = $this->db->prepare("
            UPDATE positions
            SET
                department_id = ?,
                role_id = ?,
                position_name = ?
            WHERE position_id = ?
        ");

        return $stmt->execute([
            $data['department_id'],
            $data['role_id'],
            $data['position_name'],
            $id
        ]);
    }

}