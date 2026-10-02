<?php

namespace App\Models;

use Core\Database;
use PDO;

class ApplicationValidation
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }


    /*
    |--------------------------------------------------------------------------
    | Create Validation Record
    |--------------------------------------------------------------------------
    |
    | application_id is left NULL here. It is linked afterwards, and only
    | for VALID / REVIEW submissions that become real applications.
    |
    */

    public function create(array $data): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO application_validations
            (
                submission_reference,
                posting_id,
                applicant_name,
                applicant_email,
                ip_address,
                validation_status,
                identity_status,
                education_status,
                experience_status,
                credential_status,
                consistency_score,
                findings,
                validation_summary,
                ai_model
            )
            VALUES
            (
                :submission_reference,
                :posting_id,
                :applicant_name,
                :applicant_email,
                :ip_address,
                :validation_status,
                :identity_status,
                :education_status,
                :experience_status,
                :credential_status,
                :consistency_score,
                :findings,
                :validation_summary,
                :ai_model
            )
        ");

        $stmt->execute([
            ':submission_reference' => $data['submission_reference'],
            ':posting_id' => (int) $data['posting_id'],
            ':applicant_name' => mb_substr((string) $data['applicant_name'], 0, 300, 'UTF-8'),
            ':applicant_email' => mb_substr((string) $data['applicant_email'], 0, 150, 'UTF-8'),
            ':ip_address' => $data['ip_address'] ?? null,
            ':validation_status' => $data['validation_status'],
            ':identity_status' => $data['identity_status'],
            ':education_status' => $data['education_status'],
            ':experience_status' => $data['experience_status'],
            ':credential_status' => $data['credential_status'],
            ':consistency_score' => $data['consistency_score'],
            ':findings' => json_encode($data['findings'] ?? []),
            ':validation_summary' => $data['validation_summary'] ?? null,
            ':ai_model' => $data['ai_model'] ?? null,
        ]);

        return (int) $this->db->lastInsertId();
    }


    public function reserveAttempt(
        array $data,
        int $maxInvalidAttempts,
        int $maxAttemptsPerHour
    ): int {
        $lockNames = [
            'hr1av-e-' . substr(
                hash('sha256', mb_strtolower(trim((string) $data['applicant_email']))),
                0,
                56
            ),
        ];

        $ipAddress = trim((string) ($data['ip_address'] ?? ''));

        if ($ipAddress !== '') {
            $lockNames[] = 'hr1av-i-' . substr(hash('sha256', $ipAddress), 0, 56);
        }

        sort($lockNames, SORT_STRING);
        $acquiredLocks = [];

        try {
            foreach ($lockNames as $lockName) {
                $lock = $this->db->prepare('SELECT GET_LOCK(:lock_name, 5)');
                $lock->execute([':lock_name' => $lockName]);

                if ((int) $lock->fetchColumn() !== 1) {
                    throw new \RuntimeException('Too many submission attempts. Please try again later or contact the recruitment team.');
                }

                $acquiredLocks[] = $lockName;
            }

            if (
                $this->countRecentAttempts($data['applicant_email'], $ipAddress, 'INVALID', 24)
                    >= $maxInvalidAttempts
                || $this->countRecentAttempts($data['applicant_email'], $ipAddress, null, 1)
                    >= $maxAttemptsPerHour
            ) {
                throw new \RuntimeException('Too many submission attempts. Please try again later or contact the recruitment team.');
            }

            return $this->create([
                'submission_reference' => $data['submission_reference'],
                'posting_id' => $data['posting_id'],
                'applicant_name' => $data['applicant_name'],
                'applicant_email' => $data['applicant_email'],
                'ip_address' => $ipAddress !== '' ? $ipAddress : null,
                'validation_status' => 'PROCESSING',
                'identity_status' => 'Not Checked',
                'education_status' => 'Not Checked',
                'experience_status' => 'Not Checked',
                'credential_status' => 'Not Checked',
                'consistency_score' => null,
                'findings' => [],
                'validation_summary' => 'Automated validation is processing.',
                'ai_model' => trim((string) (getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash')),
            ]);
        } finally {
            foreach (array_reverse($acquiredLocks) as $lockName) {
                try {
                    $release = $this->db->prepare('SELECT RELEASE_LOCK(:lock_name)');
                    $release->execute([':lock_name' => $lockName]);
                } catch (\Throwable $e) {
                    error_log('Unable to release application validation lock: ' . $e->getMessage());
                }
            }
        }
    }


    public function updateResult(int $validationId, array $data): void
    {
        $stmt = $this->db->prepare("
            UPDATE application_validations
            SET validation_status = :validation_status,
                identity_status = :identity_status,
                education_status = :education_status,
                experience_status = :experience_status,
                credential_status = :credential_status,
                consistency_score = :consistency_score,
                findings = :findings,
                validation_summary = :validation_summary,
                ai_model = :ai_model
            WHERE validation_id = :validation_id
        ");

        $stmt->execute([
            ':validation_status' => $data['validation_status'],
            ':identity_status' => $data['identity_status'],
            ':education_status' => $data['education_status'],
            ':experience_status' => $data['experience_status'],
            ':credential_status' => $data['credential_status'],
            ':consistency_score' => $data['consistency_score'],
            ':findings' => json_encode($data['findings'] ?? []),
            ':validation_summary' => $data['validation_summary'] ?? null,
            ':ai_model' => $data['ai_model'] ?? null,
            ':validation_id' => $validationId,
        ]);
    }


    public function markProcessingAsReview(int $validationId, string $summary): void
    {
        $stmt = $this->db->prepare("
            UPDATE application_validations
            SET validation_status = 'REVIEW',
                validation_summary = :summary
            WHERE validation_id = :validation_id
            AND validation_status <> 'INVALID'
            AND application_id IS NULL
        ");

        $stmt->execute([
            ':summary' => $summary,
            ':validation_id' => $validationId,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Link To Application
    |--------------------------------------------------------------------------
    */

    public function linkToApplication(int $validationId, int $applicationId): void
    {
        $stmt = $this->db->prepare("
            UPDATE application_validations
            SET application_id = :application_id
            WHERE validation_id = :validation_id
        ");

        $stmt->execute([
            ':application_id' => $applicationId,
            ':validation_id' => $validationId,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Copy Document Results Into academic_document_evaluations
    |--------------------------------------------------------------------------
    |
    | The validation call already reads every credential, so its extracted
    | values are stored here and the separate academic evaluation call does
    | not have to run for new submissions.
    |
    | $rows: list of [
    |   file_name, document_valid, confidence_score, document_type,
    |   institution, degree, field_of_study, graduation_year,
    |   concerns (array), ai_summary
    | ]
    |
    */

    public function applyToAcademicDocuments(int $applicationId, array $rows): void
    {
        $stmt = $this->db->prepare("
            UPDATE academic_document_evaluations
            SET
                evaluation_status = 'Evaluated',
                document_valid = :document_valid,
                confidence_score = :confidence_score,
                document_type = :document_type,
                institution = :institution,
                degree = :degree,
                field_of_study = :field_of_study,
                graduation_year = :graduation_year,
                extracted_details = :extracted_details,
                concerns = :concerns,
                ai_summary = :ai_summary,
                processed_at = CURRENT_TIMESTAMP,
                updated_at = CURRENT_TIMESTAMP
            WHERE application_id = :application_id
            AND file_name = :file_name
        ");

        foreach ($rows as $row) {
            $stmt->execute([
                ':document_valid' => !empty($row['document_valid']) ? 1 : 0,
                ':confidence_score' => (float) ($row['confidence_score'] ?? 0),
                ':document_type' => $row['document_type'] ?? null,
                ':institution' => $row['institution'] ?? null,
                ':degree' => $row['degree'] ?? null,
                ':field_of_study' => $row['field_of_study'] ?? null,
                ':graduation_year' => $row['graduation_year'] ?? null,
                ':extracted_details' => json_encode([]),
                ':concerns' => json_encode($row['concerns'] ?? []),
                ':ai_summary' => $row['ai_summary'] ?? null,
                ':application_id' => $applicationId,
                ':file_name' => $row['file_name'],
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Counts recent validation attempts for the same email OR the same IP.
    | Pass $status = 'INVALID' to count only blocked attempts, or null to
    | count every attempt.
    |
    */

    public function countRecentAttempts(
        string $email,
        ?string $ipAddress,
        ?string $status,
        int $hours
    ): int {

        $hours = max(1, $hours);

        $sql = "
            SELECT COUNT(*)
            FROM application_validations
            WHERE processed_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
            AND (applicant_email = :email
        ";

        $params = [':email' => $email];

        if ($ipAddress !== null && $ipAddress !== '') {
            $sql .= " OR ip_address = :ip_address";
            $params[':ip_address'] = $ipAddress;
        }

        $sql .= ")";

        if ($status !== null) {
            $sql .= " AND validation_status = :status";
            $params[':status'] = $status;
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return (int) $stmt->fetchColumn();
    }


    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Removes INVALID audit rows (the ones that never became applications)
    | older than the retention window.
    |
    */

    public function purgeExpiredInvalid(int $days): void
    {
        $days = max(1, $days);

        $this->db->exec("
            DELETE FROM application_validations
            WHERE validation_status = 'INVALID'
            AND application_id IS NULL
            AND processed_at < DATE_SUB(NOW(), INTERVAL {$days} DAY)
        ");
    }


    public function purgeExpiredUnlinked(int $hours = 24): void
    {
        $hours = max(1, $hours);

        $this->db->exec("
            DELETE FROM application_validations
            WHERE application_id IS NULL
            AND validation_status <> 'INVALID'
            AND processed_at < DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
        ");
    }
}