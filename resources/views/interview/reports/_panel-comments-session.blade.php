@if(! request()->filled('session_id'))
    <div class="bg-white shadow-sm sm:rounded-lg border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900">
        {{ __('Select a session from the filter above to open the session detail view.') }}
    </div>
@else
    <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-4 bg-gray-50 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">{{ $session->session_code }}</h3>
            <p class="text-sm text-gray-600 mt-1">
                {{ $session->company?->name }} · {{ $session->interviewee_name }}
                · {{ $session->interview_date?->format('Y-m-d') }}
            </p>
            @if($session->result)
                <p class="text-sm text-gray-600 mt-1">
                    {{ __('Result') }}:
                    {{ number_format((float) $session->result->percentage, 2) }}%
                    · {{ $session->result->passed ? __('Pass') : __('Fail') }}
                    · {{ str_replace('_', ' ', $session->result->decision_status) }}
                </p>
            @endif
            @if(filled($session->result?->chair_notes))
                <div class="mt-3 p-3 rounded-md bg-amber-50 border border-amber-100 text-sm text-amber-900">
                    <span class="font-medium">{{ __('Chair notes') }}:</span>
                    <span class="whitespace-pre-wrap">{{ $session->result->chair_notes }}</span>
                </div>
            @endif
        </div>

        @if($groupedScores->isEmpty())
            <p class="px-4 py-6 text-sm text-gray-500">{{ __('No panel comments for this session.') }}</p>
        @else
            <div class="divide-y divide-gray-100">
                @foreach($groupedScores as $panelistId => $scores)
                    @php($panelist = $scores->first()?->panelist)
                    <div class="p-4">
                        <h4 class="text-sm font-semibold text-gray-900">
                            {{ $panelist?->name ?? __('Panelist') }}
                            @if($panelist?->email)
                                <span class="block text-xs font-normal text-gray-500">{{ $panelist->email }}</span>
                            @endif
                            <span class="block text-xs font-normal text-indigo-700 mt-0.5">
                                {{ \App\Support\Interview\InterviewPanelCommentsReport::panelistRole($session, (int) $panelistId) }}
                            </span>
                        </h4>
                        <div class="mt-3 overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 border border-gray-100 rounded-md">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">{{ __('Q#') }}</th>
                                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">{{ __('Category') }}</th>
                                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">{{ __('Question') }}</th>
                                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">{{ __('Score') }}</th>
                                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500 min-w-[260px]">{{ __('Comment') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($scores as $score)
                                        <tr>
                                            <td class="px-3 py-2 text-sm">{{ $score->question?->sort_order ?? '—' }}</td>
                                            <td class="px-3 py-2 text-sm">{{ $score->question?->categoryLabel() ?? '—' }}</td>
                                            <td class="px-3 py-2 text-sm text-gray-600 max-w-xs">{{ $score->question?->question_text ?? '—' }}</td>
                                            <td class="px-3 py-2 text-sm">{{ $score->score ?? '—' }}</td>
                                            <td class="px-3 py-2 text-sm whitespace-pre-wrap">{{ $score->comment ?: '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endif
