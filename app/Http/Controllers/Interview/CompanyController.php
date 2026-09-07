<?php

namespace App\Http\Controllers\Interview;

use App\Http\Controllers\Controller;
use App\Models\InterviewCompany;
use App\Support\Interview\InterviewAuditLogger;
use App\Support\PaginationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $query = InterviewCompany::withCount('sessions')->orderBy('name');

        if ($request->filled('q')) {
            $term = '%'.addcslashes($request->string('q')->toString(), '%_\\').'%';
            $query->where(function ($qry) use ($term) {
                $qry->where('name', 'ilike', $term)
                    ->orWhere('registration_number', 'ilike', $term)
                    ->orWhere('contact_person', 'ilike', $term)
                    ->orWhere('contact_email', 'ilike', $term)
                    ->orWhere('contact_phone', 'ilike', $term);
            });
        }

        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            abort_unless(in_array($status, ['active', 'inactive'], true), 404);
            $query->where('status', $status);
        }

        $companies = $query->paginate(PaginationHelper::PER_PAGE)->withQueryString();

        return view('interview.companies.index', [
            'companies' => $companies,
            'filters' => [
                'q' => $request->q,
                'status' => $request->status,
            ],
        ]);
    }

    public function create(): View
    {
        return view('interview.companies.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $company = InterviewCompany::create($data);

        InterviewAuditLogger::log('company_created', 'Interview company created', $request->user(), null, [
            'company_id' => $company->id,
        ]);

        return redirect()->route('interview.companies.index')->with('status', __('Company saved.'));
    }

    public function edit(InterviewCompany $company): View
    {
        return view('interview.companies.edit', compact('company'));
    }

    public function update(Request $request, InterviewCompany $company): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => ['nullable', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $company->update($data);

        return redirect()->route('interview.companies.index')->with('status', __('Company updated.'));
    }
}
