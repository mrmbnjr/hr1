<?php

namespace App\Services;

use RuntimeException;

class ResumeInputService
{
    private string $resumeDirectory;

    private array $allowedExtensions = [
        'pdf',
        'doc',
        'docx',
    ];

    private int $maxFileSize = 5242880;

    public function __construct()
    {
        $this->resumeDirectory = dirname(__DIR__, 3) . '/public/uploads/resumes';
    }

    public function getResume(array $applicantData): array
    {
        if (!isset($applicantData['application_id'])) {
            throw new RuntimeException('Application information is missing.');
        }

        if (!isset($applicantData['resume_file'])) {
            throw new RuntimeException('The applicant resume is missing.');
        }

        $resumeFileName = trim((string) $applicantData['resume_file']);

        if ($resumeFileName === '') {
            throw new RuntimeException('No resume file was found for this application.');
        }

        if (
            str_contains($resumeFileName, '..')
            || str_contains($resumeFileName, '/')
            || str_contains($resumeFileName, '\\')
            || str_contains($resumeFileName, "\0")
        ) {
            throw new RuntimeException('The resume file name is invalid.');
        }

        $extension = strtolower(pathinfo($resumeFileName, PATHINFO_EXTENSION));

        if (!in_array($extension, $this->allowedExtensions, true)) {
            throw new RuntimeException('Unsupported resume format.');
        }

        $candidatePaths = [
            rtrim($this->resumeDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $resumeFileName,
            dirname(__DIR__, 3) . '/public/uploads/applications/' . $resumeFileName,
        ];

        $resumePath = null;

        foreach ($candidatePaths as $candidate) {
            if (is_file($candidate)) {
                $resumePath = $candidate;
                break;
            }
        }

        if ($resumePath === null) {
            throw new RuntimeException('The uploaded resume file could not be found.');
        }

        $realResumePath = realpath($resumePath);
        $realResumeDirectory = realpath(dirname($resumePath));

        if ($realResumePath === false || $realResumeDirectory === false) {
            throw new RuntimeException('The resume path is invalid.');
        }

        $allowedDirectories = [
            realpath(dirname(__DIR__, 3) . '/public/uploads/resumes') ?: '',
            realpath(dirname(__DIR__, 3) . '/public/uploads/applications') ?: '',
        ];

        $allowedDirectories = array_values(array_filter(array_unique($allowedDirectories), static fn ($dir) => $dir !== ''));

        $insideAllowedDirectory = false;

        foreach ($allowedDirectories as $directory) {
            $prefix = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            if (strncmp($realResumePath, $prefix, strlen($prefix)) === 0) {
                $insideAllowedDirectory = true;
                break;
            }
        }

        if (!$insideAllowedDirectory) {
            throw new RuntimeException('The resume file is outside the valid storage directory.');
        }

        $fileSize = filesize($realResumePath);

        if ($fileSize === false || $fileSize <= 0 || $fileSize > $this->maxFileSize) {
            throw new RuntimeException('The resume file is empty or exceeds the 5 MB size limit.');
        }

        return [
            'path' => $realResumePath,
            'filename' => $resumeFileName,
            'mime_type' => $this->detectMimeType($realResumePath),
        ];
    }

    private function detectMimeType(string $filePath): string
    {
        $mimeType = mime_content_type($filePath);

        if (is_string($mimeType) && $mimeType !== '') {
            return $mimeType;
        }

        return $this->fallbackMimeType($filePath);
    }

    private function fallbackMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($extension) {
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            default => 'application/octet-stream',
        };
    }
}
