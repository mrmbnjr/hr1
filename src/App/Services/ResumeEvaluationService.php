<?php

namespace App\Services;

use Core\Database;
use PDO;
use RuntimeException;

class ResumeEvaluationService
{
    private GeminiService $gemini;
    private ResumeInputService $resumeInput;
    private PDO $db;

    public function __construct()
    {
        $this->gemini = new GeminiService();
        $this->resumeInput = new ResumeInputService();
        $this->db = Database::connection();
    }

    public function evaluate(int $applicationId): array
    {
        if ($applicationId <= 0) {
            throw new RuntimeException('Invalid application ID.');
        }

        $application = $this->getApplicationData($applicationId);

        if (!$application) {
            throw new RuntimeException('Application not found.');
        }

        $resume = $this->resumeInput->getResume($application);

        if (trim((string) $resume['path']) === '') {
            throw new RuntimeException('No resume was provided.');
        }

        $requirements = trim((string) ($application['requirements'] ?? ''));

        if ($requirements === '') {
            throw new RuntimeException('The job requirements are missing.');
        }

        $jobPosting = [
            'title' => (string) ($application['title'] ?? ''),
            'description' => (string) ($application['description'] ?? ''),
            'requirements' => $requirements,
        ];

        $evaluation = $this->gemini->evaluateResume($resume['path'], $jobPosting);
        $evaluation = $this->validateAndNormalizeResult($evaluation);
        $this->saveScreeningResult($applicationId, $evaluation);

        return $evaluation;
    }

    private function getApplicationData(int $applicationId): ?array
    {
        $sql = "
            SELECT
                a.application_id,
                a.applicant_id,
                a.resume_file,
                jp.title,
                jp.description,
                jp.requirements,
                jp.posting_id
            FROM applications a
            INNER JOIN job_postings jp
                ON jp.posting_id = a.posting_id
            WHERE a.application_id = :application_id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':application_id' => $applicationId]);

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result ?: null;
    }

    private function validateAndNormalizeResult(mixed $evaluation): array
    {
        if (!is_array($evaluation)) {
            throw new RuntimeException('AI screening returned an invalid result.');
        }

        $scoreFields = [
            'overall_score',
            'skills_score',
            'experience_score',
            'education_score',
            'keyword_score',
        ];

        foreach ($scoreFields as $field) {
            $value = (float) ($evaluation[$field] ?? 0);

            if ($value < 0) {
                $value = 0;
            }

            if ($value > 100) {
                $value = 100;
            }

            $evaluation[$field] = round($value, 2);
        }

        $allowedRecommendations = [
            'Highly Recommended',
            'Recommended',
            'Consider',
            'Not Recommended',
        ];

        $recommendation = trim((string) ($evaluation['recommendation'] ?? 'Consider'));

        if (!in_array($recommendation, $allowedRecommendations, true)) {
            $recommendation = 'Consider';
        }

        $evaluation['recommendation'] = $recommendation;
        $evaluation['extracted_skills'] = $this->normalizeList($evaluation['extracted_skills'] ?? []);
        $evaluation['strengths'] = $this->normalizeList($evaluation['strengths'] ?? []);
        $evaluation['concerns'] = $this->normalizeList($evaluation['concerns'] ?? []);
        $evaluation['ai_summary'] = trim((string) ($evaluation['ai_summary'] ?? ''));

        return $evaluation;
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

            $items[] = preg_replace('/\s+/', ' ', $text) ?? $text;
        }

        return $items;
    }

    private function saveScreeningResult(int $applicationId, array $evaluation): void
    {
        $sql = "
            INSERT INTO ai_screening (
                application_id,
                overall_score,
                skills_score,
                experience_score,
                education_score,
                keyword_score,
                recommendation,
                extracted_skills,
                strengths,
                concerns,
                ai_summary
            ) VALUES (
                :application_id,
                :overall_score,
                :skills_score,
                :experience_score,
                :education_score,
                :keyword_score,
                :recommendation,
                :extracted_skills,
                :strengths,
                :concerns,
                :ai_summary
            )
            ON DUPLICATE KEY UPDATE
                overall_score = VALUES(overall_score),
                skills_score = VALUES(skills_score),
                experience_score = VALUES(experience_score),
                education_score = VALUES(education_score),
                keyword_score = VALUES(keyword_score),
                recommendation = VALUES(recommendation),
                extracted_skills = VALUES(extracted_skills),
                strengths = VALUES(strengths),
                concerns = VALUES(concerns),
                ai_summary = VALUES(ai_summary),
                processed_at = CURRENT_TIMESTAMP
        ";

        $stmt = $this->db->prepare($sql);

        $stmt->execute([
            ':application_id' => $applicationId,
            ':overall_score' => $evaluation['overall_score'],
            ':skills_score' => $evaluation['skills_score'],
            ':experience_score' => $evaluation['experience_score'],
            ':education_score' => $evaluation['education_score'],
            ':keyword_score' => $evaluation['keyword_score'],
            ':recommendation' => $evaluation['recommendation'],
            ':extracted_skills' => json_encode($evaluation['extracted_skills']),
            ':strengths' => json_encode($evaluation['strengths']),
            ':concerns' => json_encode($evaluation['concerns']),
            ':ai_summary' => $evaluation['ai_summary'],
        ]);
    }
}
