<?php

namespace App\Services;

use App\Models\ApplicationTimeline;
use App\Models\HiringRequirement;
use App\Models\InterviewEvaluation;
use App\Services\Auth;
use Core\Database;
use RuntimeException;

class PipelineService
{
    private ApplicationTimeline $timeline;
    private HiringRequirement $requirements;
    private InterviewEvaluation $evaluations;

    /*
     * The one fixed rating form used for every applicant of a
     * position: four criteria, each rated 1-5. Shared by the
     * evaluation form and the server-side validation.
     */
    public const RATING_CRITERIA = [
        'technical_skills' => 'Technical Skills',
        'communication'    => 'Communication',
        'problem_solving'  => 'Problem Solving',
        'culture_fit'      => 'Culture Fit'
    ];

    public const RATING_MIN = 1;
    public const RATING_MAX = 5;

    /*
     * Fixed rejection reason categories. Stored HR-internal in the
     * application timeline; the candidate email never exposes them.
     */
    public const REJECTION_REASONS = [
        'Not qualified',
        'Position filled',
        'Better candidate selected',
        'Failed requirements',
        'Withdrew',
        'Other'
    ];

    /*
     * Interviewer recommendations. Shared by the evaluation form and
     * the server-side validation.
     */
    public const RECOMMENDATIONS = [
        'Recommend',
        'Further Assessment',
        'Do Not Recommend'
    ];

    public function __construct()
    {
        $this->timeline     = new ApplicationTimeline();
        $this->requirements = new HiringRequirement();
        $this->evaluations  = new InterviewEvaluation();
    }


    /*
    |--------------------------------------------------------------------------
    | Status transitions
    |--------------------------------------------------------------------------
    */

    /**
     * Compare-and-set status transition.
     *
     * The UPDATE only takes effect while the application is still in
     * $expectedStatus, so two HR users acting at the same time cannot
     * double-advance or send two offers: the second update matches no
     * row and the action fails. The timeline row (and any work done by
     * $withinTransaction, e.g. seeding requirements) commits in the
     * same transaction as the status change.
     *
     * @param callable|null $withinTransaction Extra work executed
     *        between the status update and the commit.
     * @throws RuntimeException when the application is no longer in
     *         the expected status.
     */
    public function transition(
        int $applicationId,
        string $expectedStatus,
        string $toStatus,
        string $action,
        ?int $actorUserId = null,
        ?string $note = null,
        ?callable $withinTransaction = null
    ): void {

        $db = Database::connection();

        $db->beginTransaction();

        try {

            $stmt = $db->prepare("
                UPDATE applications
                SET
                    application_status = :to_status,
                    status_updated_at = CURRENT_TIMESTAMP
                WHERE application_id = :application_id
                AND application_status = :expected_status
            ");

            $stmt->execute([
                ':to_status'      => $toStatus,
                ':application_id' => $applicationId,
                ':expected_status' => $expectedStatus
            ]);

            if ($stmt->rowCount() === 0) {

                throw new RuntimeException(
                    'This application was already moved by another user. '
                    . 'Refresh the page to see its current stage.'
                );
            }

            if ($withinTransaction !== null) {
                $withinTransaction();
            }

            $this->timeline->add(
                $applicationId,
                $expectedStatus,
                $toStatus,
                $action,
                $actorUserId,
                $note
            );

            $db->commit();

        } catch (\Throwable $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }


    /**
     * Rejection transition usable from any non-Hired stage.
     *
     * Unlike transition() there is no single expected status; the
     * current one is read first and the update is still guarded
     * against concurrent Hires. Returns false when nothing changed
     * (already Rejected, or Hired in the meantime).
     */
    public function rejectTransition(
        int $applicationId,
        string $reasonCategory,
        ?string $note = null,
        ?int $actorUserId = null,
        bool $notifyCandidate = true
    ): bool {

        $db = Database::connection();

        $db->beginTransaction();

        try {

            $current = $db->prepare("
                SELECT application_status
                FROM applications
                WHERE application_id = :application_id
                LIMIT 1
            ");

            $current->execute([
                ':application_id' => $applicationId
            ]);

            $fromStatus = $current->fetchColumn();

            if (!$fromStatus || $fromStatus === 'Hired') {
                $db->rollBack();
                return false;
            }

            $stmt = $db->prepare("
                UPDATE applications
                SET
                    application_status = 'Rejected',
                    rejected_at = CASE
                        WHEN application_status <> 'Rejected'
                            THEN CURRENT_TIMESTAMP
                        ELSE rejected_at
                    END,
                    status_updated_at = CURRENT_TIMESTAMP
                WHERE application_id = :application_id
                AND application_status = :from_status
                AND application_status <> 'Hired'
            ");

            $stmt->execute([
                ':application_id' => $applicationId,
                ':from_status'    => $fromStatus
            ]);

            if ($stmt->rowCount() === 0) {
                $db->rollBack();
                return false;
            }

            /*
             * Rejecting while the interview result is still Pending
             * marks it Failed so the view and the HumanCapital
             * dashboard's pending-result lists stay meaningful.
             */
            if ($fromStatus === 'Interview') {

                $interview = $db->prepare("
                    UPDATE interviews
                    SET result = 'Failed'
                    WHERE application_id = :application_id
                    AND result = 'Pending'
                ");

                $interview->execute([
                    ':application_id' => $applicationId
                ]);
            }

            /*
             * The internal reason stays HR-only in the timeline; the
             * candidate email (when sent) is deliberately generic.
             */
            $timelineNote = $reasonCategory;

            if ($note !== null && trim($note) !== '') {
                $timelineNote .= ': ' . trim($note);
            }

            $this->timeline->add(
                $applicationId,
                $fromStatus,
                'Rejected',
                'Rejected',
                $actorUserId,
                $timelineNote
            );

            $db->commit();

            return true;

        } catch (\Throwable $e) {

            if ($db->inTransaction()) {
                $db->rollBack();
            }

            throw $e;
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Evaluations
    |--------------------------------------------------------------------------
    */

    /**
     * Record one interviewer evaluation. Ratings and recommendation
     * are validated by the caller.
     */
    public function recordEvaluation(array $data): int
    {
        return $this->evaluations->create($data);
    }


    /*
    |--------------------------------------------------------------------------
    | Timeline actions without a status change
    |--------------------------------------------------------------------------
    */

    /**
     * Log a pipeline action that does not change the application
     * status (evaluation submitted, requirement updated, offer sent).
     */
    public function logAction(
        int $applicationId,
        string $action,
        ?string $note = null
    ): void {

        $this->timeline->add(
            $applicationId,
            null,
            null,
            $action,
            Auth::userId(),
            $note
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Requirements
    |--------------------------------------------------------------------------
    */

    /** Seed the checks HR must complete during final selection. */
    public function seedSelectionRequirements(int $applicationId): void
    {
        $this->requirements->seedDefaults($applicationId, 'Selection');
    }


    public function seedPreEmploymentRequirements(int $applicationId): void
    {
        $this->requirements->seedDefaults($applicationId, 'Pre-employment');
    }


    public function allCleared(int $applicationId, string $stage): bool
    {
        return $this->requirements->allCleared($applicationId, $stage);
    }


    public function hasEvaluation(int $applicationId): bool
    {
        return $this->evaluations->countForApplication($applicationId) > 0;
    }


    public function evaluationsFor(int $applicationId): array
    {
        return $this->evaluations->forApplication($applicationId);
    }


    public function requirements(): HiringRequirement
    {
        return $this->requirements;
    }


    public function timeline(): ApplicationTimeline
    {
        return $this->timeline;
    }
}
