<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Course Management') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
            @endif

            <div class="mb-4">
                <a href="{{ route('courses.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                    {{ __('Add Course') }}
                </a>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Course') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Session') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Application window') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Apps') }}</th>
                                <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($courses as $course)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        <div class="font-medium">{{ $course->name }}</div>
                                        @if($course->code)
                                            <div class="text-indigo-600 text-xs font-medium">{{ $course->code }}</div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $course->session_year }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600">
                                        @if($course->application_opens_at || $course->application_deadline_at)
                                            <div>{{ $course->formattedApplicationOpensAt() ?? '—' }}</div>
                                            <div class="text-xs text-gray-500">{{ __('to') }} {{ $course->formattedApplicationDeadlineAt() ?? '—' }}</div>
                                        @else
                                            <span class="text-gray-400">{{ __('Not set') }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex flex-col gap-1">
                                            @if($course->is_published)
                                                <span class="inline-flex w-fit px-2 py-0.5 text-xs font-medium rounded-full bg-green-100 text-green-800">{{ __('Published') }}</span>
                                            @else
                                                <span class="inline-flex w-fit px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-800">{{ __('Draft') }}</span>
                                            @endif
                                            @if($course->is_published)
                                                @if($course->applicationWindowStatus() === 'open')
                                                    <span class="inline-flex w-fit px-2 py-0.5 text-xs font-medium rounded-full bg-blue-100 text-blue-800">{{ __('Accepting applications') }}</span>
                                                @elseif($course->applicationWindowStatus() === 'upcoming')
                                                    <span class="inline-flex w-fit px-2 py-0.5 text-xs font-medium rounded-full bg-gray-100 text-gray-800">{{ __('Not yet open') }}</span>
                                                @else
                                                    <span class="inline-flex w-fit px-2 py-0.5 text-xs font-medium rounded-full bg-red-100 text-red-800">{{ __('Closed') }}</span>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-600">{{ $course->training_applications_count }}</td>
                                    <td class="px-4 py-3 text-right text-sm space-x-2">
                                        <a href="{{ route('courses.edit', $course) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">{{ __('Edit') }}</a>
                                        @if($course->is_published)
                                            <form method="POST" action="{{ route('courses.unpublish', $course) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-gray-600 hover:text-gray-800 font-medium">{{ __('Unpublish') }}</button>
                                            </form>
                                        @elseif($course->canBePublished())
                                            <form method="POST" action="{{ route('courses.publish', $course) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-green-600 hover:text-green-800 font-medium">{{ __('Publish') }}</button>
                                            </form>
                                        @endif
                                        @if($course->training_applications_count === 0)
                                            <form method="POST" action="{{ route('courses.destroy', $course) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to delete this course?') }}');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium">{{ __('Delete') }}</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">{{ __('No courses found. Add a course for trainees to apply.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($courses->hasPages())
                    <div class="px-4 py-3 border-t border-gray-200">{{ $courses->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
