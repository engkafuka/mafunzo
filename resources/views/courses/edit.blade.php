<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Course') }} — {{ $course->name }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-7xl space-y-6">
            @if (session('status'))
                <div class="p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <p class="text-sm text-gray-600">{{ __('Publication status') }}</p>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            @if($course->is_published)
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">{{ __('Published') }}</span>
                            @else
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-800">{{ __('Draft') }}</span>
                            @endif
                            <span class="text-sm text-gray-500">{{ __('Session') }}: {{ $course->session_year }}</span>
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($course->is_published)
                            <form method="POST" action="{{ route('courses.unpublish', $course) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                                    {{ __('Unpublish') }}
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('courses.publish', $course) }}">
                                @csrf
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700"
                                        @if(! $course->canBePublished()) disabled title="{{ __('Set session year and application dates first') }}" @endif>
                                    {{ __('Publish Course') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
                @if(! $course->canBePublished() && ! $course->is_published)
                    <p class="mt-3 text-sm text-amber-700">{{ __('Set session year, application open date, and deadline before publishing.') }}</p>
                @endif
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('courses.update', $course) }}" class="space-y-6">
                        @csrf
                        @method('PATCH')
                        @include('courses._form', ['course' => $course])
                        <div class="flex items-center justify-between pt-4">
                            <a href="{{ route('courses.index') }}" class="text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                            <x-primary-button type="submit">{{ __('Update Course') }}</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium text-gray-900">{{ __('Open new session (same course)') }}</h3>
                <p class="mt-1 text-sm text-gray-600">{{ __('Create a copy for a new intake year. You can set new dates and publish when ready.') }}</p>
                <form method="POST" action="{{ route('courses.new-session', $course) }}" class="mt-4 flex flex-wrap items-end gap-4">
                    @csrf
                    <div>
                        <x-input-label for="new_session_year" :value="__('New session year')" />
                        <x-text-input id="new_session_year" class="block mt-1 w-40" type="number" name="session_year"
                                      min="2000" max="2100" :value="old('session_year', $course->session_year + 1)" required />
                        <x-input-error :messages="$errors->get('session_year')" class="mt-2" />
                    </div>
                    <x-primary-button type="submit">{{ __('Create New Session') }}</x-primary-button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
