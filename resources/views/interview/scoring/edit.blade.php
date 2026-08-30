<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Score interview') }} · {{ $session->session_code }}</h2></x-slot>
    <div class="page-shell"><div class="page-inner-7xl">
        @include('interview._alerts')
        <p class="mb-4 text-sm text-gray-600">{{ $session->company->name }} · {{ $session->interviewee_name }}</p>

        @if($locked)
            <div class="mb-4 p-4 rounded-md bg-amber-50 text-amber-800">{{ __('Your scores are submitted and locked.') }}</div>
        @endif

        <form method="POST" action="{{ route('interview.scoring.update', $session) }}" class="space-y-6">
            @csrf @method('PUT')
            @foreach($session->questionSet->activeQuestions as $question)
                @php($row = $scores->get($question->id))
                <div class="bg-white shadow-sm sm:rounded-lg p-5">
                    <div class="text-xs uppercase text-gray-500 mb-1">{{ $question->categoryLabel() }}</div>
                    <div class="font-medium text-gray-900 mb-2">{{ $question->question_text }}</div>
                    @if($question->rubric)<p class="text-sm text-gray-600 mb-3">{{ $question->rubric }}</p>@endif
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">{{ __('Score (max :max)', ['max' => $question->max_mark]) }}</label>
                            <input type="number" step="0.01" min="0" max="{{ $question->max_mark }}" name="scores[{{ $question->id }}]" value="{{ old('scores.'.$question->id, $row?->score) }}" @disabled($locked) class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700">{{ __('Comment') }}</label>
                            <textarea name="comments[{{ $question->id }}]" rows="2" @disabled($locked) class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('comments.'.$question->id, $row?->comment) }}</textarea>
                        </div>
                    </div>
                </div>
            @endforeach

            @unless($locked)
                <div class="flex gap-3">
                    <button type="submit" name="submit" value="0" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-semibold">{{ __('Save draft') }}</button>
                    <button type="submit" name="submit" value="1" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold" onclick="return confirm('{{ __('Submit and lock your scores? This cannot be undone.') }}')">{{ __('Submit scores') }}</button>
                </div>
            @endunless
        </form>
    </div></div>
</x-app-layout>
