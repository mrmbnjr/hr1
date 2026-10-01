<?php

namespace App\Services;

use RuntimeException;

class GeminiService
{
    private string $apiKey;
    private string $model;
    private int $timeout;
    private string $endpoint;

    private const MAX_FILE_SIZE = 5242880;

    /*
     * Upper bound on the combined size of every credential document sent
     * in one single-pass evaluation request. Base64 encoding inflates the
     * payload by roughly a third, so this stays within the API request cap.
     */
    private const MAX_BATCH_BYTES = 15728640;

    private const MAX_SUMMARY_LENGTH = 3000;
    private const MAX_ARRAY_ITEM_LENGTH = 500;
    private const MAX_ARRAY_ITEMS = 30;

    private const ALLOWED_MIME_TYPES = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    private const ALLOWED_EXTENSIONS = [
        'pdf',
        'jpg',
        'jpeg',
        'png',
    ];

    private const ALLOWED_RECOMMENDATIONS = [
        'Highly Recommended',
        'Recommended',
        'Consider',
        'Not Recommended',
    ];

    public function __construct()
    {
        $this->apiKey = trim((string) getenv('GEMINI_API_KEY'));
        $this->model = trim((string) (getenv('GEMINI_MODEL') ?: 'gemini-2.5-flash'));
        $this->timeout = max(10, (int) (getenv('GEMINI_TIMEOUT') ?: 60));
        $this->endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($this->model)
            . ':generateContent';
    }

    public function evaluateResume(string $resumePath, array $jobPosting, array $applicant): array
    {
        $this->validateConfiguration();

        $resume = $this->validateResume($resumePath);
        $requirements = $this->buildJobRequirements($jobPosting);
        $prompt = $this->buildEvaluationPrompt($requirements, $applicant);
        $response = $this->sendRequest([
            ['text' => $prompt],
            $this->buildFilePart($resume),
        ]);

        return $this->parseEvaluationResponse($response);
    }

    /*
    |--------------------------------------------------------------------------
    | Evaluate Every Credential Document In One Pass
    |--------------------------------------------------------------------------
    |
    | Every submitted credential document is attached to a single request so
    | the model reviews them as one set instead of one isolated call per file.
    |
    | @param array $documents List of ['path' => string, 'label' => string]
    | @param array $applicant ['name' => string, 'email' => string, 'phone' => string]
    |
    | @return array<int, array> One normalised result per document, in order.
    |
    */

    public function evaluateAcademicDocuments(array $documents, array $applicant): array
    {
        $this->validateConfiguration();

        if ($documents === []) {
            throw new RuntimeException(
                'No academic documents were provided.'
            );
        }

        $files = [];
        $totalBytes = 0;

        /*
         * Validate every file up front so a single bad document cannot
         * corrupt the shared request.
         */
        foreach (array_values($documents) as $position => $document) {

            $validated = $this->validateAcademicDocument(
                (string) ($document['path'] ?? '')
            );

            $totalBytes += (int) filesize($validated['path']);

            $files[] = [
                'position' => $position,
                'label' => $this->sanitizePromptText(
                    (string) (
                        $document['label']
                        ?? basename($validated['path'])
                    ),
                    200
                ),
                'mimeType' => $validated['mimeType'],
                'path' => $validated['path'],
            ];
        }

        if ($totalBytes > self::MAX_BATCH_BYTES) {
            throw new RuntimeException(
                'The submitted documents are too large to assess in a single pass.'
            );
        }

        $applicantName = $this->sanitizePromptText((string) ($applicant['name'] ?? ''), 300);
        $applicantEmail = $this->sanitizePromptText((string) ($applicant['email'] ?? ''), 300);
        $applicantPhone = $this->sanitizePromptText((string) ($applicant['phone'] ?? ''), 100);

        /*
         * Give the model an explicit manifest so it can echo back which
         * file each extracted value belongs to.
         */
        $manifest = '';

        foreach ($files as $file) {
            $manifest .= '- file_index ' . $file['position'] . ': ' . $file['label'] . "\n";
        }

        $prompt = <<<PROMPT
You are a certificate verification assistant.

You are reviewing every credential document this applicant submitted, together, in one pass. Each attached file is preceded by a "DOCUMENT {index} - {filename}" label identifying it. Assess each document independently, then return one entry per document.

Determine whether each document appears to be a genuine, readable certificate based only on visible content. Do not infer protected personal characteristics. Do not treat this result as a final legal or institutional verification.

The applicant submitted these identity details:
- Name: {$applicantName}
- Email: {$applicantEmail}
- Phone: {$applicantPhone}

The attached documents are:
{$manifest}
Compare the visible certificate holder name on each document with the submitted applicant name. Set identity_match to false for a document when the names clearly refer to different people. Ignore differences in case, whitespace, punctuation, and common name ordering. If a holder name is not visible or is unreadable, set identity_match to false because identity cannot be confirmed. Do not use email or phone to infer identity unless either is visibly printed on the document.

Return only a valid JSON object matching the schema. Include exactly one entry in "documents" for every attached file, echoing the same file_index and file_label that were provided. Use an empty string when a field is not visible. List concrete reasons for uncertainty in concerns.
PROMPT;

        /*
         * Interleave a label with each file so the ordering is explicit.
         */
        $parts = [['text' => $prompt]];

        foreach ($files as $file) {
            $parts[] = [
                'text' => 'DOCUMENT ' . $file['position'] . ' - ' . $file['label'],
            ];

            $parts[] = $this->buildFilePart($file);
        }

        return $this->parseAcademicDocumentsResponse(
            $this->sendRequest(
                $parts,
                $this->getAcademicDocumentsResponseSchema()
            )
        );
    }

