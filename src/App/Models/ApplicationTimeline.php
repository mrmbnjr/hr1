<?php

namespace App\Models;

use Core\Database;
use PDO;

class ApplicationTimeline
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }


    /*
    |--------------------------------------------------------------------------
    | Timeline
    |--------------------------------------------------------------------------
    */

    /**
     * Record one pipeline action. Called inside the same transaction as
     * the status change it belongs to.
     */
    public function add(
        int $applicationId,
        ?string $fromStatus,
        ?string $toStatus,
        string $action,
        ?int $actorUserId = null,
        ?string $note = null
    ): void {

        $stmt = $this->db->prepare("
            INSERT INTO application_timeline
            (
                application_id,
                from_status,
                to_status,
                action,
                actor_user_id,
                note
            )
            VALUES
            (
                :application_id,
                :from_status,
                :to_status,
                :action,
                :actor_user_id,
                :note
            )
        ");

        $stmt->execute([
            ':application_id' => $applicationId,
            ':from_status'    => $fromStatus,
            ':to_status'      => $toStatus,
            ':action'         => $action,
            ':actor_user_id'  => $actorUserId,
            ':note'           => $note
        ]);
    }


    /**
     * Every timeline entry for one application, newest first, with the
     * acting user's display name when they are linked to an applicant
     * record.
     */
    public function forApplication(int $applicationId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                t.event_id,
                t.from_status,
                t.to_status,
                t.action,
                t.note,
                t.created_at,

                CONCAT(
                    actor.first_name,
                    ' ',
                    COALESCE(actor.middle_name, ''),
                    ' ',
                    actor.last_name
                ) AS actor_name,

                r.role_code AS actor_role

            FROM application_timeline t

            LEFT JOIN users u
                ON t.actor_user_id = u.user_id

            LEFT JOIN employees e
                ON u.employee_id = e.employee_id

            LEFT JOIN applicants actor
                ON e.applicant_id = actor.applicant_id

            LEFT JOIN roles r
                ON u.role_id = r.role_id

            WHERE t.application_id = :application_id

            ORDER BY
                t.created_at DESC,
                t.event_id DESC
        ");

        $stmt->execute([
            ':application_id' => $applicationId
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


}
