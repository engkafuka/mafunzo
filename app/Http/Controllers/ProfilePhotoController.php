<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class ProfilePhotoController extends Controller
{
    public function show(Request $request, User $user): Response
    {
        $authUser = $request->user();
        $canView = $authUser && (
            $authUser->id === $user->id
            || in_array($authUser->role, ['super_admin', 'admin', 'staff'], true)
        );

        if (! $canView || ! $user->profile_photo_path || ! Storage::disk('local')->exists($user->profile_photo_path)) {
            abort(404);
        }

        $path = Storage::disk('local')->path($user->profile_photo_path);
        $ext = strtolower(pathinfo($user->profile_photo_path, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            default => 'application/octet-stream',
        };

        return response()->file($path, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="profile-photo.'.$ext.'"',
        ]);
    }
}
