<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Apply for Training') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <p class="text-gray-600 mb-8 text-center">{{ __('Select a course to apply for training.') }}</p>

            <div class="grid grid-cols-2 grid-rows-2 gap-6">
                @forelse($courses->take(4) as $course)
                    <a href="{{ route('training.apply', ['course_id' => $course->id]) }}"
                       class="group flex flex-col bg-white rounded-2xl border border-gray-200 shadow-sm hover:shadow-xl hover:border-indigo-300 transition-all duration-300 overflow-hidden">
                        {{-- Card accent bar --}}
                        <div class="h-1.5 bg-gradient-to-r from-indigo-500 to-indigo-700"></div>
                        <div class="p-6 flex flex-col flex-1">
                            {{-- Icon --}}
                            <div class="w-12 h-12 rounded-xl bg-indigo-100 text-indigo-600 flex items-center justify-center mb-4 group-hover:bg-indigo-200 transition-colors">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 group-hover:text-indigo-700 transition-colors">{{ $course->name }}</h3>
                            @if($course->code)
                                <p class="text-sm text-indigo-600 font-medium mt-1">{{ $course->code }}</p>
                            @endif
                            @if($course->description)
                                <p class="text-sm text-gray-500 mt-2 flex-1">{{ Str::limit($course->description, 120) }}</p>
                            @else
                                <div class="flex-1"></div>
                            @endif
                            <span class="inline-flex items-center gap-2 mt-4 text-indigo-600 font-semibold text-sm group-hover:gap-3 transition-all">
                                {{ __('Apply') }}
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                                </svg>
                            </span>
                        </div>
                    </a>
                @empty
                    <div class="col-span-2 row-span-2 text-center py-12 bg-gray-50 rounded-2xl border border-gray-200 flex items-center justify-center">
                        <p class="text-gray-500">{{ __('No courses available at the moment.') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
