<x-app-layout>
    <x-slot name="header"><h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $session->session_code }}</h2></x-slot>
    <div class="page-shell"><div class="page-inner-7xl">
        @include('interview._alerts') @include('interview._nav')

        <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6 space-y-3">
            <div class="flex flex-wrap justify-between gap-3">
                <div>
                    <div class="text-lg font-semibold text-gray-900">{{ $session->company->name }}</div>
                    <div class="text-sm text-gray-600">{{ $session->interviewee_name }} @if($session->interviewee_title)· {{ $session->interviewee_title }}@endif</div>
                </div>
                <div class="text-sm text-gray-600 text-right">
                    <div>{{ $session->statusLabel() }}</div>
                    <div>{{ $session->typeLabel() }}</div>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-700">
                <div>{{ __('Date') }}: {{ $session->interview_date?->format('Y-m-d') ?? '—' }} {{ $session->interview_time ? substr($session->interview_time, 0, 5) : '' }}</div>
                <div>{{ __('Venue') }}: {{ $session->venue ?: '—' }}</div>
                <div>{{ __('Pass mark') }}: {{ $session->pass_mark }}%</div>
            </div>
            <div class="text-sm text-gray-700">{{ __('Question set') }}: {{ $session->questionSet->title }} (v{{ $session->questionSet->version }})</div>
            @if($session->notes)<p class="text-sm text-gray-600">{{ $session->notes }}</p>@endif
        </div>

        <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
            <h3 class="font-medium mb-3">{{ __('Panelists') }}</h3>
            <ul class="divide-y divide-gray-100">
                @foreach($session->panelists as $panelist)
                    <li class="py-2 flex justify-between text-sm">
                        <span>{{ $panelist->user->name }} @if($panelist->is_chair)<span class="text-indigo-600">({{ __('Chair') }})</span>@endif</span>
                        <span class="{{ $panelist->submission_status === 'submitted' ? 'text-green-700' : 'text-amber-700' }}">
                            {{ $panelist->submission_status === 'submitted' ? __('Submitted') : __('Pending') }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="flex flex-wrap gap-3 mb-6">
            @php($myAssignment = $session->panelists->firstWhere('user_id', Auth::id()))
            @if($myAssignment && in_array($session->status, ['scoring', 'in_progress'], true))
                <a href="{{ route('interview.scoring.edit', $session) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md text-sm font-semibold">{{ __('Enter my scores') }}</a>
            @endif

            @if(in_array($session->status, ['under_review', 'completed'], true))
                <a href="{{ route('interview.review.show', $session) }}" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-semibold">{{ __('View consolidated results') }}</a>
                @if($session->result)
                    <a href="{{ route('interview.review.export.pdf', $session) }}" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-semibold">{{ __('Download PDF') }}</a>
                @endif
            @endif

            @if(Auth::user()->hasInterviewRole('admin'))
                @if(in_array($session->status, ['draft', 'scheduled', 'in_progress'], true))
                    <form method="POST" action="{{ route('interview.sessions.open-scoring', $session) }}">
                        @csrf
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-semibold">{{ __('Open scoring') }}</button>
                    </form>
                @endif
                @if($session->status !== 'completed')
                    <a href="{{ route('interview.sessions.edit', $session) }}" class="px-4 py-2 bg-white border border-gray-300 rounded-md text-sm font-semibold">{{ __('Edit session') }}</a>
                @endif
            @endif
        </div>

        @if($session->result)
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="font-medium mb-3">{{ __('Consolidated outcome') }}</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div><span class="text-gray-500">{{ __('Total') }}</span><div class="font-semibold">{{ $session->result->total_score }} / {{ $session->result->max_possible_score }}</div></div>
                    <div><span class="text-gray-500">{{ __('Percentage') }}</span><div class="font-semibold">{{ $session->result->percentage }}%</div></div>
                    <div><span class="text-gray-500">{{ __('Result') }}</span><div class="font-semibold">{{ $session->result->passed ? __('Pass') : __('Fail') }}</div></div>
                    <div><span class="text-gray-500">{{ __('Decision') }}</span><div class="font-semibold capitalize">{{ str_replace('_', ' ', $session->result->decision_status) }}</div></div>
                </div>
            </div>
        @endif
    </div></div>
</x-app-layout>
