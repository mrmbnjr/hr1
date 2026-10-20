<?php

namespace App\Models;

use Core\Database;
use PDO;

class JobOffer
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }


    /*
    |--------------------------------------------------------------------------
    | Job Offers
    |--------------------------------------------------------------------------
    */

    /**
     * The offer for one application, or null when none exists yet.
     */
    public function forApplication(int $applicationId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT
                o.offer_id,
                o.application_id,
                o.offered_position,
                o.offered_salary,
                o.employment_type,
                o.start_date,
                o.terms,
                o.status,
                o.offer_expires_on,
                o.prepared_by,
                o.sent_at,
                o.responded_at,
                o.response_note,
                o.created_at,
                o.updated_at

            FROM job_offers o

            WHERE o.application_id = :application_id

            LIMIT 1
        ");

        $stmt->execute([
            ':application_id' => $applicationId
        ]);

        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }


    /**
     * Insert or update the offer draft for an application. Position
     * and salary are snapshotted so later posting edits do not
     * rewrite offer history.
     */
    public function upsertDraft(array $data): int
    {
        $existing = $this->db->prepare("
            SELECT offer_id
            FROM job_offers
            WHERE application_id = :application_id
            LIMIT 1
        ");

        $existing->execute([
            ':application_id' => (int) $data['application_id']
        ]);

        $offerId = $existing->fetchColumn();

        if ($offerId) {

            $stmt = $this->db->prepare("
                UPDATE job_offers
                SET
                    offered_position = :offered_position,
                    offered_salary = :offered_salary,
                    employment_type = :employment_type,
                    start_date = :start_date,
                    terms = :terms,
                    offer_expires_on = :offer_expires_on,
                    prepared_by = :prepared_by
                WHERE offer_id = :offer_id
            ");

            $stmt->execute([
                ':offered_position' => $data['offered_position'],
                ':offered_salary'  => $data['offered_salary'],
                ':employment_type'  => $data['employment_type'],
                ':start_date'       => $data['start_date'],
                ':terms'            => $data['terms'] ?? null,
                ':offer_expires_on' => $data['offer_expires_on'] ?? null,
                ':prepared_by'      => $data['prepared_by'] ?? null,
                ':offer_id'         => (int) $offerId
            ]);

            return (int) $offerId;
        }

        $stmt = $this->db->prepare("
            INSERT INTO job_offers
            (
                application_id,
                offered_position,
                offered_salary,
                employment_type,
                start_date,
                terms,
                status,
                offer_expires_on,
                prepared_by
            )
            VALUES
            (
                :application_id,
                :offered_position,
                :offered_salary,
                :employment_type,
                :start_date,
                :terms,
                'Draft',
                :offer_expires_on,
                :prepared_by
            )
        ");

        $stmt->execute([
            ':application_id'   => (int) $data['application_id'],
            ':offered_position' => $data['offered_position'],
            ':offered_salary'   => $data['offered_salary'],
            ':employment_type'  => $data['employment_type'],
            ':start_date'       => $data['start_date'],
            ':terms'            => $data['terms'] ?? null,
            ':offer_expires_on' => $data['offer_expires_on'] ?? null,
            ':prepared_by'      => $data['prepared_by'] ?? null
        ]);

        return (int) $this->db->lastInsertId();
    }


    /**
     * Mark a Draft offer as Sent, or re-stamp an already Sent offer
     * after HR revises and resends it. Compare-and-set on the current
     * status so a double click cannot send twice.
     */
    public function markSent(
        int $offerId,
        string $expectedStatus = 'Draft'
    ): bool {

        $stmt = $this->db->prepare("
            UPDATE job_offers
            SET
                status = 'Sent',
                sent_at = CURRENT_TIMESTAMP,
                responded_at = NULL
            WHERE offer_id = :offer_id
            AND status = :expected_status
        ");

        $stmt->execute([
            ':offer_id'        => $offerId,
            ':expected_status' => $expectedStatus
        ]);

        return $stmt->rowCount() > 0;
    }


    /**
     * Record the candidate's response relayed by HR. Counter-requests
     * from the candidate are kept in response_note. Compare-and-set
     * on 'Sent' so a response can only be recorded once.
     */
    public function markResponse(
        int $offerId,
        string $response,
        ?string $note = null
    ): bool {

        $stmt = $this->db->prepare("
            UPDATE job_offers
            SET
                status = :status,
                responded_at = CURRENT_TIMESTAMP,
                response_note = COALESCE(:note, response_note)
            WHERE offer_id = :offer_id
            AND status = 'Sent'
        ");

        $stmt->execute([
            ':status'   => $response,
            ':note'     => $note,
            ':offer_id' => $offerId
        ]);

        return $stmt->rowCount() > 0;
    }
}
