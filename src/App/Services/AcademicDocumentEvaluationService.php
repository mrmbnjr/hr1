<?php

namespace App\Services;

use Core\Database;
use PDO;
use RuntimeException;

class AcademicDocumentEvaluationService
{
    private GeminiService $gemini;
    private AcademicDocumentInputService $documentInput;
    private PDO $db;

    public function __construct()
    {
        $this->gemini = new GeminiService();
        $this->documentInput = new AcademicDocumentInputService();
        $this->db = Database::connection();
    }


    /*
    |--------------------------------------------------------------------------
    | Evaluate All Academic Documents
    |--------------------------------------------------------------------------
    |
    | Evaluates every academic document submitted under the application.
    |
    */

    public function evaluate(int $applicationId): array
    {
        if ($applicationId <= 0) {
            throw new RuntimeException(
                'Invalid application ID.'
            );
        }


        /*
         * Get applicant information.
         */
        $stmt = $this->db->prepare("
            SELECT
                ap.first_name,
                ap.middle_name,
                ap.last_name,
                ap.email,
                ap.phone

            FROM applications a

            INNER JOIN applicants ap
                ON ap.applicant_id = a.applicant_id

            WHERE a.application_id = :application_id

            LIMIT 1
        ");

        $stmt->execute([
            ':application_id' => $applicationId
        ]);

        $application = $stmt->fetch(
            PDO::FETCH_ASSOC
        );


        if (!$application) {
            throw new RuntimeException(
                'Application not found.'
            );
        }


        /*
         * Get every academic document belonging
         * to this application.
         */
        $stmt = $this->db->prepare("
            SELECT
                evaluation_id,
                application_id,
                file_name,
                original_file_name,
                is_primary

            FROM academic_document_evaluations

            WHERE application_id = :application_id

            ORDER BY
                is_primary DESC,
                evaluation_id ASC
        ");

        $stmt->execute([
            ':application_id' => $applicationId
        ]);

        $documents = $stmt->fetchAll(
            PDO::FETCH_ASSOC
        );


        if (empty($documents)) {
            throw new RuntimeException(
                'No academic documents were submitted with this application.'
            );
        }


        /*
         * Build applicant identity information.
         */
        $applicantName = trim(
            implode(
                ' ',
                array_filter([
                    trim(
                        (string)
                        ($application['first_name'] ?? '')
                    ),
                    trim(
                        (string)
                        ($application['middle_name'] ?? '')
                    ),
                    trim(
                        (string)
                        ($application['last_name'] ?? '')
                    )
                ])
            )
        );


        $results = [];


        /*
         * Resolve every document to a readable file first. A document
         * that cannot be resolved is marked Failed and left out of the
         * shared request rather than aborting the whole pass.
         */
        $files = [];


        foreach ($documents as $position => $document) {

            $evaluationId =
                (int) $document['evaluation_id'];


            $this->markStatus(
                $evaluationId,
                'Processing'
            );


            try {

                /*
                 * AcademicDocumentInputService expects the document
                 * under academic_document_file. The real filename comes
                 * from academic_document_evaluations.file_name.
                 */
                $file =
                    $this->documentInput->getDocument([
                        'academic_document_file' =>
                            $document['file_name'],

                        'original_file_name' =>
                            $document['original_file_name']
                            ?? $document['file_name']
                    ]);


                $files[] = [
                    'position' =>
                        $position,

                    'evaluation_id' =>
                        $evaluationId,

                    'file_name' =>
                        $document['file_name'],

                    'original_file_name' =>
                        $document['original_file_name']
                        ?? $document['file_name'],

                    'path' =>
                        $file['path']
                ];

            } catch (\Throwable $e) {

                $this->markStatus(
                    $evaluationId,
                    'Failed'
                );


                $results[$position] = [
                    'evaluation_id' =>
                        $evaluationId,

                    'file_name' =>
                        $document['file_name'],

                    'original_file_name' =>
                        $document['original_file_name'],

                    'evaluation_status' =>
                        'Failed',

                    'success' =>
                        false,

                    'message' =>
                        $e->getMessage()
                ];
            }
        }


        if (!empty($files)) {

            /*
             * Every resolved document travels in ONE request so the model
             * assesses the whole set together, instead of one isolated
             * call per file.
             */
            try {

                $evaluations =
                    $this->gemini->evaluateAcademicDocuments(
                        array_map(
                            static fn (array $file): array => [
                                'path' => $file['path'],

                                'label' => $file['original_file_name']
                            ],
                            $files
                        ),
                        [
                            'name' =>
                                $applicantName,

                            'email' =>
                                (string) ($application['email'] ?? ''),

                            'phone' =>
                                (string) ($application['phone'] ?? '')
                        ]
                    );

            } catch (\Throwable $e) {

                /*
                 * The pass is all-or-nothing, so a failed request fails
                 * every document that took part in it.
                 */
                foreach ($files as $file) {

                    $this->markStatus(
                        $file['evaluation_id'],
                        'Failed'
                    );
                }

                throw $e;
            }


            $validPositions =
                array_column($files, 'position');

            $byPosition = [];


            /*
             * Match each returned entry back to its document. The model
             * echoes file_index; fall back to positional order when it is
             * missing or out of range.
             */
            foreach ($evaluations as $order => $evaluation) {

                $index =
                    $evaluation['file_index'] ?? null;


                if (
                    $index === null ||
                    !in_array($index, $validPositions, true)
                ) {
                    $index =
                        $files[$order]['position'] ?? null;
                }


                if (
                    $index === null ||
                    isset($byPosition[$index])
                ) {
                    continue;
                }


                $byPosition[$index] = $evaluation;
            }


            foreach ($files as $file) {

                $position = $file['position'];

                $evaluation =
                    $byPosition[$position] ?? null;


                if ($evaluation === null) {

                    $this->markStatus(
                        $file['evaluation_id'],
                        'Failed'
                    );


                    $results[$position] = [
                        'evaluation_id' =>
                            $file['evaluation_id'],

                        'file_name' =>
                            $file['file_name'],

                        'original_file_name' =>
                            $file['original_file_name'],

                        'evaluation_status' =>
                            'Failed',

                        'success' =>
                            false,

                        'message' =>
                            'The AI did not return a result for this document.'
                    ];

                    continue;
                }


                /*
                 * Identity verification.
                 */
                if (empty($evaluation['identity_match'])) {

                    $evaluation['document_valid'] =
                        false;

                    $evaluation['confidence_score'] =
                        0;


                    if (
                        !is_array(
                            $evaluation['concerns'] ?? null
                        )
                    ) {
                        $evaluation['concerns'] = [];
                    }


                    $evaluation['concerns'][] =
                        'The certificate holder identity could not be confirmed.';
                }


                /*
                 * Save the result against this specific document.
                 */
                $this->save(
                    $file['evaluation_id'],
                    $evaluation
                );


                /*
                 * Keep the filename information in the returned result.
                 */
                $evaluation['evaluation_id'] =
                    $file['evaluation_id'];

                $evaluation['file_name'] =
                    $file['file_name'];

                $evaluation['original_file_name'] =
                    $file['original_file_name'];

                $evaluation['evaluation_status'] =
                    'Evaluated';

                $evaluation['success'] =
                    true;


                $results[$position] = $evaluation;
            }
        }


        /*
         * Restore the original document order before returning.
         */
        ksort($results);

        return array_values($results);
    }


    /*
    |--------------------------------------------------------------------------
    | Summarize Results
    |--------------------------------------------------------------------------
    |
    | Aggregate figures across every document in the pass, so the caller
    | can report an at-a-glance status for the whole set of uploads.
    |
    */

    public function summarize(array $results): array
    {
        $summary = [
            'total' => count($results),
            'evaluated' => 0,
            'valid' => 0,
            'flagged' => 0,
            'failed' => 0,
            'average_confidence' => 0.0,
        ];

        $scoreSum = 0.0;
        $scoreCount = 0;


        foreach ($results as $result) {

            $status = strtolower(
                trim(
                    (string) (
                        $result['evaluation_status'] ?? ''
                    )
                )
            );


            if (
                ($result['success'] ?? true) === false ||
                $status === 'failed'
            ) {
                $summary['failed']++;
                continue;
            }


            $summary['evaluated']++;

            $scoreSum +=
                (float) ($result['confidence_score'] ?? 0);

            $scoreCount++;


            if (!empty($result['document_valid'])) {
                $summary['valid']++;
            } else {
                $summary['flagged']++;
            }
        }


        if ($scoreCount > 0) {
            $summary['average_confidence'] =
                round($scoreSum / $scoreCount, 2);
        }


        return $summary;
    }


    /*
    |--------------------------------------------------------------------------
    | Update Evaluation Status
    |--------------------------------------------------------------------------
    |
    | Keeps evaluation_status in sync with the real assessment lifecycle
    | (Processing / Evaluated / Failed) so the review page can tell which
    | documents have already been assessed.
    |
    */

    private function markStatus(
        int $evaluationId,
        string $status
    ): void {

        $stmt = $this->db->prepare("
            UPDATE academic_document_evaluations

            SET
                evaluation_status =
                    :evaluation_status,

                updated_at =
                    CURRENT_TIMESTAMP

            WHERE evaluation_id =
                :evaluation_id
        ");


        $stmt->execute([
            ':evaluation_status' => $status,
            ':evaluation_id' => $evaluationId
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Save Evaluation
    |--------------------------------------------------------------------------
    |
    | Updates the existing academic document evaluation.
    |
    */

    private function save(
        int $evaluationId,
        array $evaluation
    ): void {

        $stmt = $this->db->prepare("
            UPDATE academic_document_evaluations

            SET
                document_valid =
                    :document_valid,

                evaluation_status =
                    'Evaluated',

                confidence_score =
                    :confidence_score,

                document_type =
                    :document_type,

                institution =
                    :institution,

                degree =
                    :degree,

                field_of_study =
                    :field_of_study,

                graduation_year =
                    :graduation_year,

                extracted_details =
                    :extracted_details,

                concerns =
                    :concerns,

                ai_summary =
                    :ai_summary,

                processed_at =
                    CURRENT_TIMESTAMP,

                updated_at =
                    CURRENT_TIMESTAMP

            WHERE evaluation_id =
                :evaluation_id
        ");


        $stmt->execute([

            ':document_valid' =>
                !empty(
                    $evaluation['document_valid']
                )
                    ? 1
                    : 0,


            ':confidence_score' =>
                (float)
                ($evaluation['confidence_score'] ?? 0),


            ':document_type' =>
                $evaluation['document_type']
                ?? null,


            ':institution' =>
                $evaluation['institution']
                ?? null,


            ':degree' =>
                $evaluation['degree']
                ?? null,


            ':field_of_study' =>
                $evaluation['field_of_study']
                ?? null,


            ':graduation_year' =>
                $evaluation['graduation_year']
                ?? null,


            ':extracted_details' =>
                json_encode(
                    $evaluation['extracted_details']
                    ?? []
                ),


            ':concerns' =>
                json_encode(
                    $evaluation['concerns']
                    ?? []
                ),


            ':ai_summary' =>
                $evaluation['ai_summary']
                ?? null,


            ':evaluation_id' =>
                $evaluationId
        ]);
    }
}