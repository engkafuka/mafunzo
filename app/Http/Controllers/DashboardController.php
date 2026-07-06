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
        $publishedExamResultsCount = 0;

        $courseStats = null;

        if ($user && $user->role === 'trainee') {
            $currentApplication = TrainingApplication::with('course')
                ->where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->first();

            $nextCourse = Course::acceptingApplications()
                ->orderByDesc('session_year')
                ->orderBy('name')
                ->first();

            $publishedExamResultsCount = TrainingApplication::query()
                ->where('user_id', $user->id)
                ->where('status', 'payment_completed')
                ->whereNotNull('exam_results_published_at')
                ->count();
        }

        if ($user && $user->isAdminOrSuperAdmin()) {
            $courseStats = [
                'total' => Course::count(),
                'active' => Course::where('is_active', true)->count(),
                'published' => Course::where('is_published', true)->count(),
            ];
        }

        return view('dashboard', [
            'currentApplication' => $currentApplication,
            'nextCourse' => $nextCourse,
            'courseStats' => $courseStats,
            'publishedExamResultsCount' => $publishedExamResultsCount,
        ]);
    }
}
