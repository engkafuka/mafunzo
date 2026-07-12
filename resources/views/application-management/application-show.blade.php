<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Application') }} — {{ $application->registration_number ?? $application->control_number }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mb-4">
                <a href="{{ route('app-management.applications') }}" class="text-indigo-600 hover:text-indigo-800">{{ __('&larr; Back to list') }}</a>
            </div>

            @php
                $applicantDocs = $application->user && $application->user->educationBackgrounds ? $application->user->educationBackgrounds->filter(fn($eb) => $eb->certificate_path) : collect();
            @endphp

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {{-- Left column: Applicant information --}}
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-3 bg-gray-50 border-b flex items-center justify-between gap-3">
                        <h3 class="font-medium text-gray-900">{{ __('Applicant information') }}</h3>
                        <button type="button" onclick="document.getElementById('all-documents-modal').classList.remove('hidden'); document.body.classList.add('overflow-hidden');"
                                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                            {{ __('View documents') }}
                        </button>
                    </div>
                    <div class="p-6">
                        <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                            <div><dt class="text-sm font-medium text-gray-500">{{ __('Name') }}</dt><dd class="mt-0.5">{{ $application->first_name }} {{ $application->middle_name ?? '' }} {{ $application->last_name }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">{{ __('Email') }}</dt><dd class="mt-0.5">{{ $application->email }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">{{ __('Phone') }}</dt><dd class="mt-0.5">{{ $application->phone ?? $application->user->phone ?? '—' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">{{ __('Region') }}</dt><dd class="mt-0.5">{{ $application->region ?? '—' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">{{ __('District') }}</dt><dd class="mt-0.5">{{ $application->district ?? '—' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">{{ __('Company / Private') }}</dt><dd class="mt-0.5">{{ $application->company_or_private ?? '—' }}</dd></div>
                            @if($application->company_name)
                                <div class="sm:col-span-2"><dt class="text-sm font-medium text-gray-500">{{ __('Company name') }}</dt><dd class="mt-0.5">{{ $application->company_name }}</dd></div>
                                <div class="sm:col-span-2"><dt class="text-sm font-medium text-gray-500">{{ __('Company address') }}</dt><dd class="mt-0.5">{{ $application->company_address ?? '—' }}</dd></div>
                            @endif
                            <div><dt class="text-sm font-medium text-gray-500">{{ __('Gender') }}</dt><dd class="mt-0.5">{{ $application->gender ? __(ucfirst($application->gender)) : '—' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">{{ __('Date of birth') }}</dt><dd class="mt-0.5">{{ $application->date_of_birth?->format('Y-m-d') ?? '—' }}</dd></div>
                            <div><dt class="text-sm font-medium text-gray-500">{{ __('Position') }}</dt><dd class="mt-0.5">{{ \App\Models\TrainingApplication::positionLabel($application->position) ?? '—' }}</dd></div>
                        </dl>
                        <div class="mt-4 pt-4 border-t border-gray-200 space-y-1">
                            <div><span class="text-sm font-medium text-gray-500">{{ __('Course') }}:</span> {{ $application->course->name }}</div>
                            <div><span class="text-sm font-medium text-gray-500">{{ __('Control number') }}:</span> {{ $application->control_number ?? '—' }}</div>
                            <div><span class="text-sm font-medium text-gray-500">{{ __('Registration number') }}:</span> {{ $application->registration_number ?? '—' }}</div>
                            <div><span class="text-sm font-medium text-gray-500">{{ __('Application review') }}:</span> <span class="capitalize">{{ $application->application_review_status }}</span></div>
                            <div><span class="text-sm font-medium text-gray-500">{{ __('Account verified') }}:</span> {{ $application->account_verified_at ? $application->account_verified_at->format('Y-m-d H:i') : 'No' }}</div>
                            <div><span class="text-sm font-medium text-gray-500">{{ __('Payment verified') }}:</span> {{ $application->payment_verified_at ? $application->payment_verified_at->format('Y-m-d H:i') : 'No' }}</div>
                        </div>
                    </div>
                </div>

                {{-- Right column: Education background + Actions --}}
                <div class="space-y-6">
            {{-- Education background with certificate preview --}}
            @if($application->user && $application->user->educationBackgrounds->isNotEmpty())
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <h3 class="px-6 py-3 bg-gray-50 border-b font-medium text-gray-900">{{ __('Education background') }}</h3>
                    <div class="p-6 space-y-4">
                        @foreach($application->user->educationBackgrounds as $eb)
                            <div class="border border-gray-200 rounded-lg p-4 flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <p class="font-medium text-gray-900">{{ __(\App\Models\EducationBackground::levelOptions()[$eb->level] ?? $eb->level) }}</p>
                                    <p class="text-sm text-gray-600">{{ $eb->program === 'others' ? ($eb->program_other ?? 'Others') : __(ucfirst($eb->program)) }} · {{ $eb->institution }}</p>
                                </div>
                                @if($eb->certificate_path)
                                    @php
                                        $certUrl = route('trainee.profile.certificate', $eb);
                                        $isPdf = in_array(strtolower(pathinfo($eb->certificate_path, PATHINFO_EXTENSION)), ['pdf']);
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <button type="button" onclick="document.getElementById('preview-{{ $eb->id }}').classList.remove('hidden'); document.body.classList.add('overflow-hidden');"
                                                class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                            {{ __('Preview') }} {{ __('document') }}
                                        </button>
                                        <a href="{{ $certUrl }}" target="_blank" rel="noopener noreferrer"
                                           class="inline-flex items-center px-3 py-1.5 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                                            {{ __('Open in new tab') }}
                                        </a>
                                    </div>
                                    {{-- Modal: PDF/document preview --}}
                                    <div id="preview-{{ $eb->id }}" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                                        <div class="flex min-h-full items-center justify-center p-4">
                                            <div class="fixed inset-0 bg-black/60" onclick="document.getElementById('preview-{{ $eb->id }}').classList.add('hidden'); document.body.classList.remove('overflow-hidden');"></div>
                                            <div class="relative bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col">
                                                <div class="flex items-center justify-between p-3 border-b">
                                                    <span class="font-medium text-gray-900">{{ __('Document preview') }} — {{ $eb->institution }}</span>
                                                    <button type="button" onclick="document.getElementById('preview-{{ $eb->id }}').classList.add('hidden'); document.body.classList.remove('overflow-hidden');"
                                                            class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                                                </div>
                                                <div class="flex-1 min-h-0 p-2">
                                                    @if($isPdf)
                                                        <iframe src="{{ $certUrl }}#toolbar=1" class="w-full h-[75vh] rounded border border-gray-200" title="{{ __('Certificate') }}"></iframe>
                                                    @else
                                                        <img src="{{ $certUrl }}" alt="{{ __('Certificate') }}" class="max-w-full max-h-[75vh] mx-auto block rounded border border-gray-200" />
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-sm text-amber-600">{{ __('No certificate attached') }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @else
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                    <h3 class="px-6 py-3 bg-gray-50 border-b font-medium text-gray-900">{{ __('Education background') }}</h3>
                    <div class="p-6 text-gray-500 text-sm">{{ __('Applicant has not added education background yet.') }}</div>
                </div>
            @endif

                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <h3 class="px-6 py-3 bg-gray-50 border-b font-medium text-gray-900">{{ __('Actions') }}</h3>
                        <div class="px-4 sm:px-6 py-4 bg-gray-50 border-t form-actions">
                            @if($application->canBeReviewedByStaff())
                                <form method="POST" action="{{ route('app-management.applications.review', $application) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="action" value="approve">
                                    <x-primary-button type="submit">{{ __('Approve application') }}</x-primary-button>
                                </form>
                                <form method="POST" action="{{ route('app-management.applications.review', $application) }}" class="inline">
                                    @csrf
                                    <input type="hidden" name="action" value="reject">
                                    <x-danger-button type="submit">{{ __('Reject application') }}</x-danger-button>
                                </form>
                            @endif
                            @if($application->needsAccountVerification())
                                <form method="POST" action="{{ route('app-management.applications.verify-account', $application) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded-md hover:bg-amber-700 text-sm">{{ __('Verify account') }}</button>
                                </form>
                            @endif
                            @if($application->needsPaymentVerification())
                                <form method="POST" action="{{ route('app-management.applications.verify-payment', $application) }}" class="inline">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 text-sm">{{ __('Verify payment') }}</button>
                                </form>
                            @endif
                            @if($application->needsAccountVerification() || $application->needsPaymentVerification())
                                <form method="POST" action="{{ route('app-management.applications.verify-payment-package', $application) }}" class="inline"
                                      onsubmit="return confirm('{{ __('Confirm that both the account and payment are verified?') }}');">
                                    @csrf
                                    <button type="submit" class="px-4 py-2 bg-[#0a71ab] text-white rounded-md hover:bg-[#086090] text-sm">{{ __('Approve payment package') }}</button>
                                </form>
                            @endif
                            @if(!$application->canBeReviewedByStaff() && !$application->needsAccountVerification() && !$application->needsPaymentVerification())
                                <p class="text-sm text-gray-500">{{ __('No pending actions. Application review, account, and payment are complete.') }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal: All applicant documents (outside grid) --}}
            <div id="all-documents-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-modal="true">
                <div class="flex min-h-full items-center justify-center p-4">
                    <div class="fixed inset-0 bg-black/60" onclick="document.getElementById('all-documents-modal').classList.add('hidden'); document.body.classList.remove('overflow-hidden');"></div>
                    <div class="relative bg-white rounded-lg shadow-xl max-w-5xl w-full max-h-[90vh] flex flex-col">
                        <div class="flex items-center justify-between p-3 border-b shrink-0">
                            <span class="font-medium text-gray-900">{{ __('Applicant documents') }}</span>
                            <button type="button" onclick="document.getElementById('all-documents-modal').classList.add('hidden'); document.body.classList.remove('overflow-hidden');"
                                    class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
                        </div>
                        @if($applicantDocs->isNotEmpty())
                            <div class="flex flex-1 min-h-0 overflow-hidden">
                                <div class="w-56 shrink-0 border-r border-gray-200 overflow-y-auto bg-gray-50">
                                    @foreach($applicantDocs as $eb)
                                        @php
                                            $certUrl = route('trainee.profile.certificate', $eb);
                                        @endphp
                                        <button type="button" onclick="var panel=document.getElementById('all-documents-modal').querySelector('.doc-preview-panel'); Array.from(panel.children).forEach(function(el){ el.style.display='none'; }); var frame=document.getElementById('doc-frame-{{ $eb->id }}'); if(frame){ frame.style.display='block'; var ifr=frame.querySelector('iframe'); if(ifr&&!ifr.src){ ifr.src=ifr.getAttribute('data-src')||''; } var img=frame.querySelector('img'); if(img&&!img.src){ img.src=img.getAttribute('data-src')||''; } }"
                                                class="w-full text-left px-4 py-3 text-sm border-b border-gray-200 hover:bg-white focus:bg-white focus:outline-none">
                                            {{ __(\App\Models\EducationBackground::levelOptions()[$eb->level] ?? $eb->level) }} — {{ Str::limit($eb->institution, 22) }}
                                        </button>
                                    @endforeach
                                </div>
                                <div class="flex-1 min-w-0 p-4 doc-preview-panel flex items-center justify-center bg-gray-100 overflow-auto">
                                    @foreach($applicantDocs as $index => $eb)
                                        @php
                                            $certUrl = route('trainee.profile.certificate', $eb);
                                            $isPdf = in_array(strtolower(pathinfo($eb->certificate_path, PATHINFO_EXTENSION)), ['pdf']);
                                        @endphp
                                        <div id="doc-frame-{{ $eb->id }}" class="w-full h-[70vh] min-h-[300px] rounded border border-gray-200 bg-white overflow-hidden shrink-0" style="display: {{ $index === 0 ? 'block' : 'none' }};">
                                            @if($isPdf)
                                                <iframe data-src="{{ $certUrl }}#toolbar=1" src="{{ $index === 0 ? $certUrl . '#toolbar=1' : '' }}" class="w-full h-full" title="{{ $eb->institution }}"></iframe>
                                            @else
                                                <img data-src="{{ $certUrl }}" src="{{ $index === 0 ? $certUrl : '' }}" alt="{{ $eb->institution }}" class="max-w-full max-h-full mx-auto block object-contain" />
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <div class="flex-1 p-8 flex items-center justify-center text-gray-500">
                                {{ __('No documents attached.') }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
