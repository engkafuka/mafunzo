@props(['user', 'showEducation' => true])

<div {{ $attributes->merge(['class' => 'space-y-4']) }}>
    <dl class="grid gap-x-6 gap-y-3 sm:grid-cols-2">
        <div>
            <dt class="text-sm font-medium text-gray-500">{{ __('Name') }}</dt>
            <dd class="mt-0.5 text-gray-900">{{ trim($user->first_name.' '.($user->middle_name ?? '').' '.$user->last_name) }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500">{{ __('Email') }}</dt>
            <dd class="mt-0.5 text-gray-900">{{ $user->email }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500">{{ __('Phone') }}</dt>
            <dd class="mt-0.5 text-gray-900">{{ $user->phone ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500">{{ __('Region') }}</dt>
            <dd class="mt-0.5 text-gray-900">{{ $user->region ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500">{{ __('District') }}</dt>
            <dd class="mt-0.5 text-gray-900">{{ $user->district ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500">{{ __('Company / Private') }}</dt>
            <dd class="mt-0.5 text-gray-900">{{ $user->company_or_private ? __(ucfirst($user->company_or_private)) : '—' }}</dd>
        </div>
        @if($user->company_name)
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500">{{ __('Company name') }}</dt>
                <dd class="mt-0.5 text-gray-900">{{ $user->company_name }}</dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-sm font-medium text-gray-500">{{ __('Company address') }}</dt>
                <dd class="mt-0.5 text-gray-900">{{ $user->company_address ?? '—' }}</dd>
            </div>
        @endif
        <div>
            <dt class="text-sm font-medium text-gray-500">{{ __('Gender') }}</dt>
            <dd class="mt-0.5 text-gray-900">{{ $user->gender ? __(ucfirst($user->gender)) : '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500">{{ __('Date of birth') }}</dt>
            <dd class="mt-0.5 text-gray-900">{{ $user->date_of_birth?->format('Y-m-d') ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500">{{ __('Position') }}</dt>
            <dd class="mt-0.5 text-gray-900">{{ \App\Models\TrainingApplication::positionLabel($user->position) ?? '—' }}</dd>
        </div>
    </dl>

    @if($showEducation && $user->relationLoaded('educationBackgrounds') && $user->educationBackgrounds->isNotEmpty())
        <div class="pt-4 border-t border-gray-200">
            <h4 class="text-sm font-medium text-gray-900 mb-3">{{ __('Education background') }}</h4>
            <ul class="space-y-2">
                @foreach($user->educationBackgrounds as $eb)
                    <li class="text-sm text-gray-700">
                        {{ __(\App\Models\EducationBackground::levelOptions()[$eb->level] ?? $eb->level) }}
                        · {{ $eb->program === 'others' ? ($eb->program_other ?? __('Others')) : __(ucfirst($eb->program)) }}
                        · {{ $eb->institution }}
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
