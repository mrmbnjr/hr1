<?php

namespace App\Services;

use RuntimeException;

/**
 * Storage for documents an employee uploads against an assigned request.
 *
 * Validation is NOT re-implemented here: the rules (PDF / DOC / DOCX, max
 * 5 MB, signature + MIME checks) already live in ResumeInputService, so
 * this service composes it and only adds the assigned-request storage
 * directory and the safe path handling a download needs.
 */
class AssignedDocumentService
{
    private const DIRECTORY = '/storage/uploads/assigned-requests';

    private ResumeInputService $validator;

    private string $directory;

    public function __construct()
    {
        $this->validator = new ResumeInputService();
        $this->directory = dirname(__DIR__, 3) . self::DIRECTORY;
    }


    /**
     * Validate an uploaded file, move it into storage and return its
     * metadata for the database.
     *
     * @return array{stored_name:string,original_name:string,size:int,mime:string,extension:string}
     */
    public function store(array $file, int $documentId): array
    {
        /*
         * Reuse the shared validation rules. Throws RuntimeException with
         * a human-readable message when the file is not acceptable.
         */
        $extension = $this->validator->validateUploadedFile(
            $file,
            'Document'
        );

        $this->ensureDirectory();

        $originalName = basename((string) ($file['name'] ?? 'document'));

        $storedName = sprintf(
            'onboarding_document_%d_%s.%s',
            $documentId,
            bin2hex(random_bytes(8)),
            $extension
        );

        $destination = $this->directory
            . DIRECTORY_SEPARATOR
            . $storedName;

        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            throw new RuntimeException(
                'The document could not be saved. Please try again.'
            );
        }

        $size = filesize($destination);

        return [
            'stored_name' => $storedName,
            'original_name' => $originalName,
            'size' => $size === false ? 0 : (int) $size,
            'mime' => $this->detectMimeType($destination),
            'extension' => $extension,
        ];
    }


    /**
     * Resolve a stored file name to an absolute, verified path.
     *
     * Returns null when the name is unsafe or the file no longer exists,
     * so callers never stream an arbitrary path.
     */
    public function resolve(string $storedName): ?string
    {
        $storedName = trim($storedName);

        if (
            $storedName === ''
            || str_contains($storedName, '..')
            || str_contains($storedName, '/')
            || str_contains($storedName, '\\')
            || str_contains($storedName, "\0")
        ) {
            return null;
        }

        $path = $this->directory
            . DIRECTORY_SEPARATOR
            . $storedName;

        if (!is_file($path)) {
            return null;
        }

        $realPath = realpath($path);
        $realDirectory = realpath($this->directory);

        if ($realPath === false || $realDirectory === false) {
            return null;
        }

        $prefix = rtrim($realDirectory, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR;

        if (strncmp($realPath, $prefix, strlen($prefix)) !== 0) {
            return null;
        }

        return $realPath;
    }


    /**
     * Remove a previously stored file. Used when an employee replaces
     * their submission so replaced files do not accumulate on disk.
     */
    public function delete(?string $storedName): void
    {
        if ($storedName === null || $storedName === '') {
            return;
        }

        $path = $this->resolve($storedName);

        if ($path !== null) {
            @unlink($path);
        }
    }


    /**
     * Human-readable file size, e.g. "1.2 MB".
     */
    public static function formatSize(?int $bytes): string
    {
        if ($bytes === null || $bytes <= 0) {
            return '—';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        $value = (float) $bytes;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return $index === 0
            ? $bytes . ' B'
            : number_format($value, 1) . ' ' . $units[$index];
    }


    private function ensureDirectory(): void
    {
        if (!is_dir($this->directory)) {
            mkdir($this->directory, 0775, true);
        }
    }


    private function detectMimeType(string $filePath): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($filePath);

        return is_string($mime) && $mime !== ''
            ? $mime
            : 'application/octet-stream';
    }
}
