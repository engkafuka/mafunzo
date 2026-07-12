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

        return $file->storeAs(
            'profile-photos/'.$user->id,
            'photo.'.$extension,
            'local',
        );
    }

    public static function copySnapshot(User $user, int $identityCardId): string
    {
        if (! $user->profile_photo_path || ! Storage::disk('local')->exists($user->profile_photo_path)) {
            throw new \RuntimeException(__('Profile photo is missing.'));
        }

        $sourcePath = Storage::disk('local')->path($user->profile_photo_path);
        $jpegDataUri = PdfImageDataUri::jpegForPdf($sourcePath);

        if ($jpegDataUri === null) {
            throw new \RuntimeException(__('Profile photo could not be prepared for the identity card PDF.'));
        }

        $destination = 'identity-cards/photos/'.$identityCardId.'.jpg';
        $raw = base64_decode(substr($jpegDataUri, strpos($jpegDataUri, ',') + 1), true);

        if ($raw === false) {
            throw new \RuntimeException(__('Profile photo could not be prepared for the identity card PDF.'));
        }

        Storage::disk('local')->put($destination, $raw);

        return $destination;
    }
}
