<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Support\PaginationHelper;
use App\Support\TrainedUsersReport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(): View
    {
        return view('reports.index');
    }

    public function trainedUsers(Request $request): View
    {
        $columns = TrainedUsersReport::resolveColumns($request->input('columns'));
        $availableColumns = TrainedUsersReport::availableColumns();

        $applications = TrainedUsersReport::query($request)
            ->paginate(PaginationHelper::PER_PAGE)
            ->withQueryString();

        $courses = Course::orderByDesc('session_year')->orderBy('name')->get();
        $sessionYears = Course::query()
            ->whereNotNull('session_year')
            ->distinct()
            ->orderByDesc('session_year')
            ->pluck('session_year');

        $regions = TrainedUsersReport::query(new Request())
            ->reorder()
            ->whereNotNull('region')
            ->where('region', '!=', '')
            ->distinct()
            ->orderBy('region')
            ->pluck('region');

        return view('reports.trained-users', compact(
            'applications',
            'courses',
            'sessionYears',
            'regions',
            'columns',
            'availableColumns',
        ));
    }

    public function trainedUsersExport(Request $request): StreamedResponse
    {
        $request->validate([
            'columns' => ['nullable', 'array'],
            'columns.*' => ['string'],
            'course_id' => ['nullable', 'exists:courses,id'],
            'session_year' => ['nullable', 'integer'],
            'gender' => ['nullable', 'in:male,female,other'],
            'region' => ['nullable', 'string', 'max:255'],
            'certificate_status' => ['nullable', 'in:issued,not_issued'],
            'id_card_status' => ['nullable', 'in:none,draft,published,revoked'],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
        ]);

        $columns = TrainedUsersReport::resolveColumns($request->input('columns'));

        return TrainedUsersReport::exportExcel($request, $columns);
    }
}
