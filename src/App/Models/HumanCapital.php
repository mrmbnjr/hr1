<?php

namespace App\Models;

use Core\Database;
use PDO;

class HumanCapital
{
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
        $stmt = $this->db->query("

            SELECT

                d.department_id,
                d.department_name,
                d.created_at,

                COUNT(DISTINCT p.position_id) AS position_count,

                COUNT(DISTINCT e.employee_id) AS employee_count,

                COALESCE(
                    SUM(
                        CASE
                            WHEN jp.status = 'Open'
                            THEN jp.vacancies
                            ELSE 0
                        END
                    ),
                    0
                ) AS vacancies

            FROM departments d

            /*
             * Department → Position
             */
            LEFT JOIN positions p
                ON p.department_id = d.department_id

            /*
             * Position → Employee
             *
             * IMPORTANT:
             * Employees belong to positions directly.
             */
            LEFT JOIN employees e
                ON e.position_id = p.position_id

            /*
             * Position → Job Posting
             *
             * Used ONLY for vacancy information.
             */
            LEFT JOIN job_postings jp
                ON jp.position_id = p.position_id

            GROUP BY
                d.department_id,
                d.department_name,
                d.created_at

            ORDER BY
                d.department_name

        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /*
    |--------------------------------------------------------------------------
    | GET SINGLE DEPARTMENT
    |--------------------------------------------------------------------------
    */

    public function getDepartment(int $id): ?array
    {
        $stmt = $this->db->prepare("

            SELECT

                d.department_id,
                d.department_name,
                d.created_at,

                COUNT(DISTINCT p.position_id) AS position_count,

                COUNT(DISTINCT e.employee_id) AS employee_count,

                COALESCE(
                    SUM(
                        CASE
                            WHEN jp.status = 'Open'
                            THEN jp.vacancies
                            ELSE 0
                        END
                    ),
                    0
                ) AS vacancies

            FROM departments d

            /*
             * Department → Position
             */
            LEFT JOIN positions p
                ON p.department_id = d.department_id

            /*
             * Position → Employee
             */
            LEFT JOIN employees e
                ON e.position_id = p.position_id

            /*
             * Position → Job Posting
             */
            LEFT JOIN job_postings jp
                ON jp.position_id = p.position_id

            WHERE d.department_id = ?

            GROUP BY
                d.department_id,
                d.department_name,
                d.created_at

            LIMIT 1

        ");

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
                (department_name)

            VALUES
                (?)

        ");

        return $stmt->execute([
            $data['department_name']
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
                department_name = ?

            WHERE department_id = ?

        ");

        return $stmt->execute([
            $data['department_name'],
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

                d.department_name,

                /*
                 * Employees belong directly to positions.
                 */
                COUNT(DISTINCT e.employee_id) AS employee_count,

                /*
                 * Vacancies come from open job postings.
                 */
                COALESCE(
                    SUM(
                        CASE
                            WHEN jp.status = 'Open'
                            THEN jp.vacancies
                            ELSE 0
                        END
                    ),
                    0
                ) AS vacancies,

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

            /*
             * Position → Job Posting
             */
            LEFT JOIN job_postings jp
                ON jp.position_id = p.position_id

            GROUP BY
                p.position_id,
                p.position_name,
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

}