<?php

namespace App\Support;

use App\Models\Course;
use App\Models\CourseMaterial;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseMaterialStorage
{
    public static function store(Course $course, UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: 'bin');
        $basename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $basename = $basename !== '' ? $basename : 'material';
        $path = sprintf(
            'course-materials/%d/%s-%s.%s',
            $course->id,
            $basename,
            now()->format('YmdHis'),
            $extension
        );

        Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));

        return [
            'file_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ];
    }

    public static function replace(CourseMaterial $material, UploadedFile $file): array
    {
        self::delete($material->file_path);

        return self::store($material->course, $file);
    }

    public static function delete(?string $path): void
    {
        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    public static function exists(?string $path): bool
    {
        return $path !== null && Storage::disk('local')->exists($path);
    }

    public static function absolutePath(?string $path): ?string
    {
        if (! self::exists($path)) {
            return null;
        }

        return Storage::disk('local')->path($path);
    }
}
