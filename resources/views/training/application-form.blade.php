<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Confirm Application') }} — {{ $course->name }} ({{ $course->session_year }})
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
                        <h3 class="font-medium text-gray-900">{{ __('Your details from registration') }}</h3>
                        <p class="mt-1 text-sm text-gray-600">{{ __('These details were saved when you registered. Review them before submitting your application.') }}</p>
                    </div>
                    <div class="p-6">
                        <x-trainee-profile-summary :user="$user" />

                        @if($user->canResubmitRegistration())
                            <p class="mt-4 text-sm text-amber-800 bg-amber-50 border border-amber-200 rounded-md p-3">
                                {{ __('If any detail is incorrect, update your registration application first.') }}
                                <a href="{{ route('registration.resubmit') }}" class="font-medium underline">{{ __('Update application') }}</a>
                            </p>
                        @else
                            <p class="mt-4 text-sm text-gray-500">
                                {{ __('Need to change personal details? Contact WRRB staff for assistance.') }}
                            </p>
                        @endif

                        <form method="POST" action="{{ route('training.apply.store') }}" class="mt-6 pt-6 border-t border-gray-200 space-y-4">
                            @csrf
                            <input type="hidden" name="course_id" value="{{ $course->id }}">

                            <label class="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="confirm_details" value="1"
                                       class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                       {{ old('confirm_details') ? 'checked' : '' }}
                                       required>
                                <span class="text-sm text-gray-700">{{ __('I confirm that the details shown above are correct and I wish to apply for this course.') }}</span>
                            </label>
                            <x-input-error :messages="$errors->get('confirm_details')" class="mt-1" />

                            <div class="flex items-center justify-between pt-2">
                                <a href="{{ route('training.select-course') }}" class="text-gray-600 hover:text-gray-900">
                                    {{ __('Back to courses') }}
                                </a>
                                <x-primary-button type="submit">{{ __('Submit Application') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 h-fit">
                    <h3 class="font-medium text-gray-900 mb-4">{{ __('Course') }}</h3>
                    <p class="text-lg font-semibold text-indigo-700">{{ $course->name }}</p>
                    @if($course->code)
                        <p class="text-sm text-gray-500 mt-1">{{ $course->code }}</p>
                    @endif
                    <p class="text-sm text-gray-600 mt-2">{{ __('Session') }}: <span class="font-medium">{{ $course->session_year }}</span></p>
                    <div class="mt-3 p-3 rounded-lg bg-gray-50 text-sm">
                        <p><span class="font-medium">{{ __('Applications open') }}:</span> {{ $course->formattedApplicationOpensAt() ?? '—' }}</p>
                        <p class="mt-1"><span class="font-medium">{{ __('Deadline') }}:</span> {{ $course->formattedApplicationDeadlineAt() ?? '—' }}</p>
                    </div>
                    @if($course->description)
                        <p class="text-gray-600 mt-3 text-sm">{{ $course->description }}</p>
                    @endif
                    <p class="mt-4 text-sm text-gray-500">{{ __('After you submit, you will receive a control number for payment.') }}</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