    private function buildFilePart(array $file): array
    {
        return [
            'inline_data' => [
                'mime_type' => $file['mimeType'],
                'data' => base64_encode(
                    (string) file_get_contents($file['path'])
                ),
            ],
        ];
    }

    private function getAcademicDocumentsResponseSchema(): array
    {
        $documentProperties = [
            'file_index' => ['type' => 'INTEGER'],
            'file_label' => ['type' => 'STRING'],
            'document_valid' => ['type' => 'BOOLEAN'],
            'identity_match' => ['type' => 'BOOLEAN'],
            'confidence_score' => ['type' => 'NUMBER'],
            'document_type' => ['type' => 'STRING'],
            'institution' => ['type' => 'STRING'],
            'degree' => ['type' => 'STRING'],
            'field_of_study' => ['type' => 'STRING'],
            'graduation_year' => ['type' => 'STRING'],
            'extracted_details' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            'concerns' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
            'ai_summary' => ['type' => 'STRING'],
        ];

        return [
            'type' => 'OBJECT',
            'properties' => [
                'documents' => [
                    'type' => 'ARRAY',
                    'items' => [
                        'type' => 'OBJECT',
                        'properties' => $documentProperties,
                        'required' => array_keys($documentProperties),
                    ],
                ],
            ],
            'required' => ['documents'],
        ];
    }

