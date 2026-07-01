<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\TrainingApplication;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $currentApplication = null;
        $nextCourse = null;

        if ($user && $user->role === 'trainee') {
            $currentApplication = TrainingApplication::with('course')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->first();
            $applications = TrainingApplication::with('course')->where('user_id', $user->id)->orderByDesc('created_at')->get();
            $appliedCourseIds = $applications->pluck('course_id')->unique()->filter();
            $nextCourse = Course::where('is_active', true)
                ->whereNotIn('id', $appliedCourseIds)
                ->orderBy('name')
                ->first();
        }

        return view('dashboard', [
            'currentApplication' => $currentApplication,
            'nextCourse' => $nextCourse,
        ]);
    }
}
