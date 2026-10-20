<?php

namespace App\Controllers;

use App\Models\Applicant;
use App\Models\HiringRequirement;
use App\Models\JobOffer;
use App\Services\Auth;
use App\Services\MailService;
use App\Services\PipelineService;
use Core\Database;

class PipelineController
{
    private Applicant $applicant;
    private PipelineService $pipeline;
    private JobOffer $offers;
    private HiringRequirement $requirements;

    private const EMPLOYMENT_TYPES = [
        'Full-Time',
        'Part-Time',
        'Contract',
        'Internship'
    ];

    public function __construct()
    {
        $this->applicant    = new Applicant();
        $this->pipeline     = new PipelineService();
        $this->offers       = new JobOffer();
        $this->requirements = new HiringRequirement();
    }


    /*
    |--------------------------------------------------------------------------
    | Phase 2 - Interview evaluation
    |--------------------------------------------------------------------------
    */

    /**
     * Interviewer submits the fixed rating form plus a recommendation.
     * Works from the Interview stage only.
     */
    public function submitEvaluation(): void
    {
        Auth::requireRole(['ADMIN', 'HR', 'MGR']);

        $this->requirePost();

        $applicationId = (int) ($_POST['application_id'] ?? 0);

        $context = $this->context($applicationId);

        if ($context['application_status'] !== 'Interview') {
            $this->fail('Only applications in the interview stage can be evaluated.');
        }

        if (empty($context['interview_id'])) {
            $this->fail('No interview is scheduled for this application yet.');
        }

        if ((int) $context['interviewer_id'] !== (int) Auth::userId()) {
            $this->fail('Only the scheduled interviewer can submit this evaluation.', 403);
        }

        $ratings = [];

        foreach (array_keys(PipelineService::RATING_CRITERIA) as $criterion) {

            $value = (int) ($_POST[$criterion] ?? 0);

            if (
                $value < PipelineService::RATING_MIN ||
                $value > PipelineService::RATING_MAX
            ) {
                $this->fail(
                    'Every rating criterion must be between '
                    . PipelineService::RATING_MIN . ' and '
                    . PipelineService::RATING_MAX . '.'
                );
            }

            $ratings[$criterion] = $value;
        }

        $recommendation = (string) ($_POST['recommendation'] ?? '');

        if (!in_array($recommendation, PipelineService::RECOMMENDATIONS, true)) {
            $this->fail('Select a valid recommendation.');
        }

        $this->pipeline->recordEvaluation([
            'interview_id'     => (int) $context['interview_id'],
            'evaluator_id'     => (int) Auth::userId(),
            'evaluator_role'   => Auth::role(),
            'technical_skills' => $ratings['technical_skills'],
            'communication'    => $ratings['communication'],
            'problem_solving'  => $ratings['problem_solving'],
            'culture_fit'      => $ratings['culture_fit'],
            'strengths'        => $this->text('strengths', 2000),
            'weaknesses'       => $this->text('weaknesses', 2000),
            'remarks'          => $this->text('remarks', 2000),
            'recommendation'   => $recommendation
        ]);

        $this->pipeline->logAction(
            $applicationId,
            'Interview evaluation submitted',
            $recommendation
        );

        $this->ok('Evaluation submitted.');
    }


    /*
    |--------------------------------------------------------------------------
    | Phase 2 - HR advance / reject
    |--------------------------------------------------------------------------
    */

