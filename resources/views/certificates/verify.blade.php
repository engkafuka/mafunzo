<x-guest-layout>
    <div class="page-shell py-8">
        <div class="page-inner-md mx-auto">
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 bg-[#0a71ab] text-white text-center">
                    <h1 class="text-lg font-semibold">{{ config('certificate.organization_line_1') }} {{ config('certificate.organization_line_2') }}</h1>
                    <p class="text-sm text-white/90 mt-1">{{ __('Training certificate verification') }}</p>
                </div>

                <div class="p-6 space-y-4">
                    @if($isIssued)
                        <div class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-900">
                            <p class="font-semibold">{{ __('Valid training certificate') }}</p>
                            <p class="text-sm mt-1">{{ __('This completion certificate was issued by WRRB.') }}</p>
                        </div>
                    @else
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-amber-950">
                            <p class="font-semibold">{{ __('Trainee record verified') }}</p>
                            <p class="text-sm mt-1">{{ __('Examination passed. Certificate issue date has not been recorded yet.') }}</p>
                        </div>
                    @endif

                    <dl class="grid gap-3 sm:grid-cols-2 text-sm">
                        <div class="sm:col-span-2">
                            <dt class="text-gray-500">{{ __('Name') }}</dt>
                            <dd class="font-medium text-gray-900">{{ $fullName }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Registration number') }}</dt>
                            <dd class="font-mono text-gray-900">{{ $application->registration_number }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Gender') }}</dt>
                            <dd class="text-gray-900">{{ $application->gender ? __(ucfirst($application->gender)) : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Applied position') }}</dt>
                            <dd class="text-gray-900">{{ $application->effectivePositionLabel() ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">{{ __('Course') }}</dt>
                            <dd class="text-gray-900">{{ $application->course?->displayNameWithSession() ?? '—' }}</dd>
                        </div>
                        @if($application->company_name)
                            <div class="sm:col-span-2">
                                <dt class="text-gray-500">{{ __('Company') }}</dt>
                                <dd class="text-gray-900">{{ $application->company_name }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="text-gray-500">{{ __('Examination') }}</dt>
                            <dd class="text-gray-900">
                                {{ __('Passed') }}
                                @if($application->exam_score !== null)
                                    · {{ number_format((float) $application->exam_score, 2) }}%
                                @endif
                            </dd>
                        </div>
                        @if($isIssued)
                            <div>
                                <dt class="text-gray-500">{{ __('Certificate issued') }}</dt>
                                <dd class="text-gray-900">{{ $application->certificate_issued_at->format('Y-m-d') }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
