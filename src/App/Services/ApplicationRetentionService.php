<?php

namespace App\Services;

use Core\Database;
use PDO;

/**
 * Application data-retention policy for job postings.
 *
 * Two rules, both scoped to a *closed* job posting:
 *
 *   Rule A — every rejected application belonging to a closed posting is
 *            removed as soon as the posting is closed.
 *   Rule B — the remaining applications on a closed posting are removed once
 *            the posting has been closed for the configured retention window
 *            (default 30 days, measured from `job_postings.closed_at`).
 *
 * Hired applications are never removed. They back the `employees` row and the
 * hired applicant's user account, so deleting them would break those records.
 *
 * Removal is thorough: the application row (and its cascading AI screening,
 * validation and academic-document rows), interviews, any onboarding shell, the
 * uploaded resume / cover letter / certificate files, and finally the applicant
 * profile itself when it has no other application and is not an employee.
 */
class ApplicationRetentionService
{
    private PDO $db;

    /** Absolute path to the folder holding uploaded application files. */
    private string $uploadDirectory;

    /** Retention window (days) applied to postings after they close. */
    private int $retentionDays;

    public function __construct()
    {
        $this->db = Database::connection();

        $this->uploadDirectory =
            dirname(__DIR__, 3) . '/storage/uploads/applications';

        $this->retentionDays = max(
            1,
            (int) config('app.closed_application_retention_days', 30)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Entry points
    |--------------------------------------------------------------------------
    */

    /**
     * Enforce the full policy now (both rules).
     *
     * @return array{rejected:int, expired:int, files:int, applicants:int}
     */
    public function run(): array
    {
        // Rule A: rejected applications on any closed posting.
        $rejected = $this->purgeApplications(
            $this->rejectedOnClosedPostingsSql()
        );

        // Rule B: everything else on postings closed for >= the window.
        $expired = $this->purgeApplications(
            $this->expiredOnClosedPostingsSql()
        );

        return [
            'rejected'   => $rejected['applications'],
            'expired'    => $expired['applications'],
            'files'      => $rejected['files'] + $expired['files'],
            'applicants' => $rejected['applicants'] + $expired['applicants'],
        ];
    }


    /**
     * Throttled entry point for opportunistic runs on page loads. At most one
     * run per `application_cleanup_interval` seconds (default 1 hour).
     *
     * @return array<string, mixed>
     */
    public function runIfDue(?int $intervalSeconds = null): array
    {
        $intervalSeconds = $intervalSeconds
            ?? (int) config('app.application_cleanup_interval', 3600);

        $intervalSeconds = max(0, $intervalSeconds);

        $stampFile = STORAGE_PATH . '/retention.last-run';

        $lastRun = is_file($stampFile)
            ? (int) @filemtime($stampFile)
            : 0;

        if ($intervalSeconds > 0 && (time() - $lastRun) < $intervalSeconds) {
            return ['skipped' => true, 'rejected' => 0, 'expired' => 0, 'files' => 0, 'applicants' => 0];
        }

        /*
         * Touch the stamp before running so two concurrent requests do not both
         * start the purge.
         */
        @touch($stampFile);

        try {
            return $this->run() + ['skipped' => false];
        } catch (\Throwable $e) {
            error_log('Application retention cleanup failed: ' . $e->getMessage());

            return ['skipped' => false, 'error' => $e->getMessage(), 'rejected' => 0, 'expired' => 0, 'files' => 0, 'applicants' => 0];
        }
    }


    /**
     * Immediately remove the rejected applications of a single posting. Used
     * the moment a posting is closed so the list is correct without waiting
     * for the next throttled run.
     */
    public function purgeRejectedForPosting(int $postingId): int
    {
        if ($postingId <= 0) {
            return 0;
        }

        $result = $this->purgeApplications(
            "
            SELECT ap.application_id
            FROM applications ap
            WHERE ap.posting_id = :posting_id
              AND ap.application_status = 'Rejected'
            ",
            [':posting_id' => $postingId]
        );

        return $result['applications'];
    }


    /*
    |--------------------------------------------------------------------------
    | Core
    |--------------------------------------------------------------------------
    */

    /**
     * Delete every application matched by $selectSql (which must return a
     * single `application_id` column), plus its files and orphaned applicant.
     *
     * @param array<string, mixed> $params
     * @return array{applications:int, files:int, applicants:int}
     */
    private function purgeApplications(string $selectSql, array $params = []): array
    {
        $stmt = $this->db->prepare($selectSql);
        $stmt->execute($params);

        $applicationIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

        if ($applicationIds === []) {
            return ['applications' => 0, 'files' => 0, 'applicants' => 0];
        }

        $filesToDelete = [];
        $affectedApplicants = [];
        $deletedApplications = 0;

        $ownsTransaction = !$this->db->inTransaction();

        if ($ownsTransaction) {
            $this->db->beginTransaction();
        }

        try {

            foreach ($applicationIds as $applicationId) {

                /*
                 * Safety net: an application that produced an employee record
                 * must never be deleted. The queries already exclude 'Hired',
                 * so this should not trigger — but a broken status value must
                 * not take an employee's history with it.
                 */
                if ($this->hasEmployee($applicationId)) {
                    continue;
                }

                $application = $this->fetchApplicationFiles($applicationId);

                if ($application === null) {
                    continue;
                }

                $affectedApplicants[$application['applicant_id']] = true;

                foreach ($application['files'] as $fileName) {
                    $filesToDelete[$fileName] = true;
                }

                // Rows whose foreign key has no ON DELETE CASCADE go first.
                $this->deleteDependents($applicationId);

                $delete = $this->db->prepare(
                    'DELETE FROM applications WHERE application_id = :id'
                );
                $delete->execute([':id' => $applicationId]);

                $deletedApplications += $delete->rowCount();
            }

            if ($ownsTransaction) {
                $this->db->commit();
            }

        } catch (\Throwable $e) {

            if ($ownsTransaction && $this->db->inTransaction()) {
                $this->db->rollBack();
            }

            throw $e;
        }

        /*
         * Files and applicant profiles are handled after the transaction so a
         * rollback can never leave the database pointing at files we already
         * removed.
         */
        return [
            'applications' => $deletedApplications,
            'files'        => $this->deleteFiles(array_keys($filesToDelete)),
            'applicants'   => $this->deleteOrphanApplicants(array_keys($affectedApplicants)),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | Queries
    |--------------------------------------------------------------------------
    */

    private function rejectedOnClosedPostingsSql(): string
    {
        return "
            SELECT ap.application_id
            FROM applications ap
            INNER JOIN job_postings jp
                ON jp.posting_id = ap.posting_id
            WHERE jp.status = 'Closed'
              AND ap.application_status = 'Rejected'
        ";
    }


    private function expiredOnClosedPostingsSql(): string
    {
        // $this->retentionDays is an int, so inlining it is safe.
        return "
            SELECT ap.application_id
            FROM applications ap
            INNER JOIN job_postings jp
                ON jp.posting_id = ap.posting_id
            WHERE jp.status = 'Closed'
              AND jp.closed_at IS NOT NULL
              AND jp.closed_at <= DATE_SUB(NOW(), INTERVAL {$this->retentionDays} DAY)
              AND (ap.application_status IS NULL OR ap.application_status <> 'Hired')
        ";
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    private function hasEmployee(int $applicationId): bool
    {
        $stmt = $this->db->prepare(
            'SELECT employee_id FROM employees WHERE application_id = :id LIMIT 1'
        );
        $stmt->execute([':id' => $applicationId]);

        return (bool) $stmt->fetchColumn();
    }


    /**
     * Collect the uploaded file names belonging to an application.
     *
     * @return array{applicant_id:int, files:array<int, string>}|null
     */
    private function fetchApplicationFiles(int $applicationId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT applicant_id, resume_file, cover_letter_file
            FROM applications
            WHERE application_id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $applicationId]);

        $application = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$application) {
            return null;
        }

        $files = [];

        foreach (['resume_file', 'cover_letter_file'] as $column) {
            $name = trim((string) ($application[$column] ?? ''));

            if ($name !== '') {
                $files[$name] = true;
            }
        }

        $documentStmt = $this->db->prepare(
            'SELECT file_name FROM academic_document_evaluations WHERE application_id = :id'
        );
        $documentStmt->execute([':id' => $applicationId]);

        foreach ($documentStmt->fetchAll(PDO::FETCH_COLUMN) as $name) {
            $name = trim((string) $name);

            if ($name !== '') {
                $files[$name] = true;
            }
        }

        return [
            'applicant_id' => (int) $application['applicant_id'],
            'files'        => array_keys($files),
        ];
    }


    /**
     * Delete the child rows that would otherwise block the application delete
     * (their foreign keys have no ON DELETE CASCADE).
     */
    private function deleteDependents(int $applicationId): void
    {
        $this->db->prepare('DELETE FROM interviews WHERE application_id = :id')
            ->execute([':id' => $applicationId]);

        $this->db->prepare('DELETE FROM onboarding WHERE application_id = :id')
            ->execute([':id' => $applicationId]);
    }


    /**
     * @param array<int, string> $fileNames
     */
    private function deleteFiles(array $fileNames): int
    {
        $deleted = 0;
        $base = rtrim($this->uploadDirectory, '/\\');

        foreach ($fileNames as $fileName) {

            $fileName = trim((string) $fileName);

            /*
             * Never let a stored value escape the upload directory.
             */
            if (
                $fileName === ''
                || str_contains($fileName, '..')
                || str_contains($fileName, '/')
                || str_contains($fileName, '\\')
                || str_contains($fileName, "\0")
            ) {
                continue;
            }

            $path = $base . DIRECTORY_SEPARATOR . $fileName;

            if (is_file($path) && @unlink($path)) {
                $deleted++;
            }
        }

        return $deleted;
    }


    /**
     * Remove an applicant profile once it no longer has any application and is
     * not an employee.
     *
     * @param array<int, int> $applicantIds
     */
    private function deleteOrphanApplicants(array $applicantIds): int
    {
        $deleted = 0;

        foreach ($applicantIds as $applicantId) {

            $applicantId = (int) $applicantId;

            if ($applicantId <= 0) {
                continue;
            }

            $check = $this->db->prepare("
                SELECT
                    EXISTS(
                        SELECT 1 FROM applications WHERE applicant_id = :id
                    ) AS has_application,
                    EXISTS(
                        SELECT 1 FROM employees WHERE applicant_id = :id
                    ) AS is_employee
            ");
            $check->execute([':id' => $applicantId]);

            $row = $check->fetch(PDO::FETCH_ASSOC);

            if (!$row || (int) $row['has_application'] || (int) $row['is_employee']) {
                continue;
            }

            $delete = $this->db->prepare(
                'DELETE FROM applicants WHERE applicant_id = :id'
            );
            $delete->execute([':id' => $applicantId]);

            $deleted += $delete->rowCount();
        }

        return $deleted;
    }
}
