<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My examination results') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-4xl space-y-6">
            <div>
                <a href="{{ route('training.my-applications') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">{{ __('&larr; Back to my applications') }}</a>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h3 class="font-medium text-gray-900">{{ __('Published results') }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ __('Results appear here after WRRB staff records and an administrator publishes your examination marks.') }}</p>
                </div>
                <div class="p-6">
                    @forelse($publishedResults as $application)
                        <div @class(['border-b border-gray-200 pb-4 mb-4' => ! $loop->last])>
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div>
                                    <h4 class="font-medium text-gray-900">{{ $application->course->name }}</h4>
                                    <p class="mt-1 text-sm text-gray-500">
                                        {{ __('Session') }} {{ $application->course->session_year }}
                                        @if($application->registration_number)
                                            · {{ $application->registration_number }}
                                        @endif
                                    </p>
                                </div>
                                <span @class([
                                    'inline-flex rounded-full px-3 py-1 text-xs font-semibold',
                                    'bg-green-100 text-green-800' => $application->exam_passed === true,
                                    'bg-red-100 text-red-800' => $application->exam_passed === false,
                                    'bg-gray-100 text-gray-800' => $application->exam_passed === null,
                                ])>
                                    {{ $application->examResultStatusLabel() }}
                                </span>
                            </div>
                            <dl class="mt-4 grid gap-3 sm:grid-cols-3 text-sm">
                                <div>
                                    <dt class="font-medium text-gray-500">{{ __('Score') }}</dt>
                                    <dd class="mt-0.5 text-lg font-semibold text-gray-900">
                                        {{ $application->exam_score !== null ? number_format((float) $application->exam_score, 2).'%' : '—' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500">{{ __('Result') }}</dt>
                                    <dd class="mt-0.5 text-gray-900">{{ $application->examResultStatusLabel() }}</dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-500">{{ __('Published on') }}</dt>
                                    <dd class="mt-0.5 text-gray-900">{{ $application->exam_results_published_at?->format('Y-m-d H:i') }}</dd>
                                </div>
                            </dl>
                            @if($application->isEligibleForCertificate() && $application->certificate_issued_at)
                                <p class="mt-3 text-sm text-green-700">{{ __('Certificate issued on :date.', ['date' => $application->certificate_issued_at->format('Y-m-d')]) }}</p>
                            @elseif($application->exam_passed === true)
                                <p class="mt-3 text-sm text-gray-600">{{ __('You passed the examination. Certificate processing will follow staff review.') }}</p>
                            @elseif($application->exam_passed === false)
                                <p class="mt-3 text-sm text-gray-600">{{ __('Please contact WRRB staff for guidance on the next steps.') }}</p>
                            @endif
                        </div>
                    @empty
                        <p class="text-gray-500 text-sm">{{ __('No examination results have been published for your account yet.') }}</p>
                    @endforelse
                </div>
                <x-table-pagination :paginator="$publishedResults" />
            </div>

            @if($awaitingResults->total() > 0)
                <div class="bg-amber-50 border border-amber-200 shadow-sm sm:rounded-lg overflow-hidden">
                    <div class="px-6 py-4 border-b border-amber-200">
                        <h3 class="font-medium text-amber-900">{{ __('Awaiting examination results') }}</h3>
                    </div>
                    <div class="p-6 space-y-3">
                        @foreach($awaitingResults as $application)
                            <div class="flex flex-wrap items-center justify-between gap-2 text-sm">
                                <div>
                                    <p class="font-medium text-amber-950">{{ $application->course->name }}</p>
                                    <p class="text-amber-800">{{ __('Session') }} {{ $application->course->session_year }}</p>
                                </div>
                                <span class="text-amber-900">
                                    @if($application->hasRecordedExamResults())
                                        {{ __('Recorded — pending publication') }}
                                    @else
                                        {{ __('Results not yet published') }}
                                    @endif
                                </span>
                            </div>
                        @endforeach
                    </div>
                    <x-table-pagination :paginator="$awaitingResults" />
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
