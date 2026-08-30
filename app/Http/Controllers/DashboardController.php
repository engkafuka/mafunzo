<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\TrainingApplication;
use App\Models\WarehouseIdentityCard;
use App\Support\StaffWorkQueue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        // Interview-only accounts land in the interview module, not training dashboard
        if ($user && $user->isInterviewOnly() && $user->canAccessInterviewModule()) {
            return redirect()->route('interview.dashboard');
        }

        $currentApplication = null;
        $nextCourse = null;
        $publishedExamResultsCount = 0;
        $publishedIdentityCardsCount = 0;
        $workQueue = null;

        if ($user && $user->role === 'trainee') {
            // Legacy trained-person records may have course_id null — exclude those from "current course"
            $currentApplication = TrainingApplication::with(['course', 'warehouseIdentityCard', 'user'])
                ->where('user_id', $user->id)
                ->whereNotNull('course_id')
                ->orderByDesc('created_at')
                ->first();

            $nextCourse = Course::acceptingApplications()
                ->orderByDesc('session_year')
                ->orderBy('name')
                ->first();

            $publishedExamResultsCount = TrainingApplication::query()
                ->where('user_id', $user->id)
                ->whereNotNull('course_id')
                ->where('status', 'payment_completed')
                ->whereNotNull('exam_results_published_at')
                ->count();

            $publishedIdentityCardsCount = WarehouseIdentityCard::query()
                ->where('user_id', $user->id)
                ->where('status', 'published')
                ->count();
        }

        if ($user && in_array($user->role, ['super_admin', 'admin', 'staff'], true)) {
            $workQueue = StaffWorkQueue::counts();
        }

        return view('dashboard', [
            'currentApplication' => $currentApplication,
            'nextCourse' => $nextCourse,
            'publishedExamResultsCount' => $publishedExamResultsCount,
            'publishedIdentityCardsCount' => $publishedIdentityCardsCount,
            'workQueue' => $workQueue,
        ]);
    }
}
