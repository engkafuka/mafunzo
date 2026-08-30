<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $questionSet->title }}</h2></x-slot>
    <div class="page-shell"><div class="page-inner-7xl">
        @include('interview._alerts') @include('interview._nav')

        <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <div class="text-sm text-gray-600 mb-2">
                        {{ __('Version') }}: {{ $questionSet->version }} · {{ __('Max score') }}: {{ $questionSet->maxPossibleScore() }} · {{ __('Questions') }}: {{ $questionSet->questions->count() }}
                    </div>
                    @if($questionSet->description)<p class="text-sm text-gray-700">{{ $questionSet->description }}</p>@endif
                </div>
                <a href="{{ route('interview.question-sets.edit', $questionSet) }}" class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">{{ __('Edit set details') }}</a>
            </div>
        </div>

        @php
            // Open add form if validation failed on create (old input present without editing an existing question)
            $openAddForm = $errors->any() && ! old('editing_question_id');
        @endphp

        <div class="bg-white shadow-sm sm:rounded-lg mb-6" x-data="{ open: {{ $openAddForm ? 'true' : 'false' }} }">
            <button type="button"
                    @click="open = !open"
                    class="w-full px-6 py-4 flex items-center justify-between gap-3 text-left hover:bg-gray-50 rounded-lg">
                <div>
                    <h3 class="font-medium text-gray-900">{{ __('Add question') }}</h3>
                    <p class="text-xs text-gray-500 mt-0.5" x-show="!open">{{ __('Click to open the form') }}</p>
                </div>
                <span class="inline-flex items-center gap-1.5 shrink-0 px-3 py-1.5 rounded-md text-sm font-semibold"
                      :class="open ? 'bg-gray-100 text-gray-700' : 'bg-indigo-600 text-white'">
                    <svg class="w-4 h-4 transition-transform" :class="open && 'rotate-45'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <span x-text="open ? '{{ __('Close') }}' : '{{ __('Add question') }}'"></span>
                </span>
            </button>

            <div class="px-6 pb-6 border-t border-gray-100 pt-4" x-show="open" x-cloak>
                <form method="POST" action="{{ route('interview.question-sets.questions.store', $questionSet) }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Category') }}</label>
                            <select name="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                @foreach(config('interview.question_categories') as $key => $label)
                                    <option value="{{ $key }}" @selected(old('category') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Max mark') }}</label>
                            <input type="number" name="max_mark" value="{{ old('max_mark', 10) }}" min="1" max="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Sort order') }}</label>
                            <input type="number" name="sort_order" min="0" value="{{ old('sort_order') }}" placeholder="{{ __('Auto') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Question') }}</label>
                        <textarea name="question_text" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('question_text') }}</textarea>
                        <x-input-error :messages="$errors->get('question_text')" class="mt-2" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">{{ __('Rubric / guidance') }}</label>
                        <textarea name="rubric" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('rubric') }}</textarea>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">{{ __('Save question') }}</button>
                        <button type="button" @click="open = false" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700">{{ __('Cancel') }}</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
            <div class="px-4 py-3 border-b border-gray-200 font-medium text-gray-900">{{ __('Existing questions') }}</div>

            @forelse($questionSet->questions as $question)
                <div class="border-b border-gray-100 last:border-0" x-data="{ editing: {{ $errors->any() && (int) old('editing_question_id') === $question->id ? 'true' : 'false' }} }">
                    {{-- Summary row --}}
                    <div class="px-4 py-3 flex flex-wrap items-start justify-between gap-3" x-show="!editing">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2 mb-1">
                                <span class="text-xs font-medium text-gray-500">#{{ $question->sort_order }}</span>
                                <span class="inline-flex px-2 py-0.5 rounded-full bg-gray-100 text-gray-700 text-xs">{{ $question->categoryLabel() }}</span>
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $question->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $question->is_active ? __('Active') : __('Inactive') }}
                                </span>
                                <span class="text-xs text-gray-500">{{ __('Max') }}: {{ $question->max_mark }}</span>
                            </div>
                            <div class="text-sm text-gray-900">{{ $question->question_text }}</div>
                            @if($question->rubric)
                                <div class="mt-1 text-xs text-gray-500">{{ $question->rubric }}</div>
                            @endif
                        </div>
                        <button type="button" @click="editing = true"
                                class="shrink-0 px-3 py-1.5 text-sm font-medium text-indigo-600 hover:text-indigo-800 border border-indigo-200 rounded-md hover:bg-indigo-50">
                            {{ __('Edit') }}
                        </button>
                    </div>

                    {{-- Inline edit form --}}
                    <div class="px-4 py-4 bg-slate-50" x-show="editing" x-cloak>
                        <form method="POST"
                              action="{{ route('interview.question-sets.questions.update', [$questionSet, $question]) }}"
                              class="space-y-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="editing_question_id" value="{{ $question->id }}">

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Category') }}</label>
                                    <select name="category" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                        @foreach(config('interview.question_categories') as $key => $label)
                                            <option value="{{ $key }}" @selected(old('category', $question->category) === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Max mark') }}</label>
                                    <input type="number" name="max_mark" min="1" max="100"
                                           value="{{ old('max_mark', $question->max_mark) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ __('Sort order') }}</label>
                                    <input type="number" name="sort_order" min="0"
                                           value="{{ old('sort_order', $question->sort_order) }}"
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Question') }}</label>
                                <textarea name="question_text" rows="3" required
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('question_text', $question->question_text) }}</textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">{{ __('Rubric / guidance') }}</label>
                                <textarea name="rubric" rows="2"
                                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('rubric', $question->rubric) }}</textarea>
                            </div>

                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="is_active" value="1"
                                       @checked(old('is_active', $question->is_active))
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                {{ __('Active (included in scoring)') }}
                            </label>

                            <div class="flex flex-wrap gap-2">
                                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">
                                    {{ __('Update question') }}
                                </button>
                                <button type="button" @click="editing = false"
                                        class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-medium text-gray-700">
                                    {{ __('Cancel') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-500">{{ __('No questions yet. Add one above.') }}</div>
            @endforelse
        </div>
    </div></div>
</x-app-layout>
