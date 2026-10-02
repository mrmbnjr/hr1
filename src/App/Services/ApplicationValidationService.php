<?php

namespace App\Services;

/**
 * AI Validation layer.
 *
 * The AI only REPORTS findings (names, document types, consistency checks).
 * This class owns the decision: VALID / REVIEW / INVALID.
 *
 * Tune the rules in decide() and the constants below.
 */
class ApplicationValidationService
{
    /** An overall AI consistency score below this becomes REVIEW. */
    private const REVIEW_BELOW_SCORE = 60.0;

    private const UNRELATED_REJECTION_CONFIDENCE = 90.0;

    private const MAX_FINDINGS = 40;

    private GeminiService $gemini;

    public function __construct()
    {
        $this->gemini = new GeminiService();
    }


    /*
    |--------------------------------------------------------------------------
    | Validate
    |--------------------------------------------------------------------------
    |
    | @param string $resumePath  Staged resume (PDF with a real extension)
    | @param array  $documents   List of ['path' => string, 'label' => string]
    | @param array  $applicant   ['name' => string, 'email' => string, 'phone' => string]
    | @param array  $job         Job posting row (title, requirements)
    |
    | Never throws. If the AI is unavailable the result is REVIEW.
    |
    */

    public function validate(
        string $resumePath,
        array $documents,
        array $applicant,
        array $job
    ): array {

        $model = trim((string) (getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash'));

        try {

            $ai = $this->gemini->validateSubmission(
                $resumePath,
                $documents,
                $applicant,
                [
                    'title' => (string) ($job['title'] ?? ''),
                    'requirements' => (string) ($job['requirements'] ?? ''),
                ]
            );

        } catch (\Throwable $e) {

            error_log('AI validation failed: ' . $e->getMessage());

            return $this->unavailableResult($model, count($documents));
        }

        return $this->decide($ai, count($documents), $model);
    }


    /*
    |--------------------------------------------------------------------------
    | Rows For academic_document_evaluations
    |--------------------------------------------------------------------------
    |
    | @param array $validation      Result of validate()
    | @param array $finalFileNames  Stored file names, same order as the
    |                               documents passed to validate()
    |
    */

    public function academicRows(array $validation, array $finalFileNames): array
    {
        $rows = [];

        foreach ($finalFileNames as $position => $fileName) {

            $doc = $validation['documents'][$position] ?? null;

            if ($doc === null) {
                continue;
            }

            $rows[] = [
                'file_name' => $fileName,

                'document_valid' =>
                    $doc['readable']
                    && $doc['is_credential']
                    && in_array($doc['name_status'], ['match', 'variation'], true),

                'confidence_score' => $doc['confidence_score'],
                'document_type' => $doc['document_type'],
                'institution' => $doc['institution'],
                'degree' => $doc['degree'],
                'field_of_study' => $doc['field_of_study'],
                'graduation_year' => $doc['graduation_year'],
                'concerns' => $doc['concerns'],
                'ai_summary' => $doc['summary'],
            ];
        }

        return $rows;
    }


    /*
    |--------------------------------------------------------------------------
    | Decision Layer
    |--------------------------------------------------------------------------
    |
    | INVALID  strong, objective problems:
    |          - resume unreadable or not a resume
    |          - resume name clearly belongs to someone else
    |          - a credential that is not a credential document
    |          - a credential holder who is clearly a different person
    |
    | REVIEW   uncertainty or inconsistency:
    |          - name variations / name not visible
    |          - unreadable credential
    |          - credential does not match the resume's education
    |          - any consistency check reported as Conflict / Minor Differences
    |          - resume contradicts itself
    |          - low overall consistency score
    |
    | VALID    everything else.
    |
    */

    private function decide(array $ai, int $documentCount, string $model): array
    {
        $invalid = [];
        $review = [];
        $flagged = false;

        $resume = $ai['resume'];


        /*
         * Resume
         */
        if (!$resume['readable']) {

            $invalid[] = ['general', 'The submitted resume could not be read.'];

        } elseif (!$resume['is_resume']) {

            $invalid[] = ['general', 'The submitted resume file does not appear to be a resume.'];

        } elseif ($resume['name_status'] === 'mismatch') {

            $invalid[] = ['identity', 'The name on the resume does not correspond to the submitted applicant name.'];

        } elseif ($resume['name_status'] === 'variation') {

            $review[] = ['identity', 'The name on the resume differs in format from the submitted name.'];

        } elseif ($resume['name_status'] === 'not_visible') {

            $review[] = ['identity', 'The applicant name could not be confirmed on the resume.'];
        }

        if ($resume['job_relevance'] === 'unrelated') {
            $message = 'The resume appears unrelated to the position applied for.';

            if ($resume['job_relevance_confidence'] >= self::UNRELATED_REJECTION_CONFIDENCE) {
                $invalid[] = ['general', $message];
            } else {
                $review[] = ['general', $message . ' The assessment was not sufficiently certain for automatic rejection.'];
            }
        } elseif ($resume['job_relevance'] === 'unclear') {
            $review[] = ['general', 'The resume could not be confidently matched to the position applied for.'];
        }

        foreach ($resume['internal_conflicts'] as $conflict) {
            $review[] = ['experience', $conflict];
        }


        /*
         * Credential documents. File numbers match the "File N" labels
         * shown on the HR review page.
         */
        for ($i = 0; $i < $documentCount; $i++) {

            $label = 'File ' . ($i + 1);
            $doc = $ai['documents'][$i] ?? null;

            if ($doc === null) {
                $review[] = ['credential', $label . ' was not assessed by the AI.'];
                continue;
            }

            if (!$doc['readable']) {

                $review[] = ['credential', $label . ' could not be read clearly.'];
                continue;
            }

            if (!$doc['is_credential']) {

                $invalid[] = ['credential', $label . ' does not appear to be a credential document.'];
                continue;
            }

            if ($doc['name_status'] === 'mismatch') {

                $invalid[] = ['identity', 'The name on ' . $label . ' does not correspond to the submitted applicant name.'];

            } elseif ($doc['name_status'] === 'variation') {

                $review[] = ['identity', 'The name on ' . $label . ' differs in format from the submitted name.'];

            } elseif ($doc['name_status'] === 'not_visible') {

                $review[] = ['identity', 'The holder name on ' . $label . ' could not be confirmed.'];
            }

            if ($doc['matches_resume'] === 'no') {
                $review[] = ['education', $label . ' does not match the education listed on the resume.'];
            }
        }


        /*
         * AI consistency checks
         */
        $checks = $ai['checks'];

        if ($documentCount === 0) {

            $checks['credential'] = 'Not Checked';

        } elseif ($checks['credential'] === 'Unverifiable') {

            $flagged = true;
        }

        foreach (['identity', 'education', 'experience', 'credential'] as $category) {

            if (in_array($checks[$category], ['Conflict', 'Minor Differences'], true)) {
                $flagged = true;
            }
        }

        if ((float) $ai['consistency_score'] < self::REVIEW_BELOW_SCORE) {
            $flagged = true;
        }


        /*
         * Keep the per-category status consistent with the findings
         * the decision layer added.
         */
        foreach ($invalid as [$category]) {

            if (isset($checks[$category])) {
                $checks[$category] = 'Conflict';
            }
        }

        foreach ($review as [$category]) {

            if (isset($checks[$category]) && $checks[$category] === 'Consistent') {
                $checks[$category] = 'Minor Differences';
            }
        }


        /*
         * Final status
         */
        if ($invalid !== []) {
            $status = 'INVALID';
        } elseif ($review !== [] || $flagged) {
            $status = 'REVIEW';
        } else {
            $status = 'VALID';
        }


        /*
         * Findings: decision-layer reasons first, then the AI's own notes.
         */
        $findings = [];

        foreach ($invalid as [$category, $message]) {
            $findings[] = ['category' => $category, 'severity' => 'major', 'message' => $message];
        }

        foreach ($review as [$category, $message]) {
            $findings[] = ['category' => $category, 'severity' => 'minor', 'message' => $message];
        }

        foreach ($ai['findings'] as $finding) {
            $findings[] = [
                'category' => $finding['category'],
                'severity' => 'info',
                'message' => $finding['message'],
            ];
        }

        $findings = array_slice($findings, 0, self::MAX_FINDINGS);


        return [
            'status' => $status,
            'identity_status' => $checks['identity'],
            'education_status' => $checks['education'],
            'experience_status' => $checks['experience'],
            'credential_status' => $checks['credential'],
            'consistency_score' => (float) $ai['consistency_score'],
            'findings' => $findings,
            'summary' => $ai['summary'],
            'ai_model' => $model,
            'documents' => $ai['documents'],
            'ai_available' => true,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | AI Unavailable
    |--------------------------------------------------------------------------
    |
    | Never auto-INVALID on a technical failure. The application goes to
    | HR as REVIEW with a visible note.
    |
    */

    private function unavailableResult(string $model, int $documentCount): array
    {
        $message = 'Automated validation was unavailable when this application was submitted. Please verify the documents manually.';

        return [
            'status' => 'REVIEW',
            'identity_status' => 'Unverifiable',
            'education_status' => 'Unverifiable',
            'experience_status' => 'Unverifiable',
            'credential_status' => $documentCount === 0 ? 'Not Checked' : 'Unverifiable',
            'consistency_score' => null,
            'findings' => [[
                'category' => 'general',
                'severity' => 'minor',
                'message' => $message,
            ]],
            'summary' => $message,
            'ai_model' => $model,
            'documents' => [],
            'ai_available' => false,
        ];
    }
}