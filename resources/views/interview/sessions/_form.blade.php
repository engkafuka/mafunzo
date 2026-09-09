@php
    $session = $session ?? null;
    $selectedPanelists = old('panelist_ids', $session ? $session->panelists->pluck('user_id')->all() : []);
    $chairId = old('chair_user_id', $session?->panelists->firstWhere('is_chair', true)?->user_id);
@endphp

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">{{ __('Company') }}</label>
        <select name="company_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @foreach($companies as $company)
                <option value="{{ $company->id }}" @selected(old('company_id', $session?->company_id) == $company->id)>{{ $company->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">{{ __('Question set') }}</label>
        <select name="question_set_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @foreach($questionSets as $set)
                <option value="{{ $set->id }}" @selected(old('question_set_id', $session?->question_set_id) == $set->id)>{{ $set->title }} (v{{ $set->version }})</option>
            @endforeach
        </select>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">{{ __('Interviewee name') }}</label>
        <input type="text" name="interviewee_name" value="{{ old('interviewee_name', $session?->interviewee_name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">{{ __('Interviewee title') }}</label>
        <input type="text" name="interviewee_title" value="{{ old('interviewee_title', $session?->interviewee_title) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">{{ __('Interview type') }}</label>
        <select name="interview_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
            @foreach(config('interview.interview_types') as $key => $label)
                <option value="{{ $key }}" @selected(old('interview_type', $session?->interview_type ?? 'new_operator') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">{{ __('Date') }}</label>
        <input type="date" name="interview_date" value="{{ old('interview_date', $session?->interview_date?->format('Y-m-d')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">{{ __('Time') }}</label>
        <input type="time" name="interview_time" value="{{ old('interview_time', $session?->interview_time ? substr($session->interview_time, 0, 5) : null) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">{{ __('Venue') }}</label>
        <input type="text" name="venue" value="{{ old('venue', $session?->venue) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">{{ __('Pass mark (%)') }}</label>
        <input type="number" step="0.01" name="pass_mark" value="{{ old('pass_mark', $session?->pass_mark ?? config('interview.default_pass_mark')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700">{{ __('Notes') }}</label>
    <textarea name="notes" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">{{ old('notes', $session?->notes) }}</textarea>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700 mb-2">{{ __('Panelists') }}</label>
    <div class="space-y-2 max-h-48 overflow-y-auto border border-gray-200 rounded-md p-3">
        @forelse($panelistCandidates as $user)
            @php($isInactive = ! $user->isInterviewStatusActive())
            <label class="flex items-center gap-2 text-sm {{ $isInactive ? 'text-gray-400' : '' }}">
                <input type="checkbox" name="panelist_ids[]" value="{{ $user->id }}"
                       @checked(in_array($user->id, $selectedPanelists))
                       @disabled($isInactive && ! in_array($user->id, $selectedPanelists))>
                <span>
                    {{ $user->name }} ({{ $user->email }})
                    @if($isInactive)
                        <span class="text-xs text-red-600 font-medium">({{ __('Inactive') }})</span>
                    @endif
                </span>
            </label>
        @empty
            <p class="text-sm text-gray-500">{{ __('Assign panelist or chair roles under Module roles first.') }}</p>
        @endforelse
    </div>
</div>

<div>
    <label class="block text-sm font-medium text-gray-700">{{ __('Chairperson') }}</label>
    <select name="chair_user_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
        <option value="">{{ __('None selected') }}</option>
        @foreach($panelistCandidates as $user)
            @php($isInactive = ! $user->isInterviewStatusActive())
            <option value="{{ $user->id }}"
                    @selected((string) $chairId === (string) $user->id)
                    @disabled($isInactive && (string) $chairId !== (string) $user->id)>
                {{ $user->name }}@if($isInactive) ({{ __('Inactive') }})@endif
            </option>
        @endforeach
    </select>
</div>
