<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfilePhotoStorage
{
    public static function rules(bool $required = true): array
    {
        $rules = ['file', 'image', 'mimes:jpg,jpeg,png', 'max:2048', 'dimensions:min_width=200,min_height=200'];

        return $required ? array_merge(['required'], $rules) : array_merge(['nullable'], $rules);
    }

    public static function storeForUser(User $user, UploadedFile $file): string
    {
        if ($user->profile_photo_path) {
            Storage::disk('local')->delete($user->profile_photo_path);
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $directory = 'profile-photos/'.$user->id;

        // Prefer JPEG on disk so PDF generation works without GD later.
        if (extension_loaded('gd') && in_array($extension, ['png', 'gif', 'webp', 'jpg', 'jpeg'], true)) {
            $absolute = $file->getRealPath();
            if (is_string($absolute) && $absolute !== '') {
                $dataUri = PdfImageDataUri::jpegForPdf($absolute);
                if (is_string($dataUri) && str_starts_with($dataUri, 'data:image/jpeg')) {
                    $raw = base64_decode(substr($dataUri, strpos($dataUri, ',') + 1), true);
                    if ($raw !== false) {
                        $destination = $directory.'/photo.jpg';
                        Storage::disk('local')->put($destination, $raw);

                        return $destination;
                    }
                }
            }
        }

        // Without GD, keep JPEG uploads as JPEG; PNG is stored as PNG and embedded directly in PDFs.
        $storeAs = in_array($extension, ['jpg', 'jpeg'], true) ? 'jpg' : $extension;

        return $file->storeAs($directory, 'photo.'.$storeAs, 'local');
    }

    public static function copySnapshot(User $user, int $identityCardId): string
    {
        if (! $user->profile_photo_path || ! Storage::disk('local')->exists($user->profile_photo_path)) {
            throw new \RuntimeException(__('Profile photo is missing.'));
        }

        $sourcePath = Storage::disk('local')->path($user->profile_photo_path);
        $dataUri = PdfImageDataUri::dataUriForPdf($sourcePath);

        if ($dataUri === null) {
            throw new \RuntimeException(__('Profile photo could not be prepared for the identity card PDF.'));
        }

        $comma = strpos($dataUri, ',');
        $raw = $comma === false ? false : base64_decode(substr($dataUri, $comma + 1), true);

        if ($raw === false) {
            throw new \RuntimeException(__('Profile photo could not be prepared for the identity card PDF.'));
        }

        $destExt = str_starts_with($dataUri, 'data:image/png') ? 'png' : 'jpg';
        $destination = 'identity-cards/photos/'.$identityCardId.'.'.$destExt;
        Storage::disk('local')->put($destination, $raw);

        return $destination;
    }
}
