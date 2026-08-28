<?php

namespace App\Services;

use RuntimeException;

class AcademicDocumentInputService
{
    private const MAX_FILE_SIZE = 5242880;

    private array $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];

    private array $allowedMimeTypes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
    ];

    public function __construct()
    {
        $this->directory = dirname(__DIR__, 3) . '/storage/uploads/applications';
    }

    private string $directory;

    public function validateUploadedFile(array $file): string
    {
        if (($file['error'] ?? null) !== UPLOAD_ERR_OK) {
            throw new RuntimeException('Academic document upload failed.');
        }

        $path = (string) ($file['tmp_name'] ?? '');
        if ($path === '' || !is_uploaded_file($path)) {
            throw new RuntimeException('Academic document upload is invalid.');
        }

        $size = filesize($path);
        if ($size === false || $size <= 0 || $size > self::MAX_FILE_SIZE) {
            throw new RuntimeException('Academic document must be a non-empty file no larger than 5 MB.');
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $mimeType = strtolower((string) (new \finfo(FILEINFO_MIME_TYPE))->file($path));
        if (!in_array($extension, $this->allowedExtensions, true)
            || !in_array($mimeType, $this->allowedMimeTypes, true)
            || !$this->hasValidSignature($path, $extension)) {
            throw new RuntimeException('Academic document must be a valid PDF, JPG, JPEG, or PNG file.');
        }

        return $extension;
    }

    public function getDocument(array $application): array
    {
        $filename = trim((string) ($application['academic_document_file'] ?? ''));
        if ($filename === '' || str_contains($filename, '/') || str_contains($filename, '\\') || str_contains($filename, "\0") || str_contains($filename, '..')) {
            throw new RuntimeException('No academic document was found for this application.');
        }

        $path = realpath($this->directory . DIRECTORY_SEPARATOR . $filename);
        $directory = realpath($this->directory);
        if ($path === false || $directory === false || dirname($path) !== $directory || !is_readable($path)) {
            throw new RuntimeException('The academic document could not be found.');
        }

        return [
            'path' => $path,
            'filename' => $filename,
            'mime_type' => strtolower((string) (new \finfo(FILEINFO_MIME_TYPE))->file($path)),
        ];
    }

    private function hasValidSignature(string $path, string $extension): bool
    {
        $signature = file_get_contents($path, false, null, 0, 8);
        if (!is_string($signature)) {
            return false;
        }

        return match ($extension) {
            'pdf' => str_starts_with($signature, '%PDF-'),
            'jpg', 'jpeg' => str_starts_with($signature, "\xFF\xD8\xFF"),
            'png' => str_starts_with($signature, "\x89PNG\x0D\x0A\x1A\x0A"),
            default => false,
        };
    }
}