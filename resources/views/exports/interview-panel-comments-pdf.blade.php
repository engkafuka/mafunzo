<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ __('Panel comments') }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h1 { font-size: 15px; margin: 0 0 4px; }
        h2 { font-size: 12px; margin: 16px 0 6px; }
        h3 { font-size: 11px; margin: 10px 0 4px; color: #333; }
        .meta { color: #555; margin-bottom: 12px; }
        .filters { margin-bottom: 10px; }
        .filters span { display: inline-block; margin-right: 12px; }
        .session-box { border: 1px solid #ccc; margin-bottom: 14px; page-break-inside: avoid; }
        .session-head { background: #f3f4f6; padding: 8px 10px; border-bottom: 1px solid #ccc; }
        .panelist-block { padding: 8px 10px; border-top: 1px solid #eee; }
        .comment-item { margin: 6px 0; padding: 6px 8px; background: #fafafa; border: 1px solid #e5e7eb; }
        .question-text { color: #666; font-size: 9px; margin-top: 3px; }
        .chair-notes { margin-top: 6px; padding: 6px 8px; background: #fffbeb; border: 1px solid #fcd34d; }
        .empty { text-align: center; color: #666; padding: 20px; }
    </style>
</head>
<body>
    <h1>{{ __('WRRB operator interview — panel comments') }}</h1>
    <p class="meta">
        {{ __('Generated') }}: {{ $generatedAt->format('Y-m-d H:i') }}
        · {{ __('Sessions') }}: {{ $sessions->count() }}
        · {{ $commentsOnly ? __('Comments only') : __('All questions') }}
    </p>

    @if(! empty($filters))
        <p class="filters">
            @foreach($filters as $label => $value)
                <span><strong>{{ $label }}:</strong> {{ $value }}</span>
            @endforeach
        </p>
    @endif

    @forelse($sessions as $session)
        @php($panelGroups = $grouped[$session->id] ?? collect())
        <div class="session-box">
            <div class="session-head">
                <strong>{{ $session->session_code }}</strong>
                — {{ $session->company?->name }}
                — {{ $session->interviewee_name }}
                ({{ $session->interview_date?->format('Y-m-d') }})
                @if(filled($session->result?->chair_notes))
                    <div class="chair-notes">
                        <strong>{{ __('Chair notes') }}:</strong> {{ $session->result->chair_notes }}
                    </div>
                @endif
            </div>

            @if($panelGroups->isEmpty())
                <p class="empty">{{ __('No panel comments for this session.') }}</p>
            @else
                @foreach($panelGroups as $panelistId => $scores)
                    @php($panelist = $scores->first()?->panelist)
                    <div class="panelist-block">
                        <h3>{{ $panelist?->name ?? __('Panelist') }} — {{ \App\Support\Interview\InterviewPanelCommentsReport::panelistRole($session, (int) $panelistId) }}</h3>
                        @foreach($scores as $score)
                            <div class="comment-item">
                                <strong>{{ __('Q:number', ['number' => $score->question?->sort_order]) }}</strong>
                                ({{ $score->question?->categoryLabel() }})
                                · {{ __('Score') }}: {{ $score->score ?? '—' }}
                                <div>{{ $score->comment ?: '—' }}</div>
                                @if($score->question?->question_text)
                                    <div class="question-text">{{ $score->question->question_text }}</div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            @endif
        </div>
    @empty
        <p class="empty">{{ __('No panel comments match the selected filters.') }}</p>
    @endforelse
</body>
</html>
