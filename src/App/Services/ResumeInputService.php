<?php

namespace App\Services;

use RuntimeException;

class ResumeInputService
{
    private const MAX_FILE_SIZE = 5242880;

    private string $resumeDirectory;

    private array $allowedExtensions = [
        'pdf',
        'doc',
        'docx',
    ];

    public function __construct()
    {
        $this->resumeDirectory = dirname(__DIR__, 3) . '/storage/uploads/applications';
    }

    public function validateUploadedFile(array $file, string $label = 'Resume'): string
    {
        if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
            if (($file['error'] ?? null) === UPLOAD_ERR_INI_SIZE) {
                throw new RuntimeException($label . ' exceeds the server upload limit.');
            }

            throw new RuntimeException($label . ' upload failed.');
        }

        $temporaryPath = (string) ($file['tmp_name'] ?? '');

        if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
            throw new RuntimeException($label . ' upload is invalid.');
        }

        $fileSize = filesize($temporaryPath);

        if ($fileSize === false || $fileSize <= 0 || $fileSize > self::MAX_FILE_SIZE) {
            throw new RuntimeException($label . ' must be a non-empty file no larger than 5 MB.');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $mimeType = $this->detectMimeType($temporaryPath);

        if (!$this->isValidDocument($temporaryPath, $extension, $mimeType)) {
            throw new RuntimeException($label . ' must be a valid PDF, DOC, or DOCX file.');
        }

        return $extension;
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

        $resumePath = rtrim($this->resumeDirectory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR . $resumeFileName;

        if (!is_file($resumePath)) {
            throw new RuntimeException('The uploaded resume file could not be found.');
        }

        $realResumePath = realpath($resumePath);
        $realResumeDirectory = realpath(dirname($resumePath));

        if ($realResumePath === false || $realResumeDirectory === false) {
            throw new RuntimeException('The resume path is invalid.');
        }

        $allowedDirectories = [realpath($this->resumeDirectory) ?: ''];

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

        if ($fileSize === false || $fileSize <= 0 || $fileSize > self::MAX_FILE_SIZE) {
            throw new RuntimeException('The resume file is empty or exceeds the 5 MB size limit.');
        }

        $mimeType = $this->detectMimeType($realResumePath);

        if (!$this->isValidDocument($realResumePath, $extension, $mimeType)) {
            throw new RuntimeException('The stored resume content is invalid.');
        }

        return [
            'path' => $realResumePath,
            'filename' => $resumeFileName,
            'mime_type' => $mimeType,
        ];
    }

    private function detectMimeType(string $filePath): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($filePath);

        if (is_string($mimeType) && $mimeType !== '') {
            return $mimeType;
        }

        return $this->fallbackMimeType($filePath);
    }

    private function isValidDocument(string $filePath, string $extension, string $mimeType): bool
    {
        $signature = file_get_contents($filePath, false, null, 0, 8);

        if (!is_string($signature)) {
            return false;
        }

        return match ($extension) {
            'pdf' => str_starts_with($signature, '%PDF-') && $mimeType === 'application/pdf',
            'doc' => $signature === "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1"
                && in_array($mimeType, ['application/msword', 'application/CDFV2'], true),
            'docx' => $this->isValidDocx($filePath, $mimeType),
            default => false,
        };
    }

    private function isValidDocx(string $filePath, string $mimeType): bool
    {
        if (!in_array($mimeType, [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
        ], true) || !class_exists('ZipArchive')) {
            return false;
        }

        $archive = new \ZipArchive();

        if ($archive->open($filePath) !== true) {
            return false;
        }

        $hasContentTypes = $archive->locateName('[Content_Types].xml') !== false;
        $hasWordDocument = $archive->locateName('word/document.xml') !== false;
        $archive->close();

        return $hasContentTypes && $hasWordDocument;
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
