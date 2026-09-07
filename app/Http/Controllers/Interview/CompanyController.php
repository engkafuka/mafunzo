<?php

namespace App\Http\Controllers\Interview;

use App\Http\Controllers\Controller;
use App\Models\InterviewCompany;
use App\Support\Interview\InterviewAuditLogger;
use App\Support\PaginationHelper;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
        $data = $this->validatedCompany($request);

        $company = InterviewCompany::create($data);

        InterviewAuditLogger::log('company_created', 'Interview company created', $request->user(), null, [
            'company_id' => $company->id,
            'registration_number' => $company->registration_number,
        ]);

        return redirect()->route('interview.companies.index')->with('status', __('Company saved.'));
    }

    public function edit(InterviewCompany $company): View
    {
        $company->loadCount('sessions');

        return view('interview.companies.edit', compact('company'));
    }

    public function update(Request $request, InterviewCompany $company): RedirectResponse
    {
        $data = $this->validatedCompany($request, $company);

        $company->update($data);

        InterviewAuditLogger::log('company_updated', 'Interview company updated', $request->user(), null, [
            'company_id' => $company->id,
            'registration_number' => $company->registration_number,
        ]);

        return redirect()->route('interview.companies.index')->with('status', __('Company updated.'));
    }

    public function destroy(Request $request, InterviewCompany $company): RedirectResponse
    {
        $company->loadCount('sessions');

        if (! $company->canBeDeleted()) {
            return back()->with('error', __('This company has interview sessions. Deactivate it instead of deleting.'));
        }

        $meta = [
            'company_id' => $company->id,
            'name' => $company->name,
            'registration_number' => $company->registration_number,
        ];

        $company->delete();

        InterviewAuditLogger::log('company_deleted', 'Interview company permanently deleted', $request->user(), null, $meta);

        return redirect()
            ->route('interview.companies.index')
            ->with('status', __('Company deleted.'));
    }

    public function deactivate(Request $request, InterviewCompany $company): RedirectResponse
    {
        if ($company->status === 'inactive') {
            return back()->with('status', __('Company is already inactive.'));
        }

        $company->update(['status' => 'inactive']);

        InterviewAuditLogger::log('company_deactivated', 'Interview company deactivated', $request->user(), null, [
            'company_id' => $company->id,
            'registration_number' => $company->registration_number,
        ]);

        return back()->with('status', __('Company deactivated. It will no longer appear when scheduling new sessions.'));
    }

    /**
     * @return array{name: string, registration_number: string, contact_person: ?string, contact_email: ?string, contact_phone: ?string, status: string}
     */
    private function validatedCompany(Request $request, ?InterviewCompany $company = null): array
    {
        $request->merge([
            'name' => InterviewCompany::normalizeName($request->input('name')),
            'registration_number' => InterviewCompany::normalizeRegistrationNumber(
                $request->input('registration_number')
            ),
        ]);

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'registration_number' => [
                'required',
                'string',
                'max:255',
                Rule::unique('interview_companies', 'registration_number')->ignore($company?->id),
            ],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive'],
        ], [
            'registration_number.unique' => __('A company with this registration number already exists.'),
            'registration_number.required' => __('Registration number is required to prevent duplicate companies.'),
        ]);
    }
}
