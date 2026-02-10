<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class FileUploader
{
    public function __construct(private string $uploadDir)
    {
    }

    /**
     * @return array{filename: string, mime: string|null}
     */
    public function upload(UploadedFile $file): array
    {
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true);
        }

        $mime = null;
        try {
            $mime = $file->getMimeType();
        } catch (\Throwable $e) {
            $mime = $file->getClientMimeType();
        }

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
        $filename = bin2hex(random_bytes(12)) . ($extension ? '.' . $extension : '');

        $file->move($this->uploadDir, $filename);

        return [
            'filename' => $filename,
            'mime' => $mime,
        ];
    }
}