    /**
     * HR moves a evaluated applicant from Interview to Selection and
     * marks the interview result Passed.
     */
    public function advanceToSelection(): void
    {
        Auth::requireRole(['ADMIN', 'HR', 'MGR']);

        $this->requirePost();

        $applicationId = (int) ($_POST['application_id'] ?? 0);

        $context = $this->context($applicationId);

        if (!$this->pipeline->hasEvaluation($applicationId)) {
            $this->fail('At least one interview evaluation is required before advancing.');
        }

        $db = Database::connection();

        try {

            $this->pipeline->transition(
                $applicationId,
                'Interview',
                'Selection',
                'Advanced to Selection',
                Auth::userId(),
                null,
                function () use ($applicationId, $db): void {

                    /* Seed the checks that HR must verify. */
                    $this->pipeline->seedSelectionRequirements($applicationId);

                    /*
                     * Keep interviews.result meaningful for the view
                     * and the HumanCapital dashboard.
                     */
                    $stmt = $db->prepare("
                        UPDATE interviews
                        SET result = 'Passed'
                        WHERE application_id = :application_id
                        AND result = 'Pending'
                    ");

                    $stmt->execute([
                        ':application_id' => $applicationId
                    ]);
                }
            );

        } catch (\RuntimeException $e) {
            $this->fail($e->getMessage());
        }

        /*
         * Generic progress email after the commit; a mail failure is
         * logged but never undoes the transition.
         */
        $emailSent = $this->sendCandidate(
            $context,
            'sendStatusUpdate'
        );

        $this->ok(
            'Applicant advanced to the selection stage.',
            ['email_sent' => $emailSent]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Phase 3 - Selection requirements
    |--------------------------------------------------------------------------
    */

    public function updateRequirement(): void
    {
        Auth::requireRole(['ADMIN', 'HR', 'MGR']);

        $this->requirePost();

        $applicationId  = (int) ($_POST['application_id'] ?? 0);
        $requirementId  = (int) ($_POST['requirement_id'] ?? 0);
        $status         = (string) ($_POST['status'] ?? '');

        if (!in_array($status, ['Cleared', 'Failed'], true)) {
            $this->fail('A requirement can only be marked Cleared or Failed.');
        }

        $context = $this->context($applicationId);

        if (!in_array($context['application_status'], ['Selection', 'Offer'], true)) {
            $this->fail('Requirements can only be updated during their pipeline stage.');
        }

        $requirement = $this->requirements->findForApplication(
            $requirementId,
            $applicationId
        );

        if (!$requirement) {
            $this->fail('Requirement not found for this application.');
        }

        if (
            ($requirement['stage'] === 'Selection' && $context['application_status'] !== 'Selection')
            || ($requirement['stage'] === 'Pre-employment' && $context['application_status'] !== 'Offer')
        ) {
            $this->fail('This requirement is not active at the current application stage.');
        }

        if ($requirement['stage'] === 'Pre-employment') {
            if (!Auth::hasAnyRole(['ADMIN', 'HR'])) {
                $this->fail('Only HR can clear pre-employment requirements.', 403);
            }

            $offer = $this->offers->forApplication($applicationId);

            if (!$offer || $offer['status'] !== 'Accepted') {
                $this->fail('Pre-employment requirements can be updated after the offer is accepted.');
            }
        }

        $note = $this->text('note', 500);

        $this->requirements->markStatus(
            $requirementId,
            $applicationId,
            $status,
            (int) Auth::userId(),
            $note
        );

        $this->pipeline->logAction(
            $applicationId,
            'Requirement ' . strtolower($status),
            $requirement['requirement_type']
                . ($note !== null ? ': ' . $note : '')
        );

        $this->ok('Requirement updated.');
    }


    public function requestAdditionalVerification(): void
    {
        Auth::requireRole(['ADMIN', 'HR', 'MGR']);

        $this->requirePost();

        $applicationId = (int) ($_POST['application_id'] ?? 0);

        $context = $this->context($applicationId);

        if ($context['application_status'] !== 'Selection') {
            $this->fail('Additional verification can only be requested during the selection stage.');
        }

        $requirementIds = array_map(
            'intval',
            (array) ($_POST['requirement_ids'] ?? [])
        );

        $requirementIds = array_values(array_unique(array_filter(
            $requirementIds,
            static fn (int $requirementId): bool => $requirementId > 0
        )));

        if ($requirementIds === []) {
            $this->fail('Select at least one requirement for additional verification.');
        }

        foreach ($requirementIds as $requirementId) {
            $requirement = $this->requirements->findForApplication(
                $requirementId,
                $applicationId
            );

            if (
                !$requirement
                || $requirement['stage'] !== 'Selection'
                || $requirement['status'] === 'Pending'
            ) {
                $this->fail('Select only completed selection requirements to verify again.');
            }
        }

        $reset = $this->pipeline->requirements()->resetStage(
            $applicationId,
            'Selection',
            $requirementIds
        );

        if ($reset === 0) {
            $this->fail('No selection requirements were reset.');
        }

        $this->pipeline->logAction(
            $applicationId,
            'Additional verification requested',
            $reset . ' requirement(s) reset to pending'
        );

        $this->ok('Additional verification requested for the selected requirement(s).');
    }


    /*
    |--------------------------------------------------------------------------
    | Phase 3 - Approve for offer
    |--------------------------------------------------------------------------
    */

    public function approveForOffer(): void
    {
        Auth::requireRole(['ADMIN', 'HR', 'MGR']);

        $this->requirePost();

        $applicationId = (int) ($_POST['application_id'] ?? 0);

        $context = $this->context($applicationId);

        if (!$this->pipeline->allCleared($applicationId, 'Selection')) {
            $this->fail('Every selection requirement must be cleared before approving for offer.');
        }

        try {

            $this->pipeline->transition(
                $applicationId,
                'Selection',
                'Offer',
                'Approved for offer',
                Auth::userId(),
                null,
                function () use ($applicationId): void {
                    $this->pipeline->seedPreEmploymentRequirements($applicationId);
                }
            );

        } catch (\RuntimeException $e) {
            $this->fail($e->getMessage());
        }

        $this->ok('Applicant approved for offer.');
    }


    /*
    |--------------------------------------------------------------------------
    | Phase 4 - Offer
    |--------------------------------------------------------------------------
    */

    public function sendOffer(): void
    {
        Auth::requireRole(['ADMIN', 'HR']);

        $this->requirePost();

        $applicationId = (int) ($_POST['application_id'] ?? 0);

        $context = $this->context($applicationId);

        if ($context['application_status'] !== 'Offer') {
            $this->fail('Offers can only be sent once the applicant is approved for offer.');
        }

        $existing = $this->offers->forApplication($applicationId);

        if ($existing && $existing['status'] !== 'Draft') {
            $this->fail('An offer was already sent. Use revise & resend to update it.');
        }

        $offer = $this->validateOffer();

        $offerId = $this->offers->upsertDraft([
            'application_id'   => $applicationId,
            'offered_position' => $offer['offered_position'],
            'offered_salary'   => $offer['offered_salary'],
            'employment_type'  => $offer['employment_type'],
            'start_date'       => $offer['start_date'],
            'terms'            => $offer['terms'],
            'offer_expires_on' => $offer['offer_expires_on'],
            'prepared_by'      => Auth::userId()
        ]);

        if (!$this->offers->markSent($offerId, 'Draft')) {
            $this->fail('The offer could not be sent. Please try again.');
        }

        $this->pipeline->logAction(
            $applicationId,
            'Offer sent',
            $offer['offered_position']
        );

        $emailSent = $this->sendCandidate(
            $context,
            'sendOfferLetter',
            $this->offerEmailPayload($applicationId, $offer)
        );

        $this->ok(
            'Offer sent to the applicant.',
            ['email_sent' => $emailSent]
        );
    }


    public function reviseOffer(): void
    {
        Auth::requireRole(['ADMIN', 'HR']);

        $this->requirePost();

        $applicationId = (int) ($_POST['application_id'] ?? 0);

        $context = $this->context($applicationId);

        if ($context['application_status'] !== 'Offer') {
            $this->fail('Only offers can be revised.');
        }

        $existing = $this->offers->forApplication($applicationId);

        if (!$existing || $existing['status'] !== 'Sent') {
            $this->fail('Only a sent offer can be revised and resent.');
        }

        $offer = $this->validateOffer();

        $offerId = $this->offers->upsertDraft([
            'application_id'   => $applicationId,
            'offered_position' => $offer['offered_position'],
            'offered_salary'   => $offer['offered_salary'],
            'employment_type'  => $offer['employment_type'],
            'start_date'       => $offer['start_date'],
            'terms'            => $offer['terms'],
            'offer_expires_on' => $offer['offer_expires_on'],
            'prepared_by'      => Auth::userId()
        ]);

        if (!$this->offers->markSent($offerId, 'Sent')) {
            $this->fail('The revised offer could not be sent. Please try again.');
        }

        $this->pipeline->logAction(
            $applicationId,
            'Offer revised and resent',
            $offer['offered_position']
        );

        $emailSent = $this->sendCandidate(
            $context,
            'sendOfferLetter',
            $this->offerEmailPayload($applicationId, $offer)
        );

        $this->ok(
            'Revised offer sent to the applicant.',
            ['email_sent' => $emailSent]
        );
    }


    public function recordOfferResponse(): void
    {
        Auth::requireRole(['ADMIN', 'HR']);

        $this->requirePost();

        $applicationId = (int) ($_POST['application_id'] ?? 0);
        $response      = (string) ($_POST['response'] ?? '');

        if (!in_array($response, ['accept', 'decline'], true)) {
            $this->fail('The response must be accept or decline.');
        }

        $context = $this->context($applicationId);

        $existing = $this->offers->forApplication($applicationId);

        if (!$existing || $existing['status'] !== 'Sent') {
            $this->fail('Only a sent offer can record a response.');
        }

        if ($context['application_status'] !== 'Offer') {
            $this->fail('Offer responses can only be recorded during the offer stage.');
        }

        $note = $this->text('note', 1000);

        if (!$this->offers->markResponse(
            (int) $existing['offer_id'],
            $response === 'accept' ? 'Accepted' : 'Declined',
            $note
        )) {
            $this->fail('The offer response was already recorded. Refresh the application.');
        }

        if ($response === 'accept') {

            $this->pipeline->logAction(
                $applicationId,
                'Offer accepted',
                $note
            );

            $this->ok('Offer acceptance recorded.');
        }

        /*
         * A declined offer rejects the application internally with no
         * rejection email - the candidate ended the process
         * themselves.
         */
        $rejected = $this->pipeline->rejectTransition(
            $applicationId,
            'Offer Declined',
            $note,
            Auth::userId(),
            false
        );

        if (!$rejected) {
            $this->fail('The offer could not be recorded as declined.');
        }

        $this->ok('Offer decline recorded.');
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    /**
     * Load the pipeline context for an application or fail with a
     * JSON error when it does not exist.
     */
    private function context(int $applicationId): array
    {
        if ($applicationId <= 0) {
            $this->fail('Invalid application ID.');
        }

        $context = $this->applicant->getApplicationForPipeline($applicationId);

        if (!$context) {
            $this->fail('Application not found.');
        }

        return $context;
    }


    /**
     * Validate and normalise the offer form fields.
     */
    private function validateOffer(): array
    {
        $position = trim((string) ($_POST['offered_position'] ?? ''));

        if ($position === '' || mb_strlen($position) > 150) {
            $this->fail('Enter the offered position (up to 150 characters).');
        }

        $salary = (float) ($_POST['offered_salary'] ?? 0);

        if ($salary <= 0) {
            $this->fail('Enter a valid offered salary.');
        }

        $employmentType = (string) ($_POST['employment_type'] ?? '');

        if (!in_array($employmentType, self::EMPLOYMENT_TYPES, true)) {
            $this->fail('Select a valid employment type.');
        }

        $startDate = (string) ($_POST['start_date'] ?? '');

        if (!$this->validDate($startDate)) {
            $this->fail('Enter a valid proposed start date.');
        }

        $expiresOn = trim((string) ($_POST['offer_expires_on'] ?? ''));

        if ($expiresOn !== '' && !$this->validDate($expiresOn)) {
            $this->fail('Enter a valid offer expiry date.');
        }

        return [
            'offered_position' => $position,
            'offered_salary'   => number_format($salary, 2, '.', ''),
            'employment_type'  => $employmentType,
            'start_date'       => $startDate,
            'terms'            => $this->text('terms', 5000),
            'offer_expires_on' => $expiresOn !== '' ? $expiresOn : null
        ];
    }


    /**
     * Build the candidate-facing offer payload from the validated
     * form data plus the stored applicant details.
     */
    private function offerEmailPayload(
        int $applicationId,
        array $offer
    ): array {

        $stored = $this->offers->forApplication($applicationId);

        return [
            'email'           => null,
            'fullname'        => null,
            'position'        => $offer['offered_position'],
            'salary'          => 'PHP ' . number_format((float) $offer['offered_salary'], 2),
            'employment_type' => $offer['employment_type'],
            'start_date'      => date('F d, Y', strtotime($offer['start_date'])),
            'terms'           => (string) ($offer['terms'] ?? ''),
            'expires_on'      => !empty($stored['offer_expires_on'])
                ? date('F d, Y', strtotime((string) $stored['offer_expires_on']))
                : null
        ];
    }


    /**
     * Send a candidate email after the database commit. Failures are
     * logged and reported back in the response so HR knows whether
     * the email went out; they never undo the pipeline action.
     */
    private function sendCandidate(
        array $context,
        string $method,
        ?array $payload = null
    ): bool {

        $data = $payload ?? [];

        $data['email']    = (string) $context['email'];
        $data['fullname'] = (string) $context['fullname'];
        $data['position'] = $data['position']
            ?? (string) $context['position'];

        try {

            (new MailService())->{$method}($data);

            return true;

        } catch (\Throwable $e) {

            error_log(
                'Pipeline email failed (' . $method . ') for application '
                . (int) $context['application_id'] . ': '
                . $e->getMessage()
            );

            return false;
        }
    }


    private function text(string $field, int $maxLength): ?string
    {
        $value = trim((string) ($_POST[$field] ?? ''));

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, $maxLength);
    }


    private function validDate(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $date = \DateTime::createFromFormat('Y-m-d', $value);

        return $date !== false
            && $date->format('Y-m-d') === $value;
    }


    private function requirePost(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
    }


    private function ok(string $message, array $extra = []): void
    {
        $this->json(
            array_merge(
                ['success' => true, 'message' => $message],
                $extra
            )
        );
    }


    private function fail(string $message, int $status = 400): void
    {
        $this->json(
            [
                'success' => false,
                'message' => $message
            ],
            $status
        );
    }


    private function json(array $payload, int $status = 200): void
    {
        http_response_code($status);

        header('Content-Type: application/json');

        echo json_encode($payload);

        exit;
    }
}
