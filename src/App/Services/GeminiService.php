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

    public function evaluateResume(string $resumePath, array $jobPosting): array
    {
        $this->validateConfiguration();

        $resume = $this->validateResume($resumePath);
        $requirements = $this->buildJobRequirements($jobPosting);
        $prompt = $this->buildEvaluationPrompt($requirements);
        $response = $this->sendRequest($resume, $prompt);

        return $this->parseEvaluationResponse($response);
    }

    public function evaluateAcademicDocument(string $documentPath): array
    {
        $this->validateConfiguration();
        $document = $this->validateAcademicDocument($documentPath);
        $prompt = <<<'PROMPT'
You are a certificate verification assistant.

Review the attached diploma, transcript, certificate, or academic record. Determine whether it appears to be a genuine, readable certificate based only on visible content. Do not infer protected personal characteristics. Do not treat this result as a final legal or institutional verification.

Return only a valid JSON object matching the schema. Use an empty string when a field is not visible. List concrete reasons for uncertainty in concerns.
PROMPT;

        return $this->parseAcademicEvaluationResponse($this->sendRequest($document, $prompt, $this->getAcademicResponseSchema()));
    }

    private function getAcademicResponseSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'document_valid' => ['type' => 'BOOLEAN'],
                'confidence_score' => ['type' => 'NUMBER'],
                'document_type' => ['type' => 'STRING'],
                'institution' => ['type' => 'STRING'],
                'degree' => ['type' => 'STRING'],
                'field_of_study' => ['type' => 'STRING'],
                'graduation_year' => ['type' => 'STRING'],
                'extracted_details' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'concerns' => ['type' => 'ARRAY', 'items' => ['type' => 'STRING']],
                'ai_summary' => ['type' => 'STRING'],
            ],
            'required' => ['document_valid', 'confidence_score', 'document_type', 'institution', 'degree', 'field_of_study', 'graduation_year', 'extracted_details', 'concerns', 'ai_summary'],
        ];
    }

    private function getResponseSchema(): array
    {
        return [
            'type' => 'OBJECT',
            'properties' => [
                'overall_score' => ['type' => 'NUMBER'],
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
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
        $allowedMimeTypes = [
            'application/pdf', 'image/jpeg', 'image/png',
        ];

        if (!in_array($extension, $allowedExtensions, true) || !in_array($mimeType, $allowedMimeTypes, true)) {
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

    private function buildEvaluationPrompt(array $jobPosting): string
    {
        $title = $this->sanitizePromptText((string) ($jobPosting['title'] ?? ''), 500);
        $description = $this->sanitizePromptText((string) ($jobPosting['description'] ?? ''), 3000);
        $requirements = $this->sanitizePromptText((string) ($jobPosting['requirements'] ?? ''), 3000);

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
PROMPT;
    }

    private function sendRequest(array $resume, string $prompt, ?array $responseSchema = null): array
    {
        $payload = [
            'contents' => [[
                'parts' => [
                    ['text' => $prompt],
                    [
                        'inline_data' => [
                            'mime_type' => $resume['mimeType'],
                            'data' => base64_encode(file_get_contents($resume['path'])),
                        ],
                    ],
                ],
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

    private function parseAcademicEvaluationResponse(array $response): array
    {
        $json = json_decode(trim((string) ($response['text'] ?? '')), true);
        if (!is_array($json)) {
            throw new RuntimeException('AI academic validation returned invalid JSON.');
        }

        return [
            'document_valid' => (bool) ($json['document_valid'] ?? false),
            'confidence_score' => $this->normalizeScore($json['confidence_score'] ?? 0),
            'document_type' => $this->normalizeSummary($json['document_type'] ?? ''),
            'institution' => $this->normalizeSummary($json['institution'] ?? ''),
            'degree' => $this->normalizeSummary($json['degree'] ?? ''),
            'field_of_study' => $this->normalizeSummary($json['field_of_study'] ?? ''),
            'graduation_year' => $this->normalizeSummary($json['graduation_year'] ?? ''),
            'extracted_details' => $this->normalizeList($json['extracted_details'] ?? []),
            'concerns' => $this->normalizeList($json['concerns'] ?? []),
            'ai_summary' => $this->normalizeSummary($json['ai_summary'] ?? ''),
        ];
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
