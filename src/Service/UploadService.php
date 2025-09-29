<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadService
{
    public function __construct(
        private readonly SluggerInterface $slugger,
        private readonly string $uploadsDir = 'uploads/covers'
    ) {}

    public function uploadCover(UploadedFile $file, string $baseName): string
    {
        $mime = $file->getMimeType();
        if (!in_array($mime, ['image/jpeg', 'image/png'], true)) {
            throw new \RuntimeException('Type de fichier interdit');
        }

        $safe = strtolower($this->slugger->slug($baseName)->toString());
        $ext  = $file->guessExtension() ?: 'jpg';
        $name = sprintf('%s-%d.%s', $safe, time(), $ext);

        $target = dirname(__DIR__, 2) . '/public/' . $this->uploadsDir;
        if (!is_dir($target)) {
            @mkdir($target, 0775, true);
        }

        $file->move($target, $name);

        return $this->uploadsDir . '/' . $name; // chemin relatif (stocké en DB)
    }

    public function delete(?string $relativePath): void
    {
        if (!$relativePath) return;
        $full = dirname(__DIR__, 2) . '/public/' . ltrim($relativePath, '/');
        if (is_file($full)) {
            @unlink($full);
        }
    }
}
