<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Training Applications') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            <div class="mb-4">
                <a href="{{ route('training.select-course') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    {{ __('Apply for new training') }}
                </a>
            </div>

            @if (session('error'))
                <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @forelse($applications as $app)
                        @php
                            $status = \App\Support\TraineeProgressTracker::currentStatus($app);
                            $toneClasses = match ($status['tone']) {
                                'complete' => 'bg-green-50 text-green-800 border-green-200',
                                'current' => 'bg-[#0a71ab]/10 text-[#0a71ab] border-[#0a71ab]/30',
                                'waiting' => 'bg-amber-50 text-amber-900 border-amber-200',
                                'rejected' => 'bg-red-50 text-red-800 border-red-200',
                                default => 'bg-gray-50 text-gray-700 border-gray-200',
                            };
                        @endphp
                        <div class="border-b border-gray-200 last:border-0 py-4 first:pt-0 last:pb-0">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <h3 class="font-medium text-gray-900">{{ $app->course->name }}</h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ __('Session') }} {{ $app->course->session_year }} &middot;
                                        {{ $app->first_name }} {{ $app->last_name }}
                                    </p>
                                    <span class="mt-2 inline-flex items-center rounded-md border px-2.5 py-1 text-xs font-medium {{ $toneClasses }}">
                                        {{ $status['label'] }}
                                    </span>
                                    @if($app->hasPublishedExamResults())
                                        <p class="mt-2 text-sm text-gray-600">
                                            <span class="font-medium">{{ __('Examination') }}:</span>
                                            {{ $app->examResultStatusLabel() }}
                                            @if($app->exam_score !== null)
                                                · {{ number_format((float) $app->exam_score, 2) }}%
                                            @endif
                                        </p>
                                    @endif
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    @if($app->control_number)
                                        <span class="text-sm font-mono text-gray-600">{{ __('Control') }}: {{ $app->control_number }}</span>
                                    @endif
                                    @if($app->status === 'pending_payment')
                                        <a href="{{ route('training.payment', $app) }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">
                                            {{ __('Pay / Confirm') }}
                                        </a>
                                    @endif
                                    @if($app->registration_number)
                                        <span class="text-sm font-mono font-semibold text-green-600">{{ $app->registration_number }}</span>
                                    @endif
                                    @if($app->hasPublishedExamResults())
                                        <a href="{{ route('training.exam-results') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ __('View exam result') }}
                                        </a>
                                    @endif
                                    @if($app->warehouseIdentityCard?->isPublished())
                                        <a href="{{ route('training.identity-cards') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                                            {{ __('View ID card') }}
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500">{{ __('You have no training applications yet.') }}</p>
                        <p class="mt-2">
                            <a href="{{ route('training.select-course') }}" class="text-indigo-600 hover:text-indigo-800">{{ __('Apply for training') }}</a>
                        </p>
                    @endforelse
                </div>
                <x-table-pagination :paginator="$applications" class="border-t-0" />
            </div>
        </div>
    </div>
</x-app-layout>