    private function getResponseSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'overall_score' => ['type' => 'NUMBER'],
                'identity_match' => ['type' => 'BOOLEAN'],
                'skills_score' => ['type' => 'NUMBER'],
                'experience_score' => ['type' => 'NUMBER'],
                'education_score' => ['type' => 'NUMBER'],
                'keyword_score' => ['type' => 'NUMBER'],
                'recommendation' => [
                    'type' => 'STRING',
                    'enum' => [
                        'Highly Recommended',
                        'Recommended',
                        'Consider',
                        'Not Recommended',
                    ],
                ],
                'extracted_skills' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'strengths' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'concerns' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'ai_summary' => ['type' => 'STRING'],
            ],
            'required' => [
                'overall_score',
                'identity_match',
                'skills_score',
                'experience_score',
                'education_score',
                'keyword_score',
                'recommendation',
                'extracted_skills',
                'strengths',
                'concerns',
                'ai_summary',
            ],
        ];
    }

    private function validateConfiguration(): void
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('AI evaluation is temporarily unavailable.');
        }

        if ($this->model === '') {
            throw new RuntimeException('AI evaluation is temporarily unavailable.');
        }

        if (!str_starts_with($this->endpoint, 'https://')) {
            throw new RuntimeException('AI evaluation is temporarily unavailable.');
        }
    }

    private function validateResume(string $resumePath): array
    {
        if (trim($resumePath) === '') {
            throw new RuntimeException('No resume was provided.');
        }

        $realPath = realpath($resumePath);

        if ($realPath === false) {
            throw new RuntimeException('The applicant resume could not be found.');
        }

        if (!is_file($realPath) || !is_readable($realPath)) {
            throw new RuntimeException('The applicant resume is invalid or unreadable.');
        }

        $fileSize = filesize($realPath);

        if ($fileSize === false || $fileSize <= 0) {
            throw new RuntimeException('The applicant resume is empty.');
        }

        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new RuntimeException('The applicant resume exceeds the 5 MB limit.');
        }

        $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));

        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new RuntimeException('Unsupported resume file format.');
        }

        $mimeType = $this->detectMimeType($realPath);

        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new RuntimeException('Unsupported resume MIME type.');
        }

        return [
            'path' => $realPath,
            'mimeType' => $mimeType,
            'extension' => $extension,
        ];
    }

    private function validateAcademicDocument(string $documentPath): array
    {
        $realPath = realpath($documentPath);
        if ($realPath === false || !is_file($realPath) || !is_readable($realPath)) {
            throw new RuntimeException('The academic document is invalid or unreadable.');
        }

        $fileSize = filesize($realPath);
        if ($fileSize === false || $fileSize <= 0 || $fileSize > self::MAX_FILE_SIZE) {
            throw new RuntimeException('The academic document is empty or exceeds the 5 MB limit.');
        }

        $extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
        $mimeType = $this->detectMimeType($realPath);

        if (
            !in_array($extension, self::ALLOWED_EXTENSIONS, true) ||
            !in_array($mimeType, self::ALLOWED_MIME_TYPES, true)
        ) {
            throw new RuntimeException('Unsupported academic document format.');
        }

        return ['path' => $realPath, 'mimeType' => $mimeType, 'extension' => $extension];
    }

    private function detectMimeType(string $path): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($path);

        if (is_string($mimeType) && $mimeType !== '') {
            return strtolower($mimeType);
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }

    private function buildJobRequirements(array $jobPosting): array
    {
        $title = trim((string) ($jobPosting['title'] ?? ''));
        $description = trim((string) ($jobPosting['description'] ?? ''));
        $requirements = trim((string) ($jobPosting['requirements'] ?? ''));

        return [
            'title' => $title,
            'description' => $description,
            'requirements' => $requirements,
        ];
    }

    private function sanitizePromptText(string $text, int $maxLength): string
    {
        $text = preg_replace('/\s+/', ' ', trim((string) $text)) ?? '';

        if (strlen($text) > $maxLength) {
            $text = substr($text, 0, $maxLength);
        }

        return $text;
    }

    private function buildEvaluationPrompt(array $jobPosting, array $applicant): string
    {
        $title = $this->sanitizePromptText((string) ($jobPosting['title'] ?? ''), 500);
        $description = $this->sanitizePromptText((string) ($jobPosting['description'] ?? ''), 3000);
        $requirements = $this->sanitizePromptText((string) ($jobPosting['requirements'] ?? ''), 3000);
        $applicantName = $this->sanitizePromptText((string) ($applicant['name'] ?? ''), 300);
        $applicantEmail = $this->sanitizePromptText((string) ($applicant['email'] ?? ''), 300);
        $applicantPhone = $this->sanitizePromptText((string) ($applicant['phone'] ?? ''), 100);

        return <<<PROMPT
You are an AI-assisted recruitment screening system.

Evaluate the attached applicant resume against the job posting below.

The evaluation must be based only on job-related qualifications demonstrated in the resume.

Do not make decisions based on protected or sensitive personal characteristics.

Do not consider:
- age
- gender
- race
- ethnicity
- religion
- disability
- marital status
- nationality
- political beliefs
- sexual orientation
- other protected characteristics

JOB POSTING

Job Title:
{$title}

Job Description:
{$description}

Job Requirements:
{$requirements}

Evaluate the candidate fairly and return only a valid JSON object matching the schema.

The applicant submitted these identity details:
- Name: {$applicantName}
- Email: {$applicantEmail}
- Phone: {$applicantPhone}

Compare the name visible in the resume with the submitted applicant name. Set identity_match to false when they clearly refer to different people. Ignore differences in case, whitespace, punctuation, and common name ordering. If the resume name is not visible or is unreadable, set identity_match to false because identity cannot be confirmed. Do not use email or phone to infer identity unless either is visibly printed in the resume. A resume with identity_match false must receive overall_score 0 and recommendation Not Recommended.
PROMPT;
    }

    /*
     * Sends one generateContent request built from the supplied parts.
     * Parts may mix text and inline_data entries, which lets a single
     * request carry several documents at once.
     */
    private function sendRequest(array $parts, ?array $responseSchema = null): array
    {
        $payload = [
            'contents' => [[
                'parts' => $parts,
            ]],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => $responseSchema ?? $this->getResponseSchema(),
            ],
        ];

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $this->endpoint . '?key=' . rawurlencode($this->apiKey),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
        ]);

        $response = curl_exec($ch);
        $err = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $err !== '') {
            throw new RuntimeException('AI screening service could not be reached.');
        }

        $decoded = json_decode($response, true);

        if (!is_array($decoded)) {
            throw new RuntimeException('AI screening service returned an invalid response.');
        }

        if ($httpCode >= 400) {
            $message = $decoded['error']['message'] ?? 'AI screening request failed.';
            throw new RuntimeException($message);
        }

        if (!isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
            throw new RuntimeException('AI screening service did not return a usable result.');
        }

        return [
            'text' => $decoded['candidates'][0]['content']['parts'][0]['text'],
        ];
    }

    private function parseEvaluationResponse(array $response): array
    {
        $text = trim((string) ($response['text'] ?? ''));

        if ($text === '') {
            throw new RuntimeException('AI screening returned an empty response.');
        }

        $json = json_decode($text, true);

        if (!is_array($json)) {
            throw new RuntimeException('AI screening returned invalid JSON.');
        }

        $normalized = [
            'overall_score' => $this->normalizeScore($json['overall_score'] ?? 0),
            'identity_match' => $this->normalizeBoolean($json['identity_match'] ?? false),
            'skills_score' => $this->normalizeScore($json['skills_score'] ?? 0),
            'experience_score' => $this->normalizeScore($json['experience_score'] ?? 0),
            'education_score' => $this->normalizeScore($json['education_score'] ?? 0),
            'keyword_score' => $this->normalizeScore($json['keyword_score'] ?? 0),
            'recommendation' => $this->normalizeRecommendation($json['recommendation'] ?? 'Consider'),
            'extracted_skills' => $this->normalizeList($json['extracted_skills'] ?? []),
            'strengths' => $this->normalizeList($json['strengths'] ?? []),
            'concerns' => $this->normalizeList($json['concerns'] ?? []),
            'ai_summary' => $this->normalizeSummary($json['ai_summary'] ?? ''),
        ];

        return $normalized;
    }

    /*
     * Normalises the batch response into one result per document.
     */
    private function parseAcademicDocumentsResponse(array $response): array
    {
        $json = json_decode(
            trim((string) ($response['text'] ?? '')),
            true
        );

        if (
            !is_array($json) ||
            !is_array($json['documents'] ?? null)
        ) {
            throw new RuntimeException(
                'AI academic validation returned invalid JSON.'
            );
        }

        $documents = [];

        foreach ($json['documents'] as $entry) {

            if (!is_array($entry)) {
                continue;
            }

            $documents[] = [
                'file_index' => isset($entry['file_index'])
                    ? (int) $entry['file_index']
                    : null,

                'file_label' => $this->normalizeSummary(
                    (string) ($entry['file_label'] ?? '')
                ),

                'document_valid' => (bool) ($entry['document_valid'] ?? false),

                'identity_match' => $this->normalizeBoolean(
                    $entry['identity_match'] ?? false
                ),

                'confidence_score' => $this->normalizeScore(
                    $entry['confidence_score'] ?? 0
                ),

                'document_type' => $this->normalizeSummary(
                    (string) ($entry['document_type'] ?? '')
                ),

                'institution' => $this->normalizeSummary(
                    (string) ($entry['institution'] ?? '')
                ),

                'degree' => $this->normalizeSummary(
                    (string) ($entry['degree'] ?? '')
                ),

                'field_of_study' => $this->normalizeSummary(
                    (string) ($entry['field_of_study'] ?? '')
                ),

                'graduation_year' => $this->normalizeSummary(
                    (string) ($entry['graduation_year'] ?? '')
                ),

                'extracted_details' => $this->normalizeList(
                    $entry['extracted_details'] ?? []
                ),

                'concerns' => $this->normalizeList(
                    $entry['concerns'] ?? []
                ),

                'ai_summary' => $this->normalizeSummary(
                    (string) ($entry['ai_summary'] ?? '')
                ),
            ];
        }

        if ($documents === []) {
            throw new RuntimeException(
                'AI academic validation returned no document results.'
            );
        }

        return $documents;
    }

    private function normalizeScore(mixed $value): float
    {
        $score = (float) $value;

        if ($score < 0) {
            $score = 0;
        }

        if ($score > 100) {
            $score = 100;
        }

        return round($score, 2);
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
        }

        return (bool) $value;
    }

    private function normalizeRecommendation(string $value): string
    {
        $value = trim($value);
        return in_array($value, self::ALLOWED_RECOMMENDATIONS, true)
            ? $value
            : 'Consider';
    }

    private function normalizeList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $items = [];

        foreach ($value as $item) {
            $text = trim((string) $item);
            if ($text === '') {
                continue;
            }

            $text = preg_replace('/\s+/', ' ', $text) ?? $text;
            $items[] = mb_substr($text, 0, self::MAX_ARRAY_ITEM_LENGTH, 'UTF-8');

            if (count($items) >= self::MAX_ARRAY_ITEMS) {
                break;
            }
        }

        return array_values($items);
    }

    private function normalizeSummary(string $value): string
    {
        $text = preg_replace('/\s+/', ' ', trim($value)) ?? '';
        return mb_substr($text, 0, self::MAX_SUMMARY_LENGTH, 'UTF-8');
    }
}
