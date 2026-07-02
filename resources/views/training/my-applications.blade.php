<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Training Applications') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                        <div class="border-b border-gray-200 last:border-0 py-4 first:pt-0 last:pb-0">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h3 class="font-medium text-gray-900">{{ $app->course->name }}</h3>
                                    <p class="text-sm text-gray-500 mt-1">
                                        {{ __('Session') }} {{ $app->course->session_year }} &middot;
                                        {{ $app->first_name }} {{ $app->last_name }} &middot;
                                        <span class="capitalize">{{ $app->status }}</span>
                                    </p>
                                </div>
                                <div class="flex items-center gap-2">
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
                                </div>
                            </div>
                            @if($app->status === 'payment_completed')
                                <p class="mt-2 text-sm text-gray-600">
                                    <span class="font-medium">{{ __('Examination') }}:</span>
                                    @if($app->hasPublishedExamResults())
                                        {{ $app->examResultStatusLabel() }}
                                        @if($app->exam_score !== null)
                                            · {{ number_format((float) $app->exam_score, 2) }}%
                                        @endif
                                    @else
                                        {{ __('Awaiting results') }}
                                    @endif
                                </p>
                            @endif
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
