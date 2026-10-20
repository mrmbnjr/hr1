<?php

namespace App\Models;

use Core\Database;
use PDO;

class HiringRequirement
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }


    /*
    |--------------------------------------------------------------------------
    | Hiring Requirements
    |--------------------------------------------------------------------------
    */

    /**
     * Default requirement sets seeded when an application enters a
     * stage. Nothing is seeded when the application already has
     * requirements for that stage.
     */
    private const STAGE_DEFAULTS = [
        'Selection'      => [
            'Credential',
            'Reference',
            'Background Check'
        ],
        'Pre-employment' => [
            'Medical',
            'Document'
        ]
    ];


    public function seedDefaults(
        int $applicationId,
        string $stage
    ): int {

        if (!isset(self::STAGE_DEFAULTS[$stage])) {
            return 0;
        }

        $exists = $this->db->prepare("
            SELECT requirement_id
            FROM hiring_requirements
            WHERE application_id = :application_id
            AND stage = :stage
            LIMIT 1
        ");

        $exists->execute([
            ':application_id' => $applicationId,
            ':stage'          => $stage
        ]);

        if ($exists->fetch()) {
            return 0;
        }

        $insert = $this->db->prepare("
            INSERT INTO hiring_requirements
            (
                application_id,
                stage,
                requirement_type
            )
            VALUES
            (
                :application_id,
                :stage,
                :requirement_type
            )
        ");

        $seeded = 0;

        foreach (self::STAGE_DEFAULTS[$stage] as $type) {

            $insert->execute([
                ':application_id'   => $applicationId,
                ':stage'            => $stage,
                ':requirement_type' => $type
            ]);

            $seeded++;
        }

        return $seeded;
    }


    /**
     * Requirements for one application, optionally limited to one
     * stage, with the verifier's display name.
     */
    public function forApplication(
        int $applicationId,
        ?string $stage = null
    ): array {

        if ($stage === null) {
            $stmt = $this->db->prepare("
                SELECT
                    rq.requirement_id,
                    rq.stage,
                    rq.requirement_type,
                    rq.status,
                    rq.notes,
                    rq.verified_at,
                    rq.created_at,

                    CONCAT(
                        verifier.first_name,
                        ' ',
                        COALESCE(verifier.middle_name, ''),
                        ' ',
                        verifier.last_name
                    ) AS verified_by_name

                FROM hiring_requirements rq

                LEFT JOIN users u
                    ON rq.verified_by = u.user_id

                LEFT JOIN employees e
                    ON u.employee_id = e.employee_id

                LEFT JOIN applicants verifier
                    ON e.applicant_id = verifier.applicant_id

                WHERE rq.application_id = :application_id

                ORDER BY
                    FIELD(rq.stage, 'Selection', 'Pre-employment'),
                    rq.requirement_id ASC
            ");

            $stmt->execute([
                ':application_id' => $applicationId
            ]);
        } else {
            $stmt = $this->db->prepare("
                SELECT
                    rq.requirement_id,
                    rq.stage,
                    rq.requirement_type,
                    rq.status,
                    rq.notes,
                    rq.verified_at,
                    rq.created_at,

                    CONCAT(
                        verifier.first_name,
                        ' ',
                        COALESCE(verifier.middle_name, ''),
                        ' ',
                        verifier.last_name
                    ) AS verified_by_name

                FROM hiring_requirements rq

                LEFT JOIN users u
                    ON rq.verified_by = u.user_id

                LEFT JOIN employees e
                    ON u.employee_id = e.employee_id

                LEFT JOIN applicants verifier
                    ON e.applicant_id = verifier.applicant_id

                WHERE rq.application_id = :application_id
                AND rq.stage = :stage

                ORDER BY rq.requirement_id ASC
            ");

            $stmt->execute([
                ':application_id' => $applicationId,
                ':stage'          => $stage
            ]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Mark one requirement Cleared or Failed. Only requirements that
     * still belong to the application can be updated.
     */
    public function markStatus(
        int $requirementId,
        int $applicationId,
        string $status,
        int $verifiedBy,
        ?string $note = null
    ): bool {

        $stmt = $this->db->prepare("
            UPDATE hiring_requirements
            SET
                status = :status,
                notes = COALESCE(:note, notes),
                verified_by = :verified_by,
                verified_at = CURRENT_TIMESTAMP
            WHERE requirement_id = :requirement_id
            AND application_id = :application_id
        ");

        $stmt->execute([
            ':status'         => $status,
            ':note'           => $note,
            ':verified_by'    => $verifiedBy,
            ':requirement_id' => $requirementId,
            ':application_id' => $applicationId
        ]);

        return $stmt->rowCount() > 0;
    }


    /**
     * Push requirements of a stage back to Pending. When requirement
     * IDs are given only those are reset (Request Additional
     * Verification); otherwise the whole stage resets.
     */
    public function resetStage(
        int $applicationId,
        string $stage,
        array $requirementIds = []
    ): int {

        if ($requirementIds === []) {

            $stmt = $this->db->prepare("
                UPDATE hiring_requirements
                SET
                    status = 'Pending',
                    verified_by = NULL,
                    verified_at = NULL
                WHERE application_id = :application_id
                AND stage = :stage
            ");

            $stmt->execute([
                ':application_id' => $applicationId,
                ':stage'          => $stage
            ]);

            return $stmt->rowCount();
        }

        $placeholders = implode(
            ',',
            array_fill(0, count($requirementIds), '?')
        );

        $stmt = $this->db->prepare("
            UPDATE hiring_requirements
            SET
                status = 'Pending',
                verified_by = NULL,
                verified_at = NULL
            WHERE application_id = ?
            AND stage = ?
            AND requirement_id IN ({$placeholders})
        ");

        $params = array_merge(
            [$applicationId, $stage],
            array_map('intval', $requirementIds)
        );

        $stmt->execute($params);

        return $stmt->rowCount();
    }


    /**
     * Whether every requirement of a stage is Cleared. A stage with
     * no requirements is not considered cleared - the caller seeds
     * defaults first.
     */
    public function allCleared(
        int $applicationId,
        string $stage
    ): bool {

        $stmt = $this->db->prepare("
            SELECT COUNT(*)
            FROM hiring_requirements
            WHERE application_id = :application_id
            AND stage = :stage
        ");

        $stmt->execute([
            ':application_id' => $applicationId,
            ':stage'          => $stage
        ]);

        $total = (int) $stmt->fetchColumn();

        if ($total === 0) {
            return false;
        }

        $cleared = $this->db->prepare("
            SELECT COUNT(*)
            FROM hiring_requirements
            WHERE application_id = :application_id
            AND stage = :stage
            AND status = 'Cleared'
        ");

        $cleared->execute([
            ':application_id' => $applicationId,
            ':stage'          => $stage
        ]);

        return (int) $cleared->fetchColumn() === $total;
    }


    /**
     * One requirement, but only when it belongs to the given
     * application.
     */
    public function findForApplication(
        int $requirementId,
        int $applicationId
    ): ?array {

        $stmt = $this->db->prepare("
            SELECT
                requirement_id,
                application_id,
                stage,
                requirement_type,
                status
            FROM hiring_requirements
            WHERE requirement_id = :requirement_id
            AND application_id = :application_id
            LIMIT 1
        ");

        $stmt->execute([
            ':requirement_id' => $requirementId,
            ':application_id' => $applicationId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}
