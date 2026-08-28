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

    public function evaluate(int $applicationId): array
    {
        if ($applicationId <= 0) {
            throw new RuntimeException('Invalid application ID.');
        }

        $stmt = $this->db->prepare('SELECT academic_document_file FROM applications WHERE application_id = :id LIMIT 1');
        $stmt->execute([':id' => $applicationId]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$application) {
            throw new RuntimeException('Application not found.');
        }

        $document = $this->documentInput->getDocument($application);
        $evaluation = $this->gemini->evaluateAcademicDocument($document['path']);
        $this->save($applicationId, $evaluation);

        return $evaluation;
    }

    private function save(int $applicationId, array $evaluation): void
    {
        $stmt = $this->db->prepare('INSERT INTO academic_document_evaluations (application_id, document_valid, confidence_score, document_type, institution, degree, field_of_study, graduation_year, extracted_details, concerns, ai_summary) VALUES (:application_id, :document_valid, :confidence_score, :document_type, :institution, :degree, :field_of_study, :graduation_year, :extracted_details, :concerns, :ai_summary) ON DUPLICATE KEY UPDATE document_valid = VALUES(document_valid), confidence_score = VALUES(confidence_score), document_type = VALUES(document_type), institution = VALUES(institution), degree = VALUES(degree), field_of_study = VALUES(field_of_study), graduation_year = VALUES(graduation_year), extracted_details = VALUES(extracted_details), concerns = VALUES(concerns), ai_summary = VALUES(ai_summary), processed_at = CURRENT_TIMESTAMP');
        $stmt->execute([
            ':application_id' => $applicationId,
            ':document_valid' => $evaluation['document_valid'] ? 1 : 0,
            ':confidence_score' => $evaluation['confidence_score'],
            ':document_type' => $evaluation['document_type'],
            ':institution' => $evaluation['institution'],
            ':degree' => $evaluation['degree'],
            ':field_of_study' => $evaluation['field_of_study'],
            ':graduation_year' => $evaluation['graduation_year'],
            ':extracted_details' => json_encode($evaluation['extracted_details']),
            ':concerns' => json_encode($evaluation['concerns']),
            ':ai_summary' => $evaluation['ai_summary'],
        ]);
    }
}