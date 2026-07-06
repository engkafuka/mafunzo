<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Apply for Training') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl">
            @if (session('error'))
                <div class="mb-6 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif

            <p class="text-gray-600 mb-8 text-center">{{ __('Published training courses. Application dates are shown for each intake.') }}</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @forelse($courses as $course)
                    @php
                        $status = $course->applicationWindowStatus();
                        $canApply = $course->isAcceptingApplications();
                    @endphp
                    <div class="flex flex-col bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden {{ $canApply ? 'hover:shadow-xl hover:border-indigo-300' : '' }} transition-all duration-300">
                        <div class="h-1.5 bg-gradient-to-r from-indigo-500 to-indigo-700"></div>
                        <div class="p-6 flex flex-col flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-2 mb-4">
                                <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center shrink-0">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                    </svg>
                                </div>
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800">{{ __('Session') }} {{ $course->session_year }}</span>
                            </div>

                            <h3 class="text-lg font-semibold text-gray-900">{{ $course->name }}</h3>
                            @if($course->code)
                                <p class="text-sm text-indigo-600 font-medium mt-1">{{ $course->code }}</p>
                            @endif

                            <div class="mt-3 p-3 rounded-lg bg-gray-50 border border-gray-100 text-sm">
                                <p class="text-gray-700">
                                    <span class="font-medium">{{ __('Applications open') }}:</span>
                                    {{ $course->formattedApplicationOpensAt() ?? __('TBA') }}
                                </p>
                                <p class="text-gray-700 mt-1">
                                    <span class="font-medium">{{ __('Deadline') }}:</span>
                                    {{ $course->formattedApplicationDeadlineAt() ?? __('TBA') }}
                                </p>
                            </div>

                            @if($course->description)
                                <p class="text-sm text-gray-500 mt-3 flex-1">{{ \Illuminate\Support\Str::limit($course->description, 120) }}</p>
                            @else
                                <div class="flex-1"></div>
                            @endif

                            <div class="mt-4">
                                @if($pendingApplications->has($course->id))
                                    <a href="{{ route('training.payment', $pendingApplications->get($course->id)) }}"
                                       class="inline-flex items-center gap-2 text-amber-700 font-semibold text-sm hover:gap-3 transition-all">
                                        {{ __('Complete pending application') }}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                        </svg>
                                    </a>
                                @elseif($canApply)
                                    <a href="{{ route('training.apply', ['course_id' => $course->id]) }}"
                                       class="inline-flex items-center gap-2 text-indigo-600 font-semibold text-sm hover:gap-3 transition-all">
                                        {{ __('Apply now') }}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                        </svg>
                                    </a>
                                @elseif($status === 'upcoming')
                                    <p class="text-sm text-amber-700 font-medium">{{ __('Applications open on :date', ['date' => $course->formattedApplicationOpensAt()]) }}</p>
                                @elseif($status === 'closed')
                                    <p class="text-sm text-red-700 font-medium">{{ __('Application deadline has passed.') }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full text-center py-12 bg-gray-50 rounded-2xl border border-gray-200">
                        <p class="text-gray-500">{{ __('No published courses available at the moment.') }}</p>
                    </div>
                @endforelse
            </div>
            <x-table-pagination :paginator="$courses" />
        </div>
    </div>
</x-app-layout>
