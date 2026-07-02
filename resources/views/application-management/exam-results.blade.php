<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Exam results') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif

            <div class="mb-4">
                <a href="{{ $isTrainerPortal ?? false ? route('dashboard') : route('app-management.index') }}" class="text-indigo-600 hover:text-indigo-800">
                    {{ ($isTrainerPortal ?? false) ? __('&larr; Back to dashboard') : __('&larr; Back to Application Management') }}
                </a>
            </div>

            <form method="GET" class="mb-6">
                <label class="text-sm font-medium text-gray-700">{{ __('Select course') }}</label>
                <select name="course_id" class="mt-1 rounded-md border-gray-300" onchange="this.form.submit()">
                    <option value="">{{ __('— Select course —') }}</option>
                    @foreach($courses as $c)
                        <option value="{{ $c->id }}" {{ ($courseId ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                    @endforeach
                </select>
            </form>

            @if($courseId && $applications->total() > 0)
                <form method="POST" action="{{ ($isTrainerPortal ?? false) ? route('trainer.exam-results.save') : route('app-management.exam-results.save') }}">
                    @csrf
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Registration') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Score (0-100)') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Passed') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($applications as $app)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-mono">{{ $app->registration_number }}</td>
                                        <td class="px-4 py-3 text-sm">{{ $app->first_name }} {{ $app->last_name }}</td>
                                        <td class="px-4 py-3">
                                            <input type="hidden" name="results[{{ $loop->index }}][id]" value="{{ $app->id }}">
                                            <input type="number" name="results[{{ $loop->index }}][exam_score]" min="0" max="100" step="0.01"
                                                   value="{{ old('results.'.$loop->index.'.exam_score', $app->exam_score) }}"
                                                   class="rounded-md border-gray-300 text-sm w-24">
                                        </td>
                                        <td class="px-4 py-3">
                                            <select name="results[{{ $loop->index }}][exam_passed]" class="rounded-md border-gray-300 text-sm">
                                                <option value="">{{ __('—') }}</option>
                                                <option value="1" {{ old('results.'.$loop->index.'.exam_passed', $app->exam_passed) === true ? 'selected' : '' }}>{{ __('Yes') }}</option>
                                                <option value="0" {{ old('results.'.$loop->index.'.exam_passed') === '0' || $app->exam_passed === false ? 'selected' : '' }}>{{ __('No') }}</option>
                                            </select>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <x-table-pagination :paginator="$applications" />
                        <div class="px-4 py-3 bg-gray-50 border-t">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm font-medium">{{ __('Save exam results') }}</button>
                        </div>
                    </div>
                </form>
            @elseif($courseId)
                <p class="text-gray-500">{{ __('No paid applications for this course.') }}</p>
            @else
                <p class="text-gray-500">{{ __('Select a course to upload exam results.') }}</p>
            @endif
        </div>
    </div>
</x-app-layout>
