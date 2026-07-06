<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Exam results') }}
        </h2>
    </x-slot>

    <div class="page-shell">
        <div class="page-inner-6xl">
            @if (session('status'))
                <div class="mb-4 p-4 rounded-md bg-green-50 text-green-800">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="mb-4 p-4 rounded-md bg-red-50 text-red-800">{{ session('error') }}</div>
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

            @if($courseId && isset($examStats))
                <div class="mb-6 grid gap-4 sm:grid-cols-3">
                    <div class="rounded-lg border border-gray-200 bg-white p-4">
                        <p class="text-xs font-medium uppercase text-gray-500">{{ __('Recorded') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-gray-900">{{ $examStats['recorded'] }}</p>
                    </div>
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <p class="text-xs font-medium uppercase text-amber-800">{{ __('Awaiting publish') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-amber-950">{{ $examStats['awaiting_publish'] }}</p>
                    </div>
                    <div class="rounded-lg border border-green-200 bg-green-50 p-4">
                        <p class="text-xs font-medium uppercase text-green-800">{{ __('Published') }}</p>
                        <p class="mt-1 text-2xl font-semibold text-green-950">{{ $examStats['published'] }}</p>
                    </div>
                </div>

                @if(($canPublish ?? false) && $examStats['recorded'] > 0)
                    <div class="mb-6 flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 bg-white p-4">
                        <a href="{{ route('app-management.exam-results.export.pdf', ['course_id' => $courseId]) }}"
                           class="inline-flex items-center px-4 py-2 bg-gray-700 text-white text-sm font-medium rounded-md hover:bg-gray-800">
                            {{ __('Download PDF') }}
                        </a>
                        <a href="{{ route('app-management.exam-results.export.excel', ['course_id' => $courseId]) }}"
                           class="inline-flex items-center px-4 py-2 bg-emerald-700 text-white text-sm font-medium rounded-md hover:bg-emerald-800">
                            {{ __('Download Excel') }}
                        </a>
                        @if($examStats['awaiting_publish'] > 0)
                            <form method="POST" action="{{ route('app-management.exam-results.publish') }}" class="inline"
                                  onsubmit="return confirm('{{ __('Publish examination results for this course? Trainees will be able to view their scores.') }}');">
                                @csrf
                                <input type="hidden" name="course_id" value="{{ $courseId }}">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                                    {{ __('Publish results to trainees') }}
                                </button>
                            </form>
                        @endif
                        <p class="text-sm text-gray-600 w-full sm:w-auto">
                            {{ __('Save scores first, download reports if needed, then publish when trainees should see their results.') }}
                        </p>
                    </div>
                @endif
            @endif

            @if($courseId && $applications->total() > 0)
                <form method="POST" action="{{ ($isTrainerPortal ?? false) ? route('trainer.exam-results.save') : route('app-management.exam-results.save') }}">
                    @csrf
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <x-responsive-table>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Registration') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Score (0-100)') }}</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Passed') }}</th>
                                    @if($canPublish ?? false)
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Publication') }}</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($applications as $app)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-mono" data-label="{{ __('Registration') }}">{{ $app->registration_number }}</td>
                                        <td class="px-4 py-3 text-sm" data-label="{{ __('Name') }}">{{ $app->first_name }} {{ $app->last_name }}</td>
                                        <td class="px-4 py-3" data-label="{{ __('Score (0-100)') }}">
                                            <input type="hidden" name="results[{{ $loop->index }}][id]" value="{{ $app->id }}">
                                            <input type="number" name="results[{{ $loop->index }}][exam_score]" min="0" max="100" step="0.01"
                                                   value="{{ old('results.'.$loop->index.'.exam_score', $app->exam_score) }}"
                                                   class="rounded-md border-gray-300 text-sm w-24">
                                        </td>
                                        <td class="px-4 py-3" data-label="{{ __('Passed') }}">
                                            <select name="results[{{ $loop->index }}][exam_passed]" class="rounded-md border-gray-300 text-sm">
                                                <option value="">{{ __('—') }}</option>
                                                <option value="1" {{ old('results.'.$loop->index.'.exam_passed', $app->exam_passed) === true ? 'selected' : '' }}>{{ __('Yes') }}</option>
                                                <option value="0" {{ old('results.'.$loop->index.'.exam_passed') === '0' || $app->exam_passed === false ? 'selected' : '' }}>{{ __('No') }}</option>
                                            </select>
                                        </td>
                                        @if($canPublish ?? false)
                                            <td class="px-4 py-3 text-sm" data-label="{{ __('Publication') }}">
                                                <span @class([
                                                    'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                                                    'bg-green-100 text-green-800' => $app->hasPublishedExamResults(),
                                                    'bg-amber-100 text-amber-800' => $app->isAwaitingExamResultsPublication(),
                                                    'bg-gray-100 text-gray-700' => ! $app->hasRecordedExamResults(),
                                                ])>
                                                    {{ $app->examPublicationStatusLabel() }}
                                                </span>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </x-responsive-table>
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
