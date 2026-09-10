<div class="space-y-4">
    @forelse($sessions as $session)
        @php($panelGroups = ($groupedBySession ?? collect())->get($session->id, collect()))
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 overflow-hidden">
            <div class="px-4 py-3 bg-gray-50 border-b border-gray-200">
                <div class="flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <h3 class="font-semibold text-gray-900">{{ $session->session_code }}</h3>
                        <p class="text-sm text-gray-600">
                            {{ $session->company?->name }} · {{ $session->interviewee_name }}
                            · {{ $session->interview_date?->format('Y-m-d') }}
                        </p>
                    </div>
                    <a href="{{ route('interview.reports.panel-comments', array_merge(request()->query(), ['view' => 'session', 'session_id' => $session->id])) }}"
                       class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">{{ __('Open session detail') }}</a>
                </div>
                @if(filled($session->result?->chair_notes))
                    <div class="mt-3 p-3 rounded-md bg-amber-50 border border-amber-100 text-sm text-amber-900">
                        <span class="font-medium">{{ __('Chair notes') }}:</span>
                        <span class="whitespace-pre-wrap">{{ $session->result->chair_notes }}</span>
                    </div>
                @endif
            </div>

            @if($panelGroups->isEmpty())
                <p class="px-4 py-6 text-sm text-gray-500">{{ __('No panel comments for this session.') }}</p>
            @else
                <div class="divide-y divide-gray-100">
                    @foreach($panelGroups as $panelistId => $scores)
                        @php($panelist = $scores->first()?->panelist)
                        <div class="p-4">
                            <h4 class="text-sm font-semibold text-gray-900">
                                {{ $panelist?->name ?? __('Panelist') }}
                                <span class="font-normal text-gray-500">— {{ \App\Support\Interview\InterviewPanelCommentsReport::panelistRole($session, (int) $panelistId) }}</span>
                            </h4>
                            <div class="mt-3 space-y-3">
                                @foreach($scores as $score)
                                    <div class="rounded-md border border-gray-100 bg-gray-50 p-3">
                                        <div class="text-xs uppercase text-gray-500 mb-1">
                                            {{ __('Q:number (:category)', ['number' => $score->question?->sort_order, 'category' => $score->question?->categoryLabel()]) }}
                                            · {{ __('Score') }}: {{ $score->score ?? '—' }}
                                        </div>
                                        <p class="text-sm text-gray-800 whitespace-pre-wrap">{{ $score->comment ?: '—' }}</p>
                                        @if($score->question?->question_text)
                                            <p class="mt-2 text-xs text-gray-500">{{ $score->question->question_text }}</p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @empty
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-6 text-center text-sm text-gray-500">
            {{ __('No sessions match the selected filters.') }}
        </div>
    @endforelse

    @if(isset($sessions) && $sessions->hasPages())
        <div class="bg-white shadow-sm sm:rounded-lg border border-gray-200 p-4">{{ $sessions->links() }}</div>
    @endif
</div>
