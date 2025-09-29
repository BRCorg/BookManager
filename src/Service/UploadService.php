<?php

namespace App\Service;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class UploadService
{
    private string $uploadDir;
    public function __construct(
        private SluggerInterface $slugger,
        string $publicDir = __DIR__.'/../../public'
    ) {
        $this->uploadDir = rtrim($publicDir, '/').'/uploads/covers';
        (new Filesystem())->mkdir($this->uploadDir);
    }

    // Retourne un chemin relatif à /public (ex: uploads/covers/titre-1696000000.jpg)
    public function uploadCover(UploadedFile $file, string $title): string
    {
        // sécurité supplémentaire (on a déjà la contrainte de formulaire)
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        $mime = $file->getMimeType() ?? '';
        if (!isset($allowed[$mime])) {
            throw new \RuntimeException('Format non autorisé (JPG/PNG uniquement).');
        }

        $slug = strtolower($this->slugger->slug($title));
        $ext  = $allowed[$mime]; // extension propre
        $name = sprintf('%s-%d.%s', $slug, time(), $ext);

        $file->move($this->uploadDir, $name);

        return 'uploads/covers/'.$name; // chemin relatif
    }

    public function delete(?string $relativePath): void
    {
        if (!$relativePath) return;
        $abs = $this->uploadDir.'/'.basename($relativePath);
        $fs = new Filesystem();
        if ($fs->exists($abs)) {
            $fs->remove($abs);
        }
    }
}
