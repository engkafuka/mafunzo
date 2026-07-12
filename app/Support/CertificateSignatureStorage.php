<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CertificateSignatureStorage
{
    public static function relativePath(): string
    {
        return (string) config('certificate.md_signature_path', 'certificates/md-signature.png');
    }

    public static function exists(): bool
    {
        return Storage::disk('public')->exists(self::relativePath());
    }

    public static function url(): ?string
    {
        if (! self::exists()) {
            return null;
        }

        return Storage::disk('public')->url(self::relativePath());
    }

    public static function store(UploadedFile $file): string
    {
        $path = self::relativePath();
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return $path;
    }
}
